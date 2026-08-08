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
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AbstractXmlProcessor::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class ConcreteXmlProcessorTest extends TestCase
{
    /**
     * @throws XmlProcessingException
     */
    #[Test]
    public function processValidSvgContent(): void
    {
        $svg = '<svg><rect width="100" height="100" style="fill:blue;"/></svg>';
        $domDocument = new \DOMDocument();
        $domDocument->loadXML($svg);

        $callback = static fn (string $content): string => str_replace('blue', 'red', $content);

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
    #[Test]
    public function processWithCallbackReturningNonStringThrows(): void
    {
        $svg = '<svg><rect width="100" height="100"/></svg>';
        $domDocument = new \DOMDocument();
        $domDocument->loadXML($svg);

        $callback = static fn (): array => [];

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
    #[Test]
    public function processWithCallbackThrowingGenericExceptionWrapsAsXmlProcessingException(): void
    {
        $svg = '<svg><rect width="100" height="100"/></svg>';
        $domDocument = new \DOMDocument();
        $domDocument->loadXML($svg);

        $runtimeException = new \RuntimeException('boom');
        $callback = static function () use ($runtimeException): string {
            throw $runtimeException;
        };

        $processor = new /**
                          * @no-named-arguments
                          */
        readonly class extends AbstractXmlProcessor {
        };

        try {
            $processor->process($domDocument, $callback);
            self::fail('Expected XmlProcessingException was not thrown.');
        } catch (XmlProcessingException $xmlProcessingException) {
            self::assertSame('Failed to process the XML content.', $xmlProcessingException->getMessage());
            self::assertSame($runtimeException, $xmlProcessingException->getPrevious());
        }
    }

    /**
     * @throws XmlProcessingException
     */
    #[Test]
    public function processInvalidContentThrows(): void
    {
        $svg = '<svg></svg>';
        $domDocument = new \DOMDocument();
        $domDocument->loadXML($svg);

        $callback = static fn (): string => '';

        $processor = new /**
                          * @no-named-arguments
                          */
        readonly class extends AbstractXmlProcessor {
        };

        $this->expectException(XmlProcessingException::class);
        $this->expectExceptionMessage('Optimized SVG content is not valid.');

        $processor->process($domDocument, $callback);
    }
}
