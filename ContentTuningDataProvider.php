<?php
	class ContentTuningDataProvider {
		/**
		 * @var DB $db
		 */
		public function __construct($db) {
			$this->db = $db;
		}

		/**
		 * @param int $id
		 * @return ArrayObject|null
		 */
		public function getByID($id) {
			return $this->db->getRow(
				'SELECT `min_level`, `max_level` FROM `content_tuning` WHERE `id` = ?',
				[$id], ['min_level' => DB::FIELD_TYPE_INT, 'max_level' => DB::FIELD_TYPE_INT]
			);
		}
	}