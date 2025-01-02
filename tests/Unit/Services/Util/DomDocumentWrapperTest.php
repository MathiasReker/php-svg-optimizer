<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Services\Util;

use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Services\Util\DomDocumentWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
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
        $xmlContent = '<root><child>Test</child></root>';
        $domDocument = $this->domDocumentWrapper->loadFromString($xmlContent);

        $xmlString = $domDocument->saveXML();

        self::assertNotFalse($xmlString, 'saveXML() returned false');
        self::assertStringContainsString('<root><child>Test</child></root>', $xmlString);
    }

    /**
     * @throws XmlProcessingException
     */
    public function testLoadFromStringWithLineFeedsAndTabs(): void
    {
        $xmlContent = "<root>\n\t<child>Test</child>\n</root>";
        $domDocument = $this->domDocumentWrapper->loadFromString($xmlContent);

        $xmlString = $this->domDocumentWrapper->saveToString($domDocument);

        self::assertStringNotContainsString("\n", $xmlString, 'Newline characters were not removed.');
        self::assertStringNotContainsString("\t", $xmlString, 'Tab characters were not removed.');
        self::assertStringContainsString('<root><child>Test</child></root>', $xmlString);
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->domDocumentWrapper = new DomDocumentWrapper();
    }
}
