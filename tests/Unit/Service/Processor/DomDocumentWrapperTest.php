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
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DomDocumentWrapper::class)]
final class DomDocumentWrapperTest extends TestCase
{
    private DomDocumentWrapper $domDocumentWrapper;

    /**
     * @throws XmlProcessingException
     */
    #[Test]
    public function saveToStringValid(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<root><child>Test</child></root>');

        $result = $this->domDocumentWrapper->saveToString($domDocument);

        self::assertStringContainsString('<root><child>Test</child></root>', $result);
    }

    /**
     * @throws XmlProcessingException
     */
    #[Test]
    public function saveToStringRemovesIndentationButKeepsWhitespaceInContent(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML("<root>\n\t<child>\n\t\tTest\n\t</child>\n</root>");

        $result = $this->domDocumentWrapper->saveToString($domDocument);

        self::assertSame("<root><child>\n\t\tTest\n\t</child></root>", $result);
    }

    /**
     * @throws XmlProcessingException
     */
    #[Test]
    public function loadFromFileValid(): void
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
    #[Test]
    public function loadFromStringValid(): void
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
    #[Test]
    public function loadFromStringWithLineFeedsAndTabs(): void
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
    #[Test]
    public function saveToStringWritesLineBreaksAsTheParserNormalizedThem(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML("<root>\r\n\t<child>\r\n\t\tTest\r\n\t</child>\r\n</root>");

        $result = $this->domDocumentWrapper->saveToString($domDocument);

        self::assertSame("<root><child>\n\t\tTest\n\t</child></root>", $result);
    }

    /**
     * @throws XmlProcessingException
     */
    #[Test]
    public function loadFromStringInvalidThrowsException(): void
    {
        $this->expectException(XmlProcessingException::class);
        $this->expectExceptionMessage('Failed to load DOMDocument.');

        $invalidXml = '<root><unclosed></root>';
        $this->domDocumentWrapper->loadFromString($invalidXml);
    }

    /**
     * @throws XmlProcessingException
     */
    #[Test]
    public function saveToStringHandlesEmptyDomDocument(): void
    {
        $domDocument = new \DOMDocument();
        $result = $this->domDocumentWrapper->saveToString($domDocument);
        self::assertSame("<?xml version=\"1.0\"?>\n", $result);
    }

    /**
     * @throws XmlProcessingException
     */
    #[Test]
    public function loadFromFileThrowsExceptionOnMalformedXml(): void
    {
        $filePath = __DIR__ . '/malformed.xml';
        file_put_contents($filePath, '<root><unclosed>');

        $this->expectException(XmlProcessingException::class);
        $this->expectExceptionMessage('Failed to load DOMDocument.');

        try {
            $this->domDocumentWrapper->loadFromFile($filePath);
        } finally {
            unlink($filePath);
        }
    }

    /**
     * @throws XmlProcessingException
     */
    #[Test]
    public function loadFromFileThrowsExceptionOnNonExistentFile(): void
    {
        $this->expectException(XmlProcessingException::class);
        $this->expectExceptionMessage('Failed to load DOMDocument.');

        $this->domDocumentWrapper->loadFromFile('/non/existent/file.xml');
    }

    /**
     * @throws XmlProcessingException
     */
    #[Test]
    public function loadFromFileThrowsExceptionOnEmptyFilePath(): void
    {
        $this->expectException(XmlProcessingException::class);
        $this->expectExceptionMessage('File path cannot be empty.');

        $this->domDocumentWrapper->loadFromFile('');
    }

    /**
     * @throws XmlProcessingException
     */
    #[Test]
    public function loadFromStringThrowsExceptionOnEmptyContent(): void
    {
        $this->expectException(XmlProcessingException::class);
        $this->expectExceptionMessage('XML content cannot be empty.');

        $this->domDocumentWrapper->loadFromString('');
    }

    /**
     * @throws XmlProcessingException
     */
    #[Test]
    public function loadFromStringRestoresTheLibxmlErrorHandling(): void
    {
        $previous = libxml_use_internal_errors(false);

        try {
            $this->domDocumentWrapper->loadFromString('<root/>');
            self::assertFalse(libxml_use_internal_errors(true));

            $this->domDocumentWrapper->loadFromString('<root/>');
            self::assertTrue(libxml_use_internal_errors(false));
        } finally {
            libxml_use_internal_errors($previous);
        }
    }

    #[Test]
    public function loadFromStringRestoresTheLibxmlErrorHandlingWhenLoadingFails(): void
    {
        foreach ([false, true] as $internalErrors) {
            $previous = libxml_use_internal_errors($internalErrors);

            try {
                $this->domDocumentWrapper->loadFromString('<root><unclosed></root>');
                self::fail('Malformed XML must not load.');
            } catch (XmlProcessingException) {
                self::assertSame([], libxml_get_errors());
                self::assertSame($internalErrors, libxml_use_internal_errors($previous));
            }
        }
    }

    /**
     * @throws XmlProcessingException
     */
    #[Test]
    public function loadFromStringDoesNotLoadExternalDeclarations(): void
    {
        $dtdPath = __DIR__ . '/external.dtd';
        $textPath = __DIR__ . '/external.txt';

        try {
            file_put_contents($dtdPath, '<!ENTITY x "dtd">');
            file_put_contents($textPath, 'file');
            $content = \sprintf(
                '<!DOCTYPE svg SYSTEM "%s" [<!ENTITY ext SYSTEM "%s">]><svg xmlns="http://www.w3.org/2000/svg"><text>a&x;&ext;b</text></svg>',
                str_replace('\\', '/', $dtdPath),
                str_replace('\\', '/', $textPath),
            );

            $domDocument = $this->domDocumentWrapper->loadFromString($content);

            self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"><text>ab</text></svg>', $this->domDocumentWrapper->saveToString($domDocument));
        } finally {
            unlink($dtdPath);
            unlink($textPath);
        }
    }

    /**
     * @throws XmlProcessingException
     */
    #[Test]
    public function loadFromStringMergesCdataSectionsIntoTheText(): void
    {
        $domDocument = $this->domDocumentWrapper->loadFromString("<svg><style>\n  a<![CDATA[{b}]]>c\n</style></svg>");
        $style = $domDocument->getElementsByTagName('style')->item(0);

        self::assertInstanceOf(\DOMElement::class, $style);
        self::assertSame(1, $style->childNodes->length);
        self::assertInstanceOf(\DOMText::class, $style->firstChild);
        self::assertNotInstanceOf(\DOMCdataSection::class, $style->firstChild);
        self::assertSame("\n  a{b}c\n", $style->textContent);
    }

    /**
     * @throws XmlProcessingException
     */
    #[Test]
    public function saveToStringKeepsWhitespaceInCdataSections(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<root><a><![CDATA[ ]]></a> <b/></root>');

        self::assertSame('<root><a><![CDATA[ ]]></a><b/></root>', $this->domDocumentWrapper->saveToString($domDocument));
    }

    /**
     * @param non-empty-string $content
     *
     * @throws XmlProcessingException
     */
    #[DataProvider('provideLoadFromStringRemovesBlankTextNodesLikeTheParserCases')]
    #[Test]
    public function loadFromStringRemovesBlankTextNodesLikeTheParser(string $content): void
    {
        $expected = new \DOMDocument();
        $expected->preserveWhiteSpace = false;
        $expected->loadXML($content);

        $domDocument = $this->domDocumentWrapper->loadFromString($content);

        self::assertSame($expected->saveXML($expected->documentElement), $domDocument->saveXML($domDocument->documentElement));
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function provideLoadFromStringRemovesBlankTextNodesLikeTheParserCases(): iterable
    {
        yield 'Whitespace between elements' => ["<svg>\n  <g>\n    <rect/>\n  </g>\n  <!-- c -->\n  <g/>\n</svg>"];

        yield 'Whitespace that is the only content of an element' => ['<svg><title> </title><g>  </g></svg>'];

        yield 'Whitespace after a first child that is text' => ['<svg><g>a<rect/> <rect/> <rect/></g></svg>'];

        yield 'Whitespace after text that starts with whitespace' => ['<svg><g><rect/> a <rect/> <rect/></g><g><rect/>a b<rect/> <rect/></g></svg>'];

        yield 'Whitespace after text where xml:space is default' => ['<svg><g xml:space="default"><rect/> a <rect/> <rect/></g></svg>'];

        yield 'Whitespace where xml:space is preserve' => ['<svg><g xml:space="preserve"><rect/> <rect/></g><g xml:space="preserve"> <rect/> <g> <rect/> </g></g><g xml:space="default"> <rect/> </g></svg>'];
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->domDocumentWrapper = new DomDocumentWrapper();
    }
}
