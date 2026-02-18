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

final readonly class ScopeSvgStyles implements SvgOptimizerRuleInterface
{
    /**
     * Matches a CSS rule, capturing the selector and body.
     *
     * @see https://regex101.com/r/aoMXmR/1
     */
    private const string CSS_RULE_REGEX = '/(?<selector>[^{]+?){(?<body>[^}]+)}/';

    /**
     * Matches a tag selector.
     *
     * @see https://regex101.com/r/3fFyX5/1
     */
    private const string TAG_SELECTOR_REGEX = '/^[a-zA-Z][\w-]*$/';

    /**
     * Matches a class selector.
     *
     * @see https://regex101.com/r/wqnnqt/1
     */
    private const string CLASS_SELECTOR_REGEX = '/\.([\w\-]+)/';

    /**
     * Matches an ID selector.
     *
     * @see https://regex101.com/r/W8IG1C/1
     */
    private const string ID_SELECTOR_REGEX = '/#([\w\-]+)/';

    /**
     * Matches a URL reference to an ID.
     *
     * @see https://regex101.com/r/RPou2d/1
     */
    private const string URL_REFERENCE_REGEX = '/url\(#([\w\-]+)\)/';

    /**
     * Matches one or more whitespace characters.
     *
     * @see https://regex101.com/r/kNrSO5/1
     */
    private const string WHITESPACE_REGEX = '/\s+/';

    /**
     * XPath query for finding all SVG elements.
     */
    private const string SVG_QUERY = '//*[local-name()="svg"]';

    /**
     * XPath query for finding all elements with an ID attribute.
     */
    private const string ID_QUERY = './/*[@id]';

    /**
     * XPath query for finding all elements with a class attribute.
     */
    private const string CLASS_QUERY = './/*[@class]';

    /**
     * XPath query for finding all attributes containing a URL reference.
     */
    private const string URL_REFERENCE_QUERY = './/@*[contains(., "url(#")]';

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
     * Finds all SVG elements in the DOM and scopes their styles individually.
     * This prevents style conflicts when multiple SVGs are embedded in the same document.
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);
        $svgs = $domXPath->query(self::SVG_QUERY);

        if (false === $svgs || 0 === $svgs->length) {
            return;
        }

        $multiple = $svgs->length > 1;

        foreach ($svgs as $index => $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }

            $xml = $domDocument->saveXML($node);
            if (false === $xml) {
                continue;
            }

            $baseHash = mb_substr(md5($xml), 0, 10);
            $hash = $multiple ? $baseHash . '-' . $index : $baseHash;

            $this->scopeSingleSvg($domXPath, $node, $hash);
        }
    }

    /**
     * Scope a single SVG element with a unique hash.
     */
    private function scopeSingleSvg(\DOMXPath $domXPath, \DOMElement $domElement, string $hash): void
    {
        $idReplacements = $this->scopeIds($domXPath, $domElement, $hash);
        $classReplacements = $this->processStyleBlocks($domElement, $hash, $idReplacements);

        $this->updateClassAttributes($domXPath, $domElement, $classReplacements);
        $this->updateUrlReferences($domXPath, $domElement, $idReplacements);
    }

    /**
     * Scope all IDs in the given SVG element.
     *
     * @return array<string, string> a map of original to new IDs
     */
    private function scopeIds(\DOMXPath $domXPath, \DOMElement $domElement, string $hash): array
    {
        $result = [];

        $nodes = $domXPath->query(self::ID_QUERY, $domElement);
        if (false === $nodes) {
            return $result;
        }

        foreach ($nodes as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }

            $original = $node->getAttribute(SvgAttribute::Id->value);
            if ('' === $original) {
                continue;
            }

            $new = $original . '-' . $hash;
            $node->setAttribute(SvgAttribute::Id->value, $new);
            $result[$original] = $new;
        }

        return $result;
    }

    /**
     * Process all style blocks in the given SVG element.
     *
     * @param array<string, string> $idReplacements
     *
     * @return array<string, string> a map of original to new class names
     */
    private function processStyleBlocks(
        \DOMElement $domElement,
        string $hash,
        array $idReplacements,
    ): array {
        $classReplacements = [];

        foreach ($domElement->getElementsByTagName(SvgTag::Style->value) as $domNodeList) {
            $css = trim($domNodeList->nodeValue ?? '');
            if ('' === $css) {
                continue;
            }

            $processed = $this->processCssRules(
                $css,
                $hash,
                $classReplacements,
                $idReplacements
            );

            $domNodeList->nodeValue = $processed;
        }

        return $classReplacements;
    }

    /**
     * Process the CSS rules within a style block.
     *
     * @param array<string, string> $classReplacements
     * @param array<string, string> $idReplacements
     */
    private function processCssRules(
        string $css,
        string $hash,
        array &$classReplacements,
        array &$idReplacements,
    ): string {
        $result = preg_replace_callback(
            self::CSS_RULE_REGEX,
            function (array $match) use ($hash, &$classReplacements, &$idReplacements): string {
                $selector = trim($match['selector']);
                $body = $match['body'];

                if ($this->isUnsafeSelector($selector)) {
                    return '';
                }

                $selectors = array_map(trim(...), explode(',', $selector));

                foreach ($selectors as &$single) {
                    $single = $this->scopeClasses($single, $hash, $classReplacements);
                    $single = $this->scopeSelectorIds($single, $hash, $idReplacements);
                }

                return \sprintf('%s{%s}', implode(', ', $selectors), $body);
            },
            $css
        );

        return $result ?? '';
    }

    /**
     * Check if a CSS selector is considered unsafe.
     */
    private function isUnsafeSelector(string $selector): bool
    {
        if (str_starts_with($selector, '*')) {
            return true;
        }

        return 1 === preg_match(self::TAG_SELECTOR_REGEX, $selector);
    }

    /**
     * Scope classes within a CSS selector.
     *
     * @param array<string, string> $classReplacements
     */
    private function scopeClasses(
        string $selector,
        string $hash,
        array &$classReplacements,
    ): string {
        return (string) preg_replace_callback(
            self::CLASS_SELECTOR_REGEX,
            static function (array $match) use ($hash, &$classReplacements): string {
                $original = $match[1];
                $scoped = $original . '-' . $hash;
                $classReplacements[$original] = $scoped;

                return '.' . $scoped;
            },
            $selector
        );
    }

    /**
     * Scope IDs within a CSS selector.
     *
     * @param array<string, string> $idReplacements
     */
    private function scopeSelectorIds(
        string $selector,
        string $hash,
        array &$idReplacements,
    ): string {
        return (string) preg_replace_callback(
            self::ID_SELECTOR_REGEX,
            static function (array $match) use ($hash, &$idReplacements): string {
                $original = $match[1];

                if (!\array_key_exists($original, $idReplacements)) {
                    $idReplacements[$original] = $original . '-' . $hash;
                }

                return '#' . $idReplacements[$original];
            },
            $selector
        );
    }

    /**
     * Update class attributes in the SVG element.
     *
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

        $nodes = $domXPath->query(self::CLASS_QUERY, $domElement);
        if (false === $nodes) {
            return;
        }

        foreach ($nodes as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }

            $classes = preg_split(self::WHITESPACE_REGEX, $node->getAttribute(SvgAttribute::Class_->value));
            if (!\is_array($classes)) {
                continue;
            }

            foreach ($classes as &$class) {
                if (\array_key_exists($class, $classReplacements)) {
                    $class = $classReplacements[$class];
                }
            }

            $node->setAttribute(SvgAttribute::Class_->value, implode(' ', $classes));
        }
    }

    /**
     * Update URL references in the SVG element.
     *
     * @param array<string, string> $idReplacements
     */
    private function updateUrlReferences(
        \DOMXPath $domXPath,
        \DOMElement $domElement,
        array $idReplacements,
    ): void {
        if ([] === $idReplacements) {
            return;
        }

        $attributes = $domXPath->query(self::URL_REFERENCE_QUERY, $domElement);
        if (false === $attributes) {
            return;
        }

        foreach ($attributes as $attribute) {
            if (!$attribute instanceof \DOMAttr) {
                continue;
            }

            $value = $attribute->nodeValue;

            $attribute->nodeValue = preg_replace_callback(
                self::URL_REFERENCE_REGEX,
                static function (array $match) use ($idReplacements): string {
                    $id = $match[1];

                    return \array_key_exists($id, $idReplacements)
                        ? 'url(#' . $idReplacements[$id] . ')'
                        : $match[0];
                },
                (string) $value
            );
        }
    }
}
