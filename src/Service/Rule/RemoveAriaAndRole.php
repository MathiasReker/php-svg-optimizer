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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveAriaAndRole implements PassInterface
{
    use ElementTrait;

    private const string ATTRIBUTE_SELECTOR_REGEX = '/\[\s*(?:[\w*-]*\|)?\s*(role|aria-[\w-]*)(?![\w-])/i';

    private const string ARIA_PREFIX = 'aria-';

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->hasExternalStyleSheet($domDocument)) {
            return;
        }

        $selected = $this->selectedAttributeNames($domDocument);

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            foreach (array_keys(iterator_to_array($domNodeList->attributes, true)) as $attributeName) {
                $name = mb_strtolower($attributeName);

                if ((SvgAttribute::Role->value === $name || str_starts_with($name, self::ARIA_PREFIX))
                    && !\array_key_exists($name, $selected)
                ) {
                    $domNodeList->removeAttribute($attributeName);
                }
            }
        }
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to read
     *
     * @return array<string, true> the lowercase role and ARIA attribute names used in attribute selectors of style sheets
     */
    private function selectedAttributeNames(\DOMDocument $domDocument): array
    {
        preg_match_all(self::ATTRIBUTE_SELECTOR_REGEX, $this->styleSheetText($domDocument), $matches);

        return array_fill_keys(array_map(strtolower(...), $matches[1]), true);
    }
}
