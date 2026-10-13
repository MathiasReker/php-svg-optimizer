<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Rule\Trait;

use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertOneStopGradients;
use MathiasReker\PhpSvgOptimizer\Service\Rule\FlattenGroups;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedIds;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedMasks;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\IdReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Tests\Unit\Support\RegexFailureTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ConvertOneStopGradients::class)]
#[CoversClass(FlattenGroups::class)]
#[CoversClass(RemoveUnusedIds::class)]
#[CoversClass(RemoveUnusedMasks::class)]
final class IdReferenceTraitTest extends TestCase
{
    use IdReferenceTrait;
    use RegexFailureTrait;

    #[Test]
    public function findsFragmentReferences(): void
    {
        self::assertArrayHasKey('a', $this->fragmentReferences('url(#a)'));
    }

    #[RunInSeparateProcess]
    #[Test]
    public function findsNoFragmentReferencesWhenTheTextCannotBeMatched(): void
    {
        self::assertSame([], $this->withFailingRegularExpressions(fn (): array => $this->fragmentReferences('url(#a)')));
    }
}
