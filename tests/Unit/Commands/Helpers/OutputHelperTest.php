<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Commands\Helpers;

use MathiasReker\PhpSvgOptimizer\Commands\Helpers\OutputHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(OutputHelper::class)]
final class OutputHelperTest extends TestCase
{
    public function testPrintError(): void
    {
        ob_start();
        OutputHelper::printError('Something went wrong');
        $output = ob_get_clean();

        self::assertNotFalse($output);
        self::assertSame('Error: Something went wrong' . \PHP_EOL, $output);
    }

    public function testPrintVersion(): void
    {
        ob_start();
        OutputHelper::printVersion('1.2.3');
        $output = ob_get_clean();

        self::assertNotFalse($output);
        self::assertSame('PHP SVG Optimizer v1.2.3' . \PHP_EOL, $output);
    }

    public function testPrintOptimizationResult(): void
    {
        ob_start();
        OutputHelper::printOptimizationResult('file.svg', 42.567_89);
        $output = ob_get_clean();

        self::assertNotFalse($output);
        self::assertSame('file.svg (42.57%)' . \PHP_EOL, $output);
    }

    public function testPrintSummary(): void
    {
        ob_start();
        OutputHelper::printSummary(3, 1_000, 700);
        $output = ob_get_clean();

        self::assertNotFalse($output);
        self::assertStringContainsString('Total files processed: 3', $output);
        self::assertStringContainsString('Total size reduction: 300 bytes', $output);
        self::assertStringContainsString('Total reduction percentage: 30.00%', $output);
    }

    public function testPrintSummaryWithZeroOriginalSize(): void
    {
        ob_start();
        OutputHelper::printSummary(1, 0, 0);
        $output = ob_get_clean();

        self::assertNotFalse($output);
        self::assertStringContainsString('Total size reduction: 0 bytes', $output);
        self::assertStringContainsString('Total reduction percentage: 0.00%', $output);
    }

    public function testPrintHelp(): void
    {
        ob_start();
        OutputHelper::printHelp();
        $output = ob_get_clean();

        self::assertNotFalse($output);
        self::assertStringContainsString('Usage:', $output);
        self::assertStringContainsString('Options:', $output);
        self::assertStringContainsString('Commands:', $output);
        self::assertStringContainsString('Examples:', $output);
    }
}
