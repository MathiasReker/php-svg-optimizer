<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Processor;

use MathiasReker\PhpSvgOptimizer\Console\Output\Manager\OutputManager;
use MathiasReker\PhpSvgOptimizer\Console\Output\Stream\MemoryStream;
use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Model\MetaDataAggregator;
use MathiasReker\PhpSvgOptimizer\Service\Processor\SvgFileProcessor;
use MathiasReker\PhpSvgOptimizer\ValueObject\CommandOptionsValueObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SvgFileProcessor::class)]
final class SvgFileProcessorTest extends TestCase
{
    private string $tempDir;

    private string $svgFile;

    private OutputManager $outputManager;

    private MetaDataAggregator $metaDataAggregator;

    private MemoryStream $memoryStream;

    /**
     * @throws \JsonException
     * @throws \LogicException
     * @throws RiskyRulesNotAllowedException
     * @throws \RuntimeException
     * @throws \ValueError
     */
    #[Test]
    public function processSingleSvgFile(): void
    {
        $commandOptionsValueObject = new CommandOptionsValueObject(
            false,
            '',
            true,
            false,
        );

        $svgFileProcessor = new SvgFileProcessor(
            $commandOptionsValueObject,
            $this->outputManager,
            $this->metaDataAggregator
        );

        $svgFileProcessor->processPath($this->svgFile);

        $contents = file_get_contents($this->svgFile);
        self::assertIsString($contents);
        self::assertStringContainsString('<svg', $contents);

        self::assertGreaterThanOrEqual(0, $this->metaDataAggregator->getTotalOriginalSize());
        self::assertGreaterThanOrEqual(0, $this->metaDataAggregator->getTotalOptimizedSize());
    }

    /**
     * @throws \JsonException
     * @throws \LogicException
     * @throws RiskyRulesNotAllowedException
     * @throws \RuntimeException
     * @throws \ValueError
     */
    #[Test]
    public function processDirectory(): void
    {
        $file2 = $this->tempDir . '/file2.svg';
        file_put_contents($file2, '<svg><rect width="10" height="10"></rect></svg>');

        $commandOptionsValueObject = new CommandOptionsValueObject(
            true,
            '',
            false,
            false,
        );

        $svgFileProcessor = new SvgFileProcessor(
            $commandOptionsValueObject,
            $this->outputManager,
            $this->metaDataAggregator
        );

        $svgFileProcessor->processPath($this->tempDir);

        self::assertGreaterThanOrEqual(0, $this->metaDataAggregator->getTotalOriginalSize());
    }

    /**
     * @throws \JsonException
     * @throws \LogicException
     * @throws RiskyRulesNotAllowedException
     * @throws \RuntimeException
     * @throws \ValueError
     */
    #[Test]
    public function processInvalidPathPrintsError(): void
    {
        $commandOptionsValueObject = new CommandOptionsValueObject(
            true,
            '',
            false,
            false,
        );

        $svgFileProcessor = new SvgFileProcessor(
            $commandOptionsValueObject,
            $this->outputManager,
            $this->metaDataAggregator
        );

        $svgFileProcessor->processPath($this->tempDir . '/nonexistent.svg');

        $output = $this->memoryStream->getContent();
        self::assertStringContainsString('is not a valid SVG file or directory', $output);
    }

    /**
     * @throws \JsonException
     * @throws \LogicException
     * @throws RiskyRulesNotAllowedException
     * @throws \RuntimeException
     * @throws \ValueError
     */
    #[Test]
    public function processWithRiskyRulesAllowed(): void
    {
        $commandOptionsValueObject = new CommandOptionsValueObject(
            true,
            '',
            true,
            false,
        );

        $svgFileProcessor = new SvgFileProcessor(
            $commandOptionsValueObject,
            $this->outputManager,
            $this->metaDataAggregator
        );

        $svgFileProcessor->processPath($this->svgFile);

        self::assertGreaterThanOrEqual(0, $this->metaDataAggregator->getTotalOriginalSize());
    }

    /**
     * @throws \JsonException
     * @throws \LogicException
     * @throws RiskyRulesNotAllowedException
     * @throws \RuntimeException
     * @throws \ValueError
     */
    #[Test]
    public function it_does_not_modify_file_on_dry_run(): void
    {
        $originalContent = file_get_contents($this->svgFile);

        $commandOptionsValueObject = new CommandOptionsValueObject(
            true,
            '',
            true,
            true,
        );

        $svgFileProcessor = new SvgFileProcessor(
            $commandOptionsValueObject,
            $this->outputManager,
            $this->metaDataAggregator
        );

        $svgFileProcessor->processPath($this->svgFile);

        $this->assertSame($originalContent, file_get_contents($this->svgFile));
    }

    /**
     * @throws \JsonException
     * @throws \LogicException
     * @throws RiskyRulesNotAllowedException
     * @throws \RuntimeException
     * @throws \ValueError
     */
    #[Test]
    public function it_uses_config_file(): void
    {
        $configFile = $this->tempDir . '/config.json';
        file_put_contents($configFile, '{"removeComments": true}');

        $commandOptionsValueObject = new CommandOptionsValueObject(
            false,
            $configFile,
            false,
            false,
        );

        $svgFileProcessor = new SvgFileProcessor(
            $commandOptionsValueObject,
            $this->outputManager,
            $this->metaDataAggregator
        );

        $svgFileProcessor->processPath($this->svgFile);

        $this->assertStringNotContainsString('<!--', (string) file_get_contents($this->svgFile));
    }

    /**
     * @throws \RuntimeException
     */
    #[\Override]
    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/svg_test_' . uniqid();
        if (!mkdir($this->tempDir) && !is_dir($this->tempDir)) {
            throw new \RuntimeException('Failed to create temp directory');
        }

        $this->svgFile = $this->tempDir . '/test.svg';
        file_put_contents(
            $this->svgFile,
            '<svg height="100" width="100"><!-- comment --><circle cx="50" cy="50" r="40"></circle></svg>'
        );

        $this->memoryStream = new MemoryStream();
        $this->outputManager = new OutputManager($this->memoryStream);
        $this->metaDataAggregator = new MetaDataAggregator();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $files = glob($this->tempDir . '/*');
        if (false !== $files) {
            foreach ($files as $file) {
                unlink($file);
            }
        }

        rmdir($this->tempDir);
    }
}
