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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveDataAttributes implements PassInterface
{
    use ElementTrait;

    private const string DATA_ATTRIBUTE_PREFIX = 'data-';

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        $css = $this->styleSheetText($domDocument);

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            foreach (array_keys(iterator_to_array($domNodeList->attributes, true)) as $name) {
                if (0 === strncasecmp($name, self::DATA_ATTRIBUTE_PREFIX, mb_strlen(self::DATA_ATTRIBUTE_PREFIX)) && !$this->isNamedIn($css, $name)) {
                    $domNodeList->removeAttribute($name);
                }
            }
        }
    }

    /**
     * @param string $css  the style sheets of the document
     * @param string $name the name of a data attribute
     *
     * @return bool true if the style sheets name the attribute, as in an attribute selector or an `attr()` function
     */
    private function isNamedIn(string $css, string $name): bool
    {
        return 1 === preg_match('/(?<![\w-])' . preg_quote($name, '/') . '(?![\w-])/iu', $css);
    }
}
