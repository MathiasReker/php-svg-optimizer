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
use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Service\Optimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveZeroSizeShapes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\DocumentScriptTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\NumberFormatterTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\SvgElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveZeroSizeShapes::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(DocumentScriptTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(NumberFormatterTrait::class)]
#[CoversTrait(SvgElementTrait::class)]
#[UsesClass(ExecutionOrder::class)]
final class RemoveZeroSizeShapesTest extends TestCase
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
        $svgOptimizer->addPass(new RemoveZeroSizeShapes());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);

        $repeated = new SvgOptimizer(new StringProvider($actual));
        $repeated->addPass(new RemoveZeroSizeShapes());
        self::assertSame($actual, $repeated->optimize()->getContent());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Keeps zero size shapes in a document with a script, which may resize or query them' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><script>a()</script><rect width="0" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>a()</script><rect width="0" height="5"/></svg>',
        ];

        yield 'Keeps zero size shapes in a document with an event handler' => [
            '<svg xmlns="http://www.w3.org/2000/svg" onload="a()"><circle r="0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" onload="a()"><circle r="0"/></svg>',
        ];
        yield 'Keeps a zero size shape exposed to assistive technology' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="0" height="5" aria-label="a"/><rect width="0" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="0" height="5" aria-label="a"/></svg>',
        ];

        yield 'Keeps a zero size shape that can take focus' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="0" height="5" tabindex="0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="0" height="5" tabindex="0"/></svg>',
        ];

        yield 'leaves an empty document alone' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'removes a shape with a zero padded with a tab and a newline' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="&#9;0&#10;" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'keeps a shape with a zero padded with a non-breaking space' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="0&#xA0;" height="5"/></svg>',
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><rect width=\"0\u{A0}\" height=\"5\"/></svg>",
        ];

        yield 'keeps a shape with a mixed-case zero attribute name' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect Width="0" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect Width="0" height="5"/></svg>',
        ];

        yield 'removes a zero size shape deep inside groups' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g><g><circle r="0"/></g></g></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g><g/></g></g></g></svg>',
        ];

        yield 'keeps a zero size shape in an element reset to no namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g xmlns=""><rect width="0" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g xmlns=""><rect width="0" height="5"/></g></svg>',
        ];

        yield 'keeps a zero size shape in another namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><x:rect xmlns:x="urn:x" width="0" height="5"/><circle xmlns="urn:y" r="0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><x:rect xmlns:x="urn:x" width="0" height="5"/><circle xmlns="urn:y" r="0"/></svg>',
        ];

        yield 'keeps zero size shapes when an external style sheet is linked' => [
            '<?xml-stylesheet href="style.css"?><svg xmlns="http://www.w3.org/2000/svg"><rect width="0" height="5"/></svg>',
            '<?xml-stylesheet href="style.css"?><svg xmlns="http://www.w3.org/2000/svg"><rect width="0" height="5"/></svg>',
        ];

        yield 'removes shapes with a negative, padded or exponent zero' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="-0" height="5"/><circle r=" 0 "/><ellipse rx="0e5" ry="5"/><rect width="5" height=".0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'keeps a shape with a zero percentage' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="0%" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="0%" height="5"/></svg>',
        ];

        yield 'removes prefixed svg shapes and paths without data' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:rect width="0" height="5"/><svg:path d=" "/><svg:polygon/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"/>',
        ];

        yield 'removes a later zero size shape after a kept one' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="a" width="0" height="5"/><rect width="0" height="5"/><rect width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="a" width="0" height="5"/><rect width="5" height="5"/></svg>',
        ];

        yield 'keeps a zero size shape animated by a child' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="0" height="5"><animate attributeName="width" to="5" dur="1s"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="0" height="5"><animate attributeName="width" to="5" dur="1s"/></rect></svg>',
        ];

        yield 'Keeps zero-size shapes that are children of switch' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><switch><rect width="0" height="5"/><text>fallback</text></switch><rect width="0" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><switch><rect width="0" height="5"/><text>fallback</text></switch></svg>',
        ];

        yield 'Removes shapes with a zero size' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><circle r="0"/><ellipse rx="0.0" ry="2"/><ellipse rx="2" ry="0"/><rect width="10" height="-0"/><rect width="0" height="10"/><path/><path d=" "/><polygon points=""/><polyline/><rect width="1" height="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1"/></svg>',
        ];

        yield 'Keeps shapes with an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><circle id="c" r="0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><circle id="c" r="0"/></svg>',
        ];

        yield 'Keeps shapes with child elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><circle r="0"><set attributeName="r" to="5"/></circle></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><circle r="0"><set attributeName="r" to="5"/></circle></svg>',
        ];

        yield 'Keeps shapes with a style attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><circle r="0" style="r:5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><circle r="0" style="r:5"/></svg>',
        ];

        yield 'Keeps shapes when a style element exists' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>circle{r:5}</style><circle r="0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>circle{r:5}</style><circle r="0"/></svg>',
        ];

        yield 'Keeps zero-length lines and non-numeric sizes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><line x1="1" x2="1"/><circle r="0%"/><rect height="10"/><path d="M0 0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><line x1="1" x2="1"/><circle r="0%"/><rect height="10"/><path d="M0 0"/></svg>',
        ];

        yield 'Removes shapes with a zero size and surrounding whitespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><circle r=" 0 "/><rect width="1" height="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1"/></svg>',
        ];

        yield 'Keeps shapes with an xml:id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><circle xml:id="c" r="0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><circle xml:id="c" r="0"/></svg>',
        ];

        yield 'Keeps shapes with a positive size' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><circle r="0.001"/><ellipse rx="1" ry="1"/><polygon points="0 0 1 1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><circle r="0.001"/><ellipse rx="1" ry="1"/><polygon points="0 0 1 1"/></svg>',
        ];

        yield 'Removes zero-size shapes inside groups' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><circle r="0"/><rect width="1" height="1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect width="1" height="1"/></g></svg>',
        ];

        yield 'Keeps other elements without geometry' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g/><text/><use/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g/><text/><use/></svg>',
        ];
    }
}
