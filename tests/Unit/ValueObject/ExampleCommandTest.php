<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\ValueObject;

use MathiasReker\PhpSvgOptimizer\ValueObject\ExampleCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ExampleCommand::class)]
final class ExampleCommandTest extends TestCase
{
    private const string EXAMPLE_COMMAND = 'vendor/bin/svg-optimizer --dry-run --quiet process /path/to/svgs';

    private ExampleCommand $exampleCommand;

    #[Test]
    public function getCommand(): void
    {
        self::assertSame(self::EXAMPLE_COMMAND, $this->exampleCommand->getCommand());
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->exampleCommand = new ExampleCommand(self::EXAMPLE_COMMAND);
    }
}
