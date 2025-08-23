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
use MathiasReker\PhpSvgOptimizer\Console\Input\ConfigLoader;
use MathiasReker\PhpSvgOptimizer\Console\Output\Manager\OutputManager;
use MathiasReker\PhpSvgOptimizer\Console\Output\Stream\MemoryStream;
use MathiasReker\PhpSvgOptimizer\Model\MetaDataAggregator;
use MathiasReker\PhpSvgOptimizer\Model\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Data\ArgumentData;
use MathiasReker\PhpSvgOptimizer\Service\Data\MetaData;
use MathiasReker\PhpSvgOptimizer\Service\Facade\SvgOptimizerFacade;
use MathiasReker\PhpSvgOptimizer\Service\Filesystem\Finder;
use MathiasReker\PhpSvgOptimizer\Service\Formatter\ByteFormatter;
use MathiasReker\PhpSvgOptimizer\Service\Formatter\XmlFormatter;
use MathiasReker\PhpSvgOptimizer\Service\Processor\AbstractXmlProcessor;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Processor\SvgFileProcessor;
use MathiasReker\PhpSvgOptimizer\Service\Provider\AbstractProvider;
use MathiasReker\PhpSvgOptimizer\Service\Provider\FileProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertColorsToHex;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertEmptyTagsToSelfClosing;
use MathiasReker\PhpSvgOptimizer\Service\Rule\FlattenGroups;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifySvgCoordinates;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyTransformations;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveComments;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDefaultAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDeprecatedAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDoctype;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEnableBackgroundAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveInkscapeFootprints;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveInvisibleCharacters;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveMetadata;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveTitleAndDesc;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnnecessaryWhitespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnsafeElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedNamespaces;
use MathiasReker\PhpSvgOptimizer\Service\Rule\SortAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use MathiasReker\PhpSvgOptimizer\Type\Option;
use MathiasReker\PhpSvgOptimizer\Type\Rule;
use MathiasReker\PhpSvgOptimizer\ValueObject\ArgumentOptionValueObject;
use MathiasReker\PhpSvgOptimizer\ValueObject\CommandOptionsValueObject;
use MathiasReker\PhpSvgOptimizer\ValueObject\ExampleCommandValueObject;
use MathiasReker\PhpSvgOptimizer\ValueObject\MetaDataValueObject;
use MathiasReker\PhpSvgOptimizer\ValueObject\OptionValueObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Command::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(MetaData::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(XmlFormatter::class)]
#[CoversClass(AbstractXmlProcessor::class)]
#[CoversClass(AbstractProvider::class)]
#[CoversClass(FileProvider::class)]
#[CoversClass(ConvertColorsToHex::class)]
#[CoversClass(ConvertEmptyTagsToSelfClosing::class)]
#[CoversClass(FlattenGroups::class)]
#[CoversClass(MinifySvgCoordinates::class)]
#[CoversClass(MinifyTransformations::class)]
#[CoversClass(RemoveComments::class)]
#[CoversClass(RemoveDefaultAttributes::class)]
#[CoversClass(RemoveDeprecatedAttributes::class)]
#[CoversClass(RemoveDoctype::class)]
#[CoversClass(RemoveEmptyAttributes::class)]
#[CoversClass(RemoveEnableBackgroundAttribute::class)]
#[CoversClass(RemoveInkscapeFootprints::class)]
#[CoversClass(RemoveInvisibleCharacters::class)]
#[CoversClass(RemoveMetadata::class)]
#[CoversClass(RemoveTitleAndDesc::class)]
#[CoversClass(RemoveUnnecessaryWhitespace::class)]
#[CoversClass(RemoveUnsafeElements::class)]
#[CoversClass(RemoveUnusedNamespaces::class)]
#[CoversClass(SvgOptimizerFacade::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(Rule::class)]
#[CoversClass(MetaDataValueObject::class)]
#[CoversClass(MemoryStream::class)]
#[CoversClass(ArgumentData::class)]
#[CoversClass(Option::class)]
#[CoversClass(ArgumentOptionValueObject::class)]
#[CoversClass(ExampleCommandValueObject::class)]
#[CoversClass(OptionValueObject::class)]
#[CoversClass(OutputManager::class)]
#[CoversClass(MetaDataAggregator::class)]
#[CoversClass(CommandOptionsValueObject::class)]
#[CoversClass(SvgFileProcessor::class)]
#[CoversClass(ConfigLoader::class)]
#[CoversClass(ByteFormatter::class)]
#[CoversClass(SortAttributes::class)]
#[CoversClass(Finder::class)]
final class SvgOptimizerCommandTest extends TestCase
{
    private string $tempDir;

    /**
     * @throws \ReflectionException
     * @throws \RuntimeException
     * @throws \LogicException
     */
    public function testRunWithValidSvgFile(): void
    {
        $svgFile = $this->tempDir . '/test.svg';
        file_put_contents($svgFile, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $reflectionClass = new \ReflectionClass(Command::class);
        $constructor = $reflectionClass->getConstructor();

        if (!$constructor instanceof \ReflectionMethod) {
            self::fail('Constructor not found in SvgOptimizerCommand');
        }

        $command = $reflectionClass->newInstanceWithoutConstructor();

        $outputManager = new OutputManager(new MemoryStream());

        $commandOptionsValueObject = new CommandOptionsValueObject(
            false,
            ''
        );

        $constructor->invoke($command, [$svgFile], $commandOptionsValueObject, $outputManager);

        $command->run();

        $propName = 'metaDataAggregator';
        $reflectionProperty = $reflectionClass->getProperty($propName);
        $metaDataAggregator = $reflectionProperty->getValue($command);

        self::assertInstanceOf(MetaDataAggregator::class, $metaDataAggregator);
    }

    /**
     * @throws \ReflectionException
     * @throws \RuntimeException
     * @throws \LogicException
     */
    public function testRunWithNoInputFiles(): void
    {
        $reflectionClass = new \ReflectionClass(Command::class);
        $constructor = $reflectionClass->getConstructor();

        if (!$constructor instanceof \ReflectionMethod) {
            self::fail('Constructor not found in SvgOptimizerCommand');
        }

        $outputManager = new OutputManager(new MemoryStream());
        $command = $reflectionClass->newInstanceWithoutConstructor();

        $commandOptionsValueObject = new CommandOptionsValueObject(
            false,
            ''
        );

        $constructor->invoke($command, [], $commandOptionsValueObject, $outputManager);

        $command->run();
        $prop = $reflectionClass->getProperty('metaDataAggregator');
        $metaDataAggregator = $prop->getValue($command);
        \assert($metaDataAggregator instanceof MetaDataAggregator);

        $originalSizeProp = new \ReflectionProperty($metaDataAggregator::class, 'totalOriginalSize');
        $totalOriginalSize = $originalSizeProp->getValue($metaDataAggregator);

        $optimizedSizeProp = new \ReflectionProperty($metaDataAggregator::class, 'totalOptimizedSize');
        $totalOptimizedSize = $optimizedSizeProp->getValue($metaDataAggregator);

        self::assertSame(0, $totalOriginalSize);
        self::assertSame(0, $totalOptimizedSize);
    }

    /**
     * @throws \LogicException
     * @throws \RuntimeException
     */
    public function testRunWithDryRunOption(): void
    {
        $svgFile = $this->tempDir . '/test.svg';
        $originalContent = '<svg xmlns="http://www.w3.org/2000/svg"></svg>';
        file_put_contents($svgFile, $originalContent);

        $command = new Command([$svgFile], new CommandOptionsValueObject(true, ''), new OutputManager(new MemoryStream()));
        $command->run();

        $newContent = file_get_contents($svgFile);
        self::assertSame($originalContent, $newContent); // no overwrite
    }

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/svgopt_test_' . uniqid();
        mkdir($this->tempDir);
    }

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
