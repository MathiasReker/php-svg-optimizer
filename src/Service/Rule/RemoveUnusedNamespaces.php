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
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Service\Processor\AbstractXmlProcessor;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;

/**
 * @no-named-arguments
 */
final readonly class RemoveUnusedNamespaces extends AbstractXmlProcessor implements SvgOptimizerRuleInterface
{
    /**
     * Regex pattern for matching XML namespaces.
     *
     * @see https://regex101.com/r/EU11xA/1
     */
    private const string NAMESPACE_REGEX = '/xmlns:([a-zA-Z0-9\-]+)="([^"]+)"/';

    /**
     * Regex pattern for matching SVG elements with namespaces.
     *
     * @see https://regex101.com/r/pxqIJN/1
     */
    private const string ELEMENT_TEMPLATE_REGEX = '/%s:[a-zA-Z0-9\-]+/';

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
     * Removes unused XML namespaces from the SVG document.
     *
     * @param \DOMDocument $domDocument the DOM document to optimize
     *
     * @throws XmlProcessingException if the XML content cannot be processed
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $this->process($domDocument, fn (): string => $this->cleanNamespaces($domDocument));
    }

    /**
     * Cleans unused namespaces from the provided DOM document.
     *
     * This method identifies all namespace declarations, counts their usage within the
     * document, and removes any that are not used.
     *
     * @param \DOMDocument $domDocument the DOM document to clean
     *
     * @return string the SVG content with unused namespaces removed
     *
     * @throws XmlProcessingException if the XML content cannot be processed
     */
    private function cleanNamespaces(\DOMDocument $domDocument): string
    {
        $content = $this->process($domDocument, static fn (string $content): string => $content);

        $namespaceCounts = $this->countNamespaceElementsWithRegex($content);

        foreach ($namespaceCounts as $namespaceKey => $count) {
            if (0 === $count) {
                $this->removeNamespaceFromSvgTags($domDocument, $namespaceKey);
            }
        }

        return $this->process($domDocument, static fn (string $content): string => $content);
    }

    /**
     * Counts the usage of each declared namespace within the SVG content.
     *
     * It uses regular expressions to find all namespace declarations and then counts
     * how many times elements with each namespace prefix appear.
     *
     * @param string $content the raw SVG content
     *
     * @return array<string, int> a map of namespace attributes to their usage count
     */
    private function countNamespaceElementsWithRegex(string $content): array
    {
        $namespaceCounts = [];

        $namespacePattern = self::NAMESPACE_REGEX;

        $result = preg_match_all($namespacePattern, $content, $matches);
        if (false !== $result && $result > 0) {
            foreach ($matches[1] as $prefix) {
                $namespaceKey = \sprintf('%s:%s', SvgAttribute::Xmlns->value, $prefix);
                $elementPattern = \sprintf(self::ELEMENT_TEMPLATE_REGEX, preg_quote($prefix, '/'));
                preg_match_all($elementPattern, $content, $elementMatches);
                $namespaceCounts[$namespaceKey] = \count($elementMatches[0]);
            }
        }

        return $namespaceCounts;
    }

    /**
     * Removes a specific namespace attribute from the root SVG element.
     *
     * @param \DOMDocument $domDocument        the DOM document to modify
     * @param string       $namespaceAttribute The namespace attribute to remove (e.g., "xmlns:xlink").
     */
    private function removeNamespaceFromSvgTags(\DOMDocument $domDocument, string $namespaceAttribute): void
    {
        $root = $domDocument->documentElement;

        if ($root instanceof \DOMElement && $root->hasAttribute($namespaceAttribute)) {
            $root->removeAttribute($namespaceAttribute);
        }
    }
}
