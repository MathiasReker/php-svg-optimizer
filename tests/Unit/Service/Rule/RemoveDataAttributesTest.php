<?php

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Exception\SvgValidationException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Model\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Formatter\XmlFormatter;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDataAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveDataAttributes::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(XmlFormatter::class)]
final class RemoveDataAttributesTest extends TestCase
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
        $svgOptimizer->addRule(new RemoveDataAttributes());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Removes single data attribute' => [
            '<svg data-test="value" width="100"><rect width="100" height="100"/></svg>',
            '<svg width="100"><rect width="100" height="100"/></svg>',
        ];

        yield 'Removes multiple data attributes' => [
            '<svg data-id="1" data-name="icon"><circle data-radius="50" cx="50" cy="50" r="50"/></svg>',
            '<svg><circle cx="50" cy="50" r="50"/></svg>',
        ];

        yield 'Removes nested data attributes' => [
            '<svg><g data-group="1"><rect data-rect="true" width="10" height="10"/></g></svg>',
            '<svg><g><rect width="10" height="10"/></g></svg>',
        ];

        yield 'Keeps non-data attributes intact' => [
            '<svg width="100" height="100" fill="red" data-test="remove"><rect width="50" height="50"/></svg>',
            '<svg width="100" height="100" fill="red"><rect width="50" height="50"/></svg>',
        ];

        yield 'No data attributes present' => [
            '<svg width="100" height="100"><rect width="50" height="50"/></svg>',
            '<svg width="100" height="100"><rect width="50" height="50"/></svg>',
        ];

        yield 'Removes mixed case data attributes' => [
            '<svg DATA-Test="value" dAtA-Id="123"><rect DATA-Rect="true"/></svg>',
            '<svg><rect/></svg>',
        ];

        yield 'Removes data attributes from self-closing elements' => [
            '<svg><rect data-info="test" width="10" height="10"/></svg>',
            '<svg><rect width="10" height="10"/></svg>',
        ];

        yield 'Removes data attributes along with aria attributes' => [
            '<svg aria-label="icon" data-test="value"><rect data-rect="1" width="20" height="20"/></svg>',
            '<svg aria-label="icon"><rect width="20" height="20"/></svg>',
        ];

        yield 'Removes data attributes with extra spaces' => [
            '<svg   data-test="value"   data-id="5"  ><rect width="10" height="10"/></svg>',
            '<svg><rect width="10" height="10"/></svg>',
        ];

        yield 'Removes data attributes from nested <defs> elements' => [
            '<svg><defs><g data-group="nested"><rect data-rect="true" width="10" height="10"/></g></defs></svg>',
            '<svg><defs><g><rect width="10" height="10"/></g></defs></svg>',
        ];

        yield 'Handles empty SVG' => [
            '<svg></svg>',
            '<svg/>',
        ];

        yield 'Removes multiple data attributes on different elements' => [
            '<svg data-one="1"><g data-two="2"><rect data-three="3"/></g></svg>',
            '<svg><g><rect/></g></svg>',
        ];
    }
}
