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
trait DocumentScriptTrait
{
    use AsciiCaseTrait;
    use ElementTrait;

    private const string EVENT_HANDLER_PREFIX = 'on';

    /**
     * @param \DOMDocument $domDocument the document to check
     *
     * @return bool true if the document has a `<script>` or an event attribute
     */
    private function hasScriptOrEventHandler(\DOMDocument $domDocument): bool
    {
        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            if (SvgTag::Script->value === $domNodeList->localName) {
                return true;
            }

            foreach ($domNodeList->attributes as $attribute) {
                if (str_starts_with($this->asciiLowercase((string) $attribute->localName), self::EVENT_HANDLER_PREFIX)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param \DOMDocument $domDocument the document to check
     * @param string       $attribute   the attribute name
     *
     * @return bool true if a script, an event handler or an animation may change the attribute
     */
    private function mayChangeAttribute(\DOMDocument $domDocument, string $attribute): bool
    {
        return $this->hasScriptOrEventHandler($domDocument)
            || \array_key_exists($attribute, $this->animatedAttributeNames($domDocument));
    }
}
