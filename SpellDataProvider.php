<?php
	class SpellDataProvider {
		/**
		 * @param DB $db
		 * @param int $difficulty
		 */
		public function __construct($db, $difficulty = 0) {
			$this->db = $db;
			$this->difficulty = $difficulty;
		}

		/**
		 * @param int $spellID
		 * @param int $effectIndex
		 * @return ArrayObject|null
		 */
		public function getEffectEntry($spellID, $effectIndex = 0) {
			$rows = $this->db->getAll(
				'SELECT * FROM `spell_effect` WHERE `spell_id` = :spellID ORDER BY `effect_index` DESC', ['spellID' => $spellID],
				[
					'id' => DB::FIELD_TYPE_INT,
					'difficulty_id' => DB::FIELD_TYPE_INT,
					'effect_index' => DB::FIELD_TYPE_INT,
					'effect' => DB::FIELD_TYPE_INT,
					'effect_amplitude' => DB::FIELD_TYPE_FLOAT,
					'effect_aura_period' => DB::FIELD_TYPE_INT,
					'effect_bonus_coefficient' => DB::FIELD_TYPE_FLOAT,
					'effect_chain_amplitude' => DB::FIELD_TYPE_FLOAT,
					'effect_chain_targets' => DB::FIELD_TYPE_FLOAT,
					'variance' => DB::FIELD_TYPE_FLOAT,
					'effect_base_points' => DB::FIELD_TYPE_FLOAT,
					'effect_misc_value_0' => DB::FIELD_TYPE_INT,
					'effect_misc_value_1' => DB::FIELD_TYPE_INT,
					'effect_radius_index_0' => DB::FIELD_TYPE_INT,
					'effect_radius_index_1' => DB::FIELD_TYPE_INT,
					'spell_id' => DB::FIELD_TYPE_INT
				]
			);

			// Start by trying to find a row that matches the index/difficulty exactly. If that
			// fails, revert to checking if the index exists with a difficulty of 0 (default).
			// In some rare cases, spell tooltips will reference indexes which simply don't exist
			// in the data, in this case, attempt to find a row that just matches the difficulty,
			// or if that fails, a difficulty of 0.

			// Do we need to make use of Difficulty::FallbackDifficultyID here as well?
			return 
				self::filterEntry($rows, ['effect_index' => $effectIndex, 'difficulty_id' => $this->difficulty]) ??
				self::filterEntry($rows, ['effect_index' => $effectIndex, 'difficulty_id' => 0]) ??
				self::filterEntry($rows, ['difficulty_id' => $this->difficulty]) ??
				self::filterEntry($rows, ['difficulty_id' => 0]);
		}

		/**
		 * @param int $spellID
		 * @return stdClass|null
		 */
		public function getMiscEntry($spellID) {
			$rows = $this->db->getAll(
				'SELECT * FROM `spell_misc` WHERE `spell_id` = :spellID', ['spellID' => $spellID],
				[
					'id' => DB::FIELD_TYPE_INT,
					'difficulty_id' => DB::FIELD_TYPE_INT,
					'spell_id' => DB::FIELD_TYPE_INT,
					'duration_index' => DB::FIELD_TYPE_INT,
					'range_index' => DB::FIELD_TYPE_INT,
					//'content_tuning_id' => DB::FIELD_TYPE_INT
				]
			);

			// Check for the exact difficulty or fallback to 0.
			// Do we need to make use of Difficulty::FallbackDifficultyID here as well?
			return
				self::filterEntry($rows, ['difficulty_id' => $this->difficulty]) ??
				self::filterEntry($rows, ['difficulty_id' => 0]);
		}

		/**
		 * @param int $spellID
		 * @return stdClass|null
		 */
		public function getAuraOptionsEntry($spellID) {
			$rows = $this->db->getAll(
				'SELECT * FROM `spell_aura_options` WHERE `spell_id` = :spellID', ['spellID' => $spellID],
				[
					'id' => DB::FIELD_TYPE_INT,
					'difficulty_id' => DB::FIELD_TYPE_INT,
					'cumulative_aura' => DB::FIELD_TYPE_INT,
					'proc_category_recovery' => DB::FIELD_TYPE_INT,
					'proc_chance' => DB::FIELD_TYPE_INT,
					'proc_charges' => DB::FIELD_TYPE_INT,
					'spell_procs_per_minute_id' => DB::FIELD_TYPE_INT,
					'spell_id' => DB::FIELD_TYPE_INT
				]
			);

			// Check for the exact difficulty or fallback to 0.
			// Do we need to make use of Difficulty::FallbackDifficultyID here as well?
			return
				self::filterEntry($rows, ['difficulty_id' => $this->difficulty]) ??
				self::filterEntry($rows, ['difficulty_id' => 0]);
		}

		/**
		 * @param int $spellID
		 * @return stdClass|null
		 */
		public function getTargetRestrictionsEntry($spellID) {
			$rows = $this->db->getAll(
				'SELECT * FROM `spell_target_restrictions` WHERE `spell_id` = :spellID', ['spellID' => $spellID],
				[
					'id' => DB::FIELD_TYPE_INT,
					'difficulty_id' => DB::FIELD_TYPE_INT,
					'max_targets' => DB::FIELD_TYPE_INT,
					'max_target_level' => DB::FIELD_TYPE_INT,
					'spell_id' => DB::FIELD_TYPE_INT
				]
			);

			// Check for exact difficulty or fallback to 0.
			// Do we need to make use of Difficulty::FallbackDifficultyID here as well?
			return
				self::filterEntry($rows, ['difficulty_id' => $this->difficulty]) ??
				self::filterEntry($rows, ['difficulty_id' => 0]);
		}

		/**
		 * @param int $spellID
		 * @return ArrayObject|null
		 */
		public function getSpellInfo($spellID) {
			return $this->db->getRow(
				'SELECT a.`name`, b.`spell_icon_fid`, c.`description` FROM `spell_name` AS a LEFT JOIN `spell_misc` AS b ON b.`spell_id` = :spellID LEFT JOIN `spell` AS c ON c.`id` = :spellID WHERE a.`id` = :spellID',
				['spellID' => $spellID], ['spell_icon_fid' => DB::FIELD_TYPE_INT]
			);
		}

		/**
		 * @param int $spellID
		 * @return int
		 */
		public function getSpellIconID($spellID) {
			return $this->db->getRow(
				'SELECT `spell_icon_fid` FROM `spell_misc` WHERE `spell_id` = ? LIMIT 1',
				[$spellID], ['spell_icon_fid' => DB::FIELD_TYPE_INT]
			)->spell_icon_fid ?? 0;
		}

		/**
		 * @param int $spellID
		 * @return string
		 */
		public function getSpellName($spellID):string {
			$entry = $this->db->getRow('SELECT `name` FROM `spell_name` WHERE `id` = ?', [$spellID]);
			return $entry->name ?? 'Spell#' . $spellID;
		}

		/**
		 * @param int $spellID
		 * @return string|null
		 */
		public function getSpellDescription($spellID) {
			$entry = $this->db->getRow('SELECT `description` FROM `spell` WHERE `id` = ?', [$spellID]);
			return $entry->description ?? null;
		}

		/**
		 * @param int $spellID
		 * @return string|null
		 */
		public function getSpellAuraDescription($spellID) {
			$entry = $this->db->getRow('SELECT `aura` FROM `spell` WHERE `id` = ?', [$spellID]);
			return $entry->aura ?? null;
		}

		/**
		 * @param int $spellID
		 * @param int $effectIndex
		 * @return float
		 */
		public function getEffectBasePoints($spellID, $effectIndex = 0):float {
			return $this->getEffectEntry($spellID, $effectIndex)->effect_base_points ?? 0;
		}

		/**
		 * @param int $spellID
		 * @param int $effectIndex
		 * @return int
		 */
		public function getEffectAuraPeriod($spellID, $effectIndex = 0):int {
			return $this->getEffectEntry($spellID, $effectIndex)->effect_aura_period ?? 0;
		}

		/**
		 * @param int $spellID
		 * @param int $effectIndex
		 * @return float
		 */
		public function getEffectAmplitude($spellID, $effectIndex = 0):float {
			return $this->getEffectEntry($spellID, $effectIndex)->effect_amplitude ?? 0;
		}

		/**
		 * @param int $spellID
		 * @param int $effectIndex
		 * @return int
		 */
		public function getEffectChainTargets($spellID, $effectIndex = 0):int {
			return $this->getEffectEntry($spellID, $effectIndex)->effect_chain_targets ?? 0;
		}

		/**
		 * @param int $spellID
		 * @param int $effectIndex
		 * @return float
		 */
		public function getEffectChainAmplitude($spellID, $effectIndex = 0):float {
			return $this->getEffectEntry($spellID, $effectIndex)->effect_chain_amplitude ?? 0;
		}

		/**
		 * @param int $spellID
		 * @param int $effectIndex
		 * @return float
		 */
		public function getEffectBonusCoefficient($spellID, $effectIndex = 0):float {
			return $this->getEffectEntry($spellID, $effectIndex)->effect_bonus_coefficient ?? 0;
		}

		/**
		 * @param int $spellID
		 * @param int $effectIndex
		 * @return float
		 */
		public function getEffectPointsPerResource($spellID, $effectIndex = 0):float {
			return $this->getEffectEntry($spellID, $effectIndex)->effect_points_per_resource ?? 0;
		}

		/**
		 * @param int $spellID
		 * @return int
		 */
		public function getAuraCumulative($spellID):int {
			return $this->getAuraOptionsEntry($spellID)->cumulative_aura ?? 0;
		}

		/**
		 * @param int $spellID
		 * @return int
		 */
		public function getAuraProcChance($spellID):int {
			return $this->getAuraOptionsEntry($spellID)->proc_chance ?? 0;
		}

		/**
		 * @param int $spellID
		 * @return int
		 */
		public function getAuraProcRecovery($spellID):int {
			return $this->getAuraOptionsEntry($spellID)->proc_category_recovery ?? 0;
		}

		/**
		 * @param int $spellID
		 * @return int
		 */
		public function getAuraProcCharges($spellID):int {
			return $this->getAuraOptionsEntry($spellID)->proc_charges ?? 0;
		}

		/**
		 * @param int $spellID
		 * @return int
		 */
		public function getEffectMiscValue($spellID, $effectIndex = 0):int {
			// It is currently not known if the field index ($q1) is referencing
			// the effect index or the EffectMiscValue[] index. The only spell using
			// this variable is 39794, of which both EffectMiscValue[0]/[1] are 0
			// and there is only one effect index entry, thus testing inconclusive.
			return $this->getEffectEntry($spellID)->effect_misc_value_0 ?? 0;
		}

		/**
		 * @param int $spellID
		 * @return float
		 */
		public function getProcsPerMinute($spellID):float {
			$options = $this->getAuraOptionsEntry($spellID);
			if ($options === null)
				return  0;

			return $this->db->getRow(
				'SELECT `base_proc_rate` FROM `spell_procs_per_minute` WHERE `id` = ?',
				[$options->spell_procs_per_minute_id], ['base_proc_rate' => DB::FIELD_TYPE_FLOAT]
			)->base_proc_rate ?? 0;
		}

		/**
		 * @param int $spellID
		 * @param int $effectIndex
		 * @param int $radiusIndex
		 * @return float
		 */
		public function getEffectRadius($spellID, $effectIndex = 0, $radiusIndex):float {
			$effect = $this->getEffectEntry($spellID, $effectIndex);
			if ($effect === null)
				return 0;

			$radiusID = 0;
			if ($radiusIndex === 0)
				$radiusID = $effect->effect_radius_index_0;
			else if ($radiusIndex === 1)
				$radiusID = $effect->effect_radius_index_1;

			$entry = $this->db->getRow('SELECT `radius` FROM `spell_radius` WHERE `id` = ?', [$radiusID], ['radius' => DB::FIELD_TYPE_FLOAT]);
			return $entry->radius ?? 0;
		}

		/**
		 * @param int $spellID
		 * @return stdClass
		 */
		public function getSpellRange($spellID) {
			$misc = $this->getMiscEntry($spellID);
			$rangeID = $misc->range_index ?? 0;

			if ($rangeID !== 0) {
				$range = $this->db->getRow(
					'SELECT `range_min_0`, `range_min_1`, `range_max_0`, `range_max_1` FROM `spell_range` WHERE `id` = ?',
					[$rangeID], ['range_min_0' => DB::FIELD_TYPE_FLOAT, 'range_min_1' => DB::FIELD_TYPE_FLOAT, 'range_max_0' => DB::FIELD_TYPE_FLOAT, 'range_max_1' => DB::FIELD_TYPE_FLOAT]
				);

				if ($range !== null)
					return (object) ['range_min' => [$range->range_min_0, $range->range_min_1], 'range_max' => [$range->range_max_0, $range->range_max_1]];
			}

			return (object) ['range_min' => [0, 0], 'range_max' => [0, 0]];
		}

		/**
		 * @param int $spellID
		 * @return int
		 */
		public function getSpellIcon($spellID):int {
			return $this->getMiscEntry($spellID)->spell_icon_fid ?? 0;
		}

		/**
		 * @param int $spellID
		 * @return int
		 */
		public function getMiscDuration($spellID):int {
			$misc = $this->getMiscEntry($spellID);
			if ($misc === null)
				return 0;

			$entry = $this->db->getRow('SELECT `duration` FROM `spell_duration` WHERE `id` = ?', [$misc->duration_index], ['duration' => DB::FIELD_TYPE_INT]);
			return $entry->duration ?? 0;
		}

		/**
		 * @param int $spellID
		 * @return int
		 */
		public function getSpellManaCost($spellID):int {
			return $this->db->getRow(
				'SELECT `mana_cost` FROM `spell_power` WHERE `spell_id` = ? LIMIT 1',
				[$spellID], ['mana_cost' => DB::FIELD_TYPE_INT]
			)->mana_cost ?? 0;
		}

		/**
		 * @param int $spellID
		 * @return int
		 */
		public function getSpellMaxLevel($spellID):int {
			return $this->db->getRow(
				'SELECT `max_level` FROM `spell_levels` WHERE `spell_id` = ? LIMIT 1',
				[$spellID], ['max_level' => DB::FIELD_TYPE_INT]
			)->max_level ?? 0;
		}

		/**
		 * @param int $spellID
		 * @param int $effectIndex
		 * @return stdClass|null
		 */
		public function getEnchantmentEntry($spellID, $effectIndex = 0) {
			$entry = $this->getEffectEntry($spellID, $effectIndex);
			$enchantID = $entry->effect_misc_value_0 ?? 0;

			if ($enchantID === 0)
				return null;

			return $this->db->getRow(
				'SELECT `effect_points_min_0`, `effect_scaling_points_0`, `item_level_max` FROM `spell_item_enchantment` WHERE `id` = ?',
				[$enchantID], ['effect_points_min_0' => DB::FIELD_TYPE_FLOAT, 'effect_scaling_points_0' => DB::FIELD_TYPE_FLOAT, 'item_level_max' => DB::FIELD_TYPE_INT]
			);
		}

		/**
		 * @param int $spellID
		 * @param int $effectIndex
		 * @return float
		 */
		public function getEnchantmentValue($spellID, $effectIndex = 0):float {
			$enchant = $this->getEnchantmentEntry($spellID, $effectIndex);

			if ($enchant !== null)
				return $enchant->effect_points_min_0 * $enchant->effect_scaling_points_0;

			return 0;
		}

		/**
		 * @param int $spellID
		 * @param int $effectIndex
		 * @return int
		 */
		public function getEnchantmentMax($spellID, $effectIndex = 0):int {
			$enchant = $this->getEnchantmentEntry($spellID, $effectIndex);
			return $enchant->item_level_max ?? 0;
		}

		/**
		 * @param array $entries
		 * @param array $columns
		 * @return stdClass|null
		 */
		private static function filterEntry($entries, $columns) {
			foreach ($entries as $entry) {
				foreach ($columns as $key => $value) {
					if ($entry->$key === $value)
						return $entry;
				}
			}

			return null;
		}

		/**
		 * @var int
		 */
		private $difficulty;

		/**
		 * @var DB
		 */
		private $db;
	}