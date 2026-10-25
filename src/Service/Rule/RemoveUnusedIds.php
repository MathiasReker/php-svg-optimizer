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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\DefinitionElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\FragmentTargetTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\IdReferenceTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveUnusedIds implements SvgOptimizerRuleInterface
{
    use DefinitionElementTrait;
    use ElementTrait;
    use FragmentTargetTrait;
    use IdReferenceTrait;

    private const array EXTERNALLY_TARGETED_TAGS = ['symbol', 'view'];

    #[\Override]
    public static function isRisky(): bool
    {
        return true;
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
        if ($this->mayTargetAnyId($domDocument)) {
            return;
        }

        $referencedIds = $this->collectLiveReferencedIds($domDocument, self::REFERENCE_ONLY_TAGS, self::EXTERNALLY_TARGETED_TAGS, $this->collectFragmentTargets($domDocument));

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            if ($domNodeList === $domDocument->documentElement) {
                continue;
            }

            if (!\array_key_exists($domNodeList->getAttribute(SvgAttribute::Id->value), $referencedIds)) {
                $domNodeList->removeAttribute(SvgAttribute::Id->value);
            }

            if (!\array_key_exists($domNodeList->getAttributeNS(SvgNamespace::Xml->value, SvgAttribute::Id->value), $referencedIds)) {
                $domNodeList->removeAttributeNS(SvgNamespace::Xml->value, SvgAttribute::Id->value);
            }
        }
    }
}
