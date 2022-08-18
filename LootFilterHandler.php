<?php
	class LootFilterHandler {
		/**
		 * @param DB $db
		 */
		public function __construct($db) {
			$this->db = $db;
		}

		/**
		 * @return Array
		 */
		public function getClasses() {
			return $this->db->getAll(
				'SELECT `id`, `name` FROM `chr_classes`', [], ['id' => DB::FIELD_TYPE_INT]
			);
		}

		/**
		 * @return Array
		 */
		public function getSpecializations() {
			return $this->db->getAll(
				'SELECT `id`, `name`, `class_id` FROM `chr_specialization` ORDER BY `order_index` ASC',
				[], ['id' => DB::FIELD_TYPE_INT, 'class_id' => DB::FIELD_TYPE_INT]
			);
		}

		/**
		 * @return Array
		 */
		public function getItemTypes() {
			return $this->db->getAll(
				'SELECT `id`, `name` FROM `journal_item_filter_types`',
				[], ['id' => DB::FIELD_TYPE_INT]
			);
		}
	}