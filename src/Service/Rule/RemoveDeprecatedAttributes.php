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

/**
 * @no-named-arguments
 */
final readonly class RemoveDeprecatedAttributes implements SvgOptimizerRuleInterface
{
    /**
     * List of deprecated SVG attributes that should be removed from the document.
     * These attributes are no longer recommended for use in modern SVGs.
     */
    private const array ATTRIBUTES_TO_REMOVE = [
        SvgAttribute::BaseProfile->value,
        SvgAttribute::ContentScriptType->value,
        SvgAttribute::ContentStyleType->value,
        SvgAttribute::Cursor->value,
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

    /**
     * Attributes that should be replaced with modern equivalents.
     */
    private const array ATTRIBUTES_TO_REPLACE = [
        SvgAttribute::XlinkHref->value => SvgAttribute::Href->value,
        SvgAttribute::XlinkTitle->value => SvgAttribute::Title->value,
        SvgAttribute::XmlLang->value => SvgAttribute::Lang->value,
    ];

    #[\Override]
    public static function isRisky(): bool
    {
        return false;
    }

    /**
     * Optimizes the given SVG document by removing deprecated attributes and replacing
     * outdated attributes with their modern equivalents.
     *
     * This method also removes the `xlink` namespace, which is no longer needed in recent
     * versions of SVG.
     *
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized.
     *                                  The SVG will be modified in-place.
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);
        $domXPath->registerNamespace(SvgNamespace::Xlink->prefix(), SvgNamespace::Xlink->value);

        $this->replaceAttributes($domXPath, self::ATTRIBUTES_TO_REPLACE);
        $this->removeNamespaceFromSvgTags($domDocument);
        $this->removeAttributes($domXPath, self::ATTRIBUTES_TO_REMOVE);
    }

    /**
     * Replaces specific attributes in the SVG document with their modern equivalents.
     *
     * This method scans the document for the deprecated attributes listed in `$attributes`
     * and replaces them with the new names, but only if the new attribute's value is not
     * already set to the same value.
     *
     * @param \DOMXPath             $domXPath   The \DOMXPath instance used to query the SVG elements
     * @param array<string, string> $attributes An associative array where the key is the old attribute
     *                                          and the value is the new attribute name
     */
    private function replaceAttributes(\DOMXPath $domXPath, array $attributes): void
    {
        foreach ($attributes as $oldName => $newName) {
            /** @var \DOMNodeList<\DOMElement> $domNodeList */
            $domNodeList = $domXPath->query(\sprintf('//*[@%s]', $oldName));

            foreach ($domNodeList as $domElement) {
                if (!$domElement->hasAttribute($oldName)) {
                    continue;
                }

                $value = $domElement->getAttribute($oldName);

                if (!$domElement->hasAttribute($newName) || $domElement->getAttribute($newName) !== $value) {
                    $domElement->setAttribute($newName, $value);
                }

                $domElement->removeAttribute($oldName);
            }
        }
    }

    /**
     * Removes the `xlink` namespace from the root SVG element.
     *
     * The `xlink` namespace is no longer required for modern SVGs, so this method
     * removes it if it exists in the document.
     *
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG to be optimized
     */
    private function removeNamespaceFromSvgTags(\DOMDocument $domDocument): void
    {
        $root = $domDocument->documentElement;

        if ($root instanceof \DOMElement && $root->hasAttribute(SvgAttribute::XmlnsXlink->value)) {
            $root->removeAttribute(SvgAttribute::XmlnsXlink->value);
        }
    }

    /**
     * Removes specific deprecated attributes from the SVG document.
     *
     * This method removes the attributes listed in `ATTRIBUTES_TO_REMOVE` from all
     * SVG elements in the document.
     *
     * @param \DOMXPath    $domXPath   The \DOMXPath instance used to query the SVG elements
     * @param list<string> $attributes An associative array where the key is the attribute
     */
    private function removeAttributes(\DOMXPath $domXPath, array $attributes): void
    {
        foreach ($attributes as $attribute) {
            /** @var \DOMNodeList<\DOMElement> $domNodeList */
            $domNodeList = $domXPath->query(\sprintf('//*[@%s]', $attribute));

            foreach ($domNodeList as $domElement) {
                if (!$domElement->hasAttribute($attribute)) {
                    continue;
                }

                $domElement->removeAttribute($attribute);
            }
        }
    }

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return false;
    }
}
