<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Pass\Trait\Number;

use MathiasReker\PhpSvgOptimizer\Pass\ConvertShapesToPaths;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyNumberLists;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyTransforms;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AnimationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AttributeNodeTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementRenameTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberListTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberLiteralTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Path\PathDataSerializerTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ConvertShapesToPaths::class)]
#[CoversClass(MinifyNumberLists::class)]
#[CoversClass(MinifyTransforms::class)]
#[CoversTrait(AnimationTrait::class)]
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(AttributeNodeTrait::class)]
#[CoversTrait(ElementRenameTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(NumberListTrait::class)]
#[CoversTrait(NumberLiteralTrait::class)]
#[CoversTrait(PathDataSerializerTrait::class)]
#[CoversTrait(ScriptingTrait::class)]
#[CoversTrait(StyleSheetTrait::class)]
final class NumberListTraitTest extends TestCase
{
    use NumberListTrait;
    use NumberLiteralTrait;

    /**
     * @param list<string> $expected                     the numbers of the list
     * @param list<string> $expectedCompact              the numbers of the list when separators may be omitted
     * @param list<string> $expectedCompactTrailingComma the numbers of the list when separators may be omitted and the
     *                                                   list may end with a comma
     */
    #[DataProvider('provideParsesNumberListCases')]
    #[Test]
    public function parsesNumberList(string $value, array $expected, array $expectedCompact, array $expectedCompactTrailingComma): void
    {
        self::assertSame($expected, $this->parseNumberList($value, false, false));
        self::assertSame($expected, $this->parseNumberList($value, false, true));
        self::assertSame($expectedCompact, $this->parseNumberList($value, true, false));
        self::assertSame($expectedCompactTrailingComma, $this->parseNumberList($value, true, true));
    }

    /**
     * @return iterable<string, array{string, list<string>, list<string>, list<string>}>
     */
    public static function provideParsesNumberListCases(): iterable
    {
        yield 'whitespace and commas' => [" 1,2 \t3 , 4\n", ['1', '2', '3', '4'], ['1', '2', '3', '4'], ['1', '2', '3', '4']];

        yield 'exponents and signs' => ['1e2 -1E-2 +.5', ['1e2', '-1E-2', '+.5'], ['1e2', '-1E-2', '+.5'], ['1e2', '-1E-2', '+.5']];

        yield 'a single number' => ['7', ['7'], ['7'], ['7']];

        yield 'numbers without a separator' => ['1-2', [], ['1', '-2'], ['1', '-2']];

        yield 'consecutive decimals' => ['1.5.5', [], ['1.5', '.5'], ['1.5', '.5']];

        yield 'an exponent followed by a decimal' => ['1e2.5', [], ['1e2', '.5'], ['1e2', '.5']];

        yield 'a double comma' => ['1,,2', [], [], []];

        yield 'a leading comma' => [',1 2', [], [], []];

        yield 'a trailing comma' => ['1 2,', [], [], ['1', '2']];

        yield 'a trailing comma after whitespace' => ['1 2 , ', [], [], ['1', '2']];

        yield 'two trailing commas' => ['1 2,,', [], [], []];

        yield 'only a comma' => [',', [], [], []];

        yield 'a unit' => ['1px 2', [], [], []];

        yield 'an empty value' => ['', [], [], []];

        yield 'non-ASCII digits' => ['１ 2', [], [], []];
    }

    /**
     * @param list<string> $numbers minified number literals
     */
    #[DataProvider('provideParsesWhatItJoinsCases')]
    #[Test]
    public function parsesWhatItJoins(array $numbers): void
    {
        self::assertSame($numbers, $this->parseNumberList($this->joinNumberList($numbers, true), true, false));
        self::assertSame($numbers, $this->parseNumberList($this->joinNumberList($numbers, true), true, true));
        self::assertSame($numbers, $this->parseNumberList($this->joinNumberList($numbers, false), false, false));
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function provideParsesWhatItJoinsCases(): iterable
    {
        yield 'signs' => [['10', '-10', '-.5', '1']];

        yield 'fractions' => [['.5', '.5', '1.5', '.25']];

        yield 'exponents' => [['1e5', '.5', '-1e-5', '2']];

        yield 'integers' => [['1', '2', '3']];
    }

    /**
     * @param list<string> $numbers
     */
    #[DataProvider('provideJoinsNumberListCases')]
    #[Test]
    public function joinsNumberList(array $numbers, bool $compact, string $expected): void
    {
        self::assertSame($expected, $this->joinNumberList($numbers, $compact));
    }

    /**
     * @return iterable<string, array{list<string>, bool, string}>
     */
    public static function provideJoinsNumberListCases(): iterable
    {
        yield 'spaces when not compact' => [['1', '-2', '.5', '.5'], false, '1 -2 .5 .5'];

        yield 'no space before a sign when compact' => [['1', '-2'], true, '1-2'];

        yield 'no space before a fraction after a fraction when compact' => [['.5', '.5'], true, '.5.5'];

        yield 'no space before a fraction after an exponent when compact' => [['1e5', '.5'], true, '1e5.5'];

        yield 'a space before a fraction after an integer when compact' => [['1', '.5'], true, '1 .5'];

        yield 'a space between integers when compact' => [['1', '2'], true, '1 2'];

        yield 'nothing for an empty list' => [[], true, ''];
    }
}
