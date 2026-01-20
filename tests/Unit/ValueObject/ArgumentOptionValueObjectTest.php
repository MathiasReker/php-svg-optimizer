<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\ValueObject;

use MathiasReker\PhpSvgOptimizer\ValueObject\ArgumentOptionValueObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ArgumentOptionValueObject::class)]
final class ArgumentOptionValueObjectTest extends TestCase
{
    private const string SHORTHAND = '-h';

    private const string FULL = '--help';

    private const string DESCRIPTION = 'Display help for the command.';

    private ArgumentOptionValueObject $argumentOptionValueObject;

    #[Test]
    public function getShorthand(): void
    {
        self::assertSame(self::SHORTHAND, $this->argumentOptionValueObject->getShorthand());
    }

    #[Test]
    public function getFull(): void
    {
        self::assertSame(self::FULL, $this->argumentOptionValueObject->getFull());
    }

    #[Test]
    public function getDescription(): void
    {
        self::assertSame(self::DESCRIPTION, $this->argumentOptionValueObject->getDescription());
    }

    #[Test]
    public function hasName(): void
    {
        self::assertTrue($this->argumentOptionValueObject->hasName(self::SHORTHAND));
        self::assertTrue($this->argumentOptionValueObject->hasName(self::FULL));
        self::assertFalse($this->argumentOptionValueObject->hasName('--unknown'));
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->argumentOptionValueObject = new ArgumentOptionValueObject(
            self::SHORTHAND,
            self::FULL,
            self::DESCRIPTION
        );
    }
}
