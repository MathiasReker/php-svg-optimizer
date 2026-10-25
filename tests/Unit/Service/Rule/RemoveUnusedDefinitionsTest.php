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
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Service\Optimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedDefinitions;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveUnusedDefinitions::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class RemoveUnusedDefinitionsTest extends TestCase
{
    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[DataProvider('provideOptimizeCases')]
    #[Test]
    public function optimize(string $content, string $expected): void
    {
        self::assertSame($expected, $this->optimizeContent($content));
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[DataProvider('provideOptimizeCases')]
    #[Test]
    public function optimizeIsIdempotent(string $content, string $expected): void
    {
        self::assertSame($expected, $this->optimizeContent($this->optimizeContent($content)));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
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
    }

    #[Test]
    public function ruleIsMarkedAsRisky(): void
    {
        self::assertTrue(RemoveUnusedDefinitions::isRisky());
    }

    #[Test]
    public function shouldCheckSizeReturnsFalse(): void
    {
        self::assertFalse(RemoveUnusedDefinitions::shouldCheckSize());
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    private function optimizeContent(string $content): string
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($content));
        $svgOptimizer->allowRisky();
        $svgOptimizer->addRule(new RemoveUnusedDefinitions());

        return $svgOptimizer->optimize()->getContent();
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
        (new RemoveUnusedDefinitions())->optimize($domDocument);

        return (hrtime(true) - $start) / 1e9;
    }
}
