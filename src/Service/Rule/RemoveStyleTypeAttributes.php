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

/**
 * Removes the type attribute from style and script elements when it holds the default type
 * (text/css for style, a JavaScript MIME type for script).
 *
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveStyleTypeAttributes implements SvgOptimizerRuleInterface
{
    private const string XPATH_TYPED_ELEMENTS = '//*[local-name()="%s" or local-name()="%s"][@%s]';

    private const string XHTML_NAMESPACE = 'http://www.w3.org/1999/xhtml';

    private const string ASCII_WHITESPACE = " \t\n\f\r";

    private const array STYLE_TYPES = [
        'text/css',
    ];

    private const array SCRIPT_TYPES = [
        'application/ecmascript',
        'application/javascript',
        'application/x-ecmascript',
        'application/x-javascript',
        'text/ecmascript',
        'text/javascript',
        'text/javascript1.0',
        'text/javascript1.1',
        'text/javascript1.2',
        'text/javascript1.3',
        'text/javascript1.4',
        'text/javascript1.5',
        'text/jscript',
        'text/livescript',
        'text/x-ecmascript',
        'text/x-javascript',
    ];

    #[\Override]
    public static function isRisky(): bool
    {
        return false;
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
        $domXPath = new \DOMXPath($domDocument);
        $query = \sprintf(
            self::XPATH_TYPED_ELEMENTS,
            SvgTag::Style->value,
            SvgTag::Script->value,
            SvgAttribute::Type->value,
        );

        /** @var \DOMNodeList<\DOMElement> $domNodeList */
        $domNodeList = $domXPath->query($query);

        foreach ($domNodeList as $domElement) {
            if (!\in_array($domElement->namespaceURI, [null, SvgNamespace::Svg->value, self::XHTML_NAMESPACE], true)) {
                continue;
            }

            $defaultTypes = SvgTag::Style->value === $domElement->localName ? self::STYLE_TYPES : self::SCRIPT_TYPES;
            $type = trim($domElement->getAttribute(SvgAttribute::Type->value), self::ASCII_WHITESPACE);

            if ($this->isDefaultType($type, $defaultTypes)) {
                $domElement->removeAttribute(SvgAttribute::Type->value);
            }
        }
    }

    /**
     * Compares ASCII case-insensitively, like browsers do for MIME types.
     *
     * @param string       $type         the trimmed type attribute value
     * @param list<string> $defaultTypes the default types of the element
     *
     * @return bool true if the type is one of the default types
     */
    private function isDefaultType(string $type, array $defaultTypes): bool
    {
        foreach ($defaultTypes as $defaultType) {
            if (0 === strcasecmp($type, $defaultType)) {
                return true;
            }
        }

        return false;
    }
}
