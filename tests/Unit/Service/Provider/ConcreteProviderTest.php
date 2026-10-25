<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Provider;

use MathiasReker\PhpSvgOptimizer\Exception\IOException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\AbstractProvider;
use MathiasReker\PhpSvgOptimizer\Tests\Unit\Support\TemporaryDirectoryTrait;
use MathiasReker\PhpSvgOptimizer\ValueObject\Metrics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AbstractProvider::class)]
#[CoversClass(IOException::class)]
#[CoversClass(Metrics::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class ConcreteProviderTest extends TestCase
{
    use TemporaryDirectoryTrait;

    /**
     * @throws XmlProcessingException
     */
    #[Test]
    public function optimizeTrimsXmlContent(): void
    {
        $input = "<?xml version=\"1.0\"?>\n<svg>  hello  </svg>";
        $provider = $this->getConcreteProvider($input);
        $domDocument = $provider->loadContent();

        $provider->optimize($domDocument);
        $output = $provider->getOutputContent();

        self::assertStringStartsNotWith('<?xml', $output);
        self::assertSame(trim($output), $output, 'Output should be trimmed');
    }

    private function getConcreteProvider(string $inputContent): AbstractProvider
    {
        return new class($inputContent) extends AbstractProvider {
            public function __construct(
                private readonly string $testInput,
            ) {
                parent::__construct();
            }

            public function loadContent(): \DOMDocument
            {
                $domDocument = new \DOMDocument();
                if ('' !== $this->testInput) {
                    $domDocument->loadXML($this->testInput);
                }

                return $domDocument;
            }

            public function getInputContent(): string
            {
                return $this->testInput;
            }
        };
    }

    /**
     * @throws XmlProcessingException
     * @throws IOException
     */
    #[Test]
    public function saveToFileWritesCorrectly(): void
    {
        $input = '<svg>saved</svg>';
        $provider = $this->getConcreteProvider($input);
        $provider->optimize($provider->loadContent());

        $file = $this->createTemporaryDirectory() . '/svg-test.svg';
        $provider->saveToFile($file);

        self::assertFileExists($file);
        self::assertSame($provider->getOutputContent(), file_get_contents($file));
    }

    /**
     * @throws XmlProcessingException
     * @throws IOException
     */
    #[Test]
    public function saveToFileThrowsWhenDirectoryFails(): void
    {
        $this->expectException(IOException::class);

        $input = '<svg>error</svg>';
        $provider = $this->getConcreteProvider($input);
        $domDocument = $provider->loadContent();
        $provider->optimize($domDocument);

        $baseFile = $this->createTemporaryDirectory() . '/svgtest';
        file_put_contents($baseFile, '');
        $invalidPath = $baseFile . '/subdir/output.svg';
        error_clear_last();

        try {
            $provider->saveToFile($invalidPath);
        } finally {
            self::assertNull(error_get_last());
        }
    }

    /**
     * @throws XmlProcessingException
     * @throws IOException
     */
    #[Test]
    public function saveToFileCreatesDirectory(): void
    {
        $input = '<svg>dir creation</svg>';
        $provider = $this->getConcreteProvider($input);
        $provider->optimize($provider->loadContent());

        $tempDir = $this->createTemporaryDirectory() . '/svgtest';
        $filePath = $tempDir . '/output.svg';

        self::assertDirectoryDoesNotExist($tempDir);

        $provider->saveToFile($filePath);

        self::assertDirectoryExists($tempDir);
        self::assertFileExists($filePath);
        self::assertSame($provider->getOutputContent(), file_get_contents($filePath));
    }

    /**
     * @throws XmlProcessingException
     * @throws IOException
     */
    #[Test]
    public function saveToFileThrowsIOExceptionWhenWriteFails(): void
    {
        $input = '<svg>unwritable</svg>';
        $provider = $this->getConcreteProvider($input);
        $provider->optimize($provider->loadContent());

        $directoryAsFilePath = $this->createTemporaryDirectory();

        $this->expectException(IOException::class);
        $this->expectExceptionMessage('Output path is a directory: ' . $directoryAsFilePath);

        set_error_handler(static fn (): bool => true, \E_WARNING);
        try {
            $provider->saveToFile($directoryAsFilePath);
        } finally {
            restore_error_handler();
        }
    }
}
