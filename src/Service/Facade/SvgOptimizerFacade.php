<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Facade;

use MathiasReker\PhpSvgOptimizer\Contract\Service\Provider\SvgProviderInterface;
use MathiasReker\PhpSvgOptimizer\Exception\FileNotFoundException;
use MathiasReker\PhpSvgOptimizer\Exception\IOException;
use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Exception\SvgValidationException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Service\Optimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Provider\FileProvider;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Type\Rule;
use MathiasReker\PhpSvgOptimizer\Type\RuleSet;
use MathiasReker\PhpSvgOptimizer\ValueObject\Metrics;

/**
 * @api
 *
 * @see https://github.com/MathiasReker/php-svg-optimizer
 *
 * @no-named-arguments
 */
final readonly class SvgOptimizerFacade
{
    /** @var SvgOptimizer The instance responsible for performing SVG optimizations */
    private SvgOptimizer $svgOptimizer;

    /**
     * @param SvgProviderInterface $svgProvider The provider for retrieving the SVG content
     */
    private function __construct(SvgProviderInterface $svgProvider)
    {
        $this->svgOptimizer = new SvgOptimizer($svgProvider);
    }

    /**
     * @param string $content The SVG content as a string
     *
     * @return static The SvgOptimizerFacade instance configured for string-based SVG content
     */
    public static function fromString(string $content): self
    {
        return new self(new StringProvider($content));
    }

    /**
     * @param string $filePath The path to the SVG file
     *
     * @return static The SvgOptimizerFacade instance configured for file-based SVG content
     *
     * @throws FileNotFoundException If the specified file does not exist
     * @throws IOException           If the file content cannot be read
     */
    public static function fromFile(string $filePath): self
    {
        return new self(new FileProvider($filePath));
    }

    /**
     * @return $this The SvgOptimizerFacade instance
     *
     * @throws SvgValidationException        If the SVG content is invalid
     * @throws RiskyRulesNotAllowedException If risky optimization rules are used but have not been explicitly allowed
     * @throws XmlProcessingException
     */
    public function optimize(): self
    {
        $this->svgOptimizer->optimize();

        return $this;
    }

    /**
     * @param bool $convertColorsToHex              Whether to convert colors to hexadecimal format
     * @param bool $convertCssClassesToAttributes   Whether to convert CSS classes to attributes
     * @param bool $convertInlineStylesToAttributes Whether to convert inline styles to attributes
     * @param bool $fixAttributeNames               Whether to fix typos in attribute names
     * @param bool $flattenGroups                   Whether to flatten nested group elements
     * @param bool $minifyTransformations           Whether to minify transformation attributes
     * @param bool $removeAriaAndRole               Whether to remove aria and role attributes
     * @param bool $removeComments                  Whether to remove XML comments from the SVG
     * @param bool $removeDataAttributes            Whether to remove data-* attributes from elements
     * @param bool $removeDefaultAttributes         Whether to remove attributes set to their default value, default type attributes on style and script elements and xml:space="default" where it has no effect
     * @param bool $removeDuplicateElements         Whether to remove duplicate elements
     * @param bool $removeEmptyAttributes           Whether to remove empty attributes from elements
     * @param bool $removeInkscapeFootprints        Whether to remove Inkscape-specific footprints
     * @param bool $removeInvisibleCharacters       Whether to remove invisible characters
     * @param bool $removeMetadata                  Whether to remove metadata elements from the SVG
     * @param bool $removeNonStandardAttributes     Whether to remove non-standard attributes that are not widely supported in SVG
     * @param bool $removeNonStandardTags           Whether to remove non-standard tags that are not part of the official SVG specification
     * @param bool $removeUnnecessaryWhitespace     Whether to remove unnecessary whitespace
     * @param bool $removeUnusedNamespaces          Whether to remove unused namespaces
     * @param bool $removeWidthHeightAttributes     Whether to remove width and height attributes
     * @param bool $scopeSvgStyles                  Whether to rewrite IDs and class names in SVG styles to prevent conflicts
     * @param bool $sortAttributes                  Whether to sort attributes
     * @param bool $convertEllipseToCircle          Whether to convert ellipses with equal radii to circles
     * @param bool $convertShapesToPath             Whether to convert rect, line, polyline and polygon elements to paths
     * @param bool $minifyNumericValues             Whether to shorten the notation of numeric attribute values and number lists
     * @param bool $minifyPathData                  Whether to minify path data without changing coordinates
     * @param bool $removeUselessDefs               Whether to remove definitions without an id
     * @param bool $convertOneStopGradients         Whether to replace gradients with a single stop by its color
     * @param bool $removeUnusedIds                 Whether to remove ids that are not referenced in the document
     * @param bool $mergeDuplicateGradients         Whether to merge identical gradients into one
     * @param bool $mergeStyles                     Whether to merge style elements into one
     * @param bool $migrateToSvg2                   Whether to migrate SVG 1.1 and SVG Tiny markup to SVG 2, including deprecated attributes and enable-background
     * @param bool $minifyStyles                    Whether to remove comments and whitespace from style elements
     * @param bool $moveCommonAttributesToGroup     Whether to move attributes shared by all children of a group onto the group
     * @param bool $moveGroupAttributesToElements   Whether to move the transform of a group onto its children
     * @param bool $removeHiddenElements            Whether to remove elements that cannot render anything, including shapes with a zero size
     * @param bool $removeUnusedDefinitions         Whether to remove definitions that are not referenced
     * @param bool $sortDefsChildren                Whether to group the children of defs elements by tag name
     * @param bool $removeScripts                   Whether to remove scripts, event handlers and animations that rewrite links or event handlers
     * @param bool $removeUnsafeUrls                Whether to remove attributes with URLs that can run script or load external content
     * @param bool $removeForeignContent            Whether to remove foreign, embedding and HTML breakout elements and non-canonical names
     * @param bool $sanitizeStyleSheets             Whether to remove unsafe statements and declarations from style sheets and style attributes
     * @param bool $removeUseBombs                  Whether to remove use elements that form cycles or render too many instances
     * @param bool $removeUnsafeMarkup              Whether to remove processing instructions and unsafe comments
     * @param bool $removeTitles                    Whether to remove the <title> elements
     * @param bool $removeDescriptions              Whether to remove the <desc> elements
     * @param bool $removeEmptyElements             Whether to remove empty containers and empty text elements
     * @param bool $removeIneffectiveAttributes     Whether to remove attributes that have no effect: useless strokes and fills, group attributes that are neither inherited nor apply to groups, and text attributes on elements without text
     *
     * @return $this The SvgOptimizerFacade instance
     */
    public function withRules(
        bool $convertColorsToHex = false,
        bool $convertCssClassesToAttributes = false,
        bool $convertInlineStylesToAttributes = false,
        bool $fixAttributeNames = false,
        bool $flattenGroups = false,
        bool $minifyTransformations = false,
        bool $removeAriaAndRole = false,
        bool $removeComments = false,
        bool $removeDataAttributes = false,
        bool $removeDefaultAttributes = false,
        bool $removeDuplicateElements = false,
        bool $removeEmptyAttributes = false,
        bool $removeInkscapeFootprints = false,
        bool $removeInvisibleCharacters = false,
        bool $removeMetadata = false,
        bool $removeNonStandardAttributes = false,
        bool $removeNonStandardTags = false,
        bool $removeUnnecessaryWhitespace = false,
        bool $removeUnusedNamespaces = false,
        bool $removeWidthHeightAttributes = false,
        bool $scopeSvgStyles = false,
        bool $sortAttributes = false,
        bool $convertEllipseToCircle = false,
        bool $convertShapesToPath = false,
        bool $minifyNumericValues = false,
        bool $minifyPathData = false,
        bool $removeUselessDefs = false,
        bool $convertOneStopGradients = false,
        bool $removeUnusedIds = false,
        bool $mergeDuplicateGradients = false,
        bool $mergeStyles = false,
        bool $migrateToSvg2 = false,
        bool $minifyStyles = false,
        bool $moveCommonAttributesToGroup = false,
        bool $moveGroupAttributesToElements = false,
        bool $removeHiddenElements = false,
        bool $removeUnusedDefinitions = false,
        bool $sortDefsChildren = false,
        bool $removeScripts = false,
        bool $removeUnsafeUrls = false,
        bool $removeForeignContent = false,
        bool $sanitizeStyleSheets = false,
        bool $removeUseBombs = false,
        bool $removeUnsafeMarkup = false,
        bool $removeTitles = false,
        bool $removeDescriptions = false,
        bool $removeEmptyElements = false,
        bool $removeIneffectiveAttributes = false,
    ): self {
        $rules = [
            [Rule::ConvertColorsToHex, $convertColorsToHex],
            [Rule::ConvertCssClassesToAttributes, $convertCssClassesToAttributes],
            [Rule::ConvertInlineStylesToAttributes, $convertInlineStylesToAttributes],
            [Rule::FixAttributeNames, $fixAttributeNames],
            [Rule::FlattenGroups, $flattenGroups],
            [Rule::MinifyTransformations, $minifyTransformations],
            [Rule::RemoveAriaAndRole, $removeAriaAndRole],
            [Rule::RemoveComments, $removeComments],
            [Rule::RemoveDataAttributes, $removeDataAttributes],
            [Rule::RemoveDefaultAttributes, $removeDefaultAttributes],
            [Rule::RemoveDuplicateElements, $removeDuplicateElements],
            [Rule::RemoveEmptyAttributes, $removeEmptyAttributes],
            [Rule::RemoveInkscapeFootprints, $removeInkscapeFootprints],
            [Rule::RemoveInvisibleCharacters, $removeInvisibleCharacters],
            [Rule::RemoveMetadata, $removeMetadata],
            [Rule::RemoveNonStandardAttributes, $removeNonStandardAttributes],
            [Rule::RemoveNonStandardTags, $removeNonStandardTags],
            [Rule::RemoveUnnecessaryWhitespace, $removeUnnecessaryWhitespace],
            [Rule::RemoveUnusedNamespaces, $removeUnusedNamespaces],
            [Rule::RemoveWidthHeightAttributes, $removeWidthHeightAttributes],
            [Rule::ScopeSvgStyles, $scopeSvgStyles],
            [Rule::SortAttributes, $sortAttributes],
            [Rule::ConvertEllipseToCircle, $convertEllipseToCircle],
            [Rule::ConvertShapesToPath, $convertShapesToPath],
            [Rule::MinifyNumericValues, $minifyNumericValues],
            [Rule::MinifyPathData, $minifyPathData],
            [Rule::RemoveUselessDefs, $removeUselessDefs],
            [Rule::ConvertOneStopGradients, $convertOneStopGradients],
            [Rule::RemoveUnusedIds, $removeUnusedIds],
            [Rule::MergeDuplicateGradients, $mergeDuplicateGradients],
            [Rule::MergeStyles, $mergeStyles],
            [Rule::MigrateToSvg2, $migrateToSvg2],
            [Rule::MinifyStyles, $minifyStyles],
            [Rule::MoveCommonAttributesToGroup, $moveCommonAttributesToGroup],
            [Rule::MoveGroupAttributesToElements, $moveGroupAttributesToElements],
            [Rule::RemoveHiddenElements, $removeHiddenElements],
            [Rule::RemoveUnusedDefinitions, $removeUnusedDefinitions],
            [Rule::SortDefsChildren, $sortDefsChildren],
            [Rule::RemoveScripts, $removeScripts],
            [Rule::RemoveUnsafeUrls, $removeUnsafeUrls],
            [Rule::RemoveForeignContent, $removeForeignContent],
            [Rule::SanitizeStyleSheets, $sanitizeStyleSheets],
            [Rule::RemoveUseBombs, $removeUseBombs],
            [Rule::RemoveUnsafeMarkup, $removeUnsafeMarkup],
            [Rule::RemoveTitles, $removeTitles],
            [Rule::RemoveDescriptions, $removeDescriptions],
            [Rule::RemoveEmptyElements, $removeEmptyElements],
            [Rule::RemoveIneffectiveAttributes, $removeIneffectiveAttributes],
        ];

        foreach ($rules as [$rule, $enabled]) {
            if ($enabled) {
                $this->svgOptimizer->enableRules($rule);
            }
        }

        return $this;
    }

    /**
     * @return $this The SvgOptimizerFacade instance
     */
    public function withAllRules(bool $enable = true): self
    {
        if ($enable) {
            $this->svgOptimizer->enableAllRules();
        }

        return $this;
    }

    /**
     * @param RuleSet ...$ruleSets The rule sets to enable; their risky rules are applied only if risky rules are allowed
     *
     * @return $this The SvgOptimizerFacade instance
     *
     * @no-named-arguments
     */
    public function withRuleSets(RuleSet ...$ruleSets): self
    {
        $this->svgOptimizer->enableRuleSets(...$ruleSets);

        return $this;
    }

    /**
     * @param string $outputPath The file path where the optimized SVG content will be saved
     *
     * @return $this The SvgOptimizerFacade instance
     *
     * @throws \LogicException If the content is saved before optimize() is called
     * @throws IOException     If the output file cannot be written
     */
    public function saveToFile(string $outputPath): self
    {
        $this->svgOptimizer->saveToFile($outputPath);

        return $this;
    }

    /**
     * @return Metrics The metadata associated with the SVG content
     *
     * @throws \LogicException If the metadata cannot be retrieved
     */
    public function getMetaData(): Metrics
    {
        return $this->svgOptimizer->getMetaData();
    }

    /**
     * @return string The optimized SVG content
     *
     * @throws \LogicException If the content is requested before optimize() is called
     */
    public function getContent(): string
    {
        return $this->svgOptimizer->getContent();
    }

    /**
     * @param bool $allowRisky Whether to allow risky rules (default: true)
     *
     * @return $this The SvgOptimizerFacade instance
     */
    public function allowRisky(bool $allowRisky = true): self
    {
        if ($allowRisky) {
            $this->svgOptimizer->allowRisky();
        }

        return $this;
    }
}
