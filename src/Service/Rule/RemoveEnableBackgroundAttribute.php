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
final readonly class RemoveEnableBackgroundAttribute implements SvgOptimizerRuleInterface
{
    /**
     * Regular expression to match the "enable-background" value format.
     * The format is: 'new 0 0 width height'.
     *
     * @see https://regex101.com/r/h0AgKK/1
     */
    private const string ENABLE_BACKGROUND_REGEX = '/^new\s0\s0\s([-+]?\d*\.?\d+([eE][-+]?\d+)?)\s([-+]?\d*\.?\d+([eE][-+]?\d+)?)$/';

    /**
     * The name of the attribute to be removed or cleaned up.
     */
    private const string ENABLE_BACKGROUND_ATTRIBUTE = 'enable-background';

    /**
     * Optimizes the given SVG document by removing or cleaning up the `enable-background` attribute.
     *
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);
        $this->processEnableBackgroundAttributes($domXPath);
    }

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return false;
    }

    /**
     * Processes the `enable-background` attribute on SVG, mask, and pattern elements.
     *
     * @param \DOMXPath $domXPath The \DOMXPath instance used to query the SVG elements
     */
    private function processEnableBackgroundAttributes(\DOMXPath $domXPath): void
    {
        $elements = $domXPath->query('//*[@enable-background]');

        if (false === $elements) {
            return;
        }

        foreach ($elements as $element) {
            if ($element instanceof \DOMElement) {
                $enableBackgroundValue = $element->getAttribute(self::ENABLE_BACKGROUND_ATTRIBUTE);

                $width = $element->getAttribute('width');
                $height = $element->getAttribute('height');

                $cleanedValue = $this->cleanupEnableBackgroundValue($enableBackgroundValue, $width, $height);

                if ('' === trim($cleanedValue)) {
                    $element->removeAttribute(self::ENABLE_BACKGROUND_ATTRIBUTE);
                } else {
                    $element->setAttribute(self::ENABLE_BACKGROUND_ATTRIBUTE, $cleanedValue);
                }
            }
        }
    }

    /**
     * Cleans up the "enable-background" value by checking if it matches the width/height.
     *
     * @param string $value  The value of the enable-background attribute
     * @param string $width  The width of the element
     * @param string $height The height of the element
     *
     * @return string The cleaned up value, or empty if it is redundant
     */
    private function cleanupEnableBackgroundValue(string $value, string $width, string $height): string
    {
        if (\in_array(preg_match(self::ENABLE_BACKGROUND_REGEX, $value, $matches), [0, false], true)) {
            return $value;
        }

        if ($matches[1] === $width && $matches[3] === $height) {
            return '';
        }

        return $value;
    }
}
