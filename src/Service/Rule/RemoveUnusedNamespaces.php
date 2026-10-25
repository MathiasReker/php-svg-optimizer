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
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveUnusedNamespaces implements SvgOptimizerRuleInterface
{
    private const string NAMESPACE_REGEX = '/xmlns:([a-zA-Z0-9\-]+)="([^"]+)"/';

    private const string PREFIXED_NAME_REGEX = '/([\w.\-]+):(?=[a-zA-Z0-9\-])/';

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
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $namespaceCounts = $this->countNamespaceElementsWithRegex((string) $domDocument->saveXML());

        foreach ($namespaceCounts as $namespaceKey => $count) {
            if (0 === $count) {
                $this->removeNamespaceFromSvgTags($domDocument, $namespaceKey);
            }
        }
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
        preg_match_all(self::PREFIXED_NAME_REGEX, $content, $prefixedNames);

        $usages = array_count_values($prefixedNames[1]);

        foreach ($matches[1] as $prefix) {
            $namespaceCounts[\sprintf('%s:%s', SvgAttribute::Xmlns->value, $prefix)] = $usages[$prefix] ?? 0;
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
