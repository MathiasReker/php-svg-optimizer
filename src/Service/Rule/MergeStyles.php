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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\StyleSheetTokenTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class MergeStyles implements SvgOptimizerRuleInterface
{
    use ElementTrait;
    use StyleSheetTokenTrait;

    private const string LEADING_AT_RULE_REGEX = '/@(?:import|charset|namespace|\\\)/i';

    private const string MEDIA_QUERY_REGEX = '/^[\w\s(),:.<>=\/-]+$/';

    private const string MEDIA_RULE = '@media %s{%s}';

    private const string ALL_MEDIA = 'all';

    private const string ASCII_WHITESPACE_CHARACTERS = " \t\n\f\r";

    private const string BLOCK_END = '}';

    private const array MARKUP_DELIMITERS = ['<!--', '-->'];

    private const array ALLOWED_ATTRIBUTES = [SvgAttribute::Type->value, SvgAttribute::Media->value];

    private const array CONDITIONAL_ATTRIBUTES = [
        SvgAttribute::RequiredExtensions->value,
        SvgAttribute::RequiredFeatures->value,
        SvgAttribute::SystemLanguage->value,
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
        $styles = $this->elementsNamed($domDocument, SvgTag::Style->value);

        if ([] !== $this->elementsNamed($domDocument, SvgTag::Link->value)) {
            return;
        }

        foreach ($styles as $style) {
            if (!$this->isMergeableStyle($style)) {
                return;
            }
        }

        $contributors = array_values(array_filter(
            $styles,
            static fn (\DOMElement $domElement): bool => '' !== trim($domElement->textContent),
        ));

        if ($this->mergeInto($contributors)) {
            $this->removeOthers($styles, \array_slice($contributors, 0, 1));
        }
    }

    /**
     * @param \DOMElement $domElement a `<style>` element
     *
     * @return bool true if the element holds a complete CSS style sheet that applies to the whole document
     */
    private function isMergeableStyle(\DOMElement $domElement): bool
    {
        return !$this->isForeignContent($domElement)
            && $this->isCssTextStyle($domElement)
            && $this->hasAllowedAttributesOnly($domElement)
            && $this->hasUnconditionalAncestors($domElement)
            && $this->isCompleteStyleSheet($domElement->textContent);
    }

    /**
     * @param \DOMElement $domElement a `<style>` element
     */
    private function hasAllowedAttributesOnly(\DOMElement $domElement): bool
    {
        foreach ($domElement->attributes as $attribute) {
            if (!\in_array($attribute->nodeName, self::ALLOWED_ATTRIBUTES, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \DOMElement $domElement a `<style>` element
     *
     * @return bool true if no ancestor below the root can be referenced, switched or excluded by conditional processing
     */
    private function hasUnconditionalAncestors(\DOMElement $domElement): bool
    {
        for ($node = $domElement->parentNode; $node instanceof \DOMElement && $node->parentNode instanceof \DOMElement; $node = $node->parentNode) {
            if (SvgTag::Switch->value === $node->localName || $this->hasId($node) || $this->hasConditionalAttribute($node)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \DOMElement $domElement the element to check
     */
    private function hasConditionalAttribute(\DOMElement $domElement): bool
    {
        foreach (self::CONDITIONAL_ATTRIBUTES as $attribute) {
            if ($domElement->hasAttribute($attribute)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $css the content of a `<style>` element
     *
     * @return bool true if the style sheet can be followed by another style sheet without changing either
     */
    private function isCompleteStyleSheet(string $css): bool
    {
        if (1 === preg_match(self::LEADING_AT_RULE_REGEX, $css)) {
            return false;
        }

        $tokens = $this->tokenizeStyleSheet($css);

        if (false === $tokens) {
            return false;
        }

        $significant = array_filter(
            $tokens,
            static fn (array $token): bool => !\in_array($token[0], [self::TOKEN_WHITESPACE, self::TOKEN_COMMENT], true),
        );

        return [] === $significant || [self::TOKEN_DELIM, self::BLOCK_END] === $significant[array_key_last($significant)];
    }

    /**
     * @param list<\DOMElement> $contributors the `<style>` elements with content, in document order
     *
     * @return bool true if the content of the elements can be held by the first of them
     */
    private function mergeInto(array $contributors): bool
    {
        if (\count($contributors) < 2) {
            return true;
        }

        $media = array_map($this->mediaOf(...), $contributors);

        if (1 === \count(array_unique($media))) {
            $contributors[0]->textContent = implode('', array_map(static fn (\DOMElement $domElement): string => trim($domElement->textContent), $contributors));

            return true;
        }

        $css = $this->wrapInMediaRules($contributors, $media);

        if ('' === $css) {
            return false;
        }

        $contributors[0]->removeAttribute(SvgAttribute::Media->value);
        $contributors[0]->textContent = $css;

        return true;
    }

    /**
     * @param \DOMElement $domElement a `<style>` element
     *
     * @return string the media query list of the element, or an empty string if it applies to all media
     */
    private function mediaOf(\DOMElement $domElement): string
    {
        $media = trim($domElement->getAttribute(SvgAttribute::Media->value), self::ASCII_WHITESPACE_CHARACTERS);

        return 0 === strcasecmp($media, self::ALL_MEDIA) ? '' : $media;
    }

    /**
     * @param list<\DOMElement> $contributors the `<style>` elements with content, in document order
     * @param list<string>      $media        the media query list of each element
     *
     * @return string the style sheets, each wrapped in a media rule for its media, or an empty string if a style sheet
     *                cannot be wrapped
     */
    private function wrapInMediaRules(array $contributors, array $media): string
    {
        $css = '';

        foreach ($contributors as $index => $contributor) {
            $content = trim($contributor->textContent);

            if (str_contains($content, self::MARKUP_DELIMITERS[0]) || str_contains($content, self::MARKUP_DELIMITERS[1])) {
                return '';
            }

            if ('' === $media[$index]) {
                $css .= $content;
            } elseif (1 === preg_match(self::MEDIA_QUERY_REGEX, $media[$index]) && false !== $this->tokenizeStyleSheet($media[$index])) {
                $css .= \sprintf(self::MEDIA_RULE, $media[$index], $content);
            } else {
                return '';
            }
        }

        return $css;
    }

    /**
     * @param list<\DOMElement> $styles the `<style>` elements of the document
     * @param list<\DOMElement> $keep   the element holding the merged style sheet, if any
     */
    private function removeOthers(array $styles, array $keep): void
    {
        foreach ($styles as $style) {
            if (!\in_array($style, $keep, true)) {
                $style->remove();
            }
        }
    }
}
