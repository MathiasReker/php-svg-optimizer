<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Services\Util;

use MathiasReker\PhpSvgOptimizer\Enums\Command;
use MathiasReker\PhpSvgOptimizer\Enums\Option;
use MathiasReker\PhpSvgOptimizer\Services\Data\ArgumentData;
use MathiasReker\PhpSvgOptimizer\Services\Util\ArgumentParser;
use MathiasReker\PhpSvgOptimizer\ValueObjects\ArgumentOptionValueObject;
use MathiasReker\PhpSvgOptimizer\ValueObjects\CommandOptionValueObject;
use MathiasReker\PhpSvgOptimizer\ValueObjects\ExampleCommandValueObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ArgumentParser::class)]
#[CoversClass(ArgumentOptionValueObject::class)]
#[CoversClass(CommandOptionValueObject::class)]
#[CoversClass(ExampleCommandValueObject::class)]
#[CoversClass(ArgumentData::class)]
#[CoversClass(Option::class)]
#[CoversClass(Command::class)]
final class ArgumentParserTest extends TestCase
{
    private const array EXAMPLE_ARGS = ['vendor/bin/svg-optimizer', '--config=config.json', 'process', '/path/to/file.svg'];

    private ArgumentParser $argumentParser;

    public function testHasOptionReturnsFalseIfOptionDoesNotExist(): void
    {
        $hasDryRunOption = $this->argumentParser->hasOption(Option::DRY_RUN);
        self::assertFalse($hasDryRunOption);
    }

    public function testGetOptionReturnsCorrectValue(): void
    {
        $configOptionValue = $this->argumentParser->getOption(Option::CONFIG);
        self::assertSame('config.json', $configOptionValue);
    }

    public function testGetOptionReturnsNullIfOptionDoesNotExist(): void
    {
        $dryRunOptionValue = $this->argumentParser->getOption(Option::DRY_RUN);
        self::assertNull($dryRunOptionValue);
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function testGetNextPositionalArgumentIndexReturnsCorrectIndex(): void
    {
        $index = $this->argumentParser->getNextPositionalArgumentIndex();
        self::assertSame(2, $index);
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->argumentParser = new ArgumentParser(self::EXAMPLE_ARGS);
    }
}
