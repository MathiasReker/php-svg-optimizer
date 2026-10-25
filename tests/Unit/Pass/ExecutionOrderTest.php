<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Pass;

use MathiasReker\PhpSvgOptimizer\Pass\ConvertCssClassesToAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\ConvertInlineStylesToAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\ConvertOneStopGradientsToColors;
use MathiasReker\PhpSvgOptimizer\Pass\ConvertShapesToPaths;
use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Pass\FixAttributeNames;
use MathiasReker\PhpSvgOptimizer\Pass\FlattenGroups;
use MathiasReker\PhpSvgOptimizer\Pass\MergeDuplicateGradients;
use MathiasReker\PhpSvgOptimizer\Pass\MergeStyleSheets;
use MathiasReker\PhpSvgOptimizer\Pass\MigrateDeprecatedAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyColors;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyNumberLists;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyNumericValues;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyPathData;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyStyleSheets;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyTransforms;
use MathiasReker\PhpSvgOptimizer\Pass\MoveCommonAttributesToGroups;
use MathiasReker\PhpSvgOptimizer\Pass\MoveGroupTransformsToChildren;
use MathiasReker\PhpSvgOptimizer\Pass\PassInterface;
use MathiasReker\PhpSvgOptimizer\Pass\PrefixIdsAndClasses;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveComments;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDefaultAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDescriptions;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDuplicateElements;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEmptyAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEmptyContainers;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEmptyTextElements;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveForeignContent;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveHiddenElements;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveIneffectiveDefinitions;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveIneffectiveStrokeAndFill;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveIneffectiveTextAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveNonStandardAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveNonStandardElements;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveScripts;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveTitles;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveUnsafeMarkup;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveUnsafeStyles;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveUnsafeUrls;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveUnusedDefinitions;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveUnusedIds;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveUnusedNamespaces;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveUseBombs;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveZeroSizeShapes;
use MathiasReker\PhpSvgOptimizer\Pass\RevertIfLargerPassInterface;
use MathiasReker\PhpSvgOptimizer\Pass\SortAttributes;
use MathiasReker\PhpSvgOptimizer\Rule;
use MathiasReker\PhpSvgOptimizer\RuleSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @no-named-arguments
 */
#[CoversClass(ExecutionOrder::class)]
#[UsesClass(Rule::class)]
#[UsesClass(RuleSet::class)]
final class ExecutionOrderTest extends TestCase
{
    #[Test]
    public function everyPassAppearsOnce(): void
    {
        self::assertSame(ExecutionOrder::passes(), array_values(array_unique(ExecutionOrder::passes())));
        self::assertCount(57, ExecutionOrder::passes());
    }

    #[Test]
    public function securityPassesRunFirstAndSortingLast(): void
    {
        $securityPasses = array_merge(...array_map(static fn (Rule $rule): array => $rule->getPassClasses(), RuleSet::Security->getRules()));

        self::assertSame(
            [RemoveUnsafeMarkup::class, RemoveScripts::class, RemoveForeignContent::class, RemoveUnsafeUrls::class, RemoveUnsafeStyles::class, RemoveUseBombs::class],
            \array_slice(ExecutionOrder::passes(), 0, \count($securityPasses)),
        );
        self::assertSame(SortAttributes::class, ExecutionOrder::passes()[\count(ExecutionOrder::passes()) - 1]);
    }

    /**
     * @param class-string<PassInterface> $first
     * @param class-string<PassInterface> $second
     */
    #[DataProvider('providePassRunsBeforeCases')]
    #[Test]
    public function passRunsBefore(string $first, string $second): void
    {
        self::assertLessThan($this->position($second), $this->position($first));
    }

    /**
     * @return iterable<string, array{class-string<PassInterface>, class-string<PassInterface>}>
     */
    public static function providePassRunsBeforeCases(): iterable
    {
        yield 'dangerous animations are removed before unsafe links are unwrapped' => [RemoveScripts::class, RemoveForeignContent::class];

        yield 'unsafe links are unwrapped before unsafe URLs are removed' => [RemoveForeignContent::class, RemoveUnsafeUrls::class];

        yield 'use bombs are counted after all other unsafe content is removed' => [RemoveUnsafeStyles::class, RemoveUseBombs::class];

        yield 'attribute names are fixed before unknown attributes are removed' => [FixAttributeNames::class, RemoveNonStandardAttributes::class];

        yield 'xlink:title becomes a title before titles are removed' => [MigrateDeprecatedAttributes::class, RemoveTitles::class];

        yield 'titles and descriptions are removed one after the other' => [RemoveTitles::class, RemoveDescriptions::class];

        yield 'attribute names are fixed before ids and classes are prefixed' => [FixAttributeNames::class, PrefixIdsAndClasses::class];

        yield 'ids and classes are prefixed before unused ids are removed, so a rule that prefixing drops cannot keep ids for one run only' => [PrefixIdsAndClasses::class, RemoveUnusedIds::class];

        yield 'ids and classes are prefixed before style elements are merged, which may wrap rules in at-rules that cannot be prefixed' => [PrefixIdsAndClasses::class, MergeStyleSheets::class];

        yield 'ids and classes are prefixed before class rules are converted, so a rule that prefixing drops cannot block a conversion for one run only' => [PrefixIdsAndClasses::class, ConvertCssClassesToAttributes::class];

        yield 'ids and classes are prefixed before deprecated attributes are removed, so a rule that prefixing drops cannot keep them for one run only' => [PrefixIdsAndClasses::class, MigrateDeprecatedAttributes::class];

        yield 'class styles become attributes before colors are minified' => [ConvertCssClassesToAttributes::class, MinifyColors::class];

        yield 'inline styles become attributes before defaults are removed' => [ConvertInlineStylesToAttributes::class, RemoveDefaultAttributes::class];

        yield 'numbers are minified before defaults are compared' => [MinifyNumericValues::class, RemoveDefaultAttributes::class];

        yield 'stop colors are minified before gradients are inlined' => [MinifyColors::class, ConvertOneStopGradientsToColors::class];

        yield 'unused stop ids are removed before gradients are inlined' => [RemoveUnusedIds::class, ConvertOneStopGradientsToColors::class];

        yield 'empty attributes are removed before gradients are inlined' => [RemoveEmptyAttributes::class, ConvertOneStopGradientsToColors::class];

        yield 'groups are flattened before defaults are removed, as attributes moved off a group may become defaults' => [FlattenGroups::class, RemoveDefaultAttributes::class];

        yield 'style elements are merged before class rules are converted' => [MergeStyleSheets::class, ConvertCssClassesToAttributes::class];

        yield 'style comments are removed before class rules are converted' => [MinifyStyleSheets::class, ConvertCssClassesToAttributes::class];

        yield 'gradients are inlined before unused definitions are removed' => [ConvertOneStopGradientsToColors::class, RemoveUnusedDefinitions::class];

        yield 'gradients are inlined before useless definitions are removed' => [ConvertOneStopGradientsToColors::class, RemoveIneffectiveDefinitions::class];

        yield 'empty containers are removed before unused definitions, so the definitions they referenced are unused' => [RemoveEmptyContainers::class, RemoveUnusedDefinitions::class];

        yield 'useless definitions are removed before empty containers are removed' => [RemoveIneffectiveDefinitions::class, RemoveEmptyContainers::class];

        yield 'hidden elements are removed before empty containers are removed' => [RemoveHiddenElements::class, RemoveEmptyContainers::class];

        yield 'zero-size shapes are removed before empty containers are removed' => [RemoveZeroSizeShapes::class, RemoveEmptyContainers::class];

        yield 'duplicate elements are removed before empty containers are removed' => [RemoveDuplicateElements::class, RemoveEmptyContainers::class];

        yield 'empty text elements are removed before empty containers are removed' => [RemoveEmptyTextElements::class, RemoveEmptyContainers::class];

        yield 'empty text elements are removed before unused text attributes are removed' => [RemoveEmptyTextElements::class, RemoveIneffectiveTextAttributes::class];

        yield 'empty groups are removed before attributes are moved to groups' => [RemoveEmptyContainers::class, MoveCommonAttributesToGroups::class];

        yield 'groups are flattened before common attributes are moved to groups' => [FlattenGroups::class, MoveCommonAttributesToGroups::class];

        yield 'group transforms are moved after groups are flattened' => [FlattenGroups::class, MoveGroupTransformsToChildren::class];

        yield 'attributes are moved to groups before useless strokes and fills are removed' => [MoveCommonAttributesToGroups::class, RemoveIneffectiveStrokeAndFill::class];

        yield 'foreign attributes are removed before groups are flattened' => [RemoveNonStandardAttributes::class, FlattenGroups::class];

        yield 'groups are flattened before transforms are minified' => [FlattenGroups::class, MinifyTransforms::class];

        yield 'zero-size shapes are removed before shapes become paths' => [RemoveZeroSizeShapes::class, ConvertShapesToPaths::class];

        yield 'list values are minified before shapes become paths' => [MinifyNumberLists::class, ConvertShapesToPaths::class];

        yield 'shapes become paths before path data is minified' => [ConvertShapesToPaths::class, MinifyPathData::class];

        yield 'elements are removed before unused namespaces are removed' => [RemoveNonStandardElements::class, RemoveUnusedNamespaces::class];

        yield 'comments are removed before attributes are sorted' => [RemoveComments::class, SortAttributes::class];
    }

    #[Test]
    public function exactlyThePassesThatCanGrowTheDocumentAreRevertedIfLarger(): void
    {
        $revertedPasses = array_values(array_filter(
            ExecutionOrder::passes(),
            static fn (string $pass): bool => is_subclass_of($pass, RevertIfLargerPassInterface::class),
        ));

        self::assertSame(
            [MergeDuplicateGradients::class, ConvertOneStopGradientsToColors::class, FlattenGroups::class, MoveGroupTransformsToChildren::class, ConvertShapesToPaths::class],
            $revertedPasses,
        );
    }

    #[Test]
    public function everyPassOfEveryRuleHasAPosition(): void
    {
        $rulePasses = array_merge(...array_map(static fn (Rule $rule): array => $rule->getPassClasses(), Rule::cases()));
        sort($rulePasses);
        $passes = ExecutionOrder::passes();
        sort($passes);

        self::assertSame($passes, array_values(array_unique($rulePasses)));
    }

    #[Test]
    public function sortOrdersPassesByExecutionOrder(): void
    {
        $sortAttributes = new SortAttributes();
        $removeComments = new RemoveComments();
        $flattenGroups = new FlattenGroups();

        self::assertSame(
            [$removeComments, $flattenGroups, $sortAttributes],
            ExecutionOrder::sort([$sortAttributes, $removeComments, $flattenGroups]),
        );
    }

    #[Test]
    public function sortRunsPassesWithoutPositionLastInTheirOrder(): void
    {
        $first = new class implements PassInterface {
            #[\Override]
            public function apply(\DOMDocument $domDocument): void
            {
            }
        };
        $second = new class implements PassInterface {
            #[\Override]
            public function apply(\DOMDocument $domDocument): void
            {
            }
        };
        $removeComments = new RemoveComments();

        self::assertSame([$removeComments, $first, $second], ExecutionOrder::sort([$first, $removeComments, $second]));
    }

    /**
     * @param class-string<PassInterface> $pass
     */
    private function position(string $pass): int
    {
        $position = array_search($pass, ExecutionOrder::passes(), true);
        self::assertIsInt($position);

        return $position;
    }
}
