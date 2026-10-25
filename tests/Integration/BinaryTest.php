<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Integration;

use MathiasReker\PhpSvgOptimizer\Tests\Double\ProcessTrait;
use MathiasReker\PhpSvgOptimizer\Tests\Double\TemporaryDirectoryTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversNothing]
final class BinaryTest extends TestCase
{
    use ProcessTrait;
    use TemporaryDirectoryTrait;

    private const string SVG = '<svg xmlns="http://www.w3.org/2000/svg"><!-- comment --><path d="M 0 0 L 10 10"/></svg>';

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function printsTheVersionAndExitsWithZero(): void
    {
        $result = $this->runBinary(['--version']);

        self::assertSame(0, $result['exitCode']);
        self::assertMatchesRegularExpression('/^PHP SVG Optimizer \S+ by Mathias Reker and contributors\R/', $result['stdout']);
        self::assertSame('', $result['stderr']);
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function printsTheHelpWithoutArguments(): void
    {
        $result = $this->runBinary([]);

        self::assertSame(0, $result['exitCode']);
        self::assertStringStartsWith('svg-optimizer', $result['stdout']);
        self::assertStringContainsString('Usage:', $result['stdout']);
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function optimizesFilesAndSeparatesTheStreams(): void
    {
        $directory = $this->createTemporaryDirectory();
        file_put_contents($directory . '/a.svg', self::SVG);
        file_put_contents($directory . '/bad.svg', 'not an svg');

        $result = $this->runBinary(['.'], $directory);

        self::assertSame(1, $result['exitCode']);
        self::assertStringStartsWith('a.svg (', $result['stdout']);
        self::assertStringNotContainsString('Error:', $result['stdout']);
        self::assertStringStartsWith('Error: "bad.svg" is not a valid SVG file: ', $result['stderr']);
        self::assertStringNotContainsString('comment', (string) file_get_contents($directory . '/a.svg'));
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function checkExitsWithThreeThenWithZeroOnceTheFilesAreOptimized(): void
    {
        $directory = $this->createTemporaryDirectory();
        file_put_contents($directory . '/a.svg', self::SVG);

        $check = $this->runBinary(['--check', 'a.svg'], $directory);
        $optimize = $this->runBinary(['a.svg'], $directory);
        $again = $this->runBinary(['--check', 'a.svg'], $directory);

        self::assertSame(3, $check['exitCode']);
        self::assertSame(0, $optimize['exitCode']);
        self::assertSame(0, $again['exitCode']);
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function exitsWithTwoOnAnInvalidUsage(): void
    {
        $result = $this->runBinary(['--nope']);

        self::assertSame(2, $result['exitCode']);
        self::assertSame('', $result['stdout']);
        self::assertStringStartsWith('Error: Unknown option "--nope".', $result['stderr']);
    }

    /**
     * @param list<string> $arguments
     *
     * @return array{exitCode: int, stdout: string, stderr: string}
     *
     * @throws \RuntimeException
     */
    private function runBinary(array $arguments, string $workingDirectory = __DIR__): array
    {
        return $this->runProcess([\PHP_BINARY, (string) realpath(__DIR__ . '/../../bin/svg-optimizer'), ...$arguments], $workingDirectory);
    }
}
