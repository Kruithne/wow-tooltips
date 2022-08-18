<?php
	class TooltipGenerics {
		/**
		 * @param string $tagName
		 * @param bool $escape
		 * @return string
		 */
		public static function getStringTag($tagName, $escape = true):string {
			if ($escape)
				return '&lt;' . $tagName . '&gt;';

			return '<' . $tagName . '>';
		}

		/**
		 * @param string $tagName
		 * @return string
		 */
		public static function getClosingStringTag($tagName):string {
			return '</' . $tagName . '>';
		}

		/**
		 * @param string $tagName
		 * @param string $content
		 * @return string
		 */
		public static function wrapStringTags($tagName, $content):string {
			return \sprintf('<%1$s>%2$s</%1$s>', $tagName, $content);
		}
	}