<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveAriaAttributes implements PassInterface
{
    use StyleSheetTrait;

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

        foreach ($domDocument->getElementsByTagName('*') as $element) {
            foreach (array_keys(iterator_to_array($element->attributes, true)) as $attributeName) {
                $name = mb_strtolower($attributeName);

                if ((Attribute::Role->value === $name || str_starts_with($name, self::ARIA_PREFIX))
                    && !\array_key_exists($name, $selected)
                ) {
                    $element->removeAttribute($attributeName);
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
