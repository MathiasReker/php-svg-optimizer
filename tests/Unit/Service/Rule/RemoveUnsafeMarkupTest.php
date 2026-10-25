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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnsafeMarkup;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @no-named-arguments
 */
#[CoversClass(RemoveUnsafeMarkup::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(SvgAttribute::class)]
#[CoversClass(SvgTag::class)]
final class RemoveUnsafeMarkupTest extends TestCase
{
    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[DataProvider('provideOptimizeCases')]
    #[Test]
    public function optimize(string $content, string $expectedSvg): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($content));
        $svgOptimizer->addRule(new RemoveUnsafeMarkup());

        self::assertSame($expectedSvg, $svgOptimizer->optimize()->getContent());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
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

    #[Test]
    public function ruleIsNotRisky(): void
    {
        self::assertFalse(RemoveUnsafeMarkup::isRisky());
    }

    #[Test]
    public function ruleSkipsTheSizeCheck(): void
    {
        self::assertFalse(RemoveUnsafeMarkup::shouldCheckSize());
    }
}
