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
     * Deprecated attributes to remove.
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

    #[\Override]
    public static function shouldCheckSize(): bool
    {
        return false;
    }

    /**
     * Removes deprecated attributes and replaces outdated ones with modern equivalents.
     *
     * This rule performs two main actions:
     * 1. Replaces attributes like `xlink:href` with the modern `href`.
     * 2. Removes a list of attributes that are deprecated or no longer in use
     *    in modern SVG specifications.
     *
     * @param \DOMDocument $domDocument the DOM document to optimize
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
     * Replaces specified deprecated attributes with their modern counterparts.
     *
     * @param \DOMXPath             $domXPath   the XPath object for querying the document
     * @param array<string, string> $attributes a map of old attribute names to new attribute names
     */
    private function replaceAttributes(\DOMXPath $domXPath, array $attributes): void
    {
        foreach ($attributes as $oldName => $newName) {
            /** @var \DOMNodeList<\DOMElement> $elements */
            $elements = $domXPath->query('//*[@' . $oldName . ']');

            foreach ($elements as $element) {
                $value = $element->getAttribute($oldName);

                if (!$element->hasAttribute($newName) || $element->getAttribute($newName) !== $value) {
                    $element->setAttribute($newName, $value);
                }

                $element->removeAttribute($oldName);
            }
        }
    }

    /**
     * Removes the `xmlns:xlink` namespace declaration from the root `<svg>` element.
     *
     * This is typically done after `xlink:` attributes have been replaced.
     *
     * @param \DOMDocument $domDocument the DOM document to modify
     */
    private function removeNamespaceFromSvgTags(\DOMDocument $domDocument): void
    {
        $root = $domDocument->documentElement;

        if ($root instanceof \DOMElement && $root->hasAttribute(SvgAttribute::XmlnsXlink->value)) {
            $root->removeAttribute(SvgAttribute::XmlnsXlink->value);
        }
    }

    /**
     * Removes a list of specified deprecated attributes from all elements.
     *
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
