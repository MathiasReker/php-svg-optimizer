<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Xml;

use MathiasReker\PhpSvgOptimizer\Xml\EntityReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Xml\ParseException;
use MathiasReker\PhpSvgOptimizer\Xml\Parser;
use MathiasReker\PhpSvgOptimizer\Xml\SvgValidator;
use MathiasReker\PhpSvgOptimizer\Xml\TextWhitespaceTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SvgValidator::class)]
#[UsesClass(Parser::class)]
#[UsesTrait(EntityReferenceTrait::class)]
#[UsesTrait(TextWhitespaceTrait::class)]
final class SvgValidatorTest extends TestCase
{
    /**
     * @param non-empty-string $content
     *
     * @throws ParseException
     */
    #[DataProvider('provideAcceptsAnSvgRootElementCases')]
    #[Test]
    public function acceptsAnSvgRootElement(string $content): void
    {
        self::assertTrue((new SvgValidator())->isValidDocument((new Parser())->parse($content)));
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function provideAcceptsAnSvgRootElementCases(): iterable
    {
        yield 'svg root with children' => ['<svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100"/></svg>'];

        yield 'empty svg root' => ['<svg xmlns="http://www.w3.org/2000/svg"/>'];

        yield 'svg root without a namespace' => ['<svg><rect width="1" height="1"/></svg>'];

        yield 'prefixed svg root' => ['<svg:svg xmlns:svg="http://www.w3.org/2000/svg"/>'];

        yield 'svg root after a doctype whose entity looks like a comment' => ['<!DOCTYPE svg [<!ENTITY z "><!--">]><!-- --><svg xmlns="http://www.w3.org/2000/svg"/>'];
    }

    /**
     * @param non-empty-string $content
     *
     * @throws ParseException
     */
    #[DataProvider('provideRejectsARootThatIsNotAnSvgElementCases')]
    #[Test]
    public function rejectsARootThatIsNotAnSvgElement(string $content): void
    {
        self::assertFalse((new SvgValidator())->isValidDocument((new Parser())->parse($content)));
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function provideRejectsARootThatIsNotAnSvgElementCases(): iterable
    {
        yield 'div root' => ['<div>Not an SVG</div>'];

        yield 'uppercase local name' => ['<SVG xmlns="http://www.w3.org/2000/svg"/>'];

        yield 'mixed case local name' => ['<Svg/>'];

        yield 'svg in another default namespace' => ['<svg xmlns="http://www.w3.org/1999/xhtml"/>'];

        yield 'prefixed svg in another namespace' => ['<x:svg xmlns:x="urn:example"/>'];

        yield 'html root' => ['<html xmlns="http://www.w3.org/1999/xhtml"><body><svg xmlns="http://www.w3.org/2000/svg"/></body></html>'];

        yield 'math root' => ['<math xmlns="http://www.w3.org/1998/Math/MathML"/>'];

        yield 'svg element that is not the root' => ['<g xmlns="http://www.w3.org/2000/svg"><svg/></g>'];
    }

    #[Test]
    public function rejectsADocumentWithoutARootElement(): void
    {
        self::assertFalse((new SvgValidator())->isValidDocument(new \DOMDocument()));
    }
}
