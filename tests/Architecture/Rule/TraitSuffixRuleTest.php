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
 * @extends RuleTestCase<TraitSuffixRule>
 */
#[CoversClass(TraitSuffixRule::class)]
#[UsesClass(PackageLayer::class)]
final class TraitSuffixRuleTest extends RuleTestCase
{
    private const string PACKAGE = 'MathiasReker\PhpSvgOptimizer\\';

    #[Test]
    public function reportsPackageTraitsWhoseNameDoesNotEndInTrait(): void
    {
        $this->analyse([__DIR__ . '/Fixture/TraitSuffix.php'], [
            ['The name of the trait ' . self::PACKAGE . 'Pass\Trait\Unsuffixed must end in Trait.', 23],
        ]);
    }

    #[\Override]
    protected function getRule(): Rule
    {
        return new TraitSuffixRule();
    }
}
