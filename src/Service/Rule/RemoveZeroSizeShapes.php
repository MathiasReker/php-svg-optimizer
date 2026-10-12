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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\NumberFormatterTrait;

/**
 * Removes shapes that the SVG specification says are not rendered: a circle with a zero radius, an ellipse with a zero
 * radius, a rect with a zero width or height, and a path, polyline or polygon without any data.
 *
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveZeroSizeShapes implements SvgOptimizerRuleInterface
{
    use ElementTrait;
    use NumberFormatterTrait;

    /**
     * Attributes of which a zero value disables rendering, per shape.
     */
    private const array ZERO_DISABLES_RENDERING = [
        'circle' => ['r'],
        'ellipse' => ['rx', 'ry'],
        'rect' => ['width', 'height'],
    ];

    /**
     * Attribute that holds the geometry, for shapes that are not rendered when it is missing or empty.
     */
    private const array EMPTY_DISABLES_RENDERING = [
        'path' => 'd',
        'polyline' => 'points',
        'polygon' => 'points',
    ];

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
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        if ($this->hasStyleElement($domDocument)) {
            return;
        }

        foreach (iterator_to_array($domDocument->getElementsByTagName('*'), false) as $domElement) {
            if ($this->isRemovable($domElement) && $this->isZeroSize($domElement)) {
                $domElement->parentNode?->removeChild($domElement);
            }
        }
    }

    /**
     * Shapes that may be referenced, animated or sized by CSS are kept.
     *
     * @param \DOMElement $domElement the element to check
     */
    private function isRemovable(\DOMElement $domElement): bool
    {
        // A switch renders its first child whose conditions pass, so removing one would let another render.
        $parent = $domElement->parentNode;

        return !$this->hasId($domElement)
            && !$this->hasElementChildren($domElement)
            && !$domElement->hasAttribute(SvgAttribute::Style->value)
            && (!$parent instanceof \DOMElement || SvgTag::Switch->value !== $parent->localName);
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
