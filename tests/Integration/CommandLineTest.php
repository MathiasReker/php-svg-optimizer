<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Integration;

use MathiasReker\PhpSvgOptimizer\Console\Application;
use MathiasReker\PhpSvgOptimizer\Console\ExitCode;
use MathiasReker\PhpSvgOptimizer\Console\FileFinder;
use MathiasReker\PhpSvgOptimizer\Console\FileOptimizer;
use MathiasReker\PhpSvgOptimizer\Console\Input\CommandLine;
use MathiasReker\PhpSvgOptimizer\Console\Input\CommandLineParser;
use MathiasReker\PhpSvgOptimizer\Console\Input\ConfigFile;
use MathiasReker\PhpSvgOptimizer\Console\Input\Option;
use MathiasReker\PhpSvgOptimizer\Console\Input\OptionScanner;
use MathiasReker\PhpSvgOptimizer\Console\Input\RuleNameTrait;
use MathiasReker\PhpSvgOptimizer\Console\Input\SuggestionTrait;
use MathiasReker\PhpSvgOptimizer\Console\Output\ByteSizeFormatter;
use MathiasReker\PhpSvgOptimizer\Console\Output\NullStream;
use MathiasReker\PhpSvgOptimizer\Console\Output\Printer;
use MathiasReker\PhpSvgOptimizer\Console\Output\Style;
use MathiasReker\PhpSvgOptimizer\Console\RunSummary;
use MathiasReker\PhpSvgOptimizer\Exception\InvalidSvgException;
use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\File\SvgFile;
use MathiasReker\PhpSvgOptimizer\Metrics;
use MathiasReker\PhpSvgOptimizer\OptimizationResult;
use MathiasReker\PhpSvgOptimizer\Pass\ConvertCssClassesToAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\ConvertEllipsesToCircles;
use MathiasReker\PhpSvgOptimizer\Pass\ConvertInlineStylesToAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\ConvertShapesToPaths;
use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Pass\FlattenGroups;
use MathiasReker\PhpSvgOptimizer\Pass\MergeStyleSheets;
use MathiasReker\PhpSvgOptimizer\Pass\MigrateDeprecatedAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\MigrateToSvg2;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyAttributeWhitespace;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyColors;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyNumberLists;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyNumericValues;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyPathData;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyStyleSheets;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyTransforms;
use MathiasReker\PhpSvgOptimizer\Pass\MoveCommonAttributesToGroups;
use MathiasReker\PhpSvgOptimizer\Pass\MoveGroupTransformsToChildren;
use MathiasReker\PhpSvgOptimizer\Pass\Pipeline;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveComments;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDataAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDefaultAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDefaultTypeAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDefaultXmlSpace;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDescriptions;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDuplicateElements;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEditorMarkup;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEmptyAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEmptyContainers;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEmptyTextElements;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEnableBackground;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveForeignContent;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveHiddenElements;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveIneffectiveDefinitions;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveIneffectiveGroupAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveIneffectiveStrokeAndFill;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveIneffectiveTextAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveInvisibleCharacters;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveMetadata;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveNonStandardAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveNonStandardElements;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveScripts;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveUnsafeMarkup;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveUnsafeStyles;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveUnsafeUrls;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveUnusedDefinitions;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveUnusedIds;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveUnusedNamespaces;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveUseBombs;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveWidthHeightAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveZeroSizeShapes;
use MathiasReker\PhpSvgOptimizer\Pass\SortAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\SortDefinitions;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\ClassSelectorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\DeclarationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\SelectorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AnimationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\DefinitionElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementNameTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementRemovalTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\FragmentTargetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberListTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberLiteralTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Path\PathDataParserTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Path\PathDataSerializerTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Path\PathDataTokenTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Presentation\IneffectiveAttributeTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Presentation\InheritanceTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Presentation\InheritedValueTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Security\CanonicalNameTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Security\DangerousUrlTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Security\UnsafeAttributeTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Svg2\AttributeMigrationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Svg2\XlinkMigrationTrait;
use MathiasReker\PhpSvgOptimizer\Rule;
use MathiasReker\PhpSvgOptimizer\RuleSet;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
use MathiasReker\PhpSvgOptimizer\Svg\ElementAttributeMap;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;
use MathiasReker\PhpSvgOptimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Tests\Double\MemoryStream;
use MathiasReker\PhpSvgOptimizer\Tests\Double\TemporaryDirectoryTrait;
use MathiasReker\PhpSvgOptimizer\Tests\Double\WorkingDirectoryTrait;
use MathiasReker\PhpSvgOptimizer\Xml\EntityReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Xml\Parser;
use MathiasReker\PhpSvgOptimizer\Xml\Serializer;
use MathiasReker\PhpSvgOptimizer\Xml\SvgValidator;
use MathiasReker\PhpSvgOptimizer\Xml\TextWhitespaceTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Application::class)]
#[UsesClass(Attribute::class)]
#[UsesClass(ByteSizeFormatter::class)]
#[UsesClass(CommandLine::class)]
#[UsesClass(CommandLineParser::class)]
#[UsesClass(ConfigFile::class)]
#[UsesClass(ConvertCssClassesToAttributes::class)]
#[UsesClass(ConvertEllipsesToCircles::class)]
#[UsesClass(ConvertInlineStylesToAttributes::class)]
#[UsesClass(ConvertShapesToPaths::class)]
#[UsesClass(Element::class)]
#[UsesClass(ElementAttributeMap::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(FileFinder::class)]
#[UsesClass(FileOptimizer::class)]
#[UsesClass(FlattenGroups::class)]
#[UsesClass(MergeStyleSheets::class)]
#[UsesClass(Metrics::class)]
#[UsesClass(MigrateDeprecatedAttributes::class)]
#[UsesClass(MigrateToSvg2::class)]
#[UsesClass(MinifyAttributeWhitespace::class)]
#[UsesClass(MinifyColors::class)]
#[UsesClass(MinifyNumberLists::class)]
#[UsesClass(MinifyNumericValues::class)]
#[UsesClass(MinifyPathData::class)]
#[UsesClass(MinifyStyleSheets::class)]
#[UsesClass(MinifyTransforms::class)]
#[UsesClass(MoveCommonAttributesToGroups::class)]
#[UsesClass(MoveGroupTransformsToChildren::class)]
#[UsesClass(NamespaceUri::class)]
#[UsesClass(NullStream::class)]
#[UsesClass(OptimizationResult::class)]
#[UsesClass(Option::class)]
#[UsesClass(OptionScanner::class)]
#[UsesClass(Parser::class)]
#[UsesClass(Pipeline::class)]
#[UsesClass(Printer::class)]
#[UsesClass(RemoveComments::class)]
#[UsesClass(RemoveDataAttributes::class)]
#[UsesClass(RemoveDefaultAttributes::class)]
#[UsesClass(RemoveDefaultTypeAttributes::class)]
#[UsesClass(RemoveDefaultXmlSpace::class)]
#[UsesClass(RemoveDescriptions::class)]
#[UsesClass(RemoveDuplicateElements::class)]
#[UsesClass(RemoveEditorMarkup::class)]
#[UsesClass(RemoveEmptyAttributes::class)]
#[UsesClass(RemoveEmptyContainers::class)]
#[UsesClass(RemoveEmptyTextElements::class)]
#[UsesClass(RemoveEnableBackground::class)]
#[UsesClass(RemoveForeignContent::class)]
#[UsesClass(RemoveHiddenElements::class)]
#[UsesClass(RemoveIneffectiveDefinitions::class)]
#[UsesClass(RemoveIneffectiveGroupAttributes::class)]
#[UsesClass(RemoveIneffectiveStrokeAndFill::class)]
#[UsesClass(RemoveIneffectiveTextAttributes::class)]
#[UsesClass(RemoveInvisibleCharacters::class)]
#[UsesClass(RemoveMetadata::class)]
#[UsesClass(RemoveNonStandardAttributes::class)]
#[UsesClass(RemoveNonStandardElements::class)]
#[UsesClass(RemoveScripts::class)]
#[UsesClass(RemoveUnsafeMarkup::class)]
#[UsesClass(RemoveUnsafeStyles::class)]
#[UsesClass(RemoveUnsafeUrls::class)]
#[UsesClass(RemoveUnusedDefinitions::class)]
#[UsesClass(RemoveUnusedIds::class)]
#[UsesClass(RemoveUnusedNamespaces::class)]
#[UsesClass(RemoveUseBombs::class)]
#[UsesClass(RemoveWidthHeightAttributes::class)]
#[UsesClass(RemoveZeroSizeShapes::class)]
#[UsesClass(RiskyRulesNotAllowedException::class)]
#[UsesClass(Rule::class)]
#[UsesClass(RuleSet::class)]
#[UsesClass(RunSummary::class)]
#[UsesClass(Serializer::class)]
#[UsesClass(SortAttributes::class)]
#[UsesClass(SortDefinitions::class)]
#[UsesClass(Style::class)]
#[UsesClass(SvgFile::class)]
#[UsesClass(SvgOptimizer::class)]
#[UsesClass(SvgValidator::class)]
#[UsesTrait(AnimationTrait::class)]
#[UsesTrait(AsciiCaseTrait::class)]
#[UsesTrait(AttributeMigrationTrait::class)]
#[UsesTrait(CanonicalNameTrait::class)]
#[UsesTrait(ClassSelectorTrait::class)]
#[UsesTrait(DangerousUrlTrait::class)]
#[UsesTrait(DeclarationTrait::class)]
#[UsesTrait(DefinitionElementTrait::class)]
#[UsesTrait(ElementNameTrait::class)]
#[UsesTrait(ElementRemovalTrait::class)]
#[UsesTrait(ElementTrait::class)]
#[UsesTrait(EntityReferenceTrait::class)]
#[UsesTrait(FragmentTargetTrait::class)]
#[UsesTrait(IdReferenceTrait::class)]
#[UsesTrait(IdTrait::class)]
#[UsesTrait(IneffectiveAttributeTrait::class)]
#[UsesTrait(InheritanceTrait::class)]
#[UsesTrait(InheritedValueTrait::class)]
#[UsesTrait(NumberListTrait::class)]
#[UsesTrait(NumberLiteralTrait::class)]
#[UsesTrait(PathDataParserTrait::class)]
#[UsesTrait(PathDataSerializerTrait::class)]
#[UsesTrait(PathDataTokenTrait::class)]
#[UsesTrait(RuleNameTrait::class)]
#[UsesTrait(ScriptingTrait::class)]
#[UsesTrait(SelectorTrait::class)]
#[UsesTrait(StyleElementTrait::class)]
#[UsesTrait(StyleSheetTrait::class)]
#[UsesTrait(SuggestionTrait::class)]
#[UsesTrait(TextWhitespaceTrait::class)]
#[UsesTrait(UnsafeAttributeTrait::class)]
#[UsesTrait(XlinkMigrationTrait::class)]
final class CommandLineTest extends TestCase
{
    use TemporaryDirectoryTrait;
    use WorkingDirectoryTrait;

    private const string COMMENTED = '<svg xmlns="http://www.w3.org/2000/svg"><!-- comment --><path d="M 0 0 L 10 10" fill="#ff0000"/></svg>';

    private const string WITHOUT_COMMENT = '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 0 0 L 10 10" fill="#ff0000"/></svg>';

    private const string WITH_COLOR_MINIFIED = '<svg xmlns="http://www.w3.org/2000/svg"><!-- comment --><path d="M 0 0 L 10 10" fill="red"/></svg>';

    private const string SCRIPTED = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><rect width="1" height="1"/></svg>';

    private const string WITHOUT_SCRIPT = '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1"/></svg>';

    private const string WITH_UNUSED_ID = '<svg xmlns="http://www.w3.org/2000/svg"><path id="unused" d="M 0 0 L 10 10"/></svg>';

    private string $directory = '';

    #[\Override]
    protected function setUp(): void
    {
        $this->directory = (string) realpath($this->createTemporaryDirectory());
        $this->changeWorkingDirectory($this->directory);
    }

    #[Test]
    public function optimizesEveryFileOfADirectoryWithTheDefaultSelection(): void
    {
        $this->write('icons/b.svg', self::COMMENTED);
        $this->write('icons/a.svg', self::COMMENTED);

        $result = $this->execute(['icons']);

        self::assertSame(ExitCode::Success, $result['exitCode']);
        self::assertSame('', $result['stderr']);
        self::assertMatchesRegularExpression('/^icons\/a\.svg \(\d+\.\d\d%\)\nicons\/b\.svg \(\d+\.\d\d%\)\n\nSummary:\n  Files:             2\n/', $result['stdout']);
        self::assertStringNotContainsString('comment', $this->read('icons/a.svg'));
        self::assertStringContainsString('fill="red"', $this->read('icons/b.svg'));
        self::assertStringContainsString('Optimization time: ', $result['stdout']);
    }

    #[Test]
    public function printsThePathsAsGivenAndNeverAbsolutePaths(): void
    {
        $this->write('icons/sub/a.svg', self::COMMENTED);
        $this->write('single.svg', self::COMMENTED);

        $result = $this->execute(['--dry-run', 'icons', './single.svg', $this->directory . '/icons/sub/a.svg']);

        self::assertStringContainsString('icons/sub/a.svg (', $result['stdout']);
        self::assertStringContainsString('./single.svg (', $result['stdout']);
        self::assertSame(1, mb_substr_count($result['stdout'], 'a.svg ('), 'The same file is optimized once.');
        self::assertStringNotContainsString($this->directory, $result['stdout']);
    }

    #[Test]
    public function printsTheFilesBelowTheCurrentDirectoryWithoutAPrefix(): void
    {
        $this->write('a.svg', self::COMMENTED);
        $this->write('sub/b.svg', self::COMMENTED);

        $result = $this->execute(['--dry-run', '.']);

        self::assertMatchesRegularExpression('/^a\.svg \(.*\)\nsub\/b\.svg \(/', $result['stdout']);
    }

    #[Test]
    public function aFileThatIsAlreadyOptimizedStaysAsItIs(): void
    {
        $this->write('a.svg', self::COMMENTED);
        $this->execute(['a.svg']);
        $optimized = $this->read('a.svg');

        $second = $this->execute(['a.svg']);

        self::assertSame($optimized, $this->read('a.svg'));
        self::assertStringStartsWith("a.svg (0.00%)\n", $second['stdout']);
        self::assertSame(ExitCode::Success, $second['exitCode']);
    }

    #[Test]
    public function dryRunReportsTheSavingsWithoutWriting(): void
    {
        $this->write('a.svg', self::COMMENTED);

        $result = $this->execute(['--dry-run', 'a.svg']);

        self::assertSame(ExitCode::Success, $result['exitCode']);
        self::assertSame(self::COMMENTED, $this->read('a.svg'));
        self::assertMatchesRegularExpression('/^a\.svg \(\d+\.\d\d%\)\n/', $result['stdout']);
        self::assertStringContainsString('Saved:', $result['stdout']);
        self::assertSame('', $result['stderr']);
    }

    #[Test]
    public function checkExitsWithThreeAndWritesNothingWhenAFileWouldChange(): void
    {
        $this->write('a.svg', self::COMMENTED);
        $this->write('b.svg', self::COMMENTED);

        $result = $this->execute(['--check', '.']);

        self::assertSame(ExitCode::ChangesDetected, $result['exitCode']);
        self::assertSame(self::COMMENTED, $this->read('a.svg'));
        self::assertSame(self::COMMENTED, $this->read('b.svg'));
        self::assertSame("Error: 2 files would change.\n", $result['stderr']);
        self::assertStringContainsString("Summary:\n  Files:             2\n", $result['stdout']);
    }

    #[Test]
    public function checkSaysFileInTheSingular(): void
    {
        $this->write('a.svg', self::COMMENTED);

        $result = $this->execute(['--check', 'a.svg']);

        self::assertSame("Error: 1 file would change.\n", $result['stderr']);
    }

    #[Test]
    public function checkExitsWithZeroWhenNothingWouldChange(): void
    {
        $this->write('a.svg', self::COMMENTED);
        $this->execute(['a.svg']);

        $result = $this->execute(['--check', 'a.svg']);

        self::assertSame(ExitCode::Success, $result['exitCode']);
        self::assertSame('', $result['stderr']);
    }

    #[Test]
    public function checkReportsAFailureBeforeChanges(): void
    {
        $this->write('a.svg', self::COMMENTED);
        $this->write('bad.svg', 'not an svg');

        $result = $this->execute(['--check', '.']);

        self::assertSame(ExitCode::Failure, $result['exitCode']);
        self::assertStringContainsString('Error: 1 file would change.', $result['stderr']);
        self::assertStringContainsString('Error: "bad.svg" is not a valid SVG file: ', $result['stderr']);
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function selectsOnlyTheRulesOfTheGivenSets(): void
    {
        $this->write('a.svg', self::COMMENTED);

        $this->execute(['--sets=security', 'a.svg']);

        self::assertSame(self::COMMENTED, $this->read('a.svg'));

        $this->execute(['--sets=conversion', 'a.svg']);

        self::assertSame((new SvgOptimizer())->withRuleSets(RuleSet::Conversion)->optimizeString(self::COMMENTED)->getContent(), $this->read('a.svg'));
    }

    #[Test]
    public function selectsOnlyTheGivenRules(): void
    {
        $this->write('a.svg', self::COMMENTED);

        $this->execute(['--rules=removeComments', 'a.svg']);

        self::assertSame(self::WITHOUT_COMMENT, $this->read('a.svg'));
    }

    #[Test]
    public function addsTheGivenRulesToTheDefaultSet(): void
    {
        $this->write('a.svg', self::COMMENTED);

        $this->execute(['--sets=default', '-r', 'removeDescriptions', 'a.svg']);

        self::assertStringNotContainsString('comment', $this->read('a.svg'));
        self::assertStringContainsString('fill="red"', $this->read('a.svg'));
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function leavesOutTheRulesOfWithoutRulesEvenWhenARuleSetSelectsThem(): void
    {
        $this->write('a.svg', self::COMMENTED);

        $this->execute(['--without-rules=removeComments', 'a.svg']);

        self::assertSame($this->defaultsWithout(), $this->read('a.svg'));
    }

    #[Test]
    public function withoutRulesWinsOverRules(): void
    {
        $this->write('a.svg', self::COMMENTED);

        $this->execute(['--rules=removeComments,minifyColors', '--without-rules=removeComments', 'a.svg']);

        self::assertSame(self::WITH_COLOR_MINIFIED, $this->read('a.svg'));
    }

    #[Test]
    public function readsTheConfigurationFileGivenWithConfig(): void
    {
        $this->write('a.svg', self::COMMENTED);
        $this->write('config/custom.json', '{"rules": {"removeComments": true}}');

        $this->execute(['--config=config/custom.json', 'a.svg']);

        self::assertSame(self::WITHOUT_COMMENT, $this->read('a.svg'));
    }

    #[Test]
    public function readsInlineJsonGivenWithConfig(): void
    {
        $this->write('a.svg', self::COMMENTED);

        $this->execute(['-c', '{"rules": {"minifyColors": true}}', 'a.svg']);

        self::assertSame(self::WITH_COLOR_MINIFIED, $this->read('a.svg'));
    }

    #[Test]
    public function discoversTheConfigurationFileInTheWorkingDirectory(): void
    {
        $this->write('a.svg', self::COMMENTED);
        $this->write('svg-optimizer.json', '{"rules": {"removeComments": true}}');

        $this->execute(['a.svg']);

        self::assertSame(self::WITHOUT_COMMENT, $this->read('a.svg'));
    }

    #[Test]
    public function aSetOnTheCommandLineRunsARuleThatTheConfigurationFileDisables(): void
    {
        $this->write('a.svg', self::SCRIPTED);
        $this->write('svg-optimizer.json', '{"rules": {"removeScripts": false}}');

        $this->execute(['--sets=security', 'a.svg']);

        self::assertSame(self::WITHOUT_SCRIPT, $this->read('a.svg'));
    }

    #[Test]
    public function aRuleThatTheConfigurationFileDisablesStaysOffForTheSetsOfTheFile(): void
    {
        $this->write('a.svg', self::SCRIPTED);
        $this->write('svg-optimizer.json', '{"sets": ["security"], "rules": {"removeScripts": false}}');

        $this->execute(['a.svg']);

        self::assertStringContainsString('<script>', $this->read('a.svg'));
    }

    #[Test]
    public function withoutRulesOnTheCommandLineBeatsASetOnTheCommandLine(): void
    {
        $this->write('a.svg', self::SCRIPTED);

        $this->execute(['--sets=security', '--without-rules=removeScripts', 'a.svg']);

        self::assertStringContainsString('<script>', $this->read('a.svg'));
    }

    #[Test]
    public function aRuleOnTheCommandLineRunsAgainWhenTheConfigurationFileDisablesIt(): void
    {
        $this->write('a.svg', self::SCRIPTED);
        $this->write('svg-optimizer.json', '{"sets": ["security"], "rules": {"removeScripts": false}}');

        $this->execute(['--rules=removeScripts', 'a.svg']);

        self::assertSame(self::WITHOUT_SCRIPT, $this->read('a.svg'));
    }

    #[Test]
    public function theDefaultSetOnTheCommandLineRunsARuleThatTheConfigurationFileDisables(): void
    {
        $this->write('a.svg', self::COMMENTED);
        $this->write('svg-optimizer.json', '{"rules": {"removeComments": false}}');

        $this->execute(['--sets=default', 'a.svg']);

        self::assertStringNotContainsString('<!--', $this->read('a.svg'));
    }

    #[Test]
    public function configDoesNotDiscoverTheConfigurationFileWhenItIsGiven(): void
    {
        $this->write('a.svg', self::COMMENTED);
        $this->write('svg-optimizer.json', '{"rules": {"removeComments": true}}');

        $this->execute(['--config={"rules": {"minifyColors": true}}', 'a.svg']);

        self::assertSame(self::WITH_COLOR_MINIFIED, $this->read('a.svg'));
    }

    #[Test]
    public function reportsAnInvalidDiscoveredConfigurationFile(): void
    {
        $this->write('a.svg', self::COMMENTED);
        $this->write('svg-optimizer.json', '{"rules": {"banana": true}}');

        $result = $this->execute(['a.svg']);

        self::assertSame(ExitCode::InvalidUsage, $result['exitCode']);
        self::assertSame("Error: Invalid configuration file \"svg-optimizer.json\": Unknown rule \"banana\". Run svg-optimizer --list-rules to see the rules.\n", $result['stderr']);
        self::assertSame(self::COMMENTED, $this->read('a.svg'));
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function aConfigurationCanSelectDefaultAndDisableARule(): void
    {
        $this->write('a.svg', self::COMMENTED);

        $this->execute(['--config={"sets": ["default"], "rules": {"removeComments": false}}', 'a.svg']);

        self::assertSame($this->defaultsWithout(), $this->read('a.svg'));
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function aConfigurationThatOnlyDisablesARuleKeepsTheDefaultSelection(): void
    {
        $this->write('a.svg', self::COMMENTED);

        $this->execute(['--config={"rules": {"removeComments": false}}', 'a.svg']);

        self::assertSame($this->defaultsWithout(), $this->read('a.svg'));
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function rulesOnTheCommandLineOverrideADisabledRuleOfTheConfiguration(): void
    {
        $this->write('a.svg', self::COMMENTED);

        $this->execute(['--config={"sets": ["default"], "rules": {"removeComments": false}}', '--rules=removeComments', 'a.svg']);

        self::assertSame((new SvgOptimizer())->withDefaultRules()->optimizeString(self::COMMENTED)->getContent(), $this->read('a.svg'));
        self::assertStringNotContainsString('comment', $this->read('a.svg'));
    }

    #[Test]
    public function withoutRulesOnTheCommandLineOverridesAnEnabledRuleOfTheConfiguration(): void
    {
        $this->write('a.svg', self::COMMENTED);

        $this->execute(['--config={"rules": {"removeComments": true, "minifyColors": true}}', '--without-rules=removeComments', 'a.svg']);

        self::assertSame(self::WITH_COLOR_MINIFIED, $this->read('a.svg'));
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function mergesTheSetsOfTheConfigurationAndTheCommandLine(): void
    {
        $this->write('a.svg', self::COMMENTED);

        $this->execute(['--config={"sets": ["security"], "rules": {"removeComments": true}}', '--sets=conversion', 'a.svg']);

        self::assertSame(
            (new SvgOptimizer())->withRules(Rule::RemoveComments)->withRuleSets(RuleSet::Security, RuleSet::Conversion)->optimizeString(self::COMMENTED)->getContent(),
            $this->read('a.svg'),
        );
        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"><path d="M 0 0 L 10 10" fill="red"/></svg>', $this->read('a.svg'));
    }

    #[Test]
    public function skipsRiskyRulesOfASetUnlessTheyAreAllowed(): void
    {
        $this->write('a.svg', self::WITH_UNUSED_ID);

        $result = $this->execute(['--sets=cleanup', '--dry-run', 'a.svg']);
        self::assertSame(ExitCode::Success, $result['exitCode']);
        $this->execute(['--sets=cleanup', 'a.svg']);
        self::assertStringContainsString('id="unused"', $this->read('a.svg'));

        $this->execute(['--sets=cleanup', '--allow-risky', 'a.svg']);
        self::assertStringNotContainsString('id="unused"', $this->read('a.svg'));
    }

    #[Test]
    public function rejectsARiskyRuleSelectedWithoutAllowingRiskyRules(): void
    {
        $this->write('a.svg', self::WITH_UNUSED_ID);

        $result = $this->execute(['--rules=removeUnusedIds', 'a.svg']);

        self::assertSame(ExitCode::InvalidUsage, $result['exitCode']);
        self::assertSame('', $result['stdout']);
        self::assertSame("Error: Risky rules need --allow-risky, or \"allowRisky\" in the configuration: removeUnusedIds.\n", $result['stderr']);
        self::assertSame(self::WITH_UNUSED_ID, $this->read('a.svg'));
    }

    #[Test]
    public function rejectsARiskyRuleOfTheConfigurationWithoutAllowingRiskyRules(): void
    {
        $this->write('a.svg', self::WITH_UNUSED_ID);

        $result = $this->execute(['--config={"rules": {"removeUnusedIds": true}}', 'a.svg']);

        self::assertSame(ExitCode::InvalidUsage, $result['exitCode']);
        self::assertStringContainsString('removeUnusedIds', $result['stderr']);
    }

    #[Test]
    public function allowsARiskyRuleWithTheFlagOrTheConfiguration(): void
    {
        $this->write('a.svg', self::WITH_UNUSED_ID);
        $this->write('b.svg', self::WITH_UNUSED_ID);

        $flag = $this->execute(['--rules=removeUnusedIds', '--allow-risky', 'a.svg']);
        $config = $this->execute(['--config={"rules": {"removeUnusedIds": true}, "allowRisky": true}', 'b.svg']);

        self::assertSame(ExitCode::Success, $flag['exitCode']);
        self::assertSame(ExitCode::Success, $config['exitCode']);
        self::assertStringNotContainsString('id="unused"', $this->read('a.svg'));
        self::assertStringNotContainsString('id="unused"', $this->read('b.svg'));
    }

    #[Test]
    public function doesNotRejectARiskyRuleThatWithoutRulesLeavesOut(): void
    {
        $this->write('a.svg', self::WITH_UNUSED_ID);

        $result = $this->execute(['--rules=removeUnusedIds', '--without-rules=removeUnusedIds', 'a.svg']);

        self::assertSame(ExitCode::Success, $result['exitCode']);
        self::assertSame(self::WITH_UNUSED_ID, $this->read('a.svg'));
    }

    /**
     * @param list<string> $arguments
     */
    #[DataProvider('provideRejectsAnInvalidUsageCases')]
    #[Test]
    public function rejectsAnInvalidUsage(array $arguments, string $message): void
    {
        $this->write('a.svg', self::COMMENTED);

        $result = $this->execute($arguments);

        self::assertSame(ExitCode::InvalidUsage, $result['exitCode']);
        self::assertSame('', $result['stdout']);
        self::assertStringStartsWith('Error: ' . $message, $result['stderr']);
        self::assertSame(self::COMMENTED, $this->read('a.svg'));
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function provideRejectsAnInvalidUsageCases(): iterable
    {
        yield 'an unknown option' => [['--nope', 'a.svg'], 'Unknown option "--nope".'];

        yield 'the 9.x flag for risky rules' => [['-r', 'a.svg'], 'Unknown rule "a.svg".'];

        yield 'the 9.x flag for risky rules without a path' => [['-r'], 'Option "-r" requires a value.'];

        yield 'an option with a value that is another option' => [['-c', '--dry-run', 'a.svg'], 'Option "-c" requires a value.'];

        yield 'the removed process command' => [['--with-all-rules', 'process', 'a.svg'], 'Option "--with-all-rules" was removed;'];

        yield 'a repeated config' => [['-c', '{}', '-c', '{}', 'a.svg'], 'Option "--config" can be given only once.'];

        yield 'a missing configuration file' => [['--config=missing.json', 'a.svg'], 'Configuration file "missing.json" does not exist.'];

        yield 'a configuration that is a directory' => [['--config=.', 'a.svg'], 'Configuration "." is a directory, not a file.'];

        yield 'invalid inline JSON' => [['--config={"rules":', 'a.svg'], 'Invalid inline configuration: The JSON is invalid: '];

        yield 'an inline JSON list' => [['--config=[]', 'a.svg'], 'Invalid inline configuration: The configuration must be a JSON object.'];

        yield 'an unknown configuration key' => [['--config={"rule": {}}', 'a.svg'], 'Invalid inline configuration: Unknown key "rule". Did you mean "rules"?'];

        yield 'a misspelled allowRisky' => [['--config={"allowrisky": true}', 'a.svg'], 'Invalid inline configuration: Unknown key "allowrisky". Did you mean "allowRisky"?'];

        yield 'the 9.x configuration format' => [['--config={"removeComments": true}', 'a.svg'], 'Invalid inline configuration: The 9.x format is no longer supported: "removeComments" belongs below "rules". See UPGRADE-10.0.md.'];

        yield 'a rule of 9.x in the configuration' => [['--config={"rules": {"removeInkscapeFootprints": true}}', 'a.svg'], 'Invalid inline configuration: Rule "removeInkscapeFootprints" was renamed to "removeEditorMarkup".'];

        yield 'a nested configuration value' => [['--config={"rules": {"removeComments": {"a": 1}}}', 'a.svg'], 'Invalid inline configuration: The value of rule "removeComments" must be true or false.'];

        yield 'an unknown set' => [['--sets=banana', 'a.svg'], 'Unknown rule set "banana".'];

        yield 'an unknown rule' => [['--rules=removeComment', 'a.svg'], 'Unknown rule "removeComment". Did you mean "removeComments"?'];

        yield 'an empty rule list item' => [['--rules=removeComments,', 'a.svg'], 'Option "--rules" contains an empty value.'];
    }

    #[Test]
    public function namesTheConfigurationFileInAnInvalidConfigurationFileError(): void
    {
        $this->write('broken.json', '{"rules": ');

        $result = $this->execute(['--config=broken.json', 'a.svg']);

        self::assertSame(ExitCode::InvalidUsage, $result['exitCode']);
        self::assertStringStartsWith('Error: Invalid configuration file "broken.json": The JSON is invalid: ', $result['stderr']);
    }

    #[Test]
    public function treatsEverythingAfterTheEndOfOptionsAsAPath(): void
    {
        $this->write('--dry-run.svg', self::COMMENTED);

        $result = $this->execute(['--', '--dry-run.svg']);

        self::assertSame(ExitCode::Success, $result['exitCode']);
        self::assertStringStartsWith('--dry-run.svg (', $result['stdout']);
        self::assertStringNotContainsString('comment', $this->read('--dry-run.svg'));
    }

    #[Test]
    public function acceptsOptionsAfterThePaths(): void
    {
        $this->write('a.svg', self::COMMENTED);

        $result = $this->execute(['a.svg', '--dry-run', '--rules', 'removeComments']);

        self::assertSame(ExitCode::Success, $result['exitCode']);
        self::assertSame(self::COMMENTED, $this->read('a.svg'));
    }

    #[Test]
    public function acceptsCombinedShortFlags(): void
    {
        $this->write('a.svg', self::COMMENTED);

        $result = $this->execute(['-dq', 'a.svg']);

        self::assertSame(ExitCode::Success, $result['exitCode']);
        self::assertSame('', $result['stdout']);
        self::assertSame(self::COMMENTED, $this->read('a.svg'));
    }

    #[Test]
    public function quietPrintsNothingToTheOutputButStillPrintsErrors(): void
    {
        $this->write('a.svg', self::COMMENTED);
        $this->write('bad.svg', 'not an svg');

        $result = $this->execute(['--quiet', '.']);

        self::assertSame(ExitCode::Failure, $result['exitCode']);
        self::assertSame('', $result['stdout']);
        self::assertStringStartsWith('Error: "bad.svg" is not a valid SVG file: ', $result['stderr']);
        self::assertStringNotContainsString('comment', $this->read('a.svg'));
    }

    #[Test]
    public function quietStillReportsThatFilesWouldChange(): void
    {
        $this->write('a.svg', self::COMMENTED);

        $result = $this->execute(['--quiet', '--check', 'a.svg']);

        self::assertSame(ExitCode::ChangesDetected, $result['exitCode']);
        self::assertSame('', $result['stdout']);
        self::assertSame("Error: 1 file would change.\n", $result['stderr']);
    }

    #[Test]
    public function aFileThatFailsDoesNotStopTheOthers(): void
    {
        $this->write('a.svg', self::COMMENTED);
        $this->write('bad.svg', 'not an svg');
        $this->write('c.svg', self::COMMENTED);

        $result = $this->execute(['.']);

        self::assertSame(ExitCode::Failure, $result['exitCode']);
        self::assertStringContainsString('a.svg (', $result['stdout']);
        self::assertStringContainsString('c.svg (', $result['stdout']);
        self::assertStringNotContainsString('bad.svg', $result['stdout']);
        self::assertStringContainsString("Files:             2\n", $result['stdout']);
        self::assertSame('not an svg', $this->read('bad.svg'));
        self::assertStringNotContainsString('comment', $this->read('c.svg'));
        self::assertStringStartsWith('Error: "bad.svg" is not a valid SVG file: ', $result['stderr']);
    }

    #[Test]
    public function aDocumentThatIsNotAnSvgFailsWithoutAReason(): void
    {
        $this->write('page.svg', '<html xmlns="http://www.w3.org/1999/xhtml"/>');

        $result = $this->execute(['page.svg']);

        self::assertSame(ExitCode::Failure, $result['exitCode']);
        self::assertSame("Error: \"page.svg\" is not a valid SVG file.\n", $result['stderr']);
    }

    #[Test]
    public function aPathThatDoesNotExistDoesNotStopTheOthers(): void
    {
        $this->write('a.svg', self::COMMENTED);

        $result = $this->execute(['missing', 'a.svg']);

        self::assertSame(ExitCode::Failure, $result['exitCode']);
        self::assertSame("Error: \"missing\" does not exist.\n", $result['stderr']);
        self::assertStringStartsWith('a.svg (', $result['stdout']);
    }

    #[Test]
    public function aFileWithoutTheSvgExtensionGivenAsAPathFails(): void
    {
        $this->write('notes.txt', 'text');

        $result = $this->execute(['notes.txt']);

        self::assertSame(ExitCode::Failure, $result['exitCode']);
        self::assertSame("Error: \"notes.txt\" is not an SVG file.\n", $result['stderr']);
        self::assertSame('', $result['stdout']);
    }

    #[Test]
    public function aFileThatCannotBeWrittenFailsWithItsPath(): void
    {
        $this->write('a.svg', self::COMMENTED);
        $this->write('b.svg', self::COMMENTED);
        $this->makeReadOnly($this->directory . '/a.svg');

        try {
            $result = $this->execute(['.']);
        } finally {
            chmod($this->directory . '/a.svg', 0o666);
        }

        self::assertSame(ExitCode::Failure, $result['exitCode']);
        self::assertSame("Error: Failed to write optimized content to the output file: a.svg\n", $result['stderr']);
        self::assertStringNotContainsString('comment', $this->read('b.svg'));
    }

    #[Test]
    public function keepsAFileWhoseOptimizedContentIsLarger(): void
    {
        $original = '<!DOCTYPE svg [<!ENTITY e "' . str_repeat('x', 200) . '">]><svg xmlns="http://www.w3.org/2000/svg">' . str_repeat('<text>&e;</text>', 5) . '</svg>';
        $this->write('big.svg', $original);

        $result = $this->execute(['big.svg']);

        self::assertSame(ExitCode::Success, $result['exitCode']);
        self::assertStringStartsWith("big.svg (skipped, no gain)\n", $result['stdout']);
        self::assertSame($original, $this->read('big.svg'));
    }

    #[Test]
    public function checkDoesNotCountAFileWhoseOptimizedContentIsLarger(): void
    {
        $this->write('big.svg', '<!DOCTYPE svg [<!ENTITY e "' . str_repeat('x', 200) . '">]><svg xmlns="http://www.w3.org/2000/svg">' . str_repeat('<text>&e;</text>', 5) . '</svg>');

        self::assertSame(ExitCode::Success, $this->execute(['--check', 'big.svg'])['exitCode']);
    }

    #[Test]
    public function reportsWhenThereAreNoSvgFiles(): void
    {
        $this->write('empty/readme.txt', 'text');

        $result = $this->execute(['empty']);

        self::assertSame(ExitCode::Success, $result['exitCode']);
        self::assertSame("No SVG files found.\n", $result['stdout']);
    }

    #[Test]
    public function skipsDirectoriesOfDependenciesAndVersionControl(): void
    {
        $this->write('src/a.svg', self::COMMENTED);
        $this->write('src/vendor/b.svg', self::COMMENTED);
        $this->write('src/node_modules/c.svg', self::COMMENTED);
        $this->write('src/.git/d.svg', self::COMMENTED);

        $this->execute(['src']);

        self::assertStringNotContainsString('comment', $this->read('src/a.svg'));
        self::assertSame(self::COMMENTED, $this->read('src/vendor/b.svg'));
        self::assertSame(self::COMMENTED, $this->read('src/node_modules/c.svg'));
        self::assertSame(self::COMMENTED, $this->read('src/.git/d.svg'));
    }

    #[Test]
    public function stylesTheOutputWhenAnsiIsGiven(): void
    {
        $this->write('a.svg', self::COMMENTED);

        $result = $this->execute(['--ansi', '--dry-run', 'a.svg']);

        self::assertStringContainsString("\033[32m", $result['stdout']);
        self::assertStringContainsString("\033[1mSummary:\033[0m", $result['stdout']);
    }

    #[Test]
    public function printsPlainOutputWhenTheStreamsHaveNoColorSupport(): void
    {
        $this->write('a.svg', self::COMMENTED);

        $result = $this->execute(['--dry-run', 'a.svg']);

        self::assertStringNotContainsString("\033", $result['stdout']);
    }

    #[Test]
    public function stylesEachStreamAccordingToItsOwnColorSupport(): void
    {
        $this->write('bad.svg', 'not an svg');
        $this->write('a.svg', self::COMMENTED);
        $output = new MemoryStream(false);
        $error = new MemoryStream(true);

        (new Application($output, $error))->run(['--dry-run', '.']);

        self::assertStringNotContainsString("\033", $output->getContent());
        self::assertStringStartsWith("\033[31mError: ", $error->getContent());
    }

    /**
     * @param list<string> $arguments
     *
     * @return array{exitCode: ExitCode, stdout: string, stderr: string}
     */
    private function execute(array $arguments): array
    {
        $output = new MemoryStream(false);
        $error = new MemoryStream(false);

        $exitCode = (new Application($output, $error))->run($arguments);

        return [
            'exitCode' => $exitCode,
            'stdout' => str_replace(\PHP_EOL, "\n", $output->getContent()),
            'stderr' => str_replace(\PHP_EOL, "\n", $error->getContent()),
        ];
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    private function defaultsWithout(): string
    {
        return (new SvgOptimizer())->withDefaultRules()->withoutRules(Rule::RemoveComments)->optimizeString(self::COMMENTED)->getContent();
    }

    private function write(string $path, string $content): void
    {
        $file = $this->directory . '/' . $path;
        if (!is_dir(\dirname($file))) {
            mkdir(\dirname($file), 0o777, true);
        }

        file_put_contents($file, $content);
    }

    private function read(string $path): string
    {
        return (string) file_get_contents($this->directory . '/' . $path);
    }
}
