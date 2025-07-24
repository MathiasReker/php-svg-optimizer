<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Exception;

use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(XmlProcessingException::class)]
final class XmlProcessingExceptionTest extends TestCase
{
    public function testExceptionCanBeInstantiated(): void
    {
        $xmlProcessingException = new XmlProcessingException('Test message', 123);

        self::assertSame('Test message', $xmlProcessingException->getMessage());
        self::assertSame(123, $xmlProcessingException->getCode());
    }
}
