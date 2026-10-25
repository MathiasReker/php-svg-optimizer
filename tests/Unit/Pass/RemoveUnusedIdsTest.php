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
use MathiasReker\PhpSvgOptimizer\Pass\RemoveUnusedIds;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\ClassSelectorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\DeclarationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\SelectorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AnimationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\DefinitionElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\FragmentTargetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
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
#[CoversClass(RemoveUnusedIds::class)]
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
#[CoversTrait(IdReferenceTrait::class)]
#[CoversTrait(IdTrait::class)]
#[CoversTrait(ScriptingTrait::class)]
#[CoversTrait(SelectorTrait::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(Serializer::class)]
final class RemoveUnusedIdsTest extends TestCase
{
    private const string SVG = '<svg xmlns="http://www.w3.org/2000/svg">';

    private const string SVG_XLINK = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">';

    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideApplyCases')]
    #[Test]
    public function apply(string $input, string $expected): void
    {
        $pipeline = new Pipeline([new RemoveUnusedIds()]);

        $actual = $pipeline->run($input);
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideApplyCases(): iterable
    {
        yield from self::provideRemovedCases();
        yield from self::provideAttributeReferenceCases();
        yield from self::provideStyleReferenceCases();
        yield from self::provideKeptElementCases();
        yield from self::provideFragmentTargetCases();
        yield from self::provideResourceDocumentCases();

        yield 'Removes ids referenced only from elements that browsers ignore' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text id="t">a</text><text id="u">b</text><text><tref href="#t"/><altGlyph href="#u">a</altGlyph><glyphRef href="#t"/></text><rect id="r" fill="url(#t)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text id="t">a</text><text>b</text><text><tref href="#t"/><altGlyph href="#u">a</altGlyph><glyphRef href="#t"/></text><rect fill="url(#t)"/></svg>',
        ];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    private static function provideRemovedCases(): iterable
    {
        yield 'Removes an unreferenced id' => [
            self::SVG . '<rect id="a" width="1"/></svg>',
            self::SVG . '<rect width="1"/></svg>',
        ];

        yield 'Removes an unreferenced xml:id' => [
            self::SVG . '<rect xml:id="a"/></svg>',
            self::SVG . '<rect/></svg>',
        ];

        yield 'Removes an empty id' => [
            self::SVG . '<rect id=""/></svg>',
            self::SVG . '<rect/></svg>',
        ];

        yield 'Removes unreferenced ids of nested elements' => [
            self::SVG . '<a><g id="a"><g id="b"><path id="c" d="M0 0"/></g></g></a></svg>',
            self::SVG . '<a><g><g><path d="M0 0"/></g></g></a></svg>',
        ];

        yield 'Removes only the unreferenced ids' => [
            self::SVG . '<defs><linearGradient id="used"/><linearGradient id="unused"/></defs><rect fill="url(#used)"/></svg>',
            self::SVG . '<defs><linearGradient id="used"/><linearGradient/></defs><rect fill="url(#used)"/></svg>',
        ];

        yield 'Matches ids case-sensitively' => [
            self::SVG . '<rect id="A"/><use href="#a"/></svg>',
            self::SVG . '<rect/><use href="#a"/></svg>',
        ];

        yield 'Removes an id that is only a prefix of a reference' => [
            self::SVG . '<rect id="a"/><use href="#ab"/></svg>',
            self::SVG . '<rect/><use href="#ab"/></svg>',
        ];

        yield 'Removes an id that only starts with a reference' => [
            self::SVG . '<rect id="ab"/><use href="#a"/></svg>',
            self::SVG . '<rect/><use href="#a"/></svg>',
        ];

        yield 'Removes an id mentioned without a fragment in an ordinary attribute' => [
            self::SVG . '<rect id="a" class="a"/><text>#a</text></svg>',
            self::SVG . '<rect class="a"/><text>#a</text></svg>',
        ];

        yield 'Removes an id referenced only by a non-timing attribute with a dot' => [
            self::SVG . '<rect id="a"/><animate attributeName="x" values="a.end" dur="1s"/></svg>',
            self::SVG . '<rect/><animate attributeName="x" values="a.end" dur="1s"/></svg>',
        ];

        yield 'Removes an id listed in an attribute that does not hold ids' => [
            self::SVG . '<rect id="a" aria-label="a"/></svg>',
            self::SVG . '<rect aria-label="a"/></svg>',
        ];

        yield 'Removes an id mentioned in text outside a style element' => [
            self::SVG . '<rect id="a"/><desc>url(#a)</desc></svg>',
            self::SVG . '<rect/><desc>url(#a)</desc></svg>',
        ];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    private static function provideAttributeReferenceCases(): iterable
    {
        $references = [
            'a fill url' => '<rect fill="url(#a)"/>',
            'a quoted url' => '<rect clip-path="url(\'#a\')"/>',
            'a double-quoted spaced url' => '<rect mask="url( &quot;#a&quot; )"/>',
            'a fallback paint' => '<rect stroke="url(#a) red"/>',
            'a style attribute url' => '<rect style="filter:url(#a)"/>',
            'a marker url' => '<path d="M0 0" marker-start="url(#a)"/>',
            'an href fragment' => '<use href="#a"/>',
            'an anchor fragment link' => '<a href="#a"><text>t</text></a>',
            'an animation target' => '<animate href="#a" attributeName="x" to="1" dur="1s"/>',
            'an animation value' => '<set attributeName="href" to="#a"/>',
            'a fragment of another document' => '<use href="other.svg#a"/>',
            'aria-labelledby' => '<g aria-labelledby="x a"/>',
            'aria-describedby with surrounding whitespace' => '<g aria-describedby="  a  "/>',
            'aria-controls' => '<g aria-controls="a"/>',
            'aria-owns' => '<g aria-owns="a"/>',
            'aria-flowto' => '<g aria-flowto="a"/>',
            'aria-activedescendant' => '<g aria-activedescendant="a"/>',
            'aria-details' => '<g aria-details="a"/>',
            'aria-errormessage' => '<g aria-errormessage="a"/>',
            'a label for' => '<foreignObject><label xmlns="http://www.w3.org/1999/xhtml" for="a">l</label></foreignObject>',
            'a begin timing' => '<animate begin="a.end" attributeName="x" to="1" dur="1s"/>',
            'a begin timing with an offset' => '<animate begin="a.begin+1s" attributeName="x" to="1" dur="1s"/>',
            'a later begin timing' => '<animate begin="2s; x.click; a.click" attributeName="x" to="1" dur="1s"/>',
            'an end timing' => '<animate end="a.repeat(2)" attributeName="x" to="1" dur="1s"/>',
            'an earlier begin timing' => '<animate begin="a.click; 2s" attributeName="x" to="1" dur="1s"/>',
            'an id list holding a fragment' => '<g aria-labelledby="x #a"/>',
        ];

        foreach ($references as $name => $reference) {
            $svg = self::SVG . '<rect id="a"/>' . $reference . '</svg>';

            yield 'Keeps an id referenced by ' . $name => [$svg, $svg];
        }

        yield 'Keeps an id referenced by xlink:href' => [
            self::SVG_XLINK . '<rect id="a"/><use xlink:href="#a"/></svg>',
            self::SVG_XLINK . '<rect id="a"/><use xlink:href="#a"/></svg>',
        ];

        yield 'Follows references after a reference to a missing id' => [
            self::SVG . '<defs><g id="a"><use href="#b"/></g><rect id="b"/></defs><use href="#a"/><use href="#missing"/></svg>',
            self::SVG . '<defs><g id="a"><use href="#b"/></g><rect id="b"/></defs><use href="#a"/><use href="#missing"/></svg>',
        ];

        yield 'Follows the references of a definition referenced before a missing id' => [
            self::SVG . '<defs><linearGradient id="g" href="#h"/><linearGradient id="h"/></defs><rect fill="url(#g)" stroke="url(#missing)"/></svg>',
            self::SVG . '<defs><linearGradient id="g" href="#h"/><linearGradient id="h"/></defs><rect fill="url(#g)" stroke="url(#missing)"/></svg>',
        ];

        yield 'Follows the references of every descendant of a used definition' => [
            self::SVG . '<defs><g id="a" fill="url(#g0)"><rect fill="url(#g1)"/><rect fill="url(#g2)"/></g><linearGradient id="g0"/><linearGradient id="g1"/><linearGradient id="g2"/></defs><use href="#a"/></svg>',
            self::SVG . '<defs><g id="a" fill="url(#g0)"><rect fill="url(#g1)"/><rect fill="url(#g2)"/></g><linearGradient id="g0"/><linearGradient id="g1"/><linearGradient id="g2"/></defs><use href="#a"/></svg>',
        ];

        yield 'Follows references through a chain of definitions' => [
            self::SVG . '<defs><g id="a"><use href="#b"/></g><g id="b"><use href="#c"/></g><rect id="c"/></defs><use href="#a"/></svg>',
            self::SVG . '<defs><g id="a"><use href="#b"/></g><g id="b"><use href="#c"/></g><rect id="c"/></defs><use href="#a"/></svg>',
        ];

        yield 'Follows references through a cycle of definitions' => [
            self::SVG . '<defs><g id="a"><use href="#b"/></g><g id="b"><use href="#a"/></g><linearGradient id="c"/></defs><use href="#a"/></svg>',
            self::SVG . '<defs><g id="a"><use href="#b"/></g><g id="b"><use href="#a"/></g><linearGradient/></defs><use href="#a"/></svg>',
        ];

        yield 'Follows references from the first of two elements with the same id' => [
            self::SVG . '<defs><g id="a"><use href="#b"/></g><g id="a"><use href="#c"/></g></defs><rect id="b"/><rect id="c"/><use href="#a"/></svg>',
            self::SVG . '<defs><g id="a"><use href="#b"/></g><g id="a"><use href="#c"/></g></defs><rect id="b"/><rect/><use href="#a"/></svg>',
        ];

        yield 'Keeps an id referenced by a percent-encoded fragment' => [
            self::SVG . '<path id="人口" d="M0 0h9v9z"/><use href="#%E4%BA%BA%E5%8F%A3"/></svg>',
            self::SVG . '<path id="人口" d="M0 0h9v9z"/><use href="#%E4%BA%BA%E5%8F%A3"/></svg>',
        ];

        yield 'Keeps an id containing a percent sign referenced as written' => [
            self::SVG . '<rect id="a%20b"/><use href="#a%20b"/></svg>',
            self::SVG . '<rect id="a%20b"/><use href="#a%20b"/></svg>',
        ];

        yield 'Keeps an xml:id that is referenced' => [
            self::SVG . '<rect xml:id="a"/><use href="#a"/></svg>',
            self::SVG . '<rect xml:id="a"/><use href="#a"/></svg>',
        ];

        yield 'Keeps an id with a dot referenced by href' => [
            self::SVG . '<rect id="a.b"/><use href="#a.b"/></svg>',
            self::SVG . '<rect id="a.b"/><use href="#a.b"/></svg>',
        ];

        yield 'Keeps an id with an escaped dot referenced by a timing' => [
            self::SVG . '<rect id="a.b"/><animate begin="a\.b.click" attributeName="x" to="1" dur="1s"/></svg>',
            self::SVG . '<rect id="a.b"/><animate begin="a\.b.click" attributeName="x" to="1" dur="1s"/></svg>',
        ];

        yield 'Keeps a numeric id' => [
            self::SVG . '<rect id="1"/><use href="#1"/></svg>',
            self::SVG . '<rect id="1"/><use href="#1"/></svg>',
        ];

        yield 'Removes the id of an unused definition that references itself' => [
            self::SVG . '<linearGradient id="a" href="#a"/><rect/></svg>',
            self::SVG . '<linearGradient href="#a"/><rect/></svg>',
        ];

        yield 'Keeps the id of a used definition that references itself' => [
            self::SVG . '<linearGradient id="a" href="#a"/><rect fill="url(#a)"/></svg>',
            self::SVG . '<linearGradient id="a" href="#a"/><rect fill="url(#a)"/></svg>',
        ];

        yield 'Removes every id of an unused chain of linked gradients' => [
            self::SVG . '<linearGradient id="a"/><linearGradient id="b" href="#a"/><linearGradient id="c" href="#b"/><rect fill="red"/></svg>',
            self::SVG . '<linearGradient/><linearGradient href="#a"/><linearGradient href="#b"/><rect fill="red"/></svg>',
        ];

        yield 'Keeps every id of a used chain of linked gradients' => [
            self::SVG . '<linearGradient id="a"/><linearGradient id="b" href="#a"/><linearGradient id="c" href="#b"/><rect fill="url(#c)"/></svg>',
            self::SVG . '<linearGradient id="a"/><linearGradient id="b" href="#a"/><linearGradient id="c" href="#b"/><rect fill="url(#c)"/></svg>',
        ];

        yield 'Removes ids only referenced from inside an unused mask' => [
            self::SVG . '<linearGradient id="g"/><mask id="m"><rect fill="url(#g)"/></mask><rect/></svg>',
            self::SVG . '<linearGradient/><mask><rect fill="url(#g)"/></mask><rect/></svg>',
        ];

        yield 'Keeps ids referenced from inside a used mask' => [
            self::SVG . '<linearGradient id="g"/><mask id="m"><rect fill="url(#g)"/></mask><rect mask="url(#m)"/></svg>',
            self::SVG . '<linearGradient id="g"/><mask id="m"><rect fill="url(#g)"/></mask><rect mask="url(#m)"/></svg>',
        ];

        yield 'Keeps ids referenced from inside defs content used by use' => [
            self::SVG . '<defs><linearGradient id="g"/><g id="icon"><rect fill="url(#g)"/></g></defs><use href="#icon"/></svg>',
            self::SVG . '<defs><linearGradient id="g"/><g id="icon"><rect fill="url(#g)"/></g></defs><use href="#icon"/></svg>',
        ];

        yield 'Removes ids referenced only from unused defs content' => [
            self::SVG . '<defs><linearGradient id="g"/><mask id="icon"><rect fill="url(#g)"/></mask></defs><rect/></svg>',
            self::SVG . '<defs><linearGradient/><mask><rect fill="url(#g)"/></mask></defs><rect/></svg>',
        ];

        yield 'Keeps ids referenced from inside a symbol' => [
            self::SVG . '<linearGradient id="g"/><symbol id="s"><rect fill="url(#g)"/></symbol></svg>',
            self::SVG . '<linearGradient id="g"/><symbol id="s"><rect fill="url(#g)"/></symbol></svg>',
        ];

        yield 'Keeps ids referenced from a style element inside defs' => [
            self::SVG . '<defs><style>.a{fill:url(#g)}</style><linearGradient id="g"/></defs><rect class="a"/></svg>',
            self::SVG . '<defs><style>.a{fill:url(#g)}</style><linearGradient id="g"/></defs><rect class="a"/></svg>',
        ];

        yield 'Keeps an id spelled like a hex color in use' => [
            self::SVG . '<rect id="abc"/><rect fill="#abc"/></svg>',
            self::SVG . '<rect id="abc"/><rect fill="#abc"/></svg>',
        ];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    private static function provideStyleReferenceCases(): iterable
    {
        $styles = [
            'a style url' => '.x{fill:url(#a)}',
            'a quoted style url' => '.x{fill:url("#a")}',
            'an id selector' => '#a{fill:red}',
            'an id selector with a pseudo-class' => '#a:hover{fill:red}',
            'an id selector with a class' => '#a.x{fill:red}',
            'an id selector in a list' => 'rect,#a,circle{fill:red}',
            'an id selector after a descendant combinator' => 'g #a{fill:red}',
            'an id selector before a sibling combinator' => '#a+rect{fill:red}',
            'an id selector before a general sibling combinator' => '#a~rect{fill:red}',
            'an id selector before a universal selector' => '#a*{fill:red}',
            'an id selector with an attribute selector' => '#a[x]{fill:red}',
            'an attribute selector' => '[id="a"]{fill:red}',
            'a single-quoted attribute selector' => "rect[ ID = 'a' ]{fill:red}",
            'an unquoted attribute selector' => '[id=a]{fill:red}',
            'a namespaced attribute selector' => '[*|id="a"]{fill:red}',
            'an id selector in an :is() list' => ':is(rect, #a){fill:red}',
            'an id selector in a :not() list' => 'rect:not(#b, #a){fill:red}',
            'an id selector in a media block' => '@media print{#a{fill:red}}',
            'an id selector in a supports block' => '@supports (fill:red){#a{fill:red}}',
            'an id selector in a layer block' => '@layer x{#a{fill:red}}',
            'an id selector in a container block' => '@container (min-width:1px){#a{fill:red}}',
            'a nested id selector' => 'g{&amp; #a{fill:red}}',
            'an id selector with a comment' => '#a/* c */{fill:red}',
            'an important declaration' => '#a{fill:red!important}',
            'a spaced double-quoted style url' => '.x{fill:url( "#a" )}',
            'a case-insensitive attribute selector' => '[id="a" i]{fill:red}',
        ];

        foreach ($styles as $name => $style) {
            $svg = self::SVG . '<style>' . $style . '</style><rect id="a"/></svg>';

            yield 'Keeps an id referenced by ' . $name => [$svg, $svg];
        }

        yield 'Keeps an id referenced from a CDATA style element' => [
            self::SVG . '<style><![CDATA[#a{fill:red}]]></style><rect id="a"/><rect id="b"/></svg>',
            self::SVG . '<style>#a{fill:red}</style><rect id="a"/><rect/></svg>',
        ];

        yield 'Keeps an id with a dot referenced by an escaped selector' => [
            self::SVG . '<style>#a\.b{fill:red}</style><rect id="a.b"/></svg>',
            self::SVG . '<style>#a\.b{fill:red}</style><rect id="a.b"/></svg>',
        ];

        yield 'Removes an id that an exact attribute selector does not reference' => [
            self::SVG . '<style>[id="b"]{fill:red}</style><rect id="a"/><rect id="b"/></svg>',
            self::SVG . '<style>[id="b"]{fill:red}</style><rect/><rect id="b"/></svg>',
        ];

        foreach (['[id]', '[id^="a"]', '[id*=b]', '[id="A" i]', '[ xml|id ]'] as $selector) {
            $svg = self::SVG . '<style>' . $selector . '{fill:red}</style><rect id="ab"/><rect id="c"/></svg>';

            yield 'Keeps every id when the attribute selector ' . $selector . ' may match several' => [$svg, $svg];
        }

        yield 'Removes an id that a style element does not reference' => [
            self::SVG . '<style>#ab{fill:red} .a{fill:red}</style><rect id="a"/></svg>',
            self::SVG . '<style>#ab{fill:red} .a{fill:red}</style><rect/></svg>',
        ];

        yield 'Keeps an id that a style element references through a hexadecimal escape' => [
            self::SVG . '<style>#\31 23{fill:red}#\61{fill:blue}</style><rect id="123"/><rect id="a"/><rect id="b"/></svg>',
            self::SVG . '<style>#\31 23{fill:red}#\61{fill:blue}</style><rect id="123"/><rect id="a"/><rect/></svg>',
        ];

        yield 'Keeps an id that a style attribute references through an escaped url()' => [
            self::SVG . '<mask id="m"/><rect style="mask:url(#\6d)"/></svg>',
            self::SVG . '<mask id="m"/><rect style="mask:url(#\6d)"/></svg>',
        ];

        yield 'Keeps an id that an exact attribute selector references through an escape' => [
            self::SVG . '<style>[id="\61"]{fill:red}</style><rect id="a"/></svg>',
            self::SVG . '<style>[id="\61"]{fill:red}</style><rect id="a"/></svg>',
        ];

        yield 'Decodes an escape beyond Unicode as the replacement character' => [
            self::SVG . '<style>#\110000{fill:red}</style><rect id="a"/><rect id="�"/></svg>',
            self::SVG . '<style>#\110000{fill:red}</style><rect/><rect id="�"/></svg>',
        ];

        yield 'Keeps a unicode id that is referenced and removes one that is not' => [
            self::SVG . '<linearGradient id="ü"/><linearGradient id="ö"/><rect fill="url(#ü)"/></svg>',
            self::SVG . '<linearGradient id="ü"/><linearGradient/><rect fill="url(#ü)"/></svg>',
        ];

        yield 'Keeps an id written with a character reference' => [
            self::SVG . '<rect id="&#97;"/><use href="#a"/></svg>',
            self::SVG . '<rect id="a"/><use href="#a"/></svg>',
        ];

        yield 'Keeps every element with a duplicated id that is referenced' => [
            self::SVG . '<rect id="a"/><rect id="a"/><rect id="b"/><rect id="b"/><use href="#a"/></svg>',
            self::SVG . '<rect id="a"/><rect id="a"/><rect/><rect/><use href="#a"/></svg>',
        ];

        yield 'Removes an id of an element whose default namespace is reset' => [
            self::SVG . '<g xmlns=""><x id="a"/></g></svg>',
            self::SVG . '<g xmlns=""><x/></g></svg>',
        ];

        yield 'Leaves an empty document unchanged' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Keeps ids referenced from several style elements' => [
            self::SVG . '<style>#a{fill:red}</style><style>#b{fill:red}</style><rect id="a"/><rect id="b"/><rect id="c"/></svg>',
            self::SVG . '<style>#a{fill:red}</style><style>#b{fill:red}</style><rect id="a"/><rect id="b"/><rect/></svg>',
        ];

        yield 'Keeps an id referenced through a custom xlink prefix' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:l="http://www.w3.org/1999/xlink"><rect id="a"/><rect id="b"/><use l:href="#a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:l="http://www.w3.org/1999/xlink"><rect id="a"/><rect/><use l:href="#a"/></svg>',
        ];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    private static function provideKeptElementCases(): iterable
    {
        yield 'Keeps the id of the root element' => [
            '<svg xmlns="http://www.w3.org/2000/svg" id="root"><rect id="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" id="root"><rect/></svg>',
        ];

        yield 'Keeps the ids of symbols and views' => [
            self::SVG . '<symbol id="icon"><path id="p" d="M0 0"/></symbol><view id="v" viewBox="0 0 1 1"/></svg>',
            self::SVG . '<symbol id="icon"><path d="M0 0"/></symbol><view id="v" viewBox="0 0 1 1"/></svg>',
        ];

        yield 'Keeps the xml:id of the root element' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xml:id="root"><rect xml:id="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xml:id="root"><rect/></svg>',
        ];

        $scripted = [
            'a script element' => self::SVG . '<script>void 0</script><rect id="a"/></svg>',
            'a script element in defs' => self::SVG . '<defs><script>void 0</script></defs><rect id="a"/></svg>',
            'an event attribute' => self::SVG . '<rect id="a" onclick="void 0"/></svg>',
            'an event attribute on the root' => '<svg xmlns="http://www.w3.org/2000/svg" onload="void 0"><rect id="a"/></svg>',
            'an uppercase event attribute' => self::SVG . '<rect id="a"/><g ONMOUSEOVER="void 0"/></svg>',
            'a later event attribute' => self::SVG . '<rect id="a"/><g class="c" onfocus="void 0"/></svg>',
        ];

        foreach ($scripted as $name => $svg) {
            yield 'Leaves documents with ' . $name . ' untouched' => [$svg, $svg];
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    private static function provideFragmentTargetCases(): iterable
    {
        yield 'Keeps the ids of top-level groups, nested svg elements and symbols, but not of other top-level elements' => [
            self::SVG . '<g id="g"/><svg id="s"/><symbol id="y"/><path id="p" d="M0 0"/><a id="l"/></svg>',
            self::SVG . '<g id="g"/><svg id="s"/><symbol id="y"/><path d="M0 0"/><a/></svg>',
        ];

        yield 'Keeps the ids of symbols and views anywhere' => [
            self::SVG . '<g><g><symbol id="s"/><view id="v"/><path id="p" d="M0 0"/></g></g></svg>',
            self::SVG . '<g><g><symbol id="s"/><view id="v"/><path d="M0 0"/></g></g></svg>',
        ];

        yield 'Keeps both the id and the xml:id of a symbol' => [
            self::SVG . '<symbol id="s" xml:id="t"/><rect/></svg>',
            self::SVG . '<symbol id="s" xml:id="t"/><rect/></svg>',
        ];

        yield 'Keeps ids referenced from inside a symbol without an id in defs' => [
            self::SVG . '<defs><symbol><rect fill="url(#g)"/></symbol><linearGradient id="g"/><linearGradient id="h"/></defs><rect/></svg>',
            self::SVG . '<defs><symbol><rect fill="url(#g)"/></symbol><linearGradient id="g"/><linearGradient/></defs><rect/></svg>',
        ];

        yield 'Keeps the xml:id of a symbol' => [
            self::SVG . '<symbol xml:id="s"/><rect/></svg>',
            self::SVG . '<symbol xml:id="s"/><rect/></svg>',
        ];

        yield 'Keeps the ids of usable elements in defs, but not of paint servers' => [
            self::SVG . '<defs><linearGradient id="b"/><path id="a" d="M0 0"/><text id="t">x</text></defs><rect/></svg>',
            self::SVG . '<defs><linearGradient/><path id="a" d="M0 0"/><text id="t">x</text></defs><rect/></svg>',
        ];

        yield 'Keeps ids referenced from a group in defs' => [
            self::SVG . '<defs><linearGradient id="g"/><linearGradient id="u"/><g id="icon"><rect fill="url(#g)"/></g></defs><rect/></svg>',
            self::SVG . '<defs><linearGradient id="g"/><linearGradient/><g id="icon"><rect fill="url(#g)"/></g></defs><rect/></svg>',
        ];

        yield 'Keeps the ids inside groups in defs, but not inside nested svg elements in defs' => [
            self::SVG . '<defs><svg id="c"><g id="d"/></svg><g id="a"><switch><g id="b"/></switch></g></defs><rect/></svg>',
            self::SVG . '<defs><svg id="c"><g/></svg><g id="a"><switch><g id="b"/></switch></g></defs><rect/></svg>',
        ];

        yield 'Removes the ids in defs inside a rendered group' => [
            self::SVG . '<g><defs><path id="a" d="M0 0"/></defs></g><rect/></svg>',
            self::SVG . '<g><defs><path d="M0 0"/></defs></g><rect/></svg>',
        ];

        yield 'Removes the ids of groups inside a top-level group' => [
            self::SVG . '<g id="a"><g id="b"><rect/></g></g></svg>',
            self::SVG . '<g id="a"><g><rect/></g></g></svg>',
        ];

        $hidden = [
            'a display attribute' => 'display="none"',
            'a display attribute with spaces and capitals' => 'display=" NONE "',
            'a style attribute' => 'style="display: none"',
            'an important style declaration after another' => 'style="opacity:1;display:none !important;"',
        ];

        foreach ($hidden as $name => $attribute) {
            yield 'Keeps the ids of usable elements inside a group hidden by ' . $name => [
                self::SVG . '<g ' . $attribute . '><linearGradient id="b"/><g id="a"/></g><rect/></svg>',
                self::SVG . '<g ' . $attribute . '><linearGradient/><g id="a"/></g><rect/></svg>',
            ];
        }

        $shown = [
            'a display attribute other than none' => 'display="inline"',
            'a style attribute with another display' => 'style="display:nonesuch"',
            'a style attribute without display' => 'style="fill:none"',
            'a custom property named like display' => 'style="--display:none"',
            'a class without a style sheet that hides' => 'class="h"',
        ];

        foreach ($shown as $name => $attribute) {
            yield 'Removes the ids inside a group with ' . $name => [
                self::SVG . '<style>.h{fill:red}</style><g ' . $attribute . '><g id="a"/><rect/></g></svg>',
                self::SVG . '<style>.h{fill:red}</style><g ' . $attribute . '><g/><rect/></g></svg>',
            ];
        }

        yield 'Keeps the ids inside a group with a class when a style sheet hides through a class' => [
            self::SVG . '<style>.h{display:none}</style><g class="h"><g id="a"/></g><rect/></svg>',
            self::SVG . '<style>.h{display:none}</style><g class="h"><g id="a"/></g><rect/></svg>',
        ];

        yield 'Removes the ids inside a group without a class when a style sheet hides through a class' => [
            self::SVG . '<style>.h{display:none}</style><g><g id="a"/><rect/></g></svg>',
            self::SVG . '<style>.h{display:none}</style><g><g/><rect/></g></svg>',
        ];
        yield 'Removes the ids inside a group whose class no style sheet hides' => [
            self::SVG . '<style>.h{display:none}.f{fill:red}</style><g class="f"><g id="a"/><rect/></g></svg>',
            self::SVG . '<style>.h{display:none}.f{fill:red}</style><g class="f"><g/><rect/></g></svg>',
        ];

        yield 'Removes the ids inside a group whose class a style sheet without hiding rules styles' => [
            self::SVG . '<style>.f{fill:red}</style><style>.h{display:none}</style><g class="f"><g id="a"/><rect/></g></svg>',
            self::SVG . '<style>.f{fill:red}</style><style>.h{display:none}</style><g class="f"><g/><rect/></g></svg>',
        ];

        yield 'Keeps the ids inside a group with a hidden class among its classes' => [
            self::SVG . '<style>.h{display:none}.f{fill:red}</style><g class="f h"><g id="a"/><rect/></g></svg>',
            self::SVG . '<style>.h{display:none}.f{fill:red}</style><g class="f h"><g id="a"/><rect/></g></svg>',
        ];

        yield 'Keeps the ids inside a group with a class from a hiding selector list' => [
            self::SVG . '<style>.h,.k{display:none}</style><g class="k"><g id="a"/><rect/></g></svg>',
            self::SVG . '<style>.h,.k{display:none}</style><g class="k"><g id="a"/><rect/></g></svg>',
        ];

        yield 'Keeps the ids inside a group with any class when a hiding rule selects more than classes' => [
            self::SVG . '<style>rect{display:none}.f{fill:red}</style><g class="f"><g id="a"/><rect/></g></svg>',
            self::SVG . '<style>rect{display:none}.f{fill:red}</style><g class="f"><g id="a"/><rect/></g></svg>',
        ];

        yield 'Keeps the ids inside a group with any class when a hiding style sheet is not a plain rule list' => [
            self::SVG . '<style>/* c */.h{display:none}</style><g class="f"><g id="a"/><rect/></g></svg>',
            self::SVG . '<style>/* c */.h{display:none}</style><g class="f"><g id="a"/><rect/></g></svg>',
        ];

        yield 'Removes empty ids of symbols and other elements' => [
            self::SVG . '<symbol id=""/><rect id=""/></svg>',
            self::SVG . '<symbol/><rect/></svg>',
        ];

        yield 'Keeps the id of a hidden nested svg element and of the groups inside it' => [
            self::SVG . '<svg id="s" display="none"><g id="a"/></svg><rect/></svg>',
            self::SVG . '<svg id="s" display="none"><g id="a"/></svg><rect/></svg>',
        ];

        foreach (['a style element' => '<style>g:target{fill:red}</style>', 'an uppercase pseudo-class' => '<style>G:TARGET{fill:red}</style>'] as $name => $style) {
            yield 'Keeps every id when the target pseudo-class is used in ' . $name => [
                self::SVG . $style . '<rect id="a"/><g><path xml:id="b" d="M0 0"/></g></svg>',
                self::SVG . $style . '<rect id="a"/><g><path xml:id="b" d="M0 0"/></g></svg>',
            ];
        }

        yield 'Keeps every id when an external style sheet is attached' => [
            '<?xml-stylesheet href="s.css"?>' . self::SVG . '<rect id="a"/></svg>',
            '<?xml-stylesheet href="s.css"?>' . self::SVG . '<rect id="a"/></svg>',
        ];

        yield 'Removes ids when a style sheet uses another pseudo-class' => [
            self::SVG . '<style>g:hover{fill:red}</style><rect id="a"/></svg>',
            self::SVG . '<style>g:hover{fill:red}</style><rect/></svg>',
        ];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    private static function provideResourceDocumentCases(): iterable
    {
        yield 'Keeps every top-level id of a document that renders nothing' => [
            self::SVG . '<linearGradient id="a"/><mask id="b"><rect id="c"/></mask><defs><filter id="d"/></defs></svg>',
            self::SVG . '<linearGradient id="a"/><mask id="b"><rect/></mask><defs><filter id="d"/></defs></svg>',
        ];

        $roots = [
            'a hidden root' => '<svg xmlns="http://www.w3.org/2000/svg" style="display:none">',
            'a root without width' => '<svg xmlns="http://www.w3.org/2000/svg" width="0">',
            'a root without height' => '<svg xmlns="http://www.w3.org/2000/svg" height="0.0px">',
            'a root with a negative zero width' => '<svg xmlns="http://www.w3.org/2000/svg" width="-.0">',
        ];

        foreach ($roots as $name => $root) {
            yield 'Keeps the ids of rendered content in a document with ' . $name => [
                $root . '<path id="a" d="M0 0"/><linearGradient id="b"/></svg>',
                $root . '<path id="a" d="M0 0"/><linearGradient id="b"/></svg>',
            ];
        }

        foreach (['a sized root' => '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="0.5">', 'a root with a percentage' => '<svg xmlns="http://www.w3.org/2000/svg" width="100%">'] as $name => $root) {
            yield 'Removes the ids of rendered content in a document with ' . $name => [
                $root . '<path id="a" d="M0 0"/><linearGradient id="b"/></svg>',
                $root . '<path d="M0 0"/><linearGradient/></svg>',
            ];
        }

        yield 'Keeps the top-level ids when the only rendered element is hidden' => [
            self::SVG . '<path id="a" display="none" d="M0 0"/><linearGradient id="b"/></svg>',
            self::SVG . '<path id="a" display="none" d="M0 0"/><linearGradient id="b"/></svg>',
        ];

        yield 'Keeps the top-level ids when the only rendered element is in a foreign namespace' => [
            self::SVG . '<x:rect xmlns:x="urn:x" id="a"/><linearGradient id="b"/></svg>',
            self::SVG . '<x:rect xmlns:x="urn:x"/><linearGradient id="b"/></svg>',
        ];

        yield 'Keeps the top-level ids when the groups render nothing' => [
            self::SVG . '<g><g><linearGradient/></g></g><linearGradient id="b"/></svg>',
            self::SVG . '<g><g><linearGradient/></g></g><linearGradient id="b"/></svg>',
        ];

        yield 'Removes the ids when a nested group renders something' => [
            self::SVG . '<g><title/><g><circle/></g></g><linearGradient id="b"/></svg>',
            self::SVG . '<g><title/><g><circle/></g></g><linearGradient/></svg>',
        ];

        yield 'Removes the ids when a later child renders something' => [
            self::SVG . '<linearGradient id="b"/><g display="none"><rect/></g><text>x</text></svg>',
            self::SVG . '<linearGradient/><g display="none"><rect/></g><text>x</text></svg>',
        ];
    }

    #[Test]
    public function leavesADocumentWithoutRootElementUnchanged(): void
    {
        $domDocument = new \DOMDocument();

        (new RemoveUnusedIds())->apply($domDocument);

        self::assertNull($domDocument->documentElement);
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
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg" ' . implode(' ', array_map(static fn (int $i): string => 'xmlns:n' . $i . '="urn:' . $i . '"', range(1, $count))) . '><defs><rect id="r"/></defs><use href="#r"/><rect id="s"/></svg>');

        $start = hrtime(true);
        (new RemoveUnusedIds())->apply($domDocument);

        return (hrtime(true) - $start) / 1e9;
    }
}
