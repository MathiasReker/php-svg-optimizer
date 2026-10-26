<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit;

use MathiasReker\PhpSvgOptimizer\Exception\FileNotFoundException;
use MathiasReker\PhpSvgOptimizer\Exception\InvalidSvgException;
use MathiasReker\PhpSvgOptimizer\Exception\IOException;
use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\File\SvgFile;
use MathiasReker\PhpSvgOptimizer\Metrics;
use MathiasReker\PhpSvgOptimizer\OptimizationResult;
use MathiasReker\PhpSvgOptimizer\Pass\ConvertCssClassesToAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\ConvertEllipsesToCircles;
use MathiasReker\PhpSvgOptimizer\Pass\ConvertInlineStylesToAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\ConvertOneStopGradientsToColors;
use MathiasReker\PhpSvgOptimizer\Pass\ConvertShapesToPaths;
use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Pass\FixAttributeNames;
use MathiasReker\PhpSvgOptimizer\Pass\FlattenGroups;
use MathiasReker\PhpSvgOptimizer\Pass\MergeDuplicateGradients;
use MathiasReker\PhpSvgOptimizer\Pass\MergeStyleSheets;
use MathiasReker\PhpSvgOptimizer\Pass\MigrateDeprecatedAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\MigrateToSvg2;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyAttributeWhitespace;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyColors;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyNumberLists;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyNumericValues;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyPathData;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyStyleSheets;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyTransforms;
use MathiasReker\PhpSvgOptimizer\Pass\MoveCommonAttributesToGroups;
use MathiasReker\PhpSvgOptimizer\Pass\MoveGroupTransformsToChildren;
use MathiasReker\PhpSvgOptimizer\Pass\PassInterface;
use MathiasReker\PhpSvgOptimizer\Pass\Pipeline;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveAriaAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveComments;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDataAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDefaultAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDefaultTypeAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDefaultXmlSpace;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDescriptions;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDuplicateElements;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEditorMarkup;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEmptyAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEmptyContainers;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEmptyTextElements;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEnableBackground;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveForeignContent;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveHiddenElements;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveIneffectiveDefinitions;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveIneffectiveGroupAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveIneffectiveStrokeAndFill;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveIneffectiveTextAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveInvisibleCharacters;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveMetadata;
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
use MathiasReker\PhpSvgOptimizer\Pass\RemoveWidthHeightAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveZeroSizeShapes;
use MathiasReker\PhpSvgOptimizer\Pass\ScopeIdsAndClasses;
use MathiasReker\PhpSvgOptimizer\Pass\SortAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\SortDefinitions;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Color\NamedColorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\ClassSelectorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\DeclarationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\SelectorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\TokenContextTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\TokenizerTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AnimationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AttributeNodeTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\DefinitionElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementNameTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementRemovalTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementRenameTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\FragmentTargetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdRenameTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberListTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberLiteralTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Paint\GradientLinkTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Path\PathDataParserTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Path\PathDataSerializerTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Path\PathDataTokenTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Presentation\IneffectiveAttributeTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Presentation\InheritanceTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Presentation\InheritedValueTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Security\CanonicalNameTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Security\DangerousUrlTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Security\UnsafeAttributeTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Svg2\AttributeMigrationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Svg2\XlinkMigrationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Transform\TransformListTrait;
use MathiasReker\PhpSvgOptimizer\Rule;
use MathiasReker\PhpSvgOptimizer\RuleSet;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
use MathiasReker\PhpSvgOptimizer\Svg\ElementAttributeMap;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;
use MathiasReker\PhpSvgOptimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Tests\Double\TemporaryDirectoryTrait;
use MathiasReker\PhpSvgOptimizer\Xml\EntityReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Xml\Parser;
use MathiasReker\PhpSvgOptimizer\Xml\Serializer;
use MathiasReker\PhpSvgOptimizer\Xml\SvgValidator;
use MathiasReker\PhpSvgOptimizer\Xml\TextWhitespaceTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @no-named-arguments
 */
#[CoversClass(SvgOptimizer::class)]
#[UsesClass(Attribute::class)]
#[UsesClass(ConvertCssClassesToAttributes::class)]
#[UsesClass(ConvertEllipsesToCircles::class)]
#[UsesClass(ConvertInlineStylesToAttributes::class)]
#[UsesClass(ConvertOneStopGradientsToColors::class)]
#[UsesClass(ConvertShapesToPaths::class)]
#[UsesClass(Element::class)]
#[UsesClass(ElementAttributeMap::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(FixAttributeNames::class)]
#[UsesClass(FlattenGroups::class)]
#[UsesClass(MergeDuplicateGradients::class)]
#[UsesClass(MergeStyleSheets::class)]
#[UsesClass(Metrics::class)]
#[UsesClass(MigrateDeprecatedAttributes::class)]
#[UsesClass(MigrateToSvg2::class)]
#[UsesClass(MinifyAttributeWhitespace::class)]
#[UsesClass(MinifyColors::class)]
#[UsesClass(MinifyNumberLists::class)]
#[UsesClass(MinifyNumericValues::class)]
#[UsesClass(MinifyPathData::class)]
#[UsesClass(MinifyStyleSheets::class)]
#[UsesClass(MinifyTransforms::class)]
#[UsesClass(MoveCommonAttributesToGroups::class)]
#[UsesClass(MoveGroupTransformsToChildren::class)]
#[UsesClass(NamespaceUri::class)]
#[UsesClass(OptimizationResult::class)]
#[UsesClass(Parser::class)]
#[UsesClass(Pipeline::class)]
#[UsesClass(RemoveAriaAttributes::class)]
#[UsesClass(RemoveComments::class)]
#[UsesClass(RemoveDataAttributes::class)]
#[UsesClass(RemoveDefaultAttributes::class)]
#[UsesClass(RemoveDefaultTypeAttributes::class)]
#[UsesClass(RemoveDefaultXmlSpace::class)]
#[UsesClass(RemoveDescriptions::class)]
#[UsesClass(RemoveDuplicateElements::class)]
#[UsesClass(RemoveEditorMarkup::class)]
#[UsesClass(RemoveEmptyAttributes::class)]
#[UsesClass(RemoveEmptyContainers::class)]
#[UsesClass(RemoveEmptyTextElements::class)]
#[UsesClass(RemoveEnableBackground::class)]
#[UsesClass(RemoveForeignContent::class)]
#[UsesClass(RemoveHiddenElements::class)]
#[UsesClass(RemoveIneffectiveDefinitions::class)]
#[UsesClass(RemoveIneffectiveGroupAttributes::class)]
#[UsesClass(RemoveIneffectiveStrokeAndFill::class)]
#[UsesClass(RemoveIneffectiveTextAttributes::class)]
#[UsesClass(RemoveInvisibleCharacters::class)]
#[UsesClass(RemoveMetadata::class)]
#[UsesClass(RemoveNonStandardAttributes::class)]
#[UsesClass(RemoveNonStandardElements::class)]
#[UsesClass(RemoveScripts::class)]
#[UsesClass(RemoveTitles::class)]
#[UsesClass(RemoveUnsafeMarkup::class)]
#[UsesClass(RemoveUnsafeStyles::class)]
#[UsesClass(RemoveUnsafeUrls::class)]
#[UsesClass(RemoveUnusedDefinitions::class)]
#[UsesClass(RemoveUnusedIds::class)]
#[UsesClass(RemoveUnusedNamespaces::class)]
#[UsesClass(RemoveUseBombs::class)]
#[UsesClass(RemoveWidthHeightAttributes::class)]
#[UsesClass(RemoveZeroSizeShapes::class)]
#[UsesClass(RiskyRulesNotAllowedException::class)]
#[UsesClass(Rule::class)]
#[UsesClass(RuleSet::class)]
#[UsesClass(ScopeIdsAndClasses::class)]
#[UsesClass(Serializer::class)]
#[UsesClass(SortAttributes::class)]
#[UsesClass(SortDefinitions::class)]
#[UsesClass(SvgFile::class)]
#[UsesClass(SvgValidator::class)]
#[UsesTrait(AnimationTrait::class)]
#[UsesTrait(AsciiCaseTrait::class)]
#[UsesTrait(AttributeMigrationTrait::class)]
#[UsesTrait(AttributeNodeTrait::class)]
#[UsesTrait(CanonicalNameTrait::class)]
#[UsesTrait(ClassSelectorTrait::class)]
#[UsesTrait(DangerousUrlTrait::class)]
#[UsesTrait(DeclarationTrait::class)]
#[UsesTrait(DefinitionElementTrait::class)]
#[UsesTrait(ElementNameTrait::class)]
#[UsesTrait(ElementRemovalTrait::class)]
#[UsesTrait(ElementRenameTrait::class)]
#[UsesTrait(ElementTrait::class)]
#[UsesTrait(EntityReferenceTrait::class)]
#[UsesTrait(FragmentTargetTrait::class)]
#[UsesTrait(GradientLinkTrait::class)]
#[UsesTrait(IdReferenceTrait::class)]
#[UsesTrait(IdRenameTrait::class)]
#[UsesTrait(IdTrait::class)]
#[UsesTrait(IneffectiveAttributeTrait::class)]
#[UsesTrait(InheritanceTrait::class)]
#[UsesTrait(InheritedValueTrait::class)]
#[UsesTrait(NamedColorTrait::class)]
#[UsesTrait(NumberListTrait::class)]
#[UsesTrait(NumberLiteralTrait::class)]
#[UsesTrait(PathDataParserTrait::class)]
#[UsesTrait(PathDataSerializerTrait::class)]
#[UsesTrait(PathDataTokenTrait::class)]
#[UsesTrait(ScriptingTrait::class)]
#[UsesTrait(SelectorTrait::class)]
#[UsesTrait(StyleElementTrait::class)]
#[UsesTrait(StyleSheetTrait::class)]
#[UsesTrait(TextWhitespaceTrait::class)]
#[UsesTrait(TokenContextTrait::class)]
#[UsesTrait(TokenizerTrait::class)]
#[UsesTrait(TransformListTrait::class)]
#[UsesTrait(UnsafeAttributeTrait::class)]
#[UsesTrait(XlinkMigrationTrait::class)]
final class SvgOptimizerTest extends TestCase
{
    use TemporaryDirectoryTrait;

    private const string SAMPLES = __DIR__ . '/../../dev/samples/*.svg';

    private const string RULE_SET_SAMPLE_SVG = '<!DOCTYPE svg><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" width="100" height="100" viewBox="0 0 100 100" data-x="1" enable-background="new" inkscape:version="1"><!-- comment --><metadata>m</metadata><title>T</title><style type="text/css"> .a { fill : red ; } </style><style>.b{fill:blue}</style><script>alert(1)</script><foreignObject><p xmlns="http://www.w3.org/1999/xhtml">x</p></foreignObject><defs><linearGradient id="g"><stop stop-color="#ff0000"/></linearGradient><rect id="unused" width="1" height="1"/></defs><g fill="red" x="1"><rect class="a" width="10.50" height="10" style="stroke-width:1" onclick="x()"/><ellipse rx="5" ry="5" fill="rgb(0, 0, 255)"/><polygon points="0, 0, 1, 1, 2, 0"/></g><a xlink:href="javascript:alert(1)"><rect width="0" height="1"/></a><use xlink:href="#g" transform="translate(0, 0)"/><text xml:space="default"> </text><path d="M 0 0 L 10 10" aria-label="p" role="img" fill="url(#g)"/></svg>';

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function withoutRulesOnlyParsesAndSerializes(): void
    {
        self::assertSame(
            '<svg xmlns="http://www.w3.org/2000/svg"><title>T</title><!-- c --></svg>',
            (new SvgOptimizer())->optimizeString("<svg xmlns=\"http://www.w3.org/2000/svg\">\n  <title>T</title>\n  <!-- c -->\n</svg>")->getContent(),
        );
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function withRulesWithoutArgumentsSelectsNothing(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><title>T</title><!-- c --></svg>';

        self::assertSame($svg, (new SvgOptimizer())->withRules()->optimizeString($svg)->getContent());
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[DataProvider('provideWithRulesRunsThePassesOfTheRuleCases')]
    #[Test]
    public function withRulesRunsThePassesOfTheRule(Rule $rule, string $svg): void
    {
        $expected = (new Pipeline(array_map(static fn (string $passClass): PassInterface => new $passClass(), $rule->getPassClasses())))->run($svg);

        self::assertNotSame((new SvgOptimizer())->optimizeString($svg)->getContent(), $expected);
        self::assertSame($expected, (new SvgOptimizer())->withRules($rule)->withRiskyRulesAllowed()->optimizeString($svg)->getContent());
    }

    /**
     * @return iterable<string, array{Rule, string}>
     */
    public static function provideWithRulesRunsThePassesOfTheRuleCases(): iterable
    {
        yield 'minifyColors' => [Rule::MinifyColors, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" fill="rgb(255,0,0)"/></svg>'];

        yield 'convertCssClassesToAttributes' => [Rule::ConvertCssClassesToAttributes, '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}</style><rect class="a" width="1" height="1"/></svg>'];

        yield 'convertInlineStylesToAttributes' => [Rule::ConvertInlineStylesToAttributes, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" style="fill:red"/></svg>'];

        yield 'fixAttributeNames' => [Rule::FixAttributeNames, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" Fill="red"/></svg>'];

        yield 'flattenGroups' => [Rule::FlattenGroups, '<svg xmlns="http://www.w3.org/2000/svg"><g><rect width="1" height="1"/></g></svg>'];

        yield 'minifyTransforms' => [Rule::MinifyTransforms, '<svg xmlns="http://www.w3.org/2000/svg"><rect transform="translate(10, 0)" width="1" height="1"/></svg>'];

        yield 'removeAriaAttributes' => [Rule::RemoveAriaAttributes, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" aria-label="x" role="img"/></svg>'];

        yield 'removeComments' => [Rule::RemoveComments, '<svg xmlns="http://www.w3.org/2000/svg"><!-- c --><rect width="1" height="1"/></svg>'];

        yield 'removeDataAttributes' => [Rule::RemoveDataAttributes, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" data-x="1"/></svg>'];

        yield 'removeDefaultAttributes' => [Rule::RemoveDefaultAttributes, '<svg xmlns="http://www.w3.org/2000/svg" stroke-linejoin="miter"><rect width="1" height="1" stroke-linejoin="miter"/><style type="text/css">rect{fill:red}</style><text xml:space="default">a</text></svg>'];

        yield 'removeDuplicateElements' => [Rule::RemoveDuplicateElements, '<svg xmlns="http://www.w3.org/2000/svg"><defs><text x="1">a</text><text x="1">a</text></defs></svg>'];

        yield 'removeEmptyAttributes' => [Rule::RemoveEmptyAttributes, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" fill=""/></svg>'];

        yield 'removeEditorMarkup' => [Rule::RemoveEditorMarkup, '<svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect width="1" height="1" inkscape:label="x"/></svg>'];

        yield 'removeInvisibleCharacters' => [Rule::RemoveInvisibleCharacters, '<svg xmlns="http://www.w3.org/2000/svg"><g>a&#x200B;</g></svg>'];

        yield 'removeMetadata' => [Rule::RemoveMetadata, '<svg xmlns="http://www.w3.org/2000/svg"><metadata>m</metadata><rect width="1" height="1"/></svg>'];

        yield 'removeNonStandardAttributes' => [Rule::RemoveNonStandardAttributes, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" foo="bar"/></svg>'];

        yield 'removeNonStandardElements' => [Rule::RemoveNonStandardElements, '<svg xmlns="http://www.w3.org/2000/svg"><foo/><rect width="1" height="1"/></svg>'];

        yield 'minifyAttributeWhitespace' => [Rule::MinifyAttributeWhitespace, '<svg xmlns="http://www.w3.org/2000/svg"><rect width=" 1" height="1"/></svg>'];

        yield 'removeUnusedNamespaces' => [Rule::RemoveUnusedNamespaces, '<svg xmlns="http://www.w3.org/2000/svg" xmlns:foo="http://example.com/foo"><rect width="1" height="1"/></svg>'];

        yield 'removeWidthHeightAttributes' => [Rule::RemoveWidthHeightAttributes, '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10"><rect width="1" height="1"/></svg>'];

        yield 'scopeIdsAndClasses' => [Rule::ScopeIdsAndClasses, '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}</style><rect class="a" width="1" height="1"/></svg>'];

        yield 'sortAttributes' => [Rule::SortAttributes, '<svg xmlns="http://www.w3.org/2000/svg"><rect b="1" a="2"/></svg>'];

        yield 'convertEllipsesToCircles' => [Rule::ConvertEllipsesToCircles, '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="2" ry="2"/></svg>'];

        yield 'convertShapesToPaths' => [Rule::ConvertShapesToPaths, '<svg xmlns="http://www.w3.org/2000/svg"><line x1="0" y1="0" x2="5" y2="5" stroke="red"/></svg>'];

        yield 'minifyNumericValues' => [Rule::MinifyNumericValues, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1.50" height="1"/><polygon points="0, 0, 1, 1"/></svg>'];

        yield 'minifyPathData' => [Rule::MinifyPathData, '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 0 0 L 10 10"/></svg>'];

        yield 'removeIneffectiveDefinitions' => [Rule::RemoveIneffectiveDefinitions, '<svg xmlns="http://www.w3.org/2000/svg"><defs><rect width="1" height="1"/></defs><rect width="1" height="1"/></svg>'];

        yield 'convertOneStopGradientsToColors' => [Rule::ConvertOneStopGradientsToColors, '<svg xmlns="http://www.w3.org/2000/svg"><linearGradient id="g"><stop stop-color="red"/></linearGradient><rect width="1" height="1" fill="url(#g)"/></svg>'];

        yield 'removeUnusedIds' => [Rule::RemoveUnusedIds, '<svg xmlns="http://www.w3.org/2000/svg"><rect id="u" width="1" height="1"/></svg>'];

        yield 'mergeDuplicateGradients' => [Rule::MergeDuplicateGradients, '<svg xmlns="http://www.w3.org/2000/svg"><linearGradient id="a"><stop offset="0" stop-color="red"/><stop offset="1" stop-color="blue"/></linearGradient><linearGradient id="b"><stop offset="0" stop-color="red"/><stop offset="1" stop-color="blue"/></linearGradient><rect width="1" height="1" fill="url(#a)"/><rect width="1" height="1" fill="url(#b)"/></svg>'];

        yield 'mergeStyleSheets' => [Rule::MergeStyleSheets, '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><style>circle{fill:blue}</style><rect width="1" height="1"/></svg>'];

        yield 'migrateToSvg2' => [Rule::MigrateToSvg2, '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" enable-background="new 0 0 10 10"><use xlink:href="#r"/><rect id="r" width="1" height="1"/></svg>'];

        yield 'minifyStyleSheets' => [Rule::MinifyStyleSheets, '<svg xmlns="http://www.w3.org/2000/svg"><style> rect { fill : red ; } </style><rect width="1" height="1"/></svg>'];

        yield 'moveCommonAttributesToGroups' => [Rule::MoveCommonAttributesToGroups, '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red" width="1" height="1"/><circle fill="red" r="1"/></g></svg>'];

        yield 'moveGroupTransformsToChildren' => [Rule::MoveGroupTransformsToChildren, '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect width="1" height="1"/></g></svg>'];

        yield 'removeHiddenElements' => [Rule::RemoveHiddenElements, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" display="none"/><rect width="0" height="1"/><rect width="2" height="2"/></svg>'];

        yield 'removeUnusedDefinitions' => [Rule::RemoveUnusedDefinitions, '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"><stop offset="0"/></linearGradient></defs><rect width="1" height="1"/></svg>'];

        yield 'sortDefinitions' => [Rule::SortDefinitions, '<svg xmlns="http://www.w3.org/2000/svg"><defs><rect id="a" width="1" height="1"/><circle id="b" r="1"/><rect id="c" width="1" height="1"/></defs><use href="#a"/><use href="#b"/><use href="#c"/></svg>'];

        yield 'removeScripts' => [Rule::RemoveScripts, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><rect width="1" height="1"/></svg>'];

        yield 'removeUnsafeUrls' => [Rule::RemoveUnsafeUrls, '<svg xmlns="http://www.w3.org/2000/svg"><a href="javascript:alert(1)"/><rect width="1" height="1"/></svg>'];

        yield 'removeForeignContent' => [Rule::RemoveForeignContent, '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject/><rect width="1" height="1"/></svg>'];

        yield 'removeUnsafeStyles' => [Rule::RemoveUnsafeStyles, '<svg xmlns="http://www.w3.org/2000/svg"><style>@import url(a.css);rect{fill:red}</style><rect width="1" height="1"/></svg>'];

        yield 'removeUseBombs' => [Rule::RemoveUseBombs, '<svg xmlns="http://www.w3.org/2000/svg"><g id="a"><use href="#a"/></g></svg>'];

        yield 'removeUnsafeMarkup' => [Rule::RemoveUnsafeMarkup, '<svg xmlns="http://www.w3.org/2000/svg"><?php x ?><rect width="1" height="1"/></svg>'];

        yield 'removeTitles' => [Rule::RemoveTitles, '<svg xmlns="http://www.w3.org/2000/svg"><title>t</title><rect width="1" height="1"/></svg>'];

        yield 'removeDescriptions' => [Rule::RemoveDescriptions, '<svg xmlns="http://www.w3.org/2000/svg"><desc>d</desc><rect width="1" height="1"/></svg>'];

        yield 'removeEmptyElements' => [Rule::RemoveEmptyElements, '<svg xmlns="http://www.w3.org/2000/svg"><g/><text/><rect width="1" height="1"/><defs/></svg>'];

        yield 'removeIneffectiveAttributes' => [Rule::RemoveIneffectiveAttributes, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" stroke="none" stroke-width="2"/><g x="1"><rect width="1" height="1"/></g><rect width="1" height="1" font-size="12"/></svg>'];
    }

    #[Test]
    public function everyRuleIsTested(): void
    {
        self::assertEqualsCanonicalizing(
            Rule::cases(),
            array_map(static fn (array $case): Rule => $case[0], iterator_to_array(self::provideWithRulesRunsThePassesOfTheRuleCases(), false)),
        );
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function runsThePassesInExecutionOrderWhateverTheOrderOfTheRules(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" stroke-width="1"><rect style="stroke-width:1"/></svg>';

        self::assertSame(
            '<svg xmlns="http://www.w3.org/2000/svg" stroke-width="1"><rect/></svg>',
            (new SvgOptimizer())->withRules(Rule::RemoveDefaultAttributes)->withRules(Rule::ConvertInlineStylesToAttributes)->optimizeString($svg)->getContent(),
        );
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function selectingARuleTwiceRunsItOnce(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(1 1)"><g transform="translate(2 2)"><rect width="1" height="1"/></g></g></svg>';
        $rules = [Rule::FlattenGroups, Rule::MinifyTransforms, Rule::ConvertShapesToPaths];

        self::assertSame(
            (new SvgOptimizer())->withRules(...$rules)->optimizeString($svg)->getContent(),
            (new SvgOptimizer())->withRules(...$rules)->withRules(...$rules)->withRuleSets(RuleSet::Structure)->withoutRules(Rule::MergeStyleSheets, Rule::MoveGroupTransformsToChildren, Rule::MoveCommonAttributesToGroups, Rule::MergeDuplicateGradients)->optimizeString($svg)->getContent(),
        );
    }

    /**
     * @param list<Rule> $rules
     *
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[DataProvider('provideLaterPassesAcceptWhatEarlierMinifiersWriteCases')]
    #[Test]
    public function laterPassesAcceptWhatEarlierMinifiersWrite(array $rules, string $content, string $expected): void
    {
        self::assertSame($expected, (new SvgOptimizer())->withRules(...$rules)->optimizeString($content)->getContent());
    }

    /**
     * @return iterable<string, array{list<Rule>, string, string}>
     */
    public static function provideLaterPassesAcceptWhatEarlierMinifiersWriteCases(): iterable
    {
        yield 'shapes become paths after their points are minified' => [
            [Rule::MinifyNumericValues, Rule::ConvertShapesToPaths],
            '<svg xmlns="http://www.w3.org/2000/svg"><polyline points="0 0 10 -10 0.5 0.5" stroke="red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path stroke="red" d="M0 0 10-10 .5.5"/></svg>',
        ];

        yield 'groups with a minified transform are flattened' => [
            [Rule::FlattenGroups],
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(10-10)"><rect width="1" height="1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" transform="translate(10-10)"/></svg>',
        ];

        yield 'a minified group transform moves to the children' => [
            [Rule::MoveGroupTransformsToChildren],
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(.5.5)"><rect width="1" height="1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect width="1" height="1" transform="translate(.5.5)"/></g></svg>',
        ];
    }

    /**
     * @throws InvalidSvgException
     */
    #[Test]
    public function aRiskyRuleSelectedWithWithRulesNeedsRiskyRulesToBeAllowed(): void
    {
        try {
            (new SvgOptimizer())->withRules(Rule::RemoveComments, Rule::RemoveUnusedIds, Rule::FixAttributeNames, Rule::RemoveUnusedIds)->optimizeString('<svg xmlns="http://www.w3.org/2000/svg"/>');
            self::fail('Expected RiskyRulesNotAllowedException was not thrown.');
        } catch (RiskyRulesNotAllowedException $riskyRulesNotAllowedException) {
            self::assertSame([Rule::RemoveUnusedIds, Rule::FixAttributeNames], $riskyRulesNotAllowedException->getRules());
        }
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function withRiskyRulesAllowedFalseDisallowsRiskyRulesAgain(): void
    {
        $this->expectException(RiskyRulesNotAllowedException::class);

        (new SvgOptimizer())->withRiskyRulesAllowed()->withRules(Rule::RemoveWidthHeightAttributes)->withRiskyRulesAllowed(false)->optimizeString('<svg xmlns="http://www.w3.org/2000/svg"/>');
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function aRiskyRuleRunsOnceRiskyRulesAreAllowed(): void
    {
        self::assertSame(
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1"/></svg>',
            (new SvgOptimizer())->withRules(Rule::RemoveComments, Rule::RemoveUnusedIds)->withRiskyRulesAllowed()->optimizeString('<svg xmlns="http://www.w3.org/2000/svg"><rect id="a" width="1"/></svg>')->getContent(),
        );
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function withoutRulesWinsOverWithRules(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><rect id="a" width="1"/><!-- c --></svg>';

        self::assertSame(
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="a" width="1"/></svg>',
            (new SvgOptimizer())->withoutRules(Rule::RemoveUnusedIds)->withRules(Rule::RemoveComments, Rule::RemoveUnusedIds)->optimizeString($svg)->getContent(),
        );
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function withoutRulesWinsOverRuleSets(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><title>T</title><!-- c --><rect width="1.50" height="1"/></svg>';

        self::assertSame(
            '<svg xmlns="http://www.w3.org/2000/svg"><title>T</title><!-- c --><rect width="1.5" height="1"/></svg>',
            (new SvgOptimizer())->withRuleSets(RuleSet::Cleanup, RuleSet::Minification)->withoutRules(Rule::RemoveComments)->optimizeString($svg)->getContent(),
        );
    }

    /**
     * @param list<RuleSet> $ruleSets
     *
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[DataProvider('provideWithRuleSetsAppliesExactlyTheRulesOfTheSetsCases')]
    #[Test]
    public function withRuleSetsAppliesExactlyTheRulesOfTheSets(array $ruleSets, bool $allowRisky): void
    {
        $rules = array_values(array_filter(
            Rule::cases(),
            static fn (Rule $rule): bool => \in_array($rule->getRuleSet(), $ruleSets, true) && ($allowRisky || !$rule->isRisky()),
        ));

        foreach ($this->ruleSetCorpus() as $svg) {
            $expected = (new SvgOptimizer())->withRules(...$rules)->withRiskyRulesAllowed($allowRisky)->optimizeString($svg)->getContent();

            self::assertSame($expected, (new SvgOptimizer())->withRiskyRulesAllowed($allowRisky)->withRuleSets(...$ruleSets)->optimizeString($svg)->getContent());
            self::assertSame($expected, (new SvgOptimizer())->withRuleSets(...$ruleSets)->withRiskyRulesAllowed($allowRisky)->optimizeString($svg)->getContent());
        }
    }

    /**
     * @return iterable<string, array{list<RuleSet>, bool}>
     */
    public static function provideWithRuleSetsAppliesExactlyTheRulesOfTheSetsCases(): iterable
    {
        foreach (RuleSet::cases() as $ruleSet) {
            yield $ruleSet->value => [[$ruleSet], false];

            yield $ruleSet->value . ', risky' => [[$ruleSet], true];
        }

        yield 'security and minification' => [[RuleSet::Security, RuleSet::Minification], false];

        yield 'cleanup and structure, risky' => [[RuleSet::Cleanup, RuleSet::Structure], true];

        yield 'no set' => [[], true];
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function withRuleSetsChangesTheSampleForEverySet(): void
    {
        $withoutRules = (new SvgOptimizer())->optimizeString(self::RULE_SET_SAMPLE_SVG)->getContent();

        foreach (RuleSet::cases() as $ruleSet) {
            self::assertNotSame($withoutRules, (new SvgOptimizer())->withRuleSets($ruleSet)->optimizeString(self::RULE_SET_SAMPLE_SVG)->getContent(), $ruleSet->value);
        }
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function withRuleSetsSkipsRiskyRulesUnlessRiskyRulesAreAllowed(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" data-x="1"><!-- c --><rect width="1" height="1"/></svg>';

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg" data-x="1"><rect width="1" height="1"/></svg>', (new SvgOptimizer())->withRuleSets(RuleSet::Cleanup)->optimizeString($svg)->getContent());
        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1"/></svg>', (new SvgOptimizer())->withRuleSets(RuleSet::Cleanup)->withRiskyRulesAllowed()->optimizeString($svg)->getContent());
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function withRuleSetsAddsToTheRulesOfWithRules(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><title>T</title><!-- c --><rect width="1.50" height="1"/></svg>';

        self::assertSame(
            '<svg xmlns="http://www.w3.org/2000/svg"><!-- c --><rect width="1.5" height="1"/></svg>',
            (new SvgOptimizer())->withRules(Rule::RemoveTitles)->withRuleSets(RuleSet::Minification)->optimizeString($svg)->getContent(),
        );
        self::assertSame(
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1.5" height="1"/></svg>',
            (new SvgOptimizer())->withRuleSets(RuleSet::Minification)->withRules(Rule::RemoveTitles)->withRuleSets(RuleSet::Cleanup)->optimizeString($svg)->getContent(),
        );
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function withRuleSetsDoesNotAllowARiskyRuleSelectedWithWithRules(): void
    {
        $this->expectException(RiskyRulesNotAllowedException::class);

        (new SvgOptimizer())->withRuleSets(RuleSet::Cleanup)->withRules(Rule::RemoveDataAttributes)->optimizeString('<svg xmlns="http://www.w3.org/2000/svg" data-x="1"/>');
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[DataProvider('provideWithDefaultRulesSelectsTheDefaultSetsCases')]
    #[Test]
    public function withDefaultRulesSelectsTheDefaultSets(bool $allowRisky): void
    {
        foreach ($this->ruleSetCorpus() as $svg) {
            self::assertSame(
                (new SvgOptimizer())->withRuleSets(...RuleSet::defaults())->withRiskyRulesAllowed($allowRisky)->optimizeString($svg)->getContent(),
                (new SvgOptimizer())->withDefaultRules()->withRiskyRulesAllowed($allowRisky)->optimizeString($svg)->getContent(),
            );
        }
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function provideWithDefaultRulesSelectsTheDefaultSetsCases(): iterable
    {
        yield 'without risky rules' => [false];

        yield 'with risky rules' => [true];
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function withDefaultRulesAddsToTheOtherSelections(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><title>T</title><script>x</script><!-- c --></svg>';

        self::assertSame(
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            (new SvgOptimizer())->withRules(Rule::RemoveTitles)->withDefaultRules()->withRuleSets(RuleSet::Security)->optimizeString($svg)->getContent(),
        );
        self::assertSame(
            '<svg xmlns="http://www.w3.org/2000/svg"><title>T</title><script>x</script></svg>',
            (new SvgOptimizer())->withDefaultRules()->optimizeString($svg)->getContent(),
        );
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function everyWithMethodReturnsANewInstanceAndLeavesTheOriginalUnchanged(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><title>T</title><!-- c --><rect id="a" width="1"/></svg>';
        $svgOptimizer = new SvgOptimizer();
        $derived = [
            $svgOptimizer->withRules(Rule::RemoveComments),
            $svgOptimizer->withoutRules(Rule::RemoveComments),
            $svgOptimizer->withRuleSets(RuleSet::Cleanup),
            $svgOptimizer->withDefaultRules(),
            $svgOptimizer->withRiskyRulesAllowed(),
        ];

        foreach ($derived as $other) {
            self::assertNotSame($svgOptimizer, $other);
        }

        self::assertSame($svg, $svgOptimizer->optimizeString($svg)->getContent());
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function measuresTheInputAndTheOutputInBytes(): void
    {
        $metrics = (new SvgOptimizer())->withRules(Rule::RemoveComments)->optimizeString("<svg xmlns=\"http://www.w3.org/2000/svg\"><!-- \u{e9} --></svg>")->getMetrics();

        self::assertSame(57, $metrics->getOriginalSizeInBytes());
        self::assertSame(41, $metrics->getOptimizedSizeInBytes());
        self::assertSame(16, $metrics->getSavedSizeInBytes());
        self::assertEqualsWithDelta(16 / 57 * 100, $metrics->getSavedPercentage(), \PHP_FLOAT_EPSILON);
        self::assertGreaterThan(0.0, $metrics->getDurationInSeconds());
        self::assertLessThan(60.0, $metrics->getDurationInSeconds());
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function reportsNegativeSavingsWhenTheOutputIsLarger(): void
    {
        $optimizationResult = (new SvgOptimizer())->optimizeString('<svg xmlns="http://www.w3.org/2000/svg" a=\'"\'/>');
        $metrics = $optimizationResult->getMetrics();

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg" a="&quot;"/>', $optimizationResult->getContent());
        self::assertSame(-5, $metrics->getSavedSizeInBytes());
        self::assertFalse($metrics->hasSavings());
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function countsBytesNotCharacters(): void
    {
        $svg = "<svg xmlns=\"http://www.w3.org/2000/svg\"><text>\u{1F600}é</text></svg>";
        $optimizationResult = (new SvgOptimizer())->optimizeString("\xEF\xBB\xBF" . $svg);

        self::assertSame($svg, $optimizationResult->getContent());
        self::assertSame(68, $optimizationResult->getMetrics()->getOriginalSizeInBytes());
        self::assertSame(65, $optimizationResult->getMetrics()->getOptimizedSizeInBytes());
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws InvalidSvgException
     */
    #[Test]
    public function optimizeStringRejectsInputThatIsNotSvg(): void
    {
        $this->expectException(InvalidSvgException::class);

        (new SvgOptimizer())->optimizeString('<html xmlns="http://www.w3.org/1999/xhtml"/>');
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     * @throws IOException
     */
    #[Test]
    public function optimizeFileMeasuresTheInputAfterTheResultIsSavedOverIt(): void
    {
        $path = $this->createTemporaryDirectory() . '/icon.svg';
        file_put_contents($path, '<svg xmlns="http://www.w3.org/2000/svg"><!-- comment --></svg>');

        $optimizationResult = (new SvgOptimizer())->withRules(Rule::RemoveComments)->optimizeFile($path);
        $optimizationResult->saveToFile($path);

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"/>', file_get_contents($path));
        self::assertSame(62, $optimizationResult->getMetrics()->getOriginalSizeInBytes());
        self::assertSame(41, $optimizationResult->getMetrics()->getOptimizedSizeInBytes());
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     * @throws IOException
     */
    #[Test]
    public function optimizeFileThrowsWhenTheFileDoesNotExist(): void
    {
        $this->expectException(FileNotFoundException::class);

        (new SvgOptimizer())->optimizeFile('/nonexistent/path.svg');
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[DataProvider('provideOptimizeStringDoesNotEmitPhpWarningsCases')]
    #[Test]
    public function optimizeStringDoesNotEmitPhpWarnings(string $svg, bool $allowRisky): void
    {
        $warnings = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$warnings): bool {
            $warnings[] = $errstr;

            return true;
        });

        try {
            $this->allRules($allowRisky)->optimizeString($svg);
        } finally {
            restore_error_handler();
        }

        self::assertSame([], $warnings);
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function provideOptimizeStringDoesNotEmitPhpWarningsCases(): iterable
    {
        yield 'undeclared prefix' => ['<svg><use xlink:href="#x" clip-path="a"/></svg>', false];

        yield 'undeclared prefix, risky' => ['<svg><use xlink:href="#x" clip-path="a"/></svg>', true];

        yield 'undeclared prefix on removed element' => ['<svg xmlns="http://www.w3.org/2000/svg"><text><tref xlink:href=""/></text></svg>', false];

        yield 'reserved processing instruction target' => ["<svg xmlns=\"http://www.w3.org/2000/svg\">\n<?xml-foo bar?>\n<rect width=\"1\" height=\"1\"/></svg>", false];
    }

    /**
     * @throws InvalidSvgException
     * @throws RiskyRulesNotAllowedException
     */
    #[DataProvider('provideOptimizingTwiceGivesTheSameResultCases')]
    #[Test]
    public function optimizingTwiceGivesTheSameResult(string $svg, bool $allowRisky): void
    {
        $once = $this->allRules($allowRisky)->optimizeString($svg)->getContent();

        self::assertSame($once, $this->allRules($allowRisky)->optimizeString($once)->getContent());
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function provideOptimizingTwiceGivesTheSameResultCases(): iterable
    {
        $files = glob(self::SAMPLES);
        self::assertIsArray($files);
        self::assertNotSame([], $files);

        foreach ($files as $file) {
            yield basename($file) => [(string) file_get_contents($file), false];

            yield basename($file) . ', risky' => [(string) file_get_contents($file), true];
        }

        yield 'a dropped rule that blocked a class conversion' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50"><style>.a{stroke:red}[stroke]+path{stroke:purple}</style><path class="a" d="M10 10h20" stroke="red"/><path d="M10 20h20"/></svg>', true];

        yield 'a dropped rule that selected deprecated attributes' => ['<svg version="1.1" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><style>svg[version="1.1"]{fill:blue}rect[clip]{fill:green}</style><rect x="10" y="10" width="80" height="80" clip="1"/></svg>', true];

        yield 'a mask that only an empty group references' => ['<svg xmlns="http://www.w3.org/2000/svg"><mask id="m"><path/></mask><path d="M0 0h1v1H0z"/><g mask="url(#m)"><g transform="translate(1 1)"/></g></svg>', true];

        yield 'ids inside a group whose class no style sheet hides' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><style>.h{display:none}.f{fill:red}</style><rect class="h" id="r" width="10" height="10"/><g class="f"><path id="p" d="M0 0h5v5H0z"/></g></svg>', true];

        yield 'a group attribute that the style of a child repeats' => ['<svg xmlns="http://www.w3.org/2000/svg"><g><g><g style="fill:#000095"><path style="font-size:20px;fill:#000095" d="M0 0h5v5H0z"/></g></g></g></svg>', true];
    }

    private function allRules(bool $allowRisky): SvgOptimizer
    {
        $svgOptimizer = (new SvgOptimizer())->withRules(...Rule::cases())->withRiskyRulesAllowed($allowRisky);

        return $allowRisky ? $svgOptimizer : $svgOptimizer->withoutRules(...array_filter(Rule::cases(), static fn (Rule $rule): bool => $rule->isRisky()));
    }

    /**
     * @return list<string> SVG documents that exercise the rules of every set
     */
    private function ruleSetCorpus(): array
    {
        $files = glob(self::SAMPLES);
        self::assertIsArray($files);

        return [self::RULE_SET_SAMPLE_SVG, ...array_map(static fn (string $file): string => (string) file_get_contents($file), $files)];
    }
}
