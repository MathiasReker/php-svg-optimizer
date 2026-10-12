<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Exception\SvgValidationException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Model\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Formatter\XmlFormatter;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedIds;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveUnusedIds::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(XmlFormatter::class)]
final class RemoveUnusedIdsTest extends TestCase
{
    private const string SVG = '<svg xmlns="http://www.w3.org/2000/svg">';

    private const string SVG_XLINK = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">';

    /**
     * @throws SvgValidationException
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     */
    #[DataProvider('provideOptimizeCases')]
    #[Test]
    public function optimize(string $content, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($content));
        $svgOptimizer->allowRisky();
        $svgOptimizer->addRule(new RemoveUnusedIds());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield from self::provideRemovedCases();
        yield from self::provideAttributeReferenceCases();
        yield from self::provideStyleReferenceCases();
        yield from self::provideKeptElementCases();
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
            self::SVG . '<g id="a"><g id="b"><path id="c" d="M0 0"/></g></g></svg>',
            self::SVG . '<g><g><path d="M0 0"/></g></g></svg>',
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

        yield 'Follows references through a chain of definitions' => [
            self::SVG . '<defs><g id="a"><use href="#b"/></g><g id="b"><use href="#c"/></g><rect id="c"/></defs><use href="#a"/></svg>',
            self::SVG . '<defs><g id="a"><use href="#b"/></g><g id="b"><use href="#c"/></g><rect id="c"/></defs><use href="#a"/></svg>',
        ];

        yield 'Follows references through a cycle of definitions' => [
            self::SVG . '<defs><g id="a"><use href="#b"/></g><g id="b"><use href="#a"/></g><rect id="c"/></defs><use href="#a"/></svg>',
            self::SVG . '<defs><g id="a"><use href="#b"/></g><g id="b"><use href="#a"/></g><rect/></defs><use href="#a"/></svg>',
        ];

        yield 'Follows references from the first of two elements with the same id' => [
            self::SVG . '<defs><g id="a"><use href="#b"/></g><g id="a"><use href="#c"/></g></defs><rect id="b"/><rect id="c"/><use href="#a"/></svg>',
            self::SVG . '<defs><g id="a"><use href="#b"/></g><g id="a"><use href="#c"/></g></defs><rect id="b"/><rect/><use href="#a"/></svg>',
        ];

        yield 'Keeps an id referenced by a percent-encoded fragment' => [
            self::SVG . '<path id="人口" d="M0 0h9v9z"/><use href="#%E4%BA%BA%E5%8F%A3"/></svg>',
            self::SVG . '<path id="&#x4EBA;&#x53E3;" d="M0 0h9v9z"/><use href="#%E4%BA%BA%E5%8F%A3"/></svg>',
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
            self::SVG . '<linearGradient id="a" href="#a"/></svg>',
            self::SVG . '<linearGradient href="#a"/></svg>',
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
            self::SVG . '<defs><linearGradient id="g"/><g id="icon"><rect fill="url(#g)"/></g></defs><rect/></svg>',
            self::SVG . '<defs><linearGradient/><g><rect fill="url(#g)"/></g></defs><rect/></svg>',
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

        yield 'Removes an id that a style element does not reference' => [
            self::SVG . '<style>#ab{fill:red} .a{fill:red}</style><rect id="a"/></svg>',
            self::SVG . '<style>#ab{fill:red} .a{fill:red}</style><rect/></svg>',
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

    #[Test]
    public function isRiskyReturnsTrue(): void
    {
        self::assertTrue(RemoveUnusedIds::isRisky());
    }

    #[Test]
    public function shouldCheckSizeReturnsFalse(): void
    {
        self::assertFalse(RemoveUnusedIds::shouldCheckSize());
    }
}
