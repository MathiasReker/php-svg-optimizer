<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Command;

use MathiasReker\PhpSvgOptimizer\Console\Command\SvgOptimizerCommand;
use MathiasReker\PhpSvgOptimizer\Model\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Data\MetaData;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Processor\XmlProcessor;
use MathiasReker\PhpSvgOptimizer\Service\Provider\AbstractProvider;
use MathiasReker\PhpSvgOptimizer\Service\Provider\FileProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\AbstractXmlProcessor;
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
use MathiasReker\PhpSvgOptimizer\Service\SvgOptimizerService;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use MathiasReker\PhpSvgOptimizer\Type\Rule;
use MathiasReker\PhpSvgOptimizer\ValueObject\MetaDataValueObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SvgOptimizerCommand::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(MetaData::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(XmlProcessor::class)]
#[CoversClass(AbstractProvider::class)]
#[CoversClass(FileProvider::class)]
#[CoversClass(AbstractXmlProcessor::class)]
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
#[CoversClass(SvgOptimizerService::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(Rule::class)]
#[CoversClass(MetaDataValueObject::class)]
final class SvgOptimizerCommandTest extends TestCase
{
    private string $tempDir;

    /**
     * @throws \ReflectionException
     * @throws \RuntimeException
     */
    public function testRunWithValidSvgFile(): void
    {
        $svgFile = $this->tempDir . '/test.svg';
        file_put_contents($svgFile, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $reflection = new \ReflectionClass(SvgOptimizerCommand::class);
        $constructor = $reflection->getConstructor();

        if (!$constructor instanceof \ReflectionMethod) {
            self::fail('Constructor not found in SvgOptimizerCommand');
        }

        $constructor->setAccessible(true);

        // Create instance without invoking constructor, then call constructor manually
        $command = $reflection->newInstanceWithoutConstructor();
        $constructor->invoke($command, [$svgFile], '');

        // Set dryRun and quiet to true to avoid actual file writes and output
        foreach (['dryRun', 'quiet'] as $propName) {
            $prop = $reflection->getProperty($propName);
            $prop->setAccessible(true);
            $prop->setValue($command, true);
        }

        $command->run();

        // Check internals
        foreach (['totalOriginalSize', 'totalOptimizedSize', 'optimizedFiles'] as $propName) {
            $prop = $reflection->getProperty($propName);
            $prop->setAccessible(true);
            self::assertIsInt($prop->getValue($command));
        }
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
            if ('.' === $item || '..' === $item) {
                continue;
            }
            $path = $dir . \DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->deleteDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
