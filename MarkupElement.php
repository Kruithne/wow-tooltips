<?php
	class MarkupElement {
		/**
		 * @param string $tagName
		 * @param boolean $includeClosingTag
		 */
		public function __construct($tagName, $includeClosingTag = true) {
			$this->tagName = $tagName;
			$this->content = '';
			$this->attributes = [];
			$this->includeClosingTag = $includeClosingTag;
		}

		/**
		 * @param string $key
		 * @param mixed $value
		 * @return MarkupElement
		 */
		public function addAttribute($key, $value) {
			$this->attributes[$key] = $value;
			return $this;
		}

		/**
		 * @param string $content
		 * @return MarkupElement
		 */
		public function setContent($content) {
			$this->content = $content;
			return $this;
		}

		/**
		 * @return string
		 */
		public function __toString():string {
			if ($this->includeClosingTag)
				return \sprintf('<%1$s%2$s>%3$s</%1$s>', $this->tagName, $this->getAttributeString(), $this->content);

			return \sprintf('<%1$s%2$s>%3$s', $this->tagName, $this->getAttributeString(), $this->content);
		}

		/**
		 * @return string
		 */
		private function getAttributeString():string {
			$str = '';

			if (\count($this->attributes)) {
				foreach ($this->attributes as $key => $value) {
					if (\is_array($value)) {
						$value = \json_encode($value);
						$str .= ' ' . $key . '=\'' . $value . '\'';
					} else {
						$str .= ' ' . $key . '="' . $value . '"';
					}
				}
			}

			return $str;
		}

		/**
		 * @var boolean
		 */
		public $includeClosingTag;

		/**
		 * @var string
		 */
		public $content;

		/**
		 * @var string
		 */
		private $tagName;

		/**
		 * @var array
		 */
		private $attributes;
	}