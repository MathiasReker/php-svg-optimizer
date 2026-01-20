<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Exception;

use MathiasReker\PhpSvgOptimizer\Exception\SvgValidationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SvgValidationException::class)]
final class SvgValidationExceptionTest extends TestCase
{
    #[Test]
    public function canBeInstantiated(): void
    {
        $svgValidationException = new SvgValidationException('Invalid SVG detected', 100);

        self::assertSame('Invalid SVG detected', $svgValidationException->getMessage());
        self::assertSame(100, $svgValidationException->getCode());
    }
}
