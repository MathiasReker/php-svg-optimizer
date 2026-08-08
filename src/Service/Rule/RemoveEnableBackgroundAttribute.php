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
 * @no-named-arguments
 */
final readonly class RemoveEnableBackgroundAttribute implements SvgOptimizerRuleInterface
{
    /**
     * @see https://regex101.com/r/p9gXyH/1
     */
    private const string ENABLE_BACKGROUND_REGEX = '/^new\s0\s0\s([-+]?\d*\.?\d+([eE][-+]?\d+)?)\s([-+]?\d*\.?\d+([eE][-+]?\d+)?)$/';

    /**
     * @see https://regex101.com/r/s5vF6g/1
     */
    private const string STYLE_REGEX = '/\s*enable-background\s*:\s*[^;]+;\s*/i';

    private const string ENABLE_BACKGROUND_QUERY = '//*[@enable-background]';

    private const string STYLE_QUERY = '//*[@style]';

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
        $domXPath = new \DOMXPath($domDocument);

        $this->handleEnableBackgroundAttribute($domXPath);
        $this->handleStyleAttribute($domXPath);
    }

    /**
     * @param \DOMXPath $domXPath the XPath object for querying the document
     */
    private function handleEnableBackgroundAttribute(\DOMXPath $domXPath): void
    {
        /** @var \DOMNodeList<\DOMElement> $elements */
        $elements = $domXPath->query(self::ENABLE_BACKGROUND_QUERY);
        foreach ($elements as $element) {
            $width = $element->getAttribute(SvgAttribute::Width->value);
            $height = $element->getAttribute(SvgAttribute::Height->value);
            $value = trim($element->getAttribute(SvgAttribute::EnableBackground->value));

            if (1 === preg_match(self::ENABLE_BACKGROUND_REGEX, $value, $matches)
                && $matches[1] === $width && $matches[3] === $height) {
                $element->removeAttribute(SvgAttribute::EnableBackground->value);
            } else {
                $element->setAttribute(SvgAttribute::EnableBackground->value, $value);
            }
        }
    }

    /**
     * @param \DOMXPath $domXPath the XPath object for querying the document
     */
    private function handleStyleAttribute(\DOMXPath $domXPath): void
    {
        /** @var \DOMNodeList<\DOMElement> $elements */
        $elements = $domXPath->query(self::STYLE_QUERY);
        foreach ($elements as $element) {
            $style = $element->getAttribute(SvgAttribute::Style->value);
            if (str_contains($style, SvgAttribute::EnableBackground->value)) {
                $cleaned = preg_replace(self::STYLE_REGEX, '', $style) ?? '';
                if ('' === trim($cleaned)) {
                    $element->removeAttribute(SvgAttribute::Style->value);
                } else {
                    $element->setAttribute(SvgAttribute::Style->value, $cleaned);
                }
            }
        }
    }
}
