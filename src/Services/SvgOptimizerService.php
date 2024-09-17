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
    private SvgOptimizer $svgOptimizer;

    /**
     * Constructor for SvgOptimizerBuilder.
     *
     * Initializes the SvgOptimizer with a provided SVG provider.
     *
     * @param SvgProviderInterface $svgProvider The SVG provider to be used by the optimizer
     */
    public function __construct(SvgProviderInterface $svgProvider)
    {
        $this->svgOptimizer = new SvgOptimizer($svgProvider);
    }

    /**
     * Add a rule to remove the title and desc elements from the SVG document.
     *
     * @return $this The current instance for method chaining
     */
    public function removeTitleAndDesc(): self
    {
        $this->svgOptimizer->addRule(new RemoveTitleAndDesc());

        return $this;
    }

    /**
     * Add a rule to remove metadata elements from the SVG document.
     *
     * @return $this The current instance for method chaining
     */
    public function removeMetadata(): self
    {
        $this->svgOptimizer->addRule(new RemoveMetadata());

        return $this;
    }

    /**
     * Add a rule to remove all comments from the SVG document.
     *
     * @return $this The current instance for method chaining
     */
    public function removeComments(): self
    {
        $this->svgOptimizer->addRule(new RemoveComments());

        return $this;
    }

    /**
     * Add a rule to remove unnecessary whitespace from attribute values in the SVG document.
     *
     * @return $this The current instance for method chaining
     */
    public function removeUnnecessaryWhitespace(): self
    {
        $this->svgOptimizer->addRule(new RemoveUnnecessaryWhitespace());

        return $this;
    }

    /**
     * Add a rule to remove default attributes from the SVG document.
     *
     * @return $this The current instance for method chaining
     */
    public function removeDefaultAttributes(): self
    {
        $this->svgOptimizer->addRule(new RemoveDefaultAttributes());

        return $this;
    }

    /**
     * Add a rule to flatten groups in the SVG document.
     *
     * @return $this The current instance for method chaining
     */
    public function flattenGroups(): self
    {
        $this->svgOptimizer->addRule(new FlattenGroups());

        return $this;
    }

    /**
     * Add a rule to convert colors to hexadecimal values in the SVG document.
     *
     * @return $this The current instance for method chaining
     */
    public function convertColorsToHex(): self
    {
        $this->svgOptimizer->addRule(new ConvertColorsToHex());

        return $this;
    }

    /**
     * Add a rule to minify SVG coordinates in the SVG document.
     *
     * @return $this The current instance for method chaining
     */
    public function minifySvgCoordinates(): self
    {
        $this->svgOptimizer->addRule(new MinifySvgCoordinates());

        return $this;
    }

    /**
     * Add a rule to minify transformations in the SVG document.
     *
     * @return $this The current instance for method chaining
     */
    public function minifyTransformations(): self
    {
        $this->svgOptimizer->addRule(new MinifyTransformations());

        return $this;
    }

    /**
     * Optimize the SVG file by applying all added rules.
     *
     * @return $this The current instance for method chaining
     */
    public function optimize(): self
    {
        $this->svgOptimizer->optimize();

        return $this;
    }

    /**
     * Get the optimized SVG content.
     *
     * @return string The optimized SVG content
     */
    public function getContent(): string
    {
        return $this->svgOptimizer->getContent();
    }

    /**
     * Save the optimized SVG content to a file.
     *
     * @param string $outputPath The path to save the optimized SVG content to
     */
    public function saveToFile(string $outputPath): self
    {
        $this->svgOptimizer->saveToFile($outputPath);

        return $this;
    }

    /**
     * Get the metadata about the optimization process.
     *
     * @return MetaDataValueObject The metadata about the optimization
     */
    public function getMetaData(): MetaDataValueObject
    {
        return $this->svgOptimizer->getMetaData();
    }
}
