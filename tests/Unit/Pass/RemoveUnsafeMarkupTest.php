<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Pass;

use MathiasReker\PhpSvgOptimizer\Exception\InvalidSvgException;
use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Pass\Pipeline;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveUnsafeMarkup;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
use MathiasReker\PhpSvgOptimizer\Xml\Parser;
use MathiasReker\PhpSvgOptimizer\Xml\Serializer;
use MathiasReker\PhpSvgOptimizer\Xml\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @no-named-arguments
 */
#[CoversClass(RemoveUnsafeMarkup::class)]
#[CoversTrait(ElementTrait::class)]
#[UsesClass(Attribute::class)]
#[UsesClass(Element::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(Parser::class)]
#[UsesClass(Pipeline::class)]
#[UsesClass(Serializer::class)]
#[UsesClass(SvgValidator::class)]
final class RemoveUnsafeMarkupTest extends TestCase
{
    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideRemovesUnsafeMarkupCases')]
    #[Test]
    public function removesUnsafeMarkup(string $input, string $expectedSvg): void
    {
        $pipeline = new Pipeline([new RemoveUnsafeMarkup()]);

        self::assertSame($expectedSvg, $pipeline->run($input));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideRemovesUnsafeMarkupCases(): iterable
    {
        yield 'Removes processing instructions' => [
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg"><?php echo 1; ?><g><?x y?></g><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g/><rect/></svg>',
        ];

        yield 'Removes comments that look like markup' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><!-- <b> --><!--</style>--><!--<!x--><!--<?x--><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes comments that close early in an HTML parser' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><!-->x--><!--->y--><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Keeps other comments' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><!-- a > b --><!-- x < 1 --><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><!-- a > b --><!-- x < 1 --><rect/></svg>',
        ];

        yield 'Removes markup that comes from an entity' => [
            '<!DOCTYPE svg [<!ENTITY p "<?pi x?><!--<b>-->">]><svg xmlns="http://www.w3.org/2000/svg"><g>&p;</g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g/></svg>',
        ];

        yield 'Leaves scripts, unsafe URLs, foreign content and style sheets to the other rules' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><a href="javascript:x"><rect onclick="x()"/></a><foreignObject/><style>@import url(a.css);</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><a href="javascript:x"><rect onclick="x()"/></a><foreignObject/><style>@import url(a.css);</style></svg>',
        ];
    }
}
