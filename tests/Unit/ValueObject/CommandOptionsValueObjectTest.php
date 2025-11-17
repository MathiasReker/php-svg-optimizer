<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\ValueObject;

use MathiasReker\PhpSvgOptimizer\ValueObject\CommandOptionsValueObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CommandOptionsValueObject::class)]
final class CommandOptionsValueObjectTest extends TestCase
{
    public function testPropertiesAreAssignedCorrectly(): void
    {
        $commandOptionsValueObject = new CommandOptionsValueObject(
            true,
            '/path/to/config.json',
            false,
            false,
        );

        self::assertTrue($commandOptionsValueObject->isDryRun());

        self::assertSame('/path/to/config.json', $commandOptionsValueObject->getConfigPath());
    }
}
