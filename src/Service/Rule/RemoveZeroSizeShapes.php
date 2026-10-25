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
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveZeroSizeShapes implements SvgOptimizerRuleInterface
{
    use ElementTrait;
    use NumberFormatterTrait;

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
            && !$domElement->hasAttribute(SvgAttribute::Style->value)
            && SvgTag::Switch->value !== $parent->localName;
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
