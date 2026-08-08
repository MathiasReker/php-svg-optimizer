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
use MathiasReker\PhpSvgOptimizer\Service\Data\MetaData;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use MathiasReker\PhpSvgOptimizer\ValueObject\Metrics;

/**
 * @no-named-arguments
 */
final class SvgOptimizer
{
    /** @var list<SvgOptimizerRuleInterface> Array of optimization strategies */
    private array $rules = [];

    /** @var string The SVG content after optimization */
    private string $domDocumentContent = '';

    private bool $allowRisky = false;

    /** @var SvgValidator The SVG validator */
    private readonly SvgValidator $svgValidator;

    /** @var bool True if the SVG content has been optimized, false otherwise */
    private bool $isOptimized = false;

    private float $optimizationTime = 0.0;

    /**
     * @param SvgProviderInterface $svgProvider The provider used to get and save SVG content
     */
    public function __construct(
        private readonly SvgProviderInterface $svgProvider,
    ) {
        $this->svgValidator = new SvgValidator();
    }

    /**
     * @return Metrics The metadata containing information about the SVG file sizes
     *
     * @throws \LogicException If metadata is requested before optimization
     */
    public function getMetaData(): Metrics
    {
        if (false === $this->isOptimized) {
            throw new \LogicException('Metadata is not available before optimization.');
        }

        $metaData = new MetaData(
            mb_strlen($this->svgProvider->getInputContent(), '8bit'),
            mb_strlen($this->svgProvider->getOutputContent(), '8bit'),
            $this->optimizationTime,
        );

        return $metaData->toValueObject();
    }

    /**
     * @return bool True if there are rules, false otherwise
     */
    public function hasRules(): bool
    {
        return $this->getRulesCount() > 0;
    }

    /**
     * @return int The number of optimization rules
     */
    public function getRulesCount(): int
    {
        return \count($this->rules);
    }

    /**
     * @return bool True if risky rules are allowed, false otherwise
     */
    public function isRiskyRulesAllowed(): bool
    {
        return $this->allowRisky;
    }

    public function allowRisky(): self
    {
        $this->allowRisky = true;

        return $this;
    }

    /**
     * @return $this The current instance of SvgOptimizer for method chaining
     *
     * @throws SvgValidationException        If the SVG content is not valid
     * @throws XmlProcessingException        If an error occurs while processing the SVG XML
     * @throws RiskyRulesNotAllowedException
     */
    public function optimize(): self
    {
        $start = microtime(true);

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

        $end = microtime(true);

        $this->optimizationTime = $end - $start;

        return $this;
    }

    /**
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
                $originalContent = $this->checkAndRevert($domDocument, $originalContent);
            }
        }
    }

    /**
     * @throws XmlProcessingException
     */
    private function checkAndRevert(\DOMDocument $domDocument, string $originalContent): string
    {
        $newContent = $this->svgProvider->serialize($domDocument);
        if (mb_strlen($newContent, '8bit') > mb_strlen($originalContent, '8bit')) {
            if ('' !== $originalContent) {
                $domDocument->loadXML($originalContent);
            }

            return $originalContent;
        }

        return $newContent;
    }

    /**
     * @return string The optimized SVG content, or an empty string if not yet optimized
     */
    public function getContent(): string
    {
        return $this->domDocumentContent;
    }

    /**
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
     * @param SvgOptimizerRuleInterface $svgOptimizerRule The optimization rule to add
     */
    public function addRule(SvgOptimizerRuleInterface $svgOptimizerRule): void
    {
        $this->rules[] = $svgOptimizerRule;
    }

    /**
     * @param string $outputPath The path to save the optimized SVG content to
     */
    public function saveToFile(string $outputPath): self
    {
        $this->svgProvider->saveToFile($outputPath);

        return $this;
    }
}
