<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Trait;

use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait Svg2ElementMigrationTrait
{
    use ElementTrait;
    use RemoveElementsByTagNameTrait;

    private const array SVG2_REMOVED_ELEMENTS = [
        'altGlyph', 'altGlyphDef', 'altGlyphItem', 'animateColor', 'animation', 'audio', 'color-profile', 'cursor',
        'definition-src', 'font', 'font-face', 'font-face-format', 'font-face-name', 'font-face-src', 'font-face-uri',
        'glyph', 'glyphRef', 'handler', 'hkern', 'listener', 'missing-glyph', 'prefetch', 'solidColor', 'tbreak',
        'textArea', 'tref', 'video', 'vkern',
    ];

    /**
     * @param \DOMDocument $domDocument the DOM document to modify
     *
     * @return array<string, string> the ids of the removed elements, by themselves
     */
    private function removeObsoleteElements(\DOMDocument $domDocument): array
    {
        $idCounts = [];

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            $id = $domNodeList->getAttribute(SvgAttribute::Id->value);
            $idCounts[$id] = ($idCounts[$id] ?? 0) + 1;
        }

        $removedIds = [];

        foreach (self::SVG2_REMOVED_ELEMENTS as $localName) {
            foreach ($this->elementsNamed($domDocument, $localName) as $domNodeList) {
                $id = $domNodeList->getAttribute(SvgAttribute::Id->value);

                if (('' === $id || 1 === $idCounts[$id])
                    && $this->isRemovableUnrenderedElement($domNodeList, self::SVG2_REMOVED_ELEMENTS)
                ) {
                    $removedIds[$id] = $id;
                    $domNodeList->remove();
                }
            }
        }

        return $removedIds;
    }
}
