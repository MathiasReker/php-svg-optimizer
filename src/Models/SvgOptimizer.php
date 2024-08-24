<?php
/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Models;

use DOMDocument;
use MathiasReker\PhpSvgOptimizer\Exceptions\SvgValidationException;
use MathiasReker\PhpSvgOptimizer\Services\Providers\SvgProviderInterface;
use MathiasReker\PhpSvgOptimizer\Services\Rules\SvgOptimizerRuleInterface;
use MathiasReker\PhpSvgOptimizer\Services\Validators\SvgValidator;

class SvgOptimizer
{
    /**
     * @var SvgOptimizerRuleInterface[] array of optimization rules
     */
    private array $rules = [];

    /**
     * @var string the SVG document
     */
    private string $domDocument;

    /**
     * @var SvgValidator the SVG validator
     */
    private readonly SvgValidator $svgValidator;

    /**
     * SvgOptimizer constructor.
     *
     * @param SvgProviderInterface $svgProvider the SVG provider
     */
    public function __construct(private readonly SvgProviderInterface $svgProvider)
    {
        $this->svgValidator = new SvgValidator();
    }

    /**
     * Add an optimization strategy to the optimizer.
     *
     * @param SvgOptimizerRuleInterface $svgOptimizerRule the optimization strategy to add
     */
    public function addRule(SvgOptimizerRuleInterface $svgOptimizerRule): void
    {
        $this->rules[] = $svgOptimizerRule;
    }

    /**
     * Optimize the SVG file.
     *
     * @return SvgOptimizer the SvgOptimizer instance
     */
    public function optimize(): self
    {
        $isValidSvg = $this->svgValidator->isValid($this->svgProvider->getInputContent());

        if (false === $isValidSvg) {
            throw new SvgValidationException('The file does not appear to be a valid SVG file.');
        }

        $domDocument = $this->svgProvider->load();
        $this->applyRules($domDocument);
        $this->domDocument = $this->svgProvider->optimize($domDocument)->getOutputContent();

        return $this;
    }

    /**
     * Apply all optimization strategies to the SVG document.
     *
     * @param \DOMDocument $domDocument the DOMDocument instance representing the SVG file to be optimized
     */
    private function applyRules(\DOMDocument $domDocument): void
    {
        foreach ($this->rules as $rule) {
            $rule->optimize($domDocument);
        }
    }

    /**
     * Get the metadata of the SVG file.
     *
     * @return array{ originalSize: int, optimizedSize: int, savedBytes: int, savedPercentage: float}
     */
    public function getMetaData(): array
    {
        return $this->svgProvider->getMetaData();
    }

    /**
     * Get the optimized SVG content.
     *
     * @return string the optimized SVG content
     */
    public function getContent(): string
    {
        return $this->domDocument;
    }
}
