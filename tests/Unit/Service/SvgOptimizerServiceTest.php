<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service;

use MathiasReker\PhpSvgOptimizer\Exception\FileNotFoundException;
use MathiasReker\PhpSvgOptimizer\Exception\IOException;
use MathiasReker\PhpSvgOptimizer\Exception\SvgValidationException;
use MathiasReker\PhpSvgOptimizer\Model\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Processor\XmlProcessor;
use MathiasReker\PhpSvgOptimizer\Service\Provider\AbstractProvider;
use MathiasReker\PhpSvgOptimizer\Service\Provider\FileProvider;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
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
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedNamespaces;
use MathiasReker\PhpSvgOptimizer\Service\Rule\SortAttributes;
use MathiasReker\PhpSvgOptimizer\Service\SvgOptimizerService;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SvgOptimizerService::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(XmlProcessor::class)]
#[CoversClass(AbstractProvider::class)]
#[CoversClass(StringProvider::class)]
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
#[CoversClass(RemoveUnusedNamespaces::class)]
#[CoversClass(SortAttributes::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(FileProvider::class)]
final class SvgOptimizerServiceTest extends TestCase
{
    private string $sampleSvg;

    /**
     * @throws SvgValidationException
     */
    public function testOptimizeReturnsService(): void
    {
        $service = SvgOptimizerService::fromString($this->sampleSvg);
        $result = $service->optimize();

        self::assertSame($service, $result);
        self::assertNotEmpty($result->getContent());
    }

    /**
     * @throws SvgValidationException
     */
    public function testSaveToFileWritesOptimizedSvg(): void
    {
        $file = sys_get_temp_dir() . '/optimized-test.svg';

        if (file_exists($file)) {
            unlink($file);
        }

        SvgOptimizerService::fromString($this->sampleSvg)
            ->optimize()
            ->saveToFile($file);

        self::assertFileExists($file);

        $contents = file_get_contents($file);
        self::assertIsString($contents);
        self::assertStringContainsString('<svg', $contents);

        unlink($file);
    }

    /**
     * @throws FileNotFoundException
     * @throws IOException
     */
    public function testFromFileThrowsExceptionOnInvalidPath(): void
    {
        $this->expectException(FileNotFoundException::class);
        SvgOptimizerService::fromFile('/nonexistent/path.svg');
    }

    protected function setUp(): void
    {
        $this->sampleSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><title>Test</title></svg>';
    }
}
