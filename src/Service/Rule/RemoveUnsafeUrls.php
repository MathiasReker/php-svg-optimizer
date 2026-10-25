<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Contract\Service\Rule\SvgOptimizerRuleInterface;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\DangerousAttributeValueTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\EntityReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\TagMatcherTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\UnsafeAttributeTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveUnsafeUrls implements SvgOptimizerRuleInterface
{
    use AsciiCaseTrait;
    use DangerousAttributeValueTrait;
    use EntityReferenceTrait;
    use TagMatcherTrait;
    use UnsafeAttributeTrait;

    private const array URI_ATTRIBUTES = [
        'action',
        'background',
        'base',
        'cite',
        'codebase',
        'data',
        'dynsrc',
        'formaction',
        'lowsrc',
        'ping',
        'poster',
    ];

    private const string DATA_URI_REGEX = '/^\s*data:/i';

    private const string SMIL_VALUES_SAFE_DATA_URI_REGEX = '~data:image/(?:png|gif|jpeg|jpg|webp|avif|svg\+xml);base64,.*?(?=;|$)~i';

    #[\Override]
    public static function isRisky(): bool
    {
        return false;
    }

    #[\Override]
    public static function shouldCheckSize(): bool
    {
        return false;
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to sanitize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $this->expandEntityReferences($domDocument);
        $this->removeUnsafeAttributes($domDocument);
        $this->removeDataUriLinks($domDocument);
    }

    /**
     * @param \DOMAttr $domAttr the attribute to check
     *
     * @return bool true if the attribute holds a URL that can run script or load a document from elsewhere
     */
    private function isUnsafeAttribute(\DOMAttr $domAttr): bool
    {
        $name = mb_strtolower($domAttr->name);

        if ($this->isUrlAttribute($name) && $this->isDangerousUrl($domAttr->value)) {
            return true;
        }

        return $this->isDangerousAttributeValue($name, $this->normalizeValue($domAttr->value));
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to clean
     */
    private function removeDataUriLinks(\DOMDocument $domDocument): void
    {
        foreach ($this->elementList($domDocument, '*') as $domElement) {
            if ($this->isTagIn($domElement, SvgTag::dataUri())) {
                continue;
            }

            foreach (iterator_to_array($domElement->attributes, false) as $domAttr) {
                if (\in_array($this->asciiLowercase($domAttr->name), SvgAttribute::dangerousExact(), true)
                    && $this->matchesPattern($this->normalizeValue($domAttr->value), self::DATA_URI_REGEX)
                ) {
                    $domElement->removeAttributeNode($domAttr);
                }
            }
        }
    }

    /**
     * @param string $name the attribute name
     *
     * @return bool true if the attribute holds a single URL
     */
    private function isUrlAttribute(string $name): bool
    {
        return \in_array($name, SvgAttribute::dangerousExact(), true)
            || \in_array($name, self::URI_ATTRIBUTES, true)
            || SvgAttribute::Src->value === $name;
    }

    /**
     * @param string $name  the attribute name
     * @param string $value the normalized attribute value
     *
     * @return bool true if the value is dangerous
     */
    private function isDangerousAttributeValue(string $name, string $value): bool
    {
        return (SvgAttribute::Values->value === $name && $this->isSmilValuesDangerous($value))
            || (\in_array($name, SvgAttribute::dangerous(), true) && $this->isUrlAttributeValueDangerous($value))
            || (SvgAttribute::Srcset->value === $name && $this->isSrcsetDangerous($value));
    }

    /**
     * @param string $value the attribute value
     *
     * @return bool true if a dangerous protocol is found
     */
    private function isSmilValuesDangerous(string $value): bool
    {
        $value = preg_replace(self::SMIL_VALUES_SAFE_DATA_URI_REGEX, '', $value) ?? $value;

        foreach (explode(';', $value) as $part) {
            if ($this->matchesPattern($part, self::DANGEROUS_PROTOCOLS_REGEX)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $value the attribute value
     *
     * @return bool true if the URL is dangerous
     */
    private function isUrlAttributeValueDangerous(string $value): bool
    {
        return $this->matchesPattern($value, self::DANGEROUS_PROTOCOLS_REGEX) || $this->containsDangerousUrlFunction($value);
    }

    /**
     * @param string $value the srcset attribute value
     *
     * @return bool true if the value is dangerous, false otherwise
     */
    private function isSrcsetDangerous(string $value): bool
    {
        foreach (explode(',', $value) as $candidate) {
            if ($this->matchesPattern($this->normalizeValue($candidate), self::DANGEROUS_PROTOCOLS_REGEX)) {
                return true;
            }
        }

        return false;
    }
}
