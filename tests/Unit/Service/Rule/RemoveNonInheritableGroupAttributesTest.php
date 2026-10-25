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
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveNonInheritableGroupAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveNonInheritableGroupAttributes::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class RemoveNonInheritableGroupAttributesTest extends TestCase
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
        self::assertSame($expected, $this->optimizeContent($expected));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Removes geometry attributes from a group' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g x="1" y="2" width="3" height="4" cx="5" cy="6" r="7" rx="8" ry="9" d="M0 0"><rect width="1" height="1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect width="1" height="1"/></g></svg>',
        ];

        yield 'Keeps a group attribute that the inline style also sets' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g x="1" y="2" style="x:3"><rect width="1" height="1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g x="1" style="x:3"><rect width="1" height="1"/></g></svg>',
        ];

        yield 'Removes attributes that only apply to other elements from a group' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g stop-color="red" stop-opacity=".5" flood-color="red" flood-opacity=".5" lighting-color="red" mask-type="alpha" vector-effect="non-scaling-stroke" alignment-baseline="middle" baseline-shift="super"><path d="M0 0h1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><path d="M0 0h1"/></g></svg>',
        ];

        yield 'Keeps attributes that are inherited or apply to the group' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red" stroke="blue" opacity=".5" clip-path="url(#c)" mask="url(#m)" filter="url(#f)" transform="scale(2)" transform-origin="1 1" dominant-baseline="middle" text-decoration="underline" unicode-bidi="embed" overflow="hidden" clip="auto" display="inline" id="a" class="b"><path d="M0 0h1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red" stroke="blue" opacity=".5" clip-path="url(#c)" mask="url(#m)" filter="url(#f)" transform="scale(2)" transform-origin="1 1" dominant-baseline="middle" text-decoration="underline" unicode-bidi="embed" overflow="hidden" clip="auto" display="inline" id="a" class="b"><path d="M0 0h1"/></g></svg>',
        ];

        yield 'Keeps the attributes on elements other than groups' => [
            '<svg xmlns="http://www.w3.org/2000/svg" x="1" width="10"><rect x="1" y="2" width="3" height="4" vector-effect="non-scaling-stroke"/><linearGradient id="l"><stop stop-color="red"/></linearGradient><filter id="f"><feFlood flood-color="red"/></filter><mask id="m" mask-type="alpha"/><text><tspan baseline-shift="super">a</tspan></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" x="1" width="10"><rect x="1" y="2" width="3" height="4" vector-effect="non-scaling-stroke"/><linearGradient id="l"><stop stop-color="red"/></linearGradient><filter id="f"><feFlood flood-color="red"/></filter><mask id="m" mask-type="alpha"/><text><tspan baseline-shift="super">a</tspan></text></svg>',
        ];

        yield 'Removes the attributes from nested groups' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g x="1"><g y="2"><g width="3" fill="red"><rect width="1" height="1"/></g></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g fill="red"><rect width="1" height="1"/></g></g></g></svg>',
        ];

        yield 'Removes the attributes from an empty group' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g x="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g/></svg>',
        ];

        yield 'Removes the attributes from prefixed groups' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:g x="1" fill="red"><svg:rect width="1" height="1"/></svg:g></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:g fill="red"><svg:rect width="1" height="1"/></svg:g></svg:svg>',
        ];

        yield 'Removes the attributes in documents without a namespace' => [
            '<svg><g x="1"><rect width="1" height="1"/></g></svg>',
            '<svg><g><rect width="1" height="1"/></g></svg>',
        ];

        yield 'Removes padded and uppercase values' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g stop-color=" RED " width=" 10PX "><rect width="1" height="1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect width="1" height="1"/></g></svg>',
        ];

        yield 'Keeps namespaced attributes with the same local name' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:c="http://www.customnamespace.com"><g c:x="1" c:width="2"><rect width="1" height="1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:c="http://www.customnamespace.com"><g c:x="1" c:width="2"><rect width="1" height="1"/></g></svg>',
        ];

        yield 'Keeps attributes of a group whose descendant inherits a value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g stop-color="red"><linearGradient id="l" stop-color="inherit"><stop stop-color="inherit"/></linearGradient></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g stop-color="red"><linearGradient id="l" stop-color="inherit"><stop stop-color="inherit"/></linearGradient></g></svg>',
        ];

        yield 'Keeps attributes of a group whose descendant inherits a value in its style' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g x="1"><g><rect style="X: INHERIT" width="1" height="1"/></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g x="1"><g><rect style="X: INHERIT" width="1" height="1"/></g></g></svg>',
        ];

        yield 'Keeps attributes of a group whose later descendant inherits a value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g x="1"><rect width="1" height="1"/><rect x="inherit" width="1" height="1"/></g><g x="2"><rect width="1" height="1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g x="1"><rect width="1" height="1"/><rect x="inherit" width="1" height="1"/></g><g><rect width="1" height="1"/></g></svg>',
        ];

        yield 'Removes attributes of a group that uses inherit itself' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g x="1" fill="inherit"><rect width="1" height="1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="inherit"><rect width="1" height="1"/></g></svg>',
        ];

        yield 'Keeps attributes of a group whose animation uses inherit' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g stop-color="red"><rect width="1" height="1"><animate attributeName="fill" values="red;inherit" dur="1s"/></rect></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g stop-color="red"><rect width="1" height="1"><animate attributeName="fill" values="red;inherit" dur="1s"/></rect></g></svg>',
        ];

        yield 'Keeps animated attributes and removes the others' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g x="1" y="2"><set attributeName=" x " to="2"/><rect width="1" height="1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g x="1"><set attributeName=" x " to="2"/><rect width="1" height="1"/></g></svg>',
        ];

        yield 'Keeps attributes animated from elsewhere by reference' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="a" width="1" height="2"><rect width="1" height="1"/></g><animate xlink:href="#a" attributeName="width" to="5" dur="1s"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="a" width="1"><rect width="1" height="1"/></g><animate xlink:href="#a" attributeName="width" to="5" dur="1s"/></svg>',
        ];

        yield 'Keeps everything when a style element is present' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>g[x]{fill:red}</style><g x="1"><rect width="1" height="1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>g[x]{fill:red}</style><g x="1"><rect width="1" height="1"/></g></svg>',
        ];

        yield 'Keeps everything when an external style sheet is referenced' => [
            '<?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"><g x="1"><rect width="1" height="1"/></g></svg>',
            '<?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"><g x="1"><rect width="1" height="1"/></g></svg>',
        ];

        yield 'Removes the attributes when other processing instructions are present' => [
            '<?other data?><svg xmlns="http://www.w3.org/2000/svg"><g x="1"><rect width="1" height="1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect width="1" height="1"/></g></svg>',
        ];

        yield 'Keeps groups in foreign content' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject width="1" height="1"><svg xmlns="http://www.w3.org/2000/svg"><g x="1"><rect width="1" height="1"/></g></svg></foreignObject><g x="2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject width="1" height="1"><svg xmlns="http://www.w3.org/2000/svg"><g x="1"><rect width="1" height="1"/></g></svg></foreignObject><g/></svg>',
        ];

        yield 'Keeps g elements in other namespaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:c="http://www.customnamespace.com"><c:g x="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:c="http://www.customnamespace.com"><c:g x="1"/></svg>',
        ];

        yield 'Keeps documents without groups untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    private function optimizeContent(string $content): string
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($content));
        $svgOptimizer->addPass(new RemoveNonInheritableGroupAttributes());

        return $svgOptimizer->optimize()->getContent();
    }
}
