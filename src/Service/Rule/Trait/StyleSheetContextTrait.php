<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Trait;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait StyleSheetContextTrait
{
    use AsciiCaseTrait;
    use StyleSheetTokenTrait;

    private const string TOKEN_WORD = 'word';

    private const string COLON = ':';

    private const string SEMICOLON = ';';

    private const string BLOCK_START = '{';

    private const string BLOCK_END = '}';

    private const string AT_SIGN = '@';

    private const string QUERY_GROUP = 'P';

    private const array QUERY_AT_RULES = ['container', 'media', 'supports'];

    private const string COMMA = ',';

    private const array STATEMENT_ENDS = [self::SEMICOLON, self::BLOCK_START, self::BLOCK_END];

    /**
     * @param list<array{string, string}> $tokens the compacted tokens
     *
     * @return list<string> the innermost open bracket before each token, with query groups of conditional at-rules
     *                      marked separately
     */
    private function bracketContexts(array $tokens): array
    {
        $contexts = [];
        $stack = [];
        $atRule = '';
        $statementStart = true;

        foreach ($tokens as $index => [$type, $text]) {
            $top = [] === $stack ? '' : $stack[array_key_last($stack)];
            $contexts[] = $top;

            if ($statementStart && self::TOKEN_WHITESPACE !== $type) {
                $atRule = $this->atRuleName($tokens, $index);
                $statementStart = false;
            }

            if (self::TOKEN_DELIM === $type) {
                [$stack, $statementStart] = $this->nextBracketState($stack, $text, $atRule, $this->isQueryGroup($tokens, $index, $top, $atRule));
            }
        }

        return $contexts;
    }

    /**
     * @param list<array{string, string}> $tokens the compacted tokens
     * @param int                         $index  the position of the first token of a statement
     *
     * @return string the lowercase name of the at-rule the statement starts, or an empty string
     */
    private function atRuleName(array $tokens, int $index): string
    {
        $next = $tokens[$index + 1] ?? null;

        return self::AT_SIGN === $tokens[$index][1] && null !== $next && self::TOKEN_WORD === $next[0]
            ? $this->asciiLowercase($next[1])
            : '';
    }

    /**
     * @param list<array{string, string}> $tokens the compacted tokens
     * @param int                         $index  the position of a delimiter
     * @param string                      $top    the innermost open bracket
     * @param string                      $atRule the lowercase name of the current at-rule
     *
     * @return bool true if the delimiter opens a query group of a conditional at-rule
     */
    private function isQueryGroup(array $tokens, int $index, string $top, string $atRule): bool
    {
        if (self::OPENING_PARENTHESIS !== $tokens[$index][1] || !\in_array($atRule, self::QUERY_AT_RULES, true)) {
            return false;
        }

        $previous = $tokens[$index - 1];

        return self::TOKEN_WHITESPACE === $previous[0]
            || self::COMMA === $previous[1]
            || (self::OPENING_PARENTHESIS === $previous[1] && self::QUERY_GROUP === $top);
    }

    /**
     * @param list<string> $stack      the open brackets
     * @param string       $text       a delimiter
     * @param string       $atRule     the lowercase name of the current at-rule
     * @param bool         $queryGroup true if the delimiter opens a query group of a conditional at-rule
     *
     * @return array{list<string>, bool} the open brackets after the delimiter, and true if a new statement starts
     */
    private function nextBracketState(array $stack, string $text, string $atRule, bool $queryGroup): array
    {
        $top = [] === $stack ? '' : $stack[array_key_last($stack)];

        if (\in_array($text, self::OPENING_BRACKETS, true)) {
            $stack[] = $queryGroup ? self::QUERY_GROUP : $text;

            return [$stack, self::BLOCK_START === $text];
        }

        if (\array_key_exists($text, self::CLOSING_BRACKETS)) {
            array_pop($stack);

            return [$stack, self::BLOCK_END === $text];
        }

        return [$stack, self::SEMICOLON === $text && (self::BLOCK_START === $top || ('' === $top && '' !== $atRule))];
    }

    /**
     * @param list<array{string, string}> $tokens   the compacted tokens
     * @param list<string>                $contexts the innermost open bracket before each token
     * @param int                         $index    the position of a token
     *
     * @return bool true if the token is the colon of a declaration or of a query in a conditional at-rule
     */
    private function isDeclarationColon(array $tokens, array $contexts, int $index): bool
    {
        if (self::COLON !== ($tokens[$index][1] ?? '')) {
            return false;
        }

        return self::QUERY_GROUP === $contexts[$index]
            || (self::BLOCK_START === $contexts[$index] && $this->endsAsDeclaration($tokens, $index));
    }

    /**
     * @param list<array{string, string}> $tokens the compacted tokens
     * @param int                         $index  the position of a colon inside a block
     *
     * @return bool true if the colon is followed by the end of a declaration rather than by a block
     */
    private function endsAsDeclaration(array $tokens, int $index): bool
    {
        $depth = 0;
        $position = $index;

        while (\array_key_exists($position, $tokens) && (0 !== $depth || !\in_array($tokens[$position][1], self::STATEMENT_ENDS, true))) {
            $depth += $this->bracketDepthChange($tokens[$position]);
            ++$position;
        }

        return self::BLOCK_START !== ($tokens[$position][1] ?? '');
    }
}
