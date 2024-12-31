<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Services\Util;

use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Services\Util\XmlProcessor;
use MathiasReker\PhpSvgOptimizer\Services\Validators\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(XmlProcessor::class)]
#[CoversClass(XmlProcessingException::class)]
#[CoversClass(SvgValidator::class)]
final class XmlProcessorTest extends TestCase
{
    private XmlProcessor $xmlProcessor;

    private MockObject $mockObject;

    /**
     * @throws XmlProcessingException
     */
    public function testProcessValidSvgContent(): void
    {
        $svgContent = '<svg><rect width="100" height="100" style="fill:blue;"/></svg>';
        $domDocument = new \DOMDocument();
        $domDocument->loadXML($svgContent);

        $callback = static fn (string $content): string => str_replace('blue', 'red', $content);

        $this->mockObject->expects(self::once())
            ->method('isValid')
            ->with(self::stringContains('fill:red'))
            ->willReturn(true);

        $this->xmlProcessor = $this->getMockBuilder(XmlProcessor::class)
            ->onlyMethods(['process'])
            ->getMock();

        $this->xmlProcessor->method('process')->willReturnCallback(
            /*
              * @throws XmlProcessingException
             */
            callback: function (\DOMDocument $domDocument, callable $callback) use ($svgContent): string {
                $svgContent = $domDocument->saveXML();
                $svgContent = $callback($svgContent);

                if (!\is_string($svgContent)) {
                    throw new XmlProcessingException('Callback must return a string.');
                }

                /**
                 * @var SvgValidator|MockObject $mockObject
                 */
                $mockObject = $this->mockObject;
                if (!$mockObject->isValid($svgContent)) {
                    throw new XmlProcessingException('Optimized SVG content is not valid.');
                }

                return $svgContent;
            }
        );

        $result = $this->xmlProcessor->process($domDocument, $callback);

        self::assertStringContainsString('fill:red', $result);
    }

    /**
     * @throws XmlProcessingException
     */
    public function testProcessSvgWithInvalidContent(): void
    {
        $svgContent = '<svg><rect width="100" height="100" style="fill:blue;"/></svg>';
        $domDocument = new \DOMDocument();
        $domDocument->loadXML($svgContent);

        $callback = static fn (string $content): string => '<invalid>content</invalid>';

        $this->mockObject->expects(self::once())
            ->method('isValid')
            ->with('<invalid>content</invalid>')
            ->willReturn(false);

        $this->expectException(XmlProcessingException::class);
        $this->expectExceptionMessage('Optimized SVG content is not valid.');

        $this->xmlProcessor = $this->getMockBuilder(XmlProcessor::class)
            ->onlyMethods(['process'])
            ->getMock();

        $this->xmlProcessor->method('process')->willReturnCallback(
            /*
             * @throws XmlProcessingException
             */
            callback: function (\DOMDocument $domDocument, callable $callback) use ($svgContent): string {
                /**
                 * @var string $svgContent
                 */
                $svgContent = $callback($domDocument->saveXML());

                /**
                 * @var SvgValidator|MockObject $mockObject
                 */
                $mockObject = $this->mockObject;
                if (!$mockObject->isValid($svgContent)) {
                    throw new XmlProcessingException('Optimized SVG content is not valid.');
                }

                return $svgContent;
            }
        );

        $this->xmlProcessor->process($domDocument, $callback);
    }

    /**
     * @throws XmlProcessingException
     */
    public function testProcessSvgWithCallbackReturningNonString(): void
    {
        $svgContent = '<svg><rect width="100" height="100" style="fill:blue;"/></svg>';
        $domDocument = new \DOMDocument();
        $domDocument->loadXML($svgContent);

        $callback = static fn (string $content): array => [];

        $this->expectException(XmlProcessingException::class);
        $this->expectExceptionMessage('Callback must return a string.');

        $this->xmlProcessor = $this->getMockBuilder(XmlProcessor::class)
            ->onlyMethods(['process'])
            ->getMock();

        $this->xmlProcessor->method('process')->willReturnCallback(
            /**
             * @throws XmlProcessingException
             */
            static function (\DOMDocument $domDocument, callable $callback): string {
                $svgContent = $domDocument->saveXML();
                $svgContent = $callback($svgContent);

                if (!\is_string($svgContent)) {
                    throw new XmlProcessingException('Callback must return a string.');
                }

                return $svgContent;
            }
        );

        $this->xmlProcessor->process($domDocument, $callback);
    }

    /**
     * @throws XmlProcessingException
     */
    public function testProcessWithInvalidXml(): void
    {
        $svgContent = '<svg><rect width="100" height="100" style="fill:blue;"/></svg>';
        $domDocument = new \DOMDocument();
        $domDocument->loadXML($svgContent);

        $callback = static fn (string $content): string => '<svg><rect></rect>';

        $this->expectException(XmlProcessingException::class);
        $this->expectExceptionMessage('Failed to load optimized XML content.');

        $this->xmlProcessor = $this->getMockBuilder(XmlProcessor::class)
            ->onlyMethods(['process'])
            ->getMock();

        $this->xmlProcessor->method('process')->willReturnCallback(
            /*
             * @throws XmlProcessingException
             */
            callback: static function (\DOMDocument $domDocument, callable $callback): string {
                /**
                 * @var string $svgContent
                 */
                $svgContent = $callback($domDocument->saveXML());

                if (!$domDocument->loadXML($svgContent)) {
                    throw new XmlProcessingException('Failed to load optimized XML content.');
                }

                return $svgContent;
            }
        );

        $this->xmlProcessor->process($domDocument, $callback);
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->mockObject = $this->createMock(SvgValidator::class);
    }
}
