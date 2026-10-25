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
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberLiteralTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class MinifyNumericValues implements PassInterface
{
    use ElementTrait;
    use NumberLiteralTrait;
    use StyleSheetTrait;

    private const array LENGTH_ATTRIBUTES = [
        'cx', 'cy', 'dx', 'dy', 'font-size', 'fr', 'fx', 'fy', 'height', 'letter-spacing', 'markerHeight',
        'markerWidth', 'r', 'refX', 'refY', 'rx', 'ry', 'startOffset', 'stroke-dashoffset', 'stroke-width',
        'textLength', 'width', 'word-spacing', 'x', 'x1', 'x2', 'y', 'y1', 'y2',
    ];

    private const array NUMBER_ATTRIBUTES = [
        'amplitude', 'azimuth', 'diffuseConstant', 'elevation', 'exponent', 'fill-opacity', 'flood-opacity',
        'intercept', 'k', 'k1', 'k2', 'k3', 'k4', 'limitingConeAngle', 'offset', 'opacity',
        'pathLength', 'pointsAtX', 'pointsAtY', 'pointsAtZ', 'scale', 'seed', 'slope', 'specularConstant',
        'specularExponent', 'stop-opacity', 'stroke-miterlimit', 'stroke-opacity', 'surfaceScale', 'z',
    ];

    private const array INTEGER_ATTRIBUTES = ['numOctaves'];

    private const array NUMBER_ATTRIBUTES_BY_ELEMENT = [
        'dx' => ['feDropShadow', 'feOffset'],
        'dy' => ['feDropShadow', 'feOffset'],
        'x' => ['fePointLight', 'feSpotLight'],
        'y' => ['fePointLight', 'feSpotLight'],
    ];

    private const string LENGTH_UNIT_PATTERN = '%|(?i:px|cm|mm|q|in|pt|pc|r?em|r?ex|r?ch|r?cap|r?ic|r?lh|[sld]?v(?:w|h|i|b|min|max)|cq(?:w|h|i|b|min|max))';

    private const string LENGTH_REGEX = '/^\s*(' . self::NUMBER_PATTERN . ')(?:px|(' . self::LENGTH_UNIT_PATTERN . '))?\s*$/';

    private const string NUMBER_REGEX = '/^\s*(' . self::NUMBER_PATTERN . ')\s*$/';

    private const string INTEGER_REGEX = '/^\s*(-?\d+)\s*$/';

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->maySelectByAttributeValue($domDocument)) {
            return;
        }

        foreach ($domDocument->getElementsByTagName('*') as $element) {
            if (!$this->isSvgElement($element)) {
                continue;
            }

            foreach (iterator_to_array($element->attributes, false) as $domAttr) {
                $this->minifyAttribute($element->localName ?? '', $domAttr);
            }
        }
    }

    /**
     * @param string   $element the local name of the element holding the attribute
     * @param \DOMAttr $domAttr the attribute to minify
     */
    private function minifyAttribute(string $element, \DOMAttr $domAttr): void
    {
        if (null !== $domAttr->namespaceURI) {
            return;
        }

        $regex = $this->valueRegex($element, $domAttr->name);

        if ('' !== $regex && 1 === preg_match($regex, $domAttr->value, $matches)) {
            $domAttr->value = $this->minifyNumber($matches[1]) . ($matches[2] ?? '');
        }
    }

    /**
     * @param string $element   the local name of the element holding the attribute
     * @param string $attribute the attribute name
     *
     * @return string the regex matching a value that can be shortened, or an empty string if the attribute is not handled
     */
    private function valueRegex(string $element, string $attribute): string
    {
        return match (true) {
            \in_array($element, self::NUMBER_ATTRIBUTES_BY_ELEMENT[$attribute] ?? [], true) => self::NUMBER_REGEX,
            \in_array($attribute, self::LENGTH_ATTRIBUTES, true) => self::LENGTH_REGEX,
            \in_array($attribute, self::NUMBER_ATTRIBUTES, true) => self::NUMBER_REGEX,
            \in_array($attribute, self::INTEGER_ATTRIBUTES, true) => self::INTEGER_REGEX,
            default => '',
        };
    }
}
