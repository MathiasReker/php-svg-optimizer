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
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberListTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberLiteralTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Transform\TransformListTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class MinifyTransforms implements PassInterface
{
    use NumberListTrait;
    use NumberLiteralTrait;
    use StyleSheetTrait;
    use TransformListTrait;

    private const array TRANSFORM_ATTRIBUTES = [
        Attribute::Transform,
        Attribute::GradientTransform,
        Attribute::PatternTransform,
    ];

    private const string ONE = '1';

    private const string IDENTITY_TRANSFORM = 'scale(1)';

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->maySelectByAttribute($domDocument)) {
            return;
        }

        foreach ($domDocument->getElementsByTagName('*') as $element) {
            foreach (self::TRANSFORM_ATTRIBUTES as $transformAttribute) {
                if ($element->hasAttribute($transformAttribute->value)) {
                    $this->minifyAttribute($element, $transformAttribute->value);
                }
            }
        }
    }

    /**
     * @param \DOMElement $domElement the element holding the transform
     * @param string      $name       the attribute name
     */
    private function minifyAttribute(\DOMElement $domElement, string $name): void
    {
        $value = $domElement->getAttribute($name);
        $items = $this->parseTransformList($value);

        if ([] === $items) {
            return;
        }

        $minified = implode('', array_map(fn (array $item): string => $this->minifyItem($item[0], array_map($this->minifyNumber(...), $item[1])), $items));

        if ('' === $minified && !$this->canBeInherited($domElement, $name)) {
            $domElement->removeAttribute($name);

            return;
        }

        if ('' === $minified) {
            $minified = self::IDENTITY_TRANSFORM;
        }

        if (mb_strlen($minified) < mb_strlen($value)) {
            $domElement->setAttribute($name, $minified);
        }
    }

    /**
     * @param \DOMElement $domElement the element holding the transform
     * @param string      $name       the attribute name
     *
     * @return bool true if the element would inherit the attribute from a referenced element when it is removed
     */
    private function canBeInherited(\DOMElement $domElement, string $name): bool
    {
        return Attribute::Transform->value !== $name
            && ($domElement->hasAttribute(Attribute::Href->value)
                || $domElement->hasAttributeNS(NamespaceUri::Xlink->value, Attribute::Href->value));
    }

    /**
     * @param string       $function  the transform function
     * @param list<string> $arguments the minified arguments
     *
     * @return string the shortest equivalent transform, or an empty string for an identity transform
     */
    private function minifyItem(string $function, array $arguments): string
    {
        return match ($function) {
            'matrix' => $this->minifyMatrix($arguments),
            'translate' => $this->minifyTranslate($arguments),
            'scale' => $this->minifyScale($arguments),
            'rotate' => $this->minifyRotate($arguments),
            default => $this->isZeroNumber($arguments[0]) ? '' : $this->writeItem($function, $arguments),
        };
    }

    /**
     * @param list<string> $arguments the six minified arguments
     */
    private function minifyMatrix(array $arguments): string
    {
        [$a, $b, $c, $d, $e, $f] = $arguments;

        if (!$this->isZeroNumber($b) || !$this->isZeroNumber($c)) {
            return $this->writeItem('matrix', $arguments);
        }

        if (self::ONE === $a && self::ONE === $d) {
            return $this->minifyTranslate([$e, $f]);
        }

        if ($this->isZeroNumber($e) && $this->isZeroNumber($f)) {
            return $this->minifyScale([$a, $d]);
        }

        return $this->writeItem('matrix', $arguments);
    }

    /**
     * @param list<string> $arguments the minified arguments
     */
    private function minifyTranslate(array $arguments): string
    {
        if (2 === \count($arguments) && $this->isZeroNumber($arguments[1])) {
            $arguments = [$arguments[0]];
        }

        return [self::ZERO] === $arguments ? '' : $this->writeItem('translate', $arguments);
    }

    /**
     * @param list<string> $arguments the minified arguments
     */
    private function minifyScale(array $arguments): string
    {
        if (2 === \count($arguments) && $arguments[0] === $arguments[1]) {
            array_pop($arguments);
        }

        return [self::ONE] === $arguments ? '' : $this->writeItem('scale', $arguments);
    }

    /**
     * @param list<string> $arguments the minified arguments
     */
    private function minifyRotate(array $arguments): string
    {
        if ($this->isZeroNumber($arguments[0])) {
            return '';
        }

        if (3 === \count($arguments) && $this->isZeroNumber($arguments[1]) && $this->isZeroNumber($arguments[2])) {
            $arguments = [$arguments[0]];
        }

        return $this->writeItem('rotate', $arguments);
    }

    /**
     * @param string       $function  the transform function
     * @param list<string> $arguments the minified arguments
     */
    private function writeItem(string $function, array $arguments): string
    {
        return $function . '(' . $this->joinNumberList($arguments, true) . ')';
    }
}
