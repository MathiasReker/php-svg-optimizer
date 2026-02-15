<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Facade;

use MathiasReker\PhpSvgOptimizer\Exception\FileNotFoundException;
use MathiasReker\PhpSvgOptimizer\Exception\IOException;
use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Exception\SvgValidationException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Model\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Facade\SvgOptimizerFacade;
use MathiasReker\PhpSvgOptimizer\Service\Formatter\XmlFormatter;
use MathiasReker\PhpSvgOptimizer\Service\Processor\AbstractXmlProcessor;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\AbstractProvider;
use MathiasReker\PhpSvgOptimizer\Service\Provider\FileProvider;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertColorsToHex;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertCssClassesToAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertEmptyTagsToSelfClosing;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgInlineStyleProperty;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveComments;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEnableBackgroundAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveTitleAndDesc;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveWidthHeightAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SvgOptimizerFacade::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(AbstractProvider::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(XmlFormatter::class)]
#[CoversClass(ConvertColorsToHex::class)]
#[CoversClass(RemoveWidthHeightAttributes::class)]
#[CoversClass(FileProvider::class)]
#[CoversClass(RemoveEnableBackgroundAttribute::class)]
#[CoversClass(RemoveComments::class)]
#[CoversClass(RemoveTitleAndDesc::class)]
#[CoversClass(AbstractXmlProcessor::class)]
#[CoversClass(ConvertCssClassesToAttributes::class)]
#[CoversClass(ConvertEmptyTagsToSelfClosing::class)]
#[CoversClass(SvgInlineStyleProperty::class)]
final class SvgOptimizerFacadeTest extends TestCase
{
    private string $sampleSvg;

    private string $tempFilePath;

    /**
     * @throws SvgValidationException
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     */
    #[Test]
    public function optimizeReturnsService(): void
    {
        $svgOptimizerFacade = SvgOptimizerFacade::fromString($this->sampleSvg);
        $result = $svgOptimizerFacade->optimize();

        self::assertSame($svgOptimizerFacade, $result);
        self::assertNotEmpty($result->getContent());
    }

    /**
     * @throws SvgValidationException
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     */
    #[Test]
    public function saveToFileWritesOptimizedSvg(): void
    {
        $file = sys_get_temp_dir() . '/optimized-test.svg';

        if (file_exists($file)) {
            unlink($file);
        }

        SvgOptimizerFacade::fromString($this->sampleSvg)
            ->optimize()
            ->saveToFile($file);

        self::assertFileExists($file);
        self::assertStringContainsString('<svg', (string) file_get_contents($file));

        unlink($file);
    }

    /**
     * @throws FileNotFoundException
     * @throws IOException
     */
    #[Test]
    public function fromFileThrowsExceptionOnInvalidPath(): void
    {
        $this->expectException(FileNotFoundException::class);
        SvgOptimizerFacade::fromFile('/nonexistent/path.svg');
    }

    /**
     * @throws SvgValidationException
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     */
    #[Test]
    public function withRulesConfiguresRules(): void
    {
        $svgOptimizerFacade = SvgOptimizerFacade::fromString($this->sampleSvg)
            ->withRules(
                true,
                true,
                true,
                false,
                false,
                false,
                false,
                false,
                false,
                true,
            );

        $svgOptimizerFacade->optimize();

        $content = $svgOptimizerFacade->getContent();
        self::assertNotEmpty($content);
        self::assertStringContainsString('<svg', $content);
        self::assertStringNotContainsString('<!--', $content);
    }

    /**
     * @throws SvgValidationException
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     */
    #[Test]
    public function allowRiskyEnablesRiskyRules(): void
    {
        $svgOptimizerFacade = SvgOptimizerFacade::fromString($this->sampleSvg)
            ->allowRisky();

        self::assertSame($svgOptimizerFacade, $svgOptimizerFacade->allowRisky());

        $svgOptimizerFacade->withRules(true, false, false);

        $svgOptimizerFacade->optimize();

        self::assertNotEmpty($svgOptimizerFacade->getContent());
    }

    /**
     * @throws \LogicException
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     */
    #[Test]
    public function getContentReturnsSvg(): void
    {
        $svgOptimizerFacade = SvgOptimizerFacade::fromString($this->sampleSvg)->optimize();

        $content = $svgOptimizerFacade->getContent();
        self::assertStringContainsString('<svg', $content);
    }

    /**
     * @throws SvgValidationException
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     */
    #[Test]
    public function optimizeThrowsExceptionForRiskyRules(): void
    {
        $svgOptimizerFacade = SvgOptimizerFacade::fromString($this->sampleSvg)
            ->withRules(false, false, false, false, true, false, false, false, false, false, true, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, true);

        $this->expectException(RiskyRulesNotAllowedException::class);
        $svgOptimizerFacade->optimize();
    }

    /**
     * @throws SvgValidationException
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     */
    #[Test]
    public function allowRiskyFalseDoesNotEnableRiskyRules(): void
    {
        $svgOptimizerFacade = SvgOptimizerFacade::fromString($this->sampleSvg)
            ->withRules(false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, true)
            ->allowRisky(false);

        $this->expectException(RiskyRulesNotAllowedException::class);
        $svgOptimizerFacade->optimize();
    }

    /**
     * @throws SvgValidationException
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     */
    #[Test]
    public function withRulesDefaultDoesNotApplyAnyRules(): void
    {
        $svgOptimizerFacade = SvgOptimizerFacade::fromString($this->sampleSvg)->withRules();
        $svgOptimizerFacade->optimize();

        $content = $svgOptimizerFacade->getContent();
        self::assertStringContainsString('<svg', $content);
        self::assertStringContainsString('<title>', $content);
    }

    /**
     * @throws SvgValidationException
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     */
    #[Test]
    public function multipleRulesApplied(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><title>T</title><!-- c --></svg>';

        $svgOptimizerFacade = SvgOptimizerFacade::fromString($svg)
            ->withRules(
                false,
                false,
                false,
                false,
                false,
                false,
                false,
                false,
                false,
                true,
                false,
                false,
                false,
                false,
                false,
                false,
                false,
                false,
                false,
                false,
                false,
                false,
                false,
                false,
                true,
            )
            ->optimize();

        $content = $svgOptimizerFacade->getContent();

        self::assertStringNotContainsString('<!--', $content);
        self::assertStringNotContainsString('<title>', $content);
    }

    /**
     * @throws SvgValidationException
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     */
    #[Test]
    public function methodChaining(): void
    {
        $file = sys_get_temp_dir() . '/chained-test.svg';

        SvgOptimizerFacade::fromString($this->sampleSvg)
            ->allowRisky()
            ->withRules(true, false, false)
            ->optimize()
            ->saveToFile($file);

        self::assertFileExists($file);
        unlink($file);
    }

    /**
     * @throws IOException
     * @throws FileNotFoundException
     */
    #[Test]
    public function fromStringAndFromFileReturnInstances(): void
    {
        $file = sys_get_temp_dir() . '/instance-test.svg';
        file_put_contents($file, $this->sampleSvg);

        $svgOptimizerFacade = SvgOptimizerFacade::fromString($this->sampleSvg);
        $b = SvgOptimizerFacade::fromFile($file);

        self::assertNotSame($svgOptimizerFacade, $b);

        unlink($file);
    }

    /**
     * @throws SvgValidationException
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     */
    #[Test]
    public function withAllRulesEnablesAllNonRiskyRules(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><title>T</title><!-- c --></svg>';
        $svgOptimizerFacade = SvgOptimizerFacade::fromString($svg)->withAllRules()->optimize();
        $content = $svgOptimizerFacade->getContent();

        self::assertStringNotContainsString('<!--', $content);
        self::assertStringNotContainsString('<title>', $content);
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function getMetaDataReturnsCorrectData(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"></svg>';
        $svgOptimizerFacade = SvgOptimizerFacade::fromString($svg)->optimize();
        $metaDataValueObject = $svgOptimizerFacade->getMetaData();

        self::assertGreaterThan(0, $metaDataValueObject->getOriginalSize());
        self::assertGreaterThan(0, $metaDataValueObject->getOptimizedSize());
        self::assertGreaterThanOrEqual(0, $metaDataValueObject->getSavedBytes());
        self::assertGreaterThanOrEqual(0, $metaDataValueObject->getSavedPercentage());
        self::assertGreaterThanOrEqual(0, $metaDataValueObject->getOptimizationTime());
    }

    /**
     * @throws SvgValidationException
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     */
    #[Test]
    public function withRulesCanEnableSpecificRiskyRules(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"></svg>';
        $svgOptimizerFacade = SvgOptimizerFacade::fromString($svg)
            ->withRules(false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, true)
            ->allowRisky()
            ->optimize();

        $content = $svgOptimizerFacade->getContent();
        self::assertStringNotContainsString('width="100"', $content);
        self::assertStringNotContainsString('height="100"', $content);
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->sampleSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><title>Test</title><!-- comment --></svg>';
        $this->tempFilePath = sys_get_temp_dir() . '/test_svg_file_' . uniqid() . '.svg';
    }

    #[\Override]
    protected function tearDown(): void
    {
        if (file_exists($this->tempFilePath)) {
            unlink($this->tempFilePath);
        }
    }
}
