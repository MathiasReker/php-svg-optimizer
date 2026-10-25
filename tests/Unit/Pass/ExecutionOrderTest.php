<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Pass;

use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Pass\PassInterface;
use MathiasReker\PhpSvgOptimizer\Pass\RevertIfLargerPassInterface;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertColorsToHex;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertCssClassesToAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertInlineStylesToAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertOneStopGradients;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertShapesToPath;
use MathiasReker\PhpSvgOptimizer\Service\Rule\FixAttributeNames;
use MathiasReker\PhpSvgOptimizer\Service\Rule\FlattenGroups;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MergeDuplicateGradients;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MergeStyles;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyListValues;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyNumericValues;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyPathData;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyStyles;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyTransformations;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MoveCommonAttributesToGroup;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MoveGroupAttributesToElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveComments;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDefaultAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDeprecatedAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDescriptions;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDuplicateElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyContainers;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyTextElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveForeignContent;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveHiddenElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveNonStandardAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveNonStandardTags;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveScripts;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveTitles;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnsafeMarkup;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnsafeUrls;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedDefinitions;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedIds;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedNamespaces;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedTextAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUseBombs;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUselessDefs;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUselessStrokeAndFill;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveZeroSizeShapes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\SanitizeStyleSheets;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ScopeSvgStyles;
use MathiasReker\PhpSvgOptimizer\Service\Rule\SortAttributes;
use MathiasReker\PhpSvgOptimizer\Type\Rule;
use MathiasReker\PhpSvgOptimizer\Type\RuleSet;
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
        $securityPasses = array_merge(...array_map(static fn (Rule $rule): array => $rule->passes(), RuleSet::Security->rules()));

        self::assertSame(
            [RemoveUnsafeMarkup::class, RemoveScripts::class, RemoveForeignContent::class, RemoveUnsafeUrls::class, SanitizeStyleSheets::class, RemoveUseBombs::class],
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

        yield 'use bombs are counted after all other unsafe content is removed' => [SanitizeStyleSheets::class, RemoveUseBombs::class];

        yield 'attribute names are fixed before unknown attributes are removed' => [FixAttributeNames::class, RemoveNonStandardAttributes::class];

        yield 'xlink:title becomes a title before titles are removed' => [RemoveDeprecatedAttributes::class, RemoveTitles::class];

        yield 'titles and descriptions are removed one after the other' => [RemoveTitles::class, RemoveDescriptions::class];

        yield 'attribute names are fixed before ids and classes are prefixed' => [FixAttributeNames::class, ScopeSvgStyles::class];

        yield 'ids and classes are prefixed before unused ids are removed, so a rule that prefixing drops cannot keep ids for one run only' => [ScopeSvgStyles::class, RemoveUnusedIds::class];

        yield 'ids and classes are prefixed before style elements are merged, which may wrap rules in at-rules that cannot be prefixed' => [ScopeSvgStyles::class, MergeStyles::class];

        yield 'ids and classes are prefixed before class rules are converted, so a rule that prefixing drops cannot block a conversion for one run only' => [ScopeSvgStyles::class, ConvertCssClassesToAttributes::class];

        yield 'ids and classes are prefixed before deprecated attributes are removed, so a rule that prefixing drops cannot keep them for one run only' => [ScopeSvgStyles::class, RemoveDeprecatedAttributes::class];

        yield 'class styles become attributes before colors are minified' => [ConvertCssClassesToAttributes::class, ConvertColorsToHex::class];

        yield 'inline styles become attributes before defaults are removed' => [ConvertInlineStylesToAttributes::class, RemoveDefaultAttributes::class];

        yield 'numbers are minified before defaults are compared' => [MinifyNumericValues::class, RemoveDefaultAttributes::class];

        yield 'stop colors are minified before gradients are inlined' => [ConvertColorsToHex::class, ConvertOneStopGradients::class];

        yield 'unused stop ids are removed before gradients are inlined' => [RemoveUnusedIds::class, ConvertOneStopGradients::class];

        yield 'empty attributes are removed before gradients are inlined' => [RemoveEmptyAttributes::class, ConvertOneStopGradients::class];

        yield 'groups are flattened before defaults are removed, as attributes moved off a group may become defaults' => [FlattenGroups::class, RemoveDefaultAttributes::class];

        yield 'style elements are merged before class rules are converted' => [MergeStyles::class, ConvertCssClassesToAttributes::class];

        yield 'style comments are removed before class rules are converted' => [MinifyStyles::class, ConvertCssClassesToAttributes::class];

        yield 'gradients are inlined before unused definitions are removed' => [ConvertOneStopGradients::class, RemoveUnusedDefinitions::class];

        yield 'gradients are inlined before useless definitions are removed' => [ConvertOneStopGradients::class, RemoveUselessDefs::class];

        yield 'unused definitions are removed before empty containers are removed' => [RemoveUnusedDefinitions::class, RemoveEmptyContainers::class];

        yield 'useless definitions are removed before empty containers are removed' => [RemoveUselessDefs::class, RemoveEmptyContainers::class];

        yield 'hidden elements are removed before empty containers are removed' => [RemoveHiddenElements::class, RemoveEmptyContainers::class];

        yield 'zero-size shapes are removed before empty containers are removed' => [RemoveZeroSizeShapes::class, RemoveEmptyContainers::class];

        yield 'duplicate elements are removed before empty containers are removed' => [RemoveDuplicateElements::class, RemoveEmptyContainers::class];

        yield 'empty text elements are removed before empty containers are removed' => [RemoveEmptyTextElements::class, RemoveEmptyContainers::class];

        yield 'empty text elements are removed before unused text attributes are removed' => [RemoveEmptyTextElements::class, RemoveUnusedTextAttributes::class];

        yield 'empty groups are removed before attributes are moved to groups' => [RemoveEmptyContainers::class, MoveCommonAttributesToGroup::class];

        yield 'groups are flattened before common attributes are moved to groups' => [FlattenGroups::class, MoveCommonAttributesToGroup::class];

        yield 'group transforms are moved after groups are flattened' => [FlattenGroups::class, MoveGroupAttributesToElements::class];

        yield 'attributes are moved to groups before useless strokes and fills are removed' => [MoveCommonAttributesToGroup::class, RemoveUselessStrokeAndFill::class];

        yield 'foreign attributes are removed before groups are flattened' => [RemoveNonStandardAttributes::class, FlattenGroups::class];

        yield 'groups are flattened before transforms are minified' => [FlattenGroups::class, MinifyTransformations::class];

        yield 'zero-size shapes are removed before shapes become paths' => [RemoveZeroSizeShapes::class, ConvertShapesToPath::class];

        yield 'list values are minified before shapes become paths' => [MinifyListValues::class, ConvertShapesToPath::class];

        yield 'shapes become paths before path data is minified' => [ConvertShapesToPath::class, MinifyPathData::class];

        yield 'elements are removed before unused namespaces are removed' => [RemoveNonStandardTags::class, RemoveUnusedNamespaces::class];

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
            [MergeDuplicateGradients::class, ConvertOneStopGradients::class, FlattenGroups::class, MoveGroupAttributesToElements::class, ConvertShapesToPath::class],
            $revertedPasses,
        );
    }

    #[Test]
    public function everyPassOfEveryRuleHasAPosition(): void
    {
        $rulePasses = array_merge(...array_map(static fn (Rule $rule): array => $rule->passes(), Rule::cases()));
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
