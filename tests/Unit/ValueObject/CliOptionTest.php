<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\ValueObject;

use MathiasReker\PhpSvgOptimizer\ValueObject\CliOption;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CliOption::class)]
final class CliOptionTest extends TestCase
{
    private const string SHORTHAND = '-h';

    private const string FULL = '--help';

    private const string DESCRIPTION = 'Display help for the command.';

    private CliOption $cliOption;

    #[Test]
    public function getShorthand(): void
    {
        self::assertSame(self::SHORTHAND, $this->cliOption->getShorthand());
    }

    #[Test]
    public function getFull(): void
    {
        self::assertSame(self::FULL, $this->cliOption->getFull());
    }

    #[Test]
    public function getDescription(): void
    {
        self::assertSame(self::DESCRIPTION, $this->cliOption->getDescription());
    }

    #[Test]
    public function hasName(): void
    {
        self::assertTrue($this->cliOption->hasName(self::SHORTHAND));
        self::assertTrue($this->cliOption->hasName(self::FULL));
        self::assertFalse($this->cliOption->hasName('--unknown'));
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->cliOption = new CliOption(
            self::SHORTHAND,
            self::FULL,
            self::DESCRIPTION
        );
    }
}
