<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Pass;

use MathiasReker\PhpSvgOptimizer\Exception\InvalidSvgException;
use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Pass\MergeDuplicateGradients;
use MathiasReker\PhpSvgOptimizer\Pass\Pipeline;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\ClassSelectorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\DeclarationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\SelectorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AnimationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\DefinitionElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\FragmentTargetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdRenameTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Paint\GradientLinkTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
use MathiasReker\PhpSvgOptimizer\Xml\Parser;
use MathiasReker\PhpSvgOptimizer\Xml\Serializer;
use MathiasReker\PhpSvgOptimizer\Xml\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MergeDuplicateGradients::class)]
#[CoversClass(Parser::class)]
#[CoversClass(Pipeline::class)]
#[CoversClass(SvgValidator::class)]
#[CoversTrait(AnimationTrait::class)]
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(ClassSelectorTrait::class)]
#[CoversTrait(DeclarationTrait::class)]
#[CoversTrait(DefinitionElementTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(FragmentTargetTrait::class)]
#[CoversTrait(GradientLinkTrait::class)]
#[CoversTrait(IdReferenceTrait::class)]
#[CoversTrait(IdRenameTrait::class)]
#[CoversTrait(IdTrait::class)]
#[CoversTrait(ScriptingTrait::class)]
#[CoversTrait(SelectorTrait::class)]
#[CoversTrait(StyleSheetTrait::class)]
#[UsesClass(Element::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(Serializer::class)]
final class MergeDuplicateGradientsTest extends TestCase
{
    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideApplyCases')]
    #[Test]
    public function apply(string $input, string $expected): void
    {
        self::assertSame($expected, $this->optimizeContent($input));
    }

    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideApplyCases')]
    #[Test]
    public function applyIsIdempotent(string $input, string $expected): void
    {
        self::assertSame($expected, $this->optimizeContent($this->optimizeContent($input)));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideApplyCases(): iterable
    {
        yield 'Merges identical gradients into the first one' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a" x2="1"><stop offset="0" stop-color="red"/><stop offset="1"/></linearGradient><linearGradient id="b" x2="1"><stop offset="0" stop-color="red"/><stop offset="1"/></linearGradient></defs><rect fill="url(#a)"/><rect fill="url(#b)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a" x2="1"><stop offset="0" stop-color="red"/><stop offset="1"/></linearGradient></defs><rect fill="url(#a)"/><rect fill="url(#a)"/></svg>',
        ];

        yield 'Merges gradients whose attributes are in another order' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><radialGradient id="a" r="1" cx="0"><stop offset="0"/></radialGradient><radialGradient cx="0" id="b" r="1"><stop offset="0"/></radialGradient></defs><rect fill="url(#b)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><radialGradient id="a" r="1" cx="0"><stop offset="0"/></radialGradient></defs><rect fill="url(#a)"/></svg>',
        ];

        yield 'Merges every duplicate into the first gradient' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/><linearGradient id="b"/><linearGradient id="c"/></defs><rect fill="url(#c)" stroke="url(#b)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/></defs><rect fill="url(#a)" stroke="url(#a)"/></svg>',
        ];

        yield 'Rewrites quoted, padded and uppercase url references' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/><linearGradient id="b"/></defs><rect style="fill:url(\'#b\');stroke:URL( &quot;#b&quot; )" filter="url(#f)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/></defs><rect style="fill:url(\'#a\');stroke:URL( &quot;#a&quot; )" filter="url(#f)"/></svg>',
        ];

        yield 'Rewrites a reference in a value with an ampersand' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/><linearGradient id="b"/></defs><rect fill="url(#b)" data-x="a&amp;b"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/></defs><rect fill="url(#a)" data-x="a&amp;b"/></svg>',
        ];

        yield 'Rewrites href and xlink:href links and merges the gradients that become identical' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><linearGradient id="a" x2="1"/><linearGradient id="b" x2="1"/><linearGradient id="c" xlink:href="#a"/><linearGradient id="d" href="#b"/><linearGradient id="e" xlink:href="#b"/></defs><rect fill="url(#c)"/><rect fill="url(#d)"/><rect fill="url(#e)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><linearGradient id="a" x2="1"/><linearGradient id="c" xlink:href="#a"/><linearGradient id="d" href="#a"/></defs><rect fill="url(#c)"/><rect fill="url(#d)"/><rect fill="url(#c)"/></svg>',
        ];

        yield 'Removes a defs left empty' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/></defs><defs><linearGradient id="b"/></defs><rect fill="url(#b)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/></defs><rect fill="url(#a)"/></svg>',
        ];

        yield 'Keeps gradients with different stops' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"><stop offset="0"/></linearGradient><linearGradient id="b"><stop offset="1"/></linearGradient><linearGradient id="c"><stop offset="0"/><stop offset="0"/></linearGradient></defs><rect fill="url(#b)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"><stop offset="0"/></linearGradient><linearGradient id="b"><stop offset="1"/></linearGradient><linearGradient id="c"><stop offset="0"/><stop offset="0"/></linearGradient></defs><rect fill="url(#b)"/></svg>',
        ];

        yield 'Keeps gradients with different attributes or tags' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a" x2="1"/><linearGradient id="b" x2="2"/><radialGradient id="c" x2="1"/></defs><rect fill="url(#b)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a" x2="1"/><linearGradient id="b" x2="2"/><radialGradient id="c" x2="1"/></defs><rect fill="url(#b)"/></svg>',
        ];

        yield 'Merges later gradients when the first one has a stop with an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"><stop id="s" offset="0"/></linearGradient><linearGradient id="b"><stop offset="0"/></linearGradient><linearGradient id="c"><stop offset="0"/></linearGradient></defs><rect fill="url(#a)"/><rect fill="url(#c)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"><stop id="s" offset="0"/></linearGradient><linearGradient id="b"><stop offset="0"/></linearGradient></defs><rect fill="url(#a)"/><rect fill="url(#b)"/></svg>',
        ];

        yield 'Keeps gradients with stops that have an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"><stop id="s" offset="0"/></linearGradient><linearGradient id="b"><stop id="s" offset="0"/></linearGradient></defs><rect fill="url(#b)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"><stop id="s" offset="0"/></linearGradient><linearGradient id="b"><stop id="s" offset="0"/></linearGradient></defs><rect fill="url(#b)"/></svg>',
        ];

        yield 'Keeps a duplicate referenced by a fragment in another attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/><linearGradient id="b"/></defs><rect fill="url(#b)" data-ref="#b"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/><linearGradient id="b"/></defs><rect fill="url(#b)" data-ref="#b"/></svg>',
        ];

        yield 'Keeps gradients with other children than plain stops' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><defs><linearGradient id="a"><animate attributeName="x2" to="1"/></linearGradient><linearGradient id="b"><animate attributeName="x2" to="1"/></linearGradient><linearGradient id="c"><stop offset="0"><set attributeName="offset" to="1"/></stop></linearGradient><linearGradient id="d"><stop offset="0"><set attributeName="offset" to="1"/></stop></linearGradient><linearGradient id="e"><x:stop/></linearGradient><linearGradient id="f"><x:stop/></linearGradient></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><defs><linearGradient id="a"><animate attributeName="x2" to="1"/></linearGradient><linearGradient id="b"><animate attributeName="x2" to="1"/></linearGradient><linearGradient id="c"><stop offset="0"><set attributeName="offset" to="1"/></stop></linearGradient><linearGradient id="d"><stop offset="0"><set attributeName="offset" to="1"/></stop></linearGradient><linearGradient id="e"><x:stop/></linearGradient><linearGradient id="f"><x:stop/></linearGradient></defs></svg>',
        ];

        yield 'Keeps referenced gradients with other children than plain stops' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><defs><linearGradient id="a"><animate attributeName="x2" to="1"/></linearGradient><linearGradient id="b"><animate attributeName="x2" to="1"/></linearGradient><linearGradient id="c"><stop offset="0"><set attributeName="offset" to="1"/></stop></linearGradient><linearGradient id="d"><stop offset="0"><set attributeName="offset" to="1"/></stop></linearGradient><linearGradient id="e"><x:stop/></linearGradient><linearGradient id="f"><x:stop/></linearGradient></defs><rect fill="url(#a)" stroke="url(#b)"/><rect fill="url(#c)" stroke="url(#d)"/><rect fill="url(#e)" stroke="url(#f)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><defs><linearGradient id="a"><animate attributeName="x2" to="1"/></linearGradient><linearGradient id="b"><animate attributeName="x2" to="1"/></linearGradient><linearGradient id="c"><stop offset="0"><set attributeName="offset" to="1"/></stop></linearGradient><linearGradient id="d"><stop offset="0"><set attributeName="offset" to="1"/></stop></linearGradient><linearGradient id="e"><x:stop/></linearGradient><linearGradient id="f"><x:stop/></linearGradient></defs><rect fill="url(#a)" stroke="url(#b)"/><rect fill="url(#c)" stroke="url(#d)"/><rect fill="url(#e)" stroke="url(#f)"/></svg>',
        ];

        yield 'Keeps foreign gradients' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><defs><x:linearGradient id="a"/><x:linearGradient id="b"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><defs><x:linearGradient id="a"/><x:linearGradient id="b"/></defs></svg>',
        ];

        yield 'Keeps gradients with a duplicate id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/><linearGradient id="b"/><linearGradient id="b"/></defs><rect fill="url(#b)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/><linearGradient id="b"/><linearGradient id="b"/></defs><rect fill="url(#b)"/></svg>',
        ];

        yield 'Keeps gradients that inherit from different ancestors' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><g color="red"><linearGradient id="a"><stop stop-color="currentColor"/></linearGradient></g><g color="blue"><linearGradient id="b"><stop stop-color="currentColor"/></linearGradient></g><g x:color="red"><linearGradient id="c"><stop stop-color="currentColor"/></linearGradient></g><g fill="red"><linearGradient id="d"><stop stop-color="currentColor"/></linearGradient></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><g color="red"><linearGradient id="a"><stop stop-color="currentColor"/></linearGradient></g><g color="blue"><linearGradient id="b"><stop stop-color="currentColor"/></linearGradient></g><g x:color="red"><linearGradient id="c"><stop stop-color="currentColor"/></linearGradient></g><g fill="red"><linearGradient id="d"><stop stop-color="currentColor"/></linearGradient></g></svg>',
        ];

        yield 'Keeps referenced gradients that inherit from different ancestors' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x" xmlns:y="urn:"><g color="red"><linearGradient id="a"><stop stop-color="currentColor"/></linearGradient></g><g color="blue"><linearGradient id="b"><stop stop-color="currentColor"/></linearGradient></g><g x:color="red"><linearGradient id="c"><stop stop-color="currentColor"/></linearGradient></g><g fill="red"><linearGradient id="d"><stop stop-color="currentColor"/></linearGradient></g><g y:xcolor="red"><linearGradient id="e"><stop stop-color="currentColor"/></linearGradient></g><g data-a="bc"><linearGradient id="f"/></g><g data-ab="c"><linearGradient id="g"/></g><rect fill="url(#a)"/><rect fill="url(#b)"/><rect fill="url(#c)"/><rect fill="url(#d)"/><rect fill="url(#e)"/><rect fill="url(#f)"/><rect fill="url(#g)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x" xmlns:y="urn:"><g color="red"><linearGradient id="a"><stop stop-color="currentColor"/></linearGradient></g><g color="blue"><linearGradient id="b"><stop stop-color="currentColor"/></linearGradient></g><g x:color="red"><linearGradient id="c"><stop stop-color="currentColor"/></linearGradient></g><g fill="red"><linearGradient id="d"><stop stop-color="currentColor"/></linearGradient></g><g y:xcolor="red"><linearGradient id="e"><stop stop-color="currentColor"/></linearGradient></g><g data-a="bc"><linearGradient id="f"/></g><g data-ab="c"><linearGradient id="g"/></g><rect fill="url(#a)"/><rect fill="url(#b)"/><rect fill="url(#c)"/><rect fill="url(#d)"/><rect fill="url(#e)"/><rect fill="url(#f)"/><rect fill="url(#g)"/></svg>',
        ];

        yield 'Keeps referenced gradients whose ancestors nest an attribute in another order' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g data-x="1"><g data-x="2"><linearGradient id="c"/></g></g><g data-x="2"><g data-x="1"><linearGradient id="d"/></g></g><rect fill="url(#c)"/><rect fill="url(#d)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g data-x="1"><g data-x="2"><linearGradient id="c"/></g></g><g data-x="2"><g data-x="1"><linearGradient id="d"/></g></g><rect fill="url(#c)"/><rect fill="url(#d)"/></svg>',
        ];

        yield 'Keeps referenced gradients whose outer ancestors differ' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g color="red"><g><linearGradient id="a"><stop stop-color="currentColor"/></linearGradient></g></g><g color="blue"><g><linearGradient id="b"><stop stop-color="currentColor"/></linearGradient></g></g><rect fill="url(#a)"/><rect fill="url(#b)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g color="red"><g><linearGradient id="a"><stop stop-color="currentColor"/></linearGradient></g></g><g color="blue"><g><linearGradient id="b"><stop stop-color="currentColor"/></linearGradient></g></g><rect fill="url(#a)"/><rect fill="url(#b)"/></svg>',
        ];

        yield 'Merges referenced gradients nested in equal ancestors' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="blue"><g color="red" data-x="1"><linearGradient id="a"/></g></g><g fill="blue"><g data-x="1" color="red"><linearGradient id="b"/></g></g><rect fill="url(#a)"/><rect fill="url(#b)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="blue"><g color="red" data-x="1"><linearGradient id="a"/></g></g><g fill="blue"><g data-x="1" color="red"/></g><rect fill="url(#a)"/><rect fill="url(#a)"/></svg>',
        ];

        yield 'Merges gradients that inherit from equal ancestors' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g color="red" fill="blue"><linearGradient id="a"><stop stop-color="currentColor"/></linearGradient></g><g fill="blue" color="red"><linearGradient id="b"><stop stop-color="currentColor"/></linearGradient></g><rect fill="url(#b)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g color="red" fill="blue"><linearGradient id="a"><stop stop-color="currentColor"/></linearGradient></g><g fill="blue" color="red"/><rect fill="url(#a)"/></svg>',
        ];

        yield 'Keeps gradients linking into their own group' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a" href="#a"/><linearGradient id="b" href="#a"/><linearGradient id="c" href="#d"/><linearGradient id="d" href="#d"/></defs><rect fill="url(#b)"/><rect fill="url(#c)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a" href="#a"/><linearGradient id="b" href="#a"/><linearGradient id="c" href="#d"/><linearGradient id="d" href="#d"/></defs><rect fill="url(#b)"/><rect fill="url(#c)"/></svg>',
        ];

        yield 'Keeps a duplicate targeted by an animation' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a" x2="1"/><linearGradient id="b" x2="1"/></defs><set href="#b" attributeName="x2" to="2"/><rect fill="url(#b)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a" x2="1"/><linearGradient id="b" x2="1"/></defs><set href="#b" attributeName="x2" to="2"/><rect fill="url(#b)"/></svg>',
        ];

        yield 'Keeps the first gradient when it is targeted by an animation' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a" x2="1"/><linearGradient id="b" x2="1"/></defs><set href="#a" attributeName="x2" to="2"/><rect fill="url(#b)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a" x2="1"/><linearGradient id="b" x2="1"/></defs><set href="#a" attributeName="x2" to="2"/><rect fill="url(#b)"/></svg>',
        ];

        yield 'Keeps a duplicate referenced by an animation begin value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/><linearGradient id="b"/></defs><rect><set attributeName="x" to="1" begin="b.click"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/><linearGradient id="b"/></defs><rect><set attributeName="x" to="1" begin="b.click"/></rect></svg>',
        ];

        yield 'Keeps a duplicate referenced in a way that cannot be rewritten' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><defs><linearGradient id="a"/><linearGradient id="b"/><linearGradient id="d"/><linearGradient id="e"/><linearGradient id="f"/></defs><rect fill="url(#b)" x:href="#b"/><rect fill="url(a.svg#d)"/><rect fill="url(#%65)"/><text aria-labelledby="f"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><defs><linearGradient id="a"/><linearGradient id="b"/><linearGradient id="d"/><linearGradient id="e"/><linearGradient id="f"/></defs><rect fill="url(#b)" x:href="#b"/><rect fill="url(a.svg#d)"/><rect fill="url(#%65)"/><text aria-labelledby="f"/></svg>',
        ];

        yield 'Rewrites an href padded with whitespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/><linearGradient id="c"/></defs><a href=" #c"><rect/></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/></defs><a href="#a"><rect/></a></svg>',
        ];

        yield 'Merges the duplicates that can be rewritten when another one cannot' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/><linearGradient id="b"/><linearGradient id="c"/></defs><rect fill="url(#b)"/><rect><set attributeName="x" to="1" begin="c.click"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/><linearGradient id="c"/></defs><rect fill="url(#a)"/><rect><set attributeName="x" to="1" begin="c.click"/></rect></svg>',
        ];

        yield 'Keeps everything when the document has a style element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{stroke:red}</style><defs><linearGradient id="a"/><linearGradient id="b"/></defs><rect fill="url(#b)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{stroke:red}</style><defs><linearGradient id="a"/><linearGradient id="b"/></defs><rect fill="url(#b)"/></svg>',
        ];

        yield 'Keeps everything when an external style sheet is referenced' => [
            '<?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/><linearGradient id="b"/></defs><rect fill="url(#b)"/></svg>',
            '<?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/><linearGradient id="b"/></defs><rect fill="url(#b)"/></svg>',
        ];

        yield 'Keeps everything when the document has a script' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><script>document.getElementById("b")</script><defs><linearGradient id="a"/><linearGradient id="b"/></defs><rect fill="url(#b)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>document.getElementById("b")</script><defs><linearGradient id="a"/><linearGradient id="b"/></defs><rect fill="url(#b)"/></svg>',
        ];

        yield 'Keeps everything when the document has an event attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg" onload="f()"><defs><linearGradient id="a"/><linearGradient id="b"/></defs><rect fill="url(#b)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" onload="f()"><defs><linearGradient id="a"/><linearGradient id="b"/></defs><rect fill="url(#b)"/></svg>',
        ];

        yield 'Merges gradients in a prefixed document' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:defs><svg:linearGradient id="a"><svg:stop offset="0"/></svg:linearGradient><svg:linearGradient id="b"><svg:stop offset="0"/></svg:linearGradient></svg:defs><svg:rect fill="url(#b)"/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:defs><svg:linearGradient id="a"><svg:stop offset="0"/></svg:linearGradient></svg:defs><svg:rect fill="url(#a)"/></svg:svg>',
        ];

        yield 'Merges gradients in a document without a namespace' => [
            '<svg><defs><linearGradient id="a"><stop offset="0"/></linearGradient><linearGradient id="b"><stop offset="0"/></linearGradient></defs><rect fill="url(#b)"/></svg>',
            '<svg><defs><linearGradient id="a"><stop offset="0"/></linearGradient></defs><rect fill="url(#a)"/></svg>',
        ];

        yield 'Keeps duplicates of a gradient library that other documents may address' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"><stop offset="0"/></linearGradient><linearGradient id="b"><stop offset="0"/></linearGradient></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"><stop offset="0"/></linearGradient><linearGradient id="b"><stop offset="0"/></linearGradient></defs></svg>',
        ];

        yield 'Merges duplicates of a gradient library that other documents cannot address' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><pattern><linearGradient id="a"><stop offset="0"/></linearGradient><linearGradient id="b"><stop offset="0"/></linearGradient><rect fill="url(#b)"/></pattern></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><pattern><linearGradient id="a"><stop offset="0"/></linearGradient><rect fill="url(#a)"/></pattern></defs></svg>',
        ];

        yield 'Keeps an empty document untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Merges gradients in a document with astral characters in attribute names' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"><stop offset="0"/></linearGradient><linearGradient id="b"><stop offset="0"/></linearGradient></defs><rect data-😀="1" fill="url(#a)"/><rect fill="url(#b)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"><stop offset="0"/></linearGradient></defs><rect data-😀="1" fill="url(#a)"/><rect fill="url(#a)"/></svg>',
        ];
    }

    /**
     * @throws InvalidSvgException
     */
    private function optimizeContent(string $content): string
    {
        $pipeline = new Pipeline([new MergeDuplicateGradients()]);

        return $pipeline->run($content);
    }
}
