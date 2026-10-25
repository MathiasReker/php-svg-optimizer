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
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementRemovalTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Security\CanonicalNameTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Security\DangerousUrlTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Security\UnsafeAttributeTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveScripts implements PassInterface
{
    use AsciiCaseTrait;
    use CanonicalNameTrait;
    use DangerousUrlTrait;
    use ElementNameTrait;
    use ElementRemovalTrait;
    use UnsafeAttributeTrait;

    private const string EVENT_HANDLER_PREFIX = 'on';

    private const string CLOBBERING_ATTRIBUTE = 'name';

    private const array SCRIPT_ELEMENTS = [Element::Script->value, 'handler', 'listener'];

    private const string FOLDED_ATTRIBUTE_NAME = 'attributename';

    /**
     * @param \DOMDocument $domDocument the DOM document to sanitize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        $this->removeElementsWhere(
            $domDocument,
            fn (\DOMElement $domElement): bool => $this->hasLocalNameIgnoringCase($domElement, self::SCRIPT_ELEMENTS) || $this->isDangerousAnimation($domElement),
        );
        $this->removeUnsafeAttributes($domDocument);
    }

    /**
     * @param \DOMAttr $domAttr the attribute to check
     *
     * @return bool true if the attribute is an event handler or a name that can clobber the DOM of the host page
     */
    private function isUnsafeAttribute(\DOMAttr $domAttr): bool
    {
        return $this->isDangerousAttributeName($this->asciiLowercase($domAttr->name));
    }

    /**
     * @param \DOMElement $domElement the element to check
     */
    private function isDangerousAnimation(\DOMElement $domElement): bool
    {
        return $this->hasLocalNameIgnoringCase($domElement, [...Element::animationNames(), ...Element::linkingNames()])
            && [] !== array_filter(
                $this->foldedAttributeValues($domElement, self::FOLDED_ATTRIBUTE_NAME),
                $this->isDangerousAnimatedName(...),
            );
    }

    /**
     * @param string $attributeName the value of an attributeName attribute
     *
     * @return bool true if animating the attribute could rewrite a link or an event handler
     */
    private function isDangerousAnimatedName(string $attributeName): bool
    {
        $attributeName = $this->decodeUrlForCheck($attributeName);
        $separator = mb_strrpos($attributeName, ':');
        $localName = false === $separator ? $attributeName : mb_substr($attributeName, $separator + 1);

        return str_contains($attributeName, Attribute::Href->value) || $this->isDangerousAttributeName($localName);
    }

    /**
     * @param string $name the lowercase local name of an attribute
     *
     * @return bool true if the attribute is an event handler or can clobber the DOM of the host page
     */
    private function isDangerousAttributeName(string $name): bool
    {
        return self::CLOBBERING_ATTRIBUTE === $name || str_starts_with($name, self::EVENT_HANDLER_PREFIX);
    }
}
