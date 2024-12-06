<?php

/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Services\Util;

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

        Assert::assertNotFalse($result);
        Assert::assertStringContainsString('<root><child>Test</child></root>', $result);
    }

    public function testSaveToStringWithLineFeedsAndTabs(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML("<root>\n\t<child>\n\t\tTest\n\t</child>\n</root>");

        $result = $this->domDocumentWrapper->saveToString($domDocument);

        Assert::assertNotFalse($result);

        Assert::assertStringNotContainsString("\n", $result);
        Assert::assertStringNotContainsString("\t", $result);
        Assert::assertStringContainsString('<root><child>Test</child></root>', $result);
    }

    public function testLoadFromFileValid(): void
    {
        $filePath = __DIR__ . '/test.xml';

        try {
            file_put_contents($filePath, '<root><child>Test</child></root>');
            $domDocument = $this->domDocumentWrapper->loadFromFile($filePath);

            Assert::assertInstanceOf(\DOMDocument::class, $domDocument);
            $xmlString = $domDocument->saveXML();

            Assert::assertNotFalse($xmlString);
            Assert::assertStringContainsString('<root><child>Test</child></root>', $xmlString);
        } finally {
            unlink($filePath);
        }
    }

    public function testLoadFromStringValid(): void
    {
        $xmlContent = '<root><child>Test</child></root>';
        $domDocument = $this->domDocumentWrapper->loadFromString($xmlContent);

        Assert::assertInstanceOf(\DOMDocument::class, $domDocument);
        $xmlString = $domDocument->saveXML();

        Assert::assertNotFalse($xmlString);
        Assert::assertStringContainsString('<root><child>Test</child></root>', $xmlString);
    }

    public function testLoadFromStringWithLineFeedsAndTabs(): void
    {
        $xmlContent = "<root>\n\t<child>Test</child>\n</root>";
        $domDocument = $this->domDocumentWrapper->loadFromString($xmlContent);

        Assert::assertInstanceOf(\DOMDocument::class, $domDocument);

        $xmlString = $this->domDocumentWrapper->saveToString($domDocument);

        Assert::assertNotFalse($xmlString);

        Assert::assertStringNotContainsString("\n", $xmlString, 'Newline characters were not removed.');
        Assert::assertStringNotContainsString("\t", $xmlString, 'Tab characters were not removed.');
        Assert::assertStringContainsString('<root><child>Test</child></root>', $xmlString);
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->domDocumentWrapper = new DomDocumentWrapper();
    }
}
