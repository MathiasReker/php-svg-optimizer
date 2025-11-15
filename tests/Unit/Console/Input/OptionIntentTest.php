<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Input;

use MathiasReker\PhpSvgOptimizer\Console\Input\ArgumentParser;
use MathiasReker\PhpSvgOptimizer\Console\Input\OptionIntent;
use MathiasReker\PhpSvgOptimizer\Service\Data\ArgumentData;
use MathiasReker\PhpSvgOptimizer\Type\Command;
use MathiasReker\PhpSvgOptimizer\Type\Option;
use MathiasReker\PhpSvgOptimizer\ValueObject\ArgumentOptionValueObject;
use MathiasReker\PhpSvgOptimizer\ValueObject\ExampleCommandValueObject;
use MathiasReker\PhpSvgOptimizer\ValueObject\OptionValueObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(OptionIntent::class)]
#[CoversClass(ArgumentParser::class)]
#[CoversClass(ArgumentData::class)]
#[CoversClass(Command::class)]
#[CoversClass(Option::class)]
#[CoversClass(ArgumentOptionValueObject::class)]
#[CoversClass(ExampleCommandValueObject::class)]
#[CoversClass(OptionValueObject::class)]
final class OptionIntentTest extends TestCase
{
    public function testIsDryRunOptionIsSet(): void
    {
        $args = ['vendor/bin/svg-optimizer', '--dry-run'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertTrue($optionIntent->isDryRun());
    }

    public function testIsDryRunOptionIsNotSet(): void
    {
        $args = ['vendor/bin/svg-optimizer'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertFalse($optionIntent->isDryRun());
    }

    public function testIsQuietOptionIsSet(): void
    {
        $args = ['vendor/bin/svg-optimizer', '--quiet'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertTrue($optionIntent->isQuiet());
    }

    public function testIsQuietOptionIsNotSet(): void
    {
        $args = ['vendor/bin/svg-optimizer'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertFalse($optionIntent->isQuiet());
    }

    public function testIsHelpOptionIsSet(): void
    {
        $args = ['vendor/bin/svg-optimizer', '--help'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertTrue($optionIntent->isHelp());
    }

    public function testIsHelpOptionIsNotSet(): void
    {
        $args = ['vendor/bin/svg-optimizer'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertFalse($optionIntent->isHelp());
    }

    public function testIsVersionOptionIsSet(): void
    {
        $args = ['vendor/bin/svg-optimizer', '--version'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertTrue($optionIntent->isVersion());
    }

    public function testIsVersionOptionIsNotSet(): void
    {
        $args = ['vendor/bin/svg-optimizer'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertFalse($optionIntent->isVersion());
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function testGetConfigPathOptionIsSet(): void
    {
        $args = ['vendor/bin/svg-optimizer', '--config=/path/to/config'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertSame('/path/to/config', $optionIntent->getConfigPath());
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function testGetConfigPathOptionIsNotSet(): void
    {
        $args = ['vendor/bin/svg-optimizer'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertSame('', $optionIntent->getConfigPath());
    }

    public function testAllowRiskyOptionIsSet(): void
    {
        $args = ['vendor/bin/svg-optimizer', '--allow-risky'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertTrue($optionIntent->allowRisky());
    }

    public function testAllowRiskyOptionIsNotSet(): void
    {
        $args = ['vendor/bin/svg-optimizer'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertFalse($optionIntent->allowRisky());
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function testMultipleOptionsSet(): void
    {
        $args = ['vendor/bin/svg-optimizer', '--dry-run', '--quiet', '--allow-risky', '--config=/path/to/config'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertTrue($optionIntent->isDryRun());
        self::assertTrue($optionIntent->isQuiet());
        self::assertTrue($optionIntent->allowRisky());
        self::assertSame('/path/to/config', $optionIntent->getConfigPath());
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function testNoOptionsSet(): void
    {
        $args = ['vendor/bin/svg-optimizer'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertFalse($optionIntent->isDryRun());
        self::assertFalse($optionIntent->isQuiet());
        self::assertFalse($optionIntent->isHelp());
        self::assertFalse($optionIntent->isVersion());
        self::assertFalse($optionIntent->allowRisky());
        self::assertSame('', $optionIntent->getConfigPath());
    }
}
