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
use MathiasReker\PhpSvgOptimizer\Console\Input\ArgumentParser;
use MathiasReker\PhpSvgOptimizer\Console\Input\OptionIntent;
use MathiasReker\PhpSvgOptimizer\Console\Output\Manager\OutputManager;
use MathiasReker\PhpSvgOptimizer\Console\Output\Stream\SilentStream;
use MathiasReker\PhpSvgOptimizer\Console\Output\Stream\StdoutStream;
use MathiasReker\PhpSvgOptimizer\Type\Application;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
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
final class CommandDispatcherTest extends TestCase
{
    private string $binPath;

    private string $tempDir;

    /**
     * @throws \RuntimeException
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
     */
    #[Test]
    public function runWithUnknownOptionPrintsErrorAndExitsOne(): void
    {
        $result = $this->runCli(['--not-a-real-option']);

        self::assertSame(1, $result['exitCode']);
        self::assertStringContainsString(
            'Error: Unknown option: "--not-a-real-option". Run with --help to see valid options.',
            $result['stdout']
        );
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function runWithQuietAndUnknownOptionStillPrintsErrorAndExitsOne(): void
    {
        $result = $this->runCli(['--quiet', '--not-a-real-option']);

        self::assertSame(1, $result['exitCode']);
        self::assertStringContainsString(
            'Error: Unknown option: "--not-a-real-option". Run with --help to see valid options.',
            $result['stdout']
        );
    }

    /**
     * @throws \RuntimeException
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
        $this->binPath = \dirname(__DIR__, 4) . '/bin/svg-optimizer';
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
     */
    private function runCli(array $args): array
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
}
