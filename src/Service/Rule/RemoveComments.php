<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Pass\PassInterface;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveComments implements PassInterface
{
    private const string XPATH_COMMENT_NODES = '//comment()';

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);

        /** @var \DOMNodeList<\DOMComment> $domNodeList */
        $domNodeList = $domXPath->query(self::XPATH_COMMENT_NODES, null, false);

        foreach ($domNodeList as $domComment) {
            if (str_starts_with((string) $domComment->nodeValue, '!')) {
                continue;
            }

            $domComment->remove();
        }
    }
}
