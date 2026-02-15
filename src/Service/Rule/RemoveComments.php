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
     * XPath query to select all comment nodes.
     */
    private const string XPATH_COMMENT_NODES = '//comment()';

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
     * Removes all comments from the SVG document, except for legal or license comments.
     *
     * This method uses an XPath query to find all comment nodes and then iterates
     * through them, removing each one unless it is identified as a legal or
     * license comment (typically starting with an exclamation mark).
     *
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);

        /** @var \DOMNodeList<\DOMComment> $domNodeList */
        $domNodeList = $domXPath->query(self::XPATH_COMMENT_NODES);

        foreach ($domNodeList as $domComment) {
            if (str_starts_with((string) $domComment->nodeValue, '!')) {
                continue;
            }

            $domComment->parentNode?->removeChild($domComment);
        }
    }
}
