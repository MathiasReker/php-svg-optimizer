<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Command;

use MathiasReker\PhpSvgOptimizer\Console\Command\Command;
use MathiasReker\PhpSvgOptimizer\Console\Filesystem\Finder;
use MathiasReker\PhpSvgOptimizer\Console\Help\ArgumentData;
use MathiasReker\PhpSvgOptimizer\Console\Input\ConfigLoader;
use MathiasReker\PhpSvgOptimizer\Console\Output\Formatter\ByteFormatter;
use MathiasReker\PhpSvgOptimizer\Console\Output\Manager\OutputManager;
use MathiasReker\PhpSvgOptimizer\Console\Output\MetaDataAggregator;
use MathiasReker\PhpSvgOptimizer\Console\Output\Stream\MemoryStream;
use MathiasReker\PhpSvgOptimizer\Console\Processor\SvgFileProcessor;
use MathiasReker\PhpSvgOptimizer\Console\Type\Option;
use MathiasReker\PhpSvgOptimizer\Console\ValueObject\CliOption;
use MathiasReker\PhpSvgOptimizer\Console\ValueObject\CommandHelp;
use MathiasReker\PhpSvgOptimizer\Console\ValueObject\CommandOption;
use MathiasReker\PhpSvgOptimizer\Console\ValueObject\ExampleCommand;
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
use MathiasReker\PhpSvgOptimizer\Service\Rule\FlattenGroups;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyListValues;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyNumericValues;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyPathData;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifySvgCoordinates;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyTransformations;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveComments;
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
#[CoversClass(Command::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(MetaData::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(XmlFormatter::class)]
#[CoversClass(AbstractXmlProcessor::class)]
#[CoversClass(AbstractProvider::class)]
#[CoversClass(FileProvider::class)]
#[CoversClass(ConvertColorsToHex::class)]
#[CoversClass(ConvertEmptyTagsToSelfClosing::class)]
#[CoversClass(FlattenGroups::class)]
#[CoversClass(MinifyListValues::class)]
#[CoversClass(MinifyNumericValues::class)]
#[CoversClass(MinifyPathData::class)]
#[CoversClass(MinifySvgCoordinates::class)]
#[CoversClass(MinifyTransformations::class)]
#[CoversClass(RemoveComments::class)]
#[CoversClass(RemoveDefaultAttributes::class)]
#[CoversClass(RemoveDeprecatedAttributes::class)]
#[CoversClass(RemoveDoctype::class)]
#[CoversClass(RemoveEmptyAttributes::class)]
#[CoversClass(RemoveEmptyContainers::class)]
#[CoversClass(RemoveEnableBackgroundAttribute::class)]
#[CoversClass(RemoveInkscapeFootprints::class)]
#[CoversClass(RemoveInvisibleCharacters::class)]
#[CoversClass(RemoveMetadata::class)]
#[CoversClass(RemoveStyleTypeAttributes::class)]
#[CoversClass(RemoveTitleAndDesc::class)]
#[CoversClass(RemoveUnnecessaryWhitespace::class)]
#[CoversClass(RemoveUnsafeElements::class)]
#[CoversClass(RemoveUnusedIds::class)]
#[CoversClass(RemoveUnusedNamespaces::class)]
#[CoversClass(RemoveUselessDefs::class)]
#[CoversClass(SvgOptimizerFacade::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(Rule::class)]
#[CoversClass(Metrics::class)]
#[CoversClass(MemoryStream::class)]
#[CoversClass(ArgumentData::class)]
#[CoversClass(Option::class)]
#[CoversClass(CliOption::class)]
#[CoversClass(ExampleCommand::class)]
#[CoversClass(CommandHelp::class)]
#[CoversClass(OutputManager::class)]
#[CoversClass(MetaDataAggregator::class)]
#[CoversClass(CommandOption::class)]
#[CoversClass(SvgFileProcessor::class)]
#[CoversClass(ConfigLoader::class)]
#[CoversClass(ByteFormatter::class)]
#[CoversClass(SortAttributes::class)]
#[CoversClass(Finder::class)]
#[CoversClass(ConvertCssClassesToAttributes::class)]
#[CoversClass(ConvertEllipseToCircle::class)]
#[CoversClass(ConvertInlineStylesToAttributes::class)]
#[CoversClass(ConvertOneStopGradients::class)]
#[CoversClass(ConvertShapesToPath::class)]
#[CoversClass(RemoveUnusedMasks::class)]
#[CoversClass(RemoveWidthHeightAttributes::class)]
#[CoversClass(RemoveXmlSpaceDefault::class)]
#[CoversClass(RemoveZeroSizeShapes::class)]
#[CoversClass(RemoveDuplicateElements::class)]
#[CoversClass(RemoveEmptyGroups::class)]
#[CoversClass(RemoveEmptyTextElements::class)]
final class SvgOptimizerCommandTest extends TestCase
{
    private string $tempDir;

    /**
     * @throws \ReflectionException
     * @throws \RuntimeException
     * @throws \LogicException
     * @throws \ValueError
     */
    #[Test]
    public function runWithValidSvgFile(): void
    {
        $svgFile = $this->tempDir . '/test.svg';
        file_put_contents($svgFile, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $reflectionClass = new \ReflectionClass(Command::class);
        $constructor = $reflectionClass->getConstructor();

        if (!$constructor instanceof \ReflectionMethod) {
            self::fail('Constructor not found in SvgOptimizerCommand');
        }

        $command = $reflectionClass->newInstanceWithoutConstructor();

        $outputManager = new OutputManager(new MemoryStream(), false);

        $commandOption = new CommandOption(
            false,
            '',
            false,
            false,
        );

        $constructor->invoke($command, [$svgFile], $commandOption, $outputManager);

        $command->run();

        $propName = 'metaDataAggregator';
        $reflectionProperty = $reflectionClass->getProperty($propName);
        $metaDataAggregator = $reflectionProperty->getValue($command);

        self::assertInstanceOf(MetaDataAggregator::class, $metaDataAggregator);
    }

    /**
     * @throws \ReflectionException
     * @throws \RuntimeException
     * @throws \LogicException
     * @throws \ValueError
     */
    #[Test]
    public function runWithNoInputFiles(): void
    {
        $reflectionClass = new \ReflectionClass(Command::class);
        $constructor = $reflectionClass->getConstructor();

        if (!$constructor instanceof \ReflectionMethod) {
            self::fail('Constructor not found in SvgOptimizerCommand');
        }

        $outputManager = new OutputManager(new MemoryStream(), false);
        $command = $reflectionClass->newInstanceWithoutConstructor();

        $commandOption = new CommandOption(
            false,
            '',
            false,
            false,
        );

        $constructor->invoke($command, [], $commandOption, $outputManager);

        $command->run();
        $prop = $reflectionClass->getProperty('metaDataAggregator');
        $metaDataAggregator = $prop->getValue($command);
        self::assertInstanceOf(MetaDataAggregator::class, $metaDataAggregator);

        $originalSizeProp = new \ReflectionProperty($metaDataAggregator::class, 'totalOriginalSize');
        $totalOriginalSize = $originalSizeProp->getValue($metaDataAggregator);

        $optimizedSizeProp = new \ReflectionProperty($metaDataAggregator::class, 'totalOptimizedSize');
        $totalOptimizedSize = $optimizedSizeProp->getValue($metaDataAggregator);

        self::assertSame(0, $totalOriginalSize);
        self::assertSame(0, $totalOptimizedSize);
    }

    /**
     * @throws \LogicException
     * @throws \RuntimeException
     * @throws \ValueError
     */
    #[Test]
    public function runWithDryRunOption(): void
    {
        $svgFile = $this->tempDir . '/test.svg';
        $originalContent = '<svg xmlns="http://www.w3.org/2000/svg"></svg>';
        file_put_contents($svgFile, $originalContent);

        $command = new Command(
            [$svgFile],
            new CommandOption(
                true,
                '',
                false,
                false,
            ),
            new OutputManager(new MemoryStream(), false)
        );
        $command->run();

        $newContent = file_get_contents($svgFile);
        self::assertSame($originalContent, $newContent);
    }

    /**
     * @throws \LogicException
     * @throws \RuntimeException
     * @throws \ValueError
     */
    #[Test]
    public function runWithRiskyRuleInConfigButRiskyNotAllowedPrintsErrorAndDoesNotThrow(): void
    {
        $svgFile = $this->tempDir . '/risky.svg';
        file_put_contents($svgFile, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $memoryStream = new MemoryStream();

        $command = new Command(
            [$svgFile],
            new CommandOption(
                false,
                '{"scopeSvgStyles":true}',
                false,
                false,
            ),
            new OutputManager($memoryStream, false)
        );

        $command->run();

        self::assertStringContainsString(
            'Error: Risky rules are disabled. Use --allow-risky to enable it.',
            $memoryStream->getContent()
        );
    }

    /**
     * @throws \LogicException
     * @throws \RuntimeException
     * @throws \ValueError
     */
    #[Test]
    public function runWithInvalidJsonConfigPrintsJsonExceptionMessage(): void
    {
        $svgFile = $this->tempDir . '/invalid-json.svg';
        file_put_contents($svgFile, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $memoryStream = new MemoryStream();
        $configPath = '{invalid json';

        $command = new Command(
            [$svgFile],
            new CommandOption(
                false,
                $configPath,
                false,
                false,
            ),
            new OutputManager($memoryStream, false)
        );

        $command->run();

        self::assertStringContainsString(
            \sprintf('Error: Invalid JSON in configuration file "%s":', $configPath),
            $memoryStream->getContent()
        );
        self::assertSame(1, mb_substr_count($memoryStream->getContent(), 'Error: '));
        self::assertStringNotContainsString('Warning: ', $memoryStream->getContent());
    }

    /**
     * @throws \LogicException
     * @throws \RuntimeException
     * @throws \ValueError
     */
    #[Test]
    public function runWithUnknownConfigKeysWarnsOnceAndStillOptimizes(): void
    {
        $firstFile = $this->tempDir . '/first.svg';
        $secondFile = $this->tempDir . '/second.svg';
        file_put_contents($firstFile, '<svg xmlns="http://www.w3.org/2000/svg"><!-- comment --></svg>');
        file_put_contents($secondFile, '<svg xmlns="http://www.w3.org/2000/svg"><!-- comment --></svg>');

        $memoryStream = new MemoryStream();

        $command = new Command(
            [$firstFile, $secondFile],
            new CommandOption(
                false,
                '{"removeComments":true,"removeComents":true,"0":false}',
                false,
                false,
            ),
            new OutputManager($memoryStream, false)
        );

        $command->run();

        $output = $memoryStream->getContent();

        self::assertStringStartsWith(
            'Warning: Unknown rule "removeComents" in the configuration is ignored.' . \PHP_EOL
            . 'Warning: Unknown rule "0" in the configuration is ignored.' . \PHP_EOL,
            $output
        );
        self::assertSame(2, mb_substr_count($output, 'Warning: '));
        self::assertStringNotContainsString('Error: ', $output);
        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"/>', file_get_contents($firstFile));
        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"/>', file_get_contents($secondFile));
    }

    /**
     * @throws \LogicException
     * @throws \RuntimeException
     * @throws \ValueError
     */
    #[Test]
    public function runPrintsTheSummaryOnlyWhenFilesWereOptimized(): void
    {
        $svgFile = $this->tempDir . '/summary.svg';
        file_put_contents($svgFile, '<svg xmlns="http://www.w3.org/2000/svg"><!-- c --></svg>');

        $memoryStream = new MemoryStream();
        (new Command([$svgFile], new CommandOption(false, '{"removeComments":true}', false, false), new OutputManager($memoryStream, false)))->run();

        self::assertStringContainsString(\PHP_EOL . 'Summary:' . \PHP_EOL . '  Files optimized:      1' . \PHP_EOL, $memoryStream->getContent());
        self::assertStringContainsString('  Original total size:  56 B' . \PHP_EOL, $memoryStream->getContent());
        self::assertStringContainsString('  Optimized total size: 41 B' . \PHP_EOL, $memoryStream->getContent());
        self::assertStringContainsString('  Space saved:          15 B (26.79%)' . \PHP_EOL, $memoryStream->getContent());

        $emptyStream = new MemoryStream();
        (new Command([$this->tempDir . '/missing.svg'], new CommandOption(false, '', false, false), new OutputManager($emptyStream, false)))->run();

        self::assertStringNotContainsString('Summary:', $emptyStream->getContent());
    }

    /**
     * @throws \LogicException
     * @throws \RuntimeException
     * @throws \ValueError
     */
    #[Test]
    public function runWithOnlyKnownConfigKeysDoesNotWarn(): void
    {
        $svgFile = $this->tempDir . '/known.svg';
        file_put_contents($svgFile, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $memoryStream = new MemoryStream();

        $command = new Command(
            [$svgFile],
            new CommandOption(
                false,
                '{"removeComments":true,"removeMetadata":false}',
                false,
                false,
            ),
            new OutputManager($memoryStream, false)
        );

        $command->run();

        self::assertStringNotContainsString('Warning: ', $memoryStream->getContent());
        self::assertStringNotContainsString('Error: ', $memoryStream->getContent());
    }

    /**
     * @throws \LogicException
     * @throws \RuntimeException
     * @throws \ValueError
     */
    #[Test]
    public function runWithNonArrayJsonConfigPrintsInvalidArgumentExceptionMessage(): void
    {
        $svgFile = $this->tempDir . '/non-array-json.svg';
        file_put_contents($svgFile, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $memoryStream = new MemoryStream();

        $command = new Command(
            [$svgFile],
            new CommandOption(
                false,
                '42',
                false,
                false,
            ),
            new OutputManager($memoryStream, false)
        );

        $command->run();

        self::assertStringContainsString(
            'Error: Configuration must be a valid file path or a JSON string.',
            $memoryStream->getContent()
        );
    }

    /**
     * @throws \LogicException
     * @throws \RuntimeException
     * @throws \ValueError
     */
    #[Test]
    public function runWithUnwritableFilePrintsRuntimeExceptionMessage(): void
    {
        $svgFile = $this->tempDir . '/readonly.svg';
        file_put_contents($svgFile, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        chmod($svgFile, 0o444);

        try {
            $memoryStream = new MemoryStream();

            $command = new Command(
                [$svgFile],
                new CommandOption(
                    false,
                    '',
                    false,
                    false,
                ),
                new OutputManager($memoryStream, false)
            );

            set_error_handler(static fn (): bool => true, \E_WARNING);

            try {
                $command->run();
            } finally {
                restore_error_handler();
            }

            self::assertStringContainsString(
                \sprintf('Error: Failed processing "%s": Failed to write optimized content to the output file: %s', $svgFile, $svgFile),
                $memoryStream->getContent()
            );
        } finally {
            chmod($svgFile, 0o666);
        }
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/svgopt_test_' . uniqid();
        mkdir($this->tempDir);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->deleteDir($this->tempDir);
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $item) {
            if ('.' === $item) {
                continue;
            }

            if ('..' === $item) {
                continue;
            }

            $path = $dir . \DIRECTORY_SEPARATOR . $item;
            is_dir($path)
                ? $this->deleteDir($path)
                : unlink($path);
        }

        rmdir($dir);
    }
}
