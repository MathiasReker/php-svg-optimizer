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
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedMasks;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveUnusedMasks::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class RemoveUnusedMasksTest extends TestCase
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
        $svgOptimizer = new SvgOptimizer(new StringProvider($content));
        $svgOptimizer->addRule(new RemoveUnusedMasks());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Keeps the masks of a resource library, which other documents may reference' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m"><rect/></mask></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m"><rect/></mask></defs></svg>',
        ];

        yield 'Leaves the document untouched when a style sheet depends on siblings' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect:nth-child(3){fill:red}</style><mask id="m"/><rect/><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect:nth-child(3){fill:red}</style><mask id="m"/><rect/><rect/></svg>',
        ];

        yield 'Leaves the document untouched with an external style sheet' => [
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg"><mask id="m"/></svg>',
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg"><mask id="m"/></svg>',
        ];

        yield 'Removes unused mask element' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <mask id="m1"><rect width="100" height="100"/></mask>
                    </defs>
                    <rect width="100" height="100"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100"/></svg>
                XML,
        ];

        yield 'Keeps mask if referenced' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs><mask id="m1"><circle r="50"/></mask></defs>
                    <rect mask="url(#m1)" width="100" height="100"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m1"><circle r="50"/></mask></defs><rect mask="url(#m1)" width="100" height="100"/></svg>
                XML,
        ];

        yield 'Keeps a mask referenced before a style element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m1"><circle r="50"/></mask></defs><rect mask="url(#m1)"/><style>.a{fill:red}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m1"><circle r="50"/></mask></defs><rect mask="url(#m1)"/><style>.a{fill:red}</style></svg>',
        ];

        yield 'Removes empty defs after deleting unused mask' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs><mask id="unused"></mask></defs>
                    <rect width="1" height="1"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1"/></svg>
                XML,
        ];

        yield 'Keeps multiple masks when all are used' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <mask id="m1"><circle r="50"/></mask>
                        <mask id="m2"><rect width="50" height="50"/></mask>
                    </defs>
                    <rect mask="url(#m1)" width="100" height="100"/>
                    <circle mask="url(#m2)" r="40"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m1"><circle r="50"/></mask><mask id="m2"><rect width="50" height="50"/></mask></defs><rect mask="url(#m1)" width="100" height="100"/><circle mask="url(#m2)" r="40"/></svg>
                XML,
        ];

        yield 'Removes only unused mask when others are referenced' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <mask id="used"><rect width="50" height="50"/></mask>
                        <mask id="unused"><circle r="10"/></mask>
                    </defs>
                    <rect mask="url(#used)" width="100" height="100"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="used"><rect width="50" height="50"/></mask></defs><rect mask="url(#used)" width="100" height="100"/></svg>
                XML,
        ];

        yield 'Handles multiple defs sections and removes only unused masks' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs><mask id="m1"><rect width="100" height="100"/></mask></defs>
                    <defs><mask id="m2"><circle r="20"/></mask></defs>
                    <rect mask="url(#m2)" width="50" height="50"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m2"><circle r="20"/></mask></defs><rect mask="url(#m2)" width="50" height="50"/></svg>
                XML,
        ];

        yield 'Keeps mask referenced inside a group' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs><mask id="groupMask"><rect width="10" height="10"/></mask></defs>
                    <g mask="url(#groupMask)">
                        <rect width="100" height="100"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="groupMask"><rect width="10" height="10"/></mask></defs><g mask="url(#groupMask)"><rect width="100" height="100"/></g></svg>
                XML,
        ];

        yield 'Removes unused masks with whitespace or comments around them' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <!-- comment -->
                        <mask id="unused"><rect/></mask>
                    </defs>
                    <rect width="1" height="1"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1"/></svg>
                XML,
        ];

        yield 'Ignores non-mask elements inside defs' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <clipPath id="clip1"><rect/></clipPath>
                        <mask id="m1"><rect/></mask>
                    </defs>
                    <rect clip-path="url(#clip1)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><clipPath id="clip1"><rect/></clipPath></defs><rect clip-path="url(#clip1)"/></svg>
                XML,
        ];

        yield 'Handles nested mask references (mask references another mask)' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <mask id="m1" mask="url(#m2)"><rect/></mask>
                        <mask id="m2"><circle/></mask>
                    </defs>
                    <rect mask="url(#m1)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m1" mask="url(#m2)"><rect/></mask><mask id="m2"><circle/></mask></defs><rect mask="url(#m1)"/></svg>
                XML,
        ];

        yield 'Handles invalid mask references gracefully (no removal crash)' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs><mask id="m1"><rect/></mask></defs>
                    <rect mask="url(#nonexistent)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect mask="url(#nonexistent)"/></svg>
                XML,
        ];

        yield 'Handles masks without id attribute safely' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs><mask><rect/></mask></defs>
                    <rect width="100" height="100"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100"/></svg>
                XML,
        ];

        yield 'Handles multiple mask attributes in different namespaces' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <defs><mask id="m1"><rect/></mask></defs>
                    <use xlink:href="#shape" mask="url(#m1)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><mask id="m1"><rect/></mask></defs><use xlink:href="#shape" mask="url(#m1)"/></svg>
                XML,
        ];

        yield 'Removes multiple unused masks and cleans up defs' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <mask id="a"><rect/></mask>
                        <mask id="b"><rect/></mask>
                    </defs>
                    <rect width="1" height="1"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1"/></svg>
                XML,
        ];

        yield 'Keeps mask with similar id substring (no partial matches)' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <mask id="mask1"><rect/></mask>
                        <mask id="mask10"><circle/></mask>
                    </defs>
                    <rect mask="url(#mask1)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="mask1"><rect/></mask></defs><rect mask="url(#mask1)"/></svg>
                XML,
        ];

        yield 'Removes unused mask but keeps other defs content' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <mask id="unused"><rect/></mask>
                        <clipPath id="clip1"><circle/></clipPath>
                    </defs>
                    <rect clip-path="url(#clip1)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><clipPath id="clip1"><circle/></clipPath></defs><rect clip-path="url(#clip1)"/></svg>
                XML,
        ];

        yield 'Handles defs containing mixed mask and gradient' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <mask id="m1"><rect/></mask>
                        <linearGradient id="grad1"><stop offset="0%"/></linearGradient>
                    </defs>
                    <rect fill="url(#grad1)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="grad1"><stop offset="0%"/></linearGradient></defs><rect fill="url(#grad1)"/></svg>
                XML,
        ];

        yield 'Removes mask with no ID' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs><mask><rect/></mask></defs>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Removes mask with empty ID' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs><mask id=""><rect/></mask></defs>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Keeps mask referenced from a style attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m"><rect/></mask></defs><rect style="mask:url(#m)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m"><rect/></mask></defs><rect style="mask:url(#m)"/></svg>',
        ];

        yield 'Keeps mask referenced from a style element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.x{mask:url(#m)}</style><defs><mask id="m"><rect/></mask></defs><rect class="x"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.x{mask:url(#m)}</style><defs><mask id="m"><rect/></mask></defs><rect class="x"/></svg>',
        ];

        yield 'Keeps mask referenced from a CDATA style element with quoted url' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style><![CDATA[.x{mask:url( "#m" )}]]></style><defs><mask id="m"><rect/></mask></defs><rect class="x"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.x{mask:url( "#m" )}</style><defs><mask id="m"><rect/></mask></defs><rect class="x"/></svg>',
        ];

        yield 'Keeps mask referenced by xlink:href' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><mask id="m"><rect/></mask><mask id="n" xlink:href="#m"/></defs><rect mask="url(#n)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><mask id="m"><rect/></mask><mask id="n" xlink:href="#m"/></defs><rect mask="url(#n)"/></svg>',
        ];

        yield 'Keeps mask referenced by href' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m"><rect/></mask></defs><use href="#m"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m"><rect/></mask></defs><use href="#m"/></svg>',
        ];

        yield 'Keeps mask referenced with a quoted url in an attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m"><rect/></mask></defs><rect mask="url(\'#m\')"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m"><rect/></mask></defs><rect mask="url(\'#m\')"/></svg>',
        ];

        yield 'Removes a mask whose reference only appears in text content' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m"><rect/></mask></defs><text>url(#m)</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>url(#m)</text></svg>',
        ];

        yield 'Removes mask whose id only appears as a prefix of a style reference' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.x{mask:url(#m1)}</style><defs><mask id="m"><rect/></mask></defs><rect width="1" height="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.x{mask:url(#m1)}</style><rect width="1" height="1"/></svg>',
        ];

        yield 'Keeps masks in a document with a script, which may apply them' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="m"/><rect id="r"/><script>document.getElementById("r").setAttribute("mask","url(#m)")</script></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="m"/><rect id="r"/><script>document.getElementById("r").setAttribute("mask","url(#m)")</script></svg>',
        ];

        yield 'Keeps masks in a document with an event attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="m"/><rect ONCLICK="void 0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="m"/><rect ONCLICK="void 0"/></svg>',
        ];

        yield 'Keeps a mask referenced through its xml:id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><mask xml:id="m"/><mask xml:id="n"/><rect mask="url(#m)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><mask xml:id="m"/><rect mask="url(#m)"/></svg>',
        ];

        yield 'Keeps mask elements of other namespaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><mask xmlns="urn:x"/><mask/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><mask xmlns="urn:x"/></svg>',
        ];

        yield 'Keeps a mask referenced through an escaped url()' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{mask:url(#\6d)}</style><mask id="m"/><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{mask:url(#\6d)}</style><mask id="m"/><rect/></svg>',
        ];

        yield 'Removes unused masks of a prefixed SVG document' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:defs><svg:mask id="m"/></svg:defs><svg:rect mask="url(#m)"/><svg:mask id="n"/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:defs><svg:mask id="m"/></svg:defs><svg:rect mask="url(#m)"/></svg:svg>',
        ];

        yield 'Keeps a mask referenced by an animation' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="m"/><rect><set attributeName="mask" to="url(#m)"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="m"/><rect><set attributeName="mask" to="url(#m)"/></rect></svg>',
        ];

        yield 'Keeps an unused mask holding a style sheet, which applies to the whole document' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="m"><style>rect{fill:green}</style></mask><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="m"><style>rect{fill:green}</style></mask><rect/></svg>',
        ];

        yield 'Keeps an unused mask holding an animation of another element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><mask><g><set href="#r" attributeName="fill" to="green"/></g></mask><rect id="r"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><mask><g><set href="#r" attributeName="fill" to="green"/></g></mask><rect id="r"/></svg>',
        ];

        yield 'Removes a later unused mask after keeping an earlier one' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="a"/><mask id="b"/><rect mask="url(\'#a\')"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="a"/><rect mask="url(\'#a\')"/></svg>',
        ];

        yield 'Keeps masks referenced through spaced and quoted url() forms' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="a"/><mask id="b"/><mask id="c"/><rect mask="url( &quot;#a&quot; )"/><rect style="mask:url( \'#b\' )"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="a"/><mask id="b"/><rect mask="url( &quot;#a&quot; )"/><rect style="mask:url( \'#b\' )"/></svg>',
        ];

        yield 'Keeps a mask with a unicode id that is referenced' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="ü"/><mask id="ö"/><rect mask="url(#ü)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="ü"/><rect mask="url(#ü)"/></svg>',
        ];

        yield 'Keeps a mask whose id is written with a character reference' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="&#109;"/><rect mask="url(#m)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="m"/><rect mask="url(#m)"/></svg>',
        ];

        yield 'Keeps every mask with a duplicated id that is referenced' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="m"/><mask id="m"/><rect mask="url(#m)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="m"/><mask id="m"/><rect mask="url(#m)"/></svg>',
        ];

        yield 'Keeps a mask referenced from a style element inside a media block' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@media print{rect{mask:url(#m)}}</style><mask id="m"/><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@media print{rect{mask:url(#m)}}</style><mask id="m"/><rect/></svg>',
        ];

        yield 'Removes a deeply nested unused mask' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g><mask id="m"/></g></g></g><rect width="1" height="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g/></g></g><rect width="1" height="1"/></svg>',
        ];

        yield 'Removes unused masks in a document with redeclared namespace prefixes' => [
            '<s:svg xmlns:s="http://www.w3.org/2000/svg"><t:mask xmlns:t="http://www.w3.org/2000/svg" id="m"/><s:rect width="1" height="1"/></s:svg>',
            '<s:svg xmlns:s="http://www.w3.org/2000/svg"><s:rect width="1" height="1"/></s:svg>',
        ];

        yield 'Leaves an empty document unchanged' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Removes defs with only comments' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs><!-- comment --></defs>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];
    }

    #[Test]
    public function ruleIsMarkedAsNotRisky(): void
    {
        self::assertFalse(RemoveUnusedMasks::isRisky());
    }

    #[Test]
    public function shouldCheckSizeReturnsFalse(): void
    {
        self::assertFalse(RemoveUnusedMasks::shouldCheckSize());
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
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg" ' . implode(' ', array_map(static fn (int $i): string => 'xmlns:n' . $i . '="urn:' . $i . '"', range(1, $count))) . '><mask id="m"/><rect/></svg>');

        $start = hrtime(true);
        (new RemoveUnusedMasks())->optimize($domDocument);

        return (hrtime(true) - $start) / 1e9;
    }
}
