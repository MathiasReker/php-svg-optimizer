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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\IdReferenceTrait;

/**
 * Removes `id` and `xml:id` attributes that nothing in the document references. Documents with a `<script>` or an
 * event attribute are left untouched, since scripts may look elements up by id. The ids of the root element, of
 * `<symbol>` elements (sprite icons) and of `<view>` elements are kept, as they are typically targeted from outside the
 * document. Risky, because a page's style sheets, scripts or fragment links may still target the removed ids.
 *
 * @no-named-arguments
 */
final readonly class RemoveUnusedIds implements SvgOptimizerRuleInterface
{
    use ElementTrait;
    use IdReferenceTrait;

    private const string EVENT_ATTRIBUTE_PREFIX = 'on';

    /**
     * Elements whose id exists to be targeted from outside the document.
     */
    private const array EXTERNALLY_TARGETED_TAGS = ['symbol', 'view'];

    /**
     * Elements that only render when referenced, so references made from inside them only count when they are used.
     */
    private const array DEFINITION_TAGS = [
        'clipPath', 'filter', 'linearGradient', 'marker', 'mask', 'pattern', 'radialGradient', 'symbol',
    ];

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
        if ($this->isScripted($domDocument)) {
            return;
        }

        $referencedIds = $this->collectLiveReferencedIds($domDocument, self::DEFINITION_TAGS, self::EXTERNALLY_TARGETED_TAGS);

        foreach ($domDocument->getElementsByTagName('*') as $domElement) {
            if ($domElement === $domDocument->documentElement
                || \in_array($domElement->localName, self::EXTERNALLY_TARGETED_TAGS, true)) {
                continue;
            }

            if (!\array_key_exists($domElement->getAttribute(SvgAttribute::Id->value), $referencedIds)) {
                $domElement->removeAttribute(SvgAttribute::Id->value);
            }

            if (!\array_key_exists($domElement->getAttributeNS(SvgNamespace::Xml->value, SvgAttribute::Id->value), $referencedIds)) {
                $domElement->removeAttributeNS(SvgNamespace::Xml->value, SvgAttribute::Id->value);
            }
        }
    }

    /**
     * @param \DOMDocument $domDocument the document to check
     *
     * @return bool true if the document has a `<script>` or an event attribute
     */
    private function isScripted(\DOMDocument $domDocument): bool
    {
        foreach ($domDocument->getElementsByTagName('*') as $domElement) {
            if (SvgTag::Script->value === $domElement->localName) {
                return true;
            }

            foreach ($domElement->attributes as $attribute) {
                if (str_starts_with(mb_strtolower((string) $attribute->localName), self::EVENT_ATTRIBUTE_PREFIX)) {
                    return true;
                }
            }
        }

        return false;
    }
}
