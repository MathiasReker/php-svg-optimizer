<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\ValueObject;

use MathiasReker\PhpSvgOptimizer\ValueObject\OptionValueObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(OptionValueObject::class)]
final class OptionValueObjectTest extends TestCase
{
    private const string TITLE = 'process';

    private const string DESCRIPTION = 'Process SVG files for optimization.';

    private OptionValueObject $optionValueObject;

    #[Test]
    public function getTitle(): void
    {
        self::assertSame(self::TITLE, $this->optionValueObject->getTitle());
    }

    #[Test]
    public function getDescription(): void
    {
        self::assertSame(self::DESCRIPTION, $this->optionValueObject->getDescription());
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->optionValueObject = new OptionValueObject(
            self::TITLE,
            self::DESCRIPTION
        );
    }
}
