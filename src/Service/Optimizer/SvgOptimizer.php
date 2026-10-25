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
use MathiasReker\PhpSvgOptimizer\Exception\IOException;
use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Exception\SvgValidationException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Pass\PassInterface;
use MathiasReker\PhpSvgOptimizer\Pass\RevertIfLargerPassInterface;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use MathiasReker\PhpSvgOptimizer\Type\Rule;
use MathiasReker\PhpSvgOptimizer\Type\RuleSet;
use MathiasReker\PhpSvgOptimizer\ValueObject\Metrics;

/**
 * @internal
 *
 * @no-named-arguments
 */
final class SvgOptimizer
{
    /** @var list<PassInterface> The passes added one by one */
    private array $passes = [];

    /** @var list<Rule> The rules enabled one by one */
    private array $rules = [];

    /** @var string The SVG content after optimization */
    private string $domDocumentContent = '';

    private bool $allowRisky = false;

    private bool $allRulesEnabled = false;

    /** @var list<RuleSet> */
    private array $enabledRuleSets = [];

    /** @var SvgValidator The SVG validator */
    private readonly SvgValidator $svgValidator;

    private readonly DomDocumentWrapper $domDocumentWrapper;

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
        $this->domDocumentWrapper = new DomDocumentWrapper();
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

    public function allowRisky(): self
    {
        $this->allowRisky = true;

        return $this;
    }

    public function enableAllRules(): self
    {
        $this->allRulesEnabled = true;

        return $this;
    }

    /**
     * @param RuleSet ...$ruleSets The rule sets to enable
     */
    public function enableRuleSets(RuleSet ...$ruleSets): self
    {
        $this->enabledRuleSets = [...$this->enabledRuleSets, ...$ruleSets];

        return $this;
    }

    /**
     * @param Rule ...$rules The rules to enable
     */
    public function enableRules(Rule ...$rules): self
    {
        $this->rules = [...$this->rules, ...$rules];

        return $this;
    }

    /**
     * @param PassInterface $pass The pass to run, whatever the enabled rules
     */
    public function addPass(PassInterface $pass): void
    {
        $this->passes[] = $pass;
    }

    /**
     * @return list<Rule> The rules enabled one by one, and the rules enabled by enableAllRules() or enableRuleSets(),
     *                    with risky rules only if risky rules are allowed
     */
    private function selectedRules(): array
    {
        $rules = $this->rules;

        foreach (Rule::cases() as $rule) {
            if ($this->isSelected($rule) && ($this->allowRisky || !$rule->isRisky())) {
                $rules[] = $rule;
            }
        }

        return $rules;
    }

    private function isSelected(Rule $rule): bool
    {
        if ($this->allRulesEnabled) {
            return true;
        }

        foreach ($this->enabledRuleSets as $enabledRuleSet) {
            if ($enabledRuleSet->contains($rule)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<PassInterface> The added passes and the passes of the selected rules, each pass once, in execution
     *                             order; added passes without a position run last, in the order they were added
     */
    public function getPasses(): array
    {
        $passes = $this->passes;

        foreach ($this->selectedRules() as $rule) {
            foreach ($rule->passes() as $passClass) {
                if (!$this->hasPass($passes, $passClass)) {
                    $passes[] = new $passClass();
                }
            }
        }

        return ExecutionOrder::sort($passes);
    }

    /**
     * @param list<PassInterface>         $passes    The passes to search
     * @param class-string<PassInterface> $passClass The pass to look for
     */
    private function hasPass(array $passes, string $passClass): bool
    {
        foreach ($passes as $pass) {
            if ($pass::class === $passClass) {
                return true;
            }
        }

        return false;
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

        $this->domDocumentContent = $this->svgProvider->optimize($this->applyPasses($domDocument))->getOutputContent();

        $this->isOptimized = true;

        $end = microtime(true);

        $this->optimizationTime = $end - $start;

        return $this;
    }

    /**
     * @return bool True if one or more rules enabled one by one are risky, false otherwise
     */
    private function hasRiskyRules(): bool
    {
        foreach ($this->rules as $rule) {
            if ($rule->isRisky()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     *
     * @return \DOMDocument the optimized document, which is a snapshot taken before a pass whose result was reverted
     *
     * @throws XmlProcessingException
     */
    private function applyPasses(\DOMDocument $domDocument): \DOMDocument
    {
        foreach ($this->getPasses() as $pass) {
            if (!$pass instanceof RevertIfLargerPassInterface) {
                $pass->apply($domDocument);

                continue;
            }

            $snapshot = $this->domDocumentWrapper->copy($domDocument);
            $sizeBeforePass = mb_strlen($this->svgProvider->serialize($domDocument), '8bit');
            $pass->apply($domDocument);

            if (mb_strlen($this->svgProvider->serialize($domDocument), '8bit') > $sizeBeforePass) {
                $domDocument = $snapshot;
            }
        }

        return $domDocument;
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
