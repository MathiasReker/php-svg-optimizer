<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Validator;

use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class SvgValidatorTest extends TestCase
{
    private SvgValidator $svgValidator;

    #[Test]
    public function isValidWithValidSvg(): void
    {
        $validSvg = '<svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100"/></svg>';
        self::assertTrue($this->svgValidator->isValid($validSvg));
    }

    #[Test]
    public function isValidWithInvalidSvg(): void
    {
        $invalidSvg = '<div>Not an SVG</div>';
        self::assertFalse($this->svgValidator->isValid($invalidSvg));
    }

    #[Test]
    public function isValidWithEmptyString(): void
    {
        self::assertFalse($this->svgValidator->isValid(''));
    }

    #[Test]
    public function isValidWithWhitespace(): void
    {
        self::assertFalse($this->svgValidator->isValid('    '));
    }

    #[Test]
    public function isValidWithMalformedSvg(): void
    {
        $malformedSvg = '<svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100"></svg>';
        self::assertFalse($this->svgValidator->isValid($malformedSvg));
    }

    #[Test]
    public function isValidWithXmlDeclaration(): void
    {
        $svgWithXmlDeclaration = '<?xml version="1.0" encoding="UTF-8"?> <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100"/></svg>';
        self::assertTrue($this->svgValidator->isValid($svgWithXmlDeclaration));
    }

    #[Test]
    public function isValidWithSvgElementOnly(): void
    {
        $svgElementOnly = '<svg xmlns="http://www.w3.org/2000/svg"/>';
        self::assertTrue($this->svgValidator->isValid($svgElementOnly));
    }

    #[Test]
    public function isValidWithComment(): void
    {
        $svgComment = '<svg xmlns="http://www.w3.org/2000/svg"><!-- comment -->';
        self::assertFalse($this->svgValidator->isValid($svgComment));
    }

    #[Test]
    public function empty(): void
    {
        $svgComment = '';
        self::assertFalse($this->svgValidator->isValid($svgComment));
    }

    #[Test]
    public function isValidWithCommentInFrontOfSvg(): void
    {
        $svgComment = '<!-- comment --><svg xmlns="http://www.w3.org/2000/svg">';
        self::assertFalse($this->svgValidator->isValid($svgComment));
    }

    #[Test]
    public function isValidWithInternalDtdSubsetContainingDoctypeLikeEntity(): void
    {
        $svg = '<!DOCTYPE svg [<!ENTITY z "><!--">]><!-- --><svg xmlns="http://www.w3.org/2000/svg"/>';
        self::assertTrue($this->svgValidator->isValid($svg));
    }

    #[Test]
    public function isValidWithNonSvgRootHiddenBehindDoctype(): void
    {
        $html = '<!DOCTYPE html [<!ENTITY z "<svg>">]><html xmlns="http://www.w3.org/1999/xhtml"/>';
        self::assertFalse($this->svgValidator->isValid($html));
    }

    #[Test]
    public function isValidWithPrefixedSvgRoot(): void
    {
        $svg = '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"/>';
        self::assertTrue($this->svgValidator->isValid($svg));
    }

    #[Test]
    public function isValidWithSvgRootWithoutNamespace(): void
    {
        self::assertTrue($this->svgValidator->isValid('<svg><rect width="1" height="1"/></svg>'));
    }

    #[DataProvider('provideIsValidRejectsRootsThatAreNotSvgElementsCases')]
    #[Test]
    public function isValidRejectsRootsThatAreNotSvgElements(string $content): void
    {
        self::assertFalse($this->svgValidator->isValid($content));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIsValidRejectsRootsThatAreNotSvgElementsCases(): iterable
    {
        yield 'uppercase local name' => ['<SVG xmlns="http://www.w3.org/2000/svg"/>'];
        yield 'mixed case local name' => ['<Svg/>'];
        yield 'svg in another default namespace' => ['<svg xmlns="http://www.w3.org/1999/xhtml"/>'];
        yield 'prefixed svg in another namespace' => ['<x:svg xmlns:x="urn:example"/>'];

        yield 'html root' => ['<html xmlns="http://www.w3.org/1999/xhtml"><body><svg xmlns="http://www.w3.org/2000/svg"/></body></html>'];

        yield 'math root' => ['<math xmlns="http://www.w3.org/1998/Math/MathML"/>'];

        yield 'svg element that is not the root' => ['<g xmlns="http://www.w3.org/2000/svg"><svg/></g>'];

        yield 'truncated document' => ['<svg xmlns="http://www.w3.org/2000/svg"><g>'];

        yield 'two root elements' => ['<svg xmlns="http://www.w3.org/2000/svg"/><svg xmlns="http://www.w3.org/2000/svg"/>'];

        yield 'text before the root' => ['junk<svg xmlns="http://www.w3.org/2000/svg"/>'];

        yield 'invalid UTF-8' => ["<svg xmlns=\"http://www.w3.org/2000/svg\"><text>\xFF</text></svg>"];

        yield 'NUL byte' => ["\0"];

        yield 'undeclared prefix on the root' => ['<svg:svg/>'];
    }

    #[DataProvider('provideIsValidAcceptsSvgDocumentsInEveryEncodingAndLayoutCases')]
    #[Test]
    public function isValidAcceptsSvgDocumentsInEveryEncodingAndLayout(string $content): void
    {
        self::assertTrue($this->svgValidator->isValid($content));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIsValidAcceptsSvgDocumentsInEveryEncodingAndLayoutCases(): iterable
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><text>é</text></svg>';

        yield 'UTF-8 byte order mark' => ["\xEF\xBB\xBF" . $svg];

        yield 'UTF-16LE byte order mark' => ["\xFF\xFE" . mb_convert_encoding($svg, 'UTF-16LE', 'UTF-8')];

        yield 'UTF-16BE byte order mark' => ["\xFE\xFF" . mb_convert_encoding($svg, 'UTF-16BE', 'UTF-8')];

        yield 'ISO-8859-1 declaration' => ['<?xml version="1.0" encoding="ISO-8859-1"?>' . mb_convert_encoding($svg, 'ISO-8859-1', 'UTF-8')];

        yield 'CRLF line endings' => ["<?xml version=\"1.0\"?>\r\n<svg xmlns=\"http://www.w3.org/2000/svg\">\r\n  <g/>\r\n</svg>\r\n"];

        yield 'leading and trailing whitespace' => ["\n\t " . $svg . "\n\n"];

        yield 'processing instructions and comments around the root' => ['<?xml-stylesheet href="a.css"?><!-- a --><?foo?>' . $svg . '<!-- b --><?bar?>'];

        yield 'DOCTYPE with an internal subset and entities' => ['<!DOCTYPE svg [<!ENTITY a "é"><!ATTLIST svg x CDATA #IMPLIED>]><svg xmlns="http://www.w3.org/2000/svg"><text>&a;</text></svg>'];

        yield 'deep nesting' => ['<svg xmlns="http://www.w3.org/2000/svg">' . str_repeat('<g>', 200) . str_repeat('</g>', 200) . '</svg>'];
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->svgValidator = new SvgValidator();
    }
}
