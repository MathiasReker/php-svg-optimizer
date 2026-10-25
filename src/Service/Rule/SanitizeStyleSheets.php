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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\DangerousAttributeValueTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\EntityReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\StyleSheetSanitizerTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\UnsafeAttributeTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class SanitizeStyleSheets implements PassInterface
{
    use AsciiCaseTrait;
    use DangerousAttributeValueTrait;
    use EntityReferenceTrait;
    use StyleSheetSanitizerTrait;
    use UnsafeAttributeTrait;

    private const string CSS_IMAGE_SET_REGEX = '/image-set\s*\((?:[^()]++|\([^()]*+\)?)*+/i';

    private const string CSS_QUOTED_STRING_REGEX = '/([\'"])(.*?)\1/s';

    private const string STYLE_NODE_DANGEROUS_TAG_REGEX = '/<\s*(script|iframe|object|textarea|embed|link|svg)/i';

    private const string DANGEROUS_STYLE_REGEX = '/@import|expression|(?<![\w-])behavior\s*:|-o-link|-moz-binding|javascript:/i';

    /**
     * @param \DOMDocument $domDocument the DOM document to sanitize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        $this->expandEntityReferences($domDocument);
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
        return SvgAttribute::Style->value === $this->asciiLowercase($domAttr->name)
            && $this->isStyleAttributeDangerous($this->normalizeValue($domAttr->value));
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to clean
     */
    private function sanitizeStyleElements(\DOMDocument $domDocument): void
    {
        foreach (array_reverse($this->elementList($domDocument, SvgTag::Style->value)) as $domElement) {
            $text = $domElement->textContent;

            if (!$this->isStyleNodeDangerous($text)) {
                continue;
            }

            $sanitized = $this->isCssTextStyle($domElement) ? $this->sanitizeStyleSheet($text) : false;

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
        if ($this->matchesPattern($text, self::STYLE_NODE_DANGEROUS_TAG_REGEX)) {
            return true;
        }

        return $this->isStyleAttributeDangerous($this->normalizeValue($text));
    }

    /**
     * @param string $value the normalized style attribute value
     *
     * @return bool true if the value is dangerous, false otherwise
     */
    private function isStyleAttributeDangerous(string $value): bool
    {
        if ($this->matchesPattern($value, self::DANGEROUS_STYLE_REGEX)) {
            return true;
        }

        if ($this->containsDangerousUrlFunction($value)) {
            return true;
        }

        preg_match_all(self::CSS_IMAGE_SET_REGEX, $value, $imageSets);
        preg_match_all(self::CSS_QUOTED_STRING_REGEX, implode(' ', $imageSets[0]), $imageSetUrls);

        return $this->containsDangerousUrl($imageSetUrls[2]);
    }
}
