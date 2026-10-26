<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementNameTrait;
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
final readonly class RemoveUnsafeUrls implements PassInterface
{
    use AsciiCaseTrait;
    use DangerousUrlTrait;
    use ElementNameTrait;
    use ElementTrait;
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

    /**
     * @param \DOMDocument $domDocument the DOM document to sanitize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        $this->removeUnsafeAttributes($domDocument);
        $this->removeDataUriLinks($domDocument);
    }

    /**
     * @param \DOMAttr $domAttr the attribute to check
     *
     * @return bool true if the attribute holds a URL that can run script or load a document from elsewhere
     */
    #[\Override]
    private function isUnsafeAttribute(\DOMAttr $domAttr): bool
    {
        $name = mb_strtolower($domAttr->name);

        if ($this->isUrlAttribute($name) && $this->isDangerousUrl($domAttr->value)) {
            return true;
        }

        return $this->isDangerousAttributeValue($name, $this->decodeUrlForCheck($domAttr->value));
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to clean
     */
    private function removeDataUriLinks(\DOMDocument $domDocument): void
    {
        foreach ($this->elementsByQualifiedName($domDocument, '*') as $domElement) {
            if ($this->hasLocalNameIgnoringCase($domElement, Element::imageLoadingNames())) {
                continue;
            }

            foreach (iterator_to_array($domElement->attributes, false) as $domAttr) {
                if (\in_array($this->asciiLowercase($domAttr->name), Attribute::linkNames(), true)
                    && 0 !== preg_match(self::DATA_URI_REGEX, $this->decodeUrlForCheck($domAttr->value))
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
        return \in_array($name, Attribute::linkNames(), true)
            || \in_array($name, self::URI_ATTRIBUTES, true)
            || Attribute::Src->value === $name;
    }

    /**
     * @param string $name  the attribute name
     * @param string $value the normalized attribute value
     *
     * @return bool true if the value is dangerous
     */
    private function isDangerousAttributeValue(string $name, string $value): bool
    {
        return (Attribute::Values->value === $name && $this->isSmilValuesDangerous($value))
            || (\in_array($name, Attribute::urlValuedNames(), true) && $this->isUrlAttributeValueDangerous($value))
            || (Attribute::Srcset->value === $name && $this->isSrcsetDangerous($value));
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
            if (0 !== preg_match(self::DANGEROUS_URL_REGEX, $part)) {
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
        return 0 !== preg_match(self::DANGEROUS_URL_REGEX, $value) || $this->containsDangerousUrlFunction($value);
    }

    /**
     * @param string $value the srcset attribute value
     *
     * @return bool true if the value is dangerous, false otherwise
     */
    private function isSrcsetDangerous(string $value): bool
    {
        foreach (explode(',', $value) as $candidate) {
            if (0 !== preg_match(self::DANGEROUS_URL_REGEX, $this->decodeUrlForCheck($candidate))) {
                return true;
            }
        }

        return false;
    }
}
