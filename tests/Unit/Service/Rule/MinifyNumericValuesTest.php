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
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyNumericValues;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MinifyNumericValues::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(XmlFormatter::class)]
final class MinifyNumericValuesTest extends TestCase
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
        $svgOptimizer->addRule(new MinifyNumericValues());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Shortens lengths and drops px' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect x="+10.0" y="0.50" width="01.50px" height="2PX" stroke-width="1.0px"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect x="10" y=".5" width="1.5" height="2PX" stroke-width="1"/></svg>',
        ];

        yield 'Shortens a length followed by an attribute sharing its local name' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:a="urn:a"><rect x="10.0" a:x="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:a="urn:a"><rect x="10" a:x="1"/></svg>',
        ];

        yield 'Shortens unitless numbers' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity="0.50" fill-opacity="1.0" stroke-miterlimit="04"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity=".5" fill-opacity="1" stroke-miterlimit="4"/></svg>',
        ];

        yield 'Keeps px on unitless numbers' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity="1px"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity="1px"/></svg>',
        ];

        yield 'Keeps percentages and other units' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="50%" height="1em" x="1in"/><stop offset="50.0%"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="50%" height="1em" x="1in"/><stop offset="50.0%"/></svg>',
        ];

        yield 'Keeps lists and keywords' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text x="1.0 2.0" letter-spacing="normal">a</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text x="1.0 2.0" letter-spacing="normal">a</text></svg>',
        ];

        yield 'Keeps unlisted attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g data-x="1.0" class="0.50"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g data-x="1.0" class="0.50"/></svg>',
        ];

        yield 'Never rounds' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity="0.123456789012345678901"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity=".123456789012345678901"/></svg>',
        ];

        yield 'Writes zero as 0' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect x="-0.0" y="00"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect x="0" y="0"/></svg>',
        ];

        yield 'Keeps negative values' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke-dashoffset="-01.50"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke-dashoffset="-1.5"/></svg>',
        ];

        yield 'Trims surrounding whitespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect x=" 1.0 " opacity=" 0.5 "/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect x="1" opacity=".5"/></svg>',
        ];

        yield 'Shortens exponents' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect x="1.50E+02"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect x="1.5e2"/></svg>',
        ];

        yield 'Keeps namespaced attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><g x:opacity="0.50"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><g x:opacity="0.50"/></svg>',
        ];

        yield 'Keeps values that are not numbers' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity="."/><rect x="1e"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity="."/><rect x="1e"/></svg>',
        ];
    }

    #[Test]
    public function isRiskyReturnsFalse(): void
    {
        self::assertFalse(MinifyNumericValues::isRisky());
    }

    #[Test]
    public function shouldCheckSizeReturnsFalse(): void
    {
        self::assertFalse(MinifyNumericValues::shouldCheckSize());
    }
}
