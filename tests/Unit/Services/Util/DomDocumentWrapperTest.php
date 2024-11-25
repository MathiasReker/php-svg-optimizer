<?php

/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Services\Util;

use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Services\Util\DomDocumentWrapper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DomDocumentWrapper::class)]
final class DomDocumentWrapperTest extends TestCase
{
    private DomDocumentWrapper $domDocumentWrapper;

    public function testSaveToStringValid(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<root><child>Test</child></root>');

        $result = $this->domDocumentWrapper->saveToString($domDocument);

        Assert::assertStringContainsString('<root><child>Test</child></root>', $result);
    }

    public function testSaveToStringThrowsException(): void
    {
        $domDocument = $this->createMock(\DOMDocument::class);
        $domDocument->method('saveXML')->willReturn(false);

        $this->expectException(XmlProcessingException::class);
        $this->expectExceptionMessage('Failed to save XML content.');

        $this->domDocumentWrapper->saveToString($domDocument);
    }

    public function testLoadFromFileValid(): void
    {
        $filePath = __DIR__ . '/test.xml';
        file_put_contents($filePath, '<root><child>Test</child></root>');

        $domDocument = $this->domDocumentWrapper->loadFromFile($filePath);

        Assert::assertInstanceOf(\DOMDocument::class, $domDocument);
        $xmlString = $domDocument->saveXML();
        Assert::assertNotFalse($xmlString); // Ensure saveXML did not return false
        Assert::assertStringContainsString('<root><child>Test</child></root>', $xmlString);

        unlink($filePath); // Clean up the test file
    }

    public function testLoadFromFileThrowsException(): void
    {
        $invalidFilePath = 'non_existent_file.xml';

        $this->expectException(XmlProcessingException::class);
        $this->expectExceptionMessage('Failed to load DOMDocument.');

        $this->domDocumentWrapper->loadFromFile($invalidFilePath);
    }

    public function testLoadFromStringValid(): void
    {
        $xmlContent = '<root><child>Test</child></root>';
        $domDocument = $this->domDocumentWrapper->loadFromString($xmlContent);

        Assert::assertInstanceOf(\DOMDocument::class, $domDocument);
        $xmlString = $domDocument->saveXML();
        Assert::assertNotFalse($xmlString); // Ensure saveXML did not return false
        Assert::assertStringContainsString('<root><child>Test</child></root>', $xmlString);
    }

    public function testLoadFromStringThrowsException(): void
    {
        $invalidXmlContent = '<root><child>Test</root>'; // Malformed XML

        $this->expectException(XmlProcessingException::class);
        $this->expectExceptionMessage('Failed to load DOMDocument.');

        $this->domDocumentWrapper->loadFromString($invalidXmlContent);
    }

    /**
     * @throws \ReflectionException
     */
    public function testCreateDomDocument(): void
    {
        $reflectionClass = new \ReflectionClass(DomDocumentWrapper::class);
        $reflectionMethod = $reflectionClass->getMethod('createDomDocument');

        /**
         * @var \DOMDocument $domDocument
         */
        $domDocument = $reflectionMethod->invoke($this->domDocumentWrapper);

        Assert::assertInstanceOf(\DOMDocument::class, $domDocument);
        Assert::assertFalse($domDocument->formatOutput);
        Assert::assertFalse($domDocument->preserveWhiteSpace);
    }

    protected function setUp(): void
    {
        $this->domDocumentWrapper = new DomDocumentWrapper();
    }
}
