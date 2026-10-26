<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Pass\Trait\Css;

use MathiasReker\PhpSvgOptimizer\Pass\ConvertCssClassesToAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\ConvertInlineStylesToAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\ClassSelectorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\DeclarationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\SelectorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AnimationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Tests\Double\RegexFailureTrait;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(DeclarationTrait::class)]
#[CoversTrait(SelectorTrait::class)]
#[UsesClass(ConvertCssClassesToAttributes::class)]
#[UsesClass(ConvertInlineStylesToAttributes::class)]
#[UsesTrait(AnimationTrait::class)]
#[UsesTrait(ClassSelectorTrait::class)]
#[UsesTrait(ElementTrait::class)]
#[UsesTrait(IdTrait::class)]
#[UsesTrait(ScriptingTrait::class)]
#[UsesTrait(StyleElementTrait::class)]
#[UsesTrait(StyleSheetTrait::class)]
final class DeclarationTraitTest extends TestCase
{
    use DeclarationTrait;
    use RegexFailureTrait;

    /**
     * @param list<string> $expected
     */
    #[DataProvider('provideSplitsOnSeparatingSemicolonsOnlyCases')]
    #[Test]
    public function splitsOnSeparatingSemicolonsOnly(string $block, array $expected): void
    {
        self::assertTrue($this->isSplittable($block));
        self::assertSame($expected, $this->splitDeclarations($block));
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
        self::assertFalse($this->isSplittable($block));
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

    #[DataProvider('provideFindsRelatedPropertiesCases')]
    #[Test]
    public function findsRelatedProperties(string $css, string $property, bool $related, bool $declared): void
    {
        self::assertSame($related, $this->declaresRelatedProperty($css, $property));
        self::assertSame($declared, $this->declaresProperty($css, $property));
    }

    /**
     * @return iterable<string, array{string, string, bool, bool}>
     */
    public static function provideFindsRelatedPropertiesCases(): iterable
    {
        yield 'the property itself' => [';fill:red', 'fill', false, true];

        yield 'all' => [';ALL : initial', 'fill', true, true];

        yield 'shorthand of a longhand' => [';marker:url(#m)', 'marker-start', true, true];

        yield 'longhand of a shorthand' => ['{overflow-x:hidden}', 'overflow', true, true];

        yield 'prefixed longhand of a shorthand' => [' -webkit-mask-image:none', 'mask', true, true];

        yield 'property sharing a prefix' => [';fill-opacity:1', 'fill', false, false];

        yield 'property inside a value' => [';font-family:all', 'fill', false, false];
    }

    /**
     * @param list<array{string, string}> $expected
     */
    #[DataProvider('provideParsesDeclarationsCases')]
    #[Test]
    public function parsesDeclarations(string $block, array $expected): void
    {
        self::assertSame($expected, $this->parseDeclarations($block));
    }

    /**
     * @return iterable<string, array{string, list<array{string, string}>}>
     */
    public static function provideParsesDeclarationsCases(): iterable
    {
        yield 'trims names and values' => [' Fill : red ;stroke:url(data:a;b)', [['Fill', 'red'], ['stroke', 'url(data:a;b)']]];

        yield 'leaves out declarations without a colon' => ['fill;stroke:', [['stroke', '']]];

        yield 'keeps colons in values' => ['a:b:c', [['a', 'b:c']]];

        yield 'empty block' => ['', []];
    }

    #[Test]
    public function quotesThePropertyNameInItsPattern(): void
    {
        self::assertFalse($this->declaresProperty(';fxll:red', 'f.ll'));
        self::assertTrue($this->declaresProperty(';f.ll:red', 'f.ll'));
    }

    #[Test]
    public function relatesOnlyTheLonghandsOfAShorthand(): void
    {
        self::assertFalse($this->declaresRelatedProperty(';marker:url(#m)', 'markers'));
        self::assertTrue($this->declaresRelatedProperty(';marker:url(#m)', 'marker-end'));
    }

    #[Test]
    public function pinsEveryRepeatedOrImportantProperty(): void
    {
        self::assertSame(
            ['fill' => true, 'stroke' => true],
            $this->repeatedOrImportantProperties('fill:red;Fill:blue;stroke:red!important;opacity:1'),
        );
    }

    #[Test]
    public function pinsEveryPropertyOverriddenByOtherCss(): void
    {
        self::assertSame(
            ['fill' => true, 'stroke' => true, 'marker-end' => true],
            $this->propertiesOverriddenByCss(['fill', 'stroke', 'opacity', 'marker-end'], ';fill:red;[stroke]{}', ';marker:none'),
        );
    }

    #[Test]
    public function pinsEveryPropertyOfABlock(): void
    {
        self::assertSame(
            ['fill' => true, 'stroke' => true],
            $this->propertiesThatStayCss('fill:red;fill:blue;stroke:red;opacity:1', ';stroke:blue', ''),
        );
    }

    #[Test]
    public function findsEveryDifferentlySetProperty(): void
    {
        $domDocument = new \DOMDocument();
        $domElement = $domDocument->createElement('rect');
        $domElement->setAttribute('fill', 'red');
        $domElement->setAttribute('opacity', '1');

        $second = $domDocument->createElement('rect');
        $second->setAttribute('stroke', 'red');

        self::assertSame(
            ['fill' => true, 'stroke' => true],
            $this->differentlySetProperties('FILL:blue;stroke:blue;opacity:1', [$domElement, $second]),
        );
    }

    #[Test]
    public function splitsSelectorListsOutsideParentheses(): void
    {
        self::assertSame(['a', ':is(b, c)', 'd'], $this->splitSelectorList(' a ,:is(b, c),d'));
    }

    #[RunInSeparateProcess]
    #[Test]
    public function keepsTheWholeSelectorListWhenItCannotBeSplit(): void
    {
        self::assertSame(['a, b'], $this->withFailingRegularExpressions(fn (): array => $this->splitSelectorList(' a, b ')));
    }

    #[DataProvider('provideFindsAttributeSelectorsCases')]
    #[Test]
    public function findsAttributeSelectors(string $css, string $attributeName, bool $expected): void
    {
        self::assertSame($expected, $this->hasAttributeSelector($css, $attributeName));
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function provideFindsAttributeSelectorsCases(): iterable
    {
        yield 'attribute selector' => ['[ fill]{}', 'fill', true];

        yield 'namespaced attribute selector' => ['[svg|FILL=red]{}', 'fill', true];

        yield 'property without a selector' => ['a{fill:red}', 'fill', false];

        yield 'longer attribute name' => ['[fill-opacity]{}', 'fill', false];

        yield 'other attribute' => ['[stroke]{}', 'fill', false];

        yield 'quoted attribute name' => ['[f.ll]{}', 'f.ll', true];

        yield 'regular expression characters' => ['[fxll]{}', 'f.ll', false];

        yield 'name before the bracket' => ['fill[x]{}', 'fill', false];
    }

    #[RunInSeparateProcess]
    #[Test]
    public function splitsIntoNoDeclarationsWhenTheBlockCannotBeMatched(): void
    {
        self::assertSame([], $this->withFailingRegularExpressions(fn (): array => $this->splitDeclarations('fill:red;stroke:blue')));
    }
}
