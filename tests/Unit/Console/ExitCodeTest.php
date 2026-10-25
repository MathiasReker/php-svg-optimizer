<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console;

use MathiasReker\PhpSvgOptimizer\Console\ExitCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ExitCode::class)]
final class ExitCodeTest extends TestCase
{
    #[DataProvider('provideDocumentsTheExitCodesOfTheCommandCases')]
    #[Test]
    public function documentsTheExitCodesOfTheCommand(ExitCode $exitCode, int $value): void
    {
        self::assertSame($value, $exitCode->value);
    }

    /**
     * @return iterable<string, array{ExitCode, int}>
     */
    public static function provideDocumentsTheExitCodesOfTheCommandCases(): iterable
    {
        yield 'success' => [ExitCode::Success, 0];

        yield 'a file failed' => [ExitCode::Failure, 1];

        yield 'invalid usage' => [ExitCode::InvalidUsage, 2];

        yield 'changes detected' => [ExitCode::ChangesDetected, 3];
    }
}
