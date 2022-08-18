<?php
	// The follow two ITEM_WEAPON_ arrays define which table to lookup damage
	// records from depending on what type of weapon it is.
	// See ItemDataProvider->getWeaponDamageTable()
	define('ITEM_WEAPON_ONE_HANDED', [
		0, // Axe
		4, // Mace
		7, // Sword
		9, // Warglaives
		11, // Bear Claws
		13, // Fist Weapon
		15, // Dagger
		16, // Thrown
		19 // Wand
	]);

	define('ITEM_WEAPON_TWO_HANDED', [
		1, // 2H Axe
		2, // Bow
		3, // Gun
		5, // 2H Mace
		6, // Polearm
		8, // 2H Sword
		10, // Staff
		12, // Cat Claws
		17, // Spear
		18, // Crossbow
		20, // Fishing Pole
	]);

	// This map defines fallback quality IDs used when looking up
	// damage records in data tables.
	define('ITEM_DAMAGE_QUALITY_MAP', [
		7 => 3, // Heirloom -> Rare
		5 => 4 // Legendary -> Epic
	]);

	define('RANDOM_PROP_FIELD_NAME', [
		2 => 'good',
		3 => 'superior',
		4 => 'epic'
	]);

	class ItemDataProvider {
		/**
		 * @param DB $db
		 * @param int $difficulty
		 */
		public function __construct($db, $difficulty = 0) {
			$this->db = $db;
			$this->difficulty = $difficulty;
		}

		/**
		 * @param int $itemID
		 * @return int
		 */
		public function getItemIconID($itemID) {
			return $this->db->getRow(
				'SELECT COALESCE(NULLIF(a.`icon_fid`, 0), c.`icon_fid`) AS `icon_fid` FROM `item` AS a LEFT JOIN `item_modified_appearance` AS b ON b.`item_id` = a.`id` LEFT JOIN `item_appearance` AS c ON c.`id` = b.`item_appearance_id` WHERE a.`id` = ?',
				[$itemID], ['icon_fid' => DB::FIELD_TYPE_INT]
			)->icon_fid ?? 0;
		}

		/**
		 * @param int $itemID
		 * @return ArrayObject|null
		 */
		public function getItemInfo($itemID) {
			return $this->db->getRow(
				'SELECT `class_id`, `subclass_id`, `inventory_type` FROM `item` WHERE `id` = ?',
				[$itemID], ['class_id' => DB::FIELD_TYPE_INT, 'subclass_id' => DB::FIELD_TYPE_INT, 'inventory_type' => DB::FIELD_TYPE_INT]
			);
		}

		/**
		 * @param int $itemID
		 * @return ArrayObject|null
		 */
		public function getItemSparse($itemID) {
			return $this->db->getRow(
				'SELECT `name`, `description`, `overall_quality`, `item_level`, `bonding`, `sell_price`, `item_delay`, `required_level`, `required_ability`, `flags_0`, `flags_1`, `damage_variance`, `required_skill`, `required_skill_rank`, `bonus_stat_0`, `bonus_stat_1`, `bonus_stat_2`, `bonus_stat_3`, `bonus_stat_4`, `bonus_stat_5`, `bonus_stat_6`, `bonus_stat_7`, `bonus_stat_8`, `bonus_stat_9`, `stat_pct_0`, `stat_pct_1`, `stat_pct_2`, `stat_pct_3`, `stat_pct_4`, `stat_pct_5`, `stat_pct_6`, `stat_pct_7`, `stat_pct_8`, `stat_pct_9` FROM `item_sparse` WHERE `id` = ?',
				[$itemID], ['overall_quality' => DB::FIELD_TYPE_INT, 'item_level' => DB::FIELD_TYPE_INT, 'required_level' => DB::FIELD_TYPE_INT, 'required_ability' => DB::FIELD_TYPE_INT, 'bonding' => DB::FIELD_TYPE_INT, 'sell_price' => DB::FIELD_TYPE_INT, 'required_skill' => DB::FIELD_TYPE_INT, 'required_skill_rank' => DB::FIELD_TYPE_INT, 'item_delay' => DB::FIELD_TYPE_INT, 'flags' => DB::FIELD_TYPE_ARRAY | DB::FIELD_TYPE_INT, 'damage_variance' => DB::FIELD_TYPE_FLOAT, 'bonus_stat' => DB::FIELD_TYPE_ARRAY | DB::FIELD_TYPE_INT, 'stat_pct' => DB::FIELD_TYPE_ARRAY | DB::FIELD_TYPE_INT]
			);
		}

		/**
		 * @param int $itemID
		 * @return ArrayObject|null
		 */
		public function getItemSearchInfo($itemID) {
			return $this->db->getRow(
				'SELECT `name`, `overall_quality`, `item_level`, `required_level`, `required_ability`, `required_skill`, `required_skill_rank`, `flags_0` FROM `item_search_name` WHERE `id` = ?',
				[$itemID], ['overall_quality' => DB::FIELD_TYPE_INT, 'item_level' => DB::FIELD_TYPE_INT, 'required_level' => DB::FIELD_TYPE_INT, 'required_ability' => DB::FIELD_TYPE_INT, 'required_skill' => DB::FIELD_TYPE_INT, 'required_skill_rank' => DB::FIELD_TYPE_INT, 'flags' => DB::FIELD_TYPE_ARRAY | DB::FIELD_TYPE_INT]
			);
		}

		/**
		 * @param int $instanceID
		 * @return array
		 */
		public function getLootTableForInstance($instanceID) {
			return $this->applyItemSpecFilters($this->db->getAll(
				'SELECT a.`item_id`, d.`class_id`, d.`subclass_id`, d.`inventory_type`, e.`name`, e.`overall_quality`, COALESCE(i.`filter_type`, 14) AS `filter_type`, c.`name` AS `boss_name`, c.`name_slug` AS `boss_slug`, COALESCE(NULLIF(d.`icon_fid`, 0), g.`icon_fid`) AS `icon_fid` FROM `journal_encounter_items` AS a LEFT JOIN `journal_item_difficulty` AS b ON a.`id` = b.`encounter_item_id` LEFT JOIN `journal_encounter` AS c ON c.`id` = a.`journal_encounter_id` LEFT JOIN `item` AS d ON d.id = a.`item_id` LEFT JOIN `item_sparse` AS e ON e.id = a.`item_id` LEFT JOIN `item_modified_appearance` AS f ON f.`item_id` = a.`item_id` LEFT JOIN `item_appearance` AS g ON g.`id` = f.`item_appearance_id` LEFT JOIN `journal_item_filter_type` AS i ON a.`item_id` = i.`id` WHERE c.`instance_id` = ? AND (b.`difficulty_id` IS NULL OR b.`difficulty_id` = ?)',
				[$instanceID, $this->difficulty], ['item_id' => DB::FIELD_TYPE_INT, 'icon_fid' => DB::FIELD_TYPE_INT, 'class_id' => DB::FIELD_TYPE_INT, 'subclass_id' => DB::FIELD_TYPE_INT, 'inventory_type' => DB::FIELD_TYPE_INT, 'overall_quality' => DB::FIELD_TYPE_INT, 'filter_type' => DB::FIELD_TYPE_INT]
			));
		}

		/**
		 * @param int $encounterID
		 * @return array
		 */
		public function getLootTableForEncounter($encounterID) {
			return $this->applyItemSpecFilters($this->db->getAll(
				'SELECT a.`item_id`, d.`class_id`, d.`subclass_id`, d.`inventory_type`, e.`name`, e.`overall_quality`, COALESCE(i.`filter_type`, 14) AS `filter_type`, COALESCE(NULLIF(d.`icon_fid`, 0), g.`icon_fid`) AS `icon_fid` FROM `journal_encounter_items` AS a LEFT JOIN `journal_item_difficulty` AS b ON a.`id` = b.`encounter_item_id` LEFT JOIN `item` AS d ON d.id = a.`item_id` LEFT JOIN `item_sparse` AS e ON e.id = a.`item_id` LEFT JOIN `item_modified_appearance` AS f ON f.`item_id` = a.`item_id` LEFT JOIN `item_appearance` AS g ON g.`id` = f.`item_appearance_id` LEFT JOIN `journal_item_filter_type` AS i ON a.`item_id` = i.`id` WHERE a.journal_encounter_id = ? AND (b.`difficulty_id` IS NULL OR b.`difficulty_id` = ?)',
				[$encounterID, $this->difficulty], ['item_id' => DB::FIELD_TYPE_INT, 'icon_fid' => DB::FIELD_TYPE_INT, 'class_id' => DB::FIELD_TYPE_INT, 'subclass_id' => DB::FIELD_TYPE_INT, 'inventory_type' => DB::FIELD_TYPE_INT, 'overall_quality' => DB::FIELD_TYPE_INT, 'filter_type' => DB::FIELD_TYPE_INT]
			));
		}

		/**
		 * @param array $lootTable
		 * @return array
		 */
		private function applyItemSpecFilters($lootTable) {
			foreach ($lootTable as $item) {
				$item->filterClasses = [];
				$item->filterSpecs = [];

				$filters = $this->db->getAll(
					'SELECT a.`spec_id`, b.`class_id` FROM `journal_item_spec` AS a LEFT JOIN `chr_specialization` AS b ON a.`spec_id` = b.`id` WHERE a.`item_id` = ?',
					[$item->item_id], ['spec_id' => DB::FIELD_TYPE_INT, 'class_id' => DB::FIELD_TYPE_INT]
				);

				foreach ($filters as $filter) {
					if (!\in_array($filter->class_id, $item->filterClasses))
						\array_push($item->filterClasses, $filter->class_id);

					if (!\in_array($filter->spec_id, $item->filterSpecs))
						\array_push($item->filterSpecs, $filter->spec_id);
				}
			}

			return $lootTable;
		}

		/**
		 * @param int $itemLevel
		 * @return ArrayObject|null
		 */
		public function getCombatRatingMultipliers($itemLevel) {
			return $this->db->getRow(
				'SELECT `armor_mult`, `weapon_mult`, `trinket_mult`, `jewelry_mult` FROM `combat_ratings_mult_ilvl` WHERE `item_level` = ?',
				[$itemLevel], ['armor_mult' => DB::FIELD_TYPE_FLOAT, 'weapon_mult' => DB::FIELD_TYPE_FLOAT, 'trinket_mult' => DB::FIELD_TYPE_FLOAT, 'jewelry_mult' => DB::FIELD_TYPE_FLOAT]
			);
		}

		/**
		 * @param int $itemLevel
		 * @return ArrayObject|null
		 */
		public function getStaminaMultipliers($itemLevel) {
			return $this->db->getRow(
				'SELECT `armor_mult`, `weapon_mult`, `trinket_mult`, `jewelry_mult` FROM `stamina_mult_ilvl` WHERE `item_level` = ?',
				[$itemLevel], ['armor_mult' => DB::FIELD_TYPE_FLOAT, 'weapon_mult' => DB::FIELD_TYPE_FLOAT, 'trinket_mult' => DB::FIELD_TYPE_FLOAT, 'jewelry_mult' => DB::FIELD_TYPE_FLOAT]
			);
		}

		/**
		 * @param int $subclass
		 * @param int $itemLevel
		 * @param int $quality
		 * @param boolean $isCasterWeapon
		 * @return ArrayObject|null
		 */
		public function getWeaponDamage($subclass, $itemLevel, $quality, $isCasterWeapon = false) {
			$tableName = ItemDataProvider::getWeaponDamageTable($subclass, $isCasterWeapon);
			if ($tableName === null)
				return null;

			$quality = ITEM_DAMAGE_QUALITY_MAP[$quality] ?? $quality;

			return $this->db->getRow(
				'SELECT `quality_' . $quality . '` AS `multiplier` FROM `' . $tableName . '` WHERE `item_level` = ?',
				[$itemLevel], ['multiplier' => DB::FIELD_TYPE_FLOAT]
			)->multiplier ?? 0;
		}

		/**
		 * @param int $itemID
		 * @return array
		 */
		public function getItemEffects($itemID) {
			return $this->db->getAll(
				'SELECT `spell_id`, `trigger_type` FROM `item_x_item_effect` AS a LEFT JOIN `item_effect` AS b ON a.`item_effect_id` = b.`id` WHERE a.`item_id` = ?',
				[$itemID], ['spell_id' => DB::FIELD_TYPE_INT, 'trigger_type' => DB::FIELD_TYPE_INT]
			);
		}

		/**
		 * @param int $skillID
		 * @return string|null
		 */
		public function getSkillName($skillID) {
			// Ideally this would live in SkillDataProvider, but we don't need an
			// entire provider just to get names of skills for item tooltips.
			return $this->db->getRow('SELECT `display_name` FROM `skill_line` WHERE `id` = ?', [$skillID])->display_name;
		}

		/**
		 * @param int $itemLevel
		 * @param int $subclass
		 * @param int $quality
		 * @param int $inventoryType
		 * @return float
		 */
		public function getRandomPropertyValue($itemLevel, $subclass, $quality, $inventoryType) {
			$fieldIndex = ItemDataProvider::getRandomPropertyFieldIndex($inventoryType, $subclass);
			$fieldName = RANDOM_PROP_FIELD_NAME[$quality] ?? 'good';

			if ($fieldIndex === -1)
				return 0;

			$fieldNameSQL = $fieldName . '_f_' . $fieldIndex;
			return $this->db->getRow(
				'SELECT `' . $fieldNameSQL . '` AS `value` FROM `rand_prop_points` WHERE `id` = ?',
				[$itemLevel], ['value' => DB::FIELD_TYPE_FLOAT]
			)->value ?? 0;
		}

		/**
		 * @param int $subclass
		 * @param boolean $isCasterWeapon
		 * @return string|null
		 */
		private static function getWeaponDamageTable($subclass, $isCasterWeapon) {
			$tableName = 'item_damage_';

			if ($subclass === 14 || in_array($subclass, ITEM_WEAPON_ONE_HANDED))
				$tableName .= 'one_hand';
			else if (\in_array($subclass, ITEM_WEAPON_TWO_HANDED))
				$tableName .= 'two_hand';
			else
				return null;

			if ($isCasterWeapon || $subclass === 14)
				$tableName .= '_caster';

			return $tableName;

		}

		/**
		 * @param int $inventoryType
		 * @param int $subclass
		 * @return int
		 */
		private static function getRandomPropertyFieldIndex($inventoryType, $subclass) {
			switch ($inventoryType) {
				case 1: // Head
				case 4: // Shirt
				case 5: // Chest
				case 7: // Legs
				case 15: // Ranged
				case 17: // Two-Hand
				case 20: // Robe
				case 25: // Thrown
					return 0;

				case 3: // Shoulder
				case 6: // Waist
				case 8: // Feet
				case 10: // Hands
				case 12: // Trinket
					return 1;

				case 2: // Neck
				case 9: // Wrist
				case 11: // Finger
				case 16: // Back
					return 2;

				case 13: // One-Hand
				case 14: // Shield
				case 21: // Main-Hand
				case 22: // Off-Hand
				case 23: // Held In Off-Hand
					return 3;

				case 26: // Ranged Right
					return $subclass !== 19 ? 0 : 3;

				case 28: // Relic
					return 4;
			}

			return -1;
		}

		/**
		 * @var DB
		 */
		private $db;

		/**
		 * @var int
		 */
		private $difficulty;
	}