<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Processor;

use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Service\Formatter\XmlFormatter;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(XmlFormatter::class)]
final class DomDocumentWrapperTest extends TestCase
{
    private DomDocumentWrapper $domDocumentWrapper;

    /**
     * @throws XmlProcessingException
     */
    public function testSaveToStringValid(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<root><child>Test</child></root>');

        $result = $this->domDocumentWrapper->saveToString($domDocument);

        self::assertStringContainsString('<root><child>Test</child></root>', $result);
    }

    /**
     * @throws XmlProcessingException
     */
    public function testSaveToStringWithLineFeedsAndTabs(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML("<root>\n\t<child>\n\t\tTest\n\t</child>\n</root>");

        $result = $this->domDocumentWrapper->saveToString($domDocument);

        self::assertStringNotContainsString("\n", $result);
        self::assertStringNotContainsString("\t", $result);
        self::assertStringContainsString('<root><child>Test</child></root>', $result);
    }

    /**
     * @throws XmlProcessingException
     */
    public function testLoadFromFileValid(): void
    {
        $filePath = __DIR__ . '/test.xml';

        try {
            file_put_contents($filePath, '<root><child>Test</child></root>');
            $domDocument = $this->domDocumentWrapper->loadFromFile($filePath);

            $xmlString = $domDocument->saveXML();

            self::assertNotFalse($xmlString, 'saveXML() returned false');
            self::assertStringContainsString('<root><child>Test</child></root>', $xmlString);
        } finally {
            unlink($filePath);
        }
    }

    /**
     * @throws XmlProcessingException
     */
    public function testLoadFromStringValid(): void
    {
        $content = '<root><child>Test</child></root>';
        $domDocument = $this->domDocumentWrapper->loadFromString($content);

        $xmlString = $domDocument->saveXML();

        self::assertNotFalse($xmlString, 'saveXML() returned false');
        self::assertStringContainsString('<root><child>Test</child></root>', $xmlString);
    }

    /**
     * @throws XmlProcessingException
     */
    public function loadFromString(string $content): void
    {
        if ('' === trim($content)) {
            throw new XmlProcessingException('Failed to load DOMDocument: input is empty.');
        }

        $domDocument = new \DOMDocument();

        libxml_use_internal_errors(true);
        if (!$domDocument->loadXML($content)) {
            throw new XmlProcessingException('Failed to load DOMDocument.');
        }
    }

    /**
     * @throws XmlProcessingException
     */
    public function testLoadFromStringWithLineFeedsAndTabs(): void
    {
        $content = "<root>\n\t<child>Test</child>\n</root>";
        $domDocument = $this->domDocumentWrapper->loadFromString($content);

        $xmlString = $this->domDocumentWrapper->saveToString($domDocument);

        self::assertStringNotContainsString("\n", $xmlString, 'Newline characters were not removed.');
        self::assertStringNotContainsString("\t", $xmlString, 'Tab characters were not removed.');
        self::assertStringContainsString('<root><child>Test</child></root>', $xmlString);
    }

    /**
     * @throws XmlProcessingException
     */
    public function testSaveToStringRemovesCarriageReturns(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML("<root>\r\n\t<child>\r\n\t\tTest\r\n\t</child>\r\n</root>");

        $result = $this->domDocumentWrapper->saveToString($domDocument);

        self::assertStringNotContainsString("\r", $result);
        self::assertStringContainsString('<root><child>Test</child></root>', $result);
    }

    /**
     * @throws XmlProcessingException
     */
    public function testLoadFromStringInvalidThrowsException(): void
    {
        $this->expectException(XmlProcessingException::class);
        $this->expectExceptionMessage('Failed to load DOMDocument.');

        $invalidXml = '<root><unclosed></root>';
        $this->domDocumentWrapper->loadFromString($invalidXml);
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->domDocumentWrapper = new DomDocumentWrapper();
    }
}
