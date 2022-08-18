<?php
	require_once(__DIR__ . '/ItemTooltipConstants.php');

	class ItemTooltipRenderer {
		/**
		 * @param int $itemID
		 * @param DataProvider $provider
		 */
		public function __construct($itemID, $provider) {
			$this->output = '';
			$this->itemID = $itemID;
			$this->provider = $provider;
			$this->itemDataProvider = $provider->getItemDataProvider();
		}

		public function render():string {
			// class_id, subclass_id, inventory_type
			$itemInfo = $this->itemDataProvider->getItemInfo($this->itemID);
			if ($itemInfo !== null) {
				$itemSparse = $this->itemDataProvider->getItemSparse($this->itemID);
				if ($itemSparse !== null) {
					$this->renderUsingSparse($itemInfo, $itemSparse);
				} else {
					// Fallback to ItemSearchName table.
					$itemSearch = $this->itemDataProvider->getItemSearchInfo($this->itemID);
					if ($itemSearch !== null)
						$this->renderUsingSearchInfo($itemInfo, $itemSearch);
				}
			}

			return $this->output;
		}

		/**
		 * @param ArrayObject $itemInfo
		 * @param ArrayObject $itemSparse
		 */
		private function renderUsingSparse($itemInfo, $itemSparse) {
			$itemName = new MarkupElement('tt-title');
			$itemName->addAttribute('data-quality', $itemSparse->overall_quality);
			$this->output .= $itemName->setContent($itemSparse->name);
	
			$this->renderItemLevel($itemInfo, $itemSparse);

			if ($itemSparse->bonding > 0)
				$this->output .= $this->createItemLine('binding', ITEM_BINDING[$itemSparse->bonding]);

			if (($itemSparse->flags[0] & 0x80000) === 0x00080000)
				$this->output .= $this->createItemLine('unique-equip', 'Unique-Equipped');

			// TODO: 'Unique' item? (Item 23442)
			// TODO: 'Mount' item? (Item 63040)

			$this->renderItemType($itemInfo);

			// Show damage/speed for weapons?
			if ($itemInfo->class_id === 2) {
				$itemDelay = $itemSparse->item_delay / 1000;
				$isCasterWeapon = ($itemSparse->flags[1] & 0x200) == 0x200;
				$itemDamage = $this->itemDataProvider->getWeaponDamage($itemInfo->subclass_id, $itemSparse->item_level, $itemSparse->overall_quality, $isCasterWeapon);

				$minDamage = floor($itemDamage * $itemDelay * (1 - $itemSparse->damage_variance * 0.5));
				$maxDamage = ceil($itemDamage * $itemDelay * (1 + $itemSparse->damage_variance * 0.5));

				$damageLine = new MarkupElement('tt-item-double-line');
				$damageLine->content = $this->createItemLine('damage', \sprintf('%d - %d Damage', $minDamage, $maxDamage));
				$damageLine->content .= $this->createItemLine('speed', \sprintf('Speed %.2f', $itemDelay));
				$this->output .= $damageLine;

				$this->output .= $this->createItemLine('dps', \sprintf('(%.1f damage per second)', $itemDamage));
			}

			$showStats = false;
			foreach ($itemSparse->bonus_stat as $stat) {
				if ($stat !== -1 && $stat !== 0) {
					$showStats = true;
					break;
				}
			}

			if ($showStats) {
				$multiplierSlot = ItemTooltipRenderer::getMultiplierSlotForInventoryType($itemInfo->inventory_type);
				$randomProp = $this->itemDataProvider->getRandomPropertyValue($itemSparse->item_level, $itemInfo->subclass_id, $itemSparse->overall_quality, $itemInfo->inventory_type);
				$stats = [];

				foreach ($itemSparse->bonus_stat as $index => $stat) {
					if ($stat === -1 || $stat === 0)
						continue;

					$value = $this->calculateItemStat($stat, $randomProp, $itemSparse->item_level, $itemSparse->stat_pct[$index], $multiplierSlot);
					if ($value < 1)
						continue;

					$stats[$stat] = ($stats[$stat] ?? 0) + $value;
				}

				foreach ($stats as $statID => $statValue)
					$this->output .= $this->createItemLine('item-stat', \sprintf('+%d %s', $statValue, ITEM_STAT_NAMES[$statID] ?? 'Stat ' . $statID));
			}

			$description = \strlen($itemSparse->description) > 0 ? $itemSparse->description : null;
			$this->renderItemEffects($description);
			$this->renderRequiredLevels($itemSparse);

			// There's something more to this. Buzzer Blade (2169) has a sell_price of
			// 1886 but in-game is shown as 1871. Doesn't appear to be price_variance
			// or price_random_value.
			$sellPrice = $itemSparse->sell_price;
			if ($sellPrice > 0) {
				// Based on SharedXML/FormattingUtil.lua:GetMoneyString()
				$gold = floor($sellPrice / (COPPER_PER_SILVER * SILVER_PER_GOLD));
				$silver = floor(($sellPrice - ($gold * COPPER_PER_SILVER * SILVER_PER_GOLD)) / COPPER_PER_SILVER);
				$copper = fmod($sellPrice, COPPER_PER_SILVER);

				$priceLine = $this->createItemLine('item-price', 'Sell Price: ');

				if ($gold > 0)
					$priceLine->content .= \sprintf('<span class="gold">%d</span>', $gold);

				if ($silver > 0)
					$priceLine->content .= \sprintf('<span class="silver">%d</span>', $silver);

				if ($copper > 0)
					$priceLine->content .= \sprintf('<span class="copper">%d</span>', $copper);

				$this->output .= $priceLine;
			}
		}

		/**
		 * @param int $stat
		 * @param float $randomProp
		 * @param int $itemLevel
		 * @param int $statPct
		 * @param string $multiplierSlot
		 * @return float
		 */
		private function calculateItemStat($stat, $randomProp, $itemLevel, $statPct, $multiplierSlot) {
			// TODO: Socket penalty?

			// https://i.imgur.com/zSRrFUo.png
			$base = ($statPct * $randomProp) * 0.000099999997;
			$multipliers = (object) ['armor_mult' => 1, 'weapon_mult' => 1, 'trinket_mult' => 1, 'jewelry_mult' => 1];

			if (\in_array($stat, COMBAT_RATING_STATS))
				$multipliers = $this->itemDataProvider->getCombatRatingMultipliers($itemLevel) ?? $multipliers;
			else if ($stat === ITEM_STAT_STAMINA)
				$multipliers = $this->itemDataProvider->getStaminaMultipliers($itemLevel) ?? $multipliers;

			return round($multipliers->$multiplierSlot * $base);
		}

		/**
		 * @param ArrayObject $itemInfo
		 * @param ArrayObject $itemSearch
		 */
		private function renderUsingSearch($itemInfo, $itemSearch) {
			$itemName = new MarkupElement('tt-title');
			$itemName->addAttribute('data-quality', $itemSearch->overall_quality);
			$itemName->content = $itemSearch->name;

			$this->output .= $itemName->__toString();

			$this->renderItemLevel($itemInfo, $itemSearch);
			$this->renderItemType($itemInfo);

			if (($itemSearch->flags[0] & 0x80000) === 0x00080000)
				$this->output .= $this->createItemLine('unique-equip', 'Unique-Equipped');

			// 23442 Unique

			$this->renderItemEffects();
			$this->renderRequiredLevels($itemSearch);
		}

		/**
		 * @param string|null $description
		 */
		private function renderItemEffects($description = null) {
			$effects = $this->itemDataProvider->getItemEffects($this->itemID);
			$spellProvider = $this->provider->getSpellDataProvider();

			$usedDescription = false;
			foreach ($effects as $effect) {
				$prefix = ITEM_TRIGGERS[$effect->trigger_type] ?? null;

				if ($prefix !== null) {
					$spellDescription = !$usedDescription ? $description : null;

					if ($spellDescription === null) {
						$tokenizer = new SpellTooltipTokenizer($spellProvider->getSpellDescription($effect->spell_id));
						$renderer = new SpellTooltipRenderer($tokenizer->parse(), $effect->spell_id, $this->provider);
						$spellDescription = $renderer->render(false);
					} else {
						$usedDescription = true;
					}

					$this->output .= $this->createItemLine('item-effect', \sprintf('%s: %s', $prefix, $spellDescription));
				}
			}

			// Is there a flag to control this instead?
			if (!$usedDescription && $description !== null)
				$this->output .= $this->createItemLine('description', \sprintf('"%s"', $description));
		}

		/**
		 * @param ArrayObject $itemData
		 */
		private function renderRequiredLevels($itemData) {
			// Requires Level 60
			if ($itemData->required_level > 1)
				$this->output .= $this->createItemLine('req-level', 'Requires Level ' . $itemData->required_level);

			// Requires Skinning (1)
			if ($itemData->required_skill > 0 && $itemData->required_skill_rank > 0) {
				$skillName = $this->itemDataProvider->getSkillName($itemData->required_skill);
				$this->output .= $this->createItemLine('req-skill', \sprintf('Requires %s (%d)', $skillName, $itemData->required_skill_rank));
			}

			// Requires Apprentice Riding
			if ($itemData->required_ability > 0) {
				$abilityName = $this->provider->getSpellDataProvider()->getSpellName($itemData->required_ability);
				$this->output .= $this->createItemLine('req-ability', 'Requires ' . $abilityName);
			}
		}

		/**
		 * @param ArrayObject $itemInfo
		 * @param ArrayObject $itemData
		 */
		private function renderItemLevel($itemInfo, $itemData) {
			// Only show item level on Weapon/Armor items?
			if ($itemInfo->class_id === 4 || $itemInfo->class_id === 2)
				$this->output .= $this->createItemLine('item-level', \sprintf('Item Level %d', $itemData->item_level));
		}

		/**
		 * @param ArrayObject $itemInfo
		 */
		private function renderItemType($itemInfo) {
			// Do we only show this for Weapon/Armor?
			if ($itemInfo->class_id === 4 || $itemInfo->class_id === 2) {
				$classLine = new MarkupElement('tt-item-double-line');
				$classLine->content = $this->createItemLine('item-type', INVENTORY_TYPE_NAME[$itemInfo->inventory_type]);

				if ($itemInfo->subclass_id > 0)
					$classLine->content .= $this->createItemLine('item-sub-class', ITEM_SUB_CLASS[$itemInfo->class_id][$itemInfo->subclass_id]);

				$this->output .= $classLine;
			}
		}

		/**
		 * @param string $type
		 * @param string $content
		 * @return MarkupElement
		 */
		private function createItemLine($type, $content = '') {
			return (new MarkupElement('tt-item-line'))->addAttribute('data-line-type', $type)->setContent($content);
		}

		/**
		 * @param int $inventoryType
		 * @return string
		 */
		private static function getMultiplierSlotForInventoryType($inventoryType):string {
			switch ($inventoryType) {
				case INVENTORY_TYPE_NECK:
				case INVENTORY_TYPE_FINGER:
					return 'jewelry_mult';

				case INVENTORY_TYPE_TRINKET:
					return 'trinket_mult';

				case INVENTORY_TYPE_ONEHAND:
				case INVENTORY_TYPE_SHIELD:
				case INVENTORY_TYPE_RANGED:
				case INVENTORY_TYPE_TWOHAND:
				case INVENTORY_TYPE_MAINHAND:
				case INVENTORY_TYPE_OFFHAND:
				case INVENTORY_TYPE_HELDINOFFHAND:
				case INVENTORY_TYPE_RANGEDRIGHT:
					return 'weapon_mult';

				default:
					return 'armor_mult';
			}
		}

		/**
		 * @var int
		 */
		private $itemID;

		/**
		 * @var DataProvider
		 */
		private $provider;

		/**
		 * @var ItemDataProvider
		 */
		private $itemDataProvider;
	}