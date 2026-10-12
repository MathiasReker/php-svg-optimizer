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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgInlineStyleProperty;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ClassAttributeTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\CssDeclarationTrait;

/**
 * Style sheets for specific media or with nested blocks (such as `@media`) or comments are left untouched, as are `!important` declarations
 * and properties that other CSS may set, since presentation attributes lose to every style sheet rule. A class is only
 * removed from its elements when no remaining CSS refers to it.
 *
 * @internal
 *
 * @no-named-arguments
 */
final readonly class ConvertCssClassesToAttributes implements SvgOptimizerRuleInterface
{
    use AsciiCaseTrait;
    use ClassAttributeTrait;
    use CssDeclarationTrait;

    private const string CSS_RULE_REGEX = '/([^{}]+)\{([^}]+)}/';

    private const string SIMPLE_CLASS_SELECTOR_REGEX = '/^\.([a-zA-Z0-9_-]+)$/';

    private const string COMMENT_START = '/*';

    private const string IMPORTANT_MARKER = '!';

    private const array ALL_MEDIA = ['', 'all'];

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
        $classMap = $this->buildClassElementMap($domDocument);
        $convertedClasses = [];

        /** @var \DOMNodeList<\DOMElement> $domNodeList */
        $domNodeList = $domDocument->getElementsByTagName(SvgTag::Style->value);
        $otherCss = $this->collectOtherCss($domNodeList);

        foreach (iterator_to_array($domNodeList, false) as $domElement) {
            if (!$this->isConvertibleStyleElement($domElement)) {
                continue;
            }

            [$remainingCss, $classes] = $this->processCss($domElement->textContent, $classMap, $otherCss);
            $convertedClasses = [...$convertedClasses, ...$classes];

            if ('' === $remainingCss) {
                $domElement->parentNode?->removeChild($domElement);
            } else {
                $domElement->textContent = $remainingCss;
            }
        }

        $this->removeConvertedClasses($domNodeList, $classMap, $convertedClasses);
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to scan
     *
     * @return array<string, list<\DOMElement>> a map of class names to element arrays
     */
    private function buildClassElementMap(\DOMDocument $domDocument): array
    {
        $map = [];

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            foreach ($this->classList($domNodeList) as $class) {
                $map[$class][] = $domNodeList;
            }
        }

        return $map;
    }

    /**
     * @param \DOMElement $domElement a `<style>` element
     *
     * @return bool true if the style element applies to all media and consists of plain rules only
     */
    private function isConvertibleStyleElement(\DOMElement $domElement): bool
    {
        $media = $this->asciiLowercase(trim($domElement->getAttribute(SvgAttribute::Media->value)));

        return \in_array($media, self::ALL_MEDIA, true) && $this->isPlainCss($domElement->textContent);
    }

    /**
     * Presentation attributes lose to every style sheet rule, so a property that CSS other than the simple class
     * rules may set is not converted.
     *
     * @param \DOMNodeList<\DOMElement> $domNodeList the style elements of the document
     *
     * @return string the CSS that stays CSS: unconvertible style elements and rules with other selectors
     */
    private function collectOtherCss(\DOMNodeList $domNodeList): string
    {
        $otherCss = '';

        foreach ($domNodeList as $styleElement) {
            $otherCss .= $this->isConvertibleStyleElement($styleElement)
                ? $this->otherRules($styleElement->textContent)
                : $styleElement->textContent;
        }

        return $otherCss;
    }

    /**
     * @param string $css plain CSS rules
     *
     * @return string the rules with a selector other than a single class
     */
    private function otherRules(string $css): string
    {
        return (string) preg_replace_callback(
            self::CSS_RULE_REGEX,
            static fn (array $match): string => [] === preg_grep(
                self::SIMPLE_CLASS_SELECTOR_REGEX,
                array_map(trim(...), explode(',', $match[1])),
                \PREG_GREP_INVERT
            ) ? '' : $match[0],
            $css
        );
    }

    /**
     * @param string $css the CSS content from a `<style>` block
     *
     * @return bool true if the CSS consists of plain rules only, with declaration blocks that can be split safely
     */
    private function isPlainCss(string $css): bool
    {
        preg_match_all(self::CSS_RULE_REGEX, $css, $matches);

        return !str_contains($css, self::COMMENT_START)
            && '' === trim((string) preg_replace(self::CSS_RULE_REGEX, '', $css))
            && [] === array_filter($matches[2], fn (string $declarations): bool => !$this->isSplittableCssDeclarationBlock($declarations));
    }

    /**
     * @return array<string, int> a map for quick property lookup
     */
    private function getConvertibleProperties(): array
    {
        /** @var array<string, int>|null $lookup */
        static $lookup = null;

        return $lookup ??= array_flip(SvgInlineStyleProperty::values());
    }

    /**
     * @param string                           $css      the CSS content from a `<style>` block
     * @param array<string, list<\DOMElement>> $classMap the class-to-element map
     * @param string                           $otherCss the CSS that stays CSS
     *
     * @return array{string, list<string>} the CSS that could not be converted to attributes, and the converted classes
     */
    private function processCss(string $css, array $classMap, string $otherCss): array
    {
        preg_match_all(self::CSS_RULE_REGEX, $css, $matches, \PREG_SET_ORDER);
        $remainingCss = [];
        $convertedClasses = [];

        foreach ($matches as $match) {
            $declarations = trim($match[2]);
            $selectors = array_map(trim(...), explode(',', $match[1]));

            $unconvertibleSelectors = [];

            foreach ($selectors as $selector) {
                if (1 !== preg_match(self::SIMPLE_CLASS_SELECTOR_REGEX, $selector, $selectorMatch)) {
                    $unconvertibleSelectors[] = $selector;

                    continue;
                }

                $convertedClasses[] = $selectorMatch[1];
                $remainingCss[] = $this->convertClassRule($selectorMatch[1], $declarations, $classMap, $otherCss);
            }

            if ([] !== $unconvertibleSelectors) {
                $remainingCss[] = \sprintf('%s{%s}', implode(',', $unconvertibleSelectors), $declarations);
            }
        }

        return [implode('', $remainingCss), $convertedClasses];
    }

    /**
     * Applies the convertible declarations of a single-class rule to the elements with that class.
     *
     * @param string                           $class        the class name
     * @param string                           $declarations the declaration block of the rule
     * @param array<string, list<\DOMElement>> $classMap     the class-to-element map
     * @param string                           $otherCss     the CSS that stays CSS
     *
     * @return string the rule with the declarations that stay CSS, or an empty string if none do
     */
    private function convertClassRule(string $class, string $declarations, array $classMap, string $otherCss): string
    {
        [$convertible, $nonConvertible] = $this->splitDeclarations($declarations, $otherCss);
        $this->applyStylesToElements($class, $classMap, $convertible);

        return [] === $nonConvertible ? '' : $this->rebuildCssRule($class, $nonConvertible);
    }

    /**
     * @param array<string, list<\DOMElement>> $classMap
     * @param array<string, string>            $convertible
     */
    private function applyStylesToElements(string $class, array $classMap, array $convertible): void
    {
        foreach ($classMap[$class] ?? [] as $element) {
            foreach ($convertible as $prop => $value) {
                $element->setAttribute($prop, $value);
            }
        }
    }

    /**
     * @param string $declarations The CSS declaration block (e.g., "fill:red; font-size:12px").
     * @param string $otherCss     the CSS that stays CSS
     *
     * @return array{array<string, string>, array<string, string>} a tuple containing convertible and non-convertible declarations
     */
    private function splitDeclarations(string $declarations, string $otherCss): array
    {
        $convertibleLookup = $this->getConvertibleProperties();
        $convertible = [];
        $nonConvertible = [];

        foreach ($this->splitCssDeclarations($declarations) as $decl) {
            if (!str_contains($decl, ':')) {
                continue;
            }

            [$prop, $value] = array_map(trim(...), explode(':', $decl, 2));
            $propLower = $this->asciiLowercase($prop);

            if (\array_key_exists($propLower, $convertibleLookup)
                && !str_contains($value, self::IMPORTANT_MARKER)
                && !$this->declaresProperty($otherCss, $propLower)) {
                $convertible[$propLower] = $value;
            } else {
                $nonConvertible[$propLower] = $value;
            }
        }

        return [$convertible, $nonConvertible];
    }

    /**
     * @param string $css      the CSS to search
     * @param string $property a lowercase property name
     *
     * @return bool true if the CSS may declare the property
     */
    private function declaresProperty(string $css, string $property): bool
    {
        return 1 === preg_match('/[{;\s]' . preg_quote($property, '/') . '\s*:/i', $css);
    }

    /**
     * @param \DOMNodeList<\DOMElement>        $domNodeList      the style elements of the document
     * @param array<string, list<\DOMElement>> $classMap         the class-to-element map
     * @param list<string>                     $convertedClasses the classes whose rules were converted
     */
    private function removeConvertedClasses(\DOMNodeList $domNodeList, array $classMap, array $convertedClasses): void
    {
        $remainingCss = implode(' ', array_map(
            static fn (\DOMElement $domElement): string => $domElement->textContent,
            iterator_to_array($domNodeList, false)
        ));

        foreach ($convertedClasses as $convertedClass) {
            // Class names of simple selectors consist of letters, digits, `_` and `-` only, so they need no escaping.
            if (1 === preg_match('/\.' . $convertedClass . '(?![\w-])/', $remainingCss)) {
                continue;
            }

            foreach ($classMap[$convertedClass] ?? [] as $element) {
                $this->removeClass($element, $convertedClass);
            }
        }
    }

    /**
     * @param string                $class          the class name
     * @param array<string, string> $nonConvertible the map of non-convertible properties and values
     *
     * @return string the reconstructed CSS rule
     */
    private function rebuildCssRule(string $class, array $nonConvertible): string
    {
        $props = array_map(
            static fn (string $prop, string $value): string => $prop . ':' . $value,
            array_keys($nonConvertible),
            $nonConvertible
        );

        return \sprintf('.%s{', $class) . implode(';', $props) . '}';
    }
}
