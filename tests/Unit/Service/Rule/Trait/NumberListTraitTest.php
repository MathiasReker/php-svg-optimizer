<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Rule\Trait;

use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertShapesToPath;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyListValues;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyTransformations;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\NumberFormatterTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\NumberListTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ConvertShapesToPath::class)]
#[CoversClass(MinifyListValues::class)]
#[CoversClass(MinifyTransformations::class)]
final class NumberListTraitTest extends TestCase
{
    use NumberFormatterTrait;
    use NumberListTrait;

    /**
     * @param list<string> $expected        the numbers of the list
     * @param list<string> $expectedCompact the numbers of the list when separators may be omitted
     */
    #[DataProvider('provideParsesNumberListCases')]
    #[Test]
    public function parsesNumberList(string $value, array $expected, array $expectedCompact): void
    {
        self::assertSame($expected, $this->parseNumberList($value, false));
        self::assertSame($expectedCompact, $this->parseNumberList($value, true));
    }

    /**
     * @return iterable<string, array{string, list<string>, list<string>}>
     */
    public static function provideParsesNumberListCases(): iterable
    {
        yield 'whitespace and commas' => [" 1,2 \t3 , 4\n", ['1', '2', '3', '4'], ['1', '2', '3', '4']];

        yield 'exponents and signs' => ['1e2 -1E-2 +.5', ['1e2', '-1E-2', '+.5'], ['1e2', '-1E-2', '+.5']];

        yield 'a single number' => ['7', ['7'], ['7']];

        yield 'numbers without a separator' => ['1-2', [], ['1', '-2']];

        yield 'consecutive decimals' => ['1.5.5', [], ['1.5', '.5']];

        yield 'an exponent followed by a decimal' => ['1e2.5', [], ['1e2', '.5']];

        yield 'a double comma' => ['1,,2', [], []];

        yield 'a leading comma' => [',1 2', [], []];

        yield 'a trailing comma' => ['1 2,', [], ['1', '2']];

        yield 'a trailing comma after whitespace' => ['1 2 , ', [], ['1', '2']];

        yield 'two trailing commas' => ['1 2,,', [], []];

        yield 'only a comma' => [',', [], []];

        yield 'a unit' => ['1px 2', [], []];

        yield 'an empty value' => ['', [], []];

        yield 'non-ASCII digits' => ['１ 2', [], []];
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
