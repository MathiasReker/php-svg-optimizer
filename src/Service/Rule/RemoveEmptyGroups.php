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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\EmptyElementRemovalTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveEmptyGroups implements SvgOptimizerRuleInterface
{
    use EmptyElementRemovalTrait;

    #[\Override]
    public static function isRisky(): bool
    {
        return false;
    }

    #[\Override]
    public static function shouldCheckSize(): bool
    {
        return true;
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $this->removeElementsBottomUp(
            $domDocument,
            fn (\DOMElement $domElement): bool => SvgTag::G->value === $domElement->localName
                && $this->isSvgElement($domElement)
                && $this->isEmptyGroup($domElement),
        );
    }

    /**
     * @param \DOMElement $domElement the group element to check
     *
     * @return bool true if the group is empty, false otherwise
     */
    private function isEmptyGroup(\DOMElement $domElement): bool
    {
        if ($domElement->attributes->length > 0) {
            return false;
        }

        foreach ($domElement->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                return false;
            }

            if ($child instanceof \DOMText && '' !== trim($child->wholeText)) {
                return false;
            }

            if ($child instanceof \DOMComment) {
                return false;
            }
        }

        return true;
    }
}
