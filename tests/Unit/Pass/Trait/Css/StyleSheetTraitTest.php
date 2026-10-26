<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Pass\Trait\Css;

use MathiasReker\PhpSvgOptimizer\Pass\ConvertInlineStylesToAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\DeclarationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\SelectorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(StyleSheetTrait::class)]
#[UsesClass(ConvertInlineStylesToAttributes::class)]
#[UsesClass(Element::class)]
#[UsesTrait(AsciiCaseTrait::class)]
#[UsesTrait(DeclarationTrait::class)]
#[UsesTrait(SelectorTrait::class)]
final class StyleSheetTraitTest extends TestCase
{
    use StyleSheetTrait;

    /**
     * @param non-empty-string        $content  the document
     * @param array{bool, bool, bool} $expected whether the document may be sibling sensitive, may match attribute
     *                                          values and may match attributes
     */
    #[DataProvider('provideDetectsStyleRulesThatMayDependOnTheMarkupCases')]
    #[Test]
    public function detectsStyleRulesThatMayDependOnTheMarkup(string $content, array $expected): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML($content);

        self::assertSame(
            $expected,
            [
                $this->hasStructureSensitiveStyleSheet($domDocument),
                $this->maySelectByAttributeValue($domDocument),
                $this->maySelectByAttribute($domDocument),
            ],
        );
    }

    /**
     * @return iterable<string, array{non-empty-string, array{bool, bool, bool}}>
     */
    public static function provideDetectsStyleRulesThatMayDependOnTheMarkupCases(): iterable
    {
        yield 'no style sheet' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
            [false, false, false],
        ];

        yield 'style sheet without sibling or attribute selectors' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><rect/></svg>',
            [false, false, false],
        ];

        yield 'sibling combinator' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect+rect{fill:red}</style><rect/></svg>',
            [true, false, false],
        ];

        yield 'structural pseudo-class in the second style element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style>rect:nth-child(2){fill:red}</style><rect/></svg>',
            [true, false, false],
        ];

        yield 'escaped pseudo-class' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect:\6eth-child(2){fill:red}</style><rect/></svg>',
            [true, false, false],
        ];

        yield 'attribute presence selector' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[transform]{fill:red}</style><rect/></svg>',
            [false, false, true],
        ];

        yield 'attribute value selector' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[width="1.0"]{fill:red}</style><rect/></svg>',
            [false, true, true],
        ];

        yield 'xml-stylesheet instruction' => [
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
            [true, true, true],
        ];

        yield 'other processing instruction' => [
            '<?other href="a.css"?><svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
            [false, false, false],
        ];

        yield 'XHTML link element' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:h="http://www.w3.org/1999/xhtml"><h:link rel="stylesheet" href="a.css"/><rect/></svg>',
            [true, true, true],
        ];

        yield 'link element in a foreign object' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><link xmlns="http://www.w3.org/1999/xhtml" rel="stylesheet" href="a.css"/></foreignObject><rect/></svg>',
            [true, true, true],
        ];

        yield 'import rule' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@IMPORT url(a.css);</style><rect/></svg>',
            [true, true, true],
        ];

        yield 'escaped at-rule' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@\69mport url(a.css);</style><rect/></svg>',
            [true, true, true],
        ];

        yield 'other at-rule' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@media print{rect{fill:red}}</style><rect/></svg>',
            [false, false, false],
        ];
    }
}
