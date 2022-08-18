<?php
	class ExpansionHandler {
		/**
		 * @param Array $dataPath
		 * @param JSONFile $response
		 * @param Database $db
		 */
		public function __construct($dataPath, $response, $db) {
			$this->dataPath = $dataPath;
			$this->response = $response;
			$this->db = $db;
		}

		/**
		 * @param Array $finalDataPath
		 */
		public function handleDataPath(&$finalDataPath) {
			$requestExpansion = \array_shift($this->dataPath);
			$journalTier = null;

			if ($requestExpansion)
				$journalTier = $this->getJournalTierFromSlugName(\strtolower($requestExpansion));
			
			if (!$journalTier)
				$journalTier = $this->getDefaultJournalTier();

			if ($journalTier) {
				\array_push($finalDataPath, $journalTier->name_slug);
				$this->response->renderMode = RENDER_MODE_INSTANCE_LIST;
				$this->response->expansion = $journalTier;

				$instanceHandler = new InstanceHandler($this->dataPath, $this->response, $journalTier, $this->db);
				$instanceHandler->handleDataPath($finalDataPath);
			}
		}

		/**
		 * @return \ArrayObject
		 */
		private function getDefaultJournalTier() {
			return $this->db->getRow(
				'SELECT `id`, `name`, `name_slug` FROM `journal_tier` ORDER BY `id` DESC LIMIT 1',
				null, ['id' => DB::FIELD_TYPE_INT]
			);
		}

		/**
		 * @param string $slugName
		 * @return \ArrayObject
		 */
		private function getJournalTierFromSlugName($slugName) {
			return $this->db->getRow(
				'SELECT `id`, `name`, `name_slug` FROM `journal_tier` WHERE `name_slug` = ?',
				[$slugName], ['id' => DB::FIELD_TYPE_INT]
			);
		}

		/**
		 * @var Database
		 */
		private $db;

		/**
		 * @var Array
		 */
		private $dataPath;

		/**
		 * @var JSONFile
		 */
		private $response;
	}