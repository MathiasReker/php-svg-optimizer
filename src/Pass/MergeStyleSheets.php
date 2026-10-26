<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\TokenizerTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class MergeStyleSheets implements PassInterface
{
    use ElementTrait;
    use IdTrait;
    use StyleElementTrait;
    use TokenizerTrait;

    private const string LEADING_AT_RULE_REGEX = '/@(?:import|charset|namespace|\\\)/i';

    private const string MEDIA_QUERY_REGEX = '/^[\w\s(),:.<>=\/-]+$/';

    private const string MEDIA_RULE = '@media %s{%s}';

    private const string ALL_MEDIA = 'all';

    private const string ASCII_WHITESPACE_CHARACTERS = " \t\n\f\r";

    private const string BLOCK_END = '}';

    private const array MARKUP_DELIMITERS = ['<!--', '-->'];

    private const array ALLOWED_ATTRIBUTES = [Attribute::Type->value, Attribute::Media->value];

    private const array CONDITIONAL_ATTRIBUTES = [
        Attribute::RequiredExtensions->value,
        Attribute::RequiredFeatures->value,
        Attribute::SystemLanguage->value,
    ];

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        $styles = $this->elementsByLocalName($domDocument, Element::Style->value);

        if ([] !== $this->elementsByLocalName($domDocument, Element::Link->value)) {
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
            && $this->isPlainCssStyleElement($domElement)
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
            if (Element::Switch->value === $node->localName || $this->hasId($node) || $this->hasAnyAttribute($node, self::CONDITIONAL_ATTRIBUTES)) {
                return false;
            }
        }

        return true;
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

        $tokens = $this->tokenize($css);

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

        $contributors[0]->removeAttribute(Attribute::Media->value);
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
        $media = trim($domElement->getAttribute(Attribute::Media->value), self::ASCII_WHITESPACE_CHARACTERS);

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
            } elseif (1 === preg_match(self::MEDIA_QUERY_REGEX, $media[$index]) && false !== $this->tokenize($media[$index])) {
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
