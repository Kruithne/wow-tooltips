<?php
	require_once(__DIR__ . '/SpellTooltipConstants.php');

	class SpellTooltipRenderer {
		/**
		 * @param stdClass $tokens
		 * @param int $rootSpellID
		 * @param SpellDataProvider $provider
		 */
		public function __construct($tokens, $rootSpellID = 0, $provider) {
			$this->output = '';
			$this->tokens = $tokens;
			$this->rootSpellID = $rootSpellID;
			$this->provider = $provider;
			$this->difficulty = $provider->difficulty;
			$this->spellDataProvider = $provider->getSpellDataProvider();
			$this->lastVariable = null;
		}

		/**
		 * @param boolean $isFullTooltip
		 * @return string
		 */
		public function render($isFullTooltip = false):string {
			if ($isFullTooltip && $this->rootSpellID > 0) {
				$spellName = $this->spellDataProvider->getSpellName($this->rootSpellID);
				$this->output .= TooltipGenerics::wrapStringTags('tt-title', $spellName);
			}

			// TODO: Include other meta-data for spell tooltips (range, cast, etc).

			$this->renderComponent($this->tokens);
			return $this->output;
		}

		/**
		 * @param stdClass $component
		 */
		private function renderComponent($component) {
			switch ($component->type) {
				case TOKEN_ROOT: $this->renderRoot($component); break;
				case TOKEN_STRING: $this->renderString($component); break;
				case TOKEN_VARIABLE: $this->renderVariable($component); break;
				case TOKEN_EXTERNAL_VARIABLE: $this->renderExternalVariable($component); break;
				case TOKEN_EXPRESSION: $this->renderExpression($component); break;
				case TOKEN_BULLET: $this->renderBullet($component); break;
				case TOKEN_CONDITION_SWITCH: $this->renderConditionSwitch($component); break;
				case TOKEN_CONDITION_CHAIN: $this->renderConditionChain($component); break;
				case TOKEN_NAMED_VARIABLE: $this->output .= TooltipGenerics::getStringTag($component->name); break;
				case TOKEN_DIFFICULTY_BLOCK: $this->renderDifficultyBlock($component); break;
				case TOKEN_NUMBER: $this->output .= $component->value; break;
				case TOKEN_UI_ESCAPE_SEQUENCE: $this->renderEscapeSequence($component); break;
			}
		}

		/**
		 * @param stdClass $root
		 */
		private function renderRoot($root) {
			foreach ($root->components as $component)
				$this->renderComponent($component);
		}

		/**
		 * @param stdClass $component
		 */
		private function renderBullet($component) {
			$this->output .= TooltipGenerics::getStringTag('tt-bullet', false);

			foreach ($component->components as $component)
				$this->renderComponent($component);

			$this->output .= TooltipGenerics::getClosingStringTag('tt-bullet');
		}

		/**
		 * @param stdClass $component
		 */
		private function renderEscapeSequence($component) {
			switch ($component->escapeType) {
				case UI_ESCAPE_COLOR:
					$element = new MarkupElement('tt-inline-color', false);
					$element->addAttribute('data-color', $component->color);
					$this->output .= $element->__toString();
					break;

				case UI_ESCAPE_RESET:
					$this->output .= TooltipGenerics::getClosingStringTag('tt-inline-color');
					break;

				case UI_ESCAPE_ICON:
					$element = new MarkupElement('tt-inline-icon');
					$element->addAttribute('data-id', $component->fileDataID);

					// Since 99% of inlined icons are 24x24, we implicitly skip over dimensions
					// of that value here to avoid unnecessary data/parsing and default to 24x24
					// on the client-side.

					// To emulate the game, a texture height/width of 0 would use the height of the
					// text instead, but we don't want to follow that here so we skip 0/0 as well.
					// https://wowpedia.fandom.com/wiki/UI_escape_sequences

					$texHeight = $component->texHeight ?? 0;
					if ($texHeight > 0 && $texHeight !== 24)
						$element->addAttribute('data-height', $texHeight);

					$texWidth = $component->texWidth ?? 0;
					if ($texWidth > 0 && $texWidth !== 24)
						$element->addAttribute('data-width', $texWidth);

					// Similar to dimensions, offsets default to 0, 0 so we skip over those
					// values as well.

					$ofsX = $component->ofsX ?? 0;
					$ofsY = $component->ofsY ?? 0;

					if ($ofsX > 0) $element->addAttribute('data-offset-x', $ofsX);
					if ($ofsY > 0) $element->addAttribute('data-offset-y', $ofsY);

					$this->output .= $element->__toString();

					break;

				case UI_ESCAPE_HYPERLINK:
					$element = new MarkupElement('tt-inline-link', false);
					$element->addAttribute('data-link-type', $component->linkType);
					
					if (\count($component->linkArgs))
						$element->addAttribute('data-link-args', $component->linkArgs);

					$this->output .= $element->__toString();
					break;

				case UI_ESCAPE_HYPERLINK_END:
					$this->output .= TooltipGenerics::getClosingStringTag('tt-inline-link');
					break;
			}
		}

		/**
		 * @param stdClass $component
		 */
		private function renderString($component) {
			$this->output .= $component->text;
		}

		/**
		 * @param stdClass $component
		 * @return mixed
		 */
		private function getVariable($component) {
			switch ($component->variableType) {
				case VAR_TYPE_EFFECT_BASE_POINTS: return $this->getVarEffectBasePoints($component);
				case VAR_TYPE_MISC_DURATION: return $this->getVarMiscDuration($component);
				case VAR_TYPE_EFFECT_AURA_PERIOD: return $this->getVarEffectAuraPeriod($component);
				case VAR_TYPE_EFFECT_AMPLITUDE: return $this->getVarEffectAmplitude($component);
				case VAR_TYPE_EFFECT_RADIUS_0: return $this->getVarEffectRadius($component, 0);
				case VAR_TYPE_EFFECT_RADIUS_1: return $this->getVarEffectRadius($component, 1);
				case VAR_TYPE_MISC_RANGE_MAX: return $this->getVarEffectRange($component, 'range_max');
				case VAR_TYPE_MISC_RANGE_MIN: return $this->getVarEffectRange($component, 'range_min');
				case VAR_TYPE_AURA_CUMULATIVE: return $this->getVarAuraCumulative($component);
				case VAR_TYPE_EFFECT_CHAIN_TARGETS: return $this->getVarEffectChainTargets($component);
				case VAR_TYPE_TARGET_RESTRICTIONS_MAX_TARGETS: return $this->getVarTargetRestrictions($component, 'max_targets');
				case VAR_TYPE_TARGET_RESTRICTIONS_MAX_LEVEL: return $this->getVarTargetRestrictions($component, 'max_target_level');
				case VAR_TYPE_POWER_MANA_COST: return $this->getVarPowerManaCost($component);
				case VAR_TYPE_EFFECT_CHAIN_AMPLITUDE: return $this->getVarEffectChainAmplitude($component);
				case VAR_TYPE_EFFECT_VARIANCE_MIN: return $this->getVarEffectVarianceMin($component);
				case VAR_TYPE_EFFECT_VARIANCE_MAX: return $this->getVarEffectVarianceMax($component);
				case VAR_TYPE_AURA_PROC_CHANCE: return $this->getVarAuraProcChance($component);
				case VAR_TYPE_ENCHANTMENT: return $this->getVarEnchantment($component);
				case VAR_TYPE_ENCHANTMENT_MAX: return $this->getVarEnchantmentMax($component);
				case VAR_TYPE_EFFECT: return $this->getVarEffect($component);
				case VAR_TYPE_PLAYER_LEVEL: return TooltipGenerics::getStringTag('Player Level');
				case VAR_TYPE_CONTENT_TUNING_MIN_LEVEL: return $this->getVarContentTuningLevel($component, 'min_level');
				case VAR_TYPE_CONTENT_TUNING_MAX_LEVEL: return $this->getVarContentTuningLevel($component, 'max_level');
				case VAR_TYPE_EFFECT_POINTS_PER_RESOURCE: return $this->getVarEffectPointsPerResource($component);
				case VAR_TYPE_STAT_STRENGTH: return TooltipGenerics::getStringTag('Strength');
				case VAR_TYPE_STAT_AGILITY: return TooltipGenerics::getStringTag('Agility');
				case VAR_TYPE_STAT_STAMINA: return TooltipGenerics::getStringTag('Stamina');
				case VAR_TYPE_STAT_INTELLECT: return TooltipGenerics::getStringTag('Intellect');
				case VAR_TYPE_STAT_SPIRIT: return TooltipGenerics::getStringTag('Spirit');
				case VAR_TYPE_AURA_PROC_RECOVERY: return $this->getVarAuraProcRecovery($component);
				case VAR_TYPE_AURA_PROC_CHARGES: return $this->getVarAuraProcCharges($component);
				case VAR_TYPE_SPELL_AURA_PROC_PER_MINUTE: return $this->getVarAuraProcPerMinute($component);
				case VAR_TYPE_BONUS_COEFFICIENT: return $this->getVarEffectBonusCoefficient($component);
				case VAR_TYPE_EFFECT_MISC_VALUE: return $this->getVarEffectMiscValue($component);
				case VAR_TYPE_PLAYER_SPECIALIZATION: return TooltipGenerics::getStringTag('Class Specialization');
				case VAR_TYPE_LEVEL_MAX: return $this->getVarSpellMaxLevel($component);
				case VAR_TYPE_STAT_RANGED_ATTACK_POWER: return TooltipGenerics::getStringTag('Ranged Attack Power');
				case VAR_TYPE_STAT_ATTACK_POWER: return TooltipGenerics::getStringTag('Attack Power');
				case VAR_TYPE_STAT_SPELL_POWER: return TooltipGenerics::getStringTag('Spell Power');
				case VAR_TYPE_MAX_HEALTH: return TooltipGenerics::getStringTag('Max Health');
				case VAR_TYPE_STAT_SHADOW_PCT: return TooltipGenerics::getStringTag('Shadow%');
				case VAR_TYPE_WEAPON_DAMAGE_AMOUNT: return TooltipGenerics::getStringTag('Weapon Damage');
				case VAR_TYPE_HEARTHSTONE_LOCATION: return TooltipGenerics::getStringTag('Hearthstone Location');
				case VAR_TYPE_ITEM_OWB: return TooltipGenerics::getStringTag('Off-Hand Weapon Damage');
				case VAR_TYPE_ITEM_OWS: return TooltipGenerics::getStringTag('Off-Hand Weapon Speed');
				case VAR_TYPE_ITEM_RWS: return TooltipGenerics::getStringTag('Ranged Weapon Speed');
				case VAR_TYPE_ITEM_MWB: return TooltipGenerics::getStringTag('Main Weapon Damage');
				case VAR_TYPE_ITEM_MWS: return TooltipGenerics::getStringTag('Main Weapon Speed');
				case VAR_TYPE_PRIMARY_STAT: return TooltipGenerics::getStringTag('Primary Stat');
				case VAR_TYPE_GARR_AB_ACTION_FLAT: return $this->getGarrisonAbilityEffect($component->fieldIndex, 'action_value_flat');
				case VAR_TYPE_GARR_AB_COMBAT_MAX: return $this->getGarrisonAbilityEffect($component->fieldIndex, 'combat_weight_max');
				case VAR_TYPE_GARR_AB_COMBAT_BASE: return $this->getGarrisonAbilityEffect($component->fieldIndex, 'combat_weight_base');
				case VAR_TYPE_GARR_AB_ACTION_HOURS: return $this->getGarrisonAbilityEffect($component->fieldIndex, 'action_hours');
			}
		}

		/**
		 * @param stdClass $component
		 * @return mixed
		 */
		private function getExternalVariable($component) {
			switch ($component->key) {
				case EXTERNAL_VAR_SPELL_NAME: return $this->getExtSpellName($component->value);
				case EXTERNAL_VAR_SPELL_DESC: return $this->getExtSpellDescription($component->value, false);
				case EXTERNAL_VAR_SPELL_ICON: return $this->getExtSpellIcon($component->value);
				case EXTERNAL_VAR_SPELL_TOOLTIP: return $this->getExtSpellDescription($component->value, true);
				case EXTERNAL_VAR_SPELL_ID: return $this->getExtSpellID($component->value);
				case EXTERNAL_VAR_SPELL_AURA: return $this->getExtSpellAura($component->value);
				case EXTERNAL_VAR_GARR_AB_DESC: return $this->getExtGarrisonAbility($component->value);
				case EXTERNAL_VAR_GARR_BUILDING: return $this->getExtGarrisonBuilding($component->value);
				case EXTERNAL_VAR_SWITCH: return $this->getExtSwitchStatement($component);
				case EXTERNAL_VAR_LOOT_SPEC: return TooltipGenerics::getStringTag('Player Loot Specialization');
				case EXTERNAL_VAR_CLASS: return TooltipGenerics::getStringTag('Player Class');
				case EXTERNAL_VAR_CLASS_SPEC: return TooltipGenerics::getStringTag('Player Specialization');
				case EXTERNAL_VAR_VERS_DAMAGE: return TooltipGenerics::getStringTag('Versatility');
			}
		}

		/**
		 * @param stdClass $component
		 */
		private function renderDifficultyBlock($component) {
			if (\in_array($this->difficulty, $component->filter)) {
				$element = new MarkupElement('tt-difficulty');
				$element->addAttribute('data-dangerous', $component->isDangerous);

				$renderer = new SpellTooltipRenderer($component->content, $this->rootSpellID, $this->provider);
				$element->content = \trim($renderer->render(false));

				$this->output .= $element->__toString();
			}
		}

		/**
		 * @param stdClass $component
		 */
		private function renderConditionSwitch($component) {
			// Condition switches are inline ternary statements that alternate
			// between two possible values depending on the condition type.
			
			if ($component->conditionType === SWITCH_TYPE_GENDER) {
				// SWITCH_TYPE_GENDER shows the first option if the player character
				// is male, or the second option if they are female. Since we don't
				// have a "player", just display both sides of the statement.
				$this->output .= $component->consequent . '/' . $component->alternative;
			} else  if ($component->conditionType === SWITCH_TYPE_PLURALITY) {
				// SWITCH_TYPE_PLURALITY is based off the *previous* variable used.
				// If the variable is singular (1), then the first option is used,
				// otherwise the second option is used.

				// Non-strict check to match 1f/1.0r
				$this->output .= $this->lastVariable == 1 ? $component->consequent : $component->alternative;
			}
		}

		/**
		 * @param stdClass $component
		 */
		private function renderConditionChain($component) {
			// Condition chains are if/else statements. $component->conditions
			// is an array of conditions, each with a consequent. If the condition
			// is met, then the consequent should be rendered and the chain broken.
			// If the consequent is not met, move to the next condition and repeat.
			// The final condition in the chain may provide an alternative, which
			// should be rendered if no condition in the chain is met.

			// Since condition chains check player-related data such as quests,
			// auras and specializations, we don't evaluate the conditions during
			// rendering and instead just render the first consequent.

			$render = $component->conditions[0]->consequent;
			$renderer = new SpellTooltipRenderer($render, $this->rootSpellID, $this->provider);
			$this->output .= $renderer->render(false);
		}

		/**
		 * @param stdClass $component
		 */
		private function renderExternalVariable($component) {
			$value = $this->getExternalVariable($component);
			$this->output .= $value;
		}

		/**
		 * @param stdClass $component
		 */
		private function renderVariable($component) {
			$value = $this->getVariable($component);

			if ($component->variableType === VAR_TYPE_MISC_DURATION) {
				if ($value === -1) {
					$this->output .= 'until canceled';
				} else if ($value >= MS_DAY) {
					$days = \floor($value / MS_DAY);
					$this->output .= $days . ($days > 1 ? ' days' : 'day');
				} else if ($value >= MS_HOUR) {
					$hours = \floor($value / MS_HOUR);
					$this->output .= $hours . ($hours > 1 ? ' hours' : ' hour');
				} else if ($value >= MS_MINUTE) {
					$minutes = \floor($value / MS_MINUTE);
					$this->output .= $minutes . ' min'; // WoW does not pluralize minutes.
				} else {
					$seconds = \floor($value / MS_SECOND);
					$this->output .= $seconds . ' sec'; // WoW does not pluralize seconds.
				}

				return;
			}

			if (isset($component->math)) {
				switch ($component->math->operator) {
					case MATH_DIVIDE: $value /= $component->math->value; break;
					case MATH_MULTIPLY: $value *= $component->math->value; break;
				}
			}

			// From observation it appears that numeric values that appear in tooltips
			// as standalone variables are rounded. Only those shown in an expression
			// with implicit precision defined are shown otherwise. (Example 2828)
			if (\is_int($value) || \is_float($value))
				$value = \round($value);

			$this->output .= $value;
			$this->lastVariable = $value;
		}

		/**
		 * @param stdClass $component
		 */
		private function renderExpression($component) {
			$value = $this->resolveExpressionGroup($component);

			if (\is_string($value)) {
				$this->output .= $value;
			} else {
				// Handle floating-point precision rendering.
				$precision = $component->precision ?? 0;
				$parts = \explode('.', (string) $value, 2);

				if ($precision > 0) {
					$this->output .= $parts[0] . '.';

					$tail = $parts[1] ?? '';
					if (strlen($tail) < $precision) {
						$this->output .= str_pad($tail, $precision, '0');
					} else {
						$this->output .= substr($tail, 0, $precision);
					}
				} else {
					$this->output .= $parts[0];
				}
			}
		}

		/**
		 * @param stdClass $component
		 * @return mixed
		 */
		private function resolveMathFunction($component) {
			if ($component->functionType === MATH_FUNCTION_FLOOR) {
				$value = $this->resolveExpressionGroup($component->parameters->components[0]);
				return \floor($value);
			}

			if ($component->functionType === MATH_FUNCTION_GREATER_THAN) {
				$a = $this->resolveExpressionGroup($component->parameters->components[0]);
				$b = $this->resolveExpressionGroup($component->parameters->components[1]);
				return $a > $b;
			}

			if ($component->functionType === MATH_FUNCTION_CONDITION) {
				$condition = $this->resolveExpressionGroup($component->parameters->components[0]);
				$consequent = $this->resolveExpressionGroup($component->parameters->components[1]);
				$alternative = $this->resolveExpressionGroup($component->parameters->components[2]);

				return $condition ? $consequent : $alternative;
			}

			if ($component->functionType === MATH_FUNCTION_MAX) {
				$a = $this->resolveExpressionGroup($component->parameters->components[0]);
				$b = $this->resolveExpressionGroup($component->parameters->components[1]);

				return \max($a, $b);
			}

			return 0;
		}

		/**
		 * @param stdClass $group
		 * @return number|string
		 */
		private function resolveExpressionGroup($group) {
			$stack = [];
			
			$groupContainsString = false;
			$operationCount = 0;

			// Start with isPreviousOperator set to `true` to allow leading negative
			// numbers at the start of a stack.
			$isPreviousOperator = true;
			$isNegative = false;

			foreach ($group->components as $component) {
				$value = null;

				if ($component->type === TOKEN_MATH_OPERATOR) {
					if ($isPreviousOperator && $component->operator === MATH_SUBTRACT) {
						$isNegative = true;
					} else {
						$operationCount++;
						$value = $component;
					}

					$isPreviousOperator = true;
				} else {
					if ($component->type === TOKEN_MATH_FUNCTION) {
						$value = $this->resolveMathFunction($component);
					} else if ($component->type === TOKEN_GROUP) {
						$value = $this->resolveExpressionGroup($component);
					} else if ($component->type === TOKEN_VARIABLE) {
						$value = $this->getVariable($component);
					} else if ($component->type === TOKEN_EXTERNAL_VARIABLE) {
						$value = $this->getExternalVariable($component);
					} else if ($component->type === TOKEN_NAMED_VARIABLE) {
						$value = TooltipGenerics::getStringTag($component->name);
					} else if ($component->type === TOKEN_NUMBER) {
						$value = $component->value;

						if ($isNegative) {
							$value *= -1;
							$isNegative = false;
						}
					}

					$isPreviousOperator = false;
				}

				if ($value !== null) {
					\array_push($stack, $value);

					if (\is_string($value))
						$groupContainsString = true;
				}
			}

			// In the event of a string-variable appearing in the group, we
			// need to render the expression raw without computing it.
			if ($groupContainsString) {
				// Everything inside the expression needs to be a string.
				$stack = array_map(function($node) {
					if (\is_object($node)) {
						if ($node->type === TOKEN_MATH_OPERATOR)
							return \array_search($node->operator, MAP_MATH_OPERATOR);
					}

					return $node;
				}, $stack);

				return '(' . \implode(' ', $stack) . ')';
			} else {
				// Compute the expression and return a numerical value.
				$operators = [];
				foreach ($stack as $component)
					if (\is_object($component))
						\array_push($operators, $component->operator);

				usort($operators, function($a, $b) {
					return EXPR_EVAL_ORDER[$a] - EXPR_EVAL_ORDER[$b];
				});

				while (\count($operators) > 0) {
					$operation = \array_shift($operators);
					
					$index = -1;
					for ($i = 0; $i < \count($stack); $i++) {
						$obj = $stack[$i];
						if (is_object($obj) && $obj->operator === $operation) {
							$index = $i;
							break;
						}
					}

					$slice = \array_splice($stack, $index - 1, 3);
					
					$value = $slice[0];
					switch ($slice[1]->operator) {
						case MATH_DIVIDE: $value /= $slice[2]; break;
						case MATH_MULTIPLY: $value *= $slice[2]; break;
						case MATH_ADDITION: $value += $slice[2]; break;
						case MATH_SUBTRACT: $value -= $slice[2]; break;
					}

					\array_splice($stack, $index, 0, $value);
				}

				return $stack[0];
			}
		}

		/**
		 * @param stdClass $component
		 * @return string
		 */
		private function getExtSwitchStatement($component) {
			// It's not currently understood how the switch statement functions however
			// it appears to be operating as some kind of switch/case statement.
			// https://marlamin.com/u/FnRPXtzxnB.txt

			// To process this fully, we'd take $component->condition and evaluate it
			// based on the function linked above, and then return the correct case
			// from $component->cases (array).

			// Since we don't currently evaluate conditions, we just return the
			// first case available.
			$case = $component->cases[0] ?? null;
			if ($case !== null) {
				$renderer = new SpellTooltipRenderer($case, $this->rootSpellID, $this->provider);
				return $renderer->render(false);
			}

			return '';
		}

		/**
		 * @param int $buildingID
		 * @return string
		 */
		private function getExtGarrisonBuilding($buildingID):string {
			$garrisons = $this->provider->getGarrisonDataProvider();
			$buildings = $garrisons->getSameTypeBuildings($buildingID);

			$output = '';
			foreach ($buildings as $building) {
				$output .= TooltipGenerics::wrapStringTags('tt-garr-building-title', 'Level ' . $building->upgrade_level);
				$output .= TooltipGenerics::wrapStringTags('tt-garr-building-desc', $building->tooltip);
			}

			return \trim($output);
		}

		/**
		 * @param int $abilityID
		 * @return string
		 */
		private function getExtGarrisonAbility($abilityID):string {
			$garrisons = $this->provider->getGarrisonDataProvider();
			$description = $garrisons->getAbilityDescription($abilityID);

			if ($description === null)
				return 'GarrisonAbility#' . $abilityID;

			$tokenizer = new SpellTooltipTokenizer($description, TOKEN_ROOT, FLAG_IS_GARR_AB);
			$renderer = new SpellTooltipRenderer($tokenizer->parse(), $abilityID, $this->provider);

			return $renderer->render();
		}

		/**
		 * @param int $abilityEffectID
		 * @param string $fieldKey
		 * @return string
		 */
		private function getGarrisonAbilityEffect($abilityEffectID, $fieldKey):string {
			$garrisons = $this->provider->getGarrisonDataProvider();
			$effect = $garrisons->getAbilityEffect($abilityEffectID);
			
			return $effect->$fieldKey ?? '';
		}

		/**
		 * @param int $spellID
		 * @return string
		 */
		private function getExtSpellIcon($spellID):string {
			$element = new MarkupElement('tt-inline-icon');
			$element->addAttribute('data-id', $this->spellDataProvider->getSpellIcon($spellID));
			return $element->__toString();
		}

		/**
		 * @param int $spellID
		 * @return string
		 */
		private function getExtSpellName($spellID):string {
			return TooltipGenerics::wrapStringTags('tt-spell-name', $this->spellDataProvider->getSpellName($spellID));
		}

		/**
		 * @param int $spellID
		 * @return string
		 */
		private function getExtSpellID($spellID):string {
			// Blizzard doesn't actually handle this in-game (see 164449), so this
			// is just the obvious guess at how it *should* be handled.
			return (string) $spellID;
		}

		/**
		 * @param int $spellID
		 * @param boolean $isFullTooltip
		 * @return string
		 */
		private function getExtSpellDescription($spellID, $isFullTooltip):string {
			$description = $this->spellDataProvider->getSpellDescription($spellID);

			if ($description === null)
				return 'SpellDescription#' . $spellID;

			$tokenizer = new SpellTooltipTokenizer($description);
			$renderer = new SpellTooltipRenderer($tokenizer->parse(), $spellID, $this->provider);
			return $renderer->render($isFullTooltip);
		}

		/**
		 * @param int $spellID
		 * @return string
		 */
		private function getExtSpellAura($spellID) {
			$aura = $this->spellDataProvider->getSpellAuraDescription($spellID);

			if ($aura === null)
				return 'SpellAura#' . $spellID;

			$tokenizer = new SpellTooltipTokenizer($aura);

			$tokens = $tokenizer->parse();

			$renderer = new SpellTooltipRenderer($tokens, $spellID, $this->provider);
			$this->output .= $renderer->render(false);
		}

		/**
		 * @param stdClass $component
		 * @return int
		 */
		private function getVarEffectAuraPeriod($component):int {
			$period = $this->spellDataProvider->getEffectAuraPeriod($component->spellID ?? $this->rootSpellID, $component->fieldIndex ?? 0);
			
			// Unable to verify if this division should take place here, affecting the raw variable which
			// may be used in calculations, or after (in renderVariable) as a visual-only.
			return $period / 1000;
		}

		/**
		 * @param stdClass $component
		 * @return float
		 */
		private function getVarEffectAmplitude($component):float {
			return $this->spellDataProvider->getEffectAmplitude($component->spellID ?? $this->rootSpellID, $component->fieldIndex ?? 0);
		}

		/**
		 * @param stdClass $component
		 * @param int $index
		 */
		private function getVarEffectRadius($component, $index) {
			$radiusIndex = MAP_RADIUS_INDEX[$component->variableType];
			return $this->spellDataProvider->getEffectRadius($component->spellID ?? $this->rootSpellID, $component->fieldIndex, $radiusIndex);
		}

		/**
		 * @param stdClass $component
		 * @param string $fieldKey
		 * @return float
		 */
		private function getVarEffectRange($component, $fieldKey):float {
			$range = $this->spellDataProvider->getSpellRange($component->spellID ?? $this->rootSpellID);
			return $range->$fieldKey[$component->fieldIndex] ?? 0;
		}

		/**
		 * @param stdClass $component
		 * @return int
		 */
		private function getVarAuraCumulative($component):int {
			return $this->spellDataProvider->getAuraCumulative($component->spellID ?? $this->rootSpellID);
		}

		/**
		 * @param stdClass $component
		 * @return int
		 */
		private function getVarEffectChainTargets($component):int {
			return $this->spellDataProvider->getEffectChainTargets($component->spellID ?? $this->rootSpellID, $component->fieldIndex ?? 0);
		}

		/**
		 * @param stdClass $component
		 * @param string $fieldKey
		 * @return int
		 */
		private function getVarTargetRestrictions($component, $fieldKey):int {
			$entry = $this->spellDataProvider->getTargetRestrictionsEntry($component->spellID ?? $this->rootSpellID);
			return $entry->$fieldKey ?? 0;
		}

		/**
		 * @param stdClass $component
		 * @return int
		 */
		private function getVarPowerManaCost($component):int {
			return $this->spellDataProvider->getSpellManaCost($component->spellID ?? $this->rootSpellID);
		}

		/**
		 * @param stdClass $component
		 * @return float
		 */
		private function getVarEffectChainAmplitude($component):float {
			return $this->spellDataProvider->getEffectChainAmplitude($component->spellID ?? $this->rootSpellID);
		}

		/**
		 * @param stdClass $component
		 * @return float
		 */
		private function getVarEffectVarianceMin($component):float {
			$effect = $this->spellDataProvider->getEffectEntry($component->spellID ?? $this->rootSpellID, $component->fieldIndex ?? 0);
			return $effect !== null ? $effect->effect_base_points - $effect->variance : 0;
		}

		/**
		 * @param stdClass $component
		 * @return float
		 */
		private function getVarEffectVarianceMax($component):float {
			$effect = $this->spellDataProvider->getEffectEntry($component->spellID ?? $this->rootSpellID, $component->fieldIndex ?? 0);
			return $effect !== null ? $effect->effect_base_points + $effect->variance : 0;
		}

		/**
		 * @param stdClass $component
		 * @return int
		 */
		private function getVarAuraProcChance($component):int {
			return $this->spellDataProvider->getAuraProcChance($component->spellID ?? $this->rootSpellID);
		}

		/**
		 * @param stdClass $component
		 * @return float
		 */
		private function getVarEnchantment($component):float {
			return $this->spellDataProvider->getEnchantmentValue($component->spellID ?? $this->rootSpellID, $component->fieldIndex ?? 0);
		}

		/**
		 * @param stdClass $component
		 * @return int
		 */
		private function getVarEnchantmentMax($component):int {
			return $this->spellDataProvider->getEnchantmentMax($component->spellID ?? $this->rootSpellID, $component->fieldIndex ?? 0);
		}

		/**
		 * @param stdClass $component
		 * @return string
		 */
		private function getVarEffect($component):string {
			$entry = $this->spellDataProvider->getEffectEntry($component->spellID ?? $this->rootSpellID, $component->fieldIndex ?? 0);
			if ($entry === null)
				return 0;

			// TODO: This needs further research and implementation.
			return 0;

			// Effect names:
			// https://github.com/Marlamin/wow.tools/blob/16192ae695b84b91277051dc709ff24bc41a6997/dbc/js/enums.js#L580

			// For SCHOOL_DAMAGE, the different school type (potentially SpellMisc::SchoolMask) needs to be taken into
			// account. For 31707, it should be spell power, but for 73899 it should be attack power.
			
			// Other effects have not been researched.

			switch ($entry->effect) {
				case 2: // SCHOOL_DAMAGE
					return TooltipGenerics::getStringTag(($entry->effect_bonus_coefficient * 100) . '% Spell Power');
					break;

				default: return 0;
			}
		}

		/**
		 * @param stdClass $component
		 * @param string $fieldKey
		 */
		private function getVarContentTuningLevel($component, $fieldKey) {
			$contentTuning = $this->provider->getContentTuningDataProvider();
			return $contentTuning->getByID($component->fieldIndex)->$fieldKey ?? 0;
		}

		/**
		 * @param stdClass $component
		 * @return float
		 */
		private function getVarEffectPointsPerResource($component):float {
			return $this->spellDataProvider->getEffectPointsPerResource($component->spellID ?? $this->rootSpellID, $component->fieldIndex ?? 0);
		}

		/**
		 * @param stdClass $component
		 * @return int
		 */
		private function getVarAuraProcRecovery($component):int {
			$period = $this->spellDataProvider->getAuraProcRecovery($component->spellID ?? $this->rootSpellID);
			// Unable to verify if this division should take place here, affecting the raw variable which
			// may be used in calculations, or after (in renderExternalVariable) as a visual-only.

			// Assumed latter, but 126237 has this included in an expression with the output being pre-divided.
			return $period / 1000;
		}

		/**
		 * @param stdClass $component
		 * @return float
		 */
		private function getVarAuraProcPerMinute($component):float {
			return $this->spellDataProvider->getProcsPerMinute($component->spellID ?? $this->rootSpellID);
		}

		/**
		 * @param stdClass $component
		 * @return int
		 */
		private function getVarSpellMaxLevel($component):int {
			return $this->spellDataProvider->getSpellMaxLevel($component->spellID ?? $this->rootSpellID);
		}
		
		/**
		 * @param stdClass $component
		 * @return float
		 */
		private function getVarEffectBonusCoefficient($component):float {
			return $this->spellDataProvider->getEffectBonusCoefficient($component->spellID ?? $this->rootSpellID, $component->fieldIndex ?? 0);
		}

		/**
		 * @param stdClass $component
		 * @return int
		 */
		private function getVarAuraProcCharges($component):int {
			return $this->spellDataProvider->getAuraProcCharges($component->spellID ?? $this->rootSpellID);
		}

		/**
		 * @param stdClass $component
		 * @return int
		 */
		private function getVarEffectMiscValue($component):int {
			return $this->spellDataProvider->getEffectMiscValue($component->spellID ?? $this->rootSpellID, $component->fieldIndex ?? 0);
		}

		/**
		 * @param stdClass $component
		 * @return float
		 */
		private function getVarEffectBasePoints($component):float {
			$value = $this->spellDataProvider->getEffectBasePoints($component->spellID ?? $this->rootSpellID, $component->fieldIndex ?? 0);
			return \abs($value); // Do we always return a positive number?
		}

		/**
		 * @param stdClass $component
		 * @return int
		 */
		private function getVarMiscDuration($component):int {
			return $this->spellDataProvider->getMiscDuration($component->spellID ?? $this->rootSpellID);
		}

		/**
		 * @var string
		 */
		private $output;

		/**
		 * @var stdClass
		 */
		private $tokens;

		/**
		 * @var int
		 */
		private $rootSpellID;

		/**
		 * @var SpellDataProvider
		 */
		private $provider;

		/**
		 * @var int
		 */
		private $difficulty;

		/**
		 * @var SpellDataProvider
		 */
		private $spellDataProvider;

		/**
		 * @var mixed
		 */
		private $lastVariable;
	}