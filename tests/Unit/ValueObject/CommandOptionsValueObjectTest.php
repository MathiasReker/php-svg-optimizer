<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\ValueObject;

use MathiasReker\PhpSvgOptimizer\ValueObject\CommandOptions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CommandOptions::class)]
final class CommandOptionsValueObjectTest extends TestCase
{
    #[Test]
    public function propertiesAreAssignedCorrectly(): void
    {
        $commandOptions = new CommandOptions(
            true,
            '/path/to/config.json',
            false,
            true,
        );

        self::assertTrue($commandOptions->isDryRun());
        self::assertSame('/path/to/config.json', $commandOptions->getConfigPath());
        self::assertFalse($commandOptions->allowRisky());
        self::assertTrue($commandOptions->withAllRules());
    }

    #[Test]
    public function defaultValuesAreAssignedCorrectly(): void
    {
        $commandOptions = new CommandOptions(
            false,
            '',
            true,
            false,
        );

        self::assertFalse($commandOptions->isDryRun());
        self::assertSame('', $commandOptions->getConfigPath());
        self::assertTrue($commandOptions->allowRisky());
        self::assertFalse($commandOptions->withAllRules());
    }
}
