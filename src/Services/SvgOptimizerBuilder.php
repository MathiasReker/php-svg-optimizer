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
 * This class is the main entry point for the SVG optimizer.
 */
final readonly class SvgOptimizerBuilder
{
    private SvgOptimizer $svgOptimizer;

    public function __construct(SvgProviderInterface $svgProvider)
    {
        $this->svgOptimizer = new SvgOptimizer($svgProvider);
    }

    /**
     * Remove the title and desc elements from the SVG document.
     */
    public function removeTitleAndDesc(): self
    {
        $this->svgOptimizer->addRule(new RemoveTitleAndDesc());

        return $this;
    }

    /**
     * Remove the metadata from the SVG document.
     */
    public function removeMetadata(): self
    {
        $this->svgOptimizer->addRule(new RemoveMetadata());

        return $this;
    }

    /**
     * Remove all comments from the SVG document.
     */
    public function removeComments(): self
    {
        $this->svgOptimizer->addRule(new RemoveComments());

        return $this;
    }

    /**
     * Remove default attributes from the SVG document.
     */
    public function removeUnnecessaryWhitespace(): self
    {
        $this->svgOptimizer->addRule(new RemoveUnnecessaryWhitespace());

        return $this;
    }

    /**
     * Remove default attributes from the SVG document.
     */
    public function removeDefaultAttributes(): self
    {
        $this->svgOptimizer->addRule(new RemoveDefaultAttributes());

        return $this;
    }

    /**
     * Flatten groups in the SVG document.
     */
    public function flattenGroups(): self
    {
        $this->svgOptimizer->addRule(new FlattenGroups());

        return $this;
    }

    /**
     * Convert colors to hexadecimal values in the SVG document.
     */
    public function convertColorsToHex(): self
    {
        $this->svgOptimizer->addRule(new ConvertColorsToHex());

        return $this;
    }

    /**
     * Minify SVG coordinates in the SVG document.
     */
    public function minifySvgCoordinates(): self
    {
        $this->svgOptimizer->addRule(new MinifySvgCoordinates());

        return $this;
    }

    /**
     * Minify transformations in the SVG document.
     */
    public function minifyTransformations(): self
    {
        $this->svgOptimizer->addRule(new MinifyTransformations());

        return $this;
    }

    /**
     * Optimize the SVG file.
     */
    public function build(): self
    {
        $this->svgOptimizer->optimize();

        return $this;
    }

    /**
     * Get the optimized SVG content.
     */
    public function getContent(): string
    {
        return $this->svgOptimizer->getContent();
    }

    /**
     * Get the metadata about the optimization process.
     */
    public function getMetaData(): MetaDataValueObject
    {
        return $this->svgOptimizer->getMetaData();
    }
}
