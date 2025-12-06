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
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveComments;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEnableBackgroundAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveTitleAndDesc;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveWidthHeightAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
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
final class SvgOptimizerFacadeTest extends TestCase
{
    private string $sampleSvg;

    /**
     * @throws SvgValidationException
     * @throws RiskyRulesNotAllowedException
     */
    public function testOptimizeReturnsService(): void
    {
        $svgOptimizerFacade = SvgOptimizerFacade::fromString($this->sampleSvg);
        $result = $svgOptimizerFacade->optimize();

        self::assertSame($svgOptimizerFacade, $result);
        self::assertNotEmpty($result->getContent());
    }

    /**
     * @throws SvgValidationException
     * @throws RiskyRulesNotAllowedException
     */
    public function testSaveToFileWritesOptimizedSvg(): void
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
    public function testFromFileThrowsExceptionOnInvalidPath(): void
    {
        $this->expectException(FileNotFoundException::class);
        SvgOptimizerFacade::fromFile('/nonexistent/path.svg');
    }

    /**
     * @throws SvgValidationException
     * @throws RiskyRulesNotAllowedException
     */
    public function testWithRulesConfiguresRules(): void
    {
        $svgOptimizerFacade = SvgOptimizerFacade::fromString($this->sampleSvg)
            ->withRules(true, true, true);

        $svgOptimizerFacade->optimize();

        $content = $svgOptimizerFacade->getContent();
        self::assertNotEmpty($content);
        self::assertStringContainsString('<svg', $content);
        self::assertStringNotContainsString('<!--', $content);
    }

    /**
     * @throws SvgValidationException
     * @throws RiskyRulesNotAllowedException
     */
    public function testAllowRiskyEnablesRiskyRules(): void
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
     */
    public function testGetContentReturnsSvg(): void
    {
        $svgOptimizerFacade = SvgOptimizerFacade::fromString($this->sampleSvg)->optimize();

        $content = $svgOptimizerFacade->getContent();
        self::assertStringContainsString('<svg', $content);
    }

    /**
     * @throws SvgValidationException
     * @throws RiskyRulesNotAllowedException
     */
    public function testOptimizeThrowsExceptionForRiskyRules(): void
    {
        $svgOptimizerFacade = SvgOptimizerFacade::fromString($this->sampleSvg)
            ->withRules(false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, true);

        $this->expectException(RiskyRulesNotAllowedException::class);
        $svgOptimizerFacade->optimize();
    }

    /**
     * @throws SvgValidationException
     * @throws RiskyRulesNotAllowedException
     */
    public function testAllowRiskyFalseDoesNotEnableRiskyRules(): void
    {
        $svgOptimizerFacade = SvgOptimizerFacade::fromString($this->sampleSvg)
            ->withRules(false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, false, true)
            ->allowRisky(false);

        $this->expectException(RiskyRulesNotAllowedException::class);
        $svgOptimizerFacade->optimize();
    }

    /**
     * @throws SvgValidationException
     * @throws RiskyRulesNotAllowedException
     */
    public function testWithRulesDefaultDoesNotApplyAnyRules(): void
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
     */
    public function testMultipleRulesApplied(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><title>T</title><!-- c --></svg>';

        $svgOptimizerFacade = SvgOptimizerFacade::fromString($svg)
            ->withRules(false, false, false, false, false, false, false, false, true, false, false, false, false, false, false, false, false, false, false, false, true)
            ->optimize();

        $content = $svgOptimizerFacade->getContent();

        self::assertStringNotContainsString('<!--', $content);
        self::assertStringNotContainsString('<title>', $content);
    }

    /**
     * @throws SvgValidationException
     * @throws RiskyRulesNotAllowedException
     */
    public function testMethodChaining(): void
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
    public function testFromStringAndFromFileReturnInstances(): void
    {
        $file = sys_get_temp_dir() . '/instance-test.svg';
        file_put_contents($file, $this->sampleSvg);

        $svgOptimizerFacade = SvgOptimizerFacade::fromString($this->sampleSvg);
        $b = SvgOptimizerFacade::fromFile($file);

        self::assertNotSame($svgOptimizerFacade, $b);

        unlink($file);
    }

    protected function setUp(): void
    {
        $this->sampleSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><title>Test</title></svg>';
    }
}
