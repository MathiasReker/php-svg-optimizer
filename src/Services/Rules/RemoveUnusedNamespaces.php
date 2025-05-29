<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Services\Rules;

use DOMDocument;
use MathiasReker\PhpSvgOptimizer\Contracts\Services\Rules\SvgOptimizerRuleInterface;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Services\Util\XmlProcessor;

final readonly class RemoveUnusedNamespaces implements SvgOptimizerRuleInterface
{
    /**
     * Regex pattern for matching XML namespaces.
     *
     * @see https://regex101.com/r/EU11xA/1
     */
    private const string NAMESPACE_PATTERN = '/xmlns:([a-zA-Z0-9\-]+)="([^"]+)"/';

    /**
     * Regex pattern for matching SVG elements with namespaces.
     *
     * @see https://regex101.com/r/pxqIJN/1
     */
    private const string ELEMENT_PATTERN_TEMPLATE = '/%s:[a-zA-Z0-9\-]+/';

    private XmlProcessor $xmlProcessor;

    public function __construct()
    {
        $this->xmlProcessor = new XmlProcessor();
    }

    /**
     * Optimize the given DOMDocument by removing unused namespaces.
     *
     * @param \DOMDocument $domDocument The DOMDocument to optimize
     *
     * @throws XmlProcessingException When XML content cannot be saved or loaded
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $this->xmlProcessor->process($domDocument, fn (): string => $this->cleanNamespaces($domDocument));
    }

    /**
     * Optimize the SVG content by removing unused namespaces.
     *
     * @param \DOMDocument $domDocument The DOMDocument to optimize
     *
     * @return string The optimized SVG content with unused namespaces removed
     *
     * @throws XmlProcessingException When XML content cannot be saved or loaded
     */
    private function cleanNamespaces(\DOMDocument $domDocument): string
    {
        $svgContent = $this->xmlProcessor->process($domDocument, static fn (string $content): string => $content);

        $namespaceCounts = $this->countNamespaceElementsWithRegex($svgContent);

        foreach ($namespaceCounts as $namespaceKey => $count) {
            if (0 === $count) {
                $this->removeNamespaceFromSvgTags($domDocument, $namespaceKey);
            }
        }

        return $this->xmlProcessor->process($domDocument, static fn (string $content): string => $content);
    }

    /**
     * Count the number of elements associated with each namespace in the SVG content.
     *
     * @param string $svgContent The raw SVG content as a string
     *
     * @return array<string,int> An associative array where keys are namespace prefixes and values are counts of elements
     */
    private function countNamespaceElementsWithRegex(string $svgContent): array
    {
        $namespaceCounts = [];

        $namespacePattern = self::NAMESPACE_PATTERN;

        $result = preg_match_all($namespacePattern, $svgContent, $matches);
        if (false !== $result && $result > 0) {
            foreach ($matches[1] as $prefix) {
                $namespaceKey = 'xmlns:' . $prefix;
                $elementPattern = \sprintf(self::ELEMENT_PATTERN_TEMPLATE, preg_quote($prefix, '/'));
                preg_match_all($elementPattern, $svgContent, $elementMatches);
                $namespaceCounts[$namespaceKey] = \count($elementMatches[0]);
            }
        }

        return $namespaceCounts;
    }

    /**
     * Remove the specified namespace attribute from the SVG tags.
     *
     * @param \DOMDocument $domDocument        The DOMDocument instance representing the SVG to be optimized
     * @param string       $namespaceAttribute The namespace attribute to remove
     */
    private function removeNamespaceFromSvgTags(\DOMDocument $domDocument, string $namespaceAttribute): void
    {
        $root = $domDocument->documentElement;

        if ($root instanceof \DOMElement && $root->hasAttribute($namespaceAttribute)) {
            $root->removeAttribute($namespaceAttribute);
        }
    }
}
