<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Facade;

use MathiasReker\PhpSvgOptimizer\Exception\FileNotFoundException;
use MathiasReker\PhpSvgOptimizer\Exception\IOException;
use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Exception\SvgValidationException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Service\Facade\SvgOptimizerFacade;
use MathiasReker\PhpSvgOptimizer\Service\Optimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\AbstractProvider;
use MathiasReker\PhpSvgOptimizer\Service\Provider\FileProvider;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertColorsToHex;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertCssClassesToAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertEllipseToCircle;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertInlineStylesToAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertOneStopGradients;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertShapesToPath;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgInlineStyleProperty;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\FixAttributeNames;
use MathiasReker\PhpSvgOptimizer\Service\Rule\FlattenGroups;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyListValues;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyNumericValues;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyPathData;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifySvgCoordinates;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyTransformations;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveAriaAndRole;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveComments;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDataAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDefaultAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDeprecatedAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDescriptions;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDuplicateElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyContainers;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyTextElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEnableBackgroundAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveForeignContent;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveInkscapeFootprints;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveInvisibleCharacters;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveMetadata;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveNonStandardAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveNonStandardTags;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveScripts;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveStyleTypeAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveTitles;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnnecessaryWhitespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnsafeMarkup;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnsafeUrls;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedIds;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedNamespaces;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUseBombs;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUselessDefs;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveWidthHeightAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveXmlSpaceDefault;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveZeroSizeShapes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\SanitizeStyleSheets;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ScopeSvgStyles;
use MathiasReker\PhpSvgOptimizer\Service\Rule\SortAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use MathiasReker\PhpSvgOptimizer\Type\Rule;
use MathiasReker\PhpSvgOptimizer\Type\RuleSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @no-named-arguments
 */
#[CoversClass(ExecutionOrder::class)]
#[CoversClass(RemoveDescriptions::class)]
#[CoversClass(RemoveTitles::class)]
#[CoversClass(SvgOptimizerFacade::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(RuleSet::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(AbstractProvider::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(ConvertColorsToHex::class)]
#[CoversClass(RemoveWidthHeightAttributes::class)]
#[CoversClass(RemoveXmlSpaceDefault::class)]
#[CoversClass(RemoveZeroSizeShapes::class)]
#[CoversClass(FileProvider::class)]
#[CoversClass(RemoveEnableBackgroundAttribute::class)]
#[CoversClass(RemoveComments::class)]
#[CoversClass(ConvertCssClassesToAttributes::class)]
#[CoversClass(ConvertEllipseToCircle::class)]
#[CoversClass(SvgInlineStyleProperty::class)]
#[CoversClass(ConvertInlineStylesToAttributes::class)]
#[CoversClass(ConvertOneStopGradients::class)]
#[CoversClass(ConvertShapesToPath::class)]
#[CoversClass(SvgAttribute::class)]
#[CoversClass(SvgNamespace::class)]
#[CoversClass(FixAttributeNames::class)]
#[CoversClass(FlattenGroups::class)]
#[CoversClass(MinifyListValues::class)]
#[CoversClass(MinifyNumericValues::class)]
#[CoversClass(MinifyPathData::class)]
#[CoversClass(MinifySvgCoordinates::class)]
#[CoversClass(MinifyTransformations::class)]
#[CoversClass(RemoveAriaAndRole::class)]
#[CoversClass(RemoveDataAttributes::class)]
#[CoversClass(RemoveDefaultAttributes::class)]
#[CoversClass(RemoveDeprecatedAttributes::class)]
#[CoversClass(RemoveDuplicateElements::class)]
#[CoversClass(RemoveEmptyAttributes::class)]
#[CoversClass(RemoveEmptyContainers::class)]
#[CoversClass(RemoveEmptyTextElements::class)]
#[CoversClass(RemoveInkscapeFootprints::class)]
#[CoversClass(RemoveInvisibleCharacters::class)]
#[CoversClass(RemoveMetadata::class)]
#[CoversClass(RemoveUnnecessaryWhitespace::class)]
#[CoversClass(RemoveForeignContent::class)]
#[CoversClass(RemoveScripts::class)]
#[CoversClass(RemoveUnsafeMarkup::class)]
#[CoversClass(RemoveUnsafeUrls::class)]
#[CoversClass(RemoveUseBombs::class)]
#[CoversClass(SanitizeStyleSheets::class)]
#[CoversClass(RemoveUnusedIds::class)]
#[CoversClass(RemoveUnusedNamespaces::class)]
#[CoversClass(RemoveUselessDefs::class)]
#[CoversClass(ScopeSvgStyles::class)]
#[CoversClass(SortAttributes::class)]
#[CoversClass(RemoveNonStandardAttributes::class)]
#[CoversClass(RemoveNonStandardTags::class)]
#[CoversClass(RemoveStyleTypeAttributes::class)]
final class SvgOptimizerFacadeTest extends TestCase
{
    private string $sampleSvg;

    private string $tempFilePath;

    private const string RULE_SET_SAMPLE_SVG = '<!DOCTYPE svg><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" width="100" height="100" viewBox="0 0 100 100" data-x="1" enable-background="new" inkscape:version="1"><!-- comment --><metadata>m</metadata><title>T</title><style type="text/css"> .a { fill : red ; } </style><style>.b{fill:blue}</style><script>alert(1)</script><foreignObject><p xmlns="http://www.w3.org/1999/xhtml">x</p></foreignObject><defs><linearGradient id="g"><stop stop-color="#ff0000"/></linearGradient><rect id="unused" width="1" height="1"/></defs><g fill="red" x="1"><rect class="a" width="10.50" height="10" style="stroke-width:1" onclick="x()"/><ellipse rx="5" ry="5" fill="rgb(0, 0, 255)"/><polygon points="0, 0, 1, 1, 2, 0"/></g><a xlink:href="javascript:alert(1)"><rect width="0" height="1"/></a><use xlink:href="#g" transform="translate(0, 0)"/><text xml:space="default"> </text><path d="M 0 0 L 10 10" aria-label="p" role="img" fill="url(#g)"/></svg>';

    /**
     * @param list<RuleSet> $ruleSets   The rule sets to enable
     * @param bool          $allowRisky Whether risky rules are allowed
     *
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[DataProvider('provideWithRuleSetsAppliesExactlyTheRulesOfTheSetsCases')]
    #[Test]
    public function withRuleSetsAppliesExactlyTheRulesOfTheSets(array $ruleSets, bool $allowRisky): void
    {
        $rules = array_values(array_filter(
            Rule::cases(),
            static fn (Rule $rule): bool => [] !== array_filter($ruleSets, static fn (RuleSet $ruleSet): bool => \in_array($rule, $ruleSet->rules(), true))
                && ($allowRisky || !$rule->isRisky()),
        ));

        foreach ($this->ruleSetCorpus() as $svg) {
            $svgOptimizer = new SvgOptimizer(new StringProvider($svg));
            $svgOptimizer->enableRules(...$rules);

            if ($allowRisky) {
                $svgOptimizer->allowRisky();
            }

            $expected = $svgOptimizer->optimize()->getContent();

            self::assertSame($expected, SvgOptimizerFacade::fromString($svg)->allowRisky($allowRisky)->withRuleSets(...$ruleSets)->optimize()->getContent());
            self::assertSame($expected, SvgOptimizerFacade::fromString($svg)->withRuleSets(...$ruleSets)->allowRisky($allowRisky)->optimize()->getContent());
        }
    }

    /**
     * @return iterable<string, array{list<RuleSet>, bool}>
     */
    public static function provideWithRuleSetsAppliesExactlyTheRulesOfTheSetsCases(): iterable
    {
        foreach (RuleSet::cases() as $ruleSet) {
            yield $ruleSet->value => [[$ruleSet], false];

            yield $ruleSet->value . ', risky' => [[$ruleSet], true];
        }

        yield 'security and minification' => [[RuleSet::Security, RuleSet::Minification], false];

        yield 'cleanup and structure, risky' => [[RuleSet::Cleanup, RuleSet::Structure], true];

        yield 'no set' => [[], true];
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function withRuleSetsChangesTheSampleForEverySet(): void
    {
        $withoutRules = SvgOptimizerFacade::fromString(self::RULE_SET_SAMPLE_SVG)->optimize()->getContent();

        foreach (RuleSet::cases() as $ruleSet) {
            self::assertNotSame($withoutRules, SvgOptimizerFacade::fromString(self::RULE_SET_SAMPLE_SVG)->withRuleSets($ruleSet)->optimize()->getContent(), $ruleSet->value);
        }
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function withRuleSetsAppliesRiskyRulesOnlyIfRiskyRulesAreAllowedWhateverTheCallOrder(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" data-x="1"><!-- c --><rect width="1" height="1"/></svg>';

        $withoutRisky = SvgOptimizerFacade::fromString($svg)->withRuleSets(RuleSet::Cleanup)->allowRisky(false)->optimize()->getContent();
        $allowRiskyFirst = SvgOptimizerFacade::fromString($svg)->allowRisky()->withRuleSets(RuleSet::Cleanup)->optimize()->getContent();
        $allowRiskyLast = SvgOptimizerFacade::fromString($svg)->withRuleSets(RuleSet::Cleanup)->allowRisky()->optimize()->getContent();

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg" data-x="1"><rect width="1" height="1"/></svg>', $withoutRisky);
        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1"/></svg>', $allowRiskyFirst);
        self::assertSame($allowRiskyFirst, $allowRiskyLast);
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function withRuleSetsAddsToTheRulesOfWithRulesAndWithAllRules(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><title>T</title><!-- c --><rect width="1.50" height="1"/></svg>';

        $content = SvgOptimizerFacade::fromString($svg)
            ->withRules(...$this->ruleFlagsFor('removeTitles'))
            ->withRuleSets(RuleSet::Minification)
            ->optimize()
            ->getContent();

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"><!-- c --><rect width="1.5" height="1"/></svg>', $content);

        $content = SvgOptimizerFacade::fromString($svg)
            ->withRuleSets(RuleSet::Minification)
            ->withRules(...$this->ruleFlagsFor('removeTitles'))
            ->withRuleSets(RuleSet::Cleanup)
            ->optimize()
            ->getContent();

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"><rect width="1.5" height="1"/></svg>', $content);

        self::assertSame(
            SvgOptimizerFacade::fromString(self::RULE_SET_SAMPLE_SVG)->withAllRules()->optimize()->getContent(),
            SvgOptimizerFacade::fromString(self::RULE_SET_SAMPLE_SVG)->withRuleSets(RuleSet::Minification)->withAllRules()->withRuleSets(RuleSet::Cleanup)->optimize()->getContent(),
        );
    }

    /**
     * @throws SvgValidationException
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     */
    #[Test]
    public function withRuleSetsDoesNotAllowARiskyRuleEnabledWithWithRules(): void
    {
        $this->expectException(RiskyRulesNotAllowedException::class);

        SvgOptimizerFacade::fromString('<svg xmlns="http://www.w3.org/2000/svg" data-x="1"></svg>')
            ->withRuleSets(RuleSet::Cleanup)
            ->withRules(...$this->ruleFlagsFor('removeDataAttributes'))
            ->optimize();
    }

    /**
     * @return list<bool> The positional arguments of withRules() that enable only the given rule
     */
    private function ruleFlagsFor(string $ruleName): array
    {
        return array_map(
            static fn (\ReflectionParameter $reflectionParameter): bool => $ruleName === $reflectionParameter->getName(),
            (new \ReflectionMethod(SvgOptimizerFacade::class, 'withRules'))->getParameters(),
        );
    }

    /**
     * @return list<string> SVG documents that exercise the rules of every set
     */
    private function ruleSetCorpus(): array
    {
        $files = glob(__DIR__ . '/../../../../dev/resources/svg/*.svg');
        self::assertIsArray($files);

        return [self::RULE_SET_SAMPLE_SVG, ...array_map(static fn (string $file): string => (string) file_get_contents($file), $files)];
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function optimizeReturnsService(): void
    {
        $svgOptimizerFacade = SvgOptimizerFacade::fromString($this->sampleSvg);
        $result = $svgOptimizerFacade->optimize();

        self::assertSame($svgOptimizerFacade, $result);
        self::assertNotSame('', $result->getContent());
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     * @throws IOException
     */
    #[Test]
    public function saveToFileWritesOptimizedSvg(): void
    {
        $file = sys_get_temp_dir() . '/optimized-test.svg';

        if (file_exists($file)) {
            unlink($file);
        }

        SvgOptimizerFacade::fromString($this->sampleSvg)
            ->optimize()
            ->saveToFile($file);

        self::assertFileExists($file);
        self::assertStringContainsString('<svg', (string) file_get_contents($file));

        unlink($file);
    }

    /**
     * @throws FileNotFoundException
     * @throws IOException
     */
    #[Test]
    public function fromFileThrowsExceptionOnInvalidPath(): void
    {
        $this->expectException(FileNotFoundException::class);
        SvgOptimizerFacade::fromFile('/nonexistent/path.svg');
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function withRulesConfiguresRules(): void
    {
        $svgOptimizerFacade = SvgOptimizerFacade::fromString($this->sampleSvg)
            ->withRules(true, true, false, false, false, false, false, false, true);

        $svgOptimizerFacade->optimize();

        $content = $svgOptimizerFacade->getContent();
        self::assertNotSame('', $content);
        self::assertStringContainsString('<svg', $content);
        self::assertStringNotContainsString('<!--', $content);
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function allowRiskyEnablesRiskyRules(): void
    {
        $svgOptimizerFacade = SvgOptimizerFacade::fromString($this->sampleSvg)
            ->allowRisky();

        self::assertSame($svgOptimizerFacade, $svgOptimizerFacade->allowRisky());

        $svgOptimizerFacade->withRules(true, false, false);

        $svgOptimizerFacade->optimize();

        self::assertNotSame('', $svgOptimizerFacade->getContent());
    }

    /**
     * @throws \LogicException
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     */
    #[Test]
    public function getContentReturnsSvg(): void
    {
        $svgOptimizerFacade = SvgOptimizerFacade::fromString($this->sampleSvg)->optimize();

        $content = $svgOptimizerFacade->getContent();
        self::assertStringContainsString('<svg', $content);
    }

    /**
     * @throws SvgValidationException
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     */
    #[Test]
    public function optimizeThrowsExceptionForRiskyRules(): void
    {
        $svgOptimizerFacade = SvgOptimizerFacade::fromString($this->sampleSvg)
            ->withRules(...$this->ruleFlagsFor('removeDataAttributes'));

        $this->expectException(RiskyRulesNotAllowedException::class);
        $svgOptimizerFacade->optimize();
    }

    /**
     * @throws SvgValidationException
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     */
    #[Test]
    public function allowRiskyFalseDoesNotEnableRiskyRules(): void
    {
        $svgOptimizerFacade = SvgOptimizerFacade::fromString($this->sampleSvg)
            ->withRules(...$this->ruleFlagsFor('removeWidthHeightAttributes'))
            ->allowRisky(false);

        $this->expectException(RiskyRulesNotAllowedException::class);
        $svgOptimizerFacade->optimize();
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function withRulesDefaultDoesNotApplyAnyRules(): void
    {
        $svgOptimizerFacade = SvgOptimizerFacade::fromString($this->sampleSvg)->withRules();
        $svgOptimizerFacade->optimize();

        $content = $svgOptimizerFacade->getContent();
        self::assertStringContainsString('<svg', $content);
        self::assertStringContainsString('<title>', $content);
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function multipleRulesApplied(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><title>T</title><!-- c --></svg>';

        $svgOptimizerFacade = SvgOptimizerFacade::fromString($svg)
            ->withRules(...array_map(
                static fn (bool $comments, bool $titles): bool => $comments || $titles,
                $this->ruleFlagsFor('removeComments'),
                $this->ruleFlagsFor('removeTitles'),
            ))
            ->optimize();

        $content = $svgOptimizerFacade->getContent();

        self::assertStringNotContainsString('<!--', $content);
        self::assertStringNotContainsString('<title>', $content);
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     * @throws IOException
     */
    #[Test]
    public function methodChaining(): void
    {
        $file = sys_get_temp_dir() . '/chained-test.svg';

        SvgOptimizerFacade::fromString($this->sampleSvg)
            ->allowRisky()
            ->withRules(true, false, false)
            ->optimize()
            ->saveToFile($file);

        self::assertFileExists($file);
        unlink($file);
    }

    /**
     * @throws IOException
     * @throws FileNotFoundException
     */
    #[Test]
    public function fromStringAndFromFileReturnInstances(): void
    {
        $file = sys_get_temp_dir() . '/instance-test.svg';
        file_put_contents($file, $this->sampleSvg);

        $svgOptimizerFacade = SvgOptimizerFacade::fromString($this->sampleSvg);
        $b = SvgOptimizerFacade::fromFile($file);

        self::assertNotSame($svgOptimizerFacade, $b);

        unlink($file);
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function withAllRulesEnablesAllNonRiskyRules(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><title>T</title><!-- c --></svg>';
        $svgOptimizerFacade = SvgOptimizerFacade::fromString($svg)->withAllRules()->optimize();
        $content = $svgOptimizerFacade->getContent();

        self::assertStringNotContainsString('<!--', $content);
        self::assertStringNotContainsString('<title>', $content);
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function withAllRulesIncludesRiskyRulesWhetherAllowRiskyIsCalledBeforeOrAfter(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100" data-x="1"><rect width="10" height="10"/></svg>';

        $allowRiskyFirst = SvgOptimizerFacade::fromString($svg)->allowRisky()->withAllRules()->optimize()->getContent();
        $allowRiskyLast = SvgOptimizerFacade::fromString($svg)->withAllRules()->allowRisky()->optimize()->getContent();

        self::assertSame($allowRiskyFirst, $allowRiskyLast);
        self::assertStringNotContainsString('width="100"', $allowRiskyLast);
        self::assertStringNotContainsString('data-x', $allowRiskyLast);
    }

    /**
     * @throws IOException
     */
    #[Test]
    public function saveToFileBeforeOptimizeThrowsAndWritesNothing(): void
    {
        $outputPath = sys_get_temp_dir() . '/not-optimized-' . uniqid() . '.svg';

        try {
            SvgOptimizerFacade::fromString('<svg xmlns="http://www.w3.org/2000/svg"></svg>')->saveToFile($outputPath);
            self::fail('saveToFile() before optimize() must throw.');
        } catch (\LogicException $logicException) {
            self::assertSame('Content cannot be saved before optimization.', $logicException->getMessage());
        }

        self::assertFileDoesNotExist($outputPath);
    }

    /**
     * @throws \LogicException
     */
    #[Test]
    public function getContentBeforeOptimizeThrows(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Content is not available before optimization.');

        SvgOptimizerFacade::fromString('<svg xmlns="http://www.w3.org/2000/svg"></svg>')->getContent();
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function withAllRulesWithoutAllowRiskyLeavesRiskyRulesOut(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100" data-x="1"><rect width="10" height="10"/></svg>';

        $content = SvgOptimizerFacade::fromString($svg)->withAllRules()->allowRisky(false)->optimize()->getContent();

        self::assertStringContainsString('width="100"', $content);
        self::assertStringContainsString('data-x="1"', $content);
    }

    /**
     * @throws SvgValidationException
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     */
    #[Test]
    public function optimizeThrowsIfARiskyRuleIsEnabledWithoutAllowRisky(): void
    {
        $this->expectException(RiskyRulesNotAllowedException::class);

        SvgOptimizerFacade::fromString('<svg xmlns="http://www.w3.org/2000/svg" width="100"></svg>')
            ->withAllRules()
            ->withRules(...$this->ruleFlagsFor('removeWidthHeightAttributes'))
            ->optimize();
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function getMetaDataReturnsCorrectData(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"></svg>';
        $svgOptimizerFacade = SvgOptimizerFacade::fromString($svg)->optimize();
        $metrics = $svgOptimizerFacade->getMetaData();

        self::assertGreaterThan(0, $metrics->getOriginalSize());
        self::assertGreaterThan(0, $metrics->getOptimizedSize());
        self::assertGreaterThanOrEqual(0, $metrics->getSavedBytes());
        self::assertGreaterThanOrEqual(0, $metrics->getSavedPercentage());
        self::assertGreaterThanOrEqual(0, $metrics->getOptimizationTime());
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function withRulesCanEnableSpecificRiskyRules(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100"></svg>';
        $svgOptimizerFacade = SvgOptimizerFacade::fromString($svg)
            ->withRules(...$this->ruleFlagsFor('removeWidthHeightAttributes'))
            ->allowRisky()
            ->optimize();

        $content = $svgOptimizerFacade->getContent();
        self::assertStringNotContainsString('width="100"', $content);
        self::assertStringNotContainsString('height="100"', $content);
    }

    /**
     * @throws SvgValidationException
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     */
    #[DataProvider('provideOptimizeDoesNotEmitPhpWarningsCases')]
    #[Test]
    public function optimizeDoesNotEmitPhpWarnings(string $svg, bool $allowRisky): void
    {
        $warnings = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$warnings): bool {
            $warnings[] = $errstr;

            return true;
        });

        try {
            SvgOptimizerFacade::fromString($svg)->allowRisky($allowRisky)->withAllRules()->optimize();
        } finally {
            restore_error_handler();
        }

        self::assertSame([], $warnings);
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function provideOptimizeDoesNotEmitPhpWarningsCases(): iterable
    {
        yield 'undeclared prefix' => ['<svg><use xlink:href="#x" clip-path="a"/></svg>', false];

        yield 'undeclared prefix, risky' => ['<svg><use xlink:href="#x" clip-path="a"/></svg>', true];

        yield 'undeclared prefix on removed element' => ['<svg xmlns="http://www.w3.org/2000/svg"><text><tref xlink:href=""/></text></svg>', false];

        yield 'reserved processing instruction target' => ["<svg xmlns=\"http://www.w3.org/2000/svg\">\n<?xml-foo bar?>\n<rect width=\"1\" height=\"1\"/></svg>", false];
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[DataProvider('provideOptimizingTwiceGivesTheSameResultCases')]
    #[Test]
    public function optimizingTwiceGivesTheSameResult(string $file, bool $allowRisky): void
    {
        $svg = file_get_contents($file);
        self::assertIsString($svg);

        $once = SvgOptimizerFacade::fromString($svg)->allowRisky($allowRisky)->withAllRules()->optimize()->getContent();
        $twice = SvgOptimizerFacade::fromString($once)->allowRisky($allowRisky)->withAllRules()->optimize()->getContent();

        self::assertSame($once, $twice);
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function provideOptimizingTwiceGivesTheSameResultCases(): iterable
    {
        $files = glob(__DIR__ . '/../../../../dev/resources/svg/*.svg');
        self::assertIsArray($files);
        self::assertNotSame([], $files);

        foreach ($files as $file) {
            yield basename($file) => [$file, false];

            yield basename($file) . ', risky' => [$file, true];
        }
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[DataProvider('provideSaveToFileThrowsOnlyAnIoExceptionCases')]
    #[Test]
    public function saveToFileThrowsOnlyAnIoException(string $suffix): void
    {
        $directory = sys_get_temp_dir() . '/svgopt_save_' . uniqid();
        mkdir($directory);
        file_put_contents($directory . '/file.txt', 'x');
        $warnings = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$warnings): bool {
            $warnings[] = $errstr;

            return true;
        });

        try {
            SvgOptimizerFacade::fromString('<svg xmlns="http://www.w3.org/2000/svg"/>')->optimize()->saveToFile($directory . $suffix);
            self::fail('Expected IOException was not thrown.');
        } catch (IOException) {
            self::assertSame([], $warnings);
        } finally {
            restore_error_handler();
            unlink($directory . '/file.txt');
            rmdir($directory);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideSaveToFileThrowsOnlyAnIoExceptionCases(): iterable
    {
        yield 'below a file' => ['/file.txt/out.svg'];

        yield 'a directory' => [''];
    }

    /**
     * @throws FileNotFoundException
     * @throws IOException
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function metaDataDescribesTheInputFileAfterTheOptimizedContentIsSavedOverIt(): void
    {
        file_put_contents($this->tempFilePath, '<svg xmlns="http://www.w3.org/2000/svg"><!-- comment --></svg>');

        $svgOptimizerFacade = SvgOptimizerFacade::fromFile($this->tempFilePath)
            ->withAllRules()
            ->optimize()
            ->saveToFile($this->tempFilePath);

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"/>', file_get_contents($this->tempFilePath));
        self::assertSame(62, $svgOptimizerFacade->getMetaData()->getOriginalSize());
        self::assertSame(41, $svgOptimizerFacade->getMetaData()->getOptimizedSize());
        self::assertSame(21, $svgOptimizerFacade->getMetaData()->getSavedBytes());
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function enablingRulesAgainDoesNotChangeTheResult(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(1 1)"><g transform="translate(2 2)"><rect width="1" height="1"/></g></g></svg>';

        $enabled = ['flattenGroups', 'minifyTransformations', 'convertShapesToPath'];
        $flags = array_map(
            static fn (\ReflectionParameter $reflectionParameter): bool => \in_array($reflectionParameter->getName(), $enabled, true),
            (new \ReflectionMethod(SvgOptimizerFacade::class, 'withRules'))->getParameters(),
        );

        $withAllRules = SvgOptimizerFacade::fromString($svg)->withAllRules()->optimize()->getContent();
        $withRulesAgain = SvgOptimizerFacade::fromString($svg)
            ->withAllRules()
            ->withRules(...$flags)
            ->optimize()
            ->getContent();

        self::assertCount(3, array_filter($flags));

        self::assertSame($withAllRules, $withRulesAgain);
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[DataProvider('provideWithRulesEnablesTheRuleAtItsPositionCases')]
    #[Test]
    public function withRulesEnablesTheRuleAtItsPosition(int $position, Rule $rule, string $svg): void
    {
        $flags = array_fill(0, $position + 1, false);
        $flags[$position] = true;

        $svgOptimizer = new SvgOptimizer(new StringProvider($svg));
        $svgOptimizer->enableRules($rule);

        $expected = $svgOptimizer->allowRisky()->optimize()->getContent();
        $actual = SvgOptimizerFacade::fromString($svg)
            ->withRules(...$flags)
            ->allowRisky()
            ->optimize()
            ->getContent();

        self::assertNotSame(SvgOptimizerFacade::fromString($svg)->optimize()->getContent(), $expected);
        self::assertSame($expected, $actual);
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[DataProvider('provideWithRulesEnablesTheRuleAtItsPositionCases')]
    #[Test]
    public function withRulesLeavesEveryRuleOffByDefault(int $position, Rule $rule, string $svg): void
    {
        $withoutRules = SvgOptimizerFacade::fromString($svg)->optimize()->getContent();
        $withDefaultRules = SvgOptimizerFacade::fromString($svg)->withRules()->optimize()->getContent();

        self::assertSame($withoutRules, $withDefaultRules);
    }

    /**
     * @return iterable<string, array{int, Rule, string}>
     */
    public static function provideWithRulesEnablesTheRuleAtItsPositionCases(): iterable
    {
        yield 'convertColorsToHex' => [0, Rule::ConvertColorsToHex, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" fill="rgb(255,0,0)"/></svg>'];
        yield 'convertCssClassesToAttributes' => [1, Rule::ConvertCssClassesToAttributes, '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}</style><rect class="a" width="1" height="1"/></svg>'];
        yield 'convertInlineStylesToAttributes' => [2, Rule::ConvertInlineStylesToAttributes, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" style="fill:red"/></svg>'];
        yield 'fixAttributeNames' => [3, Rule::FixAttributeNames, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" Fill="red"/></svg>'];
        yield 'flattenGroups' => [4, Rule::FlattenGroups, '<svg xmlns="http://www.w3.org/2000/svg"><g><rect width="1" height="1"/></g></svg>'];
        yield 'minifySvgCoordinates' => [5, Rule::MinifySvgCoordinates, '<svg xmlns="http://www.w3.org/2000/svg"><rect x="1.50" width="1" height="1"/></svg>'];
        yield 'minifyTransformations' => [6, Rule::MinifyTransformations, '<svg xmlns="http://www.w3.org/2000/svg"><rect transform="translate(10, 0)" width="1" height="1"/></svg>'];
        yield 'removeAriaAndRole' => [7, Rule::RemoveAriaAndRole, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" aria-label="x" role="img"/></svg>'];
        yield 'removeComments' => [8, Rule::RemoveComments, '<svg xmlns="http://www.w3.org/2000/svg"><!-- c --><rect width="1" height="1"/></svg>'];
        yield 'removeDataAttributes' => [9, Rule::RemoveDataAttributes, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" data-x="1"/></svg>'];
        yield 'removeDefaultAttributes' => [10, Rule::RemoveDefaultAttributes, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" stroke-linejoin="miter"/></svg>'];
        yield 'removeDuplicateElements' => [11, Rule::RemoveDuplicateElements, '<svg xmlns="http://www.w3.org/2000/svg"><defs><text x="1">a</text><text x="1">a</text></defs></svg>'];
        yield 'removeEmptyAttributes' => [12, Rule::RemoveEmptyAttributes, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" fill=""/></svg>'];
        yield 'removeInkscapeFootprints' => [13, Rule::RemoveInkscapeFootprints, '<svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect width="1" height="1" inkscape:label="x"/></svg>'];
        yield 'removeInvisibleCharacters' => [14, Rule::RemoveInvisibleCharacters, '<svg xmlns="http://www.w3.org/2000/svg"><g>a&#x200B;</g></svg>'];
        yield 'removeMetadata' => [15, Rule::RemoveMetadata, '<svg xmlns="http://www.w3.org/2000/svg"><metadata>m</metadata><rect width="1" height="1"/></svg>'];
        yield 'removeNonStandardAttributes' => [16, Rule::RemoveNonStandardAttributes, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" foo="bar"/></svg>'];
        yield 'removeNonStandardTags' => [17, Rule::RemoveNonStandardTags, '<svg xmlns="http://www.w3.org/2000/svg"><foo/><rect width="1" height="1"/></svg>'];
        yield 'removeUnnecessaryWhitespace' => [18, Rule::RemoveUnnecessaryWhitespace, '<svg xmlns="http://www.w3.org/2000/svg"><rect width=" 1" height="1"/></svg>'];
        yield 'removeUnusedNamespaces' => [19, Rule::RemoveUnusedNamespaces, '<svg xmlns="http://www.w3.org/2000/svg" xmlns:foo="http://example.com/foo"><rect width="1" height="1"/></svg>'];
        yield 'removeWidthHeightAttributes' => [20, Rule::RemoveWidthHeightAttributes, '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10"><rect width="1" height="1"/></svg>'];
        yield 'scopeSvgStyles' => [21, Rule::ScopeSvgStyles, '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}</style><rect class="a" width="1" height="1"/></svg>'];
        yield 'sortAttributes' => [22, Rule::SortAttributes, '<svg xmlns="http://www.w3.org/2000/svg"><rect b="1" a="2"/></svg>'];
        yield 'convertEllipseToCircle' => [23, Rule::ConvertEllipseToCircle, '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="2" ry="2"/></svg>'];
        yield 'convertShapesToPath' => [24, Rule::ConvertShapesToPath, '<svg xmlns="http://www.w3.org/2000/svg"><line x1="0" y1="0" x2="5" y2="5" stroke="red"/></svg>'];
        yield 'minifyNumericValues' => [25, Rule::MinifyNumericValues, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1.50" height="1"/></svg>'];
        yield 'minifyPathData' => [26, Rule::MinifyPathData, '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 0 0 L 10 10"/></svg>'];
        yield 'removeUselessDefs' => [27, Rule::RemoveUselessDefs, '<svg xmlns="http://www.w3.org/2000/svg"><defs><rect width="1" height="1"/></defs><rect width="1" height="1"/></svg>'];
        yield 'convertOneStopGradients' => [28, Rule::ConvertOneStopGradients, '<svg xmlns="http://www.w3.org/2000/svg"><linearGradient id="g"><stop stop-color="red"/></linearGradient><rect width="1" height="1" fill="url(#g)"/></svg>'];
        yield 'removeUnusedIds' => [29, Rule::RemoveUnusedIds, '<svg xmlns="http://www.w3.org/2000/svg"><rect id="u" width="1" height="1"/></svg>'];
        yield 'mergeDuplicateGradients' => [30, Rule::MergeDuplicateGradients, '<svg xmlns="http://www.w3.org/2000/svg"><linearGradient id="a"><stop offset="0" stop-color="red"/><stop offset="1" stop-color="blue"/></linearGradient><linearGradient id="b"><stop offset="0" stop-color="red"/><stop offset="1" stop-color="blue"/></linearGradient><rect width="1" height="1" fill="url(#a)"/><rect width="1" height="1" fill="url(#b)"/></svg>'];
        yield 'mergeStyles' => [31, Rule::MergeStyles, '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><style>circle{fill:blue}</style><rect width="1" height="1"/></svg>'];
        yield 'migrateToSvg2' => [32, Rule::MigrateToSvg2, '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="#r"/><rect id="r" width="1" height="1"/></svg>'];
        yield 'minifyStyles' => [33, Rule::MinifyStyles, '<svg xmlns="http://www.w3.org/2000/svg"><style> rect { fill : red ; } </style><rect width="1" height="1"/></svg>'];
        yield 'moveCommonAttributesToGroup' => [34, Rule::MoveCommonAttributesToGroup, '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red" width="1" height="1"/><circle fill="red" r="1"/></g></svg>'];
        yield 'moveGroupAttributesToElements' => [35, Rule::MoveGroupAttributesToElements, '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect width="1" height="1"/></g></svg>'];
        yield 'removeHiddenElements' => [36, Rule::RemoveHiddenElements, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" display="none"/><rect width="2" height="2"/></svg>'];
        yield 'removeUnusedDefinitions' => [37, Rule::RemoveUnusedDefinitions, '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"><stop offset="0"/></linearGradient></defs><rect width="1" height="1"/></svg>'];
        yield 'sortDefsChildren' => [38, Rule::SortDefsChildren, '<svg xmlns="http://www.w3.org/2000/svg"><defs><rect id="a" width="1" height="1"/><circle id="b" r="1"/><rect id="c" width="1" height="1"/></defs><use href="#a"/><use href="#b"/><use href="#c"/></svg>'];
        yield 'removeScripts' => [39, Rule::RemoveScripts, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><rect width="1" height="1"/></svg>'];
        yield 'removeUnsafeUrls' => [40, Rule::RemoveUnsafeUrls, '<svg xmlns="http://www.w3.org/2000/svg"><a href="javascript:alert(1)"/><rect width="1" height="1"/></svg>'];
        yield 'removeForeignContent' => [41, Rule::RemoveForeignContent, '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject/><rect width="1" height="1"/></svg>'];
        yield 'sanitizeStyleSheets' => [42, Rule::SanitizeStyleSheets, '<svg xmlns="http://www.w3.org/2000/svg"><style>@import url(a.css);rect{fill:red}</style><rect width="1" height="1"/></svg>'];
        yield 'removeUseBombs' => [43, Rule::RemoveUseBombs, '<svg xmlns="http://www.w3.org/2000/svg"><g id="a"><use href="#a"/></g></svg>'];
        yield 'removeUnsafeMarkup' => [44, Rule::RemoveUnsafeMarkup, '<svg xmlns="http://www.w3.org/2000/svg"><?php x ?><rect width="1" height="1"/></svg>'];
        yield 'removeTitles' => [45, Rule::RemoveTitles, '<svg xmlns="http://www.w3.org/2000/svg"><title>t</title><rect width="1" height="1"/></svg>'];
        yield 'removeDescriptions' => [46, Rule::RemoveDescriptions, '<svg xmlns="http://www.w3.org/2000/svg"><desc>d</desc><rect width="1" height="1"/></svg>'];
        yield 'removeEmptyElements' => [47, Rule::RemoveEmptyElements, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1"/><defs/></svg>'];
        yield 'removeIneffectiveAttributes' => [48, Rule::RemoveIneffectiveAttributes, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" stroke-width="2"/></svg>'];
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->sampleSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><title>Test</title><!-- comment --></svg>';
        $this->tempFilePath = sys_get_temp_dir() . '/test_svg_file_' . uniqid() . '.svg';
    }

    #[\Override]
    protected function tearDown(): void
    {
        if (file_exists($this->tempFilePath)) {
            unlink($this->tempFilePath);
        }
    }
}
