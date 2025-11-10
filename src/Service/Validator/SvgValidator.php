<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Validator;

use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;

/**
 * @no-named-arguments
 */
readonly class SvgValidator
{
    /**
     * Regular expression to match the XML declaration.
     *
     * This regex pattern is used to identify and remove XML declarations
     * from the SVG content.
     *
     * @see https://regex101.com/r/ykHufE/1
     */
    private const string XML_DECLARATION_REGEX = '/^\s*<\?xml[^>]*\?>\s*/i';

    /**
     * Regular expression to match the DOCTYPE declaration.
     *
     * This regex pattern is used to identify and remove DOCTYPE declarations
     * from the SVG content.
     *
     * @see https://regex101.com/r/DIe4La/1
     */
    private const string DOCTYPE_REGEX = '/<!DOCTYPE[^>]*>/i';

    /**
     * Regular expression to match the start of an SVG tag.
     *
     * This regex pattern is used to check if the cleaned content contains
     * a valid SVG tag.
     *
     * @see https://regex101.com/r/dJUVOx/1
     */
    private const string SVG_TAG_REGEX = '/^\s*<svg\b[^>]*>/i';

    /**
     * Regular expression to match HTML comments of an SVG tag.
     *
     * This regex pattern is used to identify and remove comments from the SVG content
     *
     * @see https://regex101.com/r/uu204z/1
     */
    private const string HTML_COMMENT_REGEX = '/<!--.*?-->/s';

    private DomDocumentWrapper $domDocumentWrapper;

    public function __construct()
    {
        $this->domDocumentWrapper = new DomDocumentWrapper();
    }

    /**
     * Checks if the provided content is a valid SVG.
     *
     * This method validates if the content is a valid SVG by checking for
     * the presence of an SVG tag after removing any XML and DOCTYPE
     * declarations.
     *
     * @param string $content The SVG content to be validated
     *
     * @return bool True if the content is a valid SVG, false otherwise
     */
    public function isValid(string $content): bool
    {
        $cleanedContent = $this->removeUnnecessaryDeclarations($content);

        if (!$this->containsSvgTag($cleanedContent)) {
            return false;
        }

        return $this->isWellFormedXml($content);
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
                self::HTML_COMMENT_REGEX,
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

    /**
     * Checks whether the given content is well-formed XML using DomDocumentWrapper.
     *
     * This method attempts to parse the content using DomDocumentWrapper to ensure
     * that it is valid XML. It does **not** check for specific SVG structure,
     * only that the XML is syntactically correct.
     *
     * @param string $content the XML content to validate
     *
     * @return bool true if the content is well-formed XML, false otherwise
     */
    private function isWellFormedXml(string $content): bool
    {
        try {
            $this->domDocumentWrapper->loadFromString($content);

            return true;
        } catch (XmlProcessingException) {
            return false;
        }
    }
}
