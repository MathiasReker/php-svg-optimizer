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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\PathDataSerializerTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\RenameElementTrait;

/**
 * Converts `<rect>`, `<line>`, `<polyline>` and `<polygon>` to `<path>`. The path data reuses the original numbers
 * (a rect is drawn with relative moves), so no coordinate is recalculated.
 *
 * @no-named-arguments
 */
final readonly class ConvertShapesToPath implements SvgOptimizerRuleInterface
{
    use ElementTrait;
    use NumberFormatterTrait;
    use PathDataSerializerTrait;
    use RenameElementTrait;

    /**
     * Geometry attributes per shape, in the order the path data uses them.
     */
    private const array GEOMETRY_ATTRIBUTES = [
        'rect' => ['x', 'y', 'width', 'height'],
        'line' => ['x1', 'y1', 'x2', 'y2'],
        'polyline' => ['points'],
        'polygon' => ['points'],
    ];

    private const string POINTS_SEPARATOR_REGEX = '/[\s,]+/';

    #[\Override]
    public static function isRisky(): bool
    {
        return false;
    }

    #[\Override]
    public static function shouldCheckSize(): bool
    {
        return true;
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        if ($this->hasStyleElement($domDocument) || [] !== $this->elementsNamed($domDocument, SvgTag::Marker->value)) {
            return;
        }

        $animationTargetIds = $this->animationTargetIds($domDocument);

        foreach (self::GEOMETRY_ATTRIBUTES as $tag => $attributes) {
            foreach ($this->elementsNamed($domDocument, $tag) as $domElement) {
                if (!\array_key_exists($domElement->getAttribute(SvgAttribute::Id->value), $animationTargetIds)) {
                    $this->convert($domElement, $tag, $attributes);
                }
            }
        }
    }

    /**
     * @param \DOMElement  $domElement the shape element
     * @param string       $tag        the shape name
     * @param list<string> $attributes the geometry attributes that the path data replaces
     */
    private function convert(\DOMElement $domElement, string $tag, array $attributes): void
    {
        if ($this->hasElementChildren($domElement) || $domElement->hasAttribute(SvgAttribute::Style->value)) {
            return;
        }

        $segments = match ($tag) {
            SvgTag::Rect->value => $this->rectSegments($domElement),
            SvgTag::Line->value => $this->lineSegments($domElement),
            default => $this->pointsSegments($domElement, SvgTag::Polygon->value === $tag),
        };

        if ([] === $segments) {
            return;
        }

        foreach ($attributes as $attribute) {
            $domElement->removeAttribute($attribute);
        }

        $domElement->setAttribute(SvgAttribute::D->value, $this->serializePathData($segments));
        $this->renameElement($domElement, SvgTag::Path->value);
    }

    /**
     * Rects with rounded corners are kept, as are rects without a positive size, which do not render.
     *
     * @param \DOMElement $domElement the rect element
     *
     * @return list<array{string, list<string>}>
     */
    private function rectSegments(\DOMElement $domElement): array
    {
        if ($domElement->hasAttribute(SvgAttribute::Rx->value) || $domElement->hasAttribute(SvgAttribute::Ry->value)) {
            return [];
        }

        $values = $this->numbers($domElement, self::GEOMETRY_ATTRIBUTES['rect']);

        if ([] === $values || !$this->isPositive($values[2]) || !$this->isPositive($values[3])) {
            return [];
        }

        [$x, $y, $width, $height] = $values;

        return [['M', [$x, $y]], ['h', [$width]], ['v', [$height]], ['h', ['-' . $width]], ['z', []]];
    }

    /**
     * @param \DOMElement $domElement the line element
     *
     * @return list<array{string, list<string>}>
     */
    private function lineSegments(\DOMElement $domElement): array
    {
        $values = $this->numbers($domElement, self::GEOMETRY_ATTRIBUTES['line']);

        if ([] === $values) {
            return [];
        }

        return [['M', [$values[0], $values[1]]], ['L', [$values[2], $values[3]]]];
    }

    /**
     * Point lists with an odd number of coordinates are an error that renders partially, so they are kept.
     *
     * @param \DOMElement $domElement the polyline or polygon element
     * @param bool        $close      whether to close the path
     *
     * @return list<array{string, list<string>}>
     */
    private function pointsSegments(\DOMElement $domElement, bool $close): array
    {
        $points = trim($domElement->getAttribute(SvgAttribute::Points->value));
        $coordinates = '' === $points ? [] : preg_split(self::POINTS_SEPARATOR_REGEX, $points);

        if (false === $coordinates || [] === $coordinates || 0 !== \count($coordinates) % 2
            || [] !== array_filter($coordinates, fn (string $value): bool => !$this->isPlainNumber($value))
        ) {
            return [];
        }

        $segments = [];

        foreach (array_chunk($coordinates, 2) as $index => $pair) {
            $segments[] = [0 === $index ? 'M' : 'L', $pair];
        }

        if ($close) {
            $segments[] = ['z', []];
        }

        return $segments;
    }

    /**
     * @param \DOMElement  $domElement the element
     * @param list<string> $attributes the attributes to read, defaulting to zero when absent
     *
     * @return list<string> the minified values, or an empty list if any value is not a plain number
     */
    private function numbers(\DOMElement $domElement, array $attributes): array
    {
        $values = [];

        foreach ($attributes as $attribute) {
            $value = $domElement->hasAttribute($attribute) ? $domElement->getAttribute($attribute) : '0';

            if (!$this->isPlainNumber($value)) {
                return [];
            }

            $values[] = $this->minifyNumber(trim($value));
        }

        return $values;
    }

    /**
     * @param string $value a minified number
     */
    private function isPositive(string $value): bool
    {
        return !str_starts_with($value, '-') && !$this->isZeroNumber($value);
    }
}
