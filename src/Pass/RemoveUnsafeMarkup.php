<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveUnsafeMarkup implements PassInterface
{
    use ElementTrait;

    private const string UNSAFE_COMMENT_REGEX = '~^-?>|<[/\w!?]~';

    /**
     * @param \DOMDocument $domDocument the DOM document to sanitize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        foreach ([$domDocument, ...$this->elementsByQualifiedName($domDocument, '*')] as $domNode) {
            foreach ([...$domNode->childNodes] as $child) {
                if ($child instanceof \DOMProcessingInstruction || ($child instanceof \DOMComment && 0 !== preg_match(self::UNSAFE_COMMENT_REGEX, $child->data))) {
                    $domNode->removeChild($child);
                }
            }
        }
    }
}
