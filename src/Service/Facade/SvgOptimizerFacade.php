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
use MathiasReker\PhpSvgOptimizer\Exception\SvgValidationException;
use MathiasReker\PhpSvgOptimizer\Model\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Provider\FileProvider;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertColorsToHex;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertCssClassesToAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertEmptyTagsToSelfClosing;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertInlineStylesToAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\FlattenGroups;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifySvgCoordinates;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyTransformations;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveComments;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDefaultAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDeprecatedAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDoctype;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEnableBackgroundAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveInkscapeFootprints;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveInvisibleCharacters;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveMetadata;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveTitleAndDesc;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnnecessaryWhitespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnsafeElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedMasks;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedNamespaces;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveWidthHeightAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\SortAttributes;
use MathiasReker\PhpSvgOptimizer\ValueObject\MetaDataValueObject;

/**
 * This class is the main entry point for building and configuring the SVG optimizer.
 *
 * It allows you to add various optimization rules to the SVG optimizer and
 * build the final SVG optimizer object that can then be used to optimize SVG content.
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
     * Initializes the SvgOptimizerFacade with a specified SVG provider.
     *
     * @param SvgProviderInterface $svgProvider The provider for retrieving the SVG content
     */
    private function __construct(SvgProviderInterface $svgProvider)
    {
        $this->svgOptimizer = new SvgOptimizer($svgProvider);
    }

    /**
     * Creates an instance of SvgOptimizerFacade from a string.
     *
     * @param string $content The SVG content as a string
     *
     * @return static The SvgOptimizerFacade instance configured for string-based SVG content
     */
    public static function fromString(string $content): self
    {
        return new self(new StringProvider($content));
    }

    /**
     * Creates an instance of SvgOptimizerFacade from a file path.
     *
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
     * Optimizes the SVG content using the configured rules.
     *
     * If no rules have been added, a default set of rules will be applied.
     *
     * @return $this The SvgOptimizerFacade instance
     *
     * @throws SvgValidationException If the SVG content is invalid
     */
    public function optimize(): self
    {
        $this->svgOptimizer->optimize();

        return $this;
    }

    /**
     * Configures the optimization rules for the SVG optimizer.
     *
     * Each rule can be enabled or disabled via the respective parameters.
     *
     * @param bool $convertColorsToHex              Whether to convert colors to hexadecimal format
     * @param bool $convertEmptyTagsToSelfClosing   Whether to convert empty tags to self-closing tags
     * @param bool $flattenGroups                   Whether to flatten nested group elements
     * @param bool $minifySvgCoordinates            Whether to minify coordinate values within the SVG
     * @param bool $minifyTransformations           Whether to minify transformation attributes
     * @param bool $removeComments                  Whether to remove XML comments from the SVG
     * @param bool $removeDefaultAttributes         Whether to remove default attributes from elements
     * @param bool $removeDeprecatedAttributes      Whether to remove the xlink namespace
     * @param bool $removeDoctype                   Whether to remove the DOCTYPE declaration
     * @param bool $removeEmptyAttributes           Whether to remove empty attributes from elements
     * @param bool $removeEnableBackgroundAttribute Whether to remove the enable-background attribute
     * @param bool $removeInkscapeFootprints        Whether to remove Inkscape-specific footprints
     * @param bool $removeInvisibleCharacters       Whether to remove invisible characters
     * @param bool $removeMetadata                  Whether to remove metadata elements from the SVG
     * @param bool $removeTitleAndDesc              Whether to remove the <title> and <desc> elements
     * @param bool $removeUnnecessaryWhitespace     Whether to remove unnecessary whitespace
     * @param bool $removeUnsafeElements            Whether to remove unsafe elements
     * @param bool $removeUnusedNamespaces          Whether to remove unused namespaces
     * @param bool $sortAttributes                  Whether to sort attributes
     *
     * @return $this The SvgOptimizerFacade instance
     */
    public function withRules(
        bool $convertColorsToHex = true,
        bool $convertCssClassesToAttributes = true,
        bool $convertEmptyTagsToSelfClosing = true,
        bool $convertInlineStylesToAttributes = true,
        bool $flattenGroups = true,
        bool $minifySvgCoordinates = true,
        bool $minifyTransformations = true,
        bool $removeComments = true,
        bool $removeDefaultAttributes = true,
        bool $removeDeprecatedAttributes = true,
        bool $removeDoctype = true,
        bool $removeEmptyAttributes = true,
        bool $removeEnableBackgroundAttribute = true,
        bool $removeInkscapeFootprints = true,
        bool $removeInvisibleCharacters = true,
        bool $removeMetadata = true,
        bool $removeTitleAndDesc = true,
        bool $removeUnnecessaryWhitespace = true,
        bool $removeUnsafeElements = false,
        bool $removeUnusedMasks = true,
        bool $removeUnusedNamespaces = true,
        bool $removeWidthHeightAttributes = false,
        bool $sortAttributes = true,
    ): self {
        $rules = [
            ConvertColorsToHex::class => $convertColorsToHex,
            ConvertCssClassesToAttributes::class => $convertCssClassesToAttributes,
            ConvertEmptyTagsToSelfClosing::class => $convertEmptyTagsToSelfClosing,
            ConvertInlineStylesToAttributes::class => $convertInlineStylesToAttributes,
            FlattenGroups::class => $flattenGroups,
            MinifySvgCoordinates::class => $minifySvgCoordinates,
            MinifyTransformations::class => $minifyTransformations,
            RemoveComments::class => $removeComments,
            RemoveDefaultAttributes::class => $removeDefaultAttributes,
            RemoveDeprecatedAttributes::class => $removeDeprecatedAttributes,
            RemoveDoctype::class => $removeDoctype,
            RemoveEmptyAttributes::class => $removeEmptyAttributes,
            RemoveEnableBackgroundAttribute::class => $removeEnableBackgroundAttribute,
            RemoveInkscapeFootprints::class => $removeInkscapeFootprints,
            RemoveInvisibleCharacters::class => $removeInvisibleCharacters,
            RemoveMetadata::class => $removeMetadata,
            RemoveTitleAndDesc::class => $removeTitleAndDesc,
            RemoveUnnecessaryWhitespace::class => $removeUnnecessaryWhitespace,
            RemoveUnsafeElements::class => $removeUnsafeElements,
            RemoveUnusedMasks::class => $removeUnusedMasks,
            RemoveUnusedNamespaces::class => $removeUnusedNamespaces,
            RemoveWidthHeightAttributes::class => $removeWidthHeightAttributes,
            SortAttributes::class => $sortAttributes,
        ];

        $this->svgOptimizer->configureRules($rules);

        return $this;
    }

    /**
     * Saves the optimized SVG content to a specified file path.
     *
     * @param string $outputPath The file path where the optimized SVG content will be saved
     *
     * @return $this The SvgOptimizerFacade instance
     */
    public function saveToFile(string $outputPath): self
    {
        $this->svgOptimizer->saveToFile($outputPath);

        return $this;
    }

    /**
     * Retrieves metadata related to the SVG content.
     *
     * @return MetaDataValueObject The metadata associated with the SVG content
     *
     * @throws \LogicException If the metadata cannot be retrieved
     */
    public function getMetaData(): MetaDataValueObject
    {
        return $this->svgOptimizer->getMetaData();
    }

    /**
     * Retrieves the optimized SVG content as a string.
     *
     * @return string The optimized SVG content
     */
    public function getContent(): string
    {
        return $this->svgOptimizer->getContent();
    }
}
