<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Provider;

use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Service\Data\MetaData;
use MathiasReker\PhpSvgOptimizer\Service\Formatter\XmlFormatter;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\ValueObject\Metrics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(StringProvider::class)]
#[CoversClass(Metrics::class)]
#[CoversClass(MetaData::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(XmlFormatter::class)]
final class StringProviderTest extends TestCase
{
    private const string TEST_INPUT_STRING = '<svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100"/></svg>';

    #[Test]
    public function getInputContent(): void
    {
        $stringProvider = new StringProvider(self::TEST_INPUT_STRING);

        self::assertSame(self::TEST_INPUT_STRING, $stringProvider->getInputContent());
    }

    /**
     * @throws XmlProcessingException
     */
    #[Test]
    public function loadContent(): void
    {
        $stringProvider = new StringProvider(self::TEST_INPUT_STRING);
        $domDocument = $stringProvider->loadContent();

        self::assertNotNull($domDocument->documentElement);
        self::assertSame('svg', $domDocument->documentElement->tagName);
    }

    #[Test]
    public function getOutputContentBeforeOptimize(): void
    {
        $stringProvider = new StringProvider(self::TEST_INPUT_STRING);

        self::assertEmpty($stringProvider->getOutputContent());
    }

    /**
     * @throws XmlProcessingException
     */
    #[Test]
    public function optimize(): void
    {
        $stringProvider = new StringProvider(self::TEST_INPUT_STRING);
        $domDocument = $stringProvider->loadContent();

        $stringProvider->optimize($domDocument);

        self::assertSame(self::TEST_INPUT_STRING, $stringProvider->getOutputContent());
    }
}
