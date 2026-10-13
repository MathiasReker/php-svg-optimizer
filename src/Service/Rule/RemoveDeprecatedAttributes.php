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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveDeprecatedAttributes implements SvgOptimizerRuleInterface
{
    private const array ATTRIBUTES_TO_REMOVE = [
        SvgAttribute::BaseProfile->value,
        SvgAttribute::ContentScriptType->value,
        SvgAttribute::ContentStyleType->value,
        SvgAttribute::CurrentView->value,
        SvgAttribute::ExternalResourcesRequired->value,
        SvgAttribute::RequiredFeatures->value,
        SvgAttribute::UseCurrentView->value,
        SvgAttribute::Version->value,
        SvgAttribute::ViewTarget->value,
        SvgAttribute::Viewport->value,
        SvgAttribute::XlinkArcrole->value,
        SvgAttribute::XlinkShow->value,
        SvgAttribute::XlinkType->value,
        SvgAttribute::XmlBase->value,
        SvgAttribute::ZoomAndPan->value,
        SvgAttribute::SuspendRedraw->value,
        SvgAttribute::UnsuspendRedraw->value,
        SvgAttribute::UnsuspendRedrawAll->value,
        SvgAttribute::GlyphOrientationVertical->value,
        SvgAttribute::GlyphOrientationHorizontal->value,
    ];

    private const string XLINK_SHOW_NEW = 'new';

    private const string TARGET_BLANK = '_blank';

    private const array ATTRIBUTES_TO_REPLACE = [
        SvgAttribute::XlinkHref->value => SvgAttribute::Href->value,
        SvgAttribute::XmlLang->value => SvgAttribute::Lang->value,
    ];

    private const array DEPRECATED_ATTRIBUTES_TAKING_PRECEDENCE = [
        SvgAttribute::XmlLang->value,
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
        $domXPath = new \DOMXPath($domDocument);
        $domXPath->registerNamespace(SvgNamespace::Xlink->prefix(), SvgNamespace::Xlink->value);

        $this->convertXlinkShow($domXPath);
        $this->convertXlinkTitles($domXPath);
        $this->replaceAttributes($domXPath, self::ATTRIBUTES_TO_REPLACE);
        $this->removeAttributes($domXPath, self::ATTRIBUTES_TO_REMOVE);
        $this->removeNamespaceFromSvgTags($domDocument);
    }

    /**
     * @param \DOMXPath $domXPath the XPath object for querying the document
     */
    private function convertXlinkShow(\DOMXPath $domXPath): void
    {
        /** @var \DOMNodeList<\DOMElement> $elements */
        $elements = $domXPath->query('//*[@' . SvgAttribute::XlinkShow->value . ']');

        foreach ($elements as $element) {
            if (SvgTag::A->value === $element->localName
                && self::XLINK_SHOW_NEW === trim($element->getAttribute(SvgAttribute::XlinkShow->value))
                && !$element->hasAttribute(SvgAttribute::Target->value)
            ) {
                $element->setAttribute(SvgAttribute::Target->value, self::TARGET_BLANK);
            }
        }
    }

    /**
     * @param \DOMXPath $domXPath the XPath object for querying the document
     */
    private function convertXlinkTitles(\DOMXPath $domXPath): void
    {
        /** @var \DOMNodeList<\DOMElement> $elements */
        $elements = $domXPath->query('//*[@' . SvgAttribute::XlinkTitle->value . ']');

        foreach (iterator_to_array($elements, false) as $domElement) {
            $title = $domElement->getAttribute(SvgAttribute::XlinkTitle->value);
            $domElement->removeAttribute(SvgAttribute::XlinkTitle->value);

            if ('' === trim($title) || $this->hasTitleChild($domElement)) {
                continue;
            }

            $titleElement = $domElement->ownerDocument?->createElementNS($domElement->namespaceURI, SvgTag::Title->value);

            if ($titleElement instanceof \DOMElement) {
                $titleElement->textContent = $title;
                $domElement->insertBefore($titleElement, $domElement->firstChild);
            }
        }
    }

    /**
     * @param \DOMElement $domElement the element to check
     */
    private function hasTitleChild(\DOMElement $domElement): bool
    {
        foreach ($domElement->childNodes as $child) {
            if ($child instanceof \DOMElement && SvgTag::Title->value === $child->localName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMXPath             $domXPath   the XPath object for querying the document
     * @param array<string, string> $attributes a map of old attribute names to new attribute names
     */
    private function replaceAttributes(\DOMXPath $domXPath, array $attributes): void
    {
        foreach ($attributes as $oldName => $newName) {
            /** @var \DOMNodeList<\DOMElement> $elements */
            $elements = $domXPath->query('//*[@' . $oldName . ']');

            foreach ($elements as $element) {
                if (!$element->hasAttribute($newName) || \in_array($oldName, self::DEPRECATED_ATTRIBUTES_TAKING_PRECEDENCE, true)) {
                    $element->setAttribute($newName, $element->getAttribute($oldName));
                }

                $element->removeAttribute($oldName);
            }
        }
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to modify
     */
    private function removeNamespaceFromSvgTags(\DOMDocument $domDocument): void
    {
        $domDocument->documentElement?->removeAttribute(SvgAttribute::XmlnsXlink->value);
    }

    /**
     * @param \DOMXPath    $domXPath   the XPath object for querying the document
     * @param list<string> $attributes a list of attribute names to remove
     */
    private function removeAttributes(\DOMXPath $domXPath, array $attributes): void
    {
        if ([] === $attributes) {
            return;
        }

        $query = implode(' | ', array_map(static fn (string $attr): string => '//*[@' . $attr . ']', $attributes));
        /** @var \DOMNodeList<\DOMElement> $elements */
        $elements = $domXPath->query($query);

        foreach ($elements as $element) {
            foreach ($attributes as $attribute) {
                if ($element->hasAttribute($attribute)) {
                    $element->removeAttribute($attribute);
                }
            }
        }
    }
}
