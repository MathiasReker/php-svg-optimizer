<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Services\Rules;

use DOMDocument;
use DOMXPath;
use MathiasReker\PhpSvgOptimizer\Contracts\Services\Rules\SvgOptimizerRuleInterface;

final class RemoveDeprecatedAttributes implements SvgOptimizerRuleInterface
{
    /**
     * The XML namespace attribute for the `xlink` namespace.
     */
    private const string XMLNS_ATTRIBUTE = 'xmlns:xlink';

    /**
     * List of deprecated SVG attributes that should be removed from the document.
     * These attributes are no longer recommended for use in modern SVGs.
     *
     * @var string[] An array of deprecated attribute names
     */
    private const array ATTRIBUTES_TO_REMOVE = [
        'baseProfile',
        'requiredFeatures',
        'version',
        'xlink:arcrole',
        'xlink:show',
        'xlink:type',
        'zoomAndPan',
    ];

    /**
     * List of attributes that need to be replaced with new names in the SVG document.
     * This ensures compatibility with newer SVG standards.
     *
     * @var array<string, string> An associative array where the key is the old attribute name
     *                            and the value is the new attribute name to replace it with
     */
    private const array ATTRIBUTES_TO_REPLACE = [
        'xlink:href' => 'href',
        'xlink:title' => 'title',
        'xml:lang' => 'lang',
    ];

    /**
     * Optimizes the given SVG document by removing deprecated attributes and replacing
     * outdated attributes with their modern equivalents.
     *
     * This method also removes the `xlink` namespace, which is no longer needed in recent
     * versions of SVG.
     *
     * @param \DOMDocument $domDocument The DOMDocument instance representing the SVG file to be optimized.
     *                                  The SVG will be modified in-place.
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);
        $domXPath->registerNamespace('xlink', 'http://www.w3.org/1999/xlink');
        $this->replaceAttributes($domXPath, self::ATTRIBUTES_TO_REPLACE);
        $this->removeNamespaceFromSvgTags($domDocument);
        $this->removeAttributes($domXPath);
    }

    /**
     * Replaces specific attributes in the SVG document with their modern equivalents.
     *
     * This function scans the document for the deprecated attributes listed in `$attributes`
     * and replaces them with the new names, but only if the new attribute's value is not
     * already set to the same value.
     *
     * @param \DOMXPath             $domXPath   The DOMXPath instance used to query the SVG elements
     * @param array<string, string> $attributes An associative array where the key is the old attribute
     *                                          and the value is the new attribute name
     */
    private function replaceAttributes(\DOMXPath $domXPath, array $attributes): void
    {
        foreach ($attributes as $oldName => $newName) {
            $nodes = $domXPath->query(\sprintf('//*[@%s]', $oldName));
            if (!$nodes instanceof \DOMNodeList) {
                continue;
            }

            foreach ($nodes as $node) {
                if (!($node instanceof \DOMElement && $node->hasAttribute($oldName))) {
                    continue;
                }

                $value = $node->getAttribute($oldName);

                if (!$node->hasAttribute($newName) || $node->getAttribute($newName) !== $value) {
                    $node->setAttribute($newName, $value);
                }

                $node->removeAttribute($oldName);
            }
        }
    }

    /**
     * Removes the `xlink` namespace from the root SVG element.
     *
     * The `xlink` namespace is no longer required for modern SVGs, so this method
     * removes it if it exists in the document.
     *
     * @param \DOMDocument $domDocument The DOMDocument instance representing the SVG to be optimized
     */
    private function removeNamespaceFromSvgTags(\DOMDocument $domDocument): void
    {
        $root = $domDocument->documentElement;

        if ($root instanceof \DOMElement && $root->hasAttribute(self::XMLNS_ATTRIBUTE)) {
            $root->removeAttribute(self::XMLNS_ATTRIBUTE);
        }
    }

    /**
     * Removes specific deprecated attributes from the SVG document.
     *
     * This method removes the attributes listed in `ATTRIBUTES_TO_REMOVE` from all
     * SVG elements in the document.
     *
     * @param \DOMXPath $domXPath The DOMXPath instance used to query the SVG elements
     */
    private function removeAttributes(\DOMXPath $domXPath): void
    {
        foreach (self::ATTRIBUTES_TO_REMOVE as $attribute) {
            $nodes = $domXPath->query(\sprintf('//*[@%s]', $attribute));
            if (!$nodes instanceof \DOMNodeList) {
                continue;
            }

            foreach ($nodes as $node) {
                if (!($node instanceof \DOMElement && $node->hasAttribute($attribute))) {
                    continue;
                }

                $node->removeAttribute($attribute);
            }
        }
    }
}
