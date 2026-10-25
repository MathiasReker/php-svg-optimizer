<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Rule\Trait;

use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertOneStopGradients;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\Trait\BaseEnumTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\FlattenGroups;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedDefinitions;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedIds;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ClassAttributeTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\CssDeclarationTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\DefinitionElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\DocumentScriptTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\FragmentTargetTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\IdReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\SvgElementTrait;
use MathiasReker\PhpSvgOptimizer\Tests\Unit\Support\RegexFailureTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveUnusedDefinitions::class)]
#[CoversClass(ConvertOneStopGradients::class)]
#[CoversClass(FlattenGroups::class)]
#[CoversClass(RemoveUnusedIds::class)]
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(ClassAttributeTrait::class)]
#[CoversTrait(CssDeclarationTrait::class)]
#[CoversTrait(DefinitionElementTrait::class)]
#[CoversTrait(DocumentScriptTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(FragmentTargetTrait::class)]
#[CoversTrait(IdReferenceTrait::class)]
#[CoversTrait(SvgElementTrait::class)]
#[UsesClass(SvgAttribute::class)]
#[UsesClass(SvgNamespace::class)]
#[UsesClass(SvgTag::class)]
#[UsesTrait(BaseEnumTrait::class)]
final class IdReferenceTraitTest extends TestCase
{
    use IdReferenceTrait;
    use RegexFailureTrait;

    #[Test]
    public function findsFragmentReferences(): void
    {
        self::assertArrayHasKey('a', $this->fragmentReferences('url(#a)'));
    }

    #[Test]
    public function findsFragmentReferencesThroughCssEscapes(): void
    {
        $references = array_map(strval(...), array_keys($this->fragmentReferences('#\31 23{fill:red} url(#\6d) #a\.b #\62 c')));

        self::assertContains('123', $references);
        self::assertContains('m', $references);
        self::assertContains('a.b', $references);
        self::assertContains('bc', $references);
    }

    #[Test]
    public function findsReferencedElementsInASubtreeOutsideIgnoredElements(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg"><g><text><tspan id="a"/></text><rect xml:id="b"/></g></svg>');

        $group = $domDocument->getElementsByTagName('g')->item(0);
        self::assertInstanceOf(\DOMElement::class, $group);

        self::assertFalse($this->hasReferencedElement($group, ['a' => true], ['tspan']));
        self::assertTrue($this->hasReferencedElement($group, ['a' => true], []));
        self::assertTrue($this->hasReferencedElement($group, ['b' => true], ['tspan']));
        self::assertFalse($this->hasReferencedElement($group, ['c' => true], []));
    }

    #[Test]
    public function keepsAGroupWithAReferencedDescendant(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect id="a"/></g><use href="#a"/></svg>');

        $expected = (string) $domDocument->saveXML();

        (new FlattenGroups())->apply($domDocument);

        self::assertSame($expected, $domDocument->saveXML());
    }

    /**
     * @param string $escaped  the identifier with escapes
     * @param string $expected the decoded identifier
     */
    #[DataProvider('provideDecodesCssEscapesCases')]
    #[Test]
    public function decodesCssEscapes(string $escaped, string $expected): void
    {
        self::assertSame($expected, $this->cssUnescape($escaped));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideDecodesCssEscapesCases(): iterable
    {
        yield 'hexadecimal escape with a space' => ['\31  23', '1 23'];

        yield 'code point beyond Unicode' => ['a\110000 b', "a\u{FFFD}b"];

        yield 'largest code point' => ['\10FFFF', "\u{10FFFF}"];

        yield 'zero code point' => ['\0', "\u{FFFD}"];

        yield 'first surrogate' => ['\D800', "\u{FFFD}"];

        yield 'last surrogate' => ['\DFFF', "\u{FFFD}"];

        yield 'code points around the surrogates' => ['\D7FF\E000', "\u{D7FF}\u{E000}"];

        yield 'uppercase hexadecimal and an escaped character' => ['\E9\:', 'é:'];

        yield 'trailing backslash' => ['a\\', 'a\\'];
    }

    #[RunInSeparateProcess]
    #[Test]
    public function findsNoFragmentReferencesWhenTheTextCannotBeMatched(): void
    {
        self::assertSame([], $this->withFailingRegularExpressions(fn (): array => $this->fragmentReferences('url(#a)')));
    }
}
