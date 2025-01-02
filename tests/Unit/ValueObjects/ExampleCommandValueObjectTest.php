<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\ValueObjects;

use MathiasReker\PhpSvgOptimizer\ValueObjects\ExampleCommandValueObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ExampleCommandValueObject::class)]
final class ExampleCommandValueObjectTest extends TestCase
{
    private const string EXAMPLE_COMMAND = 'vendor/bin/svg-optimizer --dry-run --quiet process /path/to/svgs';

    private ExampleCommandValueObject $exampleCommandValueObject;

    public function testGetCommand(): void
    {
        self::assertSame(self::EXAMPLE_COMMAND, $this->exampleCommandValueObject->getCommand());
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->exampleCommandValueObject = new ExampleCommandValueObject(self::EXAMPLE_COMMAND);
    }
}
