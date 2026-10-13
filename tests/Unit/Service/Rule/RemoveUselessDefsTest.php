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
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUselessDefs;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveUselessDefs::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class RemoveUselessDefsTest extends TestCase
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
        $svgOptimizer->addRule(new RemoveUselessDefs());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Keeps a definition containing a style sheet' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><g><style>rect{fill:red}</style></g><g><path d="M0 0"/></g></defs><rect width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><g><style>rect{fill:red}</style></g></defs><rect width="5" height="5"/></svg>',
        ];

        yield 'Keeps a reference-only element containing a style sheet' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><symbol><style>rect{fill:red}</style></symbol><rect width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><symbol><style>rect{fill:red}</style></symbol><rect width="5" height="5"/></svg>',
        ];

        yield 'Removes definitions without an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient><stop offset="0"/></linearGradient><path d="M0 0"/></defs><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes reference-only elements without an id outside defs' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><radialGradient/><clipPath><rect/></clipPath><filter/><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Keeps definitions with an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="g"/><g><path id="p"/></g></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="g"/><g><path id="p"/></g></defs></svg>',
        ];

        yield 'Keeps definitions with an xml:id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient xml:id="g"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient xml:id="g"/></defs></svg>',
        ];

        yield 'Keeps style inside defs' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><style>rect{fill:red}</style></defs><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><style>rect{fill:red}</style></defs><rect/></svg>',
        ];

        yield 'Keeps rendered elements outside defs' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><path d="M0 0"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><path d="M0 0"/></g></svg>',
        ];

        yield 'Keeps empty defs with an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs id="d"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs id="d"/></svg>',
        ];

        yield 'Keeps fonts inside defs' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><font horiz-adv-x="1"/><font-face font-family="x"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><font horiz-adv-x="1"/><font-face font-family="x"/></defs></svg>',
        ];

        yield 'Keeps defs with remaining text' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs>text<path/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs>text</defs></svg>',
        ];

        yield 'Removes nested definitions without an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><linearGradient><stop/></linearGradient><rect/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect/></g></svg>',
        ];

        yield 'Keeps reference-only elements with an id descendant' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><symbol><g id="a"/></symbol></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><symbol><g id="a"/></symbol></svg>',
        ];
    }

    #[Test]
    public function isRiskyReturnsFalse(): void
    {
        self::assertFalse(RemoveUselessDefs::isRisky());
    }

    #[Test]
    public function shouldCheckSizeReturnsFalse(): void
    {
        self::assertFalse(RemoveUselessDefs::shouldCheckSize());
    }
}
