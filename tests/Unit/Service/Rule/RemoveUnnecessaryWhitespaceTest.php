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
use MathiasReker\PhpSvgOptimizer\Service\Processor\AbstractXmlProcessor;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnnecessaryWhitespace;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveUnnecessaryWhitespace::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(XmlFormatter::class)]
#[CoversClass(AbstractXmlProcessor::class)]
final class RemoveUnnecessaryWhitespaceTest extends TestCase
{
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
        $svgOptimizer->addRule(new RemoveUnnecessaryWhitespace());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Removes Unnecessary Whitespace' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">

                    <circle cx="50" cy="50" r="20" fill="red"/>

                    <rect x="10" y="10" width="30" height="30" fill="blue"/>

                    <!-- A comment -->
                    <text x="20" y="20"> Hello World </text>

                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="20" fill="red"/><rect x="10" y="10" width="30" height="30" fill="blue"/><!-- A comment --><text x="20" y="20"> Hello World </text></svg>
                XML,
        ];

        yield 'Only Whitespace and Comments' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">

                    <!-- A comment -->

                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><!-- A comment --></svg>
                XML,
        ];

        yield 'Multiple Elements and Mixed Whitespace' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="200" height="200">
                    <circle cx="100" cy="100" r="50" fill="green" />

                    <!-- Comment -->

                    <rect x="20" y="20" width="100" height="100" fill="yellow"/>

                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><circle cx="100" cy="100" r="50" fill="green"/><!-- Comment --><rect x="20" y="20" width="100" height="100" fill="yellow"/></svg>
                XML,
        ];

        yield 'Whitespace in Attribute Names' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle  cx="50" cy="50" r="20" fill="red"/>
                    <rect  x="10"  y="10" width="30" height="30" fill="blue"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="20" fill="red"/><rect x="10" y="10" width="30" height="30" fill="blue"/></svg>
                XML,
        ];

        yield 'Attributes with No Value' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle cx="50" cy="50" r="20" fill="red" />
                    <rect x="10" y="10" width="30" height="30" fill="blue" />
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="20" fill="red"/><rect x="10" y="10" width="30" height="30" fill="blue"/></svg>
                XML,
        ];

        yield 'Mixed Whitespace Types in Attributes' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle cx="50" \tcy="50" r="20" fill="red"/>
                    <rect x="10" \ny="10" width="30" height="30" fill="blue"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="20" fill="red"/><rect x="10" y="10" width="30" height="30" fill="blue"/></svg>
                XML,
        ];

        yield 'Attributes with Embedded Newlines' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle cx="50" cy="50" r="20" fill="
                    red"/>
                    <rect x="10" y="10" width="30" height="30" fill="
                    blue"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="20" fill="red"/><rect x="10" y="10" width="30" height="30" fill="blue"/></svg>
                XML,
        ];

        yield 'Preserving Whitespace in Text Nodes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <text x="10" y="10">   Preserve   This   </text>
                    <circle cx="50" cy="50" r="20" fill="red"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><text x="10" y="10">   Preserve   This   </text><circle cx="50" cy="50" r="20" fill="red"/></svg>
                XML,
        ];

        yield 'Style Attributes with Complex Values' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle cx="50" cy="50" r="20" style=" fill : red ; stroke : black ; stroke-width : 2 ;"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="20" style="fill:red;stroke:black;stroke-width:2"/></svg>
                XML,
        ];

        yield 'Minimal SVG Content' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Well-Formatted SVG' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle cx="50" cy="50" r="20" fill="red"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="20" fill="red"/></svg>
                XML,
        ];

        yield 'Remove whitespace around attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox=" 0 0  150 100 " width="100" height="100">
                    <circle cx="50" cy="50" r="20" fill="red"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 150 100" width="100" height="100"><circle cx="50" cy="50" r="20" fill="red"/></svg>
                XML,
        ];

        yield 'Handles Tabs, Spaces, and Newlines' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle cx=" 50 " cy="50" r="20" fill="red" />
                    <rect x="10" y="10" width="30" height="30" fill="blue"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="20" fill="red"/><rect x="10" y="10" width="30" height="30" fill="blue"/></svg>
                XML,
        ];

        yield 'Inline Style Attributes with Unnecessary Spaces' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle cx="50" cy="50" r="20" style=" fill : red ; stroke : black ; stroke-width : 2 ; "/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="20" style="fill:red;stroke:black;stroke-width:2"/></svg>
                XML,
        ];

        yield 'Mixed Content with Text Nodes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <text x="10" y="10">   Some   Text   Here   </text>
                    <circle cx="50" cy="50" r="20" fill="red"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><text x="10" y="10">   Some   Text   Here   </text><circle cx="50" cy="50" r="20" fill="red"/></svg>
                XML,
        ];

        yield 'Self-Closing Tags with Spaces' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <path d="M10 10 h 80 v 80 h -80 Z" fill="none" stroke="black" />
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><path d="M10 10 h 80 v 80 h -80 Z" fill="none" stroke="black"/></svg>
                XML,
        ];

        yield 'Nested Elements with Varying Whitespace' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(10 10)">
                        <circle cx="40" cy="40" r="20" fill="green"/>
                        <rect x="20" y="20" width="40" height="40" fill="yellow"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="translate(10 10)"><circle cx="40" cy="40" r="20" fill="green"/><rect x="20" y="20" width="40" height="40" fill="yellow"/></g></svg>
                XML,
        ];

        yield 'Handles Nested Elements with Mixed Whitespace' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="150" height="150">
                    <g transform="translate(20 20)">
                        <circle cx="50" cy="50" r="30" fill="blue" />
                        <g>
                            <rect x="40" y="40" width="50" height="50" fill="yellow" />
                        </g>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="150" height="150"><g transform="translate(20 20)"><circle cx="50" cy="50" r="30" fill="blue"/><g><rect x="40" y="40" width="50" height="50" fill="yellow"/></g></g></svg>
                XML,
        ];

        yield 'Preserves CDATA Sections' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <script><![CDATA[
                        console.log("This is a script");
                    ]]></script>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><script>        console.log("This is a script");    </script></svg>
                XML,
        ];

        yield 'Handles Mixed Content and Whitespace in SVG' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="200" height="200">
                    <text x="10" y="10">  Keep This Text   </text>
                    <g>
                        <circle cx="50" cy="50" r="25" fill="red" />
                        <rect x="30" y="30" width="40" height="40" fill="green" />
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><text x="10" y="10">  Keep This Text   </text><g><circle cx="50" cy="50" r="25" fill="red"/><rect x="30" y="30" width="40" height="40" fill="green"/></g></svg>
                XML,
        ];

        yield 'Handles Whitespace in Complex Path Data' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <path d="M 10 10 C 20 20, 40 20, 50 10 S 70 10, 80 10" fill="none" stroke="black"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><path d="M 10 10 C 20 20, 40 20, 50 10 S 70 10, 80 10" fill="none" stroke="black"/></svg>
                XML,
        ];

        yield 'Removes Unnecessary Whitespace in Grouped Elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="300" height="300">
                    <g>
                        <circle cx="50" cy="50" r="30" fill="orange" />
                        <rect x="70" y="70" width="60" height="60" fill="blue" />
                    </g>
                    <g>
                        <ellipse cx="150" cy="150" rx="40" ry="20" fill="pink" />
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="300" height="300"><g><circle cx="50" cy="50" r="30" fill="orange"/><rect x="70" y="70" width="60" height="60" fill="blue"/></g><g><ellipse cx="150" cy="150" rx="40" ry="20" fill="pink"/></g></svg>
                XML,
        ];

        yield 'Handles Inline Styles in Nested Elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="150" height="150">
                    <g style="stroke: black; fill: none;">
                        <circle cx="75" cy="75" r="50" style="stroke-width: 2;"/>
                        <rect x="50" y="50" width="50" height="50" style="fill: red;"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="150" height="150"><g style="stroke:black;fill:none"><circle cx="75" cy="75" r="50" style="stroke-width:2"/><rect x="50" y="50" width="50" height="50" style="fill:red"/></g></svg>
                XML,
        ];

        yield 'Whitespace After Self-Closing Tag' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle cx="50" cy="50" r="20" fill="red"  />
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="20" fill="red"/></svg>
                XML,
        ];

        yield 'Multiple Lines in Tags' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <circle cx="50" cy="50" r="20" fill="red" />
                    <rect x="10" y="10" width="30" height="30" fill="blue" />
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="20" fill="red"/><rect x="10" y="10" width="30" height="30" fill="blue"/></svg>
                XML,
        ];

        yield 'Text with Extra Whitespace' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <text x="10" y="10">   Some   Text   </text>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><text x="10" y="10">   Some   Text   </text></svg>
                XML,
        ];

        yield 'Attributes with Excess Whitespace Around Values' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle  cx="50" cy="50" r="20" fill=" red " />
                    <rect  x=" 10 "  y="10 " width="30" height=" 30 " fill="blue"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="20" fill="red"/><rect x="10" y="10" width="30" height="30" fill="blue"/></svg>
                XML,
        ];

        yield 'Complex Inline Style with Multiple Spaces' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle cx="50" cy="50" r="20" style=" fill : red ; stroke : black ; stroke-width : 2 ;" />
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="20" style="fill:red;stroke:black;stroke-width:2"/></svg>
                XML,
        ];

        yield 'Space between tags' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle cx="50" cy="50" r="20"/>     <circle cx="50" cy="50" r="20"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="20"/><circle cx="50" cy="50" r="20"/></svg>
                XML,
        ];
    }
}
