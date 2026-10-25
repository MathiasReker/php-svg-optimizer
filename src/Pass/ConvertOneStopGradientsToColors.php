<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Color\NamedColorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\DefinitionElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\FragmentTargetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberLiteralTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Paint\GradientLinkTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class ConvertOneStopGradientsToColors implements RevertIfLargerPassInterface
{
    use AsciiCaseTrait;
    use DefinitionElementTrait;
    use ElementTrait;
    use FragmentTargetTrait;
    use GradientLinkTrait;
    use IdReferenceTrait;
    use IdTrait;
    use NamedColorTrait;
    use NumberLiteralTrait;
    use ScriptingTrait;
    use StyleSheetTrait;

    private const string PAINT_REFERENCE_REGEX = '/^\s*url\(\s*([\'"]?)#([^\'")\s]+)\1\s*\)\s*$/i';

    private const string INVERTIBLE_TRANSFORM_REGEX = '/^[ \t\n\r\f,]*(?:(?:translate|rotate)\s*\([^()]*\)[ \t\n\r\f,]*)*$/';

    private const string HEX_COLOR_REGEX = '/^#(?:[\da-f]{3,4}|[\da-f]{6}|[\da-f]{8})$/i';

    private const string DEFAULT_STOP_COLOR = '#000';

    private const string OPAQUE = '1';

    private const array PAINT_ATTRIBUTES = ['fill', 'stroke'];

    private const string USER_SPACE_ON_USE = 'userSpaceOnUse';

    private const array BOX_SHAPES = ['rect', 'circle', 'ellipse'];

    private const string TRANSPARENT = 'transparent';

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->hasStyleSheet($domDocument) || $this->hasScriptOrEventHandler($domDocument)) {
            return;
        }

        $gradients = $this->gradientsById($domDocument);
        [$paintReferences, $otherReferences] = $this->collectPaintReferences($domDocument);
        $otherReferences += $this->fragmentTargetIds($domDocument);

        foreach ($paintReferences as $id => $attributes) {
            if (!\array_key_exists($id, $gradients)
                || \array_key_exists($id, $otherReferences)
                || !$this->isSvgElement($gradients[$id])) {
                continue;
            }

            $chain = $this->gradientChain($gradients[$id], $gradients);
            $color = $this->resolveStopColor($chain);

            if ('' !== $color && $this->hasInvertibleTransform($chain) && $this->paintsWithoutBoundingBox($chain, $attributes)) {
                $this->replacePaint($gradients[$id], $attributes, $color);
            }
        }

        $this->removeEmptyDefsElements($domDocument);
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to scan
     *
     * @return array{array<string, list<\DOMAttr>>, array<string, true>} the paint attributes by gradient id, and the
     *                                                                   ids referenced in any other way
     */
    private function collectPaintReferences(\DOMDocument $domDocument): array
    {
        $paintReferences = [];
        $otherReferences = [];

        foreach ($domDocument->getElementsByTagName('*') as $element) {
            foreach ($element->attributes as $attribute) {
                $id = $this->paintReferenceId($attribute);

                if ('' === $id) {
                    $otherReferences += $this->attributeReferences($attribute);
                } else {
                    $paintReferences[$id][] = $attribute;
                }
            }
        }

        return [$paintReferences, $otherReferences];
    }

    /**
     * @param \DOMAttr $domAttr the attribute to check
     *
     * @return string the referenced id if the attribute is a fill or stroke consisting of `url(#id)` only, otherwise ''
     */
    private function paintReferenceId(\DOMAttr $domAttr): string
    {
        if (!\in_array($domAttr->nodeName, self::PAINT_ATTRIBUTES, true)
            || 1 !== preg_match(self::PAINT_REFERENCE_REGEX, $domAttr->value, $matches)) {
            return '';
        }

        return $matches[2];
    }

    /**
     * @param list<\DOMElement> $chain the gradient and the gradients it links to
     *
     * @return string the color of the gradient's only stop, or '' if it cannot be replaced by a solid color
     */
    private function resolveStopColor(array $chain): string
    {
        foreach ($chain as $gradient) {
            $children = $this->childElements($gradient);

            if ([] !== $children) {
                return $this->singleStopColor($children);
            }
        }

        return '';
    }

    /**
     * @param non-empty-list<\DOMElement> $children the child elements of the gradient that defines the stops
     *
     * @return string the color of the only stop, or '' if the gradient has other children
     */
    private function singleStopColor(array $children): string
    {
        $stop = $children[0];

        return 1 === \count($children) && Element::Stop->value === $stop->localName && $this->isSvgElement($stop)
            ? $this->stopColor($stop)
            : '';
    }

    /**
     * @param \DOMElement $domElement the only stop of a gradient
     *
     * @return string the stop color, or '' if it cannot be used as a paint unchanged
     */
    private function stopColor(\DOMElement $domElement): string
    {
        $opacity = $domElement->getAttribute(Attribute::StopOpacity->value);

        if ($this->hasElementChildren($domElement)
            || $this->hasId($domElement)
            || $domElement->hasAttribute(Attribute::Style->value)
            || ($domElement->hasAttribute(Attribute::StopOpacity->value) && !$this->isOpaque($opacity))) {
            return '';
        }

        if (!$domElement->hasAttribute(Attribute::StopColor->value)) {
            return self::DEFAULT_STOP_COLOR;
        }

        $color = trim($domElement->getAttribute(Attribute::StopColor->value));

        return 1 === preg_match(self::HEX_COLOR_REGEX, $color)
            || $this->isNamedColor($color)
            || self::TRANSPARENT === $this->asciiLowercase($color) ? $color : '';
    }

    /**
     * @param string $opacity the stop opacity
     */
    private function isOpaque(string $opacity): bool
    {
        return self::OPAQUE === $this->minifyNumber(trim($opacity));
    }

    /**
     * @param list<\DOMElement> $chain the gradient and the gradients it links to
     *
     * @return bool true if the gradient transform can be inverted, as a gradient with a singular transform paints
     *              nothing, and is not set through a style attribute
     */
    private function hasInvertibleTransform(array $chain): bool
    {
        foreach ($chain as $gradient) {
            if ($gradient->hasAttribute(Attribute::Style->value)) {
                return false;
            }
        }

        return 1 === preg_match(self::INVERTIBLE_TRANSFORM_REGEX, $this->linkedGradientAttribute($chain, Attribute::GradientTransform->value));
    }

    /**
     * @param list<\DOMElement> $chain      the gradient and the gradients it links to
     * @param list<\DOMAttr>    $attributes the paint attributes referencing the gradient
     */
    private function paintsWithoutBoundingBox(array $chain, array $attributes): bool
    {
        if (self::USER_SPACE_ON_USE === $this->linkedGradientAttribute($chain, Attribute::GradientUnits->value)) {
            return true;
        }

        foreach ($attributes as $attribute) {
            $owner = $attribute->ownerElement;

            if (!$owner instanceof \DOMElement || !\in_array($owner->localName, self::BOX_SHAPES, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \DOMElement    $domElement the gradient to remove
     * @param list<\DOMAttr> $attributes the paint attributes referencing it
     * @param string         $color      the color to paint instead
     */
    private function replacePaint(\DOMElement $domElement, array $attributes, string $color): void
    {
        foreach ($attributes as $attribute) {
            $attribute->value = $color;
        }

        $domElement->remove();
    }
}
