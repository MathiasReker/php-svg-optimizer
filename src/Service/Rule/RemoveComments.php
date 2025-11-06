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
final readonly class RemoveComments implements SvgOptimizerRuleInterface
{
    /**
     * Remove all comments from the SVG document.
     *
     * This method locates all comment nodes in the SVG document and removes them.
     * It uses XPath to query for comment nodes and then removes each one from its parent node.
     *
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);

        /** @var \DOMNodeList<\DOMComment> $comments */
        $comments = $domXPath->query('//comment()');

        foreach ($comments as $comment) {
            if (str_starts_with((string) $comment->nodeValue, '!')) {
                continue; // Skip legal/license comments
            }

            $comment->parentNode?->removeChild($comment);
        }
    }

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return false;
    }
}
