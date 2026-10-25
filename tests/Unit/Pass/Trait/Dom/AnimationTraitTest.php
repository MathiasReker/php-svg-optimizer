<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Pass\Trait\Dom;

use MathiasReker\PhpSvgOptimizer\Pass\ConvertEllipsesToCircles;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AnimationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AttributeNodeTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementRenameTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberLiteralTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ConvertEllipsesToCircles::class)]
#[CoversTrait(AnimationTrait::class)]
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(AttributeNodeTrait::class)]
#[CoversTrait(ElementRenameTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(NumberLiteralTrait::class)]
#[CoversTrait(ScriptingTrait::class)]
#[CoversTrait(StyleSheetTrait::class)]
#[UsesClass(Element::class)]
final class AnimationTraitTest extends TestCase
{
    use AnimationTrait;

    #[Test]
    public function collectsTheAttributeNamesTargetedByAnimations(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg"><rect><animate attributeName=" fill "/><set attributeName="x"/></rect></svg>');

        self::assertSame(['fill' => true, 'x' => true], $this->animatedAttributeNames($domDocument));
    }

    #[Test]
    public function collectsAnimatedAttributeNamesInLinearTimeOfTheNamespaceDeclarations(): void
    {
        $small = $this->secondsToCollectAnimatedAttributeNames(2_000);
        $large = $this->secondsToCollectAnimatedAttributeNames(16_000);

        self::assertLessThan(10 * max($small, 0.05), $large);
    }

    #[Test]
    public function collectsEveryElementTargetedByAnAnimation(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg"><rect id="a"/><rect xml:id="b"/><rect id="c"/><animate href="#a"/><set href="#b"/></svg>');

        self::assertSame(['a', 'b'], array_map(static fn (\DOMElement $domElement): string => $domElement->getAttribute('id') . $domElement->getAttributeNS('http://www.w3.org/XML/1998/namespace', 'id'), $this->animationTargets($domDocument)));
    }

    private function secondsToCollectAnimatedAttributeNames(int $count): float
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg" ' . implode(' ', array_map(static fn (int $i): string => 'xmlns:n' . $i . '="urn:' . $i . '"', range(1, $count))) . '><rect><set attributeName="x"/></rect></svg>');

        $start = hrtime(true);
        $this->animatedAttributeNames($domDocument);

        return (hrtime(true) - $start) / 1e9;
    }
}
