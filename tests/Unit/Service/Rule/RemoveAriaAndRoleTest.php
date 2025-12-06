<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Exception\SvgValidationException;
use MathiasReker\PhpSvgOptimizer\Model\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveAriaAndRole;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveAriaAndRole::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
final class RemoveAriaAndRoleTest extends TestCase
{
    /**
     * @throws SvgValidationException
     */
    #[DataProvider('provideOptimizeCases')]
    public function testOptimize(string $content, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($content));
        $svgOptimizer->addRule(new RemoveAriaAndRole());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Removes single role attribute' => [
            '<svg role="img" width="100" height="100"><rect width="100" height="100" fill="blue"/></svg>',
            '<svg width="100" height="100"><rect width="100" height="100" fill="blue"/></svg>',
        ];

        yield 'Removes multiple aria attributes' => [
            '<svg aria-label="SVG Icon" aria-hidden="true"><rect width="100" height="100" fill="red"/></svg>',
            '<svg><rect width="100" height="100" fill="red"/></svg>',
        ];

        yield 'Removes role and aria attributes together' => [
            '<svg role="presentation" aria-labelledby="title"><rect width="100" height="100" fill="green"/></svg>',
            '<svg><rect width="100" height="100" fill="green"/></svg>',
        ];

        yield 'Removes attributes from nested elements' => [
            '<svg><g role="group" aria-label="Group"><rect width="50" height="50" fill="yellow"/></g></svg>',
            '<svg><g><rect width="50" height="50" fill="yellow"/></g></svg>',
        ];

        yield 'Keeps other attributes intact' => [
            '<svg width="100" height="100" role="img" aria-label="icon" viewBox="0 0 100 100"><rect width="100" height="100" fill="orange"/></svg>',
            '<svg width="100" height="100" viewBox="0 0 100 100"><rect width="100" height="100" fill="orange"/></svg>',
        ];

        yield 'No role or aria attributes present' => [
            '<svg width="100" height="100"><rect width="100" height="100" fill="purple"/></svg>',
            '<svg width="100" height="100"><rect width="100" height="100" fill="purple"/></svg>',
        ];

        yield 'Removes unusual aria-* attributes' => [
            '<svg aria-checked="true" aria-invalid="false" aria-owns="someId"><rect width="20" height="20"/></svg>',
            '<svg><rect width="20" height="20"/></svg>',
        ];

        yield 'Removes mixed case aria-* attributes' => [
            '<svg ARIA-LABEL="icon" ArIa-Hidden="true"><rect width="10" height="10"/></svg>',
            '<svg><rect width="10" height="10"/></svg>',
        ];

        yield 'Removes role and aria attributes with other elements in defs' => [
            '<svg><defs><g role="presentation" aria-label="group"><path d="M0 0L10 10"/></g></defs></svg>',
            '<svg><defs><g><path d="M0 0L10 10"/></g></defs></svg>',
        ];

        yield 'Removes aria attributes with hyphenated names' => [
            '<svg aria-labelledby="title" aria-describedby="desc"><rect width="30" height="30"/></svg>',
            '<svg><rect width="30" height="30"/></svg>',
        ];

        yield 'Handles self-closing elements with aria and role' => [
            '<svg><rect aria-label="rect" role="presentation" width="50" height="50" fill="blue"/></svg>',
            '<svg><rect width="50" height="50" fill="blue"/></svg>',
        ];

        yield 'Handles multiple nested elements with mixed aria and role' => [
            '<svg role="img"><g aria-label="group"><rect role="presentation" aria-hidden="true"/></g></svg>',
            '<svg><g><rect/></g></svg>',
        ];

        yield 'Handles empty SVG' => [
            '<svg></svg>',
            '<svg/>',
        ];

        yield 'Handles SVG with multiple attributes, keeps non-aria ones' => [
            '<svg role="img" aria-label="icon" width="100" height="100" fill="red"><circle cx="50" cy="50" r="40"/></svg>',
            '<svg width="100" height="100" fill="red"><circle cx="50" cy="50" r="40"/></svg>',
        ];

        yield 'Removes ARIA from nested <g> and <circle>' => [
            '<svg><g aria-label="group1"><circle aria-checked="true" cx="25" cy="25" r="20"/></g></svg>',
            '<svg><g><circle cx="25" cy="25" r="20"/></g></svg>',
        ];

        yield 'Removes ARIA boolean attributes' => [
            '<svg aria-hidden="true" aria-pressed="false"><rect width="10" height="10"/></svg>',
            '<svg><rect width="10" height="10"/></svg>',
        ];

        yield 'Removes ARIA numeric attributes' => [
            '<svg aria-level="3" aria-valuemax="100"><rect width="10" height="10"/></svg>',
            '<svg><rect width="10" height="10"/></svg>',
        ];

        yield 'Removes empty ARIA attributes' => [
            '<svg aria-label=""><rect width="10" height="10"/></svg>',
            '<svg><rect width="10" height="10"/></svg>',
        ];

        yield 'Removes role and ARIA on <path>' => [
            '<svg><path role="presentation" aria-hidden="true" d="M0 0L10 10"/></svg>',
            '<svg><path d="M0 0L10 10"/></svg>',
        ];

        // New test cases
        yield 'Removes aria-activedescendant attribute' => [
            '<svg aria-activedescendant="item1"><rect width="50" height="50"/></svg>',
            '<svg><rect width="50" height="50"/></svg>',
        ];

        yield 'Removes aria-atomic attribute' => [
            '<svg aria-atomic="true"><circle cx="25" cy="25" r="10"/></svg>',
            '<svg><circle cx="25" cy="25" r="10"/></svg>',
        ];

        yield 'Removes aria-autocomplete attribute' => [
            '<svg aria-autocomplete="list"><rect width="20" height="20"/></svg>',
            '<svg><rect width="20" height="20"/></svg>',
        ];

        yield 'Removes aria-busy attribute' => [
            '<svg aria-busy="false"><rect width="10" height="10"/></svg>',
            '<svg><rect width="10" height="10"/></svg>',
        ];

        yield 'Removes aria-checked attribute' => [
            '<svg aria-checked="mixed"><rect width="15" height="15"/></svg>',
            '<svg><rect width="15" height="15"/></svg>',
        ];

        yield 'Removes aria-colcount and aria-colindex attributes' => [
            '<svg aria-colcount="5" aria-colindex="2"><rect width="25" height="25"/></svg>',
            '<svg><rect width="25" height="25"/></svg>',
        ];

        yield 'Removes aria-colspan attribute' => [
            '<svg aria-colspan="3"><rect width="30" height="30"/></svg>',
            '<svg><rect width="30" height="30"/></svg>',
        ];

        yield 'Removes aria-controls attribute' => [
            '<svg aria-controls="control1"><rect width="10" height="10"/></svg>',
            '<svg><rect width="10" height="10"/></svg>',
        ];

        yield 'Removes aria-current attribute' => [
            '<svg aria-current="page"><rect width="50" height="50"/></svg>',
            '<svg><rect width="50" height="50"/></svg>',
        ];

        yield 'Removes aria-describedby attribute' => [
            '<svg aria-describedby="desc1"><rect width="40" height="40"/></svg>',
            '<svg><rect width="40" height="40"/></svg>',
        ];

        yield 'Removes aria-details attribute' => [
            '<svg aria-details="details1"><rect width="20" height="20"/></svg>',
            '<svg><rect width="20" height="20"/></svg>',
        ];

        yield 'Removes aria-disabled attribute' => [
            '<svg aria-disabled="true"><rect width="15" height="15"/></svg>',
            '<svg><rect width="15" height="15"/></svg>',
        ];

        yield 'Removes aria-dropeffect attribute' => [
            '<svg aria-dropeffect="copy"><circle cx="25" cy="25" r="12"/></svg>',
            '<svg><circle cx="25" cy="25" r="12"/></svg>',
        ];

        yield 'Removes aria-errormessage attribute' => [
            '<svg aria-errormessage="error1"><rect width="10" height="10"/></svg>',
            '<svg><rect width="10" height="10"/></svg>',
        ];

        yield 'Removes aria-expanded attribute' => [
            '<svg aria-expanded="false"><rect width="20" height="20"/></svg>',
            '<svg><rect width="20" height="20"/></svg>',
        ];

        yield 'Removes aria-flowto attribute' => [
            '<svg aria-flowto="target1"><circle cx="10" cy="10" r="5"/></svg>',
            '<svg><circle cx="10" cy="10" r="5"/></svg>',
        ];

        yield 'Removes aria-grabbed attribute' => [
            '<svg aria-grabbed="true"><rect width="25" height="25"/></svg>',
            '<svg><rect width="25" height="25"/></svg>',
        ];

        yield 'Removes aria-haspopup attribute' => [
            '<svg aria-haspopup="menu"><rect width="20" height="20"/></svg>',
            '<svg><rect width="20" height="20"/></svg>',
        ];

        yield 'Removes aria-invalid attribute' => [
            '<svg aria-invalid="grammar"><rect width="10" height="10"/></svg>',
            '<svg><rect width="10" height="10"/></svg>',
        ];

        yield 'Removes aria-keyshortcuts attribute' => [
            '<svg aria-keyshortcuts="Ctrl+S"><rect width="15" height="15"/></svg>',
            '<svg><rect width="15" height="15"/></svg>',
        ];

        yield 'Removes aria-label attribute with empty value' => [
            '<svg aria-label=""><circle cx="5" cy="5" r="3"/></svg>',
            '<svg><circle cx="5" cy="5" r="3"/></svg>',
        ];

        yield 'Removes aria-labelledby attribute' => [
            '<svg aria-labelledby="title1"><rect width="10" height="10"/></svg>',
            '<svg><rect width="10" height="10"/></svg>',
        ];

        yield 'Removes aria-level attribute' => [
            '<svg aria-level="2"><rect width="12" height="12"/></svg>',
            '<svg><rect width="12" height="12"/></svg>',
        ];

        yield 'Removes aria-live attribute' => [
            '<svg aria-live="polite"><rect width="8" height="8"/></svg>',
            '<svg><rect width="8" height="8"/></svg>',
        ];

        yield 'Removes aria-modal attribute' => [
            '<svg aria-modal="true"><rect width="16" height="16"/></svg>',
            '<svg><rect width="16" height="16"/></svg>',
        ];

        yield 'Removes aria-multiline attribute' => [
            '<svg aria-multiline="false"><rect width="14" height="14"/></svg>',
            '<svg><rect width="14" height="14"/></svg>',
        ];

        yield 'Removes aria-multiselectable attribute' => [
            '<svg aria-multiselectable="true"><rect width="18" height="18"/></svg>',
            '<svg><rect width="18" height="18"/></svg>',
        ];

        yield 'Removes aria-orientation attribute' => [
            '<svg aria-orientation="horizontal"><rect width="20" height="10"/></svg>',
            '<svg><rect width="20" height="10"/></svg>',
        ];

        yield 'Removes aria-owns attribute' => [
            '<svg aria-owns="item1 item2"><rect width="15" height="15"/></svg>',
            '<svg><rect width="15" height="15"/></svg>',
        ];

        yield 'Removes aria-placeholder attribute' => [
            '<svg aria-placeholder="Enter text"><rect width="10" height="10"/></svg>',
            '<svg><rect width="10" height="10"/></svg>',
        ];

        yield 'Removes aria-posinset and aria-setsize attributes' => [
            '<svg aria-posinset="2" aria-setsize="5"><rect width="12" height="12"/></svg>',
            '<svg><rect width="12" height="12"/></svg>',
        ];

        yield 'Removes aria-pressed attribute' => [
            '<svg aria-pressed="mixed"><rect width="14" height="14"/></svg>',
            '<svg><rect width="14" height="14"/></svg>',
        ];

        yield 'Removes aria-readonly attribute' => [
            '<svg aria-readonly="true"><rect width="10" height="10"/></svg>',
            '<svg><rect width="10" height="10"/></svg>',
        ];

        yield 'Removes aria-relevant attribute' => [
            '<svg aria-relevant="additions"><rect width="8" height="8"/></svg>',
            '<svg><rect width="8" height="8"/></svg>',
        ];

        yield 'Removes aria-required attribute' => [
            '<svg aria-required="true"><rect width="12" height="12"/></svg>',
            '<svg><rect width="12" height="12"/></svg>',
        ];

        yield 'Removes aria-roledescription attribute' => [
            '<svg aria-roledescription="slider"><rect width="15" height="15"/></svg>',
            '<svg><rect width="15" height="15"/></svg>',
        ];

        yield 'Removes aria-rowcount and aria-rowindex attributes' => [
            '<svg aria-rowcount="4" aria-rowindex="1"><rect width="20" height="20"/></svg>',
            '<svg><rect width="20" height="20"/></svg>',
        ];

        yield 'Removes aria-rowspan attribute' => [
            '<svg aria-rowspan="2"><rect width="10" height="10"/></svg>',
            '<svg><rect width="10" height="10"/></svg>',
        ];

        yield 'Removes aria-selected attribute' => [
            '<svg aria-selected="true"><rect width="18" height="18"/></svg>',
            '<svg><rect width="18" height="18"/></svg>',
        ];

        yield 'Removes aria-setsize attribute' => [
            '<svg aria-setsize="5"><rect width="16" height="16"/></svg>',
            '<svg><rect width="16" height="16"/></svg>',
        ];

        yield 'Removes aria-sort attribute' => [
            '<svg aria-sort="ascending"><rect width="12" height="12"/></svg>',
            '<svg><rect width="12" height="12"/></svg>',
        ];

        yield 'Removes aria-valuemax, aria-valuemin, aria-valuenow, aria-valuetext attributes' => [
            '<svg aria-valuemax="100" aria-valuemin="0" aria-valuenow="50" aria-valuetext="half"><rect width="10" height="10"/></svg>',
            '<svg><rect width="10" height="10"/></svg>',
        ];

        yield 'Removes aria attributes on deeply nested elements' => [
            '<svg><g><g><rect aria-label="deep" aria-hidden="true" width="10" height="10"/></g></g></svg>',
            '<svg><g><g><rect width="10" height="10"/></g></g></svg>',
        ];

        yield 'Removes aria attributes when mixed with data-* attributes' => [
            '<svg aria-label="icon" data-test="value"><rect width="20" height="20"/></svg>',
            '<svg data-test="value"><rect width="20" height="20"/></svg>',
        ];

        yield 'Removes aria attributes from multiple sibling elements' => [
            '<svg><rect aria-label="first"/><circle aria-hidden="true"/><ellipse aria-checked="true"/></svg>',
            '<svg><rect/><circle/><ellipse/></svg>',
        ];

        yield 'Removes ARIA attributes with extra spaces in tag' => [
            '<svg   aria-label="spaced"   role="img"   ><rect width="10" height="10"/></svg>',
            '<svg><rect width="10" height="10"/></svg>',
        ];

        yield 'Removes ARIA attributes with uppercase and mixed-case tags' => [
            '<SVG ARIA-LABEL="icon" ROLE="img"><RECT width="10" height="10"/></SVG>',
            '<SVG><RECT width="10" height="10"/></SVG>',
        ];

        yield 'Removes ARIA attributes on empty elements' => [
            '<svg><rect aria-label="empty"/></svg>',
            '<svg><rect/></svg>',
        ];

        yield 'Removes ARIA attributes when combined with style and class attributes' => [
            '<svg aria-label="styled" class="my-svg" style="fill:red"><rect aria-hidden="true" width="10" height="10"/></svg>',
            '<svg class="my-svg" style="fill:red"><rect width="10" height="10"/></svg>',
        ];

        yield 'Removes ARIA attributes from <defs> elements with nested content' => [
            '<svg><defs><g aria-label="nested"><path aria-hidden="true" d="M0 0L10 10"/></g></defs></svg>',
            '<svg><defs><g><path d="M0 0L10 10"/></g></defs></svg>',
        ];

        yield 'Removes ARIA attributes from self-closing nested elements in defs' => [
            '<svg><defs><rect aria-label="defs" role="presentation" width="10" height="10"/></defs></svg>',
            '<svg><defs><rect width="10" height="10"/></defs></svg>',
        ];

        yield 'Removes ARIA attributes with boolean false values' => [
            '<svg aria-hidden="false" aria-disabled="false"><rect width="10" height="10"/></svg>',
            '<svg><rect width="10" height="10"/></svg>',
        ];

        yield 'Removes ARIA attributes with numeric values' => [
            '<svg aria-valuenow="5" aria-valuemin="0" aria-valuemax="10"><rect width="10" height="10"/></svg>',
            '<svg><rect width="10" height="10"/></svg>',
        ];

        yield 'Removes ARIA attributes combined with role="none"' => [
            '<svg role="none" aria-label="none"><rect width="20" height="20"/></svg>',
            '<svg><rect width="20" height="20"/></svg>',
        ];

        yield 'Removes ARIA attributes from <symbol> elements' => [
            '<svg><symbol role="img" aria-hidden="true"><rect width="10" height="10"/></symbol></svg>',
            '<svg><symbol><rect width="10" height="10"/></symbol></svg>',
        ];

        yield 'Removes ARIA attributes from <use> elements' => [
            '<svg><use href="#icon" role="presentation" aria-label="use-icon"/></svg>',
            '<svg><use href="#icon"/></svg>',
        ];

        yield 'Removes ARIA attributes from <circle> elements with fill and stroke' => [
            '<svg><circle aria-label="circle" role="img" cx="50" cy="50" r="25" fill="blue" stroke="black"/></svg>',
            '<svg><circle cx="50" cy="50" r="25" fill="blue" stroke="black"/></svg>',
        ];

        yield 'Removes ARIA attributes when elements contain comments' => [
            '<svg><!-- Comment --><rect aria-label="commented"/></svg>',
            '<svg><!-- Comment --><rect/></svg>',
        ];

        yield 'Removes ARIA attributes when multiple attributes are on single line' => [
            '<svg aria-label="multi" role="img" width="50" height="50"><rect aria-hidden="true" width="10" height="10"/></svg>',
            '<svg width="50" height="50"><rect width="10" height="10"/></svg>',
        ];

        yield 'Removes ARIA attributes when attributes have extra quotes' => [
            '<svg aria-label=\'icon\' role="img"><rect aria-hidden="true"/></svg>',
            '<svg><rect/></svg>',
        ];
    }
}
