<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\SelectorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\FragmentTargetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdRenameTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;

/**
 * @internal
 */
final readonly class PrefixIdsAndClasses implements PassInterface
{
    use FragmentTargetTrait;
    use IdRenameTrait;
    use SelectorTrait;
    use StyleElementTrait;

    private const string CSS_RULE_REGEX = '/(?<selector>[^{]+?){(?<body>[^}]+)}/';

    private const string FUNCTIONAL_ARGUMENT_REGEX = '/\([^()]*\)/';

    private const string CLASS_SELECTOR_REGEX = '/\[[^\]]*\](*SKIP)(*FAIL)|\.((?:[\w\-]|[^\x00-\x7F])++)(?=[\s.#:\[>+~,()]|$)/';

    private const string ID_SELECTOR_REGEX = '/\[[^\]]*\](*SKIP)(*FAIL)|#\K(?:[\w\-]|[^\x00-\x7F])++(?=[\s.#:\[>+~,()]|$)/';

    private const string URL_REFERENCE_REGEX = '/url\(\s*([\'"]?)#([^\s\'")]+)\1\s*\)/';

    private const string WHITESPACE_REGEX = '/\s+/';

    private const string BLANK_BETWEEN_TAGS_REGEX = '/>[\t\n\r ]+</';

    private const string UNSCOPABLE_CSS_REGEX = '/\{[^}]*\{|@import|\\\|\[\s*(?:[^\]|=]*\|)?(?:id|class)\s*[\]~|^$*=]/i';

    private const string ESCAPED_URL_REFERENCE_QUERY = '//@*[contains(., "url(") and contains(., "\")]';

    private const string SVG_QUERY = '//*[local-name()="svg"]';

    private const string ID_QUERY = './/*[@id]';

    private const string CLASS_QUERY = './/*[@class]';

    private const string STYLE_QUERY = './/*[local-name()="style"]';

    private const string URL_REFERENCE_QUERY = './/@*[contains(., "url(")] | .//*[local-name()="style"][contains(., "url(")]';

    private const string SCOPED_NAME_REGEX = '/-[0-9a-f]{10}(?:-\d+)?$/';

    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);

        if ($this->hasUnscopableStyleSheet($domDocument) || $this->hasEscapedUrlReference($domXPath)) {
            return;
        }

        /** @var \DOMNodeList<\DOMElement> $svgs */
        $svgs = $domXPath->query(self::SVG_QUERY, null, false);
        $multiple = $svgs->length > 1;
        $fragmentTargets = $this->fragmentTargetIds($domDocument);

        foreach ($svgs as $index => $node) {
            $baseHash = mb_substr(sha1((string) preg_replace(self::BLANK_BETWEEN_TAGS_REGEX, '><', (string) $domDocument->saveXML($node))), 0, 10);
            $hash = $multiple ? $baseHash . '-' . $index : $baseHash;

            $this->scopeSingleSvg($domXPath, $node, $hash, $fragmentTargets);
        }
    }

    /**
     * @param \DOMDocument $domDocument the document to check
     *
     * @return bool true if a style element holds other content than CSS text, or CSS whose rules cannot be rewritten
     */
    private function hasUnscopableStyleSheet(\DOMDocument $domDocument): bool
    {
        foreach ($domDocument->getElementsByTagName(Element::Style->value) as $element) {
            if (!$this->isPlainCssStyleElement($element) || 1 === preg_match(self::UNSCOPABLE_CSS_REGEX, $element->textContent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMXPath $domXPath the XPath object for querying the document
     *
     * @return bool true if an attribute references an id through a url() with an escape, such as `url(#\61)`, which
     *              cannot be rewritten
     */
    private function hasEscapedUrlReference(\DOMXPath $domXPath): bool
    {
        /** @var \DOMNodeList<\DOMAttr> $attributes */
        $attributes = $domXPath->query(self::ESCAPED_URL_REFERENCE_QUERY, null, false);

        return $attributes->length > 0;
    }

    /**
     * @param array<string, true> $fragmentTargets the ids that another document may address, which keep their value
     */
    private function scopeSingleSvg(\DOMXPath $domXPath, \DOMElement $domElement, string $hash, array $fragmentTargets): void
    {
        $idReplacements = $this->scopeIds($domXPath, $domElement, $hash, $fragmentTargets);
        $classReplacements = $this->processStyleBlocks($domXPath, $domElement, $hash, $fragmentTargets);

        $this->updateClassAttributes($domXPath, $domElement, $classReplacements);
        $this->updateUrlReferences($domXPath, $domElement, $idReplacements);
        $this->renameIdReferences($domXPath, $domElement, $idReplacements);
    }

    /**
     * @param array<string, true> $fragmentTargets the ids that keep their value
     *
     * @return array<string, string> a map of original to new IDs
     */
    private function scopeIds(\DOMXPath $domXPath, \DOMElement $domElement, string $hash, array $fragmentTargets): array
    {
        $result = [];

        /** @var \DOMNodeList<\DOMElement> $nodes */
        $nodes = $domXPath->query(self::ID_QUERY, $domElement, false);

        foreach ($nodes as $node) {
            $original = $node->getAttribute(Attribute::Id->value);
            if ('' === $original || $this->isScoped($original) || \array_key_exists($original, $fragmentTargets)) {
                continue;
            }

            $new = $original . '-' . $hash;
            $node->setAttribute(Attribute::Id->value, $new);
            $result[$original] = $new;
        }

        return $result;
    }

    /**
     * @param array<string, true> $fragmentTargets the ids that keep their value
     *
     * @return array<string, string> a map of original to new class names
     */
    private function processStyleBlocks(\DOMXPath $domXPath, \DOMElement $domElement, string $hash, array $fragmentTargets): array
    {
        $classReplacements = [];

        /** @var \DOMNodeList<\DOMElement> $styles */
        $styles = $domXPath->query(self::STYLE_QUERY, $domElement, false);

        foreach ($styles as $style) {
            $css = trim($style->textContent);
            if ('' === $css) {
                continue;
            }

            [$processedCss, $newClassReplacements] = $this->processCssRules($css, $hash, $fragmentTargets);

            $classReplacements = [...$classReplacements, ...$newClassReplacements];

            if ('' === $processedCss) {
                $style->remove();

                continue;
            }

            $style->textContent = $processedCss;
        }

        return $classReplacements;
    }

    /**
     * @param array<string, true> $fragmentTargets the ids that keep their value
     *
     * @return array{string, array<string, string>}
     */
    private function processCssRules(string $css, string $hash, array $fragmentTargets): array
    {
        $newClassReplacements = [];

        $result = preg_replace_callback(
            self::CSS_RULE_REGEX,
            function (array $match) use ($hash, $fragmentTargets, &$newClassReplacements): string {
                $body = $match['body'];

                $selectors = array_filter(
                    $this->splitSelectorList($match['selector']),
                    fn (string $single): bool => !$this->isUnsafeSelector($single),
                );

                if ([] === $selectors) {
                    return '';
                }

                $scopedSelectors = [];
                foreach ($selectors as $selector) {
                    [$selector, $scopedClasses] = $this->scopeClasses($selector, $hash);
                    $newClassReplacements = [...$newClassReplacements, ...$scopedClasses];

                    $scopedSelectors[] = $this->scopeSelectorIds($selector, $hash, $fragmentTargets);
                }

                return \sprintf('%s{%s}', implode(',', $scopedSelectors), $body);
            },
            $css
        );

        return [$result ?? '', $newClassReplacements];
    }

    /**
     * @param string $name an id or class name
     */
    private function isScoped(string $name): bool
    {
        return 1 === preg_match(self::SCOPED_NAME_REGEX, $name);
    }

    /**
     * @param string $selector a selector of a selector list
     *
     * @return bool true if the selector has no class or id selector outside a functional pseudo-class, so it may match
     *              elements of the page the SVG is embedded in
     */
    private function isUnsafeSelector(string $selector): bool
    {
        do {
            $selector = (string) preg_replace(self::FUNCTIONAL_ARGUMENT_REGEX, '', $selector, -1, $count);
        } while ($count > 0);

        return 1 !== preg_match(self::CLASS_SELECTOR_REGEX, $selector) && 1 !== preg_match(self::ID_SELECTOR_REGEX, $selector);
    }

    /**
     * @return array{string, array<string, string>}
     */
    private function scopeClasses(
        string $selector,
        string $hash,
    ): array {
        $scopedClasses = [];

        $newSelector = preg_replace_callback(
            self::CLASS_SELECTOR_REGEX,
            function (array $match) use ($hash, &$scopedClasses): string {
                $original = $match[1];

                if ($this->isScoped($original)) {
                    return $match[0];
                }

                $scoped = $original . '-' . $hash;
                $scopedClasses[$original] = $scoped;

                return '.' . $scoped;
            },
            $selector
        ) ?? $selector;

        return [$newSelector, $scopedClasses];
    }

    /**
     * @param array<string, true> $fragmentTargets the ids that keep their value
     */
    private function scopeSelectorIds(string $selector, string $hash, array $fragmentTargets): string
    {
        return preg_replace_callback(
            self::ID_SELECTOR_REGEX,
            fn (array $match): string => $this->isScoped($match[0]) || \array_key_exists($match[0], $fragmentTargets) ? $match[0] : $match[0] . '-' . $hash,
            $selector
        ) ?? $selector;
    }

    /**
     * @param array<string, string> $classReplacements
     */
    private function updateClassAttributes(
        \DOMXPath $domXPath,
        \DOMElement $domElement,
        array $classReplacements,
    ): void {
        if ([] === $classReplacements) {
            return;
        }

        /** @var \DOMNodeList<\DOMElement> $nodes */
        $nodes = $domXPath->query(self::CLASS_QUERY, $domElement, false);

        foreach ($nodes as $node) {
            $this->updateSingleClassAttribute($node, $classReplacements);
        }
    }

    /**
     * @param array<string, string> $classReplacements
     */
    private function updateSingleClassAttribute(\DOMElement $domElement, array $classReplacements): void
    {
        $value = $domElement->getAttribute(Attribute::Class_->value);
        $classes = preg_split(self::WHITESPACE_REGEX, $value);

        $domElement->setAttribute(
            Attribute::Class_->value,
            false === $classes ? $value : implode(' ', array_map(static fn (string $class): string => $classReplacements[$class] ?? $class, $classes)),
        );
    }

    /**
     * @param array<string, string> $idReplacements
     */
    private function updateUrlReferences(
        \DOMXPath $domXPath,
        \DOMElement $domElement,
        array $idReplacements,
    ): void {
        /** @var \DOMNodeList<\DOMNode> $nodes */
        $nodes = $domXPath->query(self::URL_REFERENCE_QUERY, $domElement, false);

        foreach ($nodes as $node) {
            $node->textContent = preg_replace_callback(
                self::URL_REFERENCE_REGEX,
                static fn (array $match): string => \array_key_exists($match[2], $idReplacements)
                    ? 'url(' . $match[1] . '#' . $idReplacements[$match[2]] . $match[1] . ')'
                    : $match[0],
                $node->textContent
            ) ?? $node->textContent;
        }
    }
}
