<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Rule\Trait;

use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertEllipseToCircle;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertShapesToPath;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyListValues;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyNumericValues;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyPathData;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifySvgCoordinates;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyTransformations;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\NumberFormatterTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ConvertEllipseToCircle::class)]
#[CoversClass(ConvertShapesToPath::class)]
#[CoversClass(MinifyListValues::class)]
#[CoversClass(MinifyNumericValues::class)]
#[CoversClass(MinifyPathData::class)]
#[CoversClass(MinifySvgCoordinates::class)]
#[CoversClass(MinifyTransformations::class)]
final class NumberFormatterTraitTest extends TestCase
{
    use NumberFormatterTrait;

    #[DataProvider('provideMinifiesNumberCases')]
    #[Test]
    public function minifiesNumber(string $number, string $expected): void
    {
        self::assertSame($expected, $this->minifyNumber($number));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideMinifiesNumberCases(): iterable
    {
        yield 'drops a plus sign and trailing zeros' => ['+10.500', '10.5'];

        yield 'drops leading zeros' => ['-007.25', '-7.25'];

        yield 'writes a fraction without its leading zero' => ['0.5', '.5'];

        yield 'keeps a trailing decimal point, which the browser rejects' => ['1.', '1.'];

        yield 'keeps a decimal point before an exponent, which the browser rejects' => ['1.e2', '1.e2'];

        yield 'writes negative zero as zero' => ['-0.000', '0'];

        yield 'writes zero with an exponent as zero' => ['0e5', '0'];

        yield 'shortens an exponent' => ['1E+02', '1e2'];

        yield 'drops a zero exponent' => ['5e-00', '5'];

        yield 'keeps a negative exponent' => ['.5e-3', '.5e-3'];

        yield 'never rounds a long fraction' => ['0.1234567890123456789', '.1234567890123456789'];

        yield 'never rounds a huge integer' => ['123456789012345678901234567890', '123456789012345678901234567890'];

        yield 'never rounds a tiny value' => ['1e-7', '1e-7'];

        yield 'keeps trailing zeros of an integer with an exponent' => ['100e-2', '100e-2'];

        yield 'keeps an incomplete exponent' => ['1e', '1e'];

        yield 'keeps a lone decimal point' => ['.', '.'];

        yield 'keeps an empty string' => ['', ''];

        yield 'keeps a doubled sign' => ['--1', '--1'];

        yield 'keeps two decimal points' => ['1.5.5', '1.5.5'];

        yield 'keeps surrounding whitespace' => [' 1.0', ' 1.0'];

        yield 'keeps a unit' => ['1.0px', '1.0px'];

        yield 'keeps non-ASCII digits' => ['１.0', '１.0'];
    }

    #[Test]
    public function detectsZeroNumbers(): void
    {
        self::assertTrue($this->isZeroNumber('-0.0e3'));
        self::assertFalse($this->isZeroNumber('0.0001'));
        self::assertFalse($this->isZeroNumber('zero'));
    }

    #[Test]
    public function detectsPositiveNumbers(): void
    {
        self::assertTrue($this->isPositiveNumber('+1e-9'));
        self::assertTrue($this->isPositiveNumber('.5'));
        self::assertFalse($this->isPositiveNumber('-0.5'));
        self::assertFalse($this->isPositiveNumber('-0'));
        self::assertFalse($this->isPositiveNumber('0.0e5'));
    }

    #[Test]
    public function detectsPlainNumbers(): void
    {
        self::assertTrue($this->isPlainNumber(' -1.5e2 '));
        self::assertFalse($this->isPlainNumber('1px'));
        self::assertFalse($this->isPlainNumber('1 2'));
        self::assertFalse($this->isPlainNumber(''));
    }
}
