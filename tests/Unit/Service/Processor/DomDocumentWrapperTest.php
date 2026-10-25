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

    #[Test]
    public function saveToStringValid(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<root><child>Test</child></root>');

        $result = $this->domDocumentWrapper->saveToString($domDocument);

        self::assertStringContainsString('<root><child>Test</child></root>', $result);
    }

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

    #[Test]
    public function saveToStringWritesElementsWithIgnorableWhitespaceOnlyAsEmptyElements(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML("<svg xmlns=\"http://www.w3.org/2000/svg\"><g>\n  </g><text>a<tspan> </tspan>b</text></svg>");

        self::assertSame(
            '<svg xmlns="http://www.w3.org/2000/svg"><g/><text>a<tspan> </tspan>b</text></svg>',
            $this->domDocumentWrapper->saveToString($domDocument),
        );
    }

    #[Test]
    public function saveToStringLeavesTheDocumentAsItWas(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML("<svg xmlns=\"http://www.w3.org/2000/svg\">\n  <g>\n    </g>\n  <text>a <tspan>b</tspan>\n  </text>\n</svg>");

        $before = $domDocument->saveXML();

        $this->domDocumentWrapper->saveToString($domDocument);

        self::assertSame($before, $domDocument->saveXML());
    }

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

    #[Test]
    public function loadFromFileWrapsTheErrorForAPathWithANullByte(): void
    {
        try {
            $this->domDocumentWrapper->loadFromFile("icon\0.svg");
            self::fail('Expected XmlProcessingException was not thrown.');
        } catch (XmlProcessingException $xmlProcessingException) {
            self::assertSame('Failed to load DOMDocument.', $xmlProcessingException->getMessage());
            self::assertSame(0, $xmlProcessingException->getCode());
            self::assertInstanceOf(\ValueError::class, $xmlProcessingException->getPrevious());
        }
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

    /**
     * @param non-empty-string $content
     *
     * @throws XmlProcessingException
     */
    #[DataProvider('provideSaveToStringWritesAnEndTagForEmptyXhtmlElementsThatAreNotVoidCases')]
    #[Test]
    public function saveToStringWritesAnEndTagForEmptyXhtmlElementsThatAreNotVoid(string $content, string $expected): void
    {
        $domDocument = $this->domDocumentWrapper->loadFromString($content);
        $saved = $this->domDocumentWrapper->saveToString($domDocument);

        self::assertSame($expected, $saved);
        self::assertSame($saved, $this->domDocumentWrapper->saveToString($domDocument));
        self::assertSame($saved, $this->domDocumentWrapper->saveToString($this->domDocumentWrapper->loadFromString($saved)));
    }

    /**
     * @return iterable<string, array{non-empty-string, string}>
     */
    public static function provideSaveToStringWritesAnEndTagForEmptyXhtmlElementsThatAreNotVoidCases(): iterable
    {
        yield 'Empty elements in foreignObject' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><div xmlns="http://www.w3.org/1999/xhtml"><span/><p></p><script/>a</div></foreignObject><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><div xmlns="http://www.w3.org/1999/xhtml"><span></span><p></p><script></script>a</div></foreignObject><rect/></svg>',
        ];

        yield 'Void elements stay self-closing' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><div xmlns="http://www.w3.org/1999/xhtml"><br/><img src="a.png"/><hr></hr><BR/><wbr/></div></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><div xmlns="http://www.w3.org/1999/xhtml"><br/><img src="a.png"/><hr/><BR/><wbr/></div></foreignObject></svg>',
        ];

        yield 'Elements whose names only contain the name of a void element' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><foreignObject><div xmlns=\"http://www.w3.org/1999/xhtml\"><abbr/><colgroup/><lin\u{212A}/></div></foreignObject></svg>",
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><foreignObject><div xmlns=\"http://www.w3.org/1999/xhtml\"><abbr></abbr><colgroup></colgroup><lin\u{212A}></lin\u{212A}></div></foreignObject></svg>",
        ];

        yield 'Prefixed XHTML element holding only whitespace' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:h=\"http://www.w3.org/1999/xhtml\"><foreignObject><h:div>\n  </h:div></foreignObject></svg>",
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:h="http://www.w3.org/1999/xhtml"><foreignObject><h:div></h:div></foreignObject></svg>',
        ];

        yield 'Elements in other namespaces stay self-closing' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g/><foreignObject><div/><x:div xmlns:x="urn:x"/></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g/><foreignObject><div/><x:div xmlns:x="urn:x"/></foreignObject></svg>',
        ];
    }

    /**
     * @param non-empty-string $content
     *
     * @throws XmlProcessingException
     */
    #[DataProvider('provideSaveToStringWritesNonAsciiCharactersAsUtf8Cases')]
    #[Test]
    public function saveToStringWritesNonAsciiCharactersAsUtf8(string $content): void
    {
        $saved = $this->domDocumentWrapper->saveToString($this->domDocumentWrapper->loadFromString($content));

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg" font-family="日本 é 😀"><text title="ü&amp;">日本 é 😀</text></svg>', $saved);
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function provideSaveToStringWritesNonAsciiCharactersAsUtf8Cases(): iterable
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" font-family="日本 é 😀"><text title="ü&amp;">日本 é 😀</text></svg>';

        yield 'Without an XML declaration' => [$svg];

        yield 'With an XML declaration without encoding' => ['<?xml version="1.0"?>' . $svg];

        yield 'With a UTF-8 declaration' => ['<?xml version="1.0" encoding="UTF-8"?>' . $svg];

        yield 'With a lowercase utf-8 declaration' => ["<?xml version='1.0' encoding='utf-8'?>" . $svg];

        yield 'As character references' => ['<svg xmlns="http://www.w3.org/2000/svg" font-family="&#x65E5;&#26412; &#xE9; &#x1F600;"><text title="&#xFC;&amp;">&#x65E5;&#x672C; &#xE9; &#x1F600;</text></svg>'];

        yield 'UTF-16LE' => ["\xFF\xFE" . mb_convert_encoding($svg, 'UTF-16LE', 'UTF-8')];
    }

    #[Test]
    public function saveToStringDoesNotSetAnEncodingOnADocumentWithoutOne(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg" font-family="日本"/>');

        $saved = $this->domDocumentWrapper->saveToString($domDocument);

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg" font-family="&#x65E5;&#x672C;"/>', $saved);
        self::assertNull($domDocument->encoding);
    }

    /**
     * @throws XmlProcessingException
     */
    #[Test]
    public function loadFromStringGivesADocumentWithoutDeclarationTheUtf8Encoding(): void
    {
        self::assertSame('UTF-8', $this->domDocumentWrapper->loadFromString('<svg xmlns="http://www.w3.org/2000/svg"/>')->encoding);
        self::assertSame('ISO-8859-1', $this->domDocumentWrapper->loadFromString('<?xml version="1.0" encoding="ISO-8859-1"?><svg xmlns="http://www.w3.org/2000/svg"/>')->encoding);
    }

    #[Test]
    public function saveToStringKeepsTheEncodingOfTheDocument(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML("<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?><svg xmlns=\"http://www.w3.org/2000/svg\" font-family=\"caf\xE9\"/>");

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg" font-family="café"/>', $this->domDocumentWrapper->saveToString($domDocument));
        self::assertSame('ISO-8859-1', $domDocument->encoding);
    }

    #[Test]
    public function saveToStringLeavesNoPlaceholderInTheDocument(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><div xmlns="http://www.w3.org/1999/xhtml"/></foreignObject></svg>');

        $this->domDocumentWrapper->saveToString($domDocument);

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><div xmlns="http://www.w3.org/1999/xhtml"/></foreignObject></svg>', $domDocument->saveXML($domDocument->documentElement));
    }

    #[Test]
    public function loadFromFileRejectsAPathWithANullByteAndRestoresTheLibxmlErrorState(): void
    {
        $previous = libxml_use_internal_errors(false);

        try {
            $this->domDocumentWrapper->loadFromFile("icon\0.svg");
            self::fail('Expected an XmlProcessingException.');
        } catch (XmlProcessingException $xmlProcessingException) {
            self::assertSame('Failed to load DOMDocument.', $xmlProcessingException->getMessage());
            self::assertInstanceOf(\ValueError::class, $xmlProcessingException->getPrevious());
            self::assertFalse(libxml_use_internal_errors());
        } finally {
            libxml_use_internal_errors($previous);
        }
    }

    /**
     * @param non-empty-string $content
     *
     * @throws XmlProcessingException
     */
    #[DataProvider('provideLoadFromStringConvertsTheInputEncodingToUtf8Cases')]
    #[Test]
    public function loadFromStringConvertsTheInputEncodingToUtf8(string $content): void
    {
        $domDocument = $this->domDocumentWrapper->loadFromString($content);

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"><text>café €</text></svg>', $this->domDocumentWrapper->saveToString($domDocument));
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function provideLoadFromStringConvertsTheInputEncodingToUtf8Cases(): iterable
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><text>café €</text></svg>';

        yield 'UTF-8 with a byte order mark' => ["\xEF\xBB\xBF" . $svg];

        yield 'UTF-16LE with a byte order mark' => ["\xFF\xFE" . mb_convert_encoding('<?xml version="1.0" encoding="UTF-16"?>' . $svg, 'UTF-16LE', 'UTF-8')];

        yield 'UTF-16BE with a byte order mark' => ["\xFE\xFF" . mb_convert_encoding('<?xml version="1.0" encoding="UTF-16"?>' . $svg, 'UTF-16BE', 'UTF-8')];

        yield 'Windows-1252 declared in the XML declaration' => ['<?xml version="1.0" encoding="windows-1252"?>' . mb_convert_encoding($svg, 'Windows-1252', 'UTF-8')];

        yield 'UTF-16 declared on UTF-8 bytes without a byte order mark' => ['<?xml version="1.0" encoding="utf-16"?>' . $svg];

        yield 'UTF-16LE declared in single quotes on UTF-8 bytes' => ["<?xml version='1.0' encoding='UTF-16LE' standalone='no'?>\n" . $svg];

        yield 'UTF-16BE declared on UTF-8 bytes with a byte order mark' => ["\xEF\xBB\xBF<?xml version=\"1.0\" encoding = \"Utf-16be\"?>" . $svg];
    }

    /**
     * @throws XmlProcessingException
     */
    #[Test]
    public function loadFromStringAppliesTheAttributeDefaultsOfTheInternalSubset(): void
    {
        $domDocument = $this->domDocumentWrapper->loadFromString('<!DOCTYPE svg [<!ATTLIST rect fill CDATA "red">]><svg xmlns="http://www.w3.org/2000/svg"><rect/><rect fill="blue"/></svg>');

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"><rect fill="red"/><rect fill="blue"/></svg>', $this->domDocumentWrapper->saveToString($domDocument));
    }

    /**
     * @throws XmlProcessingException
     */
    #[Test]
    public function loadFromStringIgnoresAttributeDefaultsWithAnExternalSubset(): void
    {
        $domDocument = $this->domDocumentWrapper->loadFromString('<!DOCTYPE svg SYSTEM "missing.dtd" [<!ATTLIST rect fill CDATA "red">]><svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>');

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>', $this->domDocumentWrapper->saveToString($domDocument));
    }

    /**
     * @throws XmlProcessingException
     */
    #[Test]
    public function loadFromStringIgnoresAttributeDefaultsWithAPublicIdentifier(): void
    {
        $domDocument = $this->domDocumentWrapper->loadFromString('<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "" [<!ATTLIST rect fill CDATA "red">]><svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>');

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>', $this->domDocumentWrapper->saveToString($domDocument));
    }

    /**
     * @throws XmlProcessingException
     */
    #[Test]
    public function loadFromStringRejectsBytesThatAreNotValidInTheDeclaredEncoding(): void
    {
        $this->expectException(XmlProcessingException::class);

        $this->domDocumentWrapper->loadFromString("<svg xmlns=\"http://www.w3.org/2000/svg\"><text>\xC3\x28</text></svg>");
    }

    /**
     * @throws XmlProcessingException
     */
    #[Test]
    public function saveToStringKeepsOnlyStylesheetInstructionsOutsideTheDocumentElement(): void
    {
        $domDocument = $this->domDocumentWrapper->loadFromString(
            '<?xml version="1.0"?><!-- before --><?foo bar?><?XML-STYLESHEET href="a.css"?><!DOCTYPE svg><svg xmlns="http://www.w3.org/2000/svg"/><!-- after --><?xml-stylesheet href="b.css"?>',
        );

        self::assertSame(
            '<?XML-STYLESHEET href="a.css"?><?xml-stylesheet href="b.css"?><svg xmlns="http://www.w3.org/2000/svg"/>',
            $this->domDocumentWrapper->saveToString($domDocument),
        );
    }

    /**
     * @throws XmlProcessingException
     */
    #[Test]
    public function loadFromStringResolvesEntitiesUsedAsNamespaceUris(): void
    {
        $domDocument = $this->domDocumentWrapper->loadFromString(
            '<!DOCTYPE svg [<!ENTITY ns_svg "http://www.w3.org/2000/svg"><!ENTITY ns_x "urn:x">]><svg xmlns="&ns_svg;" xmlns:x="&ns_x;"><x:a/></svg>',
        );

        self::assertSame('http://www.w3.org/2000/svg', $domDocument->documentElement?->namespaceURI);
        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><x:a/></svg>', $this->domDocumentWrapper->saveToString($domDocument));
    }

    /**
     * @param non-empty-string $content
     *
     * @throws XmlProcessingException
     */
    #[DataProvider('provideLoadFromStringRejectsEntityExpansionBeyondTheAllowedSizeCases')]
    #[Test]
    public function loadFromStringRejectsEntityExpansionBeyondTheAllowedSize(string $content): void
    {
        $this->expectException(XmlProcessingException::class);
        $this->expectExceptionMessage('Entity expansion exceeds the allowed size.');

        $this->domDocumentWrapper->loadFromString($content);
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function provideLoadFromStringRejectsEntityExpansionBeyondTheAllowedSizeCases(): iterable
    {
        $large = str_repeat('A', 100_000);

        yield 'Many references to a large entity in text' => [
            '<!DOCTYPE svg [<!ENTITY a "' . $large . '">]><svg xmlns="http://www.w3.org/2000/svg"><text>' . str_repeat('&a;', 20) . '</text></svg>',
        ];

        yield 'Many references to a large entity in attributes' => [
            '<!DOCTYPE svg [<!ENTITY a "' . $large . '">]><svg xmlns="http://www.w3.org/2000/svg">' . str_repeat('<g id="&a;"/>', 20) . '</svg>',
        ];

        yield 'References to a nested entity that repeats a large entity' => [
            '<!DOCTYPE svg [<!ENTITY a "' . str_repeat('A', 50_000) . '"><!ENTITY b "&a;&a;&a;&a;&a;">]><svg xmlns="http://www.w3.org/2000/svg"><text>' . str_repeat('&b;', 10) . '</text></svg>',
        ];

        yield 'Large entity declared with character references' => [
            '<!DOCTYPE svg [<!ENTITY a "' . str_repeat('&#x41;', 20_000) . '">]><svg xmlns="http://www.w3.org/2000/svg"><text>' . str_repeat('&a;', 100) . '</text></svg>',
        ];

        yield 'References one character beyond the allowed size' => [
            '<!DOCTYPE svg [<!ENTITY a "' . str_repeat('A', 1_024) . '"><!ENTITY b "B">]><svg xmlns="http://www.w3.org/2000/svg"><text>' . str_repeat('&a;', 1_024) . '&amp;&b;</text></svg>',
        ];

        yield 'References to nested entities one character beyond the allowed size' => [
            self::nestedEntityDocument('&c;&d;'),
        ];

        yield 'A single reference to a nested entity beyond the allowed size' => [
            '<!DOCTYPE svg [<!ENTITY a "' . str_repeat('A', 1_024) . '"><!ENTITY b "' . str_repeat('&a;', 1_100) . '">]><svg xmlns="http://www.w3.org/2000/svg"><text>&b;</text></svg>',
        ];
    }

    /**
     * @param non-empty-string $content
     *
     * @throws XmlProcessingException
     */
    #[DataProvider('provideLoadFromStringAcceptsEntityExpansionUpToTheAllowedSizeCases')]
    #[Test]
    public function loadFromStringAcceptsEntityExpansionUpToTheAllowedSize(string $content): void
    {
        $domDocument = $this->domDocumentWrapper->loadFromString($content);

        self::assertInstanceOf(\DOMElement::class, $domDocument->documentElement);
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function provideLoadFromStringAcceptsEntityExpansionUpToTheAllowedSizeCases(): iterable
    {
        yield 'References that expand to exactly the allowed size' => [
            '<!DOCTYPE svg [<!ENTITY a "' . str_repeat('A', 1_024) . '"><!ENTITY b "B">]><svg xmlns="http://www.w3.org/2000/svg"><text>' . str_repeat('&a;', 1_024) . '&amp;</text></svg>',
        ];

        yield 'References to nested entities that expand to exactly the allowed size' => [
            self::nestedEntityDocument('&c;'),
        ];

        yield 'References in a large document that expand beyond the minimal allowed size' => [
            '<!DOCTYPE svg [<!ENTITY a "' . str_repeat('A', 1_024) . '">]><svg xmlns="http://www.w3.org/2000/svg"><!--' . str_repeat('x', 200_000) . '--><text>' . str_repeat('&a;', 1_500) . '</text></svg>',
        ];
    }

    /**
     * @return non-empty-string
     */
    private static function nestedEntityDocument(string $suffix): string
    {
        return '<!DOCTYPE svg [<!ENTITY a "' . str_repeat('A', 1_024) . '"><!ENTITY ext SYSTEM "ext.txt"><!ENTITY b "&a;&amp;&ext;&a;"><!ENTITY c "' . str_repeat('C', 1_546) . '"><!ENTITY d "D">]><svg xmlns="http://www.w3.org/2000/svg"><text>' . str_repeat('&b;', 510) . $suffix . '</text></svg>';
    }

    /**
     * @param non-empty-string $content
     *
     * @throws XmlProcessingException
     */
    #[DataProvider('provideLoadFromStringExpandsEntitiesWithinTheAllowedSizeCases')]
    #[Test]
    public function loadFromStringExpandsEntitiesWithinTheAllowedSize(string $content, int $expectedLength): void
    {
        $domDocument = $this->domDocumentWrapper->loadFromString($content);

        self::assertSame($expectedLength, mb_strlen($domDocument->documentElement->textContent ?? '', '8bit'));
    }

    /**
     * @return iterable<string, array{non-empty-string, int}>
     */
    public static function provideLoadFromStringExpandsEntitiesWithinTheAllowedSizeCases(): iterable
    {
        yield 'Expansion below the size floor' => [
            '<!DOCTYPE svg [<!ENTITY a "' . str_repeat('A', 1_000) . '">]><svg xmlns="http://www.w3.org/2000/svg"><text>' . str_repeat('&a;', 1_000) . '</text></svg>',
            1_000_000,
        ];

        yield 'Large entity referenced once' => [
            '<!DOCTYPE svg [<!ENTITY a "' . str_repeat('A', 2_000_000) . '">]><svg xmlns="http://www.w3.org/2000/svg"><text>&a;</text></svg>',
            2_000_000,
        ];

        yield 'Nested, predefined and undeclared references inside an entity' => [
            '<!DOCTYPE svg [<!ENTITY a "aa"><!ENTITY b "&a;&amp;&a;">]><svg xmlns="http://www.w3.org/2000/svg"><text>&b;&b;</text></svg>',
            10,
        ];
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->domDocumentWrapper = new DomDocumentWrapper();
    }
}
