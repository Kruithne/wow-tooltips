<?php
	class DataProvider {
		/**
		 * @param DB $db
		 */
		public function __construct($db, $difficulty = 0) {
			$this->db = $db;
			$this->difficulty = $difficulty;
		}

		/**
		 * @return SpellDataProvider
		 */
		public function getSpellDataProvider() {
			if ($this->spellDataProvider === null)
				$this->spellDataProvider = new SpellDataProvider($this->db, $this->difficulty);

			return $this->spellDataProvider;
		}

		/**
		 * @return GarrisonDataProvider
		 */
		public function getGarrisonDataProvider() {
			if ($this->garrisonDataProvider === null)
				$this->garrisonDataProvider = new GarrisonDataProvider($this->db);

			return $this->garrisonDataProvider;
		}

		/**
		 * @return ContentTuningDataProvider
		 */
		public function getContentTuningDataProvider() {
			if ($this->contentTuningDataProvider === null)
				$this->contentTuningDataProvider = new ContentTuningDataProvider($this->db);

			return $this->contentTuningDataProvider;
		}

		/**
		 * @return ItemDataProvider
		 */
		public function getItemDataProvider() {
			if ($this->itemDataProvider === null)
				$this->itemDataProvider = new ItemDataProvider($this->db, $this->difficulty);

			return $this->itemDataProvider;
		}

		/**
		 * @var DB
		 */
		private $db;

		/**
		 * @var int
		 */
		public $difficulty;

		/**
		 * @var ItemDataProvider
		 */
		private $itemDataProvider;

		/**
		 * @var SpellDataProvider
		 */
		private $spellDataProvider;

		/**
		 * @var GarrisonDataProvider
		 */
		private $garrisonDataProvider;


		/**
		 * @var ContentTuningDataProvider
		 */
		private $contentTuningDataProvider;
	}