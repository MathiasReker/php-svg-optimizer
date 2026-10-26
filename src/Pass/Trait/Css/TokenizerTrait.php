<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass\Trait\Css;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait TokenizerTrait
{
    private const string CSS_WHITESPACE = '[ \t\n\r\f]';

    private const string CSS_DOUBLE_QUOTED = '"(?:[^"\\\\\n\r\f]|\\\(?:\r\n|.))*"';

    private const string CSS_SINGLE_QUOTED = '\'(?:[^\'\\\\\n\r\f]|\\\(?:\r\n|.))*\'';

    private const string CSS_UNQUOTED_URL = '(?:[^ \t\n\r\f()"\'\\\\\x00-\x08\x0B\x0E-\x1F\x7F]|\\\[^\n\r\f])*';

    private const string CSS_TOKEN_REGEX = '~' . self::CSS_WHITESPACE . '+(*MARK:ws)'
        . '|/\*(?:.*?\*/|(*COMMIT)(*FAIL))(*MARK:comment)'
        . '|url\(' . self::CSS_WHITESPACE . '*(?:' . self::CSS_DOUBLE_QUOTED . '|' . self::CSS_SINGLE_QUOTED . '|' . self::CSS_UNQUOTED_URL . ')' . self::CSS_WHITESPACE . '*\)(*MARK:url)'
        . '|(?:' . self::CSS_DOUBLE_QUOTED . '|' . self::CSS_SINGLE_QUOTED . ')(*MARK:string)'
        . '|\\\(?:[0-9a-f]{1,6}(?:\r\n|' . self::CSS_WHITESPACE . ')?|[^\n\r\f0-9a-f])(*MARK:escape)'
        . '|[\w\x{80}-\x{10FFFF}-]+(*MARK:word)'
        . '|(?:/(?!\*)|[^ \t\n\r\f"\'\\\/])(*MARK:delim)~isu';

    private const string TOKEN_WHITESPACE = 'ws';

    private const string TOKEN_COMMENT = 'comment';

    private const string TOKEN_DELIM = 'delim';

    private const string URL_FUNCTION = 'url';

    private const string OPENING_PARENTHESIS = '(';

    private const array OPENING_BRACKETS = ['(', '[', '{'];

    private const array CLOSING_BRACKETS = [')' => '(', ']' => '[', '}' => '{'];

    /**
     * @param string $css the style sheet to split
     *
     * @return list<array{string, string}>|false the type and text of each token, or false if the style sheet cannot be
     *                                           tokenized confidently
     */
    private function tokenize(string $css): array|false
    {
        preg_match_all(self::CSS_TOKEN_REGEX, $css, $matches, \PREG_SET_ORDER);

        $tokens = [];

        foreach ($matches as $match) {
            $tokens[] = [$match['MARK'], $match[0]];
        }

        return implode('', array_column($tokens, 1)) === $css && $this->isBalancedStyleSheet($tokens) && !$this->hasUnparsedUrl($tokens)
            ? $tokens
            : false;
    }

    /**
     * @param list<array{string, string}> $tokens the tokens of a style sheet
     *
     * @return bool true if every bracket is closed by a matching bracket
     */
    private function isBalancedStyleSheet(array $tokens): bool
    {
        $stack = [];

        foreach (array_column($tokens, 1) as $text) {
            if (\in_array($text, self::OPENING_BRACKETS, true)) {
                $stack[] = $text;
            } elseif (\array_key_exists($text, self::CLOSING_BRACKETS) && array_pop($stack) !== self::CLOSING_BRACKETS[$text]) {
                return false;
            }
        }

        return [] === $stack;
    }

    /**
     * @param array{string, string} $token a token
     *
     * @return int 1 for an opening bracket, -1 for a closing bracket, otherwise 0
     */
    private function bracketDepthChange(array $token): int
    {
        if (\in_array($token[1], self::OPENING_BRACKETS, true)) {
            return 1;
        }

        return \array_key_exists($token[1], self::CLOSING_BRACKETS) ? -1 : 0;
    }

    /**
     * @param list<array{string, string}> $tokens the tokens of a style sheet
     *
     * @return bool true if a `url(` is not followed by a well-formed URL
     */
    private function hasUnparsedUrl(array $tokens): bool
    {
        foreach (array_column($tokens, 1) as $index => $text) {
            if (0 === strcasecmp($text, self::URL_FUNCTION)
                && [self::TOKEN_DELIM, self::OPENING_PARENTHESIS] === ($tokens[$index + 1] ?? null)) {
                return true;
            }
        }

        return false;
    }
}
