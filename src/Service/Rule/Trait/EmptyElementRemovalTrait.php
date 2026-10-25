<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Trait;

use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait EmptyElementRemovalTrait
{
    use ElementTrait;

    private const string FILTER_CSS_REGEX = '/filter|\\\/i';

    /**
     * @param \DOMDocument                $domDocument the document to clean
     * @param callable(\DOMElement): bool $isRemovable returns true for elements to remove
     */
    private function removeElementsBottomUp(\DOMDocument $domDocument, callable $isRemovable): void
    {
        if ($this->hasSiblingSensitiveStyle($domDocument) || 1 === preg_match(self::FILTER_CSS_REGEX, $this->styleSheetText($domDocument))) {
            return;
        }

        $elements = array_reverse(iterator_to_array($domDocument->getElementsByTagName('*'), false));

        foreach ($elements as $element) {
            $parent = $element->parentNode;

            if ($parent instanceof \DOMElement && SvgTag::Switch->value !== $parent->localName && $isRemovable($element)) {
                $parent->removeChild($element);
            }
        }
    }
}
