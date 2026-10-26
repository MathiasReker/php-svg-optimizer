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
 * @extends RuleTestCase<VisibilityTagRule>
 */
#[CoversClass(VisibilityTagRule::class)]
#[UsesClass(PackageLayer::class)]
final class VisibilityTagRuleTest extends RuleTestCase
{
    private const string PACKAGE = 'MathiasReker\PhpSvgOptimizer\\';

    #[Test]
    public function reportsPackageClassesWithoutTheTagOfTheirLayer(): void
    {
        $this->analyse([__DIR__ . '/Fixture/VisibilityTag.php'], [
            [self::PACKAGE . 'InternalInRoot must carry the @api tag.', 25],
            [self::PACKAGE . 'Exception\UntaggedException must carry the @api tag.', 34],
            [self::PACKAGE . 'Pass\ApiInPass must carry the @internal tag.', 52],
            [self::PACKAGE . 'Pass\UntaggedTrait must carry the @internal tag.', 59],
            [self::PACKAGE . 'Pass\UntaggedInterface must carry the @internal tag.', 66],
        ]);
    }

    #[\Override]
    protected function getRule(): Rule
    {
        return new VisibilityTagRule();
    }
}
