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
final readonly class RemoveDataAttributes implements SvgOptimizerRuleInterface
{
    #[\Override]
    public static function isRisky(): bool
    {
        return false;
    }

    /**
     * Remove all 'data-*' attributes from SVG elements.
     *
     * @param \DOMDocument $domDocument The SVG \DOMDocument to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);

        /** @var \DOMNodeList<\DOMElement> $nodes */
        $nodes = $domXPath->query('//*');

        foreach ($nodes as $node) {
            /** @var list<\DOMAttr> $attrs */
            $attrs = iterator_to_array($node->attributes, false);
            foreach ($attrs as $attr) {
                if (0 === strcasecmp(mb_substr($attr->name, 0, 5), 'data-')) {
                    $node->removeAttribute($attr->name);
                }
            }
        }
    }

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return false;
    }
}
