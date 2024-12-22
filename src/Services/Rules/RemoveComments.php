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

final class RemoveComments implements SvgOptimizerRuleInterface
{
    /**
     * Remove all comments from the SVG document.
     *
     * This method locates all comment nodes in the SVG document and removes them.
     * It uses XPath to query for comment nodes and then removes each one from its parent node.
     *
     * @param \DOMDocument $domDocument The DOMDocument instance representing the SVG file to be optimized
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);

        /**
         * @var \DOMNodeList<\DOMComment> $comments
         */
        $comments = $domXPath->query('//comment()');

        foreach ($comments as $comment) {
            // @var \DOMComment $comment
            $comment->parentNode?->removeChild($comment);
        }
    }
}
