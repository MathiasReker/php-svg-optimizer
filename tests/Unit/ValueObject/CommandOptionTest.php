<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\ValueObject;

use MathiasReker\PhpSvgOptimizer\ValueObject\CommandOption;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CommandOption::class)]
final class CommandOptionTest extends TestCase
{
    #[Test]
    public function propertiesAreAssignedCorrectly(): void
    {
        $commandOption = new CommandOption(
            true,
            '/path/to/config.json',
            false,
            true,
        );

        self::assertTrue($commandOption->isDryRun());
        self::assertSame('/path/to/config.json', $commandOption->getConfigPath());
        self::assertFalse($commandOption->allowRisky());
        self::assertTrue($commandOption->withAllRules());
    }

    #[Test]
    public function defaultValuesAreAssignedCorrectly(): void
    {
        $commandOption = new CommandOption(
            false,
            '',
            true,
            false,
        );

        self::assertFalse($commandOption->isDryRun());
        self::assertSame('', $commandOption->getConfigPath());
        self::assertTrue($commandOption->allowRisky());
        self::assertFalse($commandOption->withAllRules());
    }
}
