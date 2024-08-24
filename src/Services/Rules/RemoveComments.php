<?php
/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Services\Rules;

class RemoveComments implements SvgOptimizerRuleInterface
{
    /**
     * Remove all comments from the SVG document.
     *
     * @param \DOMDocument $domDocument the DOMDocument instance representing the SVG file to be optimized
     */
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);

        /**
         * @var \DOMNodeList<\DOMComment> $comments
         */
        $comments = $domXPath->query('//comment()');

        /**
         * @var \DOMComment $comment
         */
        foreach ($comments as $comment) {
            $parentNode = $comment->parentNode;
            $parentNode?->removeChild($comment);
        }
    }
}
