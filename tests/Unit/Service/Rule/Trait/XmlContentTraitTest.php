<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Rule\Trait;

use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertEmptyTagsToSelfClosing;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\XmlContentTrait;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ConvertEmptyTagsToSelfClosing::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class XmlContentTraitTest extends TestCase
{
    use XmlContentTrait;

    /**
     * @throws XmlProcessingException
     */
    #[Test]
    public function rewritesTheMarkupAndLoadsTheDocumentFromIt(): void
    {
        $domDocument = $this->document('<svg><rect style="fill:blue;"/></svg>');

        $result = $this->processXmlContent($domDocument, static fn (string $content): string => str_replace('blue', 'red', $content));

        self::assertStringContainsString('<svg><rect style="fill:red;"/></svg>', $result);
        self::assertSame('fill:red;', $domDocument->documentElement?->firstElementChild?->getAttribute('style'));
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function wrapsAnExceptionOfTheCallback(): void
    {
        $runtimeException = new \RuntimeException('boom');

        try {
            $this->processXmlContent($this->document('<svg/>'), static function () use ($runtimeException): string {
                throw $runtimeException;
            });
            self::fail('Expected XmlProcessingException was not thrown.');
        } catch (XmlProcessingException $xmlProcessingException) {
            self::assertSame('Failed to process the XML content.', $xmlProcessingException->getMessage());
            self::assertSame($runtimeException, $xmlProcessingException->getPrevious());
        }
    }

    #[Test]
    public function passesOnAnXmlProcessingExceptionOfTheCallback(): void
    {
        $xmlProcessingException = new XmlProcessingException('inner');

        try {
            $this->processXmlContent($this->document('<svg/>'), static function () use ($xmlProcessingException): string {
                throw $xmlProcessingException;
            });
            self::fail('Expected XmlProcessingException was not thrown.');
        } catch (XmlProcessingException $caught) {
            self::assertSame($xmlProcessingException, $caught);
        }
    }

    #[DataProvider('provideRejectsMarkupThatIsNotAValidSvgCases')]
    #[Test]
    public function rejectsMarkupThatIsNotAValidSvg(string $markup): void
    {
        $domDocument = $this->document('<svg><g/></svg>');

        try {
            $this->processXmlContent($domDocument, static fn (): string => $markup);
            self::fail('Expected XmlProcessingException was not thrown.');
        } catch (XmlProcessingException $xmlProcessingException) {
            self::assertSame('Optimized SVG content is not valid.', $xmlProcessingException->getMessage());
            self::assertSame('<svg><g/></svg>', $domDocument->saveXML($domDocument->documentElement));
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideRejectsMarkupThatIsNotAValidSvgCases(): iterable
    {
        yield 'empty' => [''];

        yield 'malformed' => ['<svg>'];

        yield 'not an svg' => ['<html/>'];
    }

    /**
     * @param non-empty-string $markup
     */
    private function document(string $markup): \DOMDocument
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML($markup);

        return $domDocument;
    }
}
