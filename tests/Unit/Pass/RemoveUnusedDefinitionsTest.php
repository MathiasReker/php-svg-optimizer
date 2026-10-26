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
use MathiasReker\PhpSvgOptimizer\Pass\Pipeline;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveUnusedDefinitions;
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
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
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
#[CoversClass(RemoveUnusedDefinitions::class)]
#[CoversTrait(AnimationTrait::class)]
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(ClassSelectorTrait::class)]
#[CoversTrait(DeclarationTrait::class)]
#[CoversTrait(DefinitionElementTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(FragmentTargetTrait::class)]
#[CoversTrait(IdReferenceTrait::class)]
#[CoversTrait(IdTrait::class)]
#[CoversTrait(ScriptingTrait::class)]
#[CoversTrait(SelectorTrait::class)]
#[CoversTrait(StyleSheetTrait::class)]
#[UsesClass(Element::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(Parser::class)]
#[UsesClass(Pipeline::class)]
#[UsesClass(Serializer::class)]
#[UsesClass(SvgValidator::class)]
final class RemoveUnusedDefinitionsTest extends TestCase
{
    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideRemovesUnusedDefinitionsCases')]
    #[Test]
    public function removesUnusedDefinitions(string $input, string $expected): void
    {
        self::assertSame($expected, $this->optimizeContent($input));
    }

    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideRemovesUnusedDefinitionsCases')]
    #[Test]
    public function removesUnusedDefinitionsIdempotently(string $input, string $expected): void
    {
        self::assertSame($expected, $this->optimizeContent($this->optimizeContent($input)));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideRemovesUnusedDefinitionsCases(): iterable
    {
        yield 'Removes an unused gradient and the empty defs' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"><stop offset="0"/></linearGradient></defs><rect width="1" height="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1"/></svg>',
        ];

        yield 'Removes every unused kind of definition except symbols' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><radialGradient id="a"/><pattern id="b"/><clipPath id="c"/><mask id="d"/><filter id="e"/><marker id="f"/><symbol id="g"/></g><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><symbol id="g"/></g><rect/></svg>',
        ];

        yield 'Removes an unused definition outside defs' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><linearGradient id="a"/></g><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g/><rect/></svg>',
        ];

        yield 'Keeps an unused element inside defs that another document may use' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="p" d="M0 0h1"/></defs><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="p" d="M0 0h1"/></defs><rect/></svg>',
        ];

        yield 'Removes an unused element inside defs that cannot be used' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><stop id="p"/></defs><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes an unused definition with an empty id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id=""/></defs><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Keeps a rendered element with an unused id outside defs' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g id="g"><path id="p" d="M0 0h1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g id="g"><path id="p" d="M0 0h1"/></g></svg>',
        ];

        yield 'Keeps definitions without an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient><stop offset="0"/></linearGradient><path d="M0 0h1"/></defs><mask/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient><stop offset="0"/></linearGradient><path d="M0 0h1"/></defs><mask/></svg>',
        ];

        yield 'Keeps a gradient referenced by a paint attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/></defs><rect fill="url(#a)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/></defs><rect fill="url(#a)"/></svg>',
        ];

        yield 'Keeps a gradient referenced by an uppercase and padded paint attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/></defs><rect fill=" URL( #a ) "/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/></defs><rect fill=" URL( #a ) "/></svg>',
        ];

        yield 'Keeps a mask referenced by a style attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m"/></defs><rect style="mask:url(#m)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m"/></defs><rect style="mask:url(#m)"/></svg>',
        ];

        yield 'Keeps a symbol referenced by href and one referenced by xlink:href' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><symbol id="a"/><symbol id="b"/></defs><use href="#a"/><use xlink:href="#b"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><symbol id="a"/><symbol id="b"/></defs><use href="#a"/><use xlink:href="#b"/></svg>',
        ];

        yield 'Keeps a definition referenced by an animation begin value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="p" d="M0 0h1"/></defs><rect><set attributeName="x" to="1" begin="p.click"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="p" d="M0 0h1"/></defs><rect><set attributeName="x" to="1" begin="p.click"/></rect></svg>',
        ];

        yield 'Keeps a definition referenced from a style element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:url(#a)}</style><defs><linearGradient id="a"/><linearGradient id="b"/></defs><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:url(#a)}</style><defs><linearGradient id="a"/></defs><rect/></svg>',
        ];

        yield 'Keeps a definition matched by an exact id attribute selector' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[id="a"]{fill:red}</style><defs><mask id="a"/><mask id="b"/></defs><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[id="a"]{fill:red}</style><defs><mask id="a"/></defs><rect/></svg>',
        ];

        yield 'Removes only the unused definition when the first one is referenced' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/><linearGradient id="b"/></defs><rect fill="url(#a)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/></defs><rect fill="url(#a)"/></svg>',
        ];

        yield 'Removes only the unused definition when a later one is referenced' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/><linearGradient id="b"/></defs><rect fill="url(#b)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="b"/></defs><rect fill="url(#b)"/></svg>',
        ];

        yield 'Removes a gradient only linked from an unused gradient' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a" href="#b"/><linearGradient id="b"><stop offset="0"/></linearGradient></defs><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Keeps a gradient linked from a used gradient' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a" href="#b"/><linearGradient id="b"><stop offset="0"/></linearGradient></defs><rect fill="url(#a)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a" href="#b"/><linearGradient id="b"><stop offset="0"/></linearGradient></defs><rect fill="url(#a)"/></svg>',
        ];

        yield 'Keeps a deeply linked chain from a used definition' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><pattern id="p"><rect fill="url(#a)"/></pattern><linearGradient id="a" href="#b"/><linearGradient id="b" href="#c"/><linearGradient id="c"/></defs><g><g><rect fill="url(#p)"/></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><pattern id="p"><rect fill="url(#a)"/></pattern><linearGradient id="a" href="#b"/><linearGradient id="b" href="#c"/><linearGradient id="c"/></defs><g><g><rect fill="url(#p)"/></g></g></svg>',
        ];

        yield 'Keeps an unused symbol and the gradient it uses' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><symbol id="s"><rect fill="url(#g)"/></symbol><linearGradient id="g"/><linearGradient id="u"/></defs><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><symbol id="s"><rect fill="url(#g)"/></symbol><linearGradient id="g"/></defs><rect/></svg>',
        ];

        yield 'Keeps a group in defs and the definitions it uses' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="g"/><linearGradient id="u"/><g id="icon"><rect fill="url(#g)"/></g></defs><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="g"/><g id="icon"><rect fill="url(#g)"/></g></defs><rect/></svg>',
        ];

        yield 'Keeps the definitions used by a symbol through use' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="p" d="M0 0"/><clipPath id="c"><rect/></clipPath></defs><symbol id="s"><use href="#p" clip-path="url(#c)"/></symbol><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="p" d="M0 0"/><clipPath id="c"><rect/></clipPath></defs><symbol id="s"><use href="#p" clip-path="url(#c)"/></symbol><rect/></svg>',
        ];

        yield 'Keeps every definition of a document that renders nothing' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/><mask id="b"/></defs><filter id="c"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"/><mask id="b"/></defs><filter id="c"/></svg>',
        ];

        yield 'Keeps every definition of a hidden inline sprite' => [
            '<svg xmlns="http://www.w3.org/2000/svg" style="display:none"><defs><linearGradient id="a"/></defs><path d="M0 0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" style="display:none"><defs><linearGradient id="a"/></defs><path d="M0 0"/></svg>',
        ];

        yield 'Keeps every definition when a style sheet shows elements by target' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.i:target{display:inline}</style><defs><linearGradient id="a"/></defs><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.i:target{display:inline}</style><defs><linearGradient id="a"/></defs><rect/></svg>',
        ];

        yield 'Removes an unused gradient inside a hidden group but keeps the group in it' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g display="none"><linearGradient id="a"/><g id="b"/></g><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g display="none"><g id="b"/></g><rect/></svg>',
        ];

        yield 'Removes a gradient only used inside an unused pattern outside defs' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="g"/></defs><g><pattern id="p"><rect fill="url(#g)"/></pattern></g><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g/><rect/></svg>',
        ];

        yield 'Removes unused self-referencing and cyclic definitions' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><pattern id="p" href="#p"/><linearGradient id="a" href="#b"/><linearGradient id="b" href="#a"/></defs><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Keeps a definition with a referenced descendant' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><g id="g"><path id="p" d="M0 0h1"/></g></defs><use href="#p"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><g id="g"><path id="p" d="M0 0h1"/></g></defs><use href="#p"/></svg>',
        ];

        yield 'Keeps style, font and other effective elements inside defs' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><style id="s">rect{fill:red}</style><font id="f"/><font-face id="ff"/><color-profile id="cp"/><discard id="d" href="#r"/></defs><rect id="r"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><style id="s">rect{fill:red}</style><font id="f"/><font-face id="ff"/><color-profile id="cp"/><discard id="d" href="#r"/></defs><rect id="r"/></svg>',
        ];

        yield 'Keeps animations inside defs' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><animateMotion id="a" href="#r" path="M0 0h1"/></defs><rect id="r"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><animateMotion id="a" href="#r" path="M0 0h1"/></defs><rect id="r"/></svg>',
        ];

        yield 'Keeps a definition containing an animation' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m"><animateTransform href="#r" attributeName="transform" type="scale" to="2"/></mask></defs><rect id="r"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m"><animateTransform href="#r" attributeName="transform" type="scale" to="2"/></mask></defs><rect id="r"/></svg>',
        ];

        yield 'Keeps a definition containing a style element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><g id="g"><style>rect{fill:red}</style></g></defs><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><g id="g"><style>rect{fill:red}</style></g></defs><rect/></svg>',
        ];

        yield 'Keeps definitions in another namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><x:linearGradient id="a"/><defs><x:path id="b"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><x:linearGradient id="a"/><defs><x:path id="b"/></defs></svg>',
        ];

        yield 'Keeps everything when a style sheet depends on child positions' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>path:nth-child(2){fill:red}</style><defs><path id="a"/></defs><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>path:nth-child(2){fill:red}</style><defs><path id="a"/></defs><rect/></svg>',
        ];

        yield 'Keeps everything when an external style sheet is referenced' => [
            '<?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"><defs><path id="a"/></defs><rect/></svg>',
            '<?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"><defs><path id="a"/></defs><rect/></svg>',
        ];

        yield 'Keeps everything when a style sheet selects ids by prefix' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[id^="a"]{fill:red}</style><defs><path id="ab"/></defs><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[id^="a"]{fill:red}</style><defs><path id="ab"/></defs><rect/></svg>',
        ];

        yield 'Keeps everything when the document has a script' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><script>document.getElementById("a")</script><defs><path id="a"/></defs><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>document.getElementById("a")</script><defs><path id="a"/></defs><rect/></svg>',
        ];

        yield 'Keeps everything when the document has an uppercase event attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="a"/></defs><rect ONCLICK="f()"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="a"/></defs><rect ONCLICK="f()"/></svg>',
        ];

        yield 'Keeps unused paint servers when a style sheet selects ids by prefix' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[id^="a"]{fill:red}</style><defs><linearGradient id="ab"/></defs><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[id^="a"]{fill:red}</style><defs><linearGradient id="ab"/></defs><rect/></svg>',
        ];

        yield 'Keeps unused paint servers when the document has a script' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><script>document.getElementById("a")</script><defs><linearGradient id="a"/></defs><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>document.getElementById("a")</script><defs><linearGradient id="a"/></defs><rect/></svg>',
        ];

        yield 'Keeps everything when an element has an xml:id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient xml:id="a" href="#b"/><linearGradient id="b"/></defs><rect fill="url(#a)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient xml:id="a" href="#b"/><linearGradient id="b"/></defs><rect fill="url(#a)"/></svg>',
        ];

        yield 'Keeps defs with an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs id="d"><linearGradient id="a"/></defs><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs id="d"/><rect/></svg>',
        ];

        yield 'Removes unused definitions in a prefixed document' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:defs><svg:linearGradient id="a"/><svg:linearGradient id="b"/></svg:defs><svg:rect fill="url(#b)"/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:defs><svg:linearGradient id="b"/></svg:defs><svg:rect fill="url(#b)"/></svg:svg>',
        ];

        yield 'Removes unused definitions in a document without a namespace' => [
            '<svg><defs><mask id="m"/></defs><rect/></svg>',
            '<svg><rect/></svg>',
        ];

        yield 'Keeps an empty document untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Masks: keeps the masks of a resource library, which other documents may reference' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m"><rect/></mask></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m"><rect/></mask></defs></svg>',
        ];

        yield 'Masks: leaves the document untouched when a style sheet depends on siblings' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect:nth-child(3){fill:red}</style><mask id="m"/><rect/><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect:nth-child(3){fill:red}</style><mask id="m"/><rect/><rect/></svg>',
        ];

        yield 'Masks: leaves the document untouched with an external style sheet' => [
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg"><mask id="m"/></svg>',
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg"><mask id="m"/></svg>',
        ];

        yield 'Masks: removes unused mask element' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <mask id="m1"><rect width="100" height="100"/></mask>
                    </defs>
                    <rect width="100" height="100"/>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100"/></svg>',
        ];

        yield 'Masks: keeps mask if referenced' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs><mask id="m1"><circle r="50"/></mask></defs>
                    <rect mask="url(#m1)" width="100" height="100"/>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m1"><circle r="50"/></mask></defs><rect mask="url(#m1)" width="100" height="100"/></svg>',
        ];

        yield 'Masks: keeps a mask referenced before a style element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m1"><circle r="50"/></mask></defs><rect mask="url(#m1)"/><style>.a{fill:red}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m1"><circle r="50"/></mask></defs><rect mask="url(#m1)"/><style>.a{fill:red}</style></svg>',
        ];

        yield 'Masks: removes empty defs after deleting unused mask' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs><mask id="unused"></mask></defs>
                    <rect width="1" height="1"/>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1"/></svg>',
        ];

        yield 'Masks: keeps multiple masks when all are used' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <mask id="m1"><circle r="50"/></mask>
                        <mask id="m2"><rect width="50" height="50"/></mask>
                    </defs>
                    <rect mask="url(#m1)" width="100" height="100"/>
                    <circle mask="url(#m2)" r="40"/>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m1"><circle r="50"/></mask><mask id="m2"><rect width="50" height="50"/></mask></defs><rect mask="url(#m1)" width="100" height="100"/><circle mask="url(#m2)" r="40"/></svg>',
        ];

        yield 'Masks: removes only unused mask when others are referenced' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <mask id="used"><rect width="50" height="50"/></mask>
                        <mask id="unused"><circle r="10"/></mask>
                    </defs>
                    <rect mask="url(#used)" width="100" height="100"/>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="used"><rect width="50" height="50"/></mask></defs><rect mask="url(#used)" width="100" height="100"/></svg>',
        ];

        yield 'Masks: handles multiple defs sections and removes only unused masks' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs><mask id="m1"><rect width="100" height="100"/></mask></defs>
                    <defs><mask id="m2"><circle r="20"/></mask></defs>
                    <rect mask="url(#m2)" width="50" height="50"/>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m2"><circle r="20"/></mask></defs><rect mask="url(#m2)" width="50" height="50"/></svg>',
        ];

        yield 'Masks: keeps mask referenced inside a group' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs><mask id="groupMask"><rect width="10" height="10"/></mask></defs>
                    <g mask="url(#groupMask)">
                        <rect width="100" height="100"/>
                    </g>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="groupMask"><rect width="10" height="10"/></mask></defs><g mask="url(#groupMask)"><rect width="100" height="100"/></g></svg>',
        ];

        yield 'Masks: removes unused masks with whitespace or comments around them' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <!-- comment -->
                        <mask id="unused"><rect/></mask>
                    </defs>
                    <rect width="1" height="1"/>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1"/></svg>',
        ];

        yield 'Masks: ignores non-mask elements inside defs' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <clipPath id="clip1"><rect/></clipPath>
                        <mask id="m1"><rect/></mask>
                    </defs>
                    <rect clip-path="url(#clip1)"/>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><clipPath id="clip1"><rect/></clipPath></defs><rect clip-path="url(#clip1)"/></svg>',
        ];

        yield 'Masks: handles nested mask references (mask references another mask)' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <mask id="m1" mask="url(#m2)"><rect/></mask>
                        <mask id="m2"><circle/></mask>
                    </defs>
                    <rect mask="url(#m1)"/>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m1" mask="url(#m2)"><rect/></mask><mask id="m2"><circle/></mask></defs><rect mask="url(#m1)"/></svg>',
        ];

        yield 'Masks: handles invalid mask references gracefully (no removal crash)' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs><mask id="m1"><rect/></mask></defs>
                    <rect mask="url(#nonexistent)"/>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"><rect mask="url(#nonexistent)"/></svg>',
        ];

        yield 'Masks: keeps a mask without an id, which cannot be unused' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs><mask><rect/></mask></defs>
                    <rect width="100" height="100"/>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask><rect/></mask></defs><rect width="100" height="100"/></svg>',
        ];

        yield 'Masks: handles multiple mask attributes in different namespaces' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <defs><mask id="m1"><rect/></mask></defs>
                    <use xlink:href="#shape" mask="url(#m1)"/>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><mask id="m1"><rect/></mask></defs><use xlink:href="#shape" mask="url(#m1)"/></svg>',
        ];

        yield 'Masks: removes multiple unused masks and cleans up defs' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <mask id="a"><rect/></mask>
                        <mask id="b"><rect/></mask>
                    </defs>
                    <rect width="1" height="1"/>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1"/></svg>',
        ];

        yield 'Masks: keeps mask with similar id substring (no partial matches)' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <mask id="mask1"><rect/></mask>
                        <mask id="mask10"><circle/></mask>
                    </defs>
                    <rect mask="url(#mask1)"/>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="mask1"><rect/></mask></defs><rect mask="url(#mask1)"/></svg>',
        ];

        yield 'Masks: removes unused mask but keeps other defs content' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <mask id="unused"><rect/></mask>
                        <clipPath id="clip1"><circle/></clipPath>
                    </defs>
                    <rect clip-path="url(#clip1)"/>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><clipPath id="clip1"><circle/></clipPath></defs><rect clip-path="url(#clip1)"/></svg>',
        ];

        yield 'Masks: handles defs containing mixed mask and gradient' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <mask id="m1"><rect/></mask>
                        <linearGradient id="grad1"><stop offset="0%"/></linearGradient>
                    </defs>
                    <rect fill="url(#grad1)"/>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="grad1"><stop offset="0%"/></linearGradient></defs><rect fill="url(#grad1)"/></svg>',
        ];

        yield 'Masks: keeps a mask without an id in defs' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs><mask><rect/></mask></defs>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask><rect/></mask></defs></svg>',
        ];

        yield 'Masks: removes mask with empty ID' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs><mask id=""><rect/></mask></defs>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Masks: keeps mask referenced from a style attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m"><rect/></mask></defs><rect style="mask:url(#m)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m"><rect/></mask></defs><rect style="mask:url(#m)"/></svg>',
        ];

        yield 'Masks: keeps mask referenced from a style element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.x{mask:url(#m)}</style><defs><mask id="m"><rect/></mask></defs><rect class="x"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.x{mask:url(#m)}</style><defs><mask id="m"><rect/></mask></defs><rect class="x"/></svg>',
        ];

        yield 'Masks: keeps mask referenced from a CDATA style element with quoted url' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style><![CDATA[.x{mask:url( "#m" )}]]></style><defs><mask id="m"><rect/></mask></defs><rect class="x"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.x{mask:url( "#m" )}</style><defs><mask id="m"><rect/></mask></defs><rect class="x"/></svg>',
        ];

        yield 'Masks: keeps mask referenced by xlink:href' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><mask id="m"><rect/></mask><mask id="n" xlink:href="#m"/></defs><rect mask="url(#n)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><mask id="m"><rect/></mask><mask id="n" xlink:href="#m"/></defs><rect mask="url(#n)"/></svg>',
        ];

        yield 'Masks: keeps mask referenced by href' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m"><rect/></mask></defs><use href="#m"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m"><rect/></mask></defs><use href="#m"/></svg>',
        ];

        yield 'Masks: keeps mask referenced with a quoted url in an attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m"><rect/></mask></defs><rect mask="url(\'#m\')"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m"><rect/></mask></defs><rect mask="url(\'#m\')"/></svg>',
        ];

        yield 'Masks: removes a mask whose reference only appears in text content' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m"><rect/></mask></defs><text>url(#m)</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>url(#m)</text></svg>',
        ];

        yield 'Masks: removes mask whose id only appears as a prefix of a style reference' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.x{mask:url(#m1)}</style><defs><mask id="m"><rect/></mask></defs><rect width="1" height="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.x{mask:url(#m1)}</style><rect width="1" height="1"/></svg>',
        ];

        yield 'Masks: keeps masks in a document with a script, which may apply them' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="m"/><rect id="r"/><script>document.getElementById("r").setAttribute("mask","url(#m)")</script></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="m"/><rect id="r"/><script>document.getElementById("r").setAttribute("mask","url(#m)")</script></svg>',
        ];

        yield 'Masks: keeps masks in a document with an event attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="m"/><rect ONCLICK="void 0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="m"/><rect ONCLICK="void 0"/></svg>',
        ];

        yield 'Masks: keeps every mask when an element has an xml:id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><mask xml:id="m"/><mask xml:id="n"/><rect mask="url(#m)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><mask xml:id="m"/><mask xml:id="n"/><rect mask="url(#m)"/></svg>',
        ];

        yield 'Masks: keeps mask elements of other namespaces and masks without an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><mask xmlns="urn:x"/><mask/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><mask xmlns="urn:x"/><mask/></svg>',
        ];

        yield 'Masks: keeps a mask referenced through an escaped url()' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{mask:url(#\6d)}</style><mask id="m"/><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{mask:url(#\6d)}</style><mask id="m"/><rect/></svg>',
        ];

        yield 'Masks: removes unused masks of a prefixed SVG document' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:defs><svg:mask id="m"/></svg:defs><svg:rect mask="url(#m)"/><svg:mask id="n"/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:defs><svg:mask id="m"/></svg:defs><svg:rect mask="url(#m)"/></svg:svg>',
        ];

        yield 'Masks: keeps a mask referenced by an animation' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="m"/><rect><set attributeName="mask" to="url(#m)"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="m"/><rect><set attributeName="mask" to="url(#m)"/></rect></svg>',
        ];

        yield 'Masks: keeps an unused mask holding a style sheet, which applies to the whole document' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="m"><style>rect{fill:green}</style></mask><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="m"><style>rect{fill:green}</style></mask><rect/></svg>',
        ];

        yield 'Masks: keeps an unused mask holding an animation of another element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><mask><g><set href="#r" attributeName="fill" to="green"/></g></mask><rect id="r"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><mask><g><set href="#r" attributeName="fill" to="green"/></g></mask><rect id="r"/></svg>',
        ];

        yield 'Masks: removes a later unused mask after keeping an earlier one' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="a"/><mask id="b"/><rect mask="url(\'#a\')"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="a"/><rect mask="url(\'#a\')"/></svg>',
        ];

        yield 'Masks: keeps masks referenced through spaced and quoted url() forms' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="a"/><mask id="b"/><mask id="c"/><rect mask="url( &quot;#a&quot; )"/><rect style="mask:url( \'#b\' )"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="a"/><mask id="b"/><rect mask="url( &quot;#a&quot; )"/><rect style="mask:url( \'#b\' )"/></svg>',
        ];

        yield 'Masks: keeps a mask with a unicode id that is referenced' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="ü"/><mask id="ö"/><rect mask="url(#ü)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="ü"/><rect mask="url(#ü)"/></svg>',
        ];

        yield 'Masks: keeps a mask whose id is written with a character reference' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="&#109;"/><rect mask="url(#m)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="m"/><rect mask="url(#m)"/></svg>',
        ];

        yield 'Masks: keeps every mask with a duplicated id that is referenced' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="m"/><mask id="m"/><rect mask="url(#m)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="m"/><mask id="m"/><rect mask="url(#m)"/></svg>',
        ];

        yield 'Masks: keeps a mask referenced from a style element inside a media block' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@media print{rect{mask:url(#m)}}</style><mask id="m"/><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@media print{rect{mask:url(#m)}}</style><mask id="m"/><rect/></svg>',
        ];

        yield 'Masks: removes a deeply nested unused mask' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g><mask id="m"/></g></g></g><rect width="1" height="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g/></g></g><rect width="1" height="1"/></svg>',
        ];

        yield 'Masks: removes unused masks in a document with redeclared namespace prefixes' => [
            '<s:svg xmlns:s="http://www.w3.org/2000/svg"><t:mask xmlns:t="http://www.w3.org/2000/svg" id="m"/><s:rect width="1" height="1"/></s:svg>',
            '<s:svg xmlns:s="http://www.w3.org/2000/svg"><s:rect width="1" height="1"/></s:svg>',
        ];

        yield 'Masks: removes defs with only comments' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs><!-- comment --></defs>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Keeps an unreferenced mask whose content is used' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><mask id="m"><rect id="r" width="10" height="10" fill="red"/></mask><use xlink:href="#r"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><mask id="m"><rect id="r" width="10" height="10" fill="red"/></mask><use xlink:href="#r"/></svg>',
        ];

        yield 'Removes a gradient referenced only from elements that browsers ignore' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><text id="t">a</text><linearGradient id="g"><stop offset="0"/></linearGradient></defs><text><tref href="#t"/><altGlyph href="#g">a</altGlyph><glyphRef href="#g"/></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><text id="t">a</text></defs><text><tref href="#t"/><altGlyph href="#g">a</altGlyph><glyphRef href="#g"/></text></svg>',
        ];
    }

    /**
     * @throws InvalidSvgException
     */
    private function optimizeContent(string $content): string
    {
        $pipeline = new Pipeline([new RemoveUnusedDefinitions()]);

        return $pipeline->run($content);
    }

    #[Test]
    public function handlesManyNamespaceDeclarationsInLinearTime(): void
    {
        $small = $this->secondsToOptimize(2_000);
        $large = $this->secondsToOptimize(8_000);

        self::assertLessThan(10 * max($small, 0.05), $large);
    }

    private function secondsToOptimize(int $count): float
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg" ' . implode(' ', array_map(static fn (int $i): string => 'xmlns:n' . $i . '="urn:' . $i . '"', range(1, $count))) . '><defs><rect id="r"/></defs><use href="#r"/></svg>');

        $start = hrtime(true);
        (new RemoveUnusedDefinitions())->apply($domDocument);

        return (hrtime(true) - $start) / 1e9;
    }
}
