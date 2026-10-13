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
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveZeroSizeShapes;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveZeroSizeShapes::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
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
        $svgOptimizer->addRule(new RemoveZeroSizeShapes());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
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

    #[Test]
    public function isRiskyReturnsFalse(): void
    {
        self::assertFalse(RemoveZeroSizeShapes::isRisky());
    }

    #[Test]
    public function shouldCheckSizeReturnsFalse(): void
    {
        self::assertFalse(RemoveZeroSizeShapes::shouldCheckSize());
    }
}
