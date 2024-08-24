<?php
/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Services\Providers;

use MathiasReker\PhpSvgOptimizer\Services\MetaData;
use MathiasReker\PhpSvgOptimizer\Services\Providers\FileProvider;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileProvider::class)]
#[CoversClass(MetaData::class)]
final class FileProviderTest extends TestCase
{
    private const TEST_INPUT_FILE = 'input.svg';

    private const TEST_OUTPUT_FILE = 'output.svg';

    public function testGetInputContent(): void
    {
        $fileProvider = new FileProvider(self::TEST_INPUT_FILE, self::TEST_OUTPUT_FILE);
        $content = $fileProvider->getInputContent();

        Assert::assertStringContainsString('<svg', $content);
        Assert::assertStringContainsString('</svg>', $content);
    }

    public function testOptimize(): void
    {
        $fileProvider = new FileProvider(self::TEST_INPUT_FILE, self::TEST_OUTPUT_FILE);
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100"/></svg>');

        $fileProvider->optimize($domDocument);

        Assert::assertFileExists(self::TEST_OUTPUT_FILE);
        $outputContent = $fileProvider->getOutputContent();
        Assert::assertStringContainsString('<svg', $outputContent);
        Assert::assertStringContainsString('</svg>', $outputContent);
    }

    public function testLoad(): void
    {
        $fileProvider = new FileProvider(self::TEST_INPUT_FILE, self::TEST_OUTPUT_FILE);
        $domDocument = $fileProvider->load();

        Assert::assertInstanceOf(\DOMDocument::class, $domDocument);
    }

    public function testGetMetaData(): void
    {
        $fileProvider = new FileProvider(self::TEST_INPUT_FILE, self::TEST_OUTPUT_FILE);
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100"/></svg>');

        $fileProvider->optimize($domDocument);

        $metaData = $fileProvider->getMetaData();

        Assert::assertArrayHasKey('originalSize', $metaData);
        Assert::assertArrayHasKey('optimizedSize', $metaData);
        Assert::assertArrayHasKey('savedBytes', $metaData);
        Assert::assertArrayHasKey('savedPercentage', $metaData);
        Assert::assertEquals(filesize(self::TEST_INPUT_FILE), $metaData['originalSize']);
        Assert::assertEquals(filesize(self::TEST_OUTPUT_FILE), $metaData['optimizedSize']);
    }

    public function testGetInputContentThrowsExceptionIfFileDoesNotExist(): void
    {
        $fileProvider = new FileProvider('nonexistent.svg', self::TEST_OUTPUT_FILE);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Input file does not exist');

        $fileProvider->getInputContent();
    }

    public function testOptimizeThrowsExceptionIfSaveXMLFails(): void
    {
        $fileProvider = new FileProvider(self::TEST_INPUT_FILE, self::TEST_OUTPUT_FILE);
        $domDocument = $this->createMock(\DOMDocument::class);
        $domDocument->method('saveXML')->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to save XML content');

        $fileProvider->optimize($domDocument);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Create a temporary input file with some SVG content
        file_put_contents(self::TEST_INPUT_FILE, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100"/></svg>');
    }

    protected function tearDown(): void
    {
        // Clean up the files created during the test
        if (file_exists(self::TEST_INPUT_FILE)) {
            unlink(self::TEST_INPUT_FILE);
        }

        if (file_exists(self::TEST_OUTPUT_FILE)) {
            unlink(self::TEST_OUTPUT_FILE);
        }

        parent::tearDown();
    }
}
