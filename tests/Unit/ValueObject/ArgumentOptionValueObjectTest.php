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
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ArgumentOptionValueObject::class)]
final class ArgumentOptionValueObjectTest extends TestCase
{
    /**
     * The shorthand for the help option.
     * This is used to test the ArgumentOptionValueObject's methods.
     */
    private const string SHORTHAND = '-h';

    /**
     * The full name for the help option.
     * This is used to test the ArgumentOptionValueObject's methods.
     */
    private const string FULL = '--help';

    /**
     * The description for the help option.
     * This is used to test the ArgumentOptionValueObject's methods.
     */
    private const string DESCRIPTION = 'Display help for the command.';

    /**
     * The ArgumentOptionValueObject instance to be tested.
     * This is used to test the ArgumentOptionValueObject's methods.
     */
    private ArgumentOptionValueObject $argumentOptionValueObject;

    public function testGetShorthand(): void
    {
        self::assertSame(self::SHORTHAND, $this->argumentOptionValueObject->getShorthand());
    }

    public function testGetFull(): void
    {
        self::assertSame(self::FULL, $this->argumentOptionValueObject->getFull());
    }

    public function testGetDescription(): void
    {
        self::assertSame(self::DESCRIPTION, $this->argumentOptionValueObject->getDescription());
    }

    public function testHasName(): void
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
