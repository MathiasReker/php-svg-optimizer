<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Processor;

use MathiasReker\PhpSvgOptimizer\Service\Optimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Processor\Trait\XmlLoaderTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SvgOptimizer::class)]
final class XmlLoaderTraitTest extends TestCase
{
    use XmlLoaderTrait;

    #[DataProvider('provideRestoresThePreviousErrorModeCases')]
    #[Test]
    public function restoresThePreviousErrorMode(bool $useInternalErrors): void
    {
        $previous = libxml_use_internal_errors($useInternalErrors);

        try {
            $this->loadXmlSilently(new \DOMDocument(), '<svg><use xlink:href="#a"/></svg>');

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

            self::assertTrue($this->loadXmlSilently($domDocument, '<svg><use xlink:href="#a"/></svg>'));
            self::assertSame('svg', $domDocument->documentElement?->nodeName);
            self::assertSame([], libxml_get_errors());
        } finally {
            libxml_use_internal_errors($previous);
        }
    }

    #[Test]
    public function returnsFalseForMalformedXml(): void
    {
        self::assertFalse($this->loadXmlSilently(new \DOMDocument(), '<svg>'));
    }

    #[Test]
    public function loadsWithoutOptionsByDefault(): void
    {
        $domDocument = new \DOMDocument();

        self::assertTrue($this->loadXmlSilently($domDocument, '<svg><![CDATA[a]]></svg>'));
        self::assertInstanceOf(\DOMCdataSection::class, $domDocument->documentElement?->firstChild);
    }

    #[Test]
    public function passesTheOptions(): void
    {
        $domDocument = new \DOMDocument();

        self::assertTrue($this->loadXmlSilently($domDocument, '<svg><![CDATA[a]]></svg>', \LIBXML_NOCDATA));
        self::assertNotInstanceOf(\DOMCdataSection::class, $domDocument->documentElement?->firstChild);
        self::assertSame('a', $domDocument->documentElement?->textContent);
    }
}
