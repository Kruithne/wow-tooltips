<?php
	class EncounterHandler {
		/**
		 * @param InstanceHandler $instanceHandler
		 * @param ArrayObject $encounter
		 * @param ArrayObject $instance
		 * @param ArrayObject $difficulty
		 */
		public function __construct($instanceHandler, $encounter, $instance, $difficulty) {
			$this->instanceHandler = $instanceHandler;
			$this->dataPath = $instanceHandler->dataPath;
			$this->response = $instanceHandler->response;
			$this->instance = $instance;
			$this->encounter = $encounter;
			$this->difficulty = $difficulty;
			$this->db = $instanceHandler->db;
		}

		/**
		 * @param Array $finalDataPath
		 */
		public function handleDataPath(&$finalDataPath) {
			$this->encounter->showOverviewTab = $this->encounter->first_section_id > 0;
			$this->encounter->hasOverview = $this->checkIfOverviewSectionsExist();
			$this->response->encounter = $this->encounter;
			$this->response->renderMode |= RENDER_MODE_ENCOUNTER;

			array_push($finalDataPath, $this->encounter->name_slug);

			$operation = array_shift($this->dataPath);

			// If an encounter has no sections (first_section_id == 0) then the
			// Overview tab should not be shown. If it does, the sections must be
			// checked to see if any are flagged as "overview". If an encounter
			// contains overview sections, then those sections should be displayed
			// under the Overview tab, and the other sections under Abilities tab.
			// If no sections are flagged as "overview", then all sections are
			// displayed under the Overview tab and the Abilities tab is hidden.

			if ($operation === 'model')
				return $this->provideModelTab($finalDataPath);

			if ($operation === 'map')
				return $this->provideMapTab($finalDataPath);

			if ($operation === 'abilities' && $this->encounter->hasOverview)
				return $this->provideAbilitiesTab($finalDataPath);

			if ($operation === 'loot') // TODO: Check if we have loot?
				return $this->provideLootTab($finalDataPath);

			// Handle cascading default behavior.
			if ($this->encounter->showOverviewTab)
				return $this->provideOverviewTab();

			if ($this->encounter->hasOverview)
				return $this->provideAbilitiesTab($finalDataPath);

			// TODO: Check if we have loot?
			return $this->provideLootTab($finalDataPath);
		}

		/**
		 * @param array &$finalDataPath
		 */
		private function provideModelTab(&$finalDataPath) {
			$this->response->encounter->models = $this->getEncounterModels();
			$this->response->renderMode |= RENDER_MODE_MODEL;
			$this->response->operation = 'model';
			array_push($finalDataPath, 'model');
		}

		/**
		 * @param array &$finalDataPath
		 */
		private function provideLootTab(&$finalDataPath) {
			$provider = new DataProvider($this->db, $this->difficulty->id ?? 0);
			$items = $provider->getItemDataProvider();

			$this->response->lootTable = $items->getLootTableForEncounter($this->encounter->id);
			$this->response->renderMode |= RENDER_MODE_LOOT;
			$this->response->operation = 'loot';
			array_push($finalDataPath, 'loot');
		}

		/**
		 * @param array &$finalDataPath
		 */
		private function provideMapTab(&$finalDataPath) {
			$this->response->mapLevels = $this->instanceHandler->getMapLevels($this->instance->id, $this->difficulty);
			$this->response->mapPins = $this->instanceHandler->getMapPins($this->instance->id, $this->difficulty);
			$this->response->renderMode |= RENDER_MODE_MAP;
			$this->response->operation = 'map';
			array_push($finalDataPath, 'map');
		}

		/**
		 * @param array &$finalDataPath
		 */
		private function provideAbilitiesTab(&$finalDataPath) {
			$this->response->encounter->overview = $this->getOverview(false);
			$this->response->renderMode |= RENDER_MODE_ABILITIES;
			$this->response->operation = 'abilities';
			array_push($finalDataPath, 'abilities');
		}

		private function provideOverviewTab() {
			$this->response->encounter->overview = $this->getOverview(true);
			$this->response->renderMode |= RENDER_MODE_OVERVIEW;
		}

		/**
		 * @return boolean
		 */
		private function checkIfOverviewSectionsExist() {
			$query = $this->db->getRow(
				'SELECT COUNT(*) AS `count` FROM journal_encounter_section AS a LEFT JOIN journal_section_difficulty AS b ON a.id = b.section_id WHERE a.journal_encounter_id = :encounter AND a.`type` = 3 AND (a.flags & 0x2 = 0 OR b.difficulty_id = :difficulty) ORDER BY a.order_index ASC',
				['encounter' => $this->encounter->id, 'difficulty' => $this->difficulty->id ?? null], ['count' => DB::FIELD_TYPE_INT]
			);

			return $query->count > 0;
		}

		/**
		 * @return Array
		 */
		private function getEncounterModels() {
			return $this->db->getAll(
				'SELECT `id`, `name`, `description`, `creature_display_info_id` FROM `journal_encounter_creature` WHERE `journal_encounter_id` = ? ORDER BY `order_index` ASC',
				[$this->encounter->id], ['id' => DB::FIELD_TYPE_INT, 'creature_display_info_id' => DB::FIELD_TYPE_INT]
			);
		}

		/**
		 * @param boolean $isOverview
		 * @return ArrayObject
		 */
		private function getOverview($isOverview = true) {
			$overview = new \ArrayObject([], \ArrayObject::ARRAY_AS_PROPS);

			$encounterRow = $this->db->getRow('SELECT a.`description` FROM `journal_encounter` AS a WHERE a.`id` = :id', ['id' => $this->encounter->id]);
			$overview->description = $encounterRow->description ?? '';

			$typeCheck = $this->encounter->hasOverview ? 'AND a.`type` ' . ($isOverview ? '= 3' : '!= 3') : '';
			$sections = $this->db->getAll(
				'SELECT a.`id`, a.`title`, a.`text`, a.`parent_section_id`, a.`type`, a.`icon_display_id`, a.`spell_id`, a.`icon_fid`, a.`flags`, a.`icon_flags` FROM journal_encounter_section AS a LEFT JOIN journal_section_difficulty AS b ON a.id = b.section_id WHERE a.journal_encounter_id = :encounter AND (a.flags & 0x2 = 0 OR b.difficulty_id = :difficulty) ' . $typeCheck . ' ORDER BY a.order_index ASC',
				['encounter' => $this->encounter->id, 'difficulty' => $this->difficulty->id ?? null],
				['id' => DB::FIELD_TYPE_INT, 'parent_section_id' => DB::FIELD_TYPE_INT, 'type' => DB::FIELD_TYPE_INT, 'icon_display_id' => DB::FIELD_TYPE_INT, 'flags' => DB::FIELD_TYPE_INT, 'icon_flags' => DB::FIELD_TYPE_INT, 'icon_fid' => DB::FIELD_TYPE_INT, 'spell_id' => DB::FIELD_TYPE_INT]
			);

			$provider = new DataProvider($this->db, $this->difficulty->id ?? 0);
			$spells = $provider->getSpellDataProvider();

			$overview->sections = [];
			foreach ($sections as $section) {
				if ($section->spell_id > 0) {
					$section->spell = $spells->getSpellInfo($section->spell_id);

					// Since spell description will overwrite the section description, parse it.
					$tokenizer = new SpellTooltipTokenizer($section->spell->description);
					$renderer = new SpellTooltipRenderer($tokenizer->parse(), $section->spell_id, $provider);
					$section->spell->description = $renderer->render(false);
				} else {
					// We only need to parse the section description if we don't have a spell
					// description, since the latter will override the former.
					$tokenizer = new SpellTooltipTokenizer($section->text);
					$renderer = new SpellTooltipRenderer($tokenizer->parse(), $section->spell_id, $provider);
					$section->text = $renderer->render(false);
				}

				if ($section->parent_section_id === 0) {
					\array_push($overview->sections, $section);
				} else {
					$parentSectionIndex = \array_search($section->parent_section_id, \array_column($sections, 'id'));
					$parentSection = $sections[$parentSectionIndex];

					if (isset($parentSection->children))
						\array_push($parentSection->children, $section);
					else
						$parentSection->children = [$section];
				}
			}

			return $overview;
		}

		/**
		 * @var InstanceHandler
		 */
		private $instanceHandler;

		/**
		 * @var ArrayObject
		 */
		private $instance;

		/**
		 * @var ArrayObject
		 */
		private $encounter;

		/**
		 * @var ArrayObject
		 */
		private $difficulty;

		/**
		 * @var Database
		 */
		private $db;

		/**
		 * @var JSONFile
		 */
		private $response;

		/**
		 * @var Array
		 */
		private $dataPath;
	}