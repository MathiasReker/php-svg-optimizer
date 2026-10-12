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
#[CoversClass(XmlFormatter::class)]
final class RemoveEmptyContainersTest extends TestCase
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
        $svgOptimizer->addRule(new RemoveEmptyContainers());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
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
