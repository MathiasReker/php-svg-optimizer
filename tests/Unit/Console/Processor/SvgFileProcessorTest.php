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
use MathiasReker\PhpSvgOptimizer\Console\Output\Manager\OutputManager;
use MathiasReker\PhpSvgOptimizer\Console\Output\MetaDataAggregator;
use MathiasReker\PhpSvgOptimizer\Console\Output\Stream\AbstractStream;
use MathiasReker\PhpSvgOptimizer\Console\Output\Stream\MemoryStream;
use MathiasReker\PhpSvgOptimizer\Console\Processor\SvgFileProcessor;
use MathiasReker\PhpSvgOptimizer\Console\ValueObject\CommandOption;
use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Model\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Data\MetaData;
use MathiasReker\PhpSvgOptimizer\Service\Facade\SvgOptimizerFacade;
use MathiasReker\PhpSvgOptimizer\Service\Formatter\XmlFormatter;
use MathiasReker\PhpSvgOptimizer\Service\Processor\AbstractXmlProcessor;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\AbstractProvider;
use MathiasReker\PhpSvgOptimizer\Service\Provider\FileProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertColorsToHex;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertCssClassesToAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertEllipseToCircle;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertEmptyTagsToSelfClosing;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertInlineStylesToAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertOneStopGradients;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertShapesToPath;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
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
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDoctype;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDuplicateElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyContainers;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyGroups;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyTextElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEnableBackgroundAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveInkscapeFootprints;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveInvisibleCharacters;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveMetadata;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveNonStandardAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveNonStandardTags;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveStyleTypeAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveTitleAndDesc;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnnecessaryWhitespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnsafeElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedIds;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedMasks;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedNamespaces;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUselessDefs;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveWidthHeightAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveXmlSpaceDefault;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveZeroSizeShapes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ScopeSvgStyles;
use MathiasReker\PhpSvgOptimizer\Service\Rule\SortAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use MathiasReker\PhpSvgOptimizer\Type\Rule;
use MathiasReker\PhpSvgOptimizer\ValueObject\Metrics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SvgFileProcessor::class)]
#[CoversClass(OutputManager::class)]
#[CoversClass(MemoryStream::class)]
#[CoversClass(MetaDataAggregator::class)]
#[CoversClass(CommandOption::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(FileProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(XmlFormatter::class)]
#[CoversClass(SvgOptimizerFacade::class)]
#[CoversClass(Rule::class)]
#[CoversClass(MetaData::class)]
#[CoversClass(Metrics::class)]
#[CoversClass(AbstractProvider::class)]
#[CoversClass(AbstractStream::class)]
#[CoversClass(Finder::class)]
#[CoversClass(ConfigLoader::class)]
#[CoversClass(RemoveComments::class)]
#[CoversClass(AbstractXmlProcessor::class)]
#[CoversClass(ConvertColorsToHex::class)]
#[CoversClass(ConvertCssClassesToAttributes::class)]
#[CoversClass(ConvertEllipseToCircle::class)]
#[CoversClass(ConvertEmptyTagsToSelfClosing::class)]
#[CoversClass(ConvertInlineStylesToAttributes::class)]
#[CoversClass(ConvertOneStopGradients::class)]
#[CoversClass(ConvertShapesToPath::class)]
#[CoversClass(SvgAttribute::class)]
#[CoversClass(SvgNamespace::class)]
#[CoversClass(SvgTag::class)]
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
#[CoversClass(RemoveDoctype::class)]
#[CoversClass(RemoveDuplicateElements::class)]
#[CoversClass(RemoveEmptyAttributes::class)]
#[CoversClass(RemoveEmptyContainers::class)]
#[CoversClass(RemoveEmptyGroups::class)]
#[CoversClass(RemoveEmptyTextElements::class)]
#[CoversClass(RemoveEnableBackgroundAttribute::class)]
#[CoversClass(RemoveInkscapeFootprints::class)]
#[CoversClass(RemoveInvisibleCharacters::class)]
#[CoversClass(RemoveMetadata::class)]
#[CoversClass(RemoveTitleAndDesc::class)]
#[CoversClass(RemoveUnnecessaryWhitespace::class)]
#[CoversClass(RemoveUnsafeElements::class)]
#[CoversClass(RemoveUnusedIds::class)]
#[CoversClass(RemoveUnusedMasks::class)]
#[CoversClass(RemoveUnusedNamespaces::class)]
#[CoversClass(RemoveUselessDefs::class)]
#[CoversClass(RemoveWidthHeightAttributes::class)]
#[CoversClass(RemoveXmlSpaceDefault::class)]
#[CoversClass(RemoveZeroSizeShapes::class)]
#[CoversClass(ScopeSvgStyles::class)]
#[CoversClass(SortAttributes::class)]
#[CoversClass(RemoveNonStandardAttributes::class)]
#[CoversClass(RemoveNonStandardTags::class)]
#[CoversClass(RemoveStyleTypeAttributes::class)]
final class SvgFileProcessorTest extends TestCase
{
    private string $tempDir;

    private string $svgFile;

    private OutputManager $outputManager;

    private MetaDataAggregator $metaDataAggregator;

    private MemoryStream $memoryStream;

    /**
     * @throws \JsonException
     * @throws \LogicException
     * @throws RiskyRulesNotAllowedException
     * @throws \RuntimeException
     * @throws \ValueError
     */
    #[Test]
    public function processSingleSvgFile(): void
    {
        $commandOption = new CommandOption(
            false,
            '',
            true,
            false,
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
     * @throws \LogicException
     * @throws RiskyRulesNotAllowedException
     * @throws \RuntimeException
     * @throws \ValueError
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
     * @throws \LogicException
     * @throws RiskyRulesNotAllowedException
     * @throws \RuntimeException
     * @throws \ValueError
     */
    #[Test]
    public function processInvalidPathPrintsError(): void
    {
        $commandOption = new CommandOption(
            true,
            '',
            false,
            false,
        );

        $svgFileProcessor = new SvgFileProcessor(
            $commandOption,
            $this->outputManager,
            $this->metaDataAggregator
        );

        $svgFileProcessor->processPath($this->tempDir . '/nonexistent.svg');

        $output = $this->memoryStream->getContent();
        self::assertStringContainsString('is not a valid SVG file or directory', $output);
    }

    /**
     * @throws \JsonException
     * @throws \LogicException
     * @throws RiskyRulesNotAllowedException
     * @throws \RuntimeException
     * @throws \ValueError
     */
    #[Test]
    public function processWithRiskyRulesAllowed(): void
    {
        $commandOption = new CommandOption(
            true,
            '',
            true,
            false,
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
     * @throws \LogicException
     * @throws RiskyRulesNotAllowedException
     * @throws \RuntimeException
     * @throws \ValueError
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
     * @throws \LogicException
     * @throws RiskyRulesNotAllowedException
     * @throws \RuntimeException
     * @throws \ValueError
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
        $this->outputManager = new OutputManager($this->memoryStream, false);
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
}
