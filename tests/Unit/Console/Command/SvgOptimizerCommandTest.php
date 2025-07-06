<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Command;

use MathiasReker\PhpSvgOptimizer\Console\Command\SvgOptimizerCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SvgOptimizerCommand::class)]
final class SvgOptimizerCommandTest extends TestCase
{
    private string $tempDir;

    /**
     * @throws \ReflectionException
     * @throws \RuntimeException
     */
    public function testRunWithValidSvgFile(): void
    {
        $svgFile = $this->tempDir . '/test.svg';
        file_put_contents($svgFile, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $reflection = new \ReflectionClass(SvgOptimizerCommand::class);
        $constructor = $reflection->getConstructor();

        if (!$constructor instanceof \ReflectionMethod) {
            self::fail('Constructor not found in SvgOptimizerCommand');
        }

        $constructor->setAccessible(true);

        // Create instance without invoking constructor, then call constructor manually
        $command = $reflection->newInstanceWithoutConstructor();
        $constructor->invoke($command, [$svgFile], '');

        // Set dryRun and quiet to true to avoid actual file writes and output
        foreach (['dryRun', 'quiet'] as $propName) {
            $prop = $reflection->getProperty($propName);
            $prop->setAccessible(true);
            $prop->setValue($command, true);
        }

        $command->run();

        // Check internals
        foreach (['totalOriginalSize', 'totalOptimizedSize', 'optimizedFiles'] as $propName) {
            $prop = $reflection->getProperty($propName);
            $prop->setAccessible(true);
            self::assertIsInt($prop->getValue($command));
        }
    }

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/svgopt_test_' . uniqid();
        mkdir($this->tempDir);
    }

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
            if ('.' === $item || '..' === $item) {
                continue;
            }
            $path = $dir . \DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->deleteDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
