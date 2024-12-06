<?php

/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Models;

use MathiasReker\PhpSvgOptimizer\Models\MetaDataValueObject;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MetaDataValueObject::class)]
final class MetaDataValueObjectTest extends TestCase
{
    private MetaDataValueObject $metaDataValueObject;

    public function testGetOriginalSize(): void
    {
        Assert::assertSame(1000, $this->metaDataValueObject->getOriginalSize());
    }

    public function testGetOptimizedSize(): void
    {
        Assert::assertSame(800, $this->metaDataValueObject->getOptimizedSize());
    }

    public function testGetSavedBytes(): void
    {
        Assert::assertSame(200, $this->metaDataValueObject->getSavedBytes());
    }

    public function testGetSavedPercentage(): void
    {
        Assert::assertEqualsWithDelta(20.0, $this->metaDataValueObject->getSavedPercentage(), \PHP_FLOAT_EPSILON);
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->metaDataValueObject = new MetaDataValueObject(
            1000,
            800,
            200,
            20.0
        );
    }
}
