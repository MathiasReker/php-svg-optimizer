<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer;

use MathiasReker\PhpSvgOptimizer\Exception\InvalidSvgException;
use MathiasReker\PhpSvgOptimizer\Exception\IOException;
use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\File\SvgFile;
use MathiasReker\PhpSvgOptimizer\Pass\PassInterface;
use MathiasReker\PhpSvgOptimizer\Pass\Pipeline;

/**
 * @api
 *
 * @no-named-arguments
 */
final class SvgOptimizer
{
    /** @var list<Rule> */
    private array $rules = [];

    /** @var list<Rule> */
    private array $excludedRules = [];

    /** @var list<RuleSet> */
    private array $ruleSets = [];

    private bool $defaultRules = false;

    private bool $riskyRulesAllowed = false;

    /**
     * @param Rule ...$rules the rules to select; a risky rule requires risky rules to be allowed
     */
    public function withRules(Rule ...$rules): self
    {
        $svgOptimizer = clone $this;
        $svgOptimizer->rules = [...$this->rules, ...$rules];

        return $svgOptimizer;
    }

    /**
     * @param Rule ...$rules the rules to leave out, even if a rule set or withRules() selects them
     */
    public function withoutRules(Rule ...$rules): self
    {
        $svgOptimizer = clone $this;
        $svgOptimizer->excludedRules = [...$this->excludedRules, ...$rules];

        return $svgOptimizer;
    }

    /**
     * @param RuleSet ...$ruleSets the rule sets to select; their risky rules only run when risky rules are allowed
     */
    public function withRuleSets(RuleSet ...$ruleSets): self
    {
        $svgOptimizer = clone $this;
        $svgOptimizer->ruleSets = [...$this->ruleSets, ...$ruleSets];

        return $svgOptimizer;
    }

    public function withDefaultRules(): self
    {
        $svgOptimizer = clone $this;
        $svgOptimizer->defaultRules = true;

        return $svgOptimizer;
    }

    /**
     * @param bool $allowed true to run the risky rules that are selected
     */
    public function withRiskyRulesAllowed(bool $allowed = true): self
    {
        $svgOptimizer = clone $this;
        $svgOptimizer->riskyRulesAllowed = $allowed;

        return $svgOptimizer;
    }

    /**
     * @param string $path the SVG file to optimize
     *
     * @throws IOException                   If the file does not exist or cannot be read
     * @throws InvalidSvgException           If the file is not a well-formed SVG document
     * @throws RiskyRulesNotAllowedException If withRules() selects a risky rule and risky rules are not allowed
     */
    public function optimizeFile(string $path): OptimizationResult
    {
        return $this->optimizeString(SvgFile::read($path));
    }

    /**
     * @param string $svg the SVG to optimize
     *
     * @throws InvalidSvgException           If the content is not a well-formed SVG document
     * @throws RiskyRulesNotAllowedException If withRules() selects a risky rule and risky rules are not allowed
     */
    public function optimizeString(string $svg): OptimizationResult
    {
        $start = microtime(true);
        $content = (new Pipeline($this->passes()))->run($svg);

        return new OptimizationResult(
            $content,
            new Metrics(mb_strlen($svg, '8bit'), mb_strlen($content, '8bit'), microtime(true) - $start),
        );
    }

    /**
     * @return list<PassInterface> the passes of the selected rules, each pass once
     *
     * @throws RiskyRulesNotAllowedException If withRules() selects a risky rule and risky rules are not allowed
     */
    private function passes(): array
    {
        $passes = [];

        foreach ($this->selectedRules() as $rule) {
            foreach ($rule->getPassClasses() as $passClass) {
                $passes[$passClass] ??= new $passClass();
            }
        }

        return array_values($passes);
    }

    /**
     * @return list<Rule> the selected rules, each rule once
     *
     * @throws RiskyRulesNotAllowedException If withRules() selects a risky rule and risky rules are not allowed
     */
    private function selectedRules(): array
    {
        $explicitRules = $this->included($this->rules);
        $riskyRules = array_values(array_filter($explicitRules, static fn (Rule $rule): bool => $rule->isRisky()));

        if (!$this->riskyRulesAllowed && [] !== $riskyRules) {
            throw new RiskyRulesNotAllowedException($riskyRules);
        }

        $setRules = array_filter(
            $this->included($this->ruleSetRules()),
            fn (Rule $rule): bool => $this->riskyRulesAllowed || !$rule->isRisky(),
        );

        return array_values([...$explicitRules, ...$setRules]);
    }

    /**
     * @return list<Rule> the rules of the rule sets selected by withRuleSets() and withDefaultRules()
     */
    private function ruleSetRules(): array
    {
        $ruleSets = $this->defaultRules ? [...RuleSet::defaults(), ...$this->ruleSets] : $this->ruleSets;

        return array_merge(...array_map(static fn (RuleSet $ruleSet): array => $ruleSet->getRules(), $ruleSets));
    }

    /**
     * @param list<Rule> $rules the rules to filter
     *
     * @return array<string, Rule> the rules that withoutRules() does not exclude, each rule once, by name
     */
    private function included(array $rules): array
    {
        $included = [];

        foreach ($rules as $rule) {
            if (!\in_array($rule, $this->excludedRules, true)) {
                $included[$rule->value] = $rule;
            }
        }

        return $included;
    }
}
