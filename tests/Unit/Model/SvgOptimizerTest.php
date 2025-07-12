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
use MathiasReker\PhpSvgOptimizer\Exception\SvgValidationException;
use MathiasReker\PhpSvgOptimizer\Model\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use MathiasReker\PhpSvgOptimizer\ValueObject\MetaDataValueObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @no-named-arguments
 *
 * @internal
 */
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(MetaDataValueObject::class)]
#[CoversClass(SvgValidator::class)]
final class SvgOptimizerTest extends TestCase
{
    private SvgProviderInterface $provider;

    public function testAddRuleAndGetRulesCount(): void
    {
        $optimizer = new SvgOptimizer($this->provider);

        self::assertSame(0, $optimizer->getRulesCount());

        $rule = new class implements SvgOptimizerRuleInterface {
            public function optimize(\DOMDocument $domDocument): void
            {
                // no-op for test
            }
        };

        $optimizer->addRule($rule);
        self::assertSame(1, $optimizer->getRulesCount());
    }

    public function testHasRules(): void
    {
        $optimizer = new SvgOptimizer($this->provider);

        self::assertFalse($optimizer->hasRules(), 'Expected hasRules() to return false when no rules are added.');

        $rule = new class implements SvgOptimizerRuleInterface {
            public function optimize(\DOMDocument $domDocument): void
            {
                // no-op for test
            }
        };

        $optimizer->addRule($rule);

        self::assertTrue($optimizer->hasRules(), 'Expected hasRules() to return true after a rule is added.');
    }

    /**
     * @throws SvgValidationException
     */
    public function testOptimizeReturnsSelfAndContentIsSet(): void
    {
        $optimizer = new SvgOptimizer($this->provider);

        $result = $optimizer->optimize();

        self::assertSame($optimizer, $result);
        self::assertSame('<svg>optimized</svg>', $optimizer->getContent());
    }

    /**
     * @throws SvgValidationException
     */
    public function testOptimizeThrowsExceptionOnInvalidSvg(): void
    {
        $invalidProvider = new class implements SvgProviderInterface {
            public function getInputContent(): string
            {
                return 'invalid svg';
            }

            public function loadContent(): \DOMDocument
            {
                $doc = new \DOMDocument();
                $doc->loadXML('<svg></svg>');

                return $doc;
            }

            public function optimize(\DOMDocument $domDocument): SvgProviderInterface
            {
                return $this;
            }

            public function getOutputContent(): string
            {
                return '';
            }

            public function getMetaData(): MetaDataValueObject
            {
                return new MetaDataValueObject(
                    100,
                    50,
                    50,
                    50.0
                );
            }

            public function saveToFile(string $path): SvgProviderInterface
            {
                return $this;
            }
        };

        $optimizer = new SvgOptimizer($invalidProvider);

        $this->expectException(SvgValidationException::class);
        $this->expectExceptionMessage('The file does not appear to be a valid SVG file.');

        $optimizer->optimize();
    }

    public function testGetMetaDataReturnsProviderMetaData(): void
    {
        $optimizer = new SvgOptimizer($this->provider);

        $metaData = $optimizer->getMetaData();

        self::assertSame(100, $metaData->getOriginalSize());
        self::assertSame(50, $metaData->getOptimizedSize());
    }

    public function testSaveToFileReturnsSelf(): void
    {
        $optimizer = new SvgOptimizer($this->provider);

        $result = $optimizer->saveToFile('/tmp/output.svg');

        self::assertSame($optimizer, $result);
    }

    public function testGetContentReturnsEmptyStringBeforeOptimize(): void
    {
        $optimizer = new SvgOptimizer($this->provider);
        self::assertSame('', $optimizer->getContent());
    }

    /**
     * @throws SvgValidationException
     */
    public function testOptimizeWithNoRules(): void
    {
        $optimizer = new SvgOptimizer($this->provider);
        $result = $optimizer->optimize();

        self::assertSame($optimizer, $result);
        self::assertSame('<svg>optimized</svg>', $optimizer->getContent());
    }

    public function testSaveToFileChainingWithDifferentPaths(): void
    {
        $optimizer = new SvgOptimizer($this->provider);

        $result1 = $optimizer->saveToFile('/tmp/file1.svg');
        $result2 = $optimizer->saveToFile('/tmp/file2.svg');

        self::assertSame($optimizer, $result1);
        self::assertSame($optimizer, $result2);
    }

    public function testConfigureRulesAddsOnlyEnabledRules(): void
    {
        $optimizer = new SvgOptimizer($this->provider);

        self::assertSame(0, $optimizer->getRulesCount());

        $ruleClassEnabled = new class implements SvgOptimizerRuleInterface {
            public function optimize(\DOMDocument $domDocument): void
            {
            }
        };

        $ruleClassDisabled = new class implements SvgOptimizerRuleInterface {
            public function optimize(\DOMDocument $domDocument): void
            {
            }
        };

        $enabledRuleClassName = $ruleClassEnabled::class;
        $disabledRuleClassName = $ruleClassDisabled::class;

        $ruleFlags = [
            $enabledRuleClassName => true,
            $disabledRuleClassName => false,
        ];

        $optimizer->configureRules($ruleFlags);

        self::assertSame(1, $optimizer->getRulesCount());
    }

    protected function setUp(): void
    {
        $this->provider = new class implements SvgProviderInterface {
            public function getInputContent(): string
            {
                return '<svg></svg>';
            }

            public function loadContent(): \DOMDocument
            {
                $doc = new \DOMDocument();
                $doc->loadXML('<svg></svg>');

                return $doc;
            }

            public function optimize(\DOMDocument $domDocument): SvgProviderInterface
            {
                return $this;
            }

            public function getOutputContent(): string
            {
                return '<svg>optimized</svg>';
            }

            public function getMetaData(): MetaDataValueObject
            {
                return new MetaDataValueObject(
                    100,
                    50,
                    50,
                    50.0
                );
            }

            public function saveToFile(string $path): SvgProviderInterface
            {
                return $this;
            }
        };
    }
}
