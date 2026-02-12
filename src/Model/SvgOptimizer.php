<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Model;

use MathiasReker\PhpSvgOptimizer\Contract\Service\Provider\SvgProviderInterface;
use MathiasReker\PhpSvgOptimizer\Contract\Service\Rule\SvgOptimizerRuleInterface;
use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Exception\SvgValidationException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use MathiasReker\PhpSvgOptimizer\ValueObject\MetaDataValueObject;

/**
 * @no-named-arguments
 */
final class SvgOptimizer
{
    /**
     * Array of optimization rules to be applied to the SVG document.
     *
     * @var list<SvgOptimizerRuleInterface> Array of optimization strategies
     */
    private array $rules = [];

    /**
     * The optimized SVG content.
     *
     * @var string The SVG content after optimization
     */
    private string $domDocumentContent = '';

    /**
     * Indicates whether risky optimization rules are allowed.
     */
    private bool $allowRisky = false;

    /**
     * The SVG validator used to check the validity of the SVG content.
     *
     * @var SvgValidator The SVG validator
     */
    private readonly SvgValidator $svgValidator;

    /**
     * Flag indicating whether the SVG content has been optimized.
     *
     * @var bool True if the SVG content has been optimized, false otherwise
     */
    private bool $isOptimized = false;

    /**
     * Constructor for SvgOptimizer.
     *
     * @param SvgProviderInterface $svgProvider The provider used to get and save SVG content
     */
    public function __construct(
        private readonly SvgProviderInterface $svgProvider,
    ) {
        $this->svgValidator = new SvgValidator();
    }

    /**
     * Get metadata related to the SVG content.
     *
     * @return MetaDataValueObject The metadata containing information about the SVG file sizes
     *
     * @throws \LogicException If metadata is requested before optimization
     */
    public function getMetaData(): MetaDataValueObject
    {
        if (false === $this->isOptimized) {
            throw new \LogicException('Metadata is not available before optimization.');
        }

        return $this->svgProvider->getMetaData();
    }

    /**
     * Check if there are any optimization rules configured.
     *
     * @return bool True if there are rules, false otherwise
     */
    public function hasRules(): bool
    {
        return $this->getRulesCount() > 0;
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

    /**
     * Checks whether risky optimization rules are allowed.
     *
     * @return bool True if risky rules are allowed, false otherwise
     */
    public function isRiskyRulesAllowed(): bool
    {
        return $this->allowRisky;
    }

    /**
     * Enables the use of risky optimization rules.
     *
     * Risky rules are disabled by default because they may alter the SVG in ways
     * that impact compatibility, rendering behavior, or semantic meaning. Call
     * this method explicitly to allow such rules to run.
     *
     * @return $this
     */
    public function allowRisky(): self
    {
        $this->allowRisky = true;

        return $this;
    }

    /**
     * Optimize the SVG content by applying all added optimization rules.
     *
     * @return $this The current instance of SvgOptimizer for method chaining
     *
     * @throws SvgValidationException        If the SVG content is not valid
     * @throws XmlProcessingException        If an error occurs while processing the SVG XML
     * @throws RiskyRulesNotAllowedException
     */
    public function optimize(): self
    {
        if ($this->hasRiskyRules() && !$this->allowRisky) {
            throw new RiskyRulesNotAllowedException('Risky optimization rules are disabled. Enable them to use these rules.');
        }

        $content = $this->svgProvider->getInputContent();

        if (!$this->svgValidator->isValid($content)) {
            throw new SvgValidationException('The file does not appear to be a valid SVG file.');
        }

        $domDocument = $this->svgProvider->loadContent();
        $this->applyRules($domDocument);
        $this->domDocumentContent = $this->svgProvider->optimize($domDocument)->getOutputContent();

        $this->isOptimized = true;

        return $this;
    }

    /**
     * Determines whether any of the configured rules are classified as risky.
     *
     * A rule is considered risky if its class implements SvgOptimizerRuleInterface::isRisky()
     * and that method returns true.
     *
     * @return bool True if one or more configured rules are risky, false otherwise
     */
    public function hasRiskyRules(): bool
    {
        foreach ($this->rules as $rule) {
            if ($rule::isRisky()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apply all configured optimization rules to the provided \DOMDocument.
     *
     * Each rule is applied in the order it was added. If a rule has
     * `shouldCheckSize()` enabled, the method compares the SVG content
     * size before and after applying the rule:
     *
     * - If the rule reduces the file size, the change is kept and
     *   considered the new "best" version of the SVG content.
     * - If the rule increases or does not improve the file size, the
     *   \DOMDocument is reverted to the previous best version.
     *
     * This ensures that only optimizations that improve (reduce) the SVG
     * size are retained, while preserving improvements from earlier rules.
     *
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     *
     * @throws XmlProcessingException
     */
    private function applyRules(\DOMDocument $domDocument): void
    {
        $originalContent = $this->svgProvider->serialize($domDocument);

        foreach ($this->rules as $rule) {
            if ($rule::isRisky() && !$this->allowRisky) {
                continue;
            }

            $rule->optimize($domDocument);

            if ($rule::shouldCheckSize()) {
                $newContent = $this->svgProvider->serialize($domDocument);
                if (mb_strlen($newContent, '8bit') > mb_strlen($originalContent, '8bit')) {
                    $domDocument->loadXML($originalContent);
                } else {
                    $originalContent = $newContent;
                }
            }
        }
    }

    /**
     * Get the optimized SVG content.
     *
     * @return string The optimized SVG content, or an empty string if not yet optimized
     */
    public function getContent(): string
    {
        return $this->domDocumentContent;
    }

    /**
     * Configure which rules to use based on flags.
     *
     * @param array<class-string<SvgOptimizerRuleInterface>, bool> $ruleFlags
     */
    public function configureRules(array $ruleFlags): void
    {
        foreach ($ruleFlags as $ruleClass => $enabled) {
            if ($enabled) {
                $this->addRule(new $ruleClass());
            }
        }
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
     * Save the optimized SVG content to a file.
     *
     * @param string $outputPath The path to save the optimized SVG content to
     */
    public function saveToFile(string $outputPath): self
    {
        $this->svgProvider->saveToFile($outputPath);

        return $this;
    }
}
