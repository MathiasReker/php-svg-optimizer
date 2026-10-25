<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Trait;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait TransformListTrait
{
    use NumberFormatterTrait;
    use NumberListTrait;

    private const array TRANSFORM_ARGUMENT_COUNTS = [
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
            $arguments = $this->parseNumberList($argumentList, false);

            if (!\in_array(\count($arguments), self::TRANSFORM_ARGUMENT_COUNTS[$function] ?? [], true)) {
                return [];
            }

            $items[] = [$function, $arguments];
        }

        return $items;
    }

    /**
     * @param string $value the attribute value
     *
     * @return bool true if the value is empty or a transform list that the browser applies, so that it can be
     *              combined with another transform list
     */
    private function isEmptyOrValidTransform(string $value): bool
    {
        return '' === trim($value) || [] !== $this->parseTransformList($value);
    }

    /**
     * @param string $parentTransform the transform of the parent, applied first
     * @param string $childTransform  the transform of the child
     *
     * @return string the combined transform list, or an empty string if both are empty
     */
    private function combineTransforms(string $parentTransform, string $childTransform): string
    {
        $transforms = array_filter(
            [trim($parentTransform), trim($childTransform)],
            static fn (string $transform): bool => '' !== $transform,
        );

        return implode(' ', $transforms);
    }
}
