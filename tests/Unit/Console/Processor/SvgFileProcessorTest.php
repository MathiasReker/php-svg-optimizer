<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Processor;

use MathiasReker\PhpSvgOptimizer\Console\Filesystem\Finder;
use MathiasReker\PhpSvgOptimizer\Console\Input\ConfigLoader;
use MathiasReker\PhpSvgOptimizer\Console\Input\RuleSetParser;
use MathiasReker\PhpSvgOptimizer\Console\Output\Manager\OutputManager;
use MathiasReker\PhpSvgOptimizer\Console\Output\MetaDataAggregator;
use MathiasReker\PhpSvgOptimizer\Console\Output\Stream\AbstractStream;
use MathiasReker\PhpSvgOptimizer\Console\Output\Stream\MemoryStream;
use MathiasReker\PhpSvgOptimizer\Console\Processor\SvgFileProcessor;
use MathiasReker\PhpSvgOptimizer\Console\ValueObject\CommandOption;
use MathiasReker\PhpSvgOptimizer\File\SvgFile;
use MathiasReker\PhpSvgOptimizer\Metrics;
use MathiasReker\PhpSvgOptimizer\OptimizationResult;
use MathiasReker\PhpSvgOptimizer\Pass\ConvertCssClassesToAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\ConvertEllipsesToCircles;
use MathiasReker\PhpSvgOptimizer\Pass\ConvertInlineStylesToAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\ConvertOneStopGradientsToColors;
use MathiasReker\PhpSvgOptimizer\Pass\ConvertShapesToPaths;
use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Pass\FixAttributeNames;
use MathiasReker\PhpSvgOptimizer\Pass\FlattenGroups;
use MathiasReker\PhpSvgOptimizer\Pass\MergeDuplicateGradients;
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
use MathiasReker\PhpSvgOptimizer\Pass\PrefixIdsAndClasses;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveAriaAttributes;
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
use MathiasReker\PhpSvgOptimizer\Pass\RemoveTitles;
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
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Presentation\IneffectiveAttributeTrait;
use MathiasReker\PhpSvgOptimizer\Rule;
use MathiasReker\PhpSvgOptimizer\RuleSet;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
use MathiasReker\PhpSvgOptimizer\Svg\ElementAttributeMap;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;
use MathiasReker\PhpSvgOptimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Xml\Parser;
use MathiasReker\PhpSvgOptimizer\Xml\Serializer;
use MathiasReker\PhpSvgOptimizer\Xml\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SvgFileProcessor::class)]
#[CoversClass(AbstractStream::class)]
#[CoversClass(Attribute::class)]
#[CoversClass(CommandOption::class)]
#[CoversClass(ConfigLoader::class)]
#[CoversClass(ConvertCssClassesToAttributes::class)]
#[CoversClass(ConvertEllipsesToCircles::class)]
#[CoversClass(ConvertInlineStylesToAttributes::class)]
#[CoversClass(ConvertOneStopGradientsToColors::class)]
#[CoversClass(ConvertShapesToPaths::class)]
#[CoversClass(Element::class)]
#[CoversClass(ExecutionOrder::class)]
#[CoversClass(Finder::class)]
#[CoversClass(FixAttributeNames::class)]
#[CoversClass(FlattenGroups::class)]
#[CoversClass(MemoryStream::class)]
#[CoversClass(MetaDataAggregator::class)]
#[CoversClass(Metrics::class)]
#[CoversClass(MigrateDeprecatedAttributes::class)]
#[CoversClass(MinifyAttributeWhitespace::class)]
#[CoversClass(MinifyColors::class)]
#[CoversClass(MinifyNumberLists::class)]
#[CoversClass(MinifyNumericValues::class)]
#[CoversClass(MinifyPathData::class)]
#[CoversClass(MinifyTransforms::class)]
#[CoversClass(NamespaceUri::class)]
#[CoversClass(OutputManager::class)]
#[CoversClass(Parser::class)]
#[CoversClass(Pipeline::class)]
#[CoversClass(PrefixIdsAndClasses::class)]
#[CoversClass(RemoveAriaAttributes::class)]
#[CoversClass(RemoveComments::class)]
#[CoversClass(RemoveDataAttributes::class)]
#[CoversClass(RemoveDefaultAttributes::class)]
#[CoversClass(RemoveDefaultTypeAttributes::class)]
#[CoversClass(RemoveDefaultXmlSpace::class)]
#[CoversClass(RemoveDescriptions::class)]
#[CoversClass(RemoveDuplicateElements::class)]
#[CoversClass(RemoveEditorMarkup::class)]
#[CoversClass(RemoveEmptyAttributes::class)]
#[CoversClass(RemoveEmptyContainers::class)]
#[CoversClass(RemoveEmptyTextElements::class)]
#[CoversClass(RemoveEnableBackground::class)]
#[CoversClass(RemoveForeignContent::class)]
#[CoversClass(RemoveIneffectiveDefinitions::class)]
#[CoversClass(RemoveInvisibleCharacters::class)]
#[CoversClass(RemoveMetadata::class)]
#[CoversClass(RemoveNonStandardAttributes::class)]
#[CoversClass(RemoveNonStandardElements::class)]
#[CoversClass(RemoveScripts::class)]
#[CoversClass(RemoveTitles::class)]
#[CoversClass(RemoveUnsafeMarkup::class)]
#[CoversClass(RemoveUnsafeStyles::class)]
#[CoversClass(RemoveUnsafeUrls::class)]
#[CoversClass(RemoveUnusedIds::class)]
#[CoversClass(RemoveUnusedNamespaces::class)]
#[CoversClass(RemoveUseBombs::class)]
#[CoversClass(RemoveWidthHeightAttributes::class)]
#[CoversClass(RemoveZeroSizeShapes::class)]
#[CoversClass(Rule::class)]
#[CoversClass(RuleSet::class)]
#[CoversClass(RuleSetParser::class)]
#[CoversClass(SortAttributes::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(SvgValidator::class)]
#[UsesClass(ElementAttributeMap::class)]
#[UsesClass(MergeDuplicateGradients::class)]
#[UsesClass(MergeStyleSheets::class)]
#[UsesClass(MigrateToSvg2::class)]
#[UsesClass(MinifyStyleSheets::class)]
#[UsesClass(MoveCommonAttributesToGroups::class)]
#[UsesClass(MoveGroupTransformsToChildren::class)]
#[UsesClass(OptimizationResult::class)]
#[UsesClass(RemoveHiddenElements::class)]
#[UsesClass(RemoveIneffectiveGroupAttributes::class)]
#[UsesClass(RemoveIneffectiveStrokeAndFill::class)]
#[UsesClass(RemoveIneffectiveTextAttributes::class)]
#[UsesClass(RemoveUnusedDefinitions::class)]
#[UsesClass(Serializer::class)]
#[UsesClass(SortDefinitions::class)]
#[UsesClass(SvgFile::class)]
#[UsesTrait(IneffectiveAttributeTrait::class)]
final class SvgFileProcessorTest extends TestCase
{
    private string $tempDir;

    private string $svgFile;

    private OutputManager $outputManager;

    private MetaDataAggregator $metaDataAggregator;

    private MemoryStream $memoryStream;

    /**
     * @throws \JsonException
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[Test]
    public function processSingleSvgFile(): void
    {
        $commandOption = new CommandOption(
            false,
            '',
            true,
            false,
            [],
        );

        $svgFileProcessor = new SvgFileProcessor(
            $commandOption,
            $this->outputManager,
            $this->metaDataAggregator
        );

        $originalSize = filesize($this->svgFile);

        $svgFileProcessor->processPath($this->svgFile);

        $contents = file_get_contents($this->svgFile);
        self::assertIsString($contents);
        self::assertStringContainsString('<svg', $contents);

        self::assertSame(1, $this->metaDataAggregator->getOptimizedFileCount());
        self::assertSame($originalSize, $this->metaDataAggregator->getTotalOriginalSize());
        self::assertSame(mb_strlen($contents), $this->metaDataAggregator->getTotalOptimizedSize());
        self::assertStringContainsString($this->svgFile . ' (', $this->memoryStream->getContent());
    }

    /**
     * @throws \JsonException
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[Test]
    public function processDirectory(): void
    {
        $file2 = $this->tempDir . '/file2.svg';
        file_put_contents($file2, '<svg><rect width="10" height="10"></rect></svg>');

        $commandOption = new CommandOption(
            true,
            '',
            false,
            false,
            [],
        );

        $svgFileProcessor = new SvgFileProcessor(
            $commandOption,
            $this->outputManager,
            $this->metaDataAggregator
        );

        $svgFileSize = filesize($this->svgFile);
        $file2Size = filesize($file2);
        self::assertNotFalse($svgFileSize);
        self::assertNotFalse($file2Size);
        $originalSize = $svgFileSize + $file2Size;

        $svgFileProcessor->processPath($this->tempDir);

        self::assertSame(2, $this->metaDataAggregator->getOptimizedFileCount());
        self::assertSame($originalSize, $this->metaDataAggregator->getTotalOriginalSize());
    }

    /**
     * @throws \JsonException
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[Test]
    public function processInvalidPathThrows(): void
    {
        $commandOption = new CommandOption(
            true,
            '',
            false,
            false,
            [],
        );

        $svgFileProcessor = new SvgFileProcessor(
            $commandOption,
            $this->outputManager,
            $this->metaDataAggregator
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('"%s" is not a valid SVG file or directory.', $this->tempDir . '/nonexistent.svg'));

        $svgFileProcessor->processPath($this->tempDir . '/nonexistent.svg');
    }

    /**
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getUnknownConfigKeysReturnsAListOfStrings(): void
    {
        $svgFileProcessor = new SvgFileProcessor(
            new CommandOption(true, '{"removeComments":true,"removeComents":false,"0":true}', false, false, []),
            $this->outputManager,
            $this->metaDataAggregator
        );

        self::assertSame(['removeComents', '0'], $svgFileProcessor->getUnknownConfigKeys());
    }

    /**
     * @throws \JsonException
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[Test]
    public function processFileWithUppercaseExtension(): void
    {
        $uppercaseFile = $this->tempDir . '/upper.SVG';
        file_put_contents($uppercaseFile, '<svg><!-- comment --><rect width="10" height="10"></rect></svg>');

        $svgFileProcessor = new SvgFileProcessor(
            new CommandOption(false, '{"removeComments":true}', false, false, []),
            $this->outputManager,
            $this->metaDataAggregator
        );

        $svgFileProcessor->processPath($uppercaseFile);

        self::assertSame(1, $this->metaDataAggregator->getOptimizedFileCount());
        self::assertStringNotContainsString('Error: ', $this->memoryStream->getContent());
        self::assertStringNotContainsString('<!--', (string) file_get_contents($uppercaseFile));
    }

    /**
     * @throws \JsonException
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[Test]
    public function processWithRiskyRulesAllowed(): void
    {
        $commandOption = new CommandOption(
            true,
            '',
            true,
            false,
            [],
        );

        $svgFileProcessor = new SvgFileProcessor(
            $commandOption,
            $this->outputManager,
            $this->metaDataAggregator
        );

        $svgFileProcessor->processPath($this->svgFile);

        self::assertGreaterThanOrEqual(0, $this->metaDataAggregator->getTotalOriginalSize());
    }

    /**
     * @throws \JsonException
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[Test]
    public function itDoesNotModifyFileOnDryRun(): void
    {
        $originalContent = file_get_contents($this->svgFile);

        $commandOption = new CommandOption(
            true,
            '',
            true,
            true,
            [],
        );

        $svgFileProcessor = new SvgFileProcessor(
            $commandOption,
            $this->outputManager,
            $this->metaDataAggregator
        );

        $svgFileProcessor->processPath($this->svgFile);

        self::assertSame($originalContent, file_get_contents($this->svgFile));
    }

    /**
     * @throws \JsonException
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[Test]
    public function itUsesConfigFile(): void
    {
        $configFile = $this->tempDir . '/config.json';
        file_put_contents($configFile, '{"removeComments": true}');

        $commandOption = new CommandOption(
            false,
            $configFile,
            false,
            false,
            [],
        );

        $svgFileProcessor = new SvgFileProcessor(
            $commandOption,
            $this->outputManager,
            $this->metaDataAggregator
        );

        $svgFileProcessor->processPath($this->svgFile);

        self::assertStringNotContainsString('<!--', (string) file_get_contents($this->svgFile));
    }

    /**
     * @throws \RuntimeException
     */
    #[\Override]
    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/svg_test_' . uniqid();
        if (!mkdir($this->tempDir) && !is_dir($this->tempDir)) {
            throw new \RuntimeException('Failed to create temp directory');
        }

        $this->svgFile = $this->tempDir . '/test.svg';
        file_put_contents(
            $this->svgFile,
            '<svg height="100" width="100"><!-- comment --><circle cx="50" cy="50" r="40"></circle></svg>'
        );

        $this->memoryStream = new MemoryStream();
        $this->outputManager = new OutputManager($this->memoryStream, false, $this->memoryStream);
        $this->metaDataAggregator = new MetaDataAggregator();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $files = glob($this->tempDir . '/*');
        if (false !== $files) {
            foreach ($files as $file) {
                unlink($file);
            }
        }

        rmdir($this->tempDir);
    }

    /**
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getUnknownConfigKeysDoesNotReportTheRuleSets(): void
    {
        $svgFileProcessor = new SvgFileProcessor(
            new CommandOption(true, '{"sets":["cleanup"],"removeComents":true}', false, false, []),
            $this->outputManager,
            $this->metaDataAggregator
        );

        self::assertSame(['removeComents'], $svgFileProcessor->getUnknownConfigKeys());
    }

    /**
     * @throws \JsonException
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[Test]
    public function processAppliesTheRuleSetsOfTheOptionsAndOfTheConfiguration(): void
    {
        $content = '<svg xmlns="http://www.w3.org/2000/svg" data-x="1"><!-- comment --><rect width="10" height="10" fill="red"/></svg>';
        file_put_contents($this->svgFile, $content);

        $svgFileProcessor = new SvgFileProcessor(
            new CommandOption(false, '{"sets":["conversion"]}', false, false, [RuleSet::Cleanup]),
            $this->outputManager,
            $this->metaDataAggregator
        );

        $svgFileProcessor->processPath($this->svgFile);

        $expected = (new SvgOptimizer())
            ->withRuleSets(RuleSet::Cleanup, RuleSet::Conversion)
            ->optimizeString($content)
            ->getContent();

        self::assertSame($expected, file_get_contents($this->svgFile));
        self::assertStringNotContainsString('<!--', $expected);
        self::assertStringContainsString('<path', $expected);
        self::assertStringContainsString('data-x="1"', $expected);
    }
}
