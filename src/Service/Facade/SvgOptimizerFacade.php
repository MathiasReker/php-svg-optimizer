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
use MathiasReker\PhpSvgOptimizer\Contract\Service\Rule\SvgOptimizerRuleInterface;
use MathiasReker\PhpSvgOptimizer\Exception\FileNotFoundException;
use MathiasReker\PhpSvgOptimizer\Exception\IOException;
use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Exception\SvgValidationException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Model\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Provider\FileProvider;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Type\Rule;
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
     * Configures the optimization rules for the SVG optimizer.
     *
     * Each rule can be enabled or disabled via the respective parameters.
     *
     * @param bool $convertColorsToHex              Whether to convert colors to hexadecimal format
     * @param bool $convertCssClassesToAttributes   Whether to convert CSS classes to attributes
     * @param bool $convertEmptyTagsToSelfClosing   Whether to convert empty tags to self-closing tags
     * @param bool $convertInlineStylesToAttributes Whether to convert inline styles to attributes
     * @param bool $fixAttributeNames               Whether to fix typos in attrubutes names
     * @param bool $flattenGroups                   Whether to flatten nested group elements
     * @param bool $minifySvgCoordinates            Whether to minify coordinate values within the SVG
     * @param bool $minifyTransformations           Whether to minify transformation attributes
     * @param bool $removeAriaAndRole               Whether to remove aria and role attributes
     * @param bool $removeComments                  Whether to remove XML comments from the SVG
     * @param bool $removeDataAttributes            Whether to remove data-* attributes from elements
     * @param bool $removeDefaultAttributes         Whether to remove default attributes from elements
     * @param bool $removeDeprecatedAttributes      Whether to remove the xlink namespace
     * @param bool $removeDoctype                   Whether to remove the DOCTYPE declaration
     * @param bool $removeDuplicateElements         Whether to remove duplicate elements
     * @param bool $removeEmptyAttributes           Whether to remove empty attributes from elements
     * @param bool $removeEmptyGroups               Whether to remove empty groups
     * @param bool $removeEmptyTextAttributes       Whether to remove empty text attributes
     * @param bool $removeEnableBackgroundAttribute Whether to remove the enable-background attribute
     * @param bool $removeInkscapeFootprints        Whether to remove Inkscape-specific footprints
     * @param bool $removeInvisibleCharacters       Whether to remove invisible characters
     * @param bool $removeMetadata                  Whether to remove metadata elements from the SVG
     * @param bool $removeNonStandardAttributes     Whether to remove non-standard attributes that are not widely supported in SVG
     * @param bool $removeNonStandardTags           Whether to remove non-standard tags that are not part of the official SVG specification
     * @param bool $removeTitleAndDesc              Whether to remove the <title> and <desc> elements
     * @param bool $removeUnnecessaryWhitespace     Whether to remove unnecessary whitespace
     * @param bool $removeUnsafeElements            Whether to remove unsafe elements
     * @param bool $removeUnusedMasks               Whether to remove unused masks
     * @param bool $removeUnusedNamespaces          Whether to remove unused namespaces
     * @param bool $removeWidthHeightAttributes     Whether to remove width and height attributes
     * @param bool $scopeSvgStyles                  Whether to rewrite IDs and class names in SVG styles to prevent conflicts
     * @param bool $sortAttributes                  Whether to sort attributes
     *
     * @return $this The SvgOptimizerFacade instance
     */
    public function withRules(
        bool $convertColorsToHex = false,
        bool $convertCssClassesToAttributes = false,
        bool $convertEmptyTagsToSelfClosing = false,
        bool $convertInlineStylesToAttributes = false,
        bool $fixAttributeNames = false,
        bool $flattenGroups = false,
        bool $minifySvgCoordinates = false,
        bool $minifyTransformations = false,
        bool $removeAriaAndRole = false,
        bool $removeComments = false,
        bool $removeDataAttributes = false,
        bool $removeDefaultAttributes = false,
        bool $removeDeprecatedAttributes = false,
        bool $removeDoctype = false,
        bool $removeDuplicateElements = false,
        bool $removeEmptyAttributes = false,
        bool $removeEmptyGroups = false,
        bool $removeEmptyTextAttributes = false,
        bool $removeEnableBackgroundAttribute = false,
        bool $removeInkscapeFootprints = false,
        bool $removeInvisibleCharacters = false,
        bool $removeMetadata = false,
        bool $removeNonStandardAttributes = false,
        bool $removeNonStandardTags = false,
        bool $removeTitleAndDesc = false,
        bool $removeUnnecessaryWhitespace = false,
        bool $removeUnsafeElements = false,
        bool $removeUnusedMasks = false,
        bool $removeUnusedNamespaces = false,
        bool $removeWidthHeightAttributes = false,
        bool $scopeSvgStyles = false,
        bool $sortAttributes = false,
    ): self {
        $rules = [
            Rule::ConvertColorsToHex->value => $convertColorsToHex,
            Rule::ConvertCssClassesToAttributes->value => $convertCssClassesToAttributes,
            Rule::ConvertEmptyTagsToSelfClosing->value => $convertEmptyTagsToSelfClosing,
            Rule::ConvertInlineStylesToAttributes->value => $convertInlineStylesToAttributes,
            Rule::FixAttributeNames->value => $fixAttributeNames,
            Rule::FlattenGroups->value => $flattenGroups,
            Rule::MinifySvgCoordinates->value => $minifySvgCoordinates,
            Rule::MinifyTransformations->value => $minifyTransformations,
            Rule::RemoveAriaAndRole->value => $removeAriaAndRole,
            Rule::RemoveComments->value => $removeComments,
            Rule::RemoveDataAttributes->value => $removeDataAttributes,
            Rule::RemoveDefaultAttributes->value => $removeDefaultAttributes,
            Rule::RemoveDeprecatedAttributes->value => $removeDeprecatedAttributes,
            Rule::RemoveDoctype->value => $removeDoctype,
            Rule::RemoveDuplicateElements->value => $removeDuplicateElements,
            Rule::RemoveEmptyAttributes->value => $removeEmptyAttributes,
            Rule::RemoveEmptyGroups->value => $removeEmptyGroups,
            Rule::RemoveEmptyTextElements->value => $removeEmptyTextAttributes,
            Rule::RemoveEnableBackgroundAttribute->value => $removeEnableBackgroundAttribute,
            Rule::RemoveInkscapeFootprints->value => $removeInkscapeFootprints,
            Rule::RemoveInvisibleCharacters->value => $removeInvisibleCharacters,
            Rule::RemoveMetadata->value => $removeMetadata,
            Rule::RemoveNonStandardAttributes->value => $removeNonStandardAttributes,
            Rule::RemoveNonStandardTags->value => $removeNonStandardTags,
            Rule::RemoveTitleAndDesc->value => $removeTitleAndDesc,
            Rule::RemoveUnnecessaryWhitespace->value => $removeUnnecessaryWhitespace,
            Rule::RemoveUnsafeElements->value => $removeUnsafeElements,
            Rule::RemoveUnusedMasks->value => $removeUnusedMasks,
            Rule::RemoveUnusedNamespaces->value => $removeUnusedNamespaces,
            Rule::RemoveWidthHeightAttributes->value => $removeWidthHeightAttributes,
            Rule::ScopeSvgStyles->value => $scopeSvgStyles,
            Rule::SortAttributes->value => $sortAttributes,
        ];

        $this->svgOptimizer->configureRules($rules);

        return $this;
    }

    /**
     * Enables all available optimization rules for the SVG optimizer.
     *
     * This method will activate every rule that is either non-risky or, if risky rules
     * are allowed, will also include risky rules. It provides a convenient way to ensure
     * that the SVG content is fully optimized according to all applicable rules.
     *
     * @return $this The SvgOptimizerFacade instance
     */
    public function withAllRules(bool $enable = true): self
    {
        if (!$enable) {
            return $this;
        }

        $allowRiskyRules = $this->svgOptimizer->isRiskyRulesAllowed();

        $rules = [];

        foreach (Rule::cases() as $rule) {
            /** @var class-string<SvgOptimizerRuleInterface> $ruleClass */
            $ruleClass = $rule->value;

            if ($allowRiskyRules || !$ruleClass::isRisky()) {
                $rules[$ruleClass] = true;
            }
        }

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

    /**
     * Enables or disables the use of risky optimization rules.
     *
     * Risky rules can potentially change the visual rendering of the SVG.
     * Use with caution.
     *
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
