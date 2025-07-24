<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Provider;

use MathiasReker\PhpSvgOptimizer\Exception\FileNotFoundException;
use MathiasReker\PhpSvgOptimizer\Exception\IOException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Service\Data\MetaData;
use MathiasReker\PhpSvgOptimizer\Service\Formatter\XmlFormatter;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\FileProvider;
use MathiasReker\PhpSvgOptimizer\ValueObject\MetaDataValueObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(FileProvider::class)]
#[CoversClass(MetaData::class)]
#[CoversClass(MetaDataValueObject::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(XmlFormatter::class)]
final class FileProviderTest extends TestCase
{
    /**
     * The path to the test input SVG file.
     * This file is used for testing the FileProvider's methods.
     */
    private const string TEST_INPUT_FILE = 'input.svg';

    /**
     * @throws FileNotFoundException
     * @throws IOException
     */
    public function testGetInputContent(): void
    {
        $fileProvider = new FileProvider(self::TEST_INPUT_FILE);
        $content = $fileProvider->getInputContent();

        self::assertStringContainsString('<svg', $content);
        self::assertStringContainsString('</svg>', $content);
    }

    /**
     * @throws XmlProcessingException
     * @throws FileNotFoundException
     * @throws IOException
     */
    public function testOptimize(): void
    {
        $fileProvider = new FileProvider(self::TEST_INPUT_FILE);
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100"/></svg>');

        $fileProvider->optimize($domDocument);

        $outputContent = $fileProvider->getOutputContent();
        self::assertStringContainsString('<svg', $outputContent);
        self::assertStringContainsString('</svg>', $outputContent);
    }

    /**
     * @throws XmlProcessingException
     * @throws FileNotFoundException
     * @throws \InvalidArgumentException
     * @throws IOException
     */
    public function testGetMetaData(): void
    {
        $fileProvider = new FileProvider(self::TEST_INPUT_FILE);
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100"/></svg>');

        $fileProvider->optimize($domDocument);

        $metaDataValueObject = $fileProvider->getMetaData();

        self::assertSame(filesize(self::TEST_INPUT_FILE), $metaDataValueObject->getOriginalSize());
    }

    /**
     * @throws FileNotFoundException
     * @throws IOException
     */
    public function testGetInputContentThrowsExceptionIfFileDoesNotExist(): void
    {
        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('Input file does not exist: nonexistent.svg');

        new FileProvider('nonexistent.svg');
    }

    /**
     * @throws XmlProcessingException
     * @throws FileNotFoundException
     * @throws IOException
     */
    public function testOptimizeThrowsExceptionIfSaveXMLFails(): void
    {
        $fileProvider = new FileProvider(self::TEST_INPUT_FILE);
        $domDocument = $this->createMock(\DOMDocument::class);
        $domDocument->method('saveXML')->willReturn(false);

        $this->expectException(XmlProcessingException::class);
        $this->expectExceptionMessage('Failed to save XML content');

        $fileProvider->optimize($domDocument);
    }

    /**
     * @throws XmlProcessingException
     * @throws FileNotFoundException
     * @throws IOException
     */
    public function testLoadContentReturnsDomDocument(): void
    {
        $fileProvider = new FileProvider(self::TEST_INPUT_FILE);

        $domDocument = $fileProvider->loadContent();

        /*
         * @phpstan-ignore-next-line
         */
        self::assertSame('svg', $domDocument->documentElement->tagName);
    }

    /**
     * @throws FileNotFoundException
     * @throws IOException
     */
    public function testGetOutputContentBeforeOptimizeReturnsEmptyString(): void
    {
        $fileProvider = new FileProvider(self::TEST_INPUT_FILE);

        $output = $fileProvider->getOutputContent();

        self::assertSame('', $output);
    }

    /**
     * @throws XmlProcessingException
     * @throws FileNotFoundException
     * @throws IOException
     */
    public function testOptimizeModifiesOutputContent(): void
    {
        $fileProvider = new FileProvider(self::TEST_INPUT_FILE);

        $domDocument = $fileProvider->loadContent();
        /*
         * @phpstan-ignore-next-line
         */
        $domDocument->documentElement->setAttribute('id', 'svg1');

        $fileProvider->optimize($domDocument);
        $output = $fileProvider->getOutputContent();

        self::assertStringContainsString('id="svg1"', $output);
    }

    /**
     * @throws XmlProcessingException
     * @throws FileNotFoundException
     * @throws IOException
     * @throws \InvalidArgumentException
     */
    public function testGetMetaDataReflectsOptimizedSize(): void
    {
        $fileProvider = new FileProvider(self::TEST_INPUT_FILE);

        $domDocument = $fileProvider->loadContent();
        $fileProvider->optimize($domDocument);

        $metaDataValueObject = $fileProvider->getMetaData();

        self::assertGreaterThan(0, $metaDataValueObject->getOriginalSize());
        self::assertGreaterThan(0, $metaDataValueObject->getOptimizedSize());
        self::assertGreaterThanOrEqual(0.0, $metaDataValueObject->getSavedPercentage());
    }

    /**
     * @throws FileNotFoundException
     * @throws IOException
     * @throws \InvalidArgumentException
     */
    public function testProviderReturnsZeroBeforeOptimization(): void
    {
        $fileProvider = new FileProvider(self::TEST_INPUT_FILE);
        $metaDataValueObject = $fileProvider->getMetaData();

        self::assertSame(filesize(self::TEST_INPUT_FILE), $metaDataValueObject->getOriginalSize());
        self::assertSame(0, $metaDataValueObject->getOptimizedSize());
    }

    /**
     * @throws XmlProcessingException
     * @throws FileNotFoundException
     * @throws IOException
     */
    public function testOptimizeWithMinimalSvg(): void
    {
        file_put_contents(
            self::TEST_INPUT_FILE,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><text textContent="a &amp; b"></text></svg>
                XML
        );

        $fileProvider = new FileProvider(self::TEST_INPUT_FILE);
        $domDocument = $fileProvider->loadContent();

        $fileProvider->optimize($domDocument);
        $output = $fileProvider->getOutputContent();

        self::assertStringContainsString('<svg', $output);
    }

    /**
     * @throws XmlProcessingException
     * @throws FileNotFoundException
     * @throws IOException
     */
    public function testLoadContentThrowsXmlProcessingException(): void
    {
        file_put_contents(self::TEST_INPUT_FILE, '<svg><invalid></svg>'); // malformed XML

        $fileProvider = new FileProvider(self::TEST_INPUT_FILE);

        $this->expectException(XmlProcessingException::class);
        $fileProvider->loadContent();
    }

    /**
     * @throws XmlProcessingException
     * @throws FileNotFoundException
     * @throws IOException
     * @throws \InvalidArgumentException
     * @throws \DivisionByZeroError
     */
    public function testMetaDataSavedPercentageCalculation(): void
    {
        $fileProvider = new FileProvider(self::TEST_INPUT_FILE);

        $domDocument = $fileProvider->loadContent();
        $fileProvider->optimize($domDocument);

        $metaDataValueObject = $fileProvider->getMetaData();

        $expectedSaved = $metaDataValueObject->getOriginalSize() - $metaDataValueObject->getOptimizedSize();
        $expectedPercentage = $expectedSaved / $metaDataValueObject->getOriginalSize() * 100;

        self::assertEqualsWithDelta($expectedPercentage, $metaDataValueObject->getSavedPercentage(), 0.01);
    }

    /**
     * @throws XmlProcessingException
     * @throws FileNotFoundException
     * @throws IOException
     */
    public function testLoadContentThrowsXmlProcessingExceptionOnMalformedXml(): void
    {
        file_put_contents(self::TEST_INPUT_FILE, '<svg><unclosed></svg>');

        $fileProvider = new FileProvider(self::TEST_INPUT_FILE);

        $this->expectException(XmlProcessingException::class);

        $fileProvider->loadContent();
    }

    /**
     * @throws XmlProcessingException
     * @throws FileNotFoundException
     * @throws IOException
     */
    public function testOptimizeUpdatesOutputContentWithAttributes(): void
    {
        $fileProvider = new FileProvider(self::TEST_INPUT_FILE);
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        /*
         * @phpstan-ignore-next-line
         */
        $domDocument->documentElement->setAttribute('data-test', 'value');

        $fileProvider->optimize($domDocument);

        $output = $fileProvider->getOutputContent();

        self::assertStringContainsString('data-test="value"', $output);
    }

    /**
     * @throws XmlProcessingException
     * @throws FileNotFoundException
     * @throws IOException
     * @throws \InvalidArgumentException
     */
    public function testMetaDataReflectsMultipleOptimizations(): void
    {
        $fileProvider = new FileProvider(self::TEST_INPUT_FILE);

        $domDocument = $fileProvider->loadContent();
        $fileProvider->optimize($domDocument);

        $metaDataValueObject = $fileProvider->getMetaData();

        /*
         * @phpstan-ignore-next-line
         */
        $domDocument->documentElement->setAttribute('class', 'test');
        $fileProvider->optimize($domDocument);

        $secondMeta = $fileProvider->getMetaData();

        self::assertSame($metaDataValueObject->getOriginalSize(), $secondMeta->getOriginalSize());
        self::assertNotSame($metaDataValueObject->getOptimizedSize(), $secondMeta->getOptimizedSize());
    }

    /**
     * @throws XmlProcessingException
     * @throws FileNotFoundException
     * @throws IOException
     */
    public function testOptimizeThrowsTypeErrorOnInvalidInput(): void
    {
        $fileProvider = new FileProvider(self::TEST_INPUT_FILE);

        $this->expectException(\TypeError::class);

        /*
         * @phpstan-ignore-next-line
         */
        $fileProvider->optimize(null);
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        file_put_contents(self::TEST_INPUT_FILE, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100"/></svg>');
    }

    #[\Override]
    protected function tearDown(): void
    {
        if (file_exists(self::TEST_INPUT_FILE)) {
            unlink(self::TEST_INPUT_FILE);
        }

        parent::tearDown();
    }
}
