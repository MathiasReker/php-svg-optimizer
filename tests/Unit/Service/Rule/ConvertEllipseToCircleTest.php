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
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertEllipseToCircle;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ConvertEllipseToCircle::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class ConvertEllipseToCircleTest extends TestCase
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
        $svgOptimizer->addRule(new ConvertEllipseToCircle());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Keeps ellipses targeted by an animation through href' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse id="e" rx="3" ry="3"/><animate href="#e" attributeName="rx" to="5"/><ellipse rx="3" ry="3"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse id="e" rx="3" ry="3"/><animate href="#e" attributeName="rx" to="5"/><circle r="3"/></svg>',
        ];

        yield 'Converts ellipse with equal radii' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse cx="5" cy="5" rx="3" ry="3" fill="red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><circle cx="5" cy="5" fill="red" r="3"/></svg>',
        ];

        yield 'Converts equal radii written differently' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="3" ry="3.0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><circle r="3"/></svg>',
        ];

        yield 'Keeps ellipse with different radii' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="3" ry="4"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="3" ry="4"/></svg>',
        ];

        yield 'Keeps ellipse with auto radius' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="3" ry="auto"/><ellipse rx="3"/><ellipse ry="3"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="3" ry="auto"/><ellipse rx="3"/><ellipse ry="3"/></svg>',
        ];

        yield 'Keeps ellipse with style attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="3" ry="3" style="rx:4"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="3" ry="3" style="rx:4"/></svg>',
        ];

        yield 'Keeps ellipse with child elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="3" ry="3"><set attributeName="rx" to="5"/></ellipse></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="3" ry="3"><set attributeName="rx" to="5"/></ellipse></svg>',
        ];

        yield 'Keeps ellipses when a style element exists' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>ellipse{fill:red}</style><ellipse rx="3" ry="3"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>ellipse{fill:red}</style><ellipse rx="3" ry="3"/></svg>',
        ];

        yield 'Keeps the id and children text' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse id="e" rx="3" ry="3"><!--c--></ellipse></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><circle id="e" r="3"><!--c--></circle></svg>',
        ];

        yield 'Converts equal radii with surrounding whitespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx=" 3" ry="3 "/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><circle r=" 3"/></svg>',
        ];

        yield 'Converts every matching ellipse' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="1" ry="1"/><ellipse rx="1" ry="2"/><g><ellipse rx="2" ry="2"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><circle r="1"/><ellipse rx="1" ry="2"/><g><circle r="2"/></g></svg>',
        ];
    }

    #[Test]
    public function isRiskyReturnsFalse(): void
    {
        self::assertFalse(ConvertEllipseToCircle::isRisky());
    }

    #[Test]
    public function shouldCheckSizeReturnsFalse(): void
    {
        self::assertFalse(ConvertEllipseToCircle::shouldCheckSize());
    }
}
