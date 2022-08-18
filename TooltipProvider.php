<?php
	class TooltipProvider {
		/**
		 * @param DB $db
		 */
		public function __construct($db) {
			$this->db = $db;
		}

		/**
		 * @param int $spellID
		 * @param int $difficulty
		 * @return string
		 */
		public function getSpellTooltip($spellID, $difficulty = 0) {
			if ($spellID === 0)
				return '';

			$provider = new DataProvider($this->db, $difficulty);
			$tokenizer = new SpellTooltipTokenizer($provider->getSpellDataProvider()->getSpellDescription($spellID));
			$renderer = new SpellTooltipRenderer($tokenizer->parse(), $spellID, $provider);

			$iconID = $provider->getSpellDataProvider()->getSpellIconID($spellID);
			
			return (object) ['content' => $renderer->render(true), 'icon' => $iconID];
		}

		/**
		 * @param int $itemID
		 * @param int $difficulty
		 * @return string
		 */
		public function getItemTooltip($itemID, $difficulty = 0) {
			if ($itemID === 0)
				return '';

			$provider = new DataProvider($this->db, $difficulty);
			$renderer = new ItemTooltipRenderer($itemID, $provider);

			$iconID = $provider->getItemDataProvider()->getItemIconID($itemID);

			return (object) ['content' => $renderer->render(), 'icon' => $iconID];
		}
	}