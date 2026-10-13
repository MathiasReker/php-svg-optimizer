<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Rule\Trait;

use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertCssClassesToAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertInlineStylesToAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\CssDeclarationTrait;
use MathiasReker\PhpSvgOptimizer\Tests\Unit\Support\RegexFailureTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ConvertCssClassesToAttributes::class)]
#[CoversClass(ConvertInlineStylesToAttributes::class)]
final class CssDeclarationTraitTest extends TestCase
{
    use CssDeclarationTrait;
    use RegexFailureTrait;

    /**
     * @param list<string> $expected
     */
    #[DataProvider('provideSplitsOnSeparatingSemicolonsOnlyCases')]
    #[Test]
    public function splitsOnSeparatingSemicolonsOnly(string $block, array $expected): void
    {
        self::assertTrue($this->isSplittableCssDeclarationBlock($block));
        self::assertSame($expected, $this->splitCssDeclarations($block));
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function provideSplitsOnSeparatingSemicolonsOnlyCases(): iterable
    {
        yield 'plain' => ['fill:red; stroke:blue', ['fill:red', 'stroke:blue']];

        yield 'empty block' => ['', []];

        yield 'empty declarations' => [';; fill:red ;;', ['fill:red']];

        yield 'url' => ['fill:url(data:a;b);x:y', ['fill:url(data:a;b)', 'x:y']];

        yield 'nested parentheses' => ['a:f(g(;));b:c', ['a:f(g(;))', 'b:c']];

        yield 'double quotes' => ['a:"x;y";b:c', ['a:"x;y"', 'b:c']];

        yield 'single quotes' => ["a:'x;y';b:c", ["a:'x;y'", 'b:c']];

        yield 'escaped quote in string' => ['a:"x\";y";b:c', ['a:"x\";y"', 'b:c']];

        yield 'escaped semicolon' => ['a:x\;y;b:c', ['a:x\;y', 'b:c']];

        yield 'parenthesis in string' => ['a:"(";b:c', ['a:"("', 'b:c']];

        yield 'string in parentheses' => ['a:url(")");b:c', ['a:url(")")', 'b:c']];

        yield 'line breaks' => ["a:b;\nc:d", ['a:b', 'c:d']];
    }

    #[DataProvider('provideRejectsAmbiguousBlocksCases')]
    #[Test]
    public function rejectsAmbiguousBlocks(string $block): void
    {
        self::assertFalse($this->isSplittableCssDeclarationBlock($block));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideRejectsAmbiguousBlocksCases(): iterable
    {
        yield 'unclosed parenthesis' => ['fill:url(a;b:c'];

        yield 'unopened parenthesis' => ['fill:a);b:c'];

        yield 'unclosed double quote' => ['a:"x;b:c'];

        yield 'unclosed single quote' => ["a:'x;b:c"];

        yield 'trailing backslash' => ['a:b\\'];
    }

    #[RunInSeparateProcess]
    #[Test]
    public function splitsIntoNoDeclarationsWhenTheBlockCannotBeMatched(): void
    {
        self::assertSame([], $this->withFailingRegularExpressions(fn (): array => $this->splitCssDeclarations('fill:red;stroke:blue')));
    }
}
