<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Model;

use MathiasReker\PhpSvgOptimizer\Contract\Service\Provider\SvgProviderInterface;
use MathiasReker\PhpSvgOptimizer\Contract\Service\Rule\SvgOptimizerRuleInterface;
use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Exception\SvgValidationException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Model\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Data\MetaData;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use MathiasReker\PhpSvgOptimizer\ValueObject\MetaDataValueObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @no-named-arguments
 *
 * @internal
 */
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(MetaDataValueObject::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(MetaData::class)]
final class SvgOptimizerTest extends TestCase
{
    private SvgProviderInterface $svgProvider;

    #[Test]
    public function addRuleAndGetRulesCount(): void
    {
        $svgOptimizer = new SvgOptimizer($this->svgProvider);

        self::assertSame(0, $svgOptimizer->getRulesCount());

        $rule = new class implements SvgOptimizerRuleInterface {
            public function optimize(\DOMDocument $domDocument): void
            {
                // no-op for test
            }

            public static function shouldCheckSize(): bool
            {
                return false;
            }

            public static function isRisky(): bool
            {
                return false;
            }
        };

        $svgOptimizer->addRule($rule);
        self::assertSame(1, $svgOptimizer->getRulesCount());
    }

    #[Test]
    public function hasRules(): void
    {
        $svgOptimizer = new SvgOptimizer($this->svgProvider);

        self::assertFalse($svgOptimizer->hasRules(), 'Expected hasRules() to return false when no rules are added.');

        $rule = new class implements SvgOptimizerRuleInterface {
            public function optimize(\DOMDocument $domDocument): void
            {
                // no-op for test
            }

            public static function shouldCheckSize(): bool
            {
                return false;
            }

            public static function isRisky(): bool
            {
                return false;
            }
        };

        $svgOptimizer->addRule($rule);

        self::assertTrue($svgOptimizer->hasRules(), 'Expected hasRules() to return true after a rule is added.');
    }

    /**
     * @throws SvgValidationException
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function optimizeReturnsSelfAndContentIsSet(): void
    {
        $svgOptimizer = new SvgOptimizer($this->svgProvider);

        $result = $svgOptimizer->optimize();

        self::assertSame($svgOptimizer, $result);
        self::assertSame('<svg>optimized</svg>', $svgOptimizer->getContent());
    }

    /**
     * @throws SvgValidationException
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function optimizeThrowsExceptionOnInvalidSvg(): void
    {
        $invalidProvider = new class implements SvgProviderInterface {
            public function getInputContent(): string
            {
                return 'invalid svg';
            }

            public function loadContent(): \DOMDocument
            {
                $domDocument = new \DOMDocument();
                $domDocument->loadXML('<svg></svg>');

                return $domDocument;
            }

            public function optimize(\DOMDocument $domDocument): SvgProviderInterface
            {
                return $this;
            }

            public function getOutputContent(): string
            {
                return '';
            }

            public function saveToFile(string $path): SvgProviderInterface
            {
                return $this;
            }

            public function serialize(\DOMDocument $domDocument): string
            {
                return '';
            }
        };

        $svgOptimizer = new SvgOptimizer($invalidProvider);

        $this->expectException(SvgValidationException::class);
        $this->expectExceptionMessage('The file does not appear to be a valid SVG file.');

        $svgOptimizer->optimize();
    }

    /**
     * @throws \LogicException
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function getMetaDataReturnsCorrectMetaData(): void
    {
        $svgOptimizer = new SvgOptimizer($this->svgProvider);

        $svgOptimizer->optimize();

        $metaDataValueObject = $svgOptimizer->getMetaData();

        self::assertSame(11, $metaDataValueObject->getOriginalSize());
        self::assertSame(20, $metaDataValueObject->getOptimizedSize());
        self::assertGreaterThan(0, $metaDataValueObject->getOptimizationTime());
    }

    #[Test]
    public function saveToFileReturnsSelf(): void
    {
        $svgOptimizer = new SvgOptimizer($this->svgProvider);

        $result = $svgOptimizer->saveToFile('/tmp/output.svg');

        self::assertSame($svgOptimizer, $result);
    }

    #[Test]
    public function getContentReturnsEmptyStringBeforeOptimize(): void
    {
        $svgOptimizer = new SvgOptimizer($this->svgProvider);
        self::assertSame('', $svgOptimizer->getContent());
    }

    /**
     * @throws SvgValidationException
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function optimizeWithNoRules(): void
    {
        $svgOptimizer = new SvgOptimizer($this->svgProvider);
        $result = $svgOptimizer->optimize();

        self::assertSame($svgOptimizer, $result);
        self::assertSame('<svg>optimized</svg>', $svgOptimizer->getContent());
    }

    #[Test]
    public function saveToFileChainingWithDifferentPaths(): void
    {
        $svgOptimizer = new SvgOptimizer($this->svgProvider);

        $result1 = $svgOptimizer->saveToFile('/tmp/file1.svg');
        $result2 = $svgOptimizer->saveToFile('/tmp/file2.svg');

        self::assertSame($svgOptimizer, $result1);
        self::assertSame($svgOptimizer, $result2);
    }

    #[Test]
    public function configureRulesAddsOnlyEnabledRules(): void
    {
        $svgOptimizer = new SvgOptimizer($this->svgProvider);

        self::assertSame(0, $svgOptimizer->getRulesCount());

        $ruleClassEnabled = new class implements SvgOptimizerRuleInterface {
            public function optimize(\DOMDocument $domDocument): void
            {
            }

            public static function shouldCheckSize(): bool
            {
                return false;
            }

            public static function isRisky(): bool
            {
                return false;
            }
        };

        $ruleClassDisabled = new class implements SvgOptimizerRuleInterface {
            public function optimize(\DOMDocument $domDocument): void
            {
            }

            public static function shouldCheckSize(): bool
            {
                return false;
            }

            public static function isRisky(): bool
            {
                return false;
            }
        };

        $enabledRuleClassName = $ruleClassEnabled::class;
        $disabledRuleClassName = $ruleClassDisabled::class;

        $ruleFlags = [
            $enabledRuleClassName => true,
            $disabledRuleClassName => false,
        ];

        $svgOptimizer->configureRules($ruleFlags);

        self::assertSame(1, $svgOptimizer->getRulesCount());
    }

    /**
     * @throws \LogicException
     */
    #[Test]
    public function getMetaDataThrowsIfCalledBeforeOptimize(): void
    {
        $svgOptimizer = new SvgOptimizer($this->svgProvider);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Metadata is not available before optimization.');

        $svgOptimizer->getMetaData();
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->svgProvider = new class implements SvgProviderInterface {
            public function getInputContent(): string
            {
                return '<svg></svg>';
            }

            public function loadContent(): \DOMDocument
            {
                $domDocument = new \DOMDocument();
                $domDocument->loadXML('<svg></svg>');

                return $domDocument;
            }

            public function optimize(\DOMDocument $domDocument): SvgProviderInterface
            {
                return $this;
            }

            public function getOutputContent(): string
            {
                return '<svg>optimized</svg>';
            }

            public function saveToFile(string $path): SvgProviderInterface
            {
                return $this;
            }

            public function serialize(\DOMDocument $domDocument): string
            {
                return '<svg>optimized</svg>';
            }
        };
    }
}
