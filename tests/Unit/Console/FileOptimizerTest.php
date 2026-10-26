<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console;

use MathiasReker\PhpSvgOptimizer\Console\FileOptimizer;
use MathiasReker\PhpSvgOptimizer\Console\Output\Printer;
use MathiasReker\PhpSvgOptimizer\Console\Output\Style;
use MathiasReker\PhpSvgOptimizer\Console\RunSummary;
use MathiasReker\PhpSvgOptimizer\Exception\InvalidSvgException;
use MathiasReker\PhpSvgOptimizer\Exception\IOException;
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
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDefaultAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDefaultTypeAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDefaultXmlSpace;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDuplicateElements;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEditorMarkup;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEmptyAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEmptyContainers;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEmptyTextElements;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEnableBackground;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveHiddenElements;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveIneffectiveDefinitions;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveIneffectiveGroupAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveIneffectiveStrokeAndFill;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveIneffectiveTextAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveInvisibleCharacters;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveMetadata;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveNonStandardAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveNonStandardElements;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveUnusedNamespaces;
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
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AttributeNodeTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\DefinitionElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementNameTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementRemovalTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementRenameTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberLiteralTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Path\PathDataParserTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Path\PathDataSerializerTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Path\PathDataTokenTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Presentation\IneffectiveAttributeTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Presentation\InheritanceTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Presentation\InheritedValueTrait;
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
use MathiasReker\PhpSvgOptimizer\Xml\EntityReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Xml\Parser;
use MathiasReker\PhpSvgOptimizer\Xml\Serializer;
use MathiasReker\PhpSvgOptimizer\Xml\SvgValidator;
use MathiasReker\PhpSvgOptimizer\Xml\TextWhitespaceTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(FileOptimizer::class)]
#[UsesClass(Attribute::class)]
#[UsesClass(ConvertCssClassesToAttributes::class)]
#[UsesClass(ConvertEllipsesToCircles::class)]
#[UsesClass(ConvertInlineStylesToAttributes::class)]
#[UsesClass(ConvertShapesToPaths::class)]
#[UsesClass(Element::class)]
#[UsesClass(ElementAttributeMap::class)]
#[UsesClass(ExecutionOrder::class)]
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
#[UsesClass(OptimizationResult::class)]
#[UsesClass(Parser::class)]
#[UsesClass(Pipeline::class)]
#[UsesClass(Printer::class)]
#[UsesClass(RemoveComments::class)]
#[UsesClass(RemoveDefaultAttributes::class)]
#[UsesClass(RemoveDefaultTypeAttributes::class)]
#[UsesClass(RemoveDefaultXmlSpace::class)]
#[UsesClass(RemoveDuplicateElements::class)]
#[UsesClass(RemoveEditorMarkup::class)]
#[UsesClass(RemoveEmptyAttributes::class)]
#[UsesClass(RemoveEmptyContainers::class)]
#[UsesClass(RemoveEmptyTextElements::class)]
#[UsesClass(RemoveEnableBackground::class)]
#[UsesClass(RemoveHiddenElements::class)]
#[UsesClass(RemoveIneffectiveDefinitions::class)]
#[UsesClass(RemoveIneffectiveGroupAttributes::class)]
#[UsesClass(RemoveIneffectiveStrokeAndFill::class)]
#[UsesClass(RemoveIneffectiveTextAttributes::class)]
#[UsesClass(RemoveInvisibleCharacters::class)]
#[UsesClass(RemoveMetadata::class)]
#[UsesClass(RemoveNonStandardAttributes::class)]
#[UsesClass(RemoveNonStandardElements::class)]
#[UsesClass(RemoveUnusedNamespaces::class)]
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
#[UsesTrait(AttributeNodeTrait::class)]
#[UsesTrait(ClassSelectorTrait::class)]
#[UsesTrait(DeclarationTrait::class)]
#[UsesTrait(DefinitionElementTrait::class)]
#[UsesTrait(ElementNameTrait::class)]
#[UsesTrait(ElementRemovalTrait::class)]
#[UsesTrait(ElementRenameTrait::class)]
#[UsesTrait(ElementTrait::class)]
#[UsesTrait(EntityReferenceTrait::class)]
#[UsesTrait(IdReferenceTrait::class)]
#[UsesTrait(IdTrait::class)]
#[UsesTrait(IneffectiveAttributeTrait::class)]
#[UsesTrait(InheritanceTrait::class)]
#[UsesTrait(InheritedValueTrait::class)]
#[UsesTrait(NumberLiteralTrait::class)]
#[UsesTrait(PathDataParserTrait::class)]
#[UsesTrait(PathDataSerializerTrait::class)]
#[UsesTrait(PathDataTokenTrait::class)]
#[UsesTrait(ScriptingTrait::class)]
#[UsesTrait(SelectorTrait::class)]
#[UsesTrait(StyleElementTrait::class)]
#[UsesTrait(StyleSheetTrait::class)]
#[UsesTrait(TextWhitespaceTrait::class)]
#[UsesTrait(XlinkMigrationTrait::class)]
final class FileOptimizerTest extends TestCase
{
    use TemporaryDirectoryTrait;

    private const string SVG = '<svg xmlns="http://www.w3.org/2000/svg"><!-- comment --><rect width="10" height="10" fill="#ff0000"/></svg>';

    private MemoryStream $memoryStream;

    private RunSummary $runSummary;

    #[\Override]
    protected function setUp(): void
    {
        $this->memoryStream = new MemoryStream(false);
        $this->runSummary = new RunSummary();
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws IOException
     * @throws InvalidSvgException
     */
    #[Test]
    public function writesTheOptimizedFileAndReportsTheSavings(): void
    {
        $path = $this->createFile(self::SVG);

        $this->createFileOptimizer(true)->optimizePath($path);

        self::assertSame((new SvgOptimizer())->withDefaultRules()->optimizeString(self::SVG)->getContent(), file_get_contents($path));
        self::assertStringNotContainsString('comment', (string) file_get_contents($path));
        self::assertMatchesRegularExpression('/^' . preg_quote($path, '/') . ' \(\d+\.\d\d%\)\R$/', $this->memoryStream->getContent());
        self::assertSame(1, $this->runSummary->getFileCount());
        self::assertSame(1, $this->runSummary->getChangedFileCount());
        self::assertSame(mb_strlen(self::SVG), $this->runSummary->getOriginalSizeInBytes());
        self::assertSame((int) filesize($path), $this->runSummary->getOptimizedSizeInBytes());
        self::assertSame([basename($path)], $this->listDirectory(\dirname($path)));
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws IOException
     * @throws InvalidSvgException
     */
    #[Test]
    public function leavesTheFileAloneWhenWritingIsDisabledAndStillCountsTheChange(): void
    {
        $path = $this->createFile(self::SVG);

        $this->createFileOptimizer(false)->optimizePath($path);

        self::assertSame(self::SVG, file_get_contents($path));
        self::assertSame(1, $this->runSummary->getChangedFileCount());
        self::assertSame(1, $this->runSummary->getFileCount());
        self::assertGreaterThan(0, $this->runSummary->getSavedSizeInBytes());
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws IOException
     * @throws InvalidSvgException
     */
    #[Test]
    public function doesNotWriteAFileThatDoesNotChange(): void
    {
        $path = $this->createFile((new SvgOptimizer())->withDefaultRules()->optimizeString(self::SVG)->getContent());
        $this->makeReadOnly($path);

        try {
            $this->createFileOptimizer(true)->optimizePath($path);
        } finally {
            chmod($path, 0o666);
        }

        self::assertSame(0, $this->runSummary->getChangedFileCount());
        self::assertSame(1, $this->runSummary->getFileCount());
        self::assertSame($path . ' (0.00%)' . \PHP_EOL, $this->memoryStream->getContent());
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws IOException
     * @throws InvalidSvgException
     */
    #[Test]
    public function keepsAFileWhoseOptimizedContentIsLarger(): void
    {
        $entity = str_repeat('x', 200);
        $original = '<!DOCTYPE svg [<!ENTITY e "' . $entity . '">]><svg xmlns="http://www.w3.org/2000/svg">' . str_repeat('<text>&e;</text>', 5) . '</svg>';
        $path = $this->createFile($original);

        $this->createFileOptimizer(true)->optimizePath($path);

        self::assertSame($original, file_get_contents($path));
        self::assertSame($path . ' (skipped, no gain)' . \PHP_EOL, $this->memoryStream->getContent());
        self::assertSame(0, $this->runSummary->getChangedFileCount());
        self::assertSame(1, $this->runSummary->getFileCount());
        self::assertSame(0, $this->runSummary->getSavedSizeInBytes());
        self::assertSame(mb_strlen($original), $this->runSummary->getOptimizedSizeInBytes());
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws InvalidSvgException
     * @throws IOException
     */
    #[Test]
    public function throwsWhenTheFileDoesNotExist(): void
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('Input file does not exist: missing.svg');

        $this->createFileOptimizer(true)->optimizePath('missing.svg');
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws IOException
     * @throws InvalidSvgException
     */
    #[Test]
    public function throwsWhenTheFileIsNotAnSvg(): void
    {
        $path = $this->createFile('not an svg');

        $this->expectException(InvalidSvgException::class);

        try {
            $this->createFileOptimizer(true)->optimizePath($path);
        } finally {
            self::assertSame('', $this->memoryStream->getContent());
            self::assertSame(0, $this->runSummary->getFileCount());
        }
    }

    /**
     * @throws IOException
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function throwsWhenARiskyRuleIsSelectedWithoutAllowingRiskyRules(): void
    {
        $path = $this->createFile(self::SVG);

        $this->expectException(RiskyRulesNotAllowedException::class);

        (new FileOptimizer((new SvgOptimizer())->withRules(Rule::RemoveUnusedIds), $this->createPrinter(), $this->runSummary, true))->optimizePath($path);
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws InvalidSvgException
     * @throws IOException
     */
    #[Test]
    public function throwsWhenTheFileCannotBeWritten(): void
    {
        $path = $this->createFile(self::SVG);
        $this->makeReadOnly($path);

        try {
            $this->expectException(IOException::class);
            $this->expectExceptionMessage('Failed to write optimized content to the output file: ' . $path);

            $this->createFileOptimizer(true)->optimizePath($path);
        } finally {
            chmod($path, 0o666);
        }
    }

    private function createFileOptimizer(bool $writing): FileOptimizer
    {
        return new FileOptimizer((new SvgOptimizer())->withDefaultRules(), $this->createPrinter(), $this->runSummary, $writing);
    }

    private function createPrinter(): Printer
    {
        return new Printer($this->memoryStream, new MemoryStream(false), false, false);
    }

    private function createFile(string $content): string
    {
        $path = $this->createTemporaryDirectory() . '/image.svg';
        file_put_contents($path, $content);

        return $path;
    }
}
