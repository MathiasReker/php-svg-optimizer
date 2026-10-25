<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Pass\PassInterface;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\StyleSheetContextTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class MinifyStyles implements PassInterface
{
    use ElementTrait;
    use StyleSheetContextTrait;

    private const string LICENSE_COMMENT_START = '/*!';

    private const string SPACE = ' ';

    private const string EMPTY_COMMENT = '/**/';

    private const array SPACE_FREE_AFTER = [self::BLOCK_START, self::BLOCK_END, self::SEMICOLON, self::COMMA, '(', '[', '>', '~', '!'];

    private const array SPACE_FREE_BEFORE = [self::BLOCK_START, self::BLOCK_END, self::SEMICOLON, self::COMMA, ')', ']', '>', '~', '!'];

    private const array DECLARATION_ENDS = [self::SEMICOLON, self::BLOCK_END];

    private const array JOINING_PAIRS = ['>=', '~=', '->', '<!', '!-'];

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        foreach ($this->elementsNamed($domDocument, SvgTag::Style->value) as $domElement) {
            if ($this->isForeignContent($domElement) || !$this->isCssTextStyle($domElement)) {
                continue;
            }

            $minified = $this->minifyStyleSheet($domElement->textContent);
            $domElement->replaceChildren(...('' === $minified ? [] : [$minified]));
        }
    }

    /**
     * @param string $css the style sheet to minify
     *
     * @return string the style sheet without comments and insignificant whitespace, or the style sheet itself if it
     *                cannot be tokenized confidently
     */
    private function minifyStyleSheet(string $css): string
    {
        $tokens = $this->tokenizeStyleSheet($css);

        if (false === $tokens) {
            return $css;
        }

        $tokens = $this->compactTokens($tokens);
        $contexts = $this->bracketContexts($tokens);
        $output = '';

        foreach ($tokens as $index => [$type, $text]) {
            $droppable = self::TOKEN_WHITESPACE === $type
                ? $this->isDroppableSeparator($tokens, $contexts, $index, $output)
                : $this->isRedundantSemicolon($tokens, $contexts, $index);

            if (!$droppable) {
                $output .= $text;
            }
        }

        return $output;
    }

    /**
     * @param list<array{string, string}> $tokens the tokens of a style sheet
     *
     * @return list<array{string, string}> the tokens with each run of whitespace and comments replaced by a single
     *                                     separator, without a trailing separator, keeping license comments
     */
    private function compactTokens(array $tokens): array
    {
        $compacted = [];
        $separator = '';

        foreach ($tokens as $token) {
            if (self::TOKEN_WHITESPACE === $token[0]) {
                $separator = self::SPACE;
            } elseif (self::TOKEN_COMMENT === $token[0] && !str_starts_with($token[1], self::LICENSE_COMMENT_START)) {
                $separator = '' === $separator ? self::EMPTY_COMMENT : $separator;
            } else {
                $compacted = $this->appendSeparator($compacted, $separator);
                $compacted[] = $token;
                $separator = '';
            }
        }

        return $compacted;
    }

    /**
     * @param list<array{string, string}> $tokens    the compacted tokens
     * @param string                      $separator a space, an empty comment, or nothing
     *
     * @return list<array{string, string}> the tokens followed by the separator
     */
    private function appendSeparator(array $tokens, string $separator): array
    {
        if ('' !== $separator) {
            $tokens[] = [self::TOKEN_WHITESPACE, $separator];
        }

        return $tokens;
    }

    /**
     * @param list<array{string, string}> $tokens   the compacted tokens
     * @param list<string>                $contexts the innermost open bracket before each token
     * @param int                         $index    the position of a separator
     * @param string                      $output   the minified style sheet so far
     *
     * @return bool true if the separator can be removed without changing the meaning of the style sheet
     */
    private function isDroppableSeparator(array $tokens, array $contexts, int $index, string $output): bool
    {
        $next = $tokens[$index + 1];

        if ('' === $output) {
            return true;
        }

        if ($this->isDeclarationColon($tokens, $contexts, $index - 1)) {
            return !\in_array($next[1], self::DECLARATION_ENDS, true);
        }

        if ($this->isDeclarationColon($tokens, $contexts, $index + 1)) {
            return true;
        }

        $previousCharacter = $output[-1];
        $nextCharacter = $next[1][0];

        return (\in_array($previousCharacter, self::SPACE_FREE_AFTER, true) || \in_array($nextCharacter, self::SPACE_FREE_BEFORE, true))
            && !\in_array($previousCharacter . $nextCharacter, self::JOINING_PAIRS, true);
    }

    /**
     * @param list<array{string, string}> $tokens   the compacted tokens
     * @param list<string>                $contexts the innermost open bracket before each token
     * @param int                         $index    the position of a token
     *
     * @return bool true if the token is a semicolon followed by another semicolon or by the end of its block
     */
    private function isRedundantSemicolon(array $tokens, array $contexts, int $index): bool
    {
        if (self::SEMICOLON !== $tokens[$index][1] || self::BLOCK_START !== $contexts[$index]) {
            return false;
        }

        $next = $tokens[$index + 1];

        if (self::TOKEN_WHITESPACE === $next[0]) {
            $next = $tokens[$index + 2];
        }

        return \in_array($next[1], self::DECLARATION_ENDS, true);
    }
}
