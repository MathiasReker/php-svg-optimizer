<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Type;

use MathiasReker\PhpSvgOptimizer\Type\Command;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Command::class)]
final class CommandTest extends TestCase
{
    #[Test]
    public function getTitle(): void
    {
        $command = Command::Process;

        $expectedTitle = 'Process';
        $actualTitle = $command->getTitle();

        self::assertSame($expectedTitle, $actualTitle, 'The getTitle method should return the correct title.');
    }

    #[Test]
    public function getDescription(): void
    {
        $command = Command::Process;

        $expectedDescription = 'Provide a list of directories or files to process.';
        $actualDescription = $command->getDescription();

        self::assertSame($expectedDescription, $actualDescription, 'The getDescription method should return the correct description.');
    }
}
