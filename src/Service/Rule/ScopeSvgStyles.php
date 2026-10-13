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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\IdRenameTrait;

/**
 * @internal
 */
final readonly class ScopeSvgStyles implements SvgOptimizerRuleInterface
{
    use IdRenameTrait;

    private const string CSS_RULE_REGEX = '/(?<selector>[^{]+?){(?<body>[^}]+)}/';

    private const string TAG_SELECTOR_REGEX = '/^[a-zA-Z][\w-]*$/';

    private const string CLASS_SELECTOR_REGEX = '/\.([\w\-]+)/';

    private const string ID_SELECTOR_REGEX = '/#([\w\-]+)/';

    private const string URL_REFERENCE_REGEX = '/url\(([\'"]?)#([^\s\'")]+)\1\)/';

    private const string WHITESPACE_REGEX = '/\s+/';

    private const string UNSCOPABLE_CSS_REGEX = '/\{[^}]*\{|@import/i';

    private const string SVG_QUERY = '//*[local-name()="svg"]';

    private const string ID_QUERY = './/*[@id]';

    private const string CLASS_QUERY = './/*[@class]';

    private const string URL_REFERENCE_QUERY = './/@*[contains(., "url(")] | .//*[local-name()="style"][contains(., "url(")]';

    private const string SCOPED_NAME_REGEX = '/-[0-9a-f]{10}(?:-\d+)?$/';

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

    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        if ($this->hasUnscopableStyleSheet($domDocument)) {
            return;
        }

        $domXPath = new \DOMXPath($domDocument);

        /** @var \DOMNodeList<\DOMElement> $svgs */
        $svgs = $domXPath->query(self::SVG_QUERY);
        $multiple = $svgs->length > 1;

        foreach ($svgs as $index => $node) {
            $baseHash = mb_substr(md5((string) $domDocument->saveXML($node)), 0, 10);
            $hash = $multiple ? $baseHash . '-' . $index : $baseHash;

            $this->scopeSingleSvg($domXPath, $node, $hash);
        }
    }

    /**
     * @param \DOMDocument $domDocument the document to check
     */
    private function hasUnscopableStyleSheet(\DOMDocument $domDocument): bool
    {
        foreach ($domDocument->getElementsByTagName(SvgTag::Style->value) as $domNodeList) {
            if (1 === preg_match(self::UNSCOPABLE_CSS_REGEX, $domNodeList->textContent)) {
                return true;
            }
        }

        return false;
    }

    private function scopeSingleSvg(\DOMXPath $domXPath, \DOMElement $domElement, string $hash): void
    {
        $idReplacements = $this->scopeIds($domXPath, $domElement, $hash);
        $classReplacements = $this->processStyleBlocks($domElement, $hash);

        $this->updateClassAttributes($domXPath, $domElement, $classReplacements);
        $this->updateUrlReferences($domXPath, $domElement, $idReplacements);
        $this->updateIdReferenceAttributes($domXPath, $domElement, $idReplacements);
    }

    /**
     * @return array<string, string> a map of original to new IDs
     */
    private function scopeIds(\DOMXPath $domXPath, \DOMElement $domElement, string $hash): array
    {
        $result = [];

        /** @var \DOMNodeList<\DOMElement> $nodes */
        $nodes = $domXPath->query(self::ID_QUERY, $domElement);

        foreach ($nodes as $node) {
            $original = $node->getAttribute(SvgAttribute::Id->value);
            if ('' === $original || $this->isScoped($original)) {
                continue;
            }

            $new = $original . '-' . $hash;
            $node->setAttribute(SvgAttribute::Id->value, $new);
            $result[$original] = $new;
        }

        return $result;
    }

    /**
     * @return array<string, string> a map of original to new class names
     */
    private function processStyleBlocks(\DOMElement $domElement, string $hash): array
    {
        $classReplacements = [];

        foreach (iterator_to_array($domElement->getElementsByTagName(SvgTag::Style->value), false) as $styleElement) {
            $css = trim($styleElement->textContent);
            if ('' === $css) {
                continue;
            }

            [$processedCss, $newClassReplacements] = $this->processCssRules($css, $hash);

            $classReplacements = [...$classReplacements, ...$newClassReplacements];

            if ('' === $processedCss) {
                $styleElement->parentNode?->removeChild($styleElement);

                continue;
            }

            $styleElement->textContent = $processedCss;
        }

        return $classReplacements;
    }

    /**
     * @return array{string, array<string, string>}
     */
    private function processCssRules(string $css, string $hash): array
    {
        $newClassReplacements = [];

        $result = preg_replace_callback(
            self::CSS_RULE_REGEX,
            function (array $match) use ($hash, &$newClassReplacements): string {
                $body = $match['body'];

                $selectors = array_filter(
                    array_map(trim(...), explode(',', $match['selector'])),
                    fn (string $single): bool => !$this->isUnsafeSelector($single),
                );

                if ([] === $selectors) {
                    return '';
                }

                $scopedSelectors = [];
                foreach ($selectors as $selector) {
                    [$selector, $scopedClasses] = $this->scopeClasses($selector, $hash);
                    $newClassReplacements = [...$newClassReplacements, ...$scopedClasses];

                    $scopedSelectors[] = $this->scopeSelectorIds($selector, $hash);
                }

                return \sprintf('%s{%s}', implode(', ', $scopedSelectors), $body);
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

    private function isUnsafeSelector(string $selector): bool
    {
        if (str_starts_with($selector, '*')) {
            return true;
        }

        return 1 === preg_match(self::TAG_SELECTOR_REGEX, $selector);
    }

    /**
     * @return array{string, array<string, string>}
     */
    private function scopeClasses(
        string $selector,
        string $hash,
    ): array {
        $scopedClasses = [];

        $newSelector = (string) preg_replace_callback(
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
        );

        return [$newSelector, $scopedClasses];
    }

    private function scopeSelectorIds(string $selector, string $hash): string
    {
        return (string) preg_replace_callback(
            self::ID_SELECTOR_REGEX,
            fn (array $match): string => $this->isScoped($match[1]) ? $match[0] : '#' . $match[1] . '-' . $hash,
            $selector
        );
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
        $nodes = $domXPath->query(self::CLASS_QUERY, $domElement);

        foreach ($nodes as $node) {
            $this->updateSingleClassAttribute($node, $classReplacements);
        }
    }

    /**
     * @param array<string, string> $classReplacements
     */
    private function updateSingleClassAttribute(\DOMElement $domElement, array $classReplacements): void
    {
        $value = $domElement->getAttribute(SvgAttribute::Class_->value);
        $classes = preg_split(self::WHITESPACE_REGEX, $value);

        $domElement->setAttribute(
            SvgAttribute::Class_->value,
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
        if ([] === $idReplacements) {
            return;
        }

        /** @var \DOMNodeList<\DOMNode> $nodes */
        $nodes = $domXPath->query(self::URL_REFERENCE_QUERY, $domElement);

        foreach ($nodes as $node) {
            $node->textContent = (string) preg_replace_callback(
                self::URL_REFERENCE_REGEX,
                static fn (array $match): string => \array_key_exists($match[2], $idReplacements)
                    ? 'url(' . $match[1] . '#' . $idReplacements[$match[2]] . $match[1] . ')'
                    : $match[0],
                $node->textContent
            );
        }
    }
}
