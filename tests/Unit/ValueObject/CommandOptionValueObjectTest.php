<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\ValueObject;

use MathiasReker\PhpSvgOptimizer\ValueObject\CommandHelp;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CommandHelp::class)]
final class CommandOptionValueObjectTest extends TestCase
{
    private const string TITLE = 'process';

    private const string DESCRIPTION = 'Process SVG files for optimization.';

    private CommandHelp $commandHelp;

    #[Test]
    public function getTitle(): void
    {
        self::assertSame(self::TITLE, $this->commandHelp->getTitle());
    }

    #[Test]
    public function getDescription(): void
    {
        self::assertSame(self::DESCRIPTION, $this->commandHelp->getDescription());
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->commandHelp = new CommandHelp(
            self::TITLE,
            self::DESCRIPTION
        );
    }
}
