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
use MathiasReker\PhpSvgOptimizer\ValueObject\OptionValueObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(OptionValueObject::class)]
#[CoversClass(CommandOptionsValueObject::class)]
final class CommandOptionValueObjectTest extends TestCase
{
    private const string TITLE = 'process';

    private const string DESCRIPTION = 'Process SVG files for optimization.';

    private OptionValueObject $commandOptionValueObject;

    public function testGetTitle(): void
    {
        self::assertSame(self::TITLE, $this->commandOptionValueObject->getTitle());
    }

    public function testGetDescription(): void
    {
        self::assertSame(self::DESCRIPTION, $this->commandOptionValueObject->getDescription());
    }

    public function testDryRunFlag(): void
    {
        $commandOptionsValueObject = new CommandOptionsValueObject(
            true,
            '',
            false,
            false,
        );

        self::assertTrue($commandOptionsValueObject->isDryRun());
    }

    public function testConfigPath(): void
    {
        $path = '/path/to/config.json';

        $commandOptionsValueObject = new CommandOptionsValueObject(
            false,
            $path,
            false,
            false,
        );

        self::assertSame($path, $commandOptionsValueObject->getConfigPath());
    }

    public function testAllowRiskyFlag(): void
    {
        $commandOptionsValueObject = new CommandOptionsValueObject(
            false,
            '',
            true,
            false,
        );

        self::assertTrue($commandOptionsValueObject->allowRisky());
    }

    public function testAllValuesAreStoredCorrectly(): void
    {
        $commandOptionsValueObject = new CommandOptionsValueObject(
            true,
            '/config.json',
            true,
            false,
        );

        self::assertTrue($commandOptionsValueObject->isDryRun());
        self::assertSame('/config.json', $commandOptionsValueObject->getConfigPath());
        self::assertTrue($commandOptionsValueObject->allowRisky());
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->commandOptionValueObject = new OptionValueObject(
            self::TITLE,
            self::DESCRIPTION
        );
    }
}
