<?php
/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Services\Validators;

class SvgValidator
{
    /**
     * Regular expression to match the XML declaration.
     *
     * This regex pattern is used to identify and remove XML declarations
     * from the SVG content.
     *
     * @see https://regex101.com/r/ykHufE/1
     *
     * @var string
     */
    private const XML_DECLARATION_REGEX = '/^\s*<\?xml[^>]*\?>\s*/i';

    /**
     * Regular expression to match the DOCTYPE declaration.
     *
     * This regex pattern is used to identify and remove DOCTYPE declarations
     * from the SVG content.
     *
     * @see https://regex101.com/r/DIe4La/1
     *
     * @var string
     */
    private const DOCTYPE_REGEX = '/<!DOCTYPE[^>]*>/i';

    /**
     * Regular expression to match the start of an SVG tag.
     *
     * This regex pattern is used to check if the cleaned content contains
     * a valid SVG tag.
     *
     * @see https://regex101.com/r/dJUVOx/1
     *
     * @var string
     */
    private const SVG_TAG_REGEX = '/^\s*<svg\b[^>]*>/i';

    /**
     * Checks if the provided content is a valid SVG.
     *
     * This method validates if the content is a valid SVG by checking for
     * the presence of an SVG tag after removing any XML and DOCTYPE
     * declarations.
     *
     * @param string|null $svgContent The SVG content to be validated
     *
     * @return bool True if the content is a valid SVG, false otherwise
     */
    public function isValid(?string $svgContent): bool
    {
        if (null === $svgContent) {
            return false;
        }

        $cleanedContent = $this->removeUnnecessaryDeclarations($svgContent);

        return $this->containsSvgTag($cleanedContent);
    }

    /**
     * Remove XML and DOCTYPE declarations from the SVG content.
     *
     * This method cleans the SVG content by removing any XML and DOCTYPE
     * declarations to simplify validation.
     *
     * @param string $content The SVG content with potential declarations
     *
     * @return string The cleaned SVG content
     */
    private function removeUnnecessaryDeclarations(string $content): string
    {
        return preg_replace(
            [
                self::XML_DECLARATION_REGEX,
                self::DOCTYPE_REGEX,
            ],
            '',
            $content
        ) ?? '';
    }

    /**
     * Checks if the cleaned content contains a valid SVG tag.
     *
     * This method checks for the presence of an SVG tag in the cleaned SVG
     * content.
     *
     * @param string $content The cleaned SVG content
     *
     * @return bool True if the content contains a valid SVG tag, false otherwise
     */
    private function containsSvgTag(string $content): bool
    {
        return 1 === preg_match(self::SVG_TAG_REGEX, $content);
    }
}
