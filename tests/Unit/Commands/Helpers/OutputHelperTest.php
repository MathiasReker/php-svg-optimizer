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
use MathiasReker\PhpSvgOptimizer\Enums\Command;
use MathiasReker\PhpSvgOptimizer\Enums\Option;
use MathiasReker\PhpSvgOptimizer\Services\Data\ArgumentData;
use MathiasReker\PhpSvgOptimizer\ValueObjects\ArgumentOptionValueObject;
use MathiasReker\PhpSvgOptimizer\ValueObjects\CommandOptionValueObject;
use MathiasReker\PhpSvgOptimizer\ValueObjects\ExampleCommandValueObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(OutputHelper::class)]
#[CoversClass(Command::class)]
#[CoversClass(ArgumentData::class)]
#[CoversClass(ArgumentOptionValueObject::class)]
#[CoversClass(CommandOptionValueObject::class)]
#[CoversClass(ExampleCommandValueObject::class)]
#[CoversClass(Option::class)]
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
        OutputHelper::printVersion('PHP SVG Optimizer', '1.2.3', 'Mathias Reker');
        $output = ob_get_clean();

        self::assertNotFalse($output);
        self::assertSame(
            'PHP SVG Optimizer v1.2.3 by Mathias Reker and contributors' . \PHP_EOL .
            'PHP runtime: ' . \PHP_VERSION . \PHP_EOL,
            $output
        );
    }

    public function testPrintOptimizationResult(): void
    {
        ob_start();
        OutputHelper::printOptimizationResult('file.svg', 42.567_89);
        $output = ob_get_clean();

        self::assertNotFalse($output);
        self::assertSame('file.svg (42.57%)' . \PHP_EOL, $output);
    }

    public function testPrintHelp(): void
    {
        ob_start();
        OutputHelper::printHelp();
        $output = ob_get_clean();

        self::assertNotFalse($output);
        self::assertStringContainsString('PHP SVG Optimizer', $output);
        self::assertStringContainsString('Usage:', $output);
        self::assertStringContainsString('Options:', $output);
        self::assertStringContainsString('Commands:', $output);
        self::assertStringContainsString('Examples:', $output);
    }
}
