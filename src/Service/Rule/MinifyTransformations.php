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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\NumberFormatterTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\NumberListTrait;

/**
 * Shortens transform lists, such as `translate(10, 0) scale(2, 2) rotate(0)` to `translate(10)scale(2)`. Identity
 * transforms are dropped, default arguments are omitted, and numbers are written in their shortest notation.
 * Values are never rounded and transforms are never combined; a value that is not a valid transform list is kept.
 *
 * @internal
 *
 * @no-named-arguments
 */
final readonly class MinifyTransformations implements SvgOptimizerRuleInterface
{
    use NumberFormatterTrait;
    use NumberListTrait;

    private const array TRANSFORM_ATTRIBUTES = [
        SvgAttribute::Transform,
        SvgAttribute::GradientTransform,
        SvgAttribute::PatternTransform,
    ];

    /**
     * The number of arguments each transform function accepts.
     */
    private const array ARGUMENT_COUNTS = [
        'matrix' => [6],
        'translate' => [1, 2],
        'scale' => [1, 2],
        'rotate' => [1, 3],
        'skewX' => [1],
        'skewY' => [1],
    ];

    private const string TRANSFORM_ITEM = '[a-zA-Z]++\s*+\([^()]*+\)';

    private const string TRANSFORM_LIST_REGEX = '/^\s*+' . self::TRANSFORM_ITEM . '(?:\s*+,?+\s*+' . self::TRANSFORM_ITEM . ')*+\s*+$/';

    private const string TRANSFORM_ITEM_REGEX = '/([a-zA-Z]+)\s*\(([^()]*)\)/';

    private const string ONE = '1';

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
        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            foreach (self::TRANSFORM_ATTRIBUTES as $svgAttribute) {
                if ($domNodeList->hasAttribute($svgAttribute->value)) {
                    $this->minifyAttribute($domNodeList, $svgAttribute->value);
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

        $minified = implode('', array_map(fn (array $item): string => $this->minifyItem($item[0], $item[1]), $items));

        if ('' === $minified) {
            $domElement->removeAttribute($name);
        } elseif (mb_strlen($minified) < mb_strlen($value)) {
            $domElement->setAttribute($name, $minified);
        }
    }

    /**
     * @param string $value the attribute value
     *
     * @return list<array{string, list<string>}> the transforms as [function, arguments], or an empty list if the value
     *                                           is not a valid transform list
     */
    private function parseTransformList(string $value): array
    {
        if (1 !== preg_match(self::TRANSFORM_LIST_REGEX, $value)) {
            return [];
        }

        preg_match_all(self::TRANSFORM_ITEM_REGEX, $value, $matches, \PREG_SET_ORDER);

        $items = [];

        foreach ($matches as [, $function, $argumentList]) {
            $arguments = array_map($this->minifyNumber(...), $this->parseNumberList($argumentList));

            if (!\in_array(\count($arguments), self::ARGUMENT_COUNTS[$function] ?? [], true)) {
                return [];
            }

            $items[] = [$function, $arguments];
        }

        return $items;
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
     * A matrix without rotation or skew is written as the equivalent translate or scale.
     *
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
            $arguments = [$arguments[0]];
        }

        return [self::ONE] === $arguments ? '' : $this->writeItem('scale', $arguments);
    }

    /**
     * A rotation by zero degrees is an identity around any center.
     *
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
