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
trait StyleSheetSanitizerTrait
{
    use StyleSheetTokenTrait;

    private const string STATEMENT_END = ';';

    private const string BLOCK_START = '{';

    private const string BLOCK_END = '}';

    private const string ASCII_WHITESPACE_CHARACTERS = " \t\n\f\r";

    private const string AT_KEYWORD_START = '@';

    /**
     * @param string $css the text of a style sheet
     *
     * @return string|false the style sheet without its unsafe statements and declarations, or false if it cannot be
     *                      sanitized with confidence
     */
    private function sanitizeStyleSheet(string $css): string|false
    {
        $tokens = $this->tokenizeStyleSheet($css);

        if (false === $tokens) {
            return false;
        }

        $sanitized = implode('', array_map($this->sanitizeStatement(...), $this->splitStatements($tokens)));

        return $this->isStyleNodeDangerous($sanitized) ? false : $sanitized;
    }

    /**
     * @param list<array{string, string}> $tokens the tokens of a balanced style sheet or block
     *
     * @return list<list<array{string, string}>> the statements, each ending with a semicolon or a block at the top level
     */
    private function splitStatements(array $tokens): array
    {
        $statements = [];
        $statement = [];
        $depth = 0;

        foreach ($tokens as $token) {
            $statement[] = $token;
            $depth += $this->bracketDepthChange($token);

            if (0 === $depth && \in_array($token[1], [self::STATEMENT_END, self::BLOCK_END], true)) {
                $statements[] = $statement;
                $statement = [];
            }
        }

        if ([] !== $statement) {
            $statements[] = $statement;
        }

        return $statements;
    }

    /**
     * @param list<array{string, string}> $statement the tokens of a statement
     *
     * @return string the statement, the statement without its unsafe declarations, or an empty string
     */
    private function sanitizeStatement(array $statement): string
    {
        $text = implode('', array_column($statement, 1));

        if (!$this->isStyleNodeDangerous($text)) {
            return $text;
        }

        $blockStart = array_search([self::TOKEN_DELIM, self::BLOCK_START], $statement, true);

        if (!\is_int($blockStart)
            || [self::TOKEN_DELIM, self::BLOCK_END] !== $statement[\count($statement) - 1]
            || $this->isAtRule($statement)
        ) {
            return '';
        }

        $prelude = implode('', array_column(\array_slice($statement, 0, $blockStart), 1));

        return $this->isStyleNodeDangerous($prelude)
            ? ''
            : $prelude . self::BLOCK_START . $this->sanitizeDeclarations(\array_slice($statement, $blockStart + 1, -1)) . self::BLOCK_END;
    }

    /**
     * @param list<array{string, string}> $statement the tokens of a statement
     *
     * @return bool true if the first token that is not whitespace or a comment starts an at-rule
     */
    private function isAtRule(array $statement): bool
    {
        $significant = array_values(array_filter(
            $statement,
            static fn (array $token): bool => !\in_array($token[0], [self::TOKEN_WHITESPACE, self::TOKEN_COMMENT], true),
        ));

        return self::AT_KEYWORD_START === ($significant[0][1] ?? '');
    }

    /**
     * @param list<array{string, string}> $tokens the tokens inside the block of a rule
     *
     * @return string the declarations that are safe
     */
    private function sanitizeDeclarations(array $tokens): string
    {
        $declarations = '';

        foreach ($this->splitStatements($tokens) as $declaration) {
            $text = implode('', array_column($declaration, 1));

            if (!$this->isStyleNodeDangerous($text)) {
                $declarations .= $text;
            }
        }

        return $declarations;
    }
}
