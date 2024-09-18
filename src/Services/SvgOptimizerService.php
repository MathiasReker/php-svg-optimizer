<?php
/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Services;

use MathiasReker\PhpSvgOptimizer\Contracts\Services\Providers\SvgProviderInterface;
use MathiasReker\PhpSvgOptimizer\Models\MetaDataValueObject;
use MathiasReker\PhpSvgOptimizer\Models\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Services\Providers\FileProvider;
use MathiasReker\PhpSvgOptimizer\Services\Providers\StringProvider;
use MathiasReker\PhpSvgOptimizer\Services\Rules\ConvertColorsToHex;
use MathiasReker\PhpSvgOptimizer\Services\Rules\FlattenGroups;
use MathiasReker\PhpSvgOptimizer\Services\Rules\MinifySvgCoordinates;
use MathiasReker\PhpSvgOptimizer\Services\Rules\MinifyTransformations;
use MathiasReker\PhpSvgOptimizer\Services\Rules\RemoveComments;
use MathiasReker\PhpSvgOptimizer\Services\Rules\RemoveDefaultAttributes;
use MathiasReker\PhpSvgOptimizer\Services\Rules\RemoveMetadata;
use MathiasReker\PhpSvgOptimizer\Services\Rules\RemoveTitleAndDesc;
use MathiasReker\PhpSvgOptimizer\Services\Rules\RemoveUnnecessaryWhitespace;

/**
 * This class is the main entry point for building and configuring the SVG optimizer.
 *
 * It allows you to add various optimization rules to the SVG optimizer and
 * build the final SVG optimizer object that can then be used to optimize SVG content.
 *
 * @see https://github.com/MathiasReker/php-svg-optimizer
 */
final readonly class SvgOptimizerService
{
    /**
     * @var SvgOptimizer The instance responsible for performing SVG optimizations
     */
    private SvgOptimizer $svgOptimizer;

    /**
     * Initializes the SvgOptimizerService with a specified SVG provider.
     *
     * @param SvgProviderInterface $svgProvider The provider for retrieving the SVG content
     */
    private function __construct(SvgProviderInterface $svgProvider)
    {
        $this->svgOptimizer = new SvgOptimizer($svgProvider);
    }

    /**
     * Creates an instance of SvgOptimizerService from a file path.
     *
     * @param string $filePath The path to the SVG file
     *
     * @return static The SvgOptimizerService instance configured for file-based SVG content
     */
    public static function fromFile(string $filePath): self
    {
        return new self(new FileProvider($filePath));
    }

    /**
     * Creates an instance of SvgOptimizerService from a string.
     *
     * @param string $content The SVG content as a string
     *
     * @return static The SvgOptimizerService instance configured for string-based SVG content
     */
    public static function fromString(string $content): self
    {
        return new self(new StringProvider($content));
    }

    /**
     * Optimizes the SVG content using the configured rules.
     *
     * If no rules have been added, a default set of rules will be applied.
     *
     * @return $this The SvgOptimizerService instance
     */
    public function optimize(): self
    {
        if (0 === $this->svgOptimizer->getRulesCount()) {
            $this->withRules();
        }

        $this->svgOptimizer->optimize();

        return $this;
    }

    /**
     * Configures the optimization rules for the SVG optimizer.
     *
     * Each rule can be enabled or disabled via the respective parameters.
     *
     * @param bool $removeTitleAndDesc          Whether to remove the <title> and <desc> elements
     * @param bool $removeComments              Whether to remove XML comments from the SVG
     * @param bool $removeUnnecessaryWhitespace Whether to remove unnecessary whitespace
     * @param bool $removeDefaultAttributes     Whether to remove default attributes from elements
     * @param bool $removeMetadata              Whether to remove metadata elements from the SVG
     * @param bool $convertColorsToHex          Whether to convert colors to hexadecimal format
     * @param bool $minifySvgCoordinates        Whether to minify coordinate values within the SVG
     * @param bool $minifyTransformations       Whether to minify transformation attributes
     * @param bool $flattenGroups               Whether to flatten nested group elements
     *
     * @return $this The SvgOptimizerService instance
     */
    public function withRules(
        bool $removeTitleAndDesc = true,
        bool $removeComments = true,
        bool $removeUnnecessaryWhitespace = true,
        bool $removeDefaultAttributes = true,
        bool $removeMetadata = true,
        bool $convertColorsToHex = true,
        bool $minifySvgCoordinates = true,
        bool $minifyTransformations = true,
        bool $flattenGroups = true,
    ): self {
        $rules = [
            RemoveTitleAndDesc::class => $removeTitleAndDesc,
            RemoveComments::class => $removeComments,
            RemoveUnnecessaryWhitespace::class => $removeUnnecessaryWhitespace,
            RemoveDefaultAttributes::class => $removeDefaultAttributes,
            RemoveMetadata::class => $removeMetadata,
            ConvertColorsToHex::class => $convertColorsToHex,
            MinifySvgCoordinates::class => $minifySvgCoordinates,
            MinifyTransformations::class => $minifyTransformations,
            FlattenGroups::class => $flattenGroups,
        ];

        foreach (array_keys(array_filter($rules)) as $ruleClass) {
            $this->svgOptimizer->addRule(new $ruleClass());
        }

        return $this;
    }

    /**
     * Saves the optimized SVG content to a specified file path.
     *
     * @param string $outputPath The file path where the optimized SVG content will be saved
     *
     * @return $this The SvgOptimizerService instance
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
