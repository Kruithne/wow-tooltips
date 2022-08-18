<?php
	class GarrisonDataProvider {
		/**
		 * @var DB $db
		 */
		public function __construct($db) {
			$this->db = $db;
		}

		/**
		 * @param int $buildingID
		 * @return array
		 */
		public function getSameTypeBuildings($buildingID) {
			return $this->db->getAll(
				'SELECT `id`, `tooltip`, `upgrade_level` FROM `garr_building` WHERE `building_type` = (SELECT `building_type` FROM `garr_building` WHERE `id` = ?)',
				[$buildingID], ['id' => DB::FIELD_TYPE_INT, 'upgrade_level' => DB::FIELD_TYPE_INT]
			);
		}

		/**
		 * @param int $abilityID
		 * @return string|null
		 */
		public function getAbilityDescription($abilityID) {
			return $this->db->getRow(
				'SELECT `description` FROM `garr_ability` WHERE `id` = ?', [$abilityID]
			)->description ?? null;
		}

		/**
		 * @param int $abilityEffectID
		 * @return ArrayObject|null
		 */
		public function getAbilityEffect($abilityEffectID) {
			return $this->db->getRow(
				'SELECT `combat_weight_base`, `combat_weight_max`, `action_value_flat`, `action_hours` FROM `garr_ability_effect` WHERE `id` =  ?',
				[$abilityEffectID], ['combat_weight_base' => DB::FIELD_TYPE_FLOAT, 'combat_weight_max' => DB::FIELD_TYPE_FLOAT, 'action_value_flat' => DB::FIELD_TYPE_FLOAT, 'action_hours' => DB::FIELD_TYPE_INT]
			) ?? null;
		}

		/**
		 * @var DB
		 */
		private $db;
	}