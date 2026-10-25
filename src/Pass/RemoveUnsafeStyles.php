<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\TokenizerTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Security\DangerousUrlTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Security\UnsafeAttributeTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveUnsafeStyles implements PassInterface
{
    use AsciiCaseTrait;
    use DangerousUrlTrait;
    use ElementTrait;
    use StyleElementTrait;
    use TokenizerTrait;
    use UnsafeAttributeTrait;

    private const string CSS_IMAGE_SET_REGEX = '/image-set\s*\((?:[^()]++|\([^()]*+\)?)*+/i';

    private const string CSS_QUOTED_STRING_REGEX = '/([\'"])(.*?)\1/s';

    private const string DANGEROUS_STYLE_CONTENT_REGEX = '/<\s*(script|iframe|object|textarea|embed|link|svg)/i';

    private const string DANGEROUS_STYLE_REGEX = '/@import|expression|(?<![\w-])behavior\s*:|-o-link|-moz-binding|javascript:/i';

    private const string STATEMENT_END = ';';

    private const string BLOCK_START = '{';

    private const string BLOCK_END = '}';

    private const string ASCII_WHITESPACE_CHARACTERS = " \t\n\f\r";

    private const string AT_KEYWORD_START = '@';

    /**
     * @param \DOMDocument $domDocument the DOM document to sanitize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        $this->removeUnsafeAttributes($domDocument);
        $this->sanitizeStyleElements($domDocument);
    }

    /**
     * @param \DOMAttr $domAttr the attribute to check
     *
     * @return bool true if the attribute is a style attribute with an unsafe declaration
     */
    private function isUnsafeAttribute(\DOMAttr $domAttr): bool
    {
        return Attribute::Style->value === $this->asciiLowercase($domAttr->name)
            && $this->isStyleAttributeDangerous($this->decodeUrlForCheck($domAttr->value));
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to clean
     */
    private function sanitizeStyleElements(\DOMDocument $domDocument): void
    {
        foreach (array_reverse($this->elementsByQualifiedName($domDocument, Element::Style->value)) as $domElement) {
            $text = $domElement->textContent;

            if (!$this->isStyleNodeDangerous($text)) {
                continue;
            }

            $sanitized = $this->isPlainCssStyleElement($domElement) ? $this->sanitizeStyleSheet($text) : false;

            if (false === $sanitized || '' === trim($sanitized, self::ASCII_WHITESPACE_CHARACTERS)) {
                $domElement->remove();
            } else {
                $domElement->textContent = $sanitized;
            }
        }
    }

    /**
     * @param string $text the text content of a `<style>` element
     *
     * @return bool true if the style element's content is dangerous
     */
    private function isStyleNodeDangerous(string $text): bool
    {
        if (1 === preg_match(self::DANGEROUS_STYLE_CONTENT_REGEX, $text)) {
            return true;
        }

        return $this->isStyleAttributeDangerous($this->decodeUrlForCheck($text));
    }

    /**
     * @param string $value the normalized style attribute value
     *
     * @return bool true if the value is dangerous, false otherwise
     */
    private function isStyleAttributeDangerous(string $value): bool
    {
        if (1 === preg_match(self::DANGEROUS_STYLE_REGEX, $value)) {
            return true;
        }

        if ($this->containsDangerousUrlFunction($value)) {
            return true;
        }

        preg_match_all(self::CSS_IMAGE_SET_REGEX, $value, $imageSets);
        preg_match_all(self::CSS_QUOTED_STRING_REGEX, implode(' ', $imageSets[0]), $imageSetUrls);

        return $this->containsDangerousUrl($imageSetUrls[2]);
    }

    /**
     * @param string $css the text of a style sheet
     *
     * @return string|false the style sheet without its unsafe statements and declarations, or false if it cannot be
     *                      sanitized with confidence
     */
    private function sanitizeStyleSheet(string $css): string|false
    {
        $tokens = $this->tokenize($css);

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
