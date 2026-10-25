<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AnimationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\DefinitionElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\FragmentTargetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdRenameTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Paint\GradientLinkTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class MergeDuplicateGradients implements RevertIfLargerPassInterface
{
    use AnimationTrait;
    use DefinitionElementTrait;
    use ElementTrait;
    use FragmentTargetTrait;
    use GradientLinkTrait;
    use IdReferenceTrait;
    use IdRenameTrait;
    use IdTrait;
    use ScriptingTrait;
    use StyleSheetTrait;

    private const string URL_REFERENCE_REGEX = '/(url\(\s*([\'"]?)#)([^\'")\s]+)(\2\s*\))/i';

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->hasStyleSheet($domDocument)
            || $this->hasScriptOrEventHandler($domDocument)) {
            return;
        }

        while ($this->mergeOnce($domDocument)) {
            $this->removeEmptyDefsElements($domDocument);
        }
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     *
     * @return bool true if a gradient was merged
     */
    private function mergeOnce(\DOMDocument $domDocument): bool
    {
        $gradients = $this->gradientsById($domDocument);
        $replacements = $this->duplicateIds($gradients, $this->animationTargetIds($domDocument), $this->fragmentTargetIds($domDocument));
        $replacements = array_diff_key($replacements, $this->blockedIds($domDocument, $replacements));

        foreach ($domDocument->getElementsByTagName('*') as $element) {
            foreach ($element->attributes as $attribute) {
                $attribute->textContent = $this->rewrittenValue($attribute, $replacements);
            }
        }

        foreach (array_keys($replacements) as $id) {
            $gradients[$id]->remove();
        }

        return [] !== $replacements;
    }

    /**
     * @param array<string, \DOMElement> $gradients       the gradients by id
     * @param array<string, true>        $targetIds       the ids targeted by an animation
     * @param array<string, true>        $fragmentTargets the ids that another document may address
     *
     * @return array<string, string> the id of each duplicate gradient mapped to the id of the first identical one
     */
    private function duplicateIds(array $gradients, array $targetIds, array $fragmentTargets): array
    {
        $firstIds = [];
        $replacements = [];

        foreach ($gradients as $id => $gradient) {
            if (\array_key_exists($id, $targetIds) || !$this->isMergeable($gradient)) {
                continue;
            }

            $key = serialize([$this->ancestorAttributes($gradient), $this->gradientKey($gradient)]);
            $firstIds[$key] ??= $id;

            if ($firstIds[$key] !== $id && !\array_key_exists($id, $fragmentTargets)) {
                $replacements[$id] = $firstIds[$key];
            }
        }

        return array_filter(
            $replacements,
            fn (string $keptId): bool => !$this->linksIntoGroup($gradients[$keptId], $keptId, $replacements),
        );
    }

    /**
     * @param \DOMElement           $domElement   the kept gradient
     * @param string                $keptId       the id of the kept gradient
     * @param array<string, string> $replacements the duplicate ids mapped to the kept ids
     *
     * @return bool true if the gradients of the group link to a gradient of the same group
     */
    private function linksIntoGroup(\DOMElement $domElement, string $keptId, array $replacements): bool
    {
        $linkedId = $this->linkedGradientId($domElement);

        return $linkedId === $keptId || ($replacements[$linkedId] ?? '') === $keptId;
    }

    /**
     * @param \DOMElement $domElement the gradient to check
     *
     * @return bool true if the gradient only has stops without ids or content, and is not foreign content
     */
    private function isMergeable(\DOMElement $domElement): bool
    {
        if ($this->isForeignContent($domElement)) {
            return false;
        }

        foreach ($domElement->childNodes as $childNode) {
            if ($childNode instanceof \DOMElement
                && (Element::Stop->value !== $childNode->localName
                    || $childNode->namespaceURI !== $domElement->namespaceURI
                    || $this->hasId($childNode)
                    || $this->hasElementChildren($childNode))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \DOMElement $domElement the gradient to describe
     *
     * @return string the canonical form of the gradient without its id
     */
    private function gradientKey(\DOMElement $domElement): string
    {
        $domDocument = new \DOMDocument();
        $domNode = $domDocument->importNode($domElement, true);
        \assert($domNode instanceof \DOMElement);
        $domNode->removeAttribute(Attribute::Id->value);
        $domDocument->appendChild($domNode);

        return (string) $domDocument->C14N();
    }

    /**
     * @param \DOMElement $domElement the gradient to describe
     *
     * @return list<list<array{?string, ?string, string}>> the sorted attributes of each ancestor the gradient inherits
     *                                                     properties from
     */
    private function ancestorAttributes(\DOMElement $domElement): array
    {
        $ancestors = [];

        for ($node = $domElement->parentNode; $node instanceof \DOMElement; $node = $node->parentNode) {
            $attributes = [];

            foreach ($node->attributes as $attribute) {
                $attributes[] = [$attribute->namespaceURI, $attribute->localName, $attribute->value];
            }

            sort($attributes);
            $ancestors[] = $attributes;
        }

        return $ancestors;
    }

    /**
     * @param \DOMDocument          $domDocument  the DOM document to scan
     * @param array<string, string> $replacements the duplicate ids mapped to the kept ids
     *
     * @return array<string, true> the ids that are still referenced after rewriting the references
     */
    private function blockedIds(\DOMDocument $domDocument, array $replacements): array
    {
        $blocked = [];

        foreach ($domDocument->getElementsByTagName('*') as $element) {
            foreach ($element->attributes as $attribute) {
                $blocked += $this->attributeValueReferences($attribute->nodeName, $this->rewrittenValue($attribute, $replacements));
            }
        }

        return $blocked;
    }

    /**
     * @param \DOMAttr              $domAttr      the attribute to rewrite
     * @param array<string, string> $replacements the duplicate ids mapped to the kept ids
     *
     * @return string the value with each `url(#id)` and each `href` fragment of a duplicate pointing to the kept id
     */
    private function rewrittenValue(\DOMAttr $domAttr, array $replacements): string
    {
        $value = preg_replace_callback(
            self::URL_REFERENCE_REGEX,
            static fn (array $match): string => $match[1] . ($replacements[$match[3]] ?? $match[3]) . $match[4],
            $domAttr->value,
        ) ?? $domAttr->value;

        return $this->isRewritableHref($domAttr) ? $this->replaceFragmentId($value, $replacements) : $value;
    }

    /**
     * @param \DOMAttr $domAttr the attribute to check
     *
     * @return bool true if the attribute is an `href` or `xlink:href`
     */
    private function isRewritableHref(\DOMAttr $domAttr): bool
    {
        return Attribute::Href->value === $domAttr->localName
            && \in_array($domAttr->namespaceURI, [null, NamespaceUri::Xlink->value], true);
    }
}
