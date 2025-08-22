<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Processor;

use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Service\Processor\AbstractXmlProcessor;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AbstractXmlProcessor::class)]
#[CoversClass(SvgValidator::class)]
final class ConcreteXmlProcessorTest extends TestCase
{
    /**
     * Test processing with a valid callback that changes fill color from blue to red.
     *
     * @throws XmlProcessingException
     */
    public function testProcessValidSvgContent(): void
    {
        $svg = '<svg><rect width="100" height="100" style="fill:blue;"/></svg>';
        $domDocument = new \DOMDocument();
        $domDocument->loadXML($svg);

        $callback = static fn (string $content): string => str_replace('blue', 'red', $content);

        // Use anonymous class because AbstractXmlProcessor is abstract
        $processor = new /**
                          * @no-named-arguments
                          */
        readonly class extends AbstractXmlProcessor {
        };

        $result = $processor->process($domDocument, $callback);

        self::assertStringContainsString('fill:red', $result);
        self::assertStringContainsString('<svg', $result);
    }

    /**
     * @throws XmlProcessingException
     */
    public function testProcessWithInvalidXmlThrows(): void
    {
        $svg = '<svg><rect width="100" height="100"/></svg>';
        $domDocument = new \DOMDocument();
        $domDocument->loadXML($svg);

        // Callback returns broken XML (missing closing tags)
        $callback = static fn (string $content): string => '<svg><rect>';

        $processor = new /**
                          * @no-named-arguments
                          */
        readonly class extends AbstractXmlProcessor {
        };

        $this->expectException(XmlProcessingException::class);
        $this->expectExceptionMessage('Failed to load optimized XML content.');

        $processor->process($domDocument, $callback);
    }

    /**
     * Test processing with callback returning non-string (should throw).
     *
     * @throws XmlProcessingException
     */
    public function testProcessWithCallbackReturningNonStringThrows(): void
    {
        $svg = '<svg><rect width="100" height="100"/></svg>';
        $domDocument = new \DOMDocument();
        $domDocument->loadXML($svg);

        // Callback returns array, not string
        $callback = static fn (string $content): array => [];

        $processor = new /**
                          * @no-named-arguments
                          */
        readonly class extends AbstractXmlProcessor {
        };

        $this->expectException(XmlProcessingException::class);
        $this->expectExceptionMessage('Callback must return a string.');

        $processor->process($domDocument, $callback);
    }

    /**
     * @throws XmlProcessingException
     */
    public function testProcessInvalidContentThrows(): void
    {
        $svg = '<svg></svg>';
        $domDocument = new \DOMDocument();
        $domDocument->loadXML($svg);

        // Callback returns empty string (invalid SVG)
        $callback = static fn (string $content): string => '';

        $processor = new /**
                          * @no-named-arguments
                          */
        readonly class extends AbstractXmlProcessor {
        };

        $this->expectException(XmlProcessingException::class);
        $this->expectExceptionMessage('Optimized SVG content is not valid.');

        $processor->process($domDocument, $callback);
    }

    /**
     * @throws XmlProcessingException
     */
    public function testProcessLoadXmlTriggersWarningConvertedToException(): void
    {
        $svg = '<svg></svg>';
        $domDocument = new \DOMDocument();
        $domDocument->loadXML($svg);

        // Intentionally invalid XML that will cause loadXML to fail
        $callback = static fn (string $content): string => '<svg><invalid>';

        $processor = new /**
                          * @no-named-arguments
                          */
        readonly class extends AbstractXmlProcessor {
        };

        $this->expectException(XmlProcessingException::class);
        $this->expectExceptionMessage('Failed to load optimized XML content.');

        $processor->process($domDocument, $callback);
    }
}
