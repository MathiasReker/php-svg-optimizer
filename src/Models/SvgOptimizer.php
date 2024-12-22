<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Models;

use DOMDocument;
use MathiasReker\PhpSvgOptimizer\Contracts\Services\Providers\SvgProviderInterface;
use MathiasReker\PhpSvgOptimizer\Contracts\Services\Rules\SvgOptimizerRuleInterface;
use MathiasReker\PhpSvgOptimizer\Exception\SvgValidationException;
use MathiasReker\PhpSvgOptimizer\Services\Validators\SvgValidator;
use MathiasReker\PhpSvgOptimizer\ValueObjects\MetaDataValueObject;

final class SvgOptimizer
{
    /**
     * Array of optimization rules to be applied to the SVG document.
     *
     * @var SvgOptimizerRuleInterface[] Array of optimization strategies
     */
    private array $rules = [];

    /**
     * The optimized SVG content.
     *
     * @var ?string The SVG content after optimization, or null if not yet optimized
     */
    private ?string $domDocument = null;

    /**
     * The SVG validator used to check the validity of the SVG content.
     *
     * @var SvgValidator The SVG validator
     */
    private readonly SvgValidator $svgValidator;

    /**
     * SvgOptimizer constructor.
     *
     * @param SvgProviderInterface $svgProvider The provider used to get and save SVG content
     */
    public function __construct(
        private readonly SvgProviderInterface $svgProvider,
    ) {
        $this->svgValidator = new SvgValidator();
    }

    /**
     * Add an optimization rule to the optimizer.
     *
     * @param SvgOptimizerRuleInterface $svgOptimizerRule The optimization rule to add
     */
    public function addRule(SvgOptimizerRuleInterface $svgOptimizerRule): void
    {
        $this->rules[] = $svgOptimizerRule;
    }

    /**
     * Optimize the SVG content by applying all added optimization rules.
     *
     * @return SvgOptimizer The current instance of SvgOptimizer for method chaining
     *
     * @throws SvgValidationException If the SVG content is not valid
     */
    public function optimize(): self
    {
        $svgContent = $this->svgProvider->getInputContent();

        if (!$this->svgValidator->isValid($svgContent)) {
            throw new SvgValidationException('The file does not appear to be a valid SVG file.');
        }

        $domDocument = $this->svgProvider->loadContent();
        $this->applyRules($domDocument);
        $this->domDocument = $this->svgProvider->optimize($domDocument)->getOutputContent();

        return $this;
    }

    /**
     * Apply all optimization rules to the provided DOMDocument.
     *
     * @param \DOMDocument $domDocument The DOMDocument instance representing the SVG file to be optimized
     */
    private function applyRules(\DOMDocument $domDocument): void
    {
        foreach ($this->rules as $rule) {
            $rule->optimize($domDocument);
        }
    }

    /**
     * Get metadata related to the SVG content.
     *
     * @return MetaDataValueObject The metadata containing information about the SVG file sizes
     */
    public function getMetaData(): MetaDataValueObject
    {
        return $this->svgProvider->getMetaData();
    }

    /**
     * Get the optimized SVG content.
     *
     * @return string The optimized SVG content, or an empty string if not yet optimized
     */
    public function getContent(): string
    {
        return $this->domDocument ?? '';
    }

    /**
     * Save the optimized SVG content to a file.
     *
     * @param string $outputPath The path to save the optimized SVG content to
     */
    public function saveToFile(string $outputPath): self
    {
        $this->svgProvider->saveToFile($outputPath);

        return $this;
    }

    /**
     * Get the number of optimization rules added to the optimizer.
     *
     * @return int The number of optimization rules
     */
    public function getRulesCount(): int
    {
        return \count($this->rules);
    }
}
