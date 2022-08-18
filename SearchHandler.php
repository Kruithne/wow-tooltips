<?php
	class SearchHandler {
		const MIN_SEARCH_LENGTH = 3;
		const QUICK_SEARCH_ENTRY_LIMIT = 6;

		const SEARCH_TYPE_INSTANCE = 0x1;
		const SEARCH_TYPE_ITEM = 0x2;
		const SEARCH_TYPE_SECTION = 0x3;
		const SEARCH_TYPE_ENCOUNTER = 0x4;

		const SEARCH_TYPES = [
			SearchHandler::SEARCH_TYPE_INSTANCE,
			SearchHandler::SEARCH_TYPE_ENCOUNTER,
			SearchHandler::SEARCH_TYPE_ITEM,
			SearchHandler::SEARCH_TYPE_SECTION,
		];

		/**
		 * @param DB $db
		 */
		public function __construct($db) {
			$this->db = $db;
		}

		/**
		 * @param string $search
		 * @param int [$limit=0]
		 * @return stdClass
		 */
		public function search($searchText, $limit = 0) {
			$totalEntryCount = 0;
			$totalEntries = [];

			if (\strlen($searchText) >= SearchHandler::MIN_SEARCH_LENGTH) {
				$searchText = '%' . $searchText . '%';

				foreach (SearchHandler::SEARCH_TYPES as $searchType) {
					if ($limit === 0 || $totalEntryCount < $limit) {
						$entries = $this->getEntries($searchText, $searchType);
						$totalEntryCount += \count($entries);
						$totalEntries = \array_merge($totalEntries, $entries);
					} else {
						$totalEntryCount += $this->getEntryCount($searchText, $searchType);
					}
				}

				if ($limit > 0)
					$totalEntries = \array_slice($totalEntries, 0, $limit);
			}

			return (object) ['entries' => $totalEntries, 'totalEntryCount' => $totalEntryCount];
		}

		/**
		 * @param string $searchText
		 * @param int $searchType
		 * @return array
		 */
		private function getEntries($searchText, $searchType) {
			switch ($searchType) {
				case SearchHandler::SEARCH_TYPE_INSTANCE:
					return $this->db->getAll(
						'SELECT a.`name`, a.`name_slug`, a.`button_fid` AS `icon_id`, c.`name_slug` AS `expac_slug`, ? AS `type` FROM journal_instance AS a LEFT JOIN `journal_tier_instance` AS b ON b.`journal_instance_id` = a.`id` LEFT JOIN `journal_tier` AS c ON c.`id` = b.`journal_tier_id` WHERE c.`name_slug` IS NOT NULL AND a.`name` LIKE ? GROUP BY a.`id`',
						[$searchType, $searchText], ['type' => DB::FIELD_TYPE_INT, 'icon_id' => DB::FIELD_TYPE_INT]
					);

				case SearchHandler::SEARCH_TYPE_ITEM:
					return $this->db->getAll(
						'SELECT a.`id`, COALESCE(NULLIF(e.`icon_fid`, 0), g.`icon_fid`) AS `icon_id`, b.`name`, c.`name` AS `encounter_name`, c.`name_slug` AS `encounter_slug`, d.`name` AS `instance_name`, d.`name_slug` AS `instance_slug`, ? AS `type`, b.`overall_quality`, i.name_slug AS `expac_slug`, k.`name_slug` AS `difficulty_slug`, k.`name` AS `difficulty_name` FROM `journal_encounter_items` AS a LEFT JOIN `item_sparse` AS b ON a.item_id = b.id LEFT JOIN `journal_encounter` AS c ON a.journal_encounter_id = c.id LEFT JOIN `journal_instance` AS d ON c.instance_id = d.id LEFT JOIN `item` AS e ON a.`item_id` = e.`id` LEFT JOIN `item_modified_appearance` AS f ON f.`item_id` = a.`item_id` LEFT JOIN `item_appearance` AS g ON g.`id` = f.`item_appearance_id` LEFT JOIN `journal_tier_instance` AS h ON h.`journal_instance_id` = d.`id` LEFT JOIN `journal_tier` AS i ON i.`id` = h.`journal_tier_id` LEFT JOIN `journal_item_difficulty` AS j ON j.`encounter_item_id` = a.`id` LEFT JOIN `difficulty_info` AS k ON k.`id` = j.`difficulty_id` WHERE b.`name` LIKE ?',
						[$searchType, $searchText], ['type' => DB::FIELD_TYPE_INT, 'icon_id' => DB::FIELD_TYPE_INT, 'overall_quality' => DB::FIELD_TYPE_INT]
					);

				case SearchHandler::SEARCH_TYPE_ENCOUNTER:
					return $this->db->getAll(
						'SELECT a.`name`, a.`name_slug`, b.`name` AS `instance_name`, b.`name_slug` AS `instance_slug`, d.`name` AS `expac_name`, d.`name_slug` AS `expac_slug`, ? AS `type` FROM `journal_encounter` AS a LEFT JOIN `journal_instance` AS b ON a.`instance_id` = b.`id` LEFT JOIN `journal_tier_instance` AS c ON c.`journal_instance_id` = b.`id` LEFT JOIN `journal_tier` AS d ON d.`id` = c.`journal_tier_id` WHERE a.`name` LIKE ?',
						[$searchType, $searchText], ['type' => DB::FIELD_TYPE_INT]
					);
			}

			return [];
		}

		/**
		 * @param string $searchText
		 * @param int $searchType
		 * @return int
		 */
		private function getEntryCount($searchText, $searchType):int {
			$query = '';

			switch ($searchType) {
				case SearchHandler::SEARCH_TYPE_INSTANCE:
					$query = 'SELECT COUNT(*) AS `total` FROM `journal_instance` AS a LEFT JOIN `journal_tier_instance` AS b ON b.`journal_instance_id` = a.`id` LEFT JOIN `journal_tier` AS c ON c.`id` = b.`journal_tier_id` WHERE c.`name_slug` IS NOT NULL AND a.`name` LIKE ? GROUP BY a.`id`';
					break;

				case SearchHandler::SEARCH_TYPE_ITEM:
					$query = 'SELECT COUNT(*) FROM `journal_encounter_items` AS a LEFT JOIN `item_sparse` AS b ON a.`item_id` = b.`id` WHERE b.`name` LIKE ?';
					break;

				case SearchHandler::SEARCH_TYPE_ENCOUNTER:
					$query = 'SELECT COUNT(*) AS `total` FROM `journal_encounter` WHERE `name` LIKE ?';
					break;

				default:
					return 0;
			}

			return $this->db->getRow($query, [$searchText], ['total' => DB::FIELD_TYPE_INT])->total;
		}

		/**
		 * @var DB
		 */
		private $db;
	}