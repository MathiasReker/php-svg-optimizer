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
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementNameTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveEmptyContainers implements PassInterface
{
    use AsciiCaseTrait;
    use ElementNameTrait;
    use ElementTrait;
    use IdTrait;
    use ScriptingTrait;
    use StyleSheetTrait;

    private const string FILTER_OR_ESCAPE_REGEX = '/filter|\\\/i';

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if (!$this->hasScriptOrEventHandler($domDocument)) {
            $this->removeElementsBottomUp($domDocument, $this->isRemovable(...));
        }
    }

    /**
     * @param \DOMElement $domElement the element to check
     */
    private function isRemovable(\DOMElement $domElement): bool
    {
        return $this->isSvgElement($domElement)
            && $this->hasLocalNameIgnoringCase($domElement, $this->containerElements())
            && !$this->isUserExposed($domElement)
            && $this->isEmptyElement($domElement)
            && !$domElement->hasAttribute(Attribute::Filter->value)
            && !str_contains($this->asciiLowercase($domElement->getAttribute(Attribute::Style->value)), Attribute::Filter->value);
    }

    /**
     * @return list<string>
     */
    private function containerElements(): array
    {
        return array_map(static fn (Element $element): string => $element->value, [
            Element::ClipPath,
            Element::Defs,
            Element::G,
            Element::Marker,
            Element::Mask,
            Element::Pattern,
            Element::Switch,
            Element::Symbol,
        ]);
    }

    /**
     * @param \DOMDocument                $domDocument the document to clean
     * @param callable(\DOMElement): bool $isRemovable returns true for elements to remove
     */
    private function removeElementsBottomUp(\DOMDocument $domDocument, callable $isRemovable): void
    {
        if ($this->hasStructureSensitiveStyleSheet($domDocument) || 1 === preg_match(self::FILTER_OR_ESCAPE_REGEX, $this->styleSheetText($domDocument))) {
            return;
        }

        $elements = array_reverse($this->elementsByQualifiedName($domDocument, '*'));

        foreach ($elements as $element) {
            $parent = $element->parentNode;

            if ($parent instanceof \DOMElement && Element::Switch->value !== $parent->localName && $isRemovable($element)) {
                $parent->removeChild($element);
            }
        }
    }
}
