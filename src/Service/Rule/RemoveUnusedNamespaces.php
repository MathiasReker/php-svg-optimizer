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
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveUnusedNamespaces extends AbstractXmlProcessor implements SvgOptimizerRuleInterface
{
    private const string NAMESPACE_REGEX = '/xmlns:([a-zA-Z0-9\-]+)="([^"]+)"/';

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
     * @param string $content the raw SVG content
     *
     * @return array<string, int> a map of namespace attributes to their usage count
     */
    private function countNamespaceElementsWithRegex(string $content): array
    {
        $namespaceCounts = [];

        preg_match_all(self::NAMESPACE_REGEX, $content, $matches);

        // The prefix pattern only allows characters that are literal in a regex.
        foreach ($matches[1] as $prefix) {
            $namespaceKey = \sprintf('%s:%s', SvgAttribute::Xmlns->value, $prefix);
            preg_match_all(\sprintf(self::ELEMENT_TEMPLATE_REGEX, $prefix), $content, $elementMatches);
            $namespaceCounts[$namespaceKey] = \count($elementMatches[0]);
        }

        return $namespaceCounts;
    }

    /**
     * @param \DOMDocument $domDocument        the DOM document to modify
     * @param string       $namespaceAttribute The namespace attribute to remove (e.g., "xmlns:xlink").
     */
    private function removeNamespaceFromSvgTags(\DOMDocument $domDocument, string $namespaceAttribute): void
    {
        $root = $domDocument->documentElement;

        if ($root instanceof \DOMElement) {
            $root->removeAttribute($namespaceAttribute);
        }
    }
}
