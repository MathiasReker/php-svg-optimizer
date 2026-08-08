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
use MathiasReker\PhpSvgOptimizer\ValueObject\Metrics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(FileProvider::class)]
#[CoversClass(MetaData::class)]
#[CoversClass(Metrics::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(XmlFormatter::class)]
final class FileProviderTest extends TestCase
{
    private const string TEST_INPUT_FILE = 'input.svg';

    /**
     * @throws FileNotFoundException
     * @throws IOException
     */
    #[Test]
    public function getInputContent(): void
    {
        $fileProvider = new FileProvider(self::TEST_INPUT_FILE);
        $content = $fileProvider->getInputContent();

        self::assertStringContainsString('<svg', $content);
        self::assertStringContainsString('</svg>', $content);
    }

    /**
     * @throws FileNotFoundException
     * @throws IOException
     */
    #[Test]
    public function getInputContentThrowsExceptionIfFileDoesNotExist(): void
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
    #[Test]
    public function loadContentReturnsDomDocument(): void
    {
        $fileProvider = new FileProvider(self::TEST_INPUT_FILE);

        $domDocument = $fileProvider->loadContent();

        self::assertNotNull($domDocument->documentElement);
        self::assertSame('svg', $domDocument->documentElement->tagName);
    }

    /**
     * @throws FileNotFoundException
     * @throws IOException
     */
    #[Test]
    public function getOutputContentBeforeOptimizeReturnsEmptyString(): void
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
    #[Test]
    public function optimizeModifiesOutputContent(): void
    {
        $fileProvider = new FileProvider(self::TEST_INPUT_FILE);

        $domDocument = $fileProvider->loadContent();

        if ($domDocument->documentElement instanceof \DOMElement) {
            $domDocument->documentElement->setAttribute('id', 'svg1');
        }

        $fileProvider->optimize($domDocument);
        $output = $fileProvider->getOutputContent();

        self::assertStringContainsString('id="svg1"', $output);
    }

    /**
     * @throws XmlProcessingException
     * @throws FileNotFoundException
     * @throws IOException
     */
    #[Test]
    public function optimize(): void
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
     * @throws IOException
     */
    #[Test]
    public function optimizeWithMinimalSvg(): void
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
    #[Test]
    public function loadContentThrowsXmlProcessingException(): void
    {
        file_put_contents(self::TEST_INPUT_FILE, '<svg><invalid></svg>');

        $fileProvider = new FileProvider(self::TEST_INPUT_FILE);

        $this->expectException(XmlProcessingException::class);
        $fileProvider->loadContent();
    }

    /**
     * @throws XmlProcessingException
     * @throws FileNotFoundException
     * @throws IOException
     */
    #[Test]
    public function loadContentThrowsXmlProcessingExceptionOnMalformedXml(): void
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
    #[Test]
    public function optimizeUpdatesOutputContentWithAttributes(): void
    {
        $fileProvider = new FileProvider(self::TEST_INPUT_FILE);
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        if ($domDocument->documentElement instanceof \DOMElement) {
            $domDocument->documentElement->setAttribute('data-test', 'value');
        }

        $fileProvider->optimize($domDocument);

        $output = $fileProvider->getOutputContent();

        self::assertStringContainsString('data-test="value"', $output);
    }

    /**
     * @throws FileNotFoundException
     * @throws IOException
     * @throws \ReflectionException
     */
    #[Test]
    public function optimizeThrowsTypeErrorOnInvalidInput(): void
    {
        $fileProvider = new FileProvider(self::TEST_INPUT_FILE);

        $this->expectException(\TypeError::class);

        $reflectionMethod = new \ReflectionMethod($fileProvider, 'optimize');
        $reflectionMethod->invoke($fileProvider, null);
    }

    /**
     * @throws FileNotFoundException
     * @throws IOException
     * @throws XmlProcessingException
     */
    #[Test]
    public function saveToFileThrowsIOException(): void
    {
        $fileProvider = new FileProvider(self::TEST_INPUT_FILE);
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg/>');

        $fileProvider->optimize($domDocument);

        $nonWritablePath = '/non/writable/path/output.svg';
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('Failed to create directory for output file: ' . $nonWritablePath);

        $fileProvider->saveToFile($nonWritablePath);
    }

    /**
     * @throws FileNotFoundException
     * @throws IOException
     */
    #[Test]
    public function getInputContentThrowsIOExceptionWhenFileIsNotReadable(): void
    {
        $probeFile = sys_get_temp_dir() . '/' . uniqid('unreadable_probe_', true) . '.svg';
        file_put_contents($probeFile, '<svg></svg>');
        chmod($probeFile, 0o000);
        clearstatcache(true, $probeFile);
        $chmodMakesUnreadable = !is_readable($probeFile);
        chmod($probeFile, 0o644);
        unlink($probeFile);

        $userName = (string) getenv('USERNAME');
        $filePath = sys_get_temp_dir() . '/' . uniqid('unreadable_', true) . '.svg';
        file_put_contents($filePath, '<svg></svg>');

        try {
            if ($chmodMakesUnreadable) {
                chmod($filePath, 0o000);
            } else {
                exec('icacls ' . escapeshellarg($filePath) . ' /deny ' . escapeshellarg($userName . ':R') . ' 2>&1');
            }

            clearstatcache(true, $filePath);

            self::assertIsNotReadable(
                $filePath,
                'Precondition failed: could not make the file unreadable in this environment.'
            );

            $this->expectException(IOException::class);
            $this->expectExceptionMessage(\sprintf('Input file is not readable: %s', $filePath));

            new FileProvider($filePath);
        } finally {
            if ($chmodMakesUnreadable) {
                chmod($filePath, 0o644);
            } else {
                exec('icacls ' . escapeshellarg($filePath) . ' /remove:d ' . escapeshellarg($userName) . ' 2>&1');
            }

            clearstatcache(true, $filePath);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    #[\Override]
    protected function setUp(): void
    {
        file_put_contents(self::TEST_INPUT_FILE, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100"/></svg>');
    }

    protected function tearDown(): void
    {
        if (file_exists(self::TEST_INPUT_FILE)) {
            unlink(self::TEST_INPUT_FILE);
        }

        parent::tearDown();
    }
}
