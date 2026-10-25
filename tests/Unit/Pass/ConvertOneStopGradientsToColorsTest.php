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
use MathiasReker\PhpSvgOptimizer\Pass\ConvertOneStopGradientsToColors;
use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Pass\Pipeline;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Color\NamedColorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\ClassSelectorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\DeclarationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\SelectorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AnimationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\DefinitionElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\FragmentTargetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberLiteralTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Paint\GradientLinkTrait;
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
 */
#[CoversClass(ConvertOneStopGradientsToColors::class)]
#[CoversClass(Parser::class)]
#[CoversClass(Pipeline::class)]
#[CoversClass(SvgValidator::class)]
#[CoversTrait(AnimationTrait::class)]
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(ClassSelectorTrait::class)]
#[CoversTrait(DeclarationTrait::class)]
#[CoversTrait(DefinitionElementTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(FragmentTargetTrait::class)]
#[CoversTrait(GradientLinkTrait::class)]
#[CoversTrait(IdReferenceTrait::class)]
#[CoversTrait(IdTrait::class)]
#[CoversTrait(NamedColorTrait::class)]
#[CoversTrait(NumberLiteralTrait::class)]
#[CoversTrait(ScriptingTrait::class)]
#[CoversTrait(SelectorTrait::class)]
#[CoversTrait(StyleSheetTrait::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(Serializer::class)]
final class ConvertOneStopGradientsToColorsTest extends TestCase
{
    private const string SVG = '<svg xmlns="http://www.w3.org/2000/svg">';

    private const string SVG_XLINK = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">';

    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideApplyCases')]
    #[Test]
    public function apply(string $input, string $expected): void
    {
        $pipeline = new Pipeline([new ConvertOneStopGradientsToColors()]);

        $actual = $pipeline->run($input);
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideApplyCases(): iterable
    {
        yield 'Keeps a gradient of a zero-sized resource library, which other documents may reference' => [
            '<svg xmlns="http://www.w3.org/2000/svg" width="0" height="0"><defs><linearGradient id="g"><stop stop-color="red"/></linearGradient></defs><rect width="5" height="5" fill="url(#g)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" width="0" height="0"><defs><linearGradient id="g"><stop stop-color="red"/></linearGradient></defs><rect width="5" height="5" fill="url(#g)"/></svg>',
        ];

        yield 'Leaves the document untouched with an event handler' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><linearGradient id="g"><stop stop-color="red"/></linearGradient><rect fill="url(#g)" width="5" height="5" onclick="f()"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><linearGradient id="g"><stop stop-color="red"/></linearGradient><rect fill="url(#g)" width="5" height="5" onclick="f()"/></svg>',
        ];

        yield from self::provideConvertedCases();
        yield from self::provideLinkCases();
        yield from self::provideStopSkipCases();
        yield from self::provideReferenceSkipCases();
        yield from self::provideDocumentSkipCases();
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    private static function provideConvertedCases(): iterable
    {
        yield 'Converts a gradient referenced after a paint that is not a gradient' => [
            self::SVG . '<defs><pattern id="p" width="1" height="1"/><linearGradient id="g"><stop stop-color="red"/></linearGradient></defs><rect fill="url(#p)"/><rect fill="url(#g)"/></svg>',
            self::SVG . '<defs><pattern id="p" width="1" height="1"/></defs><rect fill="url(#p)"/><rect fill="red"/></svg>',
        ];

        yield 'Converts a linear gradient fill and removes the emptied defs' => [
            self::SVG . '<defs><linearGradient id="g"><stop offset="0" stop-color="#f00"/></linearGradient></defs><rect fill="url(#g)"/></svg>',
            self::SVG . '<rect fill="#f00"/></svg>',
        ];

        yield 'Converts a radial gradient stroke' => [
            self::SVG . '<defs><radialGradient id="g" cx="0.3" r="2"><stop offset="1" stop-color="blue"/></radialGradient></defs><circle r="5" stroke="url(#g)"/></svg>',
            self::SVG . '<circle r="5" stroke="blue"/></svg>',
        ];

        yield 'Converts every fill and stroke reference' => [
            self::SVG . '<defs><linearGradient id="g"><stop stop-color="#abc"/></linearGradient></defs><rect fill="url(#g)" stroke="url(#g)"/><circle fill="url(#g)"/><ellipse stroke="url(#g)"/></svg>',
            self::SVG . '<rect fill="#abc" stroke="#abc"/><circle fill="#abc"/><ellipse stroke="#abc"/></svg>',
        ];

        yield 'Uses black for a stop without a stop color' => [
            self::SVG . '<defs><linearGradient id="g"><stop offset="0"/></linearGradient></defs><rect fill="url(#g)"/></svg>',
            self::SVG . '<rect fill="#000"/></svg>',
        ];

        yield 'Converts a stop with stop-opacity 1' => [
            self::SVG . '<defs><linearGradient id="g"><stop stop-color="red" stop-opacity="1"/></linearGradient></defs><rect fill="url(#g)"/></svg>',
            self::SVG . '<rect fill="red"/></svg>',
        ];

        yield 'Converts a stop with stop-opacity 1.0 and surrounding whitespace' => [
            self::SVG . '<defs><linearGradient id="g"><stop stop-color="red" stop-opacity=" 1.0 "/></linearGradient></defs><rect fill="url(#g)"/></svg>',
            self::SVG . '<rect fill="red"/></svg>',
        ];

        yield 'Converts a stop with stop-opacity +1e0' => [
            self::SVG . '<defs><linearGradient id="g"><stop stop-color="red" stop-opacity="+1e0"/></linearGradient></defs><rect fill="url(#g)"/></svg>',
            self::SVG . '<rect fill="red"/></svg>',
        ];

        yield 'Trims the stop color' => [
            self::SVG . '<defs><linearGradient id="g"><stop stop-color=" red "/></linearGradient></defs><rect fill="url(#g)"/></svg>',
            self::SVG . '<rect fill="red"/></svg>',
        ];

        yield 'Keeps the case of a named color' => [
            self::SVG . '<defs><linearGradient id="g"><stop stop-color="DarkSlateGray"/></linearGradient></defs><rect fill="url(#g)"/></svg>',
            self::SVG . '<rect fill="DarkSlateGray"/></svg>',
        ];

        yield 'Converts transparent' => [
            self::SVG . '<defs><linearGradient id="g"><stop stop-color="transparent"/></linearGradient></defs><rect fill="url(#g)"/></svg>',
            self::SVG . '<rect fill="transparent"/></svg>',
        ];

        yield 'Converts hex colors with an alpha channel' => [
            self::SVG . '<defs><linearGradient id="a"><stop stop-color="#F008"/></linearGradient><linearGradient id="b"><stop stop-color="#11223344"/></linearGradient></defs><rect fill="url(#a)" stroke="url(#b)"/></svg>',
            self::SVG . '<rect fill="#F008" stroke="#11223344"/></svg>',
        ];

        yield 'Converts quoted and spaced url references' => [
            self::SVG . '<defs><linearGradient id="g"><stop stop-color="red"/></linearGradient></defs><rect fill=" url( \'#g\' ) " stroke="URL(&quot;#g&quot;)"/></svg>',
            self::SVG . '<rect fill="red" stroke="red"/></svg>',
        ];

        yield 'Converts a gradient outside defs' => [
            self::SVG . '<linearGradient id="g"><stop stop-color="red"/></linearGradient><rect fill="url(#g)"/></svg>',
            self::SVG . '<rect fill="red"/></svg>',
        ];

        yield 'Ignores whitespace and comments around the stop' => [
            self::SVG . '<defs><linearGradient id="g"> <!-- c --> <stop stop-color="red"/> </linearGradient></defs><rect fill="url(#g)"/></svg>',
            self::SVG . '<rect fill="red"/></svg>',
        ];

        yield 'Keeps other definitions in defs' => [
            self::SVG . '<defs><linearGradient id="g"><stop stop-color="red"/></linearGradient><clipPath id="c"><rect/></clipPath></defs><rect fill="url(#g)" clip-path="url(#c)"/></svg>',
            self::SVG . '<defs><clipPath id="c"><rect/></clipPath></defs><rect fill="red" clip-path="url(#c)"/></svg>',
        ];

        yield 'Keeps an emptied defs with an id' => [
            self::SVG . '<defs id="d"><linearGradient id="g"><stop stop-color="red"/></linearGradient></defs><rect fill="url(#g)"/></svg>',
            self::SVG . '<defs id="d"/><rect fill="red"/></svg>',
        ];

        yield 'Converts a gradient with a numeric id' => [
            self::SVG . '<defs><linearGradient id="1"><stop stop-color="red"/></linearGradient></defs><rect fill="url(#1)"/></svg>',
            self::SVG . '<rect fill="red"/></svg>',
        ];

        yield 'Converts only the gradients that qualify' => [
            self::SVG . '<defs><linearGradient id="a"><stop stop-color="red"/></linearGradient><linearGradient id="b"><stop stop-color="red"/><stop offset="1" stop-color="blue"/></linearGradient></defs><rect fill="url(#a)" stroke="url(#b)"/></svg>',
            self::SVG . '<defs><linearGradient id="b"><stop stop-color="red"/><stop offset="1" stop-color="blue"/></linearGradient></defs><rect fill="red" stroke="url(#b)"/></svg>',
        ];

        yield 'Converts a stop with a class when there is no style element' => [
            self::SVG . '<defs><linearGradient id="g"><stop class="s" stop-color="red"/></linearGradient></defs><rect fill="url(#g)"/></svg>',
            self::SVG . '<rect fill="red"/></svg>',
        ];

        yield 'Keeps a bounding box gradient on a polyline, path or group' => [
            self::SVG . '<defs><linearGradient id="g"><stop stop-color="red"/></linearGradient></defs><polyline points="0 0 1 1" stroke="url(#g)"/></svg>',
            self::SVG . '<defs><linearGradient id="g"><stop stop-color="red"/></linearGradient></defs><polyline points="0 0 1 1" stroke="url(#g)"/></svg>',
        ];

        yield 'Keeps a bounding box gradient on the stroke of a straight path' => [
            self::SVG . '<defs><linearGradient id="g" gradientUnits="objectBoundingBox"><stop stop-color="red"/></linearGradient></defs><path d="M0 0H10" stroke="url(#g)"/></svg>',
            self::SVG . '<defs><linearGradient id="g" gradientUnits="objectBoundingBox"><stop stop-color="red"/></linearGradient></defs><path d="M0 0H10" stroke="url(#g)"/></svg>',
        ];

        yield 'Keeps a bounding box gradient when one reference is not a box shape' => [
            self::SVG . '<defs><linearGradient id="g"><stop stop-color="red"/></linearGradient></defs><rect fill="url(#g)"/><text fill="url(#g)">a</text></svg>',
            self::SVG . '<defs><linearGradient id="g"><stop stop-color="red"/></linearGradient></defs><rect fill="url(#g)"/><text fill="url(#g)">a</text></svg>',
        ];

        yield 'Converts a gradient in user space on any element' => [
            self::SVG . '<defs><linearGradient id="g" gradientUnits="userSpaceOnUse"><stop stop-color="red"/></linearGradient></defs><path d="M0 0H10" stroke="url(#g)"/><g fill="url(#g)"><text>a</text></g></svg>',
            self::SVG . '<path d="M0 0H10" stroke="red"/><g fill="red"><text>a</text></g></svg>',
        ];

        yield 'Converts a gradient that inherits user space units through href' => [
            self::SVG . '<defs><linearGradient id="a" gradientUnits=" userSpaceOnUse "><stop stop-color="red"/></linearGradient><linearGradient id="b" href="#a"/></defs><line x2="10" stroke="url(#b)"/></svg>',
            self::SVG . '<defs><linearGradient id="a" gradientUnits=" userSpaceOnUse "><stop stop-color="red"/></linearGradient></defs><line x2="10" stroke="red"/></svg>',
        ];

        yield 'Uses the units of the gradient itself over the linked gradient' => [
            self::SVG . '<defs><linearGradient id="a" gradientUnits="userSpaceOnUse"><stop stop-color="red"/></linearGradient><linearGradient id="b" href="#a" gradientUnits="objectBoundingBox"/></defs><line x2="10" stroke="url(#b)"/></svg>',
            self::SVG . '<defs><linearGradient id="a" gradientUnits="userSpaceOnUse"><stop stop-color="red"/></linearGradient><linearGradient id="b" href="#a" gradientUnits="objectBoundingBox"/></defs><line x2="10" stroke="url(#b)"/></svg>',
        ];

        yield 'Converts a bounding box gradient on circles and ellipses' => [
            self::SVG . '<defs><radialGradient id="g"><stop stop-color="red"/></radialGradient></defs><circle r="1" fill="url(#g)"/><ellipse rx="1" ry="2" stroke="url(#g)"/></svg>',
            self::SVG . '<circle r="1" fill="red"/><ellipse rx="1" ry="2" stroke="red"/></svg>',
        ];

        yield 'Converts a gradient with translations and rotations' => [
            self::SVG . '<defs><linearGradient id="g" gradientTransform=" translate(1, 2),rotate(30) translate (3) "><stop stop-color="red"/></linearGradient></defs><rect fill="url(#g)"/></svg>',
            self::SVG . '<rect fill="red"/></svg>',
        ];

        yield 'Skips a gradient with a transform that may be singular' => [
            self::SVG . '<defs><linearGradient id="g" gradientTransform="rotate(30) scale(0)"><stop stop-color="red"/></linearGradient></defs><rect fill="url(#g)"/></svg>',
            self::SVG . '<defs><linearGradient id="g" gradientTransform="rotate(30) scale(0)"><stop stop-color="red"/></linearGradient></defs><rect fill="url(#g)"/></svg>',
        ];

        yield 'Skips a gradient that inherits a transform that may be singular through href' => [
            self::SVG . '<defs><linearGradient id="a" gradientTransform="matrix(1 1 1 1 0 0)"/><linearGradient id="b" href="#a"><stop stop-color="red"/></linearGradient></defs><rect fill="url(#b)"/></svg>',
            self::SVG . '<defs><linearGradient id="a" gradientTransform="matrix(1 1 1 1 0 0)"/><linearGradient id="b" href="#a"><stop stop-color="red"/></linearGradient></defs><rect fill="url(#b)"/></svg>',
        ];

        yield 'Uses the transform of the gradient itself over the linked gradient' => [
            self::SVG . '<defs><linearGradient id="a" gradientTransform="scale(0)"/><linearGradient id="b" href="#a" gradientTransform="rotate(5)"><stop stop-color="red"/></linearGradient></defs><rect fill="url(#b)"/></svg>',
            self::SVG . '<defs><linearGradient id="a" gradientTransform="scale(0)"/></defs><rect fill="red"/></svg>',
        ];

        yield 'Skips a gradient with a style attribute, which may set a transform' => [
            self::SVG . '<defs><linearGradient id="g" style="transform:scale(0)"><stop stop-color="red"/></linearGradient></defs><rect fill="url(#g)"/></svg>',
            self::SVG . '<defs><linearGradient id="g" style="transform:scale(0)"><stop stop-color="red"/></linearGradient></defs><rect fill="url(#g)"/></svg>',
        ];

        yield 'Skips a gradient linking to a gradient with a style attribute' => [
            self::SVG . '<defs><linearGradient id="a" style="transform:scale(0)"/><linearGradient id="b" href="#a"><stop stop-color="red"/></linearGradient></defs><rect fill="url(#b)"/></svg>',
            self::SVG . '<defs><linearGradient id="a" style="transform:scale(0)"/><linearGradient id="b" href="#a"><stop stop-color="red"/></linearGradient></defs><rect fill="url(#b)"/></svg>',
        ];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    private static function provideLinkCases(): iterable
    {
        yield 'Inherits the stop through href and keeps the linked gradient' => [
            self::SVG . '<defs><linearGradient id="a"><stop stop-color="red"/></linearGradient><linearGradient id="b" href="#a" x2="0"/></defs><rect fill="url(#b)"/></svg>',
            self::SVG . '<defs><linearGradient id="a"><stop stop-color="red"/></linearGradient></defs><rect fill="red"/></svg>',
        ];

        yield 'Inherits the stop through xlink:href' => [
            self::SVG_XLINK . '<defs><linearGradient id="a"><stop stop-color="red"/></linearGradient><radialGradient id="b" xlink:href="#a"/></defs><rect fill="url(#b)"/></svg>',
            self::SVG_XLINK . '<defs><linearGradient id="a"><stop stop-color="red"/></linearGradient></defs><rect fill="red"/></svg>',
        ];

        yield 'Inherits the stop through equal href and xlink:href' => [
            self::SVG_XLINK . '<defs><linearGradient id="a"><stop stop-color="red"/></linearGradient><linearGradient id="b" href="#a" xlink:href="#a"/></defs><rect fill="url(#b)"/></svg>',
            self::SVG_XLINK . '<defs><linearGradient id="a"><stop stop-color="red"/></linearGradient></defs><rect fill="red"/></svg>',
        ];

        yield 'Inherits the stop through a chain of links' => [
            self::SVG . '<defs><linearGradient id="a"><stop stop-color="red"/></linearGradient><linearGradient id="b" href="#a"/><linearGradient id="c" href="#b"/></defs><rect fill="url(#c)"/></svg>',
            self::SVG . '<defs><linearGradient id="a"><stop stop-color="red"/></linearGradient><linearGradient id="b" href="#a"/></defs><rect fill="red"/></svg>',
        ];

        yield 'Follows a chain of sixteen links' => [
            self::linkChain(16),
            self::SVG . '<linearGradient id="g0"><stop stop-color="red"/></linearGradient>' . self::chainLinks(16, false) . '<rect fill="red"/></svg>',
        ];

        yield 'Skips a chain of seventeen links' => [
            self::linkChain(17),
            self::linkChain(17),
        ];

        yield 'Uses its own stop instead of the linked stops' => [
            self::SVG . '<defs><linearGradient id="a"><stop stop-color="red"/><stop offset="1" stop-color="blue"/></linearGradient><linearGradient id="b" href="#a"><stop stop-color="lime"/></linearGradient></defs><rect fill="url(#b)"/></svg>',
            self::SVG . '<defs><linearGradient id="a"><stop stop-color="red"/><stop offset="1" stop-color="blue"/></linearGradient></defs><rect fill="lime"/></svg>',
        ];

        yield 'Skips a gradient inheriting two stops' => [
            self::SVG . '<defs><linearGradient id="a"><stop stop-color="red"/><stop offset="1" stop-color="blue"/></linearGradient><linearGradient id="b" href="#a"/></defs><rect fill="url(#b)"/></svg>',
            self::SVG . '<defs><linearGradient id="a"><stop stop-color="red"/><stop offset="1" stop-color="blue"/></linearGradient><linearGradient id="b" href="#a"/></defs><rect fill="url(#b)"/></svg>',
        ];

        yield 'Skips a gradient without stops or link' => [
            self::SVG . '<defs><linearGradient id="g"/></defs><rect fill="url(#g)"/></svg>',
            self::SVG . '<defs><linearGradient id="g"/></defs><rect fill="url(#g)"/></svg>',
        ];

        yield 'Skips a link to a non-gradient element' => [
            self::SVG . '<defs><pattern id="a"><stop stop-color="red"/></pattern><linearGradient id="b" href="#a"/></defs><rect fill="url(#b)"/></svg>',
            self::SVG . '<defs><pattern id="a"><stop stop-color="red"/></pattern><linearGradient id="b" href="#a"/></defs><rect fill="url(#b)"/></svg>',
        ];

        yield 'Skips a link to a missing element' => [
            self::SVG . '<defs><linearGradient id="b" href="#a"/></defs><rect fill="url(#b)"/></svg>',
            self::SVG . '<defs><linearGradient id="b" href="#a"/></defs><rect fill="url(#b)"/></svg>',
        ];

        yield 'Skips an external link' => [
            self::SVG . '<defs><linearGradient id="a"><stop stop-color="red"/></linearGradient><linearGradient id="b" href="other.svg#a"/></defs><rect fill="url(#b)"/></svg>',
            self::SVG . '<defs><linearGradient id="a"><stop stop-color="red"/></linearGradient><linearGradient id="b" href="other.svg#a"/></defs><rect fill="url(#b)"/></svg>',
        ];

        yield 'Skips a link to a path rather than a fragment' => [
            self::SVG . '<defs><linearGradient id="a"><stop stop-color="red"/></linearGradient><linearGradient id="b" href="/a"/></defs><rect fill="url(#b)"/></svg>',
            self::SVG . '<defs><linearGradient id="a"><stop stop-color="red"/></linearGradient><linearGradient id="b" href="/a"/></defs><rect fill="url(#b)"/></svg>',
        ];

        yield 'Skips conflicting href and xlink:href' => [
            self::SVG_XLINK . '<defs><linearGradient id="a"><stop stop-color="red"/></linearGradient><linearGradient id="c"><stop stop-color="red"/></linearGradient><linearGradient id="b" href="#a" xlink:href="#c"/></defs><rect fill="url(#b)"/></svg>',
            self::SVG_XLINK . '<defs><linearGradient id="a"><stop stop-color="red"/></linearGradient><linearGradient id="c"><stop stop-color="red"/></linearGradient><linearGradient id="b" href="#a" xlink:href="#c"/></defs><rect fill="url(#b)"/></svg>',
        ];

        yield 'Skips a link cycle' => [
            self::SVG . '<defs><linearGradient id="a" href="#b"/><linearGradient id="b" href="#a"/></defs><rect fill="url(#a)"/></svg>',
            self::SVG . '<defs><linearGradient id="a" href="#b"/><linearGradient id="b" href="#a"/></defs><rect fill="url(#a)"/></svg>',
        ];

        yield 'Skips a gradient that is the source of a link' => [
            self::SVG . '<defs><linearGradient id="a"><stop stop-color="red"/></linearGradient><linearGradient id="b" href="#a"><stop stop-color="red"/><stop offset="1"/></linearGradient></defs><rect fill="url(#a)" stroke="url(#b)"/></svg>',
            self::SVG . '<defs><linearGradient id="a"><stop stop-color="red"/></linearGradient><linearGradient id="b" href="#a"><stop stop-color="red"/><stop offset="1"/></linearGradient></defs><rect fill="url(#a)" stroke="url(#b)"/></svg>',
        ];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    private static function provideStopSkipCases(): iterable
    {
        $stops = [
            'two stops' => '<stop stop-color="red"/><stop offset="1" stop-color="red"/>',
            'a translucent stop' => '<stop stop-color="red" stop-opacity="0.5"/>',
            'a stop with a percentage opacity' => '<stop stop-color="red" stop-opacity="100%"/>',
            'a stop with an empty opacity' => '<stop stop-color="red" stop-opacity=""/>',
            'a stop with an opacity above 1' => '<stop stop-color="red" stop-opacity="10"/>',
            'a stop with a style' => '<stop style="stop-color:red"/>',
            'a stop with an empty style' => '<stop stop-color="red" style=""/>',
            'a currentColor stop' => '<stop stop-color="currentColor"/>',
            'an inherited stop color' => '<stop stop-color="inherit"/>',
            'a none stop color' => '<stop stop-color="none"/>',
            'an empty stop color' => '<stop stop-color=""/>',
            'an unknown color keyword' => '<stop stop-color="foo"/>',
            'a color keyword pattern' => '<stop stop-color="re."/>',
            'a color keyword with a non-ASCII letter' => '<stop stop-color="Khaki"/>',
            'a functional color' => '<stop stop-color="rgb(255,0,0)"/>',
            'a custom property color' => '<stop stop-color="var(--c)"/>',
            'a hex color of invalid length' => '<stop stop-color="#12345"/>',
            'a hex color with invalid digits' => '<stop stop-color="#ggg"/>',
            'a color with an ICC fallback' => '<stop stop-color="red icc-color(p, 1)"/>',
            'a stop with an id' => '<stop id="s" stop-color="red"/>',
            'a stop with an xml:id' => '<stop xml:id="s" stop-color="red"/>',
            'an animated stop' => '<stop stop-color="red"><animate attributeName="stop-color" to="blue" dur="1s"/></stop>',
            'a stop next to an animation' => '<stop stop-color="red"/><animate attributeName="x1" to="1" dur="1s"/>',
            'a non-stop child only' => '<desc>d</desc>',
        ];

        foreach ($stops as $name => $stop) {
            $svg = self::SVG . '<defs><linearGradient id="g">' . $stop . '</linearGradient></defs><rect fill="url(#g)"/></svg>';

            yield 'Skips ' . $name => [$svg, $svg];
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    private static function provideReferenceSkipCases(): iterable
    {
        $gradient = '<defs><linearGradient id="g"><stop stop-color="red"/></linearGradient></defs>';

        $references = [
            'a style attribute reference' => '<rect fill="url(#g)"/><rect style="fill:url(#g)"/>',
            'a fallback paint' => '<rect fill="url(#g) red"/>',
            'a paint with a leading value' => '<rect fill="red url(#g)"/>',
            'a mask reference' => '<rect fill="url(#g)" mask="url(#g)"/>',
            'an animation value' => '<rect fill="url(#g)"><set attributeName="fill" to="url(#g)"/></rect>',
            'an href reference' => '<rect fill="url(#g)"/><use href="#g"/>',
            'an aria reference' => '<rect fill="url(#g)" aria-labelledby="x g"/>',
            'a timing reference' => '<rect fill="url(#g)"><animate begin="g.end" attributeName="x" to="1" dur="1s"/></rect>',
            'a namespaced fill attribute' => '<rect xmlns:x="urn:x" fill="url(#g)" x:fill="url(#g)"/>',
            'a line' => '<line x2="10" stroke="url(#g)"/>',
            'a line among other references' => '<rect fill="url(#g)"/><line x2="10" stroke="url(#g)"/>',
            'an unreferenced gradient' => '<rect fill="red"/>',
            'a reference to a missing gradient' => '<rect fill="url(#h)"/>',
            'an external reference' => '<rect fill="url(other.svg#g)"/>',
        ];

        foreach ($references as $name => $reference) {
            $svg = self::SVG . $gradient . $reference . '</svg>';

            yield 'Skips ' . $name => [$svg, $svg];
        }

        yield 'Skips a duplicate id' => [
            self::SVG . $gradient . '<rect id="g"/><rect fill="url(#g)"/></svg>',
            self::SVG . $gradient . '<rect id="g"/><rect fill="url(#g)"/></svg>',
        ];

        yield 'Skips an id duplicated by an xml:id' => [
            self::SVG . $gradient . '<rect xml:id="g"/><rect fill="url(#g)"/></svg>',
            self::SVG . $gradient . '<rect xml:id="g"/><rect fill="url(#g)"/></svg>',
        ];

        yield 'Skips a gradient with an xml:id' => [
            self::SVG . '<defs><linearGradient id="g" xml:id="h"><stop stop-color="red"/></linearGradient></defs><rect fill="url(#g)"/></svg>',
            self::SVG . '<defs><linearGradient id="g" xml:id="h"><stop stop-color="red"/></linearGradient></defs><rect fill="url(#g)"/></svg>',
        ];

        yield 'Skips a gradient with an empty id' => [
            self::SVG . '<defs><linearGradient id=""><stop stop-color="red"/></linearGradient></defs><rect fill="url(#)"/></svg>',
            self::SVG . '<defs><linearGradient id=""><stop stop-color="red"/></linearGradient></defs><rect fill="url(#)"/></svg>',
        ];

        yield 'Matches ids case-sensitively' => [
            self::SVG . '<defs><linearGradient id="g"><stop stop-color="red"/></linearGradient></defs><rect fill="url(#G)"/></svg>',
            self::SVG . '<defs><linearGradient id="g"><stop stop-color="red"/></linearGradient></defs><rect fill="url(#G)"/></svg>',
        ];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    private static function provideDocumentSkipCases(): iterable
    {
        yield 'Skips gradients of other namespaces' => [
            self::SVG . '<x:linearGradient xmlns:x="urn:x" id="g"><stop stop-color="red"/></x:linearGradient><rect fill="url(#g)"/></svg>',
            self::SVG . '<x:linearGradient xmlns:x="urn:x" id="g"><stop stop-color="red"/></x:linearGradient><rect fill="url(#g)"/></svg>',
        ];

        yield 'Skips gradients whose only child is a stop of another namespace' => [
            self::SVG . '<linearGradient id="g"><x:stop xmlns:x="urn:x" stop-color="red"/></linearGradient><rect fill="url(#g)"/></svg>',
            self::SVG . '<linearGradient id="g"><x:stop xmlns:x="urn:x" stop-color="red"/></linearGradient><rect fill="url(#g)"/></svg>',
        ];

        yield 'Converts gradients of a prefixed SVG document' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:linearGradient id="g"><svg:stop stop-color="red"/></svg:linearGradient><svg:rect fill="url(#g)"/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:rect fill="red"/></svg:svg>',
        ];

        yield 'Follows a link through a custom xlink prefix' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:l="http://www.w3.org/1999/xlink"><linearGradient id="a"><stop stop-color="red"/></linearGradient><linearGradient id="b" l:href="#a"/><rect fill="url(#b)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:l="http://www.w3.org/1999/xlink"><linearGradient id="a"><stop stop-color="red"/></linearGradient><rect fill="red"/></svg>',
        ];

        yield 'Converts gradients referenced through spaced and quoted url() forms' => [
            self::SVG . '<linearGradient id="g" gradientUnits="userSpaceOnUse"><stop stop-color="red"/></linearGradient><path fill="url( &quot;#g&quot; )" stroke=" url(\'#g\') "/></svg>',
            self::SVG . '<path fill="red" stroke="red"/></svg>',
        ];

        yield 'Converts a gradient with a unicode id' => [
            self::SVG . '<linearGradient id="ü" gradientUnits="userSpaceOnUse"><stop stop-color="RED"/></linearGradient><path fill="url(#ü)"/></svg>',
            self::SVG . '<path fill="RED"/></svg>',
        ];

        yield 'Skips gradients with a duplicated id' => [
            self::SVG . '<linearGradient id="g"><stop stop-color="red"/></linearGradient><linearGradient id="g"><stop stop-color="blue"/></linearGradient><rect fill="url(#g)"/></svg>',
            self::SVG . '<linearGradient id="g"><stop stop-color="red"/></linearGradient><linearGradient id="g"><stop stop-color="blue"/></linearGradient><rect fill="url(#g)"/></svg>',
        ];

        yield 'Skips a gradient referenced by an aria list' => [
            self::SVG . '<linearGradient id="g"><stop stop-color="red"/></linearGradient><rect fill="url(#g)"/><g aria-describedby="x g"/></svg>',
            self::SVG . '<linearGradient id="g"><stop stop-color="red"/></linearGradient><rect fill="url(#g)"/><g aria-describedby="x g"/></svg>',
        ];

        yield 'Skips a gradient referenced by an animation timing' => [
            self::SVG . '<linearGradient id="g"><stop stop-color="red"/></linearGradient><rect fill="url(#g)"/><animate begin="g.click+1s" attributeName="x"/></svg>',
            self::SVG . '<linearGradient id="g"><stop stop-color="red"/></linearGradient><rect fill="url(#g)"/><animate begin="g.click+1s" attributeName="x"/></svg>',
        ];

        yield 'Converts a later gradient after skipping an earlier one' => [
            self::SVG . '<linearGradient id="a"><stop stop-color="red" stop-opacity=".5"/></linearGradient><linearGradient id="b"><stop stop-color="blue"/></linearGradient><rect fill="url(#a)"/><rect fill="url(#b)"/></svg>',
            self::SVG . '<linearGradient id="a"><stop stop-color="red" stop-opacity=".5"/></linearGradient><rect fill="url(#a)"/><rect fill="blue"/></svg>',
        ];

        yield 'Leaves an empty document unchanged' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Skips a gradient referenced through an escaped url()' => [
            self::SVG . '<linearGradient id="g"><stop stop-color="red"/></linearGradient><rect fill="url(#g)"/><rect stroke="url(#\67) red"/></svg>',
            self::SVG . '<linearGradient id="g"><stop stop-color="red"/></linearGradient><rect fill="url(#g)"/><rect stroke="url(#\67) red"/></svg>',
        ];

        yield 'Skips documents with a style element' => [
            self::SVG . '<style>rect{opacity:.5}</style><defs><linearGradient id="g"><stop stop-color="red"/></linearGradient></defs><rect fill="url(#g)"/></svg>',
            self::SVG . '<style>rect{opacity:.5}</style><defs><linearGradient id="g"><stop stop-color="red"/></linearGradient></defs><rect fill="url(#g)"/></svg>',
        ];

        yield 'Skips documents with a script element' => [
            self::SVG . '<script>void 0</script><defs><linearGradient id="g"><stop stop-color="red"/></linearGradient></defs><rect fill="url(#g)"/></svg>',
            self::SVG . '<script>void 0</script><defs><linearGradient id="g"><stop stop-color="red"/></linearGradient></defs><rect fill="url(#g)"/></svg>',
        ];

        yield 'Leaves documents without gradients unchanged' => [
            self::SVG . '<defs/><rect fill="url(#g)"/></svg>',
            self::SVG . '<rect fill="url(#g)"/></svg>',
        ];
    }

    /**
     * @param int $links the number of links between the referenced gradient and the stop
     */
    private static function linkChain(int $links): string
    {
        return self::SVG . '<linearGradient id="g0"><stop stop-color="red"/></linearGradient>' . self::chainLinks($links, true) . '<rect fill="url(#g' . $links . ')"/></svg>';
    }

    /**
     * @param int  $links       the number of linking gradients
     * @param bool $includeLast whether to include the referenced gradient at the end of the chain
     */
    private static function chainLinks(int $links, bool $includeLast): string
    {
        $chain = '';

        for ($i = 1; $i <= $links - ($includeLast ? 0 : 1); ++$i) {
            $chain .= '<linearGradient id="g' . $i . '" href="#g' . ($i - 1) . '"/>';
        }

        return $chain;
    }
}
