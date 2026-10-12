<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Processor;

use MathiasReker\PhpSvgOptimizer\Model\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Processor\AbstractXmlProcessor;
use MathiasReker\PhpSvgOptimizer\Service\Processor\Trait\XmlLoaderTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AbstractXmlProcessor::class)]
#[CoversClass(SvgOptimizer::class)]
final class XmlLoaderTraitTest extends TestCase
{
    #[DataProvider('provideRestoresThePreviousErrorModeCases')]
    #[Test]
    public function restoresThePreviousErrorMode(bool $useInternalErrors): void
    {
        $previous = libxml_use_internal_errors($useInternalErrors);

        try {
            $this->load(new \DOMDocument(), '<svg><use xlink:href="#a"/></svg>');

            self::assertSame($useInternalErrors, libxml_use_internal_errors());
        } finally {
            libxml_use_internal_errors($previous);
        }
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function provideRestoresThePreviousErrorModeCases(): iterable
    {
        yield 'internal errors disabled' => [false];

        yield 'internal errors enabled' => [true];
    }

    #[Test]
    public function loadsRecoverableXmlWithoutLeavingErrorsBehind(): void
    {
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            $domDocument = new \DOMDocument();

            self::assertTrue($this->load($domDocument, '<svg><use xlink:href="#a"/></svg>'));
            self::assertSame('svg', $domDocument->documentElement?->nodeName);
            self::assertSame([], libxml_get_errors());
        } finally {
            libxml_use_internal_errors($previous);
        }
    }

    #[Test]
    public function returnsFalseForMalformedXml(): void
    {
        self::assertFalse($this->load(new \DOMDocument(), '<svg>'));
    }

    #[Test]
    public function passesTheOptions(): void
    {
        $domDocument = new \DOMDocument();

        self::assertTrue($this->load($domDocument, '<svg><![CDATA[a]]></svg>', \LIBXML_NOCDATA));
        self::assertNotInstanceOf(\DOMCdataSection::class, $domDocument->documentElement?->firstChild);
        self::assertSame('a', $domDocument->documentElement?->textContent);
    }

    /**
     * @param non-empty-string $xml
     */
    private function load(\DOMDocument $domDocument, string $xml, int $options = 0): bool
    {
        $loader = new class {
            use XmlLoaderTrait;

            /**
             * @param non-empty-string $xml
             */
            public function load(\DOMDocument $domDocument, string $xml, int $options): bool
            {
                return $this->loadXmlSilently($domDocument, $xml, $options);
            }
        };

        return $loader->load($domDocument, $xml, $options);
    }
}
