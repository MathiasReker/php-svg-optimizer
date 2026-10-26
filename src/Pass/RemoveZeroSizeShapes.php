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
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberLiteralTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveZeroSizeShapes implements PassInterface
{
    use ElementTrait;
    use IdTrait;
    use NumberLiteralTrait;
    use ScriptingTrait;
    use StyleSheetTrait;

    private const array ZERO_DISABLES_RENDERING = [
        'circle' => ['r'],
        'ellipse' => ['rx', 'ry'],
        'rect' => ['width', 'height'],
    ];

    private const array EMPTY_DISABLES_RENDERING = [
        'path' => 'd',
        'polyline' => 'points',
        'polygon' => 'points',
    ];

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->hasStyleSheet($domDocument) || $this->hasScriptOrEventHandler($domDocument)) {
            return;
        }

        foreach ($this->elementsByQualifiedName($domDocument, '*') as $domElement) {
            $parent = $domElement->parentNode;

            if ($parent instanceof \DOMElement && $this->isRemovable($domElement, $parent) && $this->isZeroSize($domElement)) {
                $parent->removeChild($domElement);
            }
        }
    }

    /**
     * @param \DOMElement $domElement the element to check
     * @param \DOMElement $parent     the parent of the element
     */
    private function isRemovable(\DOMElement $domElement, \DOMElement $parent): bool
    {
        return $this->isSvgElement($domElement)
            && !$this->isUserExposed($domElement)
            && !$this->hasElementChildren($domElement)
            && !$domElement->hasAttribute(Attribute::Style->value)
            && Element::Switch->value !== $parent->localName;
    }

    /**
     * @param \DOMElement $domElement the element to check
     */
    private function isZeroSize(\DOMElement $domElement): bool
    {
        $localName = (string) $domElement->localName;

        foreach (self::ZERO_DISABLES_RENDERING[$localName] ?? [] as $attribute) {
            if ($this->isZeroAttribute($domElement, $attribute)) {
                return true;
            }
        }

        $geometry = self::EMPTY_DISABLES_RENDERING[$localName] ?? null;

        return null !== $geometry && '' === trim($domElement->getAttribute($geometry));
    }

    /**
     * @param \DOMElement $domElement the element to check
     * @param string      $attribute  the attribute name
     *
     * @return bool true if the attribute is present and a plain number equal to zero
     */
    private function isZeroAttribute(\DOMElement $domElement, string $attribute): bool
    {
        $value = $domElement->getAttribute($attribute);

        return $this->isPlainNumber($value) && $this->isZeroNumber(trim($value));
    }
}
