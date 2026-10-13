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
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveUnusedIds implements SvgOptimizerRuleInterface
{
    use ElementTrait;
    use IdReferenceTrait;

    private const string EVENT_ATTRIBUTE_PREFIX = 'on';

    private const array EXTERNALLY_TARGETED_TAGS = ['symbol', 'view'];

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
        if ($this->mayTargetAnyId($domDocument)) {
            return;
        }

        $referencedIds = $this->collectLiveReferencedIds($domDocument, self::DEFINITION_TAGS, self::EXTERNALLY_TARGETED_TAGS);

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            if ($domNodeList === $domDocument->documentElement
                || \in_array($domNodeList->localName, self::EXTERNALLY_TARGETED_TAGS, true)) {
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

    /**
     * @param \DOMDocument $domDocument the document to check
     *
     * @return bool true if the document has a script, or a style sheet with an attribute selector that may match ids
     *              other than its value
     */
    private function mayTargetAnyId(\DOMDocument $domDocument): bool
    {
        foreach ($this->elementsNamed($domDocument, SvgTag::Style->value) as $domElement) {
            if ($this->hasInexactIdAttributeSelector($domElement->textContent)) {
                return true;
            }
        }

        return $this->isScripted($domDocument);
    }

    /**
     * @param \DOMDocument $domDocument the document to check
     *
     * @return bool true if the document has a `<script>` or an event attribute
     */
    private function isScripted(\DOMDocument $domDocument): bool
    {
        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            if (SvgTag::Script->value === $domNodeList->localName) {
                return true;
            }

            foreach ($domNodeList->attributes as $attribute) {
                if (str_starts_with(mb_strtolower((string) $attribute->localName), self::EVENT_ATTRIBUTE_PREFIX)) {
                    return true;
                }
            }
        }

        return false;
    }
}
