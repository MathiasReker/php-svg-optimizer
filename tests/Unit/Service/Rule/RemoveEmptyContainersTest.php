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
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyContainers;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveEmptyContainers::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class RemoveEmptyContainersTest extends TestCase
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
        $svgOptimizer->addRule(new RemoveEmptyContainers());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);

        $repeated = new SvgOptimizer(new StringProvider($actual));
        $repeated->addRule(new RemoveEmptyContainers());
        self::assertSame($actual, $repeated->optimize()->getContent());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Keeps an empty group exposed to assistive technology' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g role="img" aria-label="Chart"/><g/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g role="img" aria-label="Chart"/></svg>',
        ];

        yield 'Keeps an empty group that can take focus' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g tabindex="0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g tabindex="0"/></svg>',
        ];

        yield 'Keeps an empty group with an event handler' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g onclick="f()"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g onclick="f()"/></svg>',
        ];

        yield 'Removes an empty group with a namespaced role' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><g x:role="img"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"/>',
        ];

        yield 'Keeps an empty group that a style sheet may filter' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{filter:url(#f)}</style><filter id="f" filterUnits="userSpaceOnUse" width="9" height="9"><feFlood/></filter><g class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{filter:url(#f)}</style><filter id="f" filterUnits="userSpaceOnUse" width="9" height="9"><feFlood/></filter><g class="a"/></svg>',
        ];

        yield 'leaves an empty document alone' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'removes containers that only hold tabs and newlines' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs>&#9;&#10;&#13;</defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'keeps a container with a non-breaking space' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs>&#xA0;</defs></svg>',
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><defs>\u{A0}</defs></svg>",
        ];

        yield 'removes mixed-case container elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><DEFS/><clippath/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'removes deeply nested empty containers' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><g><symbol><g><mask/></g></symbol></g></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'keeps empty containers in another namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><x:defs xmlns:x="urn:x"/><metadata><g xmlns="urn:y"/></metadata></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><x:defs xmlns:x="urn:x"/><metadata><g xmlns="urn:y"/></metadata></svg>',
        ];

        yield 'removes empty prefixed svg containers' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:defs/><svg:g fill="red"/><svg:rect width="5" height="5"/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:rect width="5" height="5"/></svg:svg>',
        ];

        yield 'keeps empty containers when an external style sheet is linked' => [
            '<?xml-stylesheet href="style.css"?><svg xmlns="http://www.w3.org/2000/svg"><g fill="red"/></svg>',
            '<?xml-stylesheet href="style.css"?><svg xmlns="http://www.w3.org/2000/svg"><g fill="red"/></svg>',
        ];

        yield 'keeps an empty group with a filter in an uppercase style' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="FILTER:url(#f)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="FILTER:url(#f)"/></svg>',
        ];

        yield 'keeps an empty container with an xml:id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g xml:id="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g xml:id="a"/></svg>',
        ];

        yield 'removes a later empty container after a kept one' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g id="a"/><g fill="red"/><mask/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g id="a"/></svg>',
        ];

        yield 'Keeps empty containers when a sibling combinator may match' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>defs~rect{fill:red}</style><defs/><rect width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>defs~rect{fill:red}</style><defs/><rect width="5" height="5"/></svg>',
        ];

        yield 'Keeps empty containers when a relational pseudo-class may match' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>svg:has(g) rect{fill:red}</style><g/><rect width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>svg:has(g) rect{fill:red}</style><g/><rect width="5" height="5"/></svg>',
        ];

        yield 'Removes empty containers when the style sheet does not depend on siblings' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><defs/><rect width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><rect width="5" height="5"/></svg>',
        ];

        yield 'Keeps empty group with a filter in its style' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="filter:url(#f)"/><g style="FILTER: url(#f)"/><g style="fill:red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="filter:url(#f)"/><g style="FILTER: url(#f)"/></svg>',
        ];

        yield 'Keeps empty children of switch' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><switch><g/><text>fallback</text></switch><g/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><switch><g/><text>fallback</text></switch></svg>',
        ];

        yield 'Removes empty containers' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs/><symbol/><clipPath/><mask> </mask><pattern/><marker/><switch/><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes containers emptied by the removal' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><g><defs/></g></g><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Keeps containers with an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><clipPath id="c"/><g id="g"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><clipPath id="c"/><g id="g"/></svg>',
        ];

        yield 'Keeps group with a filter' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g filter="url(#f)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g filter="url(#f)"/></svg>',
        ];

        yield 'Keeps containers with content' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect/></g><switch>text</switch></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect/></g><switch>text</switch></svg>',
        ];

        yield 'Keeps other empty elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a/><svg/><text/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a/><svg/><text/></svg>',
        ];

        yield 'Keeps containers with an xml:id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><symbol xml:id="s"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><symbol xml:id="s"/></svg>',
        ];

        yield 'Removes containers with only comments' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><!--c--></g><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Keeps containers whose descendants are kept' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g id="a"/><g/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g id="a"/></g></svg>',
        ];

        yield 'Keeps the root element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];
    }

    #[Test]
    public function isRiskyReturnsFalse(): void
    {
        self::assertFalse(RemoveEmptyContainers::isRisky());
    }

    #[Test]
    public function shouldCheckSizeReturnsFalse(): void
    {
        self::assertFalse(RemoveEmptyContainers::shouldCheckSize());
    }
}
