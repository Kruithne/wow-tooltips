<?php
	define('INSTANCE_TYPE_DUNGEON', 'dungeons');
	define('INSTANCE_TYPE_RAID', 'raids');

	define('INSTANCE_TYPE_ID', [
		INSTANCE_TYPE_DUNGEON => 1,
		INSTANCE_TYPE_RAID => 2
	]);

	class InstanceHandler {
		/**
		 * @param Array $dataPath
		 * @param JSONFile $response
		 * @param ArrayObject $journalTier
		 * @param Database $db
		 */
		public function __construct($dataPath, $response, $journalTier, $db) {
			$this->dataPath = $dataPath;
			$this->response = $response;
			$this->journalTier = $journalTier;
			$this->db = $db;
		}

		/**
		 * @param Array $finalDataPath
		 */
		public function handleDataPath(&$finalDataPath) {
			$operation = array_shift($this->dataPath);

			if ($operation === null || strlen(trim($operation)) === 0)
				$operation = INSTANCE_TYPE_DUNGEON;

			$journalTierID = null;
			if ($this->journalTier !== null)
				$journalTierID = $this->journalTier->id;

			if ($operation !== INSTANCE_TYPE_DUNGEON && $operation !== INSTANCE_TYPE_RAID) {
				$instance = $this->getInstanceFromSlug($operation);
				if ($instance) {
					$this->response->instance = $instance;
					$this->response->renderMode = RENDER_MODE_INSTANCE;
					$this->response->instanceType = array_search($instance->instance_type, INSTANCE_TYPE_ID) ?? INSTANCE_TYPE_DUNGEON;

					array_push($finalDataPath, $instance->name_slug);

					$difficulties = $this->getDifficultiesForInstance($instance);
					$selectedDifficulty = null;

					if (count($difficulties)) {
						$this->response->difficulties = $difficulties;

						$selectedDifficulty = $difficulties[0] ?? null;
						$requestDifficulty = array_shift($this->dataPath);

						if ($requestDifficulty !== null && count($difficulties)) {
							$difficultyIndex = array_search($requestDifficulty, array_column($difficulties, 'name_slug'));
							if ($difficultyIndex !== false)
								$selectedDifficulty = $difficulties[$difficultyIndex];
						}

						if ($selectedDifficulty !== null) {
							$this->response->selectedDifficulty = $selectedDifficulty->id;
							array_push($finalDataPath, $selectedDifficulty->name_slug);
						}
					}

					$this->response->encounters = $this->getEncounterList($instance->id, $selectedDifficulty);

					$operation = array_shift($this->dataPath);

					if ($operation === 'loot') {
						$provider = new DataProvider($this->db, $selectedDifficulty->id ?? 0);
						$items = $provider->getItemDataProvider();

						$this->response->lootTable = $items->getLootTableForInstance($instance->id);
						$this->response->renderMode |= RENDER_MODE_LOOT;
						$this->response->operation = $operation;

						array_push($finalDataPath, $operation);
					} else if ($operation === 'map') {
						$this->response->renderMode |= RENDER_MODE_MAP;
						$this->response->mapLevels = $this->getMapLevels($instance->id, $selectedDifficulty);
						$this->response->mapPins = $this->getMapPins($instance->id, $selectedDifficulty);
						$this->response->operation = $operation;
						array_push($finalDataPath, $operation);
					} else if ($operation !== null) {
						$encounter = $this->getEncounterFromSlug($operation, $instance, $selectedDifficulty);
						if ($encounter) {
							$encounterHandler = new EncounterHandler($this, $encounter, $instance, $selectedDifficulty);
							$encounterHandler->handleDataPath($finalDataPath);

							$this->response->renderMode &= $this->response->renderMode ^ RENDER_MODE_INSTANCE;
						} else {
							$operation = null;
						}
					}

					if ($operation === null) {
						$this->response->instance->description = $this->getInstanceDescription($instance->id);
						$this->response->renderMode |= RENDER_MODE_OVERVIEW;
					}
				} else {
					// Invalid instance, default back to dungeon list.
					$operation = INSTANCE_TYPE_DUNGEON;
				}
			}

			if ($operation === INSTANCE_TYPE_DUNGEON || $operation === INSTANCE_TYPE_RAID) {
				$this->response->instances = $this->getInstances($journalTierID, INSTANCE_TYPE_ID[$operation]);
				$this->response->instanceType = $operation;
				array_push($finalDataPath, $operation);
			}
		}

		/**
		 * @param string $slugName
		 * @param ArrayObject $instance
		 * @param ArrayObject $difficulty
		 * @return ArrayObject|null
		 */
		private function getEncounterFromSlug($slugName, $instance, $difficulty) {
			return $this->db->getRow(
				'SELECT a.`id`, a.`name`, a.`name_slug`, a.`first_section_id`, a.`ui_map_id` FROM `journal_encounter` AS a LEFT JOIN `journal_encounter_difficulty` AS b ON a.`id` = b.`journal_encounter_id` LEFT JOIN `journal_encounter_creature` AS c ON c.`journal_encounter_id` = a.`id` AND c.`order_index` = 0 WHERE a.`instance_id` = :instance AND a.`flags` & 16 = 0 AND (a.`flags` & 0x2 = 0 OR (:difficulty IS NOT NULL and b.`difficulty` = :difficulty)) AND a.`name_slug` = :slug',
				['slug' => $slugName, 'instance' => $instance->id, 'difficulty' => $difficulty->id ?? null], ['id' => DB::FIELD_TYPE_INT, 'first_section_id' => DB::FIELD_TYPE_INT, 'ui_map_id' => DB::FIELD_TYPE_INT]
			);
		}

		/**
		 * @param int $instanceID
		 * @param ArrayObject|null $difficulty
		 * @return Array
		 */
		public function getMapLevels($instanceID, $difficulty) {
			$uiMapID = $this->db->getRow(
				'SELECT IFNULL((SELECT a.`ui_map_id` FROM `journal_encounter` AS a LEFT JOIN `journal_encounter_difficulty` AS b ON b.`journal_encounter_id` = a.`id` WHERE a.`flags` & 16 = 0 AND (a.`difficulty` = -1 OR (:difficulty IS NOT NULL AND b.`difficulty` = :difficulty)) AND a.`instance_id` = :instance ORDER BY a.`order_index` ASC LIMIT 1), 0) AS `ui_map_id`',
				['difficulty' => $difficulty->id ?? null, 'instance' => $instanceID], ['ui_map_id' => DB::FIELD_TYPE_INT]
			)->ui_map_id;

			if ($uiMapID === 0)
				return [];

			$groupMembers = $this->db->getAll(
				'SELECT a.`id`, a.`name`, d.`layer_width`, d.`layer_height`, d.`tile_width`, d.`tile_height`, a.`ui_map_id` FROM `ui_map_group_member` AS a LEFT JOIN `ui_map_x_map_art` AS b ON b.`ui_map_id` = a.`ui_map_id` LEFT JOIN `ui_map_art` AS c ON c.`id` = b.`ui_map_art_id` LEFT JOIN `ui_map_art_style_layer` AS d ON d.`ui_map_art_style_id` = c.`ui_map_art_style_id` WHERE `ui_map_group_id` = (SELECT `ui_map_group_id` FROM `ui_map_group_member` WHERE `ui_map_id` = :mapID) ORDER BY a.`floor_index` ASC',
				['mapID' => $uiMapID], ['id' => DB::FIELD_TYPE_INT, 'layer_width' => DB::FIELD_TYPE_INT, 'layer_height' => DB::FIELD_TYPE_INT, 'tile_width' => DB::FIELD_TYPE_INT, 'tile_height' => DB::FIELD_TYPE_INT, 'ui_map_id' => DB::FIELD_TYPE_INT]
			);

			if (!count($groupMembers)) {
				$groupMembers = $this->db->getAll(
					'SELECT a.`id` AS `ui_map_id`, a.`name`, d.`layer_width`, d.`layer_height`, d.`tile_width`, d.`tile_height` FROM `ui_map` AS a LEFT JOIN `ui_map_x_map_art` AS b ON b.`ui_map_id` = a.`id` LEFT JOIN `ui_map_art` AS c ON c.`id` = b.`ui_map_art_id` LEFT JOIN `ui_map_art_style_layer` AS d ON d.`ui_map_art_style_id` = c.`ui_map_art_style_id` WHERE a.`id` = :mapID',
					['mapID' => $uiMapID], ['ui_map_id' => DB::FIELD_TYPE_INT, 'layer_width' => DB::FIELD_TYPE_INT, 'layer_height' => DB::FIELD_TYPE_INT, 'tile_width' => DB::FIELD_TYPE_INT, 'tile_height' => DB::FIELD_TYPE_INT]
				);
			}

			foreach ($groupMembers as $groupMember) {
				$groupMember->tiles = $this->db->getAll(
					'SELECT `file_data_id`, `row`, `col` FROM `ui_map_art_tile` WHERE `ui_map_art_id` = (SELECT `ui_map_art_id` FROM `ui_map_x_map_art` WHERE `ui_map_id` = ?)',
					[$groupMember->ui_map_id], ['file_data_id' => DB::FIELD_TYPE_INT, 'row' => DB::FIELD_TYPE_INT, 'col' => DB::FIELD_TYPE_INT]
				);
			}

			return $groupMembers;
		}

		/**
		 * @param int $instanceID
		 * @param ArrayObject|null $difficulty
		 * @return Array
		 */
		public function getMapPins($instanceID, $difficulty) {
			return $this->db->getAll(
				'SELECT a.`id`, a.`name`, a.`name_slug`, a.`description`, a.`ui_map_id`, a.`map_x`, a.`map_y`, c.`creature_display_info_id` FROM `journal_encounter` AS a LEFT JOIN `journal_encounter_difficulty` AS b ON b.`journal_encounter_id` = a.`id` LEFT JOIN `journal_encounter_creature` AS c ON c.`journal_encounter_id` = a.`id` AND c.`order_index` = 0 WHERE a.`flags` & 16 = 0 AND (a.`difficulty` = -1 OR (:difficulty IS NOT NULL AND b.`difficulty` = :difficulty)) AND a.`instance_id` = :instance ORDER BY a.`order_index` ASC',
				['difficulty' => $difficulty->id ?? null, 'instance' => $instanceID], ['id' => DB::FIELD_TYPE_INT, 'ui_map_id' => DB::FIELD_TYPE_INT, 'map_x' => DB::FIELD_TYPE_FLOAT, 'map_y' => DB::FIELD_TYPE_FLOAT, 'creature_display_info_id' => DB::FIELD_TYPE_INT]
			);
		}

		/**
		 * @param ArrayObject instance
		 * @return Array
		 */
		private function getDifficultiesForInstance($instance) {
			// 0x2 JournalInstance::Flags (Hide User-Selectable Difficulty)
			if (($instance->flags & 0x2) === 0x2)
				return [];

			return $this->db->getAll(
				'SELECT b.`id`, c.`name`, c.`name_slug`, b.`max_players` FROM `map_difficulty` AS a LEFT JOIN `difficulty` AS b ON a.`difficulty_id` = b.`id` LEFT JOIN `difficulty_info` AS c ON b.`id` = c.`id` WHERE a.`map_id` = ? AND c.`id` IS NOT NULL ORDER BY b.`order_index` ASC',
				[$instance->map_id], ['id' => DB::FIELD_TYPE_INT, 'max_players' => DB::FIELD_TYPE_INT]
			);
		}

		/**
		 * @param string $slugName
		 * @return ArrayObject
		 */
		private function getInstanceFromSlug($slugName) {
			return $this->db->getRow(
				'SELECT a.`id`, a.`name`, a.`map_id`, a.`name_slug`, a.`button_small_fid`, a.`lore_fid`, a.`flags`, b.`instance_type` FROM `journal_instance` AS a LEFT JOIN `map` AS b ON a.`map_id` = b.`id` WHERE `name_slug` = ?',
				[$slugName], ['id' => DB::FIELD_TYPE_INT, 'map_id' => DB::FIELD_TYPE_INT, 'button_small_fid' => DB::FIELD_TYPE_INT, 'lore_fid' => DB::FIELD_TYPE_INT, 'instance_type' => DB::FIELD_TYPE_INT, 'flags' => DB::FIELD_TYPE_INT]
			);
		}

		/**
		 * @param int $instanceID
		 * @return ArrayObject
		 */
		private function getInstanceDescription($instanceID) {
			return $this->db->getRow('SELECT `description` FROM `journal_instance` WHERE `id` = ?', [$instanceID])->description;
		}

		/**
		 * @param int $instanceID
		 * @param ArrayObject|null $difficultyID
		 * @return Array
		 */
		private function getEncounterList($instanceID, $difficulty) {
			return $this->db->getAll(
				'SELECT a.`id`, a.`name`, a.`name_slug`, b.`creature_display_info_id` FROM `journal_encounter` AS a LEFT JOIN `journal_encounter_creature` AS b ON b.`journal_encounter_id` = a.`id` AND b.`order_index` = 0 LEFT JOIN `journal_encounter_difficulty` AS c ON a.`id` = c.`journal_encounter_id` WHERE a.`instance_id` = :instance AND (a.`flags` & 0x2 = 0 OR (:difficulty IS NOT NULL and c.`difficulty` = :difficulty)) AND a.`flags` & 16 = 0 ORDER BY a.`order_index` ASC',
				['instance' => $instanceID, 'difficulty' => $difficulty->id ?? null], ['id' => DB::FIELD_TYPE_INT, 'creature_display_info_id' => DB::FIELD_TYPE_INT]
			);
		}

		/**
		 * @param int $journalTierID
		 * @param int $instanceType
		 * @return Array
		 */
		private function getInstances($journalTierID, $instanceType) {
			return $this->db->getAll('SELECT a.`id`, a.`name`, a.`name_slug`, a.`button_fid` FROM `journal_instance` AS a LEFT JOIN `journal_tier_instance` AS b ON b.`journal_instance_id` = a.`id` LEFT JOIN `map` AS c ON c.`id` = a.`map_id` WHERE (:tier IS NULL OR b.`journal_tier_id` = :tier) AND c.`instance_type` = :type ORDER BY a.`order_index` ASC, a.`name` ASC',
				['tier' => $journalTierID, 'type' => $instanceType], ['id' => DB::FIELD_TYPE_INT, 'button_fid' => DB::FIELD_TYPE_INT]
			);
		}

		/**
		 * @var ArrayObject
		 */
		private $journalTier;

		/**
		 * @var Database
		 */
		public $db;

		/**
		 * @var JSONFile
		 */
		public $response;

		/**
		 * @var Array
		 */
		public $dataPath;
	}