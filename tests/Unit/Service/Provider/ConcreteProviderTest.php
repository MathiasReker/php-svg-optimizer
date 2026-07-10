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
use MathiasReker\PhpSvgOptimizer\Service\Data\MetaData;
use MathiasReker\PhpSvgOptimizer\Service\Formatter\XmlFormatter;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\AbstractProvider;
use MathiasReker\PhpSvgOptimizer\ValueObject\MetaDataValueObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AbstractProvider::class)]
#[CoversClass(IOException::class)]
#[CoversClass(MetaData::class)]
#[CoversClass(MetaDataValueObject::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(XmlFormatter::class)]
final class ConcreteProviderTest extends TestCase
{
    private string $tmpFile = '';

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
                $this->inputContent = $testInput;
            }

            public function loadContent(): \DOMDocument
            {
                $domDocument = new \DOMDocument();
                $domDocument->loadXML($this->testInput);

                return $domDocument;
            }

            public function getInputContent(): string
            {
                return $this->inputContent;
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

        $this->tmpFile = sys_get_temp_dir() . '/svg-test-' . uniqid('', true) . '.svg';
        $provider->saveToFile($this->tmpFile);

        self::assertFileExists($this->tmpFile);
        self::assertSame($provider->getOutputContent(), file_get_contents($this->tmpFile));
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

        $baseFile = tempnam(sys_get_temp_dir(), 'svgtest');
        file_put_contents($baseFile, '');
        $invalidPath = $baseFile . '/subdir/output.svg';

        $provider->saveToFile($invalidPath);
        unlink($baseFile);
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

        $tempDir = sys_get_temp_dir() . '/svgtest_' . uniqid();
        $filePath = $tempDir . '/output.svg';

        self::assertDirectoryDoesNotExist($tempDir);

        $provider->saveToFile($filePath);

        self::assertDirectoryExists($tempDir);
        self::assertFileExists($filePath);
        self::assertSame($provider->getOutputContent(), file_get_contents($filePath));

        unlink($filePath);
        rmdir($tempDir);
    }

    #[\Override]
    protected function tearDown(): void
    {
        if (file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
    }
}
