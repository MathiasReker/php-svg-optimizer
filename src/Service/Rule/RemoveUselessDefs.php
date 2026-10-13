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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveUselessDefs implements SvgOptimizerRuleInterface
{
    use ElementTrait;

    private const array REFERENCE_ONLY_TAGS = [
        'clipPath', 'filter', 'linearGradient', 'marker', 'mask', 'pattern', 'radialGradient', 'symbol',
    ];

    private const array DEFS_EFFECTIVE_TAGS = [
        'color-profile', 'font', 'font-face', 'script', 'style',
    ];

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
        foreach (iterator_to_array($domDocument->getElementsByTagName('*'), false) as $domElement) {
            if ($this->isUselessDefinition($domElement)) {
                $domElement->parentNode?->removeChild($domElement);
            }
        }

        $this->removeEmptyDefs($domDocument);
    }

    /**
     * @param \DOMElement $domElement the element to check
     */
    private function isUselessDefinition(\DOMElement $domElement): bool
    {
        $localName = (string) $domElement->localName;
        $parent = $domElement->parentNode;
        $isDefsChild = $parent instanceof \DOMElement && SvgTag::Defs->value === $parent->localName;

        $isCandidate = \in_array($localName, self::REFERENCE_ONLY_TAGS, true)
            || ($isDefsChild && !\in_array($localName, self::DEFS_EFFECTIVE_TAGS, true));

        return $isCandidate && !$this->hasIdInSubtree($domElement);
    }
}
