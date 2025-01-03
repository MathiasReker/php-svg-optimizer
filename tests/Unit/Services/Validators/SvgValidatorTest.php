<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Services\Validators;

use MathiasReker\PhpSvgOptimizer\Services\Validators\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SvgValidator::class)]
final class SvgValidatorTest extends TestCase
{
    private SvgValidator $svgValidator;

    public function testIsValidWithValidSvg(): void
    {
        $validSvg = '<svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100"/></svg>';
        self::assertTrue($this->svgValidator->isValid($validSvg));
    }

    public function testIsValidWithInvalidSvg(): void
    {
        $invalidSvg = '<div>Not an SVG</div>';
        self::assertFalse($this->svgValidator->isValid($invalidSvg));
    }

    public function testIsValidWithEmptyString(): void
    {
        self::assertFalse($this->svgValidator->isValid(''));
    }

    public function testIsValidWithWhitespace(): void
    {
        self::assertFalse($this->svgValidator->isValid('    '));
    }

    public function testIsValidWithMalformedSvg(): void
    {
        $malformedSvg = '<svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100"></svg>';
        self::assertTrue($this->svgValidator->isValid($malformedSvg));
    }

    public function testIsValidWithXmlDeclaration(): void
    {
        $svgWithXmlDeclaration = '<?xml version="1.0" encoding="UTF-8"?> <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100"/></svg>';
        self::assertTrue($this->svgValidator->isValid($svgWithXmlDeclaration));
    }

    public function testIsValidWithSvgElementOnly(): void
    {
        $svgElementOnly = '<svg xmlns="http://www.w3.org/2000/svg"/>';
        self::assertTrue($this->svgValidator->isValid($svgElementOnly));
    }

    public function testIsValidWithComment(): void
    {
        $svgComment = '<svg xmlns="http://www.w3.org/2000/svg"><!-- comment -->';
        self::assertTrue($this->svgValidator->isValid($svgComment));
    }

    public function testIsValidWithCommentInFrontOfSvg(): void
    {
        $svgComment = '<!-- comment --><svg xmlns="http://www.w3.org/2000/svg">';
        self::assertTrue($this->svgValidator->isValid($svgComment));
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->svgValidator = new SvgValidator();
    }
}
