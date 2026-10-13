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
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->svgValidator = new SvgValidator();
    }
}
