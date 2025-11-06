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
     * Optimize the SVG document by minifying all transform attributes.
     *
     * @param \DOMDocument $domDocument The SVG document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);

        /** @var \DOMNodeList<\DOMElement> $elements */
        $elements = $domXPath->query('//*[@transform]');

        foreach ($elements as $element) {
            $transform = $element->getAttribute('transform');

            $transform = $this->convertPercentagesToNumbers($transform);
            $transform = $this->removeIdentityTransforms($transform);
            $transform = $this->normalizeSpacesAndCommas($transform);

            $transform = trim($transform);

            if ($this->isEmptyTransform($transform)) {
                $element->removeAttribute('transform');
            } else {
                $element->setAttribute('transform', $transform);
            }
        }
    }

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return false;
    }

    /**
     * Convert percentage values in the transform attribute to decimal numbers.
     *
     * @param string $transform The transform attribute string
     *
     * @return string The transform string with percentages converted
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
     * Remove identity transformations such as translate(0), scale(1), rotate(0), skewX(0), skewY(0).
     *
     * @param string $transform The transform attribute string
     *
     * @return string The transform string without identity transformations
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
     * Normalize whitespace and commas by collapsing multiple spaces and removing redundant commas.
     *
     * @param string $transform The transform attribute string
     *
     * @return string The normalized transform string
     */
    private function normalizeSpacesAndCommas(string $transform): string
    {
        $transform = preg_replace(self::MULTIPLE_SPACES_REGEX, ' ', $transform) ?? $transform;

        return preg_replace(self::REDUNDANT_COMMAS_REGEX, ',', $transform) ?? $transform;
    }

    /**
     * Determine whether the transform string is empty or equivalent to zero.
     *
     * @param string $transform The transform attribute string
     *
     * @return bool True if the transform should be considered empty and removed
     */
    private function isEmptyTransform(string $transform): bool
    {
        return '' === $transform
            || '0' === $transform
            || 1 === preg_match(self::EMPTY_TRANSFORM_REGEX, $transform);
    }
}
