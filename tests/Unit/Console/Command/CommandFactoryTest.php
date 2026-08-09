<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Command;

use MathiasReker\PhpSvgOptimizer\Console\Command\Command;
use MathiasReker\PhpSvgOptimizer\Console\Command\CommandFactory;
use MathiasReker\PhpSvgOptimizer\Console\Input\ArgumentParser;
use MathiasReker\PhpSvgOptimizer\Console\Output\Manager\OutputManager;
use MathiasReker\PhpSvgOptimizer\Console\Output\Stream\MemoryStream;
use MathiasReker\PhpSvgOptimizer\Model\MetaDataAggregator;
use MathiasReker\PhpSvgOptimizer\Service\Processor\SvgFileProcessor;
use MathiasReker\PhpSvgOptimizer\ValueObject\CommandOption;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CommandFactory::class)]
#[CoversClass(Command::class)]
final class CommandFactoryTest extends TestCase
{
    private string $tempDir;

    /**
     * @throws \ReflectionException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function createBuildsCommandFromProvidedDependencies(): void
    {
        $svgFile = $this->tempDir . '/test.svg';
        file_put_contents($svgFile, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $memoryStream = new MemoryStream();
        $argumentParser = new ArgumentParser(
            [
                'vendor/bin/svg-optimizer',
                'process',
                $svgFile,
            ]
        );

        $commandFactory = new CommandFactory($memoryStream, $argumentParser, false);

        self::assertSame($memoryStream, $commandFactory->stream);
        self::assertSame($argumentParser, $commandFactory->argumentParser);

        $commandOption = new CommandOption(false, '', false, false);

        $command = $commandFactory->create($commandOption);

        $reflectionClass = new \ReflectionClass(Command::class);

        $pathsProperty = $reflectionClass->getProperty('paths');
        self::assertSame($argumentParser->getPaths(), $pathsProperty->getValue($command));

        $commandOptionProperty = $reflectionClass->getProperty('commandOption');
        self::assertSame($commandOption, $commandOptionProperty->getValue($command));

        $outputManagerProperty = $reflectionClass->getProperty('outputManager');
        $outputManager = $outputManagerProperty->getValue($command);
        self::assertInstanceOf(OutputManager::class, $outputManager);

        $reflectionProperty = new \ReflectionProperty(OutputManager::class, 'stream');
        self::assertSame($memoryStream, $reflectionProperty->getValue($outputManager));

        $metaDataAggregatorProperty = $reflectionClass->getProperty('metaDataAggregator');
        self::assertInstanceOf(MetaDataAggregator::class, $metaDataAggregatorProperty->getValue($command));

        $svgFileProcessorProperty = $reflectionClass->getProperty('svgFileProcessor');
        self::assertInstanceOf(SvgFileProcessor::class, $svgFileProcessorProperty->getValue($command));
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/svgopt_factory_test_' . uniqid();
        mkdir($this->tempDir);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->deleteDir($this->tempDir);
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $item) {
            if ('.' === $item) {
                continue;
            }

            if ('..' === $item) {
                continue;
            }

            $path = $dir . \DIRECTORY_SEPARATOR . $item;
            is_dir($path)
                ? $this->deleteDir($path)
                : unlink($path);
        }

        rmdir($dir);
    }
}
