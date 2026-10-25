<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom;

use MathiasReker\PhpSvgOptimizer\Svg\Element;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait ScriptingTrait
{
    use AnimationTrait;
    use AsciiCaseTrait;

    private const string EVENT_HANDLER_PREFIX = 'on';

    /**
     * @param \DOMDocument $domDocument the document to check
     *
     * @return bool true if the document has a `<script>` or an event attribute
     */
    private function hasScriptOrEventHandler(\DOMDocument $domDocument): bool
    {
        foreach ($domDocument->getElementsByTagName('*') as $element) {
            if (Element::Script->value === $element->localName) {
                return true;
            }

            foreach ($element->attributes as $attribute) {
                if ($this->isEventHandlerName((string) $attribute->localName)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param string $name the local name of an attribute
     *
     * @return bool true if the attribute name is the name of an event handler, in any case
     */
    private function isEventHandlerName(string $name): bool
    {
        return str_starts_with($this->asciiLowercase($name), self::EVENT_HANDLER_PREFIX);
    }

    /**
     * @param \DOMDocument $domDocument the document to check
     * @param string       $attribute   the attribute name
     *
     * @return bool true if a script, an event handler or an animation may change the attribute
     */
    private function mayChangeAttributeAtRuntime(\DOMDocument $domDocument, string $attribute): bool
    {
        return $this->hasScriptOrEventHandler($domDocument)
            || \array_key_exists($attribute, $this->animatedAttributeNames($domDocument));
    }
}
