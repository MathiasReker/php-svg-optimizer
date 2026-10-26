<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Architecture\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * @internal
 *
 * @extends RuleTestCase<NoStaticPropertyRule>
 */
#[CoversClass(NoStaticPropertyRule::class)]
#[UsesClass(PackageLayer::class)]
final class NoStaticPropertyRuleTest extends RuleTestCase
{
    private const string PACKAGE = 'MathiasReker\PhpSvgOptimizer\\';

    #[Test]
    public function reportsStaticPropertiesOfPackageClasses(): void
    {
        $this->analyse([__DIR__ . '/Fixture/StaticProperty.php'], [
            [self::PACKAGE . 'Svg\Cache::$values must not be static.', 19],
        ]);
    }

    #[\Override]
    protected function getRule(): Rule
    {
        return new NoStaticPropertyRule();
    }
}
