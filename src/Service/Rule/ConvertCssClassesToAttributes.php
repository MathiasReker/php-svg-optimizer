<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Pass\PassInterface;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgInlineStyleProperty;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ClassAttributeTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\CssDeclarationTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\CssStyleElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\DocumentScriptTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class ConvertCssClassesToAttributes implements PassInterface
{
    use ClassAttributeTrait;
    use CssDeclarationTrait;
    use CssStyleElementTrait;
    use DocumentScriptTrait;
    use ElementTrait;

    private const string XHTML_NAMESPACE = 'http://www.w3.org/1999/xhtml';

    private const array ALL_MEDIA = ['', 'all'];

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        /** @var \DOMNodeList<\DOMElement> $domNodeList */
        $domNodeList = $domDocument->getElementsByTagName(SvgTag::Style->value);
        $otherCss = $this->collectOtherCss($domNodeList);

        if ($this->hasExternalStyleSheet($domDocument) || $this->mayChangeAttribute($domDocument, SvgAttribute::Class_->value)) {
            return;
        }

        $classMap = $this->buildClassElementMap($domDocument);
        $styleElements = $this->styleElementsMatching($domNodeList, $this->isConvertibleStyleElement(...));
        $blockedProperties = $this->blockedProperties($styleElements, $classMap, $otherCss);
        $convertedClasses = [];

        foreach ($styleElements as $styleElement) {
            [$remainingCss, $classes] = $this->processCss($styleElement->textContent, $classMap, $blockedProperties);
            $convertedClasses = [...$convertedClasses, ...$classes];

            if ('' !== $remainingCss) {
                $styleElement->textContent = $remainingCss;
            } elseif ($this->hasId($styleElement)) {
                $styleElement->replaceChildren();
            } else {
                $styleElement->remove();
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
     * @return bool true if the style element is a CSS style sheet without a title, which may make it an alternative
     *              style sheet, that applies to all media and consists of plain rules only
     */
    private function isConvertibleStyleElement(\DOMElement $domElement): bool
    {
        $media = $this->asciiLowercase(trim($domElement->getAttribute(SvgAttribute::Media->value)));

        return \in_array($domElement->namespaceURI, [null, SvgNamespace::Svg->value, self::XHTML_NAMESPACE], true)
            && $this->isCssTextStyle($domElement)
            && !$domElement->hasAttribute(SvgAttribute::Title->value)
            && \in_array($media, self::ALL_MEDIA, true)
            && $this->isPlainCssRuleList($domElement->textContent);
    }

    /**
     * @param \DOMNodeList<\DOMElement> $domNodeList the style elements of the document
     *
     * @return string the CSS that stays CSS: unconvertible style elements and rules with other selectors
     */
    private function collectOtherCss(\DOMNodeList $domNodeList): string
    {
        $otherCss = '';

        foreach ($domNodeList as $styleElement) {
            $otherCss .= $this->isConvertibleStyleElement($styleElement)
                ? $this->rulesWithOtherSelectors($styleElement->textContent)
                : $styleElement->textContent;
        }

        return $otherCss;
    }

    /**
     * @param list<\DOMElement>                $styleElements the convertible style elements
     * @param array<string, list<\DOMElement>> $classMap      the class-to-element map
     * @param string                           $otherCss      the CSS that stays CSS
     *
     * @return array<string, true> the properties that stay CSS in every rule, as one of their declarations has to
     */
    private function blockedProperties(array $styleElements, array $classMap, string $otherCss): array
    {
        $styleCss = implode('', array_map(
            static fn (\DOMElement $domElement): string => $domElement->textContent,
            $styleElements,
        ));
        $rules = [];

        foreach ($styleElements as $styleElement) {
            preg_match_all(self::CSS_PLAIN_RULE_REGEX, $styleElement->textContent, $matches, \PREG_SET_ORDER);
            $rules = [...$rules, ...$matches];
        }

        $properties = array_merge([], ...array_map(
            fn (array $rule): array => $this->cssPropertyNames($rule[2]),
            $rules,
        ));
        $blocked = $this->propertiesOverriddenByCss($properties, $otherCss, $styleCss);

        foreach ($rules as $rule) {
            $elements = $this->elementsWithClassSelectors($rule[1], $classMap);
            $blocked = [...$blocked, ...$this->blockedRuleProperties($rule[2], $elements)];
        }

        return $blocked;
    }

    /**
     * @param string            $declarations the declaration block of the rule
     * @param list<\DOMElement> $elements     the elements that the rule converts to attributes on
     *
     * @return array<string, true> the properties of the rule that have to stay CSS
     */
    private function blockedRuleProperties(string $declarations, array $elements): array
    {
        return $this->hasNonSvgElement($elements)
            ? array_fill_keys($this->cssPropertyNames($declarations), true)
            : $this->repeatedOrImportantProperties($declarations) + $this->differentlySetProperties($declarations, $elements);
    }

    /**
     * @param string                           $css               the CSS content from a `<style>` block
     * @param array<string, list<\DOMElement>> $classMap          the class-to-element map
     * @param array<string, true>              $blockedProperties the properties that stay CSS
     *
     * @return array{string, list<string>} the CSS that could not be converted to attributes, and the converted classes
     */
    private function processCss(string $css, array $classMap, array $blockedProperties): array
    {
        preg_match_all(self::CSS_PLAIN_RULE_REGEX, $css, $matches, \PREG_SET_ORDER);
        $remainingCss = [];
        $convertedClasses = [];

        foreach ($matches as $match) {
            $declarations = trim($match[2]);
            $selectors = $this->splitSelectorList($match[1]);

            $unconvertibleSelectors = [];

            foreach ($selectors as $selector) {
                if (1 !== preg_match(self::SIMPLE_CLASS_SELECTOR_REGEX, $selector, $selectorMatch)) {
                    $unconvertibleSelectors[] = $selector;

                    continue;
                }

                $convertedClasses[] = $selectorMatch[1];
                $remainingCss[] = $this->convertClassRule($selectorMatch[1], $declarations, $classMap, $blockedProperties);
            }

            if ([] !== $unconvertibleSelectors) {
                $remainingCss[] = \sprintf('%s{%s}', implode(',', $unconvertibleSelectors), $declarations);
            }
        }

        return [implode('', $remainingCss), $convertedClasses];
    }

    /**
     * @param string                           $class             the class name
     * @param string                           $declarations      the declaration block of the rule
     * @param array<string, list<\DOMElement>> $classMap          the class-to-element map
     * @param array<string, true>              $blockedProperties the properties that stay CSS
     *
     * @return string the rule with the declarations that stay CSS, or an empty string if none do
     */
    private function convertClassRule(string $class, string $declarations, array $classMap, array $blockedProperties): string
    {
        [$convertible, $nonConvertible] = $this->splitDeclarations($declarations, $blockedProperties);
        $this->applyStylesToElements($class, $classMap, $convertible);

        return [] === $nonConvertible ? '' : \sprintf('.%s{', $class) . implode(';', $nonConvertible) . '}';
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
     * @param string              $declarations      The CSS declaration block (e.g., "fill:red; font-size:12px").
     * @param array<string, true> $blockedProperties the properties that stay CSS
     *
     * @return array{array<string, string>, list<string>} the convertible declarations by property, and the declarations
     *                                                    that stay CSS, as written
     */
    private function splitDeclarations(string $declarations, array $blockedProperties): array
    {
        $convertible = [];
        $nonConvertible = [];

        foreach ($this->parseCssDeclarations($declarations) as [$prop, $value]) {
            $propLower = $this->asciiLowercase($prop);

            if (null !== SvgInlineStyleProperty::tryFrom($propLower) && !\array_key_exists($propLower, $blockedProperties)) {
                $convertible[$propLower] = $value;
            } else {
                $nonConvertible[] = $prop . ':' . $value;
            }
        }

        return [$convertible, $nonConvertible];
    }

    /**
     * @param \DOMNodeList<\DOMElement>        $domNodeList      the style elements of the document
     * @param array<string, list<\DOMElement>> $classMap         the class-to-element map
     * @param list<string>                     $convertedClasses the classes whose rules were converted
     */
    private function removeConvertedClasses(\DOMNodeList $domNodeList, array $classMap, array $convertedClasses): void
    {
        $remainingCss = $this->styleElementsText($domNodeList);

        foreach ($convertedClasses as $convertedClass) {
            if ($this->cssMatchesClass($remainingCss, $convertedClass)) {
                continue;
            }

            foreach ($classMap[$convertedClass] ?? [] as $element) {
                $this->removeClass($element, $convertedClass);
            }
        }
    }
}
