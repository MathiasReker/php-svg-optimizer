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
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEmptyContainers;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AnimationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementNameTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
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
#[CoversClass(RemoveEmptyContainers::class)]
#[CoversTrait(AnimationTrait::class)]
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(ElementNameTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(IdTrait::class)]
#[CoversTrait(ScriptingTrait::class)]
#[CoversTrait(StyleSheetTrait::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(Parser::class)]
#[UsesClass(Pipeline::class)]
#[UsesClass(Serializer::class)]
#[UsesClass(SvgValidator::class)]
final class RemoveEmptyContainersTest extends TestCase
{
    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideRemovesEmptyContainersCases')]
    #[Test]
    public function removesEmptyContainers(string $input, string $expected): void
    {
        $pipeline = new Pipeline([new RemoveEmptyContainers()]);

        $actual = $pipeline->run($input);
        self::assertSame($expected, $actual);

        $repeated = new Pipeline([new RemoveEmptyContainers()]);
        self::assertSame($actual, $repeated->run($actual));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideRemovesEmptyContainersCases(): iterable
    {
        yield 'Keeps empty containers in a document with a script, which may fill or query them' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><script>a()</script><g/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>a()</script><g/></svg>',
        ];

        yield 'Keeps empty containers in a document with an event handler on another element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g/><rect width="5" height="5" onclick="a()"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g/><rect width="5" height="5" onclick="a()"/></svg>',
        ];
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

        yield 'Groups: keeps empty groups when a style sheet may apply a filter' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>g{FILTER:url(#f)}</style><filter id="f" filterUnits="userSpaceOnUse" width="9" height="9"><feFlood/></filter><g/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>g{FILTER:url(#f)}</style><filter id="f" filterUnits="userSpaceOnUse" width="9" height="9"><feFlood/></filter><g/></svg>',
        ];

        yield 'Groups: keeps empty groups when a style sheet escapes a name' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>g{filt\65r:url(#f)}</style><filter id="f" filterUnits="userSpaceOnUse" width="9" height="9"><feFlood/></filter><g/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>g{filt\65r:url(#f)}</style><filter id="f" filterUnits="userSpaceOnUse" width="9" height="9"><feFlood/></filter><g/></svg>',
        ];

        yield 'Groups: removes empty groups when the style sheet applies no filter' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>g{fill:red}</style><g/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>g{fill:red}</style></svg>',
        ];

        yield 'Groups: keeps a group with a non-breaking space' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g>&#xA0;</g></svg>',
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><g>\u{a0}</g></svg>",
        ];

        yield 'Groups: keeps a group with an entity' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g>&amp;</g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g>&amp;</g></svg>',
        ];

        yield 'Groups: removes an empty mixed-case group element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><G/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Groups: keeps an empty group reset to no namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g xmlns=""/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g xmlns=""/></svg>',
        ];

        yield 'Groups: keeps an empty group in another namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><x:g xmlns:x="urn:x"/><metadata><g xmlns="urn:y"/></metadata></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><x:g xmlns:x="urn:x"/><metadata><g xmlns="urn:y"/></metadata></svg>',
        ];

        yield 'Groups: removes an empty prefixed svg group' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:g/><svg:rect width="5" height="5"/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:rect width="5" height="5"/></svg:svg>',
        ];

        yield 'Groups: keeps empty groups when an external style sheet is linked' => [
            '<?xml-stylesheet href="style.css"?><svg xmlns="http://www.w3.org/2000/svg"><g/></svg>',
            '<?xml-stylesheet href="style.css"?><svg xmlns="http://www.w3.org/2000/svg"><g/></svg>',
        ];

        yield 'Groups: removes deeply nested empty groups' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g><g/></g></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Groups: removes a group with only an empty character data section' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><![CDATA[ ]]></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Groups: keeps an empty group inside a switch' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><switch><g/><rect width="5" height="5"/></switch></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><switch><g/><rect width="5" height="5"/></switch></svg>',
        ];

        yield 'Groups: keeps empty groups when a sibling combinator may match' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>g+rect{fill:red}</style><g></g><rect width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>g+rect{fill:red}</style><g/><rect width="5" height="5"/></svg>',
        ];

        yield 'Groups: keeps empty groups when a structural pseudo-class may match' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect:NTH-child(3){fill:red}</style><g/><rect width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect:NTH-child(3){fill:red}</style><g/><rect width="5" height="5"/></svg>',
        ];

        yield 'Groups: keeps empty groups when an external style sheet applies' => [
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg"><g/><rect width="5" height="5"/></svg>',
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg"><g/><rect width="5" height="5"/></svg>',
        ];

        yield 'Groups: removes empty groups when the style sheet does not depend on siblings' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>g rect{fill:red}</style><g/><rect width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>g rect{fill:red}</style><rect width="5" height="5"/></svg>',
        ];

        yield 'Groups: removes completely empty group' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g></g>
                    <rect width="10" height="10" fill="red"/>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10" fill="red"/></svg>',
        ];

        yield 'Groups: keeps group with attributes' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g id="keep"/>
                    <rect width="10" height="10" fill="blue"/>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"><g id="keep"/><rect width="10" height="10" fill="blue"/></svg>',
        ];

        yield 'Groups: keeps group with child elements' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g>
                        <circle cx="5" cy="5" r="5" fill="green"/>
                    </g>
                    <g></g>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"><g><circle cx="5" cy="5" r="5" fill="green"/></g></svg>',
        ];

        yield 'Groups: removes multiple consecutive empty groups' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g></g>
                    <g></g>
                    <g><rect width="10" height="10"/></g>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect width="10" height="10"/></g></svg>',
        ];

        yield 'Groups: removes nested empty groups' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g>
                        <g></g>
                        <g id="inner"><g></g></g>
                    </g>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g id="inner"/></g></svg>',
        ];

        yield 'Groups: ignores non-group elements' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect width="10" height="10"/>
                    <circle cx="5" cy="5" r="5"/>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/><circle cx="5" cy="5" r="5"/></svg>',
        ];

        yield 'Groups: removes groups with comments only' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g><!-- comment --></g>
                    <g></g>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Groups: keeps group with whitespace-only text' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g>   </g>
                    <g></g>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Groups: removes group with only nested empty groups' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g>
                        <g></g>
                        <g></g>
                    </g>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Groups: keeps group with only non-whitespace text and no elements' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g>meaningful text</g>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"><g>meaningful text</g></svg>',
        ];

        yield 'Groups: removes an empty group nested after a leading text node in the parent' => [
            '<svg xmlns="http://www.w3.org/2000/svg">leading text<g/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg">leading text</svg>',
        ];

        yield 'Groups: keeps mixed content group (text + element)' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g>text<rect width="5" height="5"/></g>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"><g>text<rect width="5" height="5"/></g></svg>',
        ];

        yield 'Groups: removes empty groups deeply nested with other groups' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g>
                        <g>
                            <g></g>
                        </g>
                        <g><circle cx="2" cy="2" r="2"/></g>
                    </g>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><circle cx="2" cy="2" r="2"/></g></g></svg>',
        ];

        yield 'Groups: removes a group with comments and whitespace only' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g> <!-- comment --> </g>
                </svg>
                EOD,
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Groups: groups with only empty text nodes' => [
            <<<'EOD'
                <svg><g>
                	
                </g></svg>
                EOD,
            '<svg/>',
        ];

        yield 'Groups: groups with defs elements' => [
            '<svg><defs><g/></defs></svg>',
            '<svg/>',
        ];
    }
}
