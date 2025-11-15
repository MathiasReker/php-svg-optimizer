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
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversNothing]
final class SvgFileProcessorTest extends TestCase
{
    private string $tempDir;

    private string $svgFile;

    private OutputManager $outputManager;

    private MetaDataAggregator $metaDataAggregator;

    /**
     * @throws \JsonException
     * @throws \LogicException
     * @throws RiskyRulesNotAllowedException
     * @throws \RuntimeException
     * @throws \ValueError
     */
    public function testProcessSingleSvgFile(): void
    {
        $commandOptionsValueObject = new CommandOptionsValueObject(
            false,
            '',
            true
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
    public function testProcessDirectory(): void
    {
        $file2 = $this->tempDir . '/file2.svg';
        file_put_contents($file2, '<svg><rect width="10" height="10"></rect></svg>');

        $commandOptionsValueObject = new CommandOptionsValueObject(
            true,
            '',
            false
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
    public function testProcessInvalidPathPrintsError(): void
    {
        $memoryStream = new MemoryStream();
        $outputManager = new OutputManager($memoryStream);
        $metaDataAggregator = new MetaDataAggregator();

        $commandOptionsValueObject = new CommandOptionsValueObject(
            true,
            '',
            false
        );

        $svgFileProcessor = new SvgFileProcessor(
            $commandOptionsValueObject,
            $outputManager,
            $metaDataAggregator
        );

        $svgFileProcessor->processPath($this->tempDir . '/nonexistent.svg');

        $output = $memoryStream->getContent();
        self::assertStringContainsString('is not a valid SVG file or directory', $output);
    }

    /**
     * @throws \JsonException
     * @throws \LogicException
     * @throws RiskyRulesNotAllowedException
     * @throws \RuntimeException
     * @throws \ValueError
     */
    public function testProcessWithRiskyRulesAllowed(): void
    {
        $commandOptionsValueObject = new CommandOptionsValueObject(
            true,
            '',
            true
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
     * @throws \RuntimeException
     */
    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/svg_test_' . uniqid();
        if (!mkdir($this->tempDir) && !is_dir($this->tempDir)) {
            throw new \RuntimeException('Failed to create temp directory');
        }

        $this->svgFile = $this->tempDir . '/test.svg';
        file_put_contents(
            $this->svgFile,
            '<svg height="100" width="100"><circle cx="50" cy="50" r="40"></circle></svg>'
        );

        $this->outputManager = new OutputManager(new MemoryStream());
        $this->metaDataAggregator = new MetaDataAggregator();
    }

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
