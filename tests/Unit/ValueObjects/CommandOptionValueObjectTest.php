<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\ValueObjects;

use MathiasReker\PhpSvgOptimizer\ValueObjects\CommandOptionValueObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CommandOptionValueObject::class)]
final class CommandOptionValueObjectTest extends TestCase
{
    private const string TITLE = 'process';

    private const string DESCRIPTION = 'Process SVG files for optimization.';

    private CommandOptionValueObject $commandOptionValueObject;

    public function testGetTitle(): void
    {
        self::assertSame(self::TITLE, $this->commandOptionValueObject->getTitle());
    }

    public function testGetDescription(): void
    {
        self::assertSame(self::DESCRIPTION, $this->commandOptionValueObject->getDescription());
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->commandOptionValueObject = new CommandOptionValueObject(
            self::TITLE,
            self::DESCRIPTION
        );
    }
}
