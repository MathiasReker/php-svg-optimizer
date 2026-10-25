<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Pass\Trait\Number;

use MathiasReker\PhpSvgOptimizer\Pass\ConvertEllipsesToCircles;
use MathiasReker\PhpSvgOptimizer\Pass\ConvertShapesToPaths;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyNumberLists;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyNumericValues;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyPathData;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyTransforms;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AnimationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AttributeNodeTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementRenameTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberLiteralTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ConvertEllipsesToCircles::class)]
#[CoversClass(ConvertShapesToPaths::class)]
#[CoversClass(MinifyNumberLists::class)]
#[CoversClass(MinifyNumericValues::class)]
#[CoversClass(MinifyPathData::class)]
#[CoversClass(MinifyTransforms::class)]
#[CoversTrait(AnimationTrait::class)]
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(AttributeNodeTrait::class)]
#[CoversTrait(ElementRenameTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(NumberLiteralTrait::class)]
#[CoversTrait(ScriptingTrait::class)]
#[CoversTrait(StyleSheetTrait::class)]
final class NumberLiteralTraitTest extends TestCase
{
    use NumberLiteralTrait;

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
