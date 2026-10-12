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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\IdReferenceTrait;

/**
 * @no-named-arguments
 */
final readonly class RemoveUnusedMasks implements SvgOptimizerRuleInterface
{
    use ElementTrait;
    use IdReferenceTrait;

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
        $referencedIds = $this->collectReferencedIds($domDocument);

        $this->removeUnusedMasks($domDocument, $referencedIds);
        $this->removeEmptyDefs($domDocument);
    }

    /**
     * @param \DOMDocument        $domDocument   the DOM document to modify
     * @param array<string, true> $referencedIds the ids referenced anywhere in the document
     */
    private function removeUnusedMasks(\DOMDocument $domDocument, array $referencedIds): void
    {
        $masks = iterator_to_array($domDocument->getElementsByTagName(SvgTag::Mask->value), false);

        foreach ($masks as $mask) {
            $maskId = $mask->getAttribute(SvgAttribute::Id->value);

            if ('' === $maskId || !\array_key_exists($maskId, $referencedIds)) {
                $mask->parentNode?->removeChild($mask);
            }
        }
    }
}
