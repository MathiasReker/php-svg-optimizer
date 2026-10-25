<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Optimizer;

use MathiasReker\PhpSvgOptimizer\Contract\Service\Provider\SvgProviderInterface;
use MathiasReker\PhpSvgOptimizer\Contract\Service\Rule\SvgOptimizerRuleInterface;
use MathiasReker\PhpSvgOptimizer\Exception\IOException;
use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Exception\SvgValidationException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Service\Processor\Trait\XmlLoaderTrait;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use MathiasReker\PhpSvgOptimizer\Type\Rule;
use MathiasReker\PhpSvgOptimizer\ValueObject\Metrics;

/**
 * @internal
 *
 * @no-named-arguments
 */
final class SvgOptimizer
{
    use XmlLoaderTrait;

    /** @var list<SvgOptimizerRuleInterface> Array of optimization strategies */
    private array $rules = [];

    /** @var string The SVG content after optimization */
    private string $domDocumentContent = '';

    private bool $allowRisky = false;

    private bool $allRulesEnabled = false;

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

        $originalSize = mb_strlen($this->svgProvider->getInputContent(), '8bit');
        $optimizedSize = mb_strlen($this->svgProvider->getOutputContent(), '8bit');
        $savedBytes = $originalSize - $optimizedSize;

        return new Metrics(
            $originalSize,
            $optimizedSize,
            $savedBytes,
            $savedBytes / $originalSize * 100,
            $this->optimizationTime,
        );
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

    public function allowRisky(): self
    {
        $this->allowRisky = true;

        if ($this->allRulesEnabled) {
            $this->configureRules($this->allRuleFlags());
        }

        return $this;
    }

    public function enableAllRules(): self
    {
        $this->allRulesEnabled = true;
        $this->configureRules($this->allRuleFlags());

        return $this;
    }

    /**
     * @return array<class-string<SvgOptimizerRuleInterface>, bool> Every rule, with risky rules only if risky rules are allowed
     */
    private function allRuleFlags(): array
    {
        $ruleFlags = [];

        foreach (Rule::cases() as $rule) {
            /** @var class-string<SvgOptimizerRuleInterface> $ruleClass */
            $ruleClass = $rule->value;
            $ruleFlags[$ruleClass] = $this->allowRisky || !$ruleClass::isRisky();
        }

        return $ruleFlags;
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

        try {
            $domDocument = $this->svgProvider->loadContent();
        } catch (XmlProcessingException $xmlProcessingException) {
            throw new SvgValidationException('The file does not appear to be a valid SVG file.', 0, $xmlProcessingException);
        }

        if (!$this->svgValidator->isValidDocument($domDocument)) {
            throw new SvgValidationException('The file does not appear to be a valid SVG file.');
        }

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
    private function hasRiskyRules(): bool
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
        foreach ($this->rules as $rule) {
            if (!$rule::shouldCheckSize()) {
                $rule->optimize($domDocument);

                continue;
            }

            $contentBeforeRule = $this->svgProvider->serialize($domDocument);
            $rule->optimize($domDocument);
            $this->revertIfLarger($domDocument, $contentBeforeRule);
        }
    }

    /**
     * @throws XmlProcessingException
     */
    private function revertIfLarger(\DOMDocument $domDocument, string $contentBeforeRule): void
    {
        $newContent = $this->svgProvider->serialize($domDocument);

        if ('' !== $contentBeforeRule && mb_strlen($newContent, '8bit') > mb_strlen($contentBeforeRule, '8bit')) {
            $this->loadXmlSilently($domDocument, $contentBeforeRule);
        }
    }

    /**
     * @return string The optimized SVG content
     *
     * @throws \LogicException If the content is requested before optimization
     */
    public function getContent(): string
    {
        if (!$this->isOptimized) {
            throw new \LogicException('Content is not available before optimization.');
        }

        return $this->domDocumentContent;
    }

    /**
     * @param array<class-string<SvgOptimizerRuleInterface>, bool> $ruleFlags
     */
    public function configureRules(array $ruleFlags): void
    {
        foreach ($ruleFlags as $ruleClass => $enabled) {
            if ($enabled && !$this->hasRule($ruleClass)) {
                $this->addRule(new $ruleClass());
            }
        }

        usort(
            $this->rules,
            static fn (SvgOptimizerRuleInterface $a, SvgOptimizerRuleInterface $b): int => self::priority($a) <=> self::priority($b),
        );
    }

    /**
     * @param class-string<SvgOptimizerRuleInterface> $ruleClass
     */
    private function hasRule(string $ruleClass): bool
    {
        foreach ($this->rules as $rule) {
            if ($rule::class === $ruleClass) {
                return true;
            }
        }

        return false;
    }

    private static function priority(SvgOptimizerRuleInterface $svgOptimizerRule): int
    {
        return Rule::tryFrom($svgOptimizerRule::class)?->priority() ?? \PHP_INT_MAX;
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
     *
     * @throws \LogicException If the content is saved before optimization
     * @throws IOException     If the output file cannot be written
     */
    public function saveToFile(string $outputPath): self
    {
        if (!$this->isOptimized) {
            throw new \LogicException('Content cannot be saved before optimization.');
        }

        $this->svgProvider->saveToFile($outputPath);

        return $this;
    }
}
