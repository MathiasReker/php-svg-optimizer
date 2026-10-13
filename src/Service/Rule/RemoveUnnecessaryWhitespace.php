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

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveUnnecessaryWhitespace implements SvgOptimizerRuleInterface
{
    private const string WHITESPACE_REGEX = '/\s+/';

    private const string CSS_STRING_REGEX = '/("(?:[^"\\\]|\\\.)*"|\'(?:[^\'\\\]|\\\.)*\')/';

    private const string CSS_SEPARATOR_WHITESPACE_REGEX = '/\s*([;:,])\s*/';

    /**
     * @param string $style the style attribute value
     */
    private function minifyStyle(string $style): string
    {
        $parts = preg_split(self::CSS_STRING_REGEX, $style, -1, \PREG_SPLIT_DELIM_CAPTURE);

        if (false === $parts) {
            return $style;
        }

        foreach ($parts as $index => $part) {
            if (0 === $index % 2) {
                $part = preg_replace(self::WHITESPACE_REGEX, ' ', $part) ?? $part;
                $parts[$index] = preg_replace(self::CSS_SEPARATOR_WHITESPACE_REGEX, '$1', $part) ?? $part;
            }
        }

        return rtrim(trim(implode('', $parts)), ';');
    }

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
        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            foreach (iterator_to_array($domNodeList->attributes, false) as $attribute) {
                $value = SvgAttribute::Style->value === $attribute->nodeName
                    ? $this->minifyStyle($attribute->value)
                    : preg_replace(self::WHITESPACE_REGEX, ' ', trim($attribute->value)) ?? $attribute->value;

                if ($value !== $attribute->value) {
                    $domNodeList->setAttributeNS($attribute->namespaceURI, $attribute->nodeName, $value);
                }
            }
        }
    }
}
