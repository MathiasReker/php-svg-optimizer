<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Input;

use MathiasReker\PhpSvgOptimizer\Console\Input\OptionScanner;
use MathiasReker\PhpSvgOptimizer\Console\Input\SuggestionTrait;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversTrait(SuggestionTrait::class)]
#[UsesClass(OptionScanner::class)]
final class SuggestionTraitTest extends TestCase
{
    use SuggestionTrait;

    /**
     * @param list<string> $candidates
     */
    #[DataProvider('provideSuggestNamesTheClosestCandidateCases')]
    #[Test]
    public function suggestNamesTheClosestCandidate(string $name, array $candidates, string $expected): void
    {
        self::assertSame($expected, self::suggest($name, $candidates));
    }

    /**
     * @return iterable<string, array{string, list<string>, string}>
     */
    public static function provideSuggestNamesTheClosestCandidateCases(): iterable
    {
        yield 'a missing letter' => ['dryrun', ['dry-run', 'check'], ' Did you mean "dry-run"?'];

        yield 'a different letter case' => ['ALLOWRISKY', ['allowRisky', 'rules'], ' Did you mean "allowRisky"?'];

        yield 'the closest of several' => ['rule', ['rules', 'sets', 'rulez'], ' Did you mean "rules"?'];

        yield 'an exact match' => ['sets', ['sets'], ' Did you mean "sets"?'];

        yield 'a long name' => ['removeUnusedIdz', ['removeUnusedIds', 'removeComments'], ' Did you mean "removeUnusedIds"?'];

        yield 'nothing close' => ['banana', ['sets', 'rules'], ''];

        yield 'no candidates' => ['sets', [], ''];

        yield 'a multibyte name' => ['rüles', ['rules'], ' Did you mean "rules"?'];
    }
}
