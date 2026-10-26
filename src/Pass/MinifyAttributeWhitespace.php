<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AttributeNodeTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class MinifyAttributeWhitespace implements PassInterface
{
    use AsciiCaseTrait;
    use AttributeNodeTrait;
    use ElementTrait;
    use ScriptingTrait;
    use StyleSheetTrait;

    private const string WHITESPACE_REGEX = '/\s+/';

    private const string CSS_STRING_REGEX = '/("(?:[^"\\\]|\\\.)*"|\'(?:[^\'\\\]|\\\.)*\')/';

    private const string CSS_SEPARATOR_WHITESPACE_REGEX = '/\s*([;:,])\s*/';

    private const string DATA_ATTRIBUTE_PREFIX = 'data-';

    private const string UNICODE_ATTRIBUTE = 'unicode';

    private const string CSS_ESCAPE = '\\';

    private const array VERBATIM_ATTRIBUTES = [
        Attribute::AttributeName->value,
        Attribute::Download->value,
        Attribute::Href->value,
        Attribute::Id->value,
        Attribute::In->value,
        Attribute::In2->value,
        Attribute::Lang->value,
        Attribute::Result->value,
        Attribute::Target->value,
        Attribute::Title->value,
        self::UNICODE_ATTRIBUTE,
    ];

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->maySelectByAttributeValue($domDocument)) {
            return;
        }

        foreach ($this->elementsByQualifiedName($domDocument, '*') as $element) {
            if (!$this->isForeignContent($element)) {
                $this->minifyAttributes($element);
            }
        }
    }

    /**
     * @param \DOMElement $domElement the element whose attributes to minify
     */
    private function minifyAttributes(\DOMElement $domElement): void
    {
        foreach (iterator_to_array($domElement->attributes, false) as $domAttr) {
            if ($this->isVerbatim($domAttr)) {
                continue;
            }

            $value = Attribute::Style->value === $domAttr->nodeName
                ? $this->minifyStyle($domAttr->value)
                : $this->minifyOutsideStrings($domAttr->value, static fn (string $part): string => $part);

            if ($value !== $domAttr->value) {
                $this->setAttributeNodeValue($domAttr, $value);
            }
        }
    }

    /**
     * @param \DOMAttr $domAttr the attribute to check
     *
     * @return bool true if the whitespace in the value is part of the content, such as in a URL, a name, a script, the
     *              character of a glyph or a style with an escape
     */
    private function isVerbatim(\DOMAttr $domAttr): bool
    {
        return \in_array($domAttr->localName, self::VERBATIM_ATTRIBUTES, true)
            || str_starts_with($domAttr->nodeName, self::DATA_ATTRIBUTE_PREFIX)
            || $this->isEventHandlerName((string) $domAttr->localName)
            || (Attribute::Style->value === $domAttr->nodeName && str_contains($domAttr->value, self::CSS_ESCAPE));
    }

    /**
     * @param string $style the style attribute value
     */
    private function minifyStyle(string $style): string
    {
        return rtrim($this->minifyOutsideStrings($style, $this->minifyStylePart(...)), ';');
    }

    /**
     * @param string $part a part of a style attribute value outside quoted strings
     */
    private function minifyStylePart(string $part): string
    {
        return preg_replace(self::CSS_SEPARATOR_WHITESPACE_REGEX, '$1', $part) ?? $part;
    }

    /**
     * @param string                   $value      the attribute value
     * @param \Closure(string): string $minifyPart further minifies a part outside quoted strings
     *
     * @param-immediately-invoked-callable $minifyPart
     *
     * @return string the value with whitespace collapsed outside quoted strings and trimmed
     */
    private function minifyOutsideStrings(string $value, \Closure $minifyPart): string
    {
        $parts = preg_split(self::CSS_STRING_REGEX, $value, -1, \PREG_SPLIT_DELIM_CAPTURE);

        if (false === $parts) {
            return $value;
        }

        foreach ($parts as $index => $part) {
            if (0 === $index % 2) {
                $parts[$index] = $minifyPart(preg_replace(self::WHITESPACE_REGEX, ' ', $part) ?? $part);
            }
        }

        return trim(implode('', $parts));
    }
}
