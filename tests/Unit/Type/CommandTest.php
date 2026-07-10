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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Command::class)]
final class CommandTest extends TestCase
{
    #[DataProvider('provideGetTitleCases')]
    #[Test]
    public function getTitle(Command $command, string $expectedTitle): void
    {
        self::assertSame($expectedTitle, $command->getTitle());
    }

    /**
     * @return \Generator<array{Command, string}>
     */
    public static function provideGetTitleCases(): iterable
    {
        yield 'Process command' => [
            Command::Process,
            'Process',
        ];
    }

    #[DataProvider('provideGetDescriptionCases')]
    #[Test]
    public function getDescription(Command $command, string $expectedDescription): void
    {
        self::assertSame($expectedDescription, $command->getDescription());
    }

    /**
     * @return \Generator<array{Command, string}>
     */
    public static function provideGetDescriptionCases(): iterable
    {
        yield 'Process command' => [
            Command::Process,
            'Provide a list of directories or files to process.',
        ];
    }
}
