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
     * The SVG optimizer instance.
     */
    private SvgOptimizer $svgOptimizer;

    /**
     * SvgOptimizerService constructor.
     */
    public function __construct(SvgProviderInterface $svgProvider)
    {
        $this->svgOptimizer = new SvgOptimizer($svgProvider);
    }

    /**
     * Static factory method to create SvgOptimizerService from a file path.
     */
    public static function fromFile(string $filePath): self
    {
        return new self(new FileProvider($filePath));
    }

    /**
     * Static factory method to create SvgOptimizerService from a string.
     */
    public static function fromString(string $content): self
    {
        return new self(new StringProvider($content));
    }

    /**
     * Optimize the SVG content.
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
     * Add an optimization rule to the SVG optimizer.
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
        bool $flattenGroups = true
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
     * Save the optimized SVG content to a file.
     */
    public function saveToFile(string $outputPath): self
    {
        $this->svgOptimizer->saveToFile($outputPath);

        return $this;
    }

    /**
     * Get metadata related to the SVG content.
     */
    public function getMetaData(): MetaDataValueObject
    {
        return $this->svgOptimizer->getMetaData();
    }

    /**
     * Get the optimized SVG content.
     */
    public function getContent(): string
    {
        return $this->svgOptimizer->getContent();
    }
}
