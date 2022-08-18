<?php
	use KrameWork\Database\ConnectionString;
	use KrameWork\Storage\JSONFile;

	class VersionHandler {
		/**
		 * @param Array $dataPath
		 * @param JSONFile $response
		 */
		public function __construct($dataPath, $response) {
			$this->dataPath = $dataPath;
			$this->response = $response;

			$this->versions = (new JSONFile(__DIR__ . '/../cfg/site_versions.json'))->versions;
		}

		/**
		 * @param string $searchText
		 * @param string $requestSiteVersion
		 */
		public function handleSearch($searchText, $requestSiteVersion) {
			$siteVersion = $this->getSiteVersion($requestSiteVersion);
			$db = $this->getDatabase($siteVersion);

			$searchHandler = new SearchHandler($db);
			$this->response->renderMode = RENDER_MODE_SEARCH;
			$this->response->searchResults = $searchHandler->search($searchText, SearchHandler::QUICK_SEARCH_ENTRY_LIMIT);
		}

		/**
		 * @param string $requestSiteVersion
		 */
		public function provideLootFilterInfo($requestSiteVersion) {
			$siteVersion = $this->getSiteVersion($requestSiteVersion);
			$db = $this->getDatabase($siteVersion);

			$handler = new LootFilterHandler($db);
			$this->response->classes = $handler->getClasses();
			$this->response->specializations = $handler->getSpecializations();
			$this->response->itemTypes = $handler->getItemTypes();
		}

		public function handleDataPath() {
			$finalDataPath = [];

			$requestSiteVersion = array_shift($this->dataPath);
			$siteVersion = $this->getSiteVersion($requestSiteVersion);

			array_push($finalDataPath, $siteVersion->id);

			$this->response->version = $siteVersion->id;

			$operation = $this->dataPath[0] ?? null;
			$db = $this->getDatabase($siteVersion);

			if (\strpos($operation, 'search') === 0) {
				$searchText = \urldecode(\substr($operation, \strpos($operation, '=') + 1));
				$searchHandler = new SearchHandler($db);

				$this->response->searchResults = $searchHandler->search($searchText, 0);
				$this->response->renderMode = RENDER_MODE_SEARCH;

				\array_push($finalDataPath, $operation);
			} else if ($siteVersion->hasExpansions) {
				$expansionHandler = new ExpansionHandler($this->dataPath, $this->response, $db);
				$expansionHandler->handleDataPath($finalDataPath);
			} else {
				$instanceHandler = new InstanceHandler($this->dataPath, $this->response, null, $db);
				$instanceHandler->handleDataPath($finalDataPath);
			}

			$this->response->finalDataPath = $finalDataPath;
		}

		/**
		 * @param string $requestSiteVersion
		 */
		public function provideExpansionList($requestSiteVersion) {
			$siteVersion = $this->getSiteVersion($requestSiteVersion);

			if ($siteVersion->hasExpansions) {
				$db = $this->getDatabase($siteVersion);
				$rows = $db->getAll('SELECT `id`, `name`, `name_slug` FROM `journal_tier` ORDER BY `id` ASC', []);
				$this->response->expansions = $rows;
			} else {
				$this->response->expansions = [];
			}
		}

		/**
		 * @param string $requestSiteVersion
		 * @return TooltipProvider
		 */
		public function getTooltipProvider($requestSiteVersion) {
			$siteVersion = $this->getSiteVersion($requestSiteVersion);
			$db = $this->getDatabase($siteVersion);

			return new TooltipProvider($db);
		}

		/**
		 * @param JSONFile $siteVersion
		 * @return Database
		 */
		private function getDatabase($siteVersion) {
			$cfg = new JSONFile(__DIR__ . '/../cfg/db_' . $siteVersion->id . '.json');
			$dsn = new ConnectionString($cfg->host, $cfg->user, $cfg->pass);
			return new DB($dsn, DB::DB_DRIVER_PDO);
		}

		/**
		 * @return object
		 */
		private function getDefaultSiteVersion() {
			$key = array_search(true, array_column($this->versions, 'isDefault'));
			if ($key !== false)
				return $this->versions[$key];

			// Bad site configuration, default to first entry.
			return $this->versions[0];
		}

		/**
		 * @param string|null $targetKey
		 * @return object
		 */
		private function getSiteVersion($targetKey) {
			if ($targetKey !== null) {
				$key = array_search($targetKey, array_column($this->versions, 'id'));
				if ($key !== false)
					return $this->versions[$key];
			}
			
			return $this->getDefaultSiteVersion();
		}

		/**
		 * @var Array
		 */
		private $versions;

		/**
		 * @var Array
		 */
		private $dataPath;

		/**
		 * @var JSONFile
		 */
		private $response;
	}