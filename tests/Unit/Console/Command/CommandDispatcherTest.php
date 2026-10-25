<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Command;

use MathiasReker\PhpSvgOptimizer\Console\Command\CommandDispatcher;
use MathiasReker\PhpSvgOptimizer\Console\Command\CommandFactory;
use MathiasReker\PhpSvgOptimizer\Console\Filesystem\Finder;
use MathiasReker\PhpSvgOptimizer\Console\Help\ArgumentData;
use MathiasReker\PhpSvgOptimizer\Console\Input\ArgumentParser;
use MathiasReker\PhpSvgOptimizer\Console\Input\ConfigLoader;
use MathiasReker\PhpSvgOptimizer\Console\Input\FileCollector;
use MathiasReker\PhpSvgOptimizer\Console\Input\OptionIntent;
use MathiasReker\PhpSvgOptimizer\Console\Input\RuleSetParser;
use MathiasReker\PhpSvgOptimizer\Console\Output\Formatter\ByteFormatter;
use MathiasReker\PhpSvgOptimizer\Console\Output\Manager\OutputManager;
use MathiasReker\PhpSvgOptimizer\Console\Output\MetaDataAggregator;
use MathiasReker\PhpSvgOptimizer\Console\Output\Stream\MemoryStream;
use MathiasReker\PhpSvgOptimizer\Console\Output\Stream\SilentStream;
use MathiasReker\PhpSvgOptimizer\Console\Output\Stream\StderrStream;
use MathiasReker\PhpSvgOptimizer\Console\Output\Stream\StdoutStream;
use MathiasReker\PhpSvgOptimizer\Console\Processor\SvgFileProcessor;
use MathiasReker\PhpSvgOptimizer\Console\Type\Application;
use MathiasReker\PhpSvgOptimizer\Console\Type\Command;
use MathiasReker\PhpSvgOptimizer\Console\Type\Option;
use MathiasReker\PhpSvgOptimizer\Console\ValueObject\CliOption;
use MathiasReker\PhpSvgOptimizer\Console\ValueObject\CommandHelp;
use MathiasReker\PhpSvgOptimizer\Console\ValueObject\CommandOption;
use MathiasReker\PhpSvgOptimizer\Console\ValueObject\ExampleCommand;
use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Service\Facade\SvgOptimizerFacade;
use MathiasReker\PhpSvgOptimizer\Service\Optimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Processor\Trait\TextWhitespaceTrait;
use MathiasReker\PhpSvgOptimizer\Service\Provider\AbstractProvider;
use MathiasReker\PhpSvgOptimizer\Service\Provider\FileProvider;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertColorsToHex;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertCssClassesToAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertEllipseToCircle;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertInlineStylesToAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertShapesToPath;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveComments;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDataAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDefaultAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDuplicateElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyContainers;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyTextElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveHiddenElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveInkscapeFootprints;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveInvisibleCharacters;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveMetadata;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveNonInheritableGroupAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveStyleTypeAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedDefinitions;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedIds;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedNamespaces;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedTextAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUselessDefs;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUselessStrokeAndFill;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveWidthHeightAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveXmlSpaceDefault;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveZeroSizeShapes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\AttributeNodeTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ClassAttributeTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\CssDeclarationTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\CssStyleElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\DefinitionElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\DocumentScriptTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\EmptyElementRemovalTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\EntityReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\FragmentTargetTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\IdReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\InheritanceBoundaryTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\NumberFormatterTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\NumberListTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\PathDataSerializerTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\PresentationAttributeCleanupTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\RemoveElementsByTagNameTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\RenameElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\SvgElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\TagMatcherTrait;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use MathiasReker\PhpSvgOptimizer\Type\Rule;
use MathiasReker\PhpSvgOptimizer\Type\RuleSet;
use MathiasReker\PhpSvgOptimizer\ValueObject\Metrics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CommandDispatcher::class)]
#[CoversClass(CommandFactory::class)]
#[CoversClass(ArgumentParser::class)]
#[CoversClass(OptionIntent::class)]
#[CoversClass(OutputManager::class)]
#[CoversClass(StdoutStream::class)]
#[CoversClass(SilentStream::class)]
#[CoversClass(Application::class)]
#[CoversClass(RuleSetParser::class)]
#[UsesClass(\MathiasReker\PhpSvgOptimizer\Console\Command\Command::class)]
#[UsesClass(AbstractProvider::class)]
#[UsesClass(ArgumentData::class)]
#[UsesClass(ByteFormatter::class)]
#[UsesClass(CliOption::class)]
#[UsesClass(Command::class)]
#[UsesClass(CommandHelp::class)]
#[UsesClass(CommandOption::class)]
#[UsesClass(ConfigLoader::class)]
#[UsesClass(ConvertColorsToHex::class)]
#[UsesClass(ConvertCssClassesToAttributes::class)]
#[UsesClass(ConvertEllipseToCircle::class)]
#[UsesClass(ConvertInlineStylesToAttributes::class)]
#[UsesClass(ConvertShapesToPath::class)]
#[UsesClass(DomDocumentWrapper::class)]
#[UsesClass(ExampleCommand::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(FileCollector::class)]
#[UsesClass(FileProvider::class)]
#[UsesClass(Finder::class)]
#[UsesClass(MemoryStream::class)]
#[UsesClass(MetaDataAggregator::class)]
#[UsesClass(Metrics::class)]
#[UsesClass(Option::class)]
#[UsesClass(RemoveComments::class)]
#[UsesClass(RemoveDataAttributes::class)]
#[UsesClass(RemoveDefaultAttributes::class)]
#[UsesClass(RemoveDuplicateElements::class)]
#[UsesClass(RemoveEmptyAttributes::class)]
#[UsesClass(RemoveEmptyContainers::class)]
#[UsesClass(RemoveEmptyTextElements::class)]
#[UsesClass(RemoveHiddenElements::class)]
#[UsesClass(RemoveInkscapeFootprints::class)]
#[UsesClass(RemoveInvisibleCharacters::class)]
#[UsesClass(RemoveMetadata::class)]
#[UsesClass(RemoveNonInheritableGroupAttributes::class)]
#[UsesClass(RemoveStyleTypeAttributes::class)]
#[UsesClass(RemoveUnusedDefinitions::class)]
#[UsesClass(RemoveUnusedIds::class)]
#[UsesClass(RemoveUnusedNamespaces::class)]
#[UsesClass(RemoveUnusedTextAttributes::class)]
#[UsesClass(RemoveUselessDefs::class)]
#[UsesClass(RemoveUselessStrokeAndFill::class)]
#[UsesClass(RemoveWidthHeightAttributes::class)]
#[UsesClass(RemoveXmlSpaceDefault::class)]
#[UsesClass(RemoveZeroSizeShapes::class)]
#[UsesClass(Rule::class)]
#[UsesClass(RuleSet::class)]
#[UsesClass(StderrStream::class)]
#[UsesClass(StringProvider::class)]
#[UsesClass(SvgAttribute::class)]
#[UsesClass(SvgFileProcessor::class)]
#[UsesClass(SvgOptimizer::class)]
#[UsesClass(SvgOptimizerFacade::class)]
#[UsesClass(SvgTag::class)]
#[UsesClass(SvgValidator::class)]
#[UsesTrait(AsciiCaseTrait::class)]
#[UsesTrait(AttributeNodeTrait::class)]
#[UsesTrait(ClassAttributeTrait::class)]
#[UsesTrait(CssDeclarationTrait::class)]
#[UsesTrait(CssStyleElementTrait::class)]
#[UsesTrait(DefinitionElementTrait::class)]
#[UsesTrait(DocumentScriptTrait::class)]
#[UsesTrait(ElementTrait::class)]
#[UsesTrait(EmptyElementRemovalTrait::class)]
#[UsesTrait(EntityReferenceTrait::class)]
#[UsesTrait(FragmentTargetTrait::class)]
#[UsesTrait(IdReferenceTrait::class)]
#[UsesTrait(InheritanceBoundaryTrait::class)]
#[UsesTrait(NumberFormatterTrait::class)]
#[UsesTrait(NumberListTrait::class)]
#[UsesTrait(PathDataSerializerTrait::class)]
#[UsesTrait(PresentationAttributeCleanupTrait::class)]
#[UsesTrait(RemoveElementsByTagNameTrait::class)]
#[UsesTrait(RenameElementTrait::class)]
#[UsesTrait(SvgElementTrait::class)]
#[UsesTrait(TagMatcherTrait::class)]
#[UsesTrait(TextWhitespaceTrait::class)]
final class CommandDispatcherTest extends TestCase
{
    private string $binPath;

    private string $tempDir;

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function theBinaryExitsWithTheExitCodeAndSeparatesErrors(): void
    {
        $svgFile = $this->tempDir . '/bad.svg';
        file_put_contents($svgFile, 'not svg');

        $failure = $this->runBinary(['--dry-run', 'process', $svgFile]);
        $success = $this->runBinary(['--version']);

        self::assertSame(1, $failure['exitCode']);
        self::assertSame('', $failure['stdout']);
        self::assertStringContainsString('Error:', $failure['stderr']);
        self::assertSame(0, $success['exitCode']);
        self::assertStringContainsString(Application::Version->value, $success['stdout']);
    }

    /**
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[Test]
    public function runWithNoArgumentsPrintsHelpAndExitsZero(): void
    {
        $result = $this->runCli([]);

        self::assertSame(0, $result['exitCode']);
        self::assertStringContainsString('PHP SVG Optimizer', $result['stdout']);
        self::assertStringContainsString('Usage:', $result['stdout']);
    }

    /**
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[Test]
    public function runWithHelpOptionPrintsHelpAndExitsZero(): void
    {
        $result = $this->runCli(['--help']);

        self::assertSame(0, $result['exitCode']);
        self::assertStringContainsString('Usage:', $result['stdout']);
        self::assertStringContainsString('Options:', $result['stdout']);
        self::assertStringContainsString('Examples:', $result['stdout']);
    }

    /**
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[Test]
    public function runWithVersionOptionPrintsVersionAndExitsZero(): void
    {
        $result = $this->runCli(['--version']);

        self::assertSame(0, $result['exitCode']);
        self::assertStringContainsString(
            \sprintf(
                '%s v%s by %s and contributors',
                Application::Name->value,
                Application::Version->value,
                Application::Author->value,
            ),
            $result['stdout']
        );
        self::assertStringContainsString('PHP runtime: ' . \PHP_VERSION, $result['stdout']);
    }

    /**
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[Test]
    public function runWithUnknownOptionPrintsErrorAndExitsOne(): void
    {
        $result = $this->runCli(['--not-a-real-option']);

        self::assertSame(1, $result['exitCode']);
        self::assertSame(
            'Error: Unknown option: "--not-a-real-option". Run with --help to see valid options.' . \PHP_EOL,
            $result['stderr']
        );
        self::assertSame('', $result['stdout']);
    }

    /**
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[Test]
    public function runWithQuietAndUnknownOptionStillPrintsErrorAndExitsOne(): void
    {
        $result = $this->runCli(['--quiet', '--not-a-real-option']);

        self::assertSame(1, $result['exitCode']);
        self::assertStringContainsString(
            'Error: Unknown option: "--not-a-real-option". Run with --help to see valid options.',
            $result['stderr']
        );
    }

    /**
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[Test]
    public function runWithQuietStillPrintsProcessingErrorsToStderr(): void
    {
        $validFile = $this->tempDir . '/valid.svg';
        file_put_contents($validFile, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        $invalidFile = $this->tempDir . '/invalid.svg';
        file_put_contents($invalidFile, 'not an svg');

        $result = $this->runCli(['--quiet', 'process', $this->tempDir]);

        self::assertSame(1, $result['exitCode']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString(
            \sprintf('Error: Failed processing "%s": The file does not appear to be a valid SVG file.', (string) realpath($invalidFile)),
            $result['stderr']
        );
    }

    /**
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[Test]
    public function runWithValidSvgFileOptimizesAndExitsZero(): void
    {
        $svgFile = $this->tempDir . '/valid.svg';
        file_put_contents($svgFile, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $result = $this->runCli(['process', $svgFile]);

        self::assertSame(0, $result['exitCode']);

        self::assertStringContainsString((string) realpath($svgFile), $result['stdout']);
        self::assertStringContainsString('Summary:', $result['stdout']);
        self::assertStringContainsString('Files optimized:      1', $result['stdout']);
    }

    /**
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[Test]
    public function runWithUnknownCommandPrintsErrorAndExitsOne(): void
    {
        $svgFile = $this->tempDir . '/valid.svg';
        file_put_contents($svgFile, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $result = $this->runCli(['optimize', $svgFile]);

        self::assertSame(1, $result['exitCode']);
        self::assertStringContainsString('Error: Unknown command "optimize".', $result['stderr']);
    }

    /**
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[Test]
    public function runWithAnEmptyConfigValuePrintsErrorAndExitsOne(): void
    {
        $result = $this->runCli(['--config=', '--with-all-rules', 'process', $this->tempDir]);

        self::assertSame(1, $result['exitCode']);
        self::assertStringContainsString('Option "--config" requires a value.', $result['stderr']);
    }

    /**
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[Test]
    public function runWithMissingCommandPrintsErrorAndExitsOne(): void
    {
        $result = $this->runCli(['--dry-run']);

        self::assertSame(1, $result['exitCode']);
        self::assertStringContainsString('Error: No command given.', $result['stderr']);
    }

    /**
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[Test]
    public function runAcceptsOptionsAfterThePathsAndASeparateConfigValue(): void
    {
        $svgFile = $this->tempDir . '/options-after-paths.svg';
        $originalContent = '<svg xmlns="http://www.w3.org/2000/svg"><!-- comment --></svg>';
        file_put_contents($svgFile, $originalContent);

        $result = $this->runCli(['process', $svgFile, '--dry-run', '-c', '{"removeComments":true}']);

        self::assertSame(0, $result['exitCode']);
        self::assertSame('', $result['stderr']);
        self::assertStringContainsString((string) realpath($svgFile), $result['stdout']);
        self::assertSame($originalContent, file_get_contents($svgFile));

        $result = $this->runCli(['--config', '{"removeComments":true}', 'process', $svgFile]);

        self::assertSame(0, $result['exitCode']);
        self::assertSame('', $result['stderr']);
        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"/>', file_get_contents($svgFile));
    }

    /**
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[Test]
    public function runWithInvalidSvgFileExitsOne(): void
    {
        $svgFile = $this->tempDir . '/invalid.svg';
        file_put_contents($svgFile, 'not an svg');

        $result = $this->runCli(['process', $svgFile]);

        self::assertSame(1, $result['exitCode']);
        self::assertStringContainsString('Error: Failed processing', $result['stderr']);
    }

    /**
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[Test]
    public function runWithRiskyRuleNotAllowedExitsOne(): void
    {
        $svgFile = $this->tempDir . '/valid.svg';
        file_put_contents($svgFile, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $result = $this->runCli(['--config={"removeDataAttributes":true}', 'process', $svgFile]);

        self::assertSame(1, $result['exitCode']);
        self::assertStringContainsString('Error: Risky rules are disabled.', $result['stderr']);
    }

    /**
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[Test]
    public function runWithoutAnsiOptionDoesNotColorOutput(): void
    {
        $svgFile = $this->tempDir . '/no-ansi.svg';
        file_put_contents($svgFile, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $result = $this->runCli(['process', $svgFile]);

        self::assertSame(0, $result['exitCode']);
        self::assertStringNotContainsString("\033[", $result['stdout']);
    }

    /**
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[Test]
    public function runWithAnsiOptionForcesColoredOutputEvenWithoutATty(): void
    {
        $svgFile = $this->tempDir . '/ansi.svg';
        file_put_contents($svgFile, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $result = $this->runCli(['--ansi', 'process', $svgFile]);

        self::assertSame(0, $result['exitCode']);
        self::assertStringContainsString("\033[", $result['stdout']);
    }

    /**
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[Test]
    public function runWithNoAnsiOptionNeverColorsOutput(): void
    {
        $svgFile = $this->tempDir . '/explicit-no-ansi.svg';
        file_put_contents($svgFile, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $result = $this->runCli(['--no-ansi', 'process', $svgFile]);

        self::assertSame(0, $result['exitCode']);
        self::assertStringNotContainsString("\033[", $result['stdout']);
    }

    /**
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[Test]
    public function runWithBothAnsiAndNoAnsiOptionsNoAnsiWins(): void
    {
        $svgFile = $this->tempDir . '/both-ansi-flags.svg';
        file_put_contents($svgFile, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $result = $this->runCli(['--ansi', '--no-ansi', 'process', $svgFile]);

        self::assertSame(0, $result['exitCode']);
        self::assertStringNotContainsString("\033[", $result['stdout']);
    }

    /**
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[Test]
    public function runWithDryRunOptionDoesNotModifyFile(): void
    {
        $svgFile = $this->tempDir . '/dryrun.svg';
        $originalContent = '<svg xmlns="http://www.w3.org/2000/svg"></svg>';
        file_put_contents($svgFile, $originalContent);

        $result = $this->runCli(['--dry-run', 'process', $svgFile]);

        self::assertSame(0, $result['exitCode']);
        self::assertStringContainsString((string) realpath($svgFile), $result['stdout']);
        self::assertSame($originalContent, file_get_contents($svgFile));
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->binPath = __DIR__ . '/../../../../bin/svg-optimizer';
        self::assertTrue(is_file($this->binPath), \sprintf('Could not resolve bin/svg-optimizer at "%s".', $this->binPath));

        $this->tempDir = sys_get_temp_dir() . '/svgopt_dispatcher_test_' . uniqid();
        mkdir($this->tempDir);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->deleteDir($this->tempDir);
    }

    /**
     * @param list<string> $args
     *
     * @return array{stdout: string, stderr: string, exitCode: int}
     *
     * @throws \RuntimeException
     * @throws \LogicException
     */
    private function runCli(array $args): array
    {
        $outputStream = new MemoryStream();
        $errorStream = new MemoryStream();

        $exitCode = CommandDispatcher::withStreams([$this->binPath, ...$args], $outputStream, $errorStream)->run();

        return [
            'stdout' => $outputStream->getContent(),
            'stderr' => $errorStream->getContent(),
            'exitCode' => $exitCode,
        ];
    }

    /**
     * @param list<string> $args
     *
     * @return array{stdout: string, stderr: string, exitCode: int}
     */
    private function runBinary(array $args): array
    {
        $command = [\PHP_BINARY, $this->binPath, ...$args];

        $process = proc_open(
            $command,
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes
        );

        if (!\is_resource($process)) {
            self::fail('Failed to start the CLI process.');
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [
            'stdout' => false !== $stdout ? $stdout : '',
            'stderr' => false !== $stderr ? $stderr : '',
            'exitCode' => $exitCode,
        ];
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

    /**
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[Test]
    public function fromArgsCreatesADispatcherForTheStandardStreams(): void
    {
        self::assertSame(0, CommandDispatcher::fromArgs([$this->binPath, '--quiet', '--version'])->run());
    }

    /**
     * @param list<string>  $options  The options that select the rule sets
     * @param list<RuleSet> $ruleSets The rule sets the options select
     *
     * @throws \RuntimeException
     * @throws \LogicException
     * @throws RiskyRulesNotAllowedException
     */
    #[DataProvider('provideRunAppliesTheGivenRuleSetsCases')]
    #[Test]
    public function runAppliesTheGivenRuleSets(array $options, array $ruleSets): void
    {
        $content = '<svg xmlns="http://www.w3.org/2000/svg" data-x="1"><!-- comment --><rect width="10" height="10" fill="red"/></svg>';
        $svgFile = $this->tempDir . '/sets.svg';
        file_put_contents($svgFile, $content);

        $result = $this->runCli([...$options, 'process', $svgFile]);

        $expected = SvgOptimizerFacade::fromString($content)->withRuleSets(...$ruleSets)->optimize()->getContent();

        self::assertSame(0, $result['exitCode']);
        self::assertSame('', $result['stderr']);
        self::assertNotSame($content, $expected);
        self::assertSame($expected, file_get_contents($svgFile));
    }

    /**
     * @return iterable<string, array{list<string>, list<RuleSet>}>
     */
    public static function provideRunAppliesTheGivenRuleSetsCases(): iterable
    {
        yield 'one set' => [['--set=cleanup'], [RuleSet::Cleanup]];

        yield 'comma-separated sets' => [['--set=cleanup,conversion'], [RuleSet::Cleanup, RuleSet::Conversion]];

        yield 'repeated shorthand option' => [['-s', 'conversion', '-s', 'cleanup'], [RuleSet::Cleanup, RuleSet::Conversion]];

        yield 'configuration' => [['--config={"sets":["conversion"]}'], [RuleSet::Conversion]];

        yield 'option and configuration' => [['--set=conversion', '-c', '{"sets":["cleanup"]}'], [RuleSet::Cleanup, RuleSet::Conversion]];
    }

    /**
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[Test]
    public function runAppliesTheRiskyRulesOfARuleSetOnlyWithAllowRisky(): void
    {
        $svgFile = $this->tempDir . '/risky-set.svg';
        file_put_contents($svgFile, '<svg xmlns="http://www.w3.org/2000/svg" data-x="1"><!-- comment --></svg>');

        $result = $this->runCli(['--set=cleanup', 'process', $svgFile]);

        self::assertSame(0, $result['exitCode']);
        self::assertSame('', $result['stderr']);
        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg" data-x="1"/>', file_get_contents($svgFile));

        $result = $this->runCli(['--set=cleanup', '--allow-risky', 'process', $svgFile]);

        self::assertSame(0, $result['exitCode']);
        self::assertSame('', $result['stderr']);
        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"/>', file_get_contents($svgFile));
    }

    /**
     * @param list<string> $options The options that select the rule sets
     *
     * @throws \RuntimeException
     * @throws \LogicException
     */
    #[DataProvider('provideRunWithAnInvalidRuleSetPrintsErrorAndExitsOneCases')]
    #[Test]
    public function runWithAnInvalidRuleSetPrintsErrorAndExitsOne(array $options, string $error): void
    {
        $content = '<svg xmlns="http://www.w3.org/2000/svg"><!-- comment --></svg>';
        $svgFile = $this->tempDir . '/invalid-set.svg';
        file_put_contents($svgFile, $content);

        $result = $this->runCli([...$options, 'process', $svgFile]);

        self::assertSame(1, $result['exitCode']);
        self::assertSame('Error: ' . $error . \PHP_EOL, $result['stderr']);
        self::assertSame('', $result['stdout']);
        self::assertSame($content, file_get_contents($svgFile));
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function provideRunWithAnInvalidRuleSetPrintsErrorAndExitsOneCases(): iterable
    {
        $validNames = 'Valid rule sets are: security, standards, cleanup, conversion, structure, minification.';

        yield 'unknown set in the option' => [['--set=cleanup,lossless'], 'Unknown rule set "lossless". ' . $validNames];

        yield 'option without value' => [['--set='], 'Option "--set" requires a value.'];

        yield 'unknown set in the configuration' => [['--config={"sets":["risky"]}'], 'Unknown rule set "risky". ' . $validNames];

        yield 'sets in the configuration that are not a list' => [['--config={"sets":"cleanup"}'], 'Configuration value for "sets" must be a list of rule set names.'];
    }
}
