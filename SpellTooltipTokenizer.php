<?php
	require_once(__DIR__ . '/SpellTooltipConstants.php');

	class SpellTooltipTokenizer {
		/**
		 * @param string $text
		 * @param string $rootType
		 * @param int $flags
		 */
		public function __construct($text, $rootType = TOKEN_ROOT, $flags = 0x0) {
			$this->text = $text;
			$this->chars = [];
			$this->charLengthArray = 0;
			$this->tree = (object) ['type' => $rootType, 'components' => []];
			$this->cursor = 0;
			$this->flags = $flags;

			$this->isExpression = ($flags & FLAG_IS_EXPRESSION) === FLAG_IS_EXPRESSION;
			$this->isGarrisonAbility = ($flags & FLAG_IS_GARR_AB) === FLAG_IS_GARR_AB;
		}

		/**
		 * @return Array
		 */
		public function parse() {
			$this->chars = \mb_str_split($this->text, 1, 'UTF-8');
			$this->charLengthArray = \count($this->chars);

			$chain = [$this->tree];

			if (($this->flags & FLAG_IS_PARAMS) === FLAG_IS_PARAMS) {
				$group = (object) ['type' => TOKEN_EXPRESSION, 'components' => []];
				\array_push($this->tree->components, $group);
				\array_push($chain, $group);
			}

			while ($this->cursor < $this->charLengthArray) {
				$char = $this->chars[$this->cursor];
				$ord = ord($char);
				
				$component = null;
				if ($ord === 0x24) {
					$this->cursor++;
					$component = $this->parseVariableToken(false);
				} else if ($ord === 0x7C) {
					$this->cursor++;
					$component = $this->parseEscapeSequence();
				} else if ($this->isExpression && \in_array($char, CHARSET_MATH)) {
					$component = (object) ['type' => TOKEN_MATH_OPERATOR, 'operator' => MAP_MATH_OPERATOR[$char]];
					$this->cursor++;
				} else if ($char === '(') {
					$group = (object) ['type' => TOKEN_EXPRESSION, 'components' => []];
					\array_push($chain[array_key_last($chain)]->components, $group);
					\array_push($chain, $group);
					$this->cursor++;
				} else if ($char === ')') {
					\array_pop($chain);
					$this->cursor++;
				} else if ($this->isExpression && $char === ',') {
					$group = (object) ['type' => TOKEN_EXPRESSION, 'components' => []];
					\array_pop($chain);
					\array_push($chain[array_key_last($chain)]->components, $group);
					\array_push($chain, $group);
					$this->cursor++;
				} else if ($this->isExpression && $char === '.' || \ctype_digit($char)) {
					$component = (object) ['type' => TOKEN_NUMBER, 'value' => $this->parseFloatLiteral()];
				} else {
					$component = $this->parseStringToken();
				}

				if ($component !== null)
					\array_push($chain[\array_key_last($chain)]->components, $component);
			}

			return $this->tree;
		}

		/**
		 * @param boolean $isCondition
		 * @return stdClass
		 */
		private function parseVariableToken($isCondition = false) {
			$token = (object) ['type' => TOKEN_VARIABLE, 'components' => []];

			$first = true;
			while ($this->cursor < $this->charLengthArray) {
				$char = $this->chars[$this->cursor];
				$component = null;

				if ($char === '?')
					return $this->parseConditionalChain();

				if ($char === '[' && $first)
					return $this->parseDifficultyBlock();

				if ($char === '{')
					return $this->parseExpression();

				if ($char === '<')
					return $this->parseNamedVariable();

				if ($first && ($char === '/' || $char === '*'))
					$char = $this->handleMathVariable($token);

				// If $char is NULL after handleMathVariable, then we encountered a
				// broken math operator variable at the end of input.
				if ($char === null)
					break;

				if ($char === '@') {
					$externalVariable = $this->parseExternalVariable();
					if ($externalVariable !== null)
						return $externalVariable;
				}
				
				if (!$this->isExpression) {
					if (\in_array($char, CHARSET_PLURALITY))
						return $this->parseConditionalSwitch(SWITCH_TYPE_PLURALITY);
					
					if (\in_array($char, CHARSET_GENDER))
						return $this->parseConditionalSwitch(SWITCH_TYPE_GENDER);
				}
				
				 if (\ctype_digit($char)) {
					$component = $this->parseIntLiteral();
				} else if (\ctype_alpha($char)) {
					$string = $this->parseAlphaLiteral();

					if ($this->isExpression) {
						$functionVar = MAP_MATH_FUNCTIONS[\strtolower($string)] ?? null;
						if ($functionVar !== null) {
							$functionBody = $this->parseCascadingSection('(', ')');
							$functionParser = new SpellTooltipTokenizer($functionBody, TOKEN_FUNCTION_PARAMS, $this->flags | FLAG_IS_EXPRESSION | FLAG_IS_PARAMS);
							return (object) ['type' => TOKEN_MATH_FUNCTION, 'functionType' => $functionVar, 'parameters' => $functionParser->parse()];
						}
					}

					// Despite being prefixed like a variable, $bullet; is a special case
					// where it should be left inline in the string and handled by the
					// game UI. To make parsing/rendering less confusing, we parse it as a
					// content block with everything that would be "indented" by the UI
					// stored as a component tree within.
					if ($string === 'bullet') {
						$content = $this->parseStringLiteral("\r", 1);
						$bulletTokenizer = new SpellTooltipTokenizer($content, TOKEN_BULLET);
						return $bulletTokenizer->parse();
					} else {
						$component = $string;
					}
				} else {
					break;
				}

				if ($component !== null)
					\array_push($token->components, $component);

				$first = false;
			}

			return $this->formatVariableToken($token, $isCondition);
		}

		/**
		 * @return stdClass
		 */
		private function parseEscapeSequence() {
			$id = $this->chars[$this->cursor];
			$component = (object) ['type' => TOKEN_UI_ESCAPE_SEQUENCE, 'escapeType' => 0];

			switch ($id) {
				case 'c':
					$component->escapeType = UI_ESCAPE_COLOR;
					$component->color = $this->parseString(8, 1);
					break;

				case 'r':
					$component->escapeType = UI_ESCAPE_RESET;
					$this->cursor++;
					break;

				case 'T':
					$component->escapeType = UI_ESCAPE_ICON;
					$this->parseIconEscapeSequenceParameters($component);
					break;

				case 'H':
					// https://wowpedia.fandom.com/wiki/Hyperlinks
					$arguments = \explode(':', $this->parseStringLiteral('|', 1, 2));
					$arguments = \array_map(function($arg) {
						if (\is_numeric(trim($arg)))
							return \strpos($arg, '.') ? (float) $arg : (int) $arg;

						return $arg;
					}, $arguments);

					$component->escapeType = UI_ESCAPE_HYPERLINK;
					$component->linkType = \array_shift($arguments);
					$component->linkArgs = $arguments;
					break;

				case 'h':
					// Handle the standalone "end hyperlink" tag.
					$component->escapeType = UI_ESCAPE_HYPERLINK_END;
					$this->cursor++;
					break;
			}

			return $component;
		}

		/**
		 * @param stdClass &$component
		 */
		private function parseIconEscapeSequenceParameters(&$component) {
			$raw = \explode(':', $this->parseStringLiteral('|', 1, 2));

			$component->fileDataID = (int) (\array_shift($raw) ?? 0);
			if (\count($raw)) $component->texHeight = (int) (\array_shift($raw));
			if (\count($raw)) $component->texWidth = (int) (\array_shift($raw));
			if (\count($raw)) $component->ofsX = (int) (\array_shift($raw));
			if (\count($raw)) $component->ofsY = (int) (\array_shift($raw));
		}

		/**
		 * @param stdClass $token
		 * @return string|null
		 */
		private function handleMathVariable($token) {
			$operator = MAP_MATH_OPERATOR[$this->chars[$this->cursor]];
			$this->cursor++;

			$token->math = (object) ['operator' => $operator , 'value' => $this->parseIntLiteral()];
			$this->cursor++;

			return $this->chars[$this->cursor] ?? null;
		}

		/**
		 * @param stdClass $token
		 * @param boolean $isCondition
		 * @return stdClass
		 */
		private function formatVariableToken($token, $isCondition = false) {
			$newToken = (object) ['type' => TOKEN_VARIABLE, 'variableType' => VAR_TYPE_UNKNOWN];

			if (isset($token->math))
				$newToken->math = $token->math;
			
			$component = \array_shift($token->components);
			if (\is_int($component)) {
				$newToken->spellID = $component;
				$component = \array_shift($token->components);
			}

			$variableMap = $isCondition ? MAP_CONDITION_TYPES : ($this->isGarrisonAbility ? MAP_GARR_AB_TYPES : MAP_VARIABLE_TYPES);
			$newToken->variableType = $variableMap[$component] ?? $variableMap[\strtolower($component)] ?? VAR_TYPE_UNKNOWN;
			$component = \array_shift($token->components);

			if ($component && \is_int($component))
				$newToken->fieldIndex = $component - 1;

			return $newToken;
		}

		/**
		 * @return stdClass
		 */
		private function parseDifficultyBlock() {
			$this->cursor++;
			$block = (object) ['type' => TOKEN_DIFFICULTY_BLOCK, 'filter' => [], 'isDangerous' => false, 'content' => ''];

			while ($this->cursor < $this->charLengthArray) {
				$char = $this->chars[$this->cursor];

				if ($char === ' ' || $char === ',') {
					$this->cursor++;
				} else if ($char === '!') {
					$block->isDangerous = true;
					$this->cursor++;
				} else if (\ctype_digit($char)) {
					\array_push($block->filter, $this->parseIntLiteral());
				} else {
					$content = \substr($this->parseCascadingSection('[', ']'), 0, -1);
					$blockTokenizer = new SpellTooltipTokenizer($content, TOKEN_ROOT);
					$block->content = $blockTokenizer->parse();
					break;
				}
			}

			return $block;
		}

		/**
		 * @return stdClass|null
		 */
		private function parseExternalVariable() {
			$startIndex = $this->cursor;

			$this->cursor++;
			$variableName = \strtolower($this->parseAlphaLiteral());

			if (isset(MAP_EXTERNAL_VAR[$variableName])) {
				$token = (object) ['type' => TOKEN_EXTERNAL_VARIABLE, 'key' => MAP_EXTERNAL_VAR[$variableName]];

				if ($token->key === EXTERNAL_VAR_SWITCH) {
					$this->parseSwitch($token);
				} else {
					$peek = $this->chars[$this->cursor + 1] ?? null;
					if ($peek !== null && \ctype_digit($peek))
						$token->value = $this->parseIntLiteral();
				}
				
				return $token;
			} else {
				$this->cursor = $startIndex;
				return null;
			}
		}

		/**
		 * @param stdClass $token
		 */
		private function parseSwitch($token) {
			//$@switch<$w1>[$@spellaura323725][$@spellaura323787][$@spellaura336307]
			$this->cursor += 2;
			$token->condition = $this->parseVariableToken();
			$this->cursor++;

			$token->cases = [];

			while (true) {
				$char = $this->chars[$this->cursor] ?? null;
				if ($char !== '[')
					break;

				$tokenizer = new SpellTooltipTokenizer($this->parseCascadingSection());
				\array_push($token->cases, $tokenizer->parse());
			}
		}

		/**
		 * @return stdClass
		 */
		private function parseNamedVariable() {
			// ${$s1*$<CAP>/$AP}
			return (object) ['type' => TOKEN_NAMED_VARIABLE, 'name' => $this->parseStringLiteral('>', 1, 1)];
		}

		/**
		 * @return stdClass
		 */
		private function parseExpression() {
			// ${$357919s1*$357919s3/100}.1
			$tokenizer = new SpellTooltipTokenizer($this->parseStringLiteral('}', 1, 1), TOKEN_EXPRESSION, $this->flags | FLAG_IS_EXPRESSION);
			$expression = $tokenizer->parse();

			if ($this->cursor < $this->charLengthArray && $this->chars[$this->cursor] === '.') {
				$peek = $this->chars[$this->cursor + 1] ?? null;
				if (\ctype_digit($peek)) {
					$expression->precision = (int) $peek;
					$this->cursor += 2;
				}
			}

			return $expression;
		}

		/**
		 * @param stdClass $group
		 * @return stdClass
		 */
		private function parseCondition(&$group = null) {
			// ((q57036)&!(q62690)&!(q62681))
			if ($this->chars[$this->cursor] === '(')
				$this->cursor++;

			if ($group === null)
				$group = (object) ['type' => TOKEN_CONDITION, 'groups' => []];

			while ($this->cursor < $this->charLengthArray) {
				$char = $this->chars[$this->cursor];

				if ($char === '[')
					return $group;

				if ($char === '(') {
					// Open new group
					$this->cursor++;
					$childGroup = (object) ['type' => TOKEN_CONDITION, 'groups' => []];
					\array_push($group->groups, $childGroup);
					$this->parseCondition($childGroup);
				} else if ($char === ')') {
					$this->cursor++;
					return $group;
				} else if ($char === ' ') {
					$this->cursor++;
				} else if ($char === '=') {
					// 49020, 49143 - $?$owb==0, 289954 - $?$pl=120
					$peek = $this->chars[$this->cursor + 1] ?? null;
					$this->cursor += $peek === '=' ? 2 : 1;

					\array_push($group->groups, (object) ['type' => TOKEN_CONDITION_EQUALS, 'value' => $this->parseIntLiteral()]);
				} else if (\in_array($char, CHARSET_CONDITIONS)) {
					$operator = (object) ['type' => TOKEN_CONDITIONAL_OPERATOR];
					if ($char === '!')
						$operator->operator = CONDITIONAL_OPERATOR_NOT;
					else if ($char === '&')
						$operator->operator = CONDITIONAL_OPERATOR_AND;
					else if ($char === '|')
						$operator->operator = CONDITIONAL_OPERATOR_OR;

					\array_push($group->groups, $operator);
					$this->cursor++;
				} else {
					if ($char === '$')
						$this->cursor++;

					\array_push($group->groups, $this->parseVariableToken(true));
				}
			}

			return $group;
		}

		private function parseConditionalChain() {
			// $?a137012[your Swiftmend by ${$357919s1*$357919s2/100}.1]?a137024[your Renewing Mist by ${$357919s1*$357919s3/100}.1]?a137029[your Holy Shock by ${$357919s1*$357919s4/100}.1]?a137032[your Penance by ${$357919s1*$357919s5/100}.1]?a137031[your Circle of Healing by ${$357919s1*$357919s6/100}.1]?a137039[your Riptide by ${$357919s1*$357919s7/100}.1][an appropriate healing spell by $357919s1]
			$conditional = (object) ['type' => TOKEN_CONDITION_CHAIN, 'conditions' => []];

			while ($this->cursor < $this->charLengthArray) {
				$condition = new stdClass();
				\array_push($conditional->conditions, $condition);

				$this->cursor++;
				$condition->condition = $this->parseCondition();
				$this->cursor++;

				$consequentTokenizer = new SpellTooltipTokenizer($this->parseStringLiteral(']', 0, 1));
				$condition->consequent = $consequentTokenizer->parse();

				if ($this->cursor < $this->charLengthArray) {
					$peek = $this->chars[$this->cursor];
					if ($peek === '[') {
						$alternativeTokenizer = new SpellTooltipTokenizer($this->parseStringLiteral(']', 1, 1));
						$condition->alternative = $alternativeTokenizer->parse();
						break;
					} else if ($peek !== '?') {
						throw new Error(\sprintf('Unexpected char %s (%d) following conditional', $peek, ord($peek)));
					}
				}
			}

			return $conditional;
		}

		/**
		 * @param string $conditionType
		 * @return stdClass
		 */
		private function parseConditionalSwitch($conditionType) {
			// The caster trades place with one of $ghis:her; images.
			$consequent = $this->parseStringLiteral(':', 1);
			$alternative = $this->parseStringLiteral(';', 1);

			$this->cursor++;
			return (object) [
				'type' => TOKEN_CONDITION_SWITCH,
				'consequent' => $consequent,
				'alternative' => $alternative,
				'conditionType' => $conditionType
			];
		}

		/**
		 * @return float
		 */
		private function parseFloatLiteral() {
			$startIndex = $this->cursor;
			$endIndex = $this->seekNextNonFloat();

			return (float) $this->extractString($startIndex, $endIndex);
		}

		/**
		 * @return int
		 */
		private function parseIntLiteral() {
			$startIndex = $this->cursor;
			$endIndex = $this->seekNextNonInt();

			return (int) $this->extractString($startIndex, $endIndex);
		}

		/**
		 * @return string
		 */
		private function parseAlphaLiteral() {
			$startIndex = $this->cursor;
			$endIndex = $this->seekNextNonAlpha();

			return $this->extractString($startIndex, $endIndex);
		}

		/**
		 * @param string|array $break
		 * @param int $preSeek
		 * @param int $postSeek
		 * @return string
		 */
		private function parseStringLiteral($break = ' ', $preSeek = 0, $postSeek = 0) {
			if ($preSeek > 0)
				$this->cursor += $preSeek;

			$startIndex = $this->cursor;
			$endIndex = $this->seekNextChar($break);

			if ($postSeek > 0)
				$this->cursor += $postSeek;

			return $this->extractString($startIndex, $endIndex);
		}

		/**
		 * @param int $length
		 * @param int $preSeek
		 * @param int $postSeek
		 * @return string
		 */
		private function parseString($length, $preSeek = 0, $postSeek = 0):string {
			if ($preSeek > 0)
				$this->cursor += $preSeek;

			$startIndex = $this->cursor;
			$this->cursor += $length;

			if ($postSeek > 0)
				$this->cursor += $postSeek;

			return $this->extractString($startIndex, $startIndex + $length);
		}

		/**
		 * @param string $sectionOpen
		 * @param string $sectionClose
		 * @return string
		 */
		private function parseCascadingSection($sectionOpen = '[', $sectionClose = ']') {
			if ($this->chars[$this->cursor] === $sectionOpen)
				$this->cursor++;

			$open = 0;
			$startIndex = $this->cursor;

			while ($this->cursor < $this->charLengthArray) {
				$char = $this->chars[$this->cursor];
				if ($char === $sectionOpen) {
					$open++;
				} else if ($char === $sectionClose) {
					if ($open === 0)
						break;

					$open--;
				}

				$this->cursor++;
			}

			$string = $this->extractString($startIndex, $this->cursor);
			$this->cursor++;
			return $string;
		}

		/**
		 * @return stdClass
		 */
		private function parseStringToken() {
			return (object) ['type' => TOKEN_STRING, 'text' => $this->parseStringLiteral(['$', '|'])];
		}

		/**
		 * @param int $startIndex
		 * @param int $endIndex
		 * @return string
		 */
		private function extractString($startIndex, $endIndex) {
			$extract = \array_slice($this->chars, $startIndex, $endIndex - $startIndex);
			return \implode($extract);
		}

		/**
		 * @return int
		 */
		private function seekNextNonInt() {
			while ($this->cursor < $this->charLengthArray) {
				if (!\ctype_digit($this->chars[$this->cursor]))
					return $this->cursor;

				$this->cursor++;
			}

			return $this->cursor;
		}

		/**
		 * @return int
		 */
		private function seekNextNonFloat() {
			while ($this->cursor < $this->charLengthArray) {
				$char = $this->chars[$this->cursor];
				if (!($char === '.' || \ctype_digit($char)))
					return $this->cursor;

				$this->cursor++;
			}

			return $this->cursor;
		}

		/**
		 * @return int
		 */
		private function seekNextNonAlpha() {
			while ($this->cursor < $this->charLengthArray) {
				if (!\ctype_alpha($this->chars[$this->cursor]))
					return $this->cursor;

				$this->cursor++;
			}

			return $this->cursor;
		}

		/**
		 * @param string|array $char
		 * @return int
		 */
		private function seekNextChar($char) {
			if (\is_array($char)) {
				while ($this->cursor < $this->charLengthArray) {
					if (\in_array($this->chars[$this->cursor], $char))
						return $this->cursor;

					$this->cursor++;
				}
			} else {
				while ($this->cursor < $this->charLengthArray) {
					if ($this->chars[$this->cursor] === $char)
						return $this->cursor;

					$this->cursor++;
				}
			}

			return $this->cursor;
		}

		/**
		 * @var boolean
		 */
		private $isExpression;

		/**
		 * @var Array
		 */
		private $tree;

		/**
		 * @var Array
		 */
		private $chars;

		/**
		 * @var string
		 */
		private $text;

		/**
		 * @var int
		 */
		private $cursor;
	}