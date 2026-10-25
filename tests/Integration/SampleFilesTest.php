<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Integration;

use MathiasReker\PhpSvgOptimizer\Exception\InvalidSvgException;
use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
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
use MathiasReker\PhpSvgOptimizer\Pass\Pipeline;
use MathiasReker\PhpSvgOptimizer\Pass\PrefixIdsAndClasses;
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
use MathiasReker\PhpSvgOptimizer\Pass\SortAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\SortDefinitions;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\ClassSelectorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\DeclarationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\TokenContextTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\TokenizerTrait;
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
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
use MathiasReker\PhpSvgOptimizer\Svg\ElementAttributeMap;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;
use MathiasReker\PhpSvgOptimizer\SvgOptimizer;
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
#[UsesClass(PrefixIdsAndClasses::class)]
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
#[UsesClass(Rule::class)]
#[UsesClass(Serializer::class)]
#[UsesClass(SortAttributes::class)]
#[UsesClass(SortDefinitions::class)]
#[UsesClass(SvgValidator::class)]
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
#[UsesTrait(IneffectiveAttributeTrait::class)]
#[UsesTrait(InheritanceTrait::class)]
#[UsesTrait(InheritedValueTrait::class)]
#[UsesTrait(NumberListTrait::class)]
#[UsesTrait(NumberLiteralTrait::class)]
#[UsesTrait(PathDataParserTrait::class)]
#[UsesTrait(PathDataSerializerTrait::class)]
#[UsesTrait(PathDataTokenTrait::class)]
#[UsesTrait(ScriptingTrait::class)]
#[UsesTrait(StyleElementTrait::class)]
#[UsesTrait(TextWhitespaceTrait::class)]
#[UsesTrait(TokenContextTrait::class)]
#[UsesTrait(TokenizerTrait::class)]
#[UsesTrait(TransformListTrait::class)]
#[UsesTrait(UnsafeAttributeTrait::class)]
#[UsesTrait(XlinkMigrationTrait::class)]
final class SampleFilesTest extends TestCase
{
    private const string SVG_NAMESPACE = 'http://www.w3.org/2000/svg';

    private const string XLINK_NAMESPACE = 'http://www.w3.org/1999/xlink';

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws InvalidSvgException
     */
    #[DataProvider('provideSampleFileCases')]
    #[Test]
    public function referencesStillPointToAnElement(string $file, bool $allowRisky): void
    {
        $domDocument = $this->optimize($file, $allowRisky);
        $ids = array_map(static fn (\DOMAttr $domAttr): string => $domAttr->value, $this->attributes($domDocument, '//@id'));

        foreach ($this->attributes($domDocument, '//@*') as $domAttr) {
            foreach ($this->references($domAttr) as $reference) {
                self::assertContains($reference, $ids, $domAttr->nodeName . '="' . $domAttr->value . '"');
            }
        }
    }

    /**
     * @return list<\DOMAttr>
     */
    private function attributes(\DOMDocument $domDocument, string $expression): array
    {
        $domNodeList = (new \DOMXPath($domDocument))->query($expression);
        self::assertInstanceOf(\DOMNodeList::class, $domNodeList);

        return array_values(array_filter(
            iterator_to_array($domNodeList, false),
            static fn (\DOMNameSpaceNode|\DOMNode $domNode): bool => $domNode instanceof \DOMAttr,
        ));
    }

    /**
     * @return list<string>
     */
    private function references(\DOMAttr $domAttr): array
    {
        $isHref = 'href' === $domAttr->localName && \in_array($domAttr->namespaceURI, [null, self::XLINK_NAMESPACE], true);

        if ($isHref && str_starts_with($domAttr->value, '#')) {
            return [mb_substr($domAttr->value, 1)];
        }

        preg_match_all('/url\(\s*[\'"]?#([^\'")\s]+)/', $domAttr->value, $matches);

        return $matches[1];
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws InvalidSvgException
     */
    #[DataProvider('provideSampleFileCases')]
    #[Test]
    public function drawingElementsAreKept(string $file, bool $allowRisky): void
    {
        $content = file_get_contents($file);
        self::assertIsString($content);
        self::assertNotSame('', $content);

        $domDocument = new \DOMDocument();
        $domDocument->loadXML($content, \LIBXML_NOENT | \LIBXML_NONET);

        self::assertSame(
            $this->countDrawingElements($domDocument),
            $this->countDrawingElements($this->optimize($file, $allowRisky)),
        );
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function provideSampleFileCases(): iterable
    {
        $files = glob(__DIR__ . '/../../dev/samples/*.svg');
        self::assertIsArray($files);
        self::assertNotSame([], $files);

        foreach ($files as $file) {
            yield basename($file) => [$file, false];

            yield basename($file) . ', risky' => [$file, true];
        }
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws InvalidSvgException
     */
    private function optimize(string $file, bool $allowRisky): \DOMDocument
    {
        $content = file_get_contents($file);
        self::assertIsString($content);

        $optimized = (new SvgOptimizer())
            ->withRules(...array_values(array_filter(Rule::cases(), static fn (Rule $rule): bool => $allowRisky || !$rule->isRisky())))
            ->withRiskyRulesAllowed($allowRisky)
            ->optimizeString($content)
            ->getContent();
        self::assertNotSame('', $optimized);

        $domDocument = new \DOMDocument();
        self::assertTrue($domDocument->loadXML($optimized));
        self::assertSame(self::SVG_NAMESPACE, $domDocument->documentElement?->namespaceURI);

        return $domDocument;
    }

    private function countDrawingElements(\DOMDocument $domDocument): int
    {
        $domXPath = new \DOMXPath($domDocument);
        $domXPath->registerNamespace('svg', self::SVG_NAMESPACE);

        $count = 0;
        foreach (['text', 'use', 'image'] as $tag) {
            $count += $domXPath->query('//svg:' . $tag)->length ?? 0;
        }

        return $count;
    }
}
