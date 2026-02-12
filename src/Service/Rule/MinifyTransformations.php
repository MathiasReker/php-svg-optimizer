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

/**
 * @no-named-arguments
 */
final readonly class MinifyTransformations implements SvgOptimizerRuleInterface
{
    /**
     * Regex pattern to match percentage values in transformations.
     *
     * @see https://regex101.com/r/JUBzng/1
     */
    private const string PERCENTAGE_REGEX = '/(\d+)%/';

    /**
     * Regex pattern to match identity translate transformations.
     *
     * @see https://regex101.com/r/bHuCPE/1
     */
    private const string TRANSLATE_REGEX = '/\btranslate\(\s*0(?:e[+-]?\d+)?\s*(,\s*0(?:e[+-]?\d+)?\s*)?\)/i';

    /**
     * Regex pattern to match identity scale transformations.
     *
     * @see https://regex101.com/r/6R39n2/1
     */
    private const string SCALE_REGEX = '/\bscale\(\s*1(?:e[+-]?\d+)?\s*(,\s*1(?:e[+-]?\d+)?\s*)?\)/i';

    /**
     * Regex pattern to match identity rotate transformations.
     *
     * @see https://regex101.com/r/2vmgRO/1
     */
    private const string ROTATE_REGEX = '/\brotate\(\s*0\s*\)/';

    /**
     * Regex pattern to match identity skewX transformations.
     *
     * @see https://regex101.com/r/83aNVu/1
     */
    private const string SKEW_X_REGEX = '/\bskewX\(\s*0\s*\)/';

    /**
     * Regex pattern to match identity skewY transformations.
     *
     * @see https://regex101.com/r/tiPsgQ/1
     */
    private const string SKEW_Y_REGEX = '/\bskewY\(\s*0\s*\)/';

    /**
     * Regex pattern to match multiple consecutive spaces.
     *
     * @see https://regex101.com/r/OuyK7V/1
     */
    private const string MULTIPLE_SPACES_REGEX = '/\s+/';

    /**
     * Regex pattern to match redundant commas.
     *
     * @see https://regex101.com/r/E8wfPk/1
     */
    private const string REDUNDANT_COMMAS_REGEX = '/\s*,\s*/';

    /**
     * Regex pattern to match empty or whitespace-only transform attributes.
     *
     * This pattern matches strings that consist only of semicolons, commas, spaces, or are completely empty.
     *
     * @see https://regex101.com/r/LQt8ho/1
     */
    private const string EMPTY_TRANSFORM_REGEX = '/^[;, ]*$/';

    /**
     * Regex pattern to match identity matrix transformations.
     *
     * This pattern matches the identity matrix transformation in SVG, which is equivalent to no transformation.
     *
     * @see https://regex101.com/r/o39rvr/1
     */
    private const string MATRIX_IDENTITY_REGEX = '/\bmatrix\(\s*1(?:e[+-]?\d+)?\s+0(?:e[+-]?\d+)?\s+0(?:e[+-]?\d+)?\s+1(?:e[+-]?\d+)?\s+0(?:e[+-]?\d+)?\s+0(?:e[+-]?\d+)?\s*\)/i';

    /**
     * XPath query to select all elements with a transform attribute.
     */
    private const string XPATH_TRANSFORM_ATTRIBUTES = '//*[@transform]';

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
     * Minifies `transform` attributes on all applicable SVG elements.
     *
     * This rule applies several optimizations:
     * - Converts percentage values to decimals.
     * - Removes identity transformations (e.g., `translate(0)`, `scale(1)`).
     * - Normalizes whitespace and commas.
     * - Removes the `transform` attribute entirely if it becomes empty.
     *
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);

        /** @var \DOMNodeList<\DOMElement> $domNodeList */
        $domNodeList = $domXPath->query(self::XPATH_TRANSFORM_ATTRIBUTES);

        foreach ($domNodeList as $domElement) {
            $transform = $domElement->getAttribute(SvgAttribute::Transform->value);

            $transform = $this->convertPercentagesToNumbers($transform);
            $transform = $this->removeIdentityTransforms($transform);
            $transform = $this->normalizeSpacesAndCommas($transform);

            $transform = trim($transform);

            if ($this->isEmptyTransform($transform)) {
                $domElement->removeAttribute(SvgAttribute::Transform->value);
            } else {
                $domElement->setAttribute(SvgAttribute::Transform->value, $transform);
            }
        }
    }

    /**
     * Converts percentage values within a transform string to their decimal equivalents.
     *
     * @param string $transform the transform attribute value
     *
     * @return string the modified transform string
     */
    private function convertPercentagesToNumbers(string $transform): string
    {
        return preg_replace_callback(
            self::PERCENTAGE_REGEX,
            static fn (array $matches): string => (string) ((float) $matches[1] / 100),
            $transform
        ) ?? $transform;
    }

    /**
     * Removes identity transformations from a transform string.
     *
     * This includes `translate(0)`, `scale(1)`, `rotate(0)`, `skewX(0)`,
     * `skewY(0)`, and the identity matrix.
     *
     * @param string $transform the transform attribute value
     *
     * @return string the modified transform string
     */
    private function removeIdentityTransforms(string $transform): string
    {
        return preg_replace(
            [
                self::TRANSLATE_REGEX,
                self::SCALE_REGEX,
                self::ROTATE_REGEX,
                self::SKEW_X_REGEX,
                self::SKEW_Y_REGEX,
                self::MATRIX_IDENTITY_REGEX,
            ],
            '',
            $transform
        ) ?? $transform;
    }

    /**
     * Normalizes whitespace and commas in a transform string.
     *
     * Collapses multiple spaces into one and standardizes the spacing around commas.
     *
     * @param string $transform the transform attribute value
     *
     * @return string the normalized transform string
     */
    private function normalizeSpacesAndCommas(string $transform): string
    {
        $transform = preg_replace(self::MULTIPLE_SPACES_REGEX, ' ', $transform) ?? $transform;

        return preg_replace(self::REDUNDANT_COMMAS_REGEX, ',', $transform) ?? $transform;
    }

    /**
     * Checks if a transform string is effectively empty.
     *
     * A transform is considered empty if it contains only whitespace, commas,
     * or semicolons.
     *
     * @param string $transform the transform attribute value
     *
     * @return bool true if the transform is empty
     */
    private function isEmptyTransform(string $transform): bool
    {
        return 1 === preg_match(self::EMPTY_TRANSFORM_REGEX, $transform);
    }
}
