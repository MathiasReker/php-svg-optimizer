<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Facade;

use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Service\Facade\SvgOptimizerFacade;
use MathiasReker\PhpSvgOptimizer\Service\Optimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Processor\Trait\TextWhitespaceTrait;
use MathiasReker\PhpSvgOptimizer\Service\Provider\AbstractProvider;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertColorsToHex;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertCssClassesToAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertEllipseToCircle;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertInlineStylesToAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertOneStopGradients;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertShapesToPath;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgElementAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\FixAttributeNames;
use MathiasReker\PhpSvgOptimizer\Service\Rule\FlattenGroups;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MergeDuplicateGradients;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MergeStyles;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MigrateToSvg2;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyListValues;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyNumericValues;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyPathData;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyStyles;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyTransformations;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MoveCommonAttributesToGroup;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MoveGroupAttributesToElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveAriaAndRole;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveComments;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDataAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDefaultAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDeprecatedAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDescriptions;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDuplicateElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyContainers;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyTextElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEnableBackgroundAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveForeignContent;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveHiddenElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveInkscapeFootprints;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveInvisibleCharacters;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveMetadata;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveNonInheritableGroupAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveNonStandardAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveNonStandardTags;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveScripts;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveStyleTypeAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveTitles;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnnecessaryWhitespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnsafeMarkup;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnsafeUrls;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedDefinitions;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedIds;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedNamespaces;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedTextAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUseBombs;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUselessDefs;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUselessStrokeAndFill;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveWidthHeightAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveXmlSpaceDefault;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveZeroSizeShapes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\SanitizeStyleSheets;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ScopeSvgStyles;
use MathiasReker\PhpSvgOptimizer\Service\Rule\SortAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\SortDefsChildren;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\AttributeNodeTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\CanonicalNameTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ClassAttributeTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\CssDeclarationTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\CssStyleElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\DangerousAttributeValueTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\DefinitionElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\DocumentScriptTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementListTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementRemovalTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\EmptyElementRemovalTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\EntityReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\FragmentTargetTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\GradientLinkTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\IdReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\IdRenameTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\InheritanceBoundaryTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\NumberFormatterTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\NumberListTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ParentValueTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\PathDataParserTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\PathDataSerializerTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\PathDataTokenTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\PresentationAttributeCleanupTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\RemoveElementsByTagNameTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\RenameElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\StyleSheetContextTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\StyleSheetTokenTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\Svg2AttributeMigrationTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\Svg2ElementMigrationTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\Svg2TinyMigrationTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\Svg2XlinkMigrationTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\SvgElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\TagMatcherTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\TransformListTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\UnsafeAttributeTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\UseBombTrait;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use MathiasReker\PhpSvgOptimizer\Type\Rule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SvgOptimizerFacade::class)]
#[UsesClass(AbstractProvider::class)]
#[UsesClass(ConvertColorsToHex::class)]
#[UsesClass(ConvertCssClassesToAttributes::class)]
#[UsesClass(ConvertEllipseToCircle::class)]
#[UsesClass(ConvertInlineStylesToAttributes::class)]
#[UsesClass(ConvertOneStopGradients::class)]
#[UsesClass(ConvertShapesToPath::class)]
#[UsesClass(DomDocumentWrapper::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(FixAttributeNames::class)]
#[UsesClass(FlattenGroups::class)]
#[UsesClass(MergeDuplicateGradients::class)]
#[UsesClass(MergeStyles::class)]
#[UsesClass(MigrateToSvg2::class)]
#[UsesClass(MinifyListValues::class)]
#[UsesClass(MinifyNumericValues::class)]
#[UsesClass(MinifyPathData::class)]
#[UsesClass(MinifyStyles::class)]
#[UsesClass(MinifyTransformations::class)]
#[UsesClass(MoveCommonAttributesToGroup::class)]
#[UsesClass(MoveGroupAttributesToElements::class)]
#[UsesClass(RemoveAriaAndRole::class)]
#[UsesClass(RemoveComments::class)]
#[UsesClass(RemoveDataAttributes::class)]
#[UsesClass(RemoveDefaultAttributes::class)]
#[UsesClass(RemoveDeprecatedAttributes::class)]
#[UsesClass(RemoveDescriptions::class)]
#[UsesClass(RemoveDuplicateElements::class)]
#[UsesClass(RemoveEmptyAttributes::class)]
#[UsesClass(RemoveEmptyContainers::class)]
#[UsesClass(RemoveEmptyTextElements::class)]
#[UsesClass(RemoveEnableBackgroundAttribute::class)]
#[UsesClass(RemoveForeignContent::class)]
#[UsesClass(RemoveHiddenElements::class)]
#[UsesClass(RemoveInkscapeFootprints::class)]
#[UsesClass(RemoveInvisibleCharacters::class)]
#[UsesClass(RemoveMetadata::class)]
#[UsesClass(RemoveNonInheritableGroupAttributes::class)]
#[UsesClass(RemoveNonStandardAttributes::class)]
#[UsesClass(RemoveNonStandardTags::class)]
#[UsesClass(RemoveScripts::class)]
#[UsesClass(RemoveStyleTypeAttributes::class)]
#[UsesClass(RemoveTitles::class)]
#[UsesClass(RemoveUnnecessaryWhitespace::class)]
#[UsesClass(RemoveUnsafeMarkup::class)]
#[UsesClass(RemoveUnsafeUrls::class)]
#[UsesClass(RemoveUnusedDefinitions::class)]
#[UsesClass(RemoveUnusedIds::class)]
#[UsesClass(RemoveUnusedNamespaces::class)]
#[UsesClass(RemoveUnusedTextAttributes::class)]
#[UsesClass(RemoveUseBombs::class)]
#[UsesClass(RemoveUselessDefs::class)]
#[UsesClass(RemoveUselessStrokeAndFill::class)]
#[UsesClass(RemoveWidthHeightAttributes::class)]
#[UsesClass(RemoveXmlSpaceDefault::class)]
#[UsesClass(RemoveZeroSizeShapes::class)]
#[UsesClass(Rule::class)]
#[UsesClass(SanitizeStyleSheets::class)]
#[UsesClass(ScopeSvgStyles::class)]
#[UsesClass(SortAttributes::class)]
#[UsesClass(SortDefsChildren::class)]
#[UsesClass(StringProvider::class)]
#[UsesClass(SvgAttribute::class)]
#[UsesClass(SvgElementAttributes::class)]
#[UsesClass(SvgNamespace::class)]
#[UsesClass(SvgOptimizer::class)]
#[UsesClass(SvgTag::class)]
#[UsesClass(SvgValidator::class)]
#[UsesTrait(AsciiCaseTrait::class)]
#[UsesTrait(AttributeNodeTrait::class)]
#[UsesTrait(CanonicalNameTrait::class)]
#[UsesTrait(ClassAttributeTrait::class)]
#[UsesTrait(CssDeclarationTrait::class)]
#[UsesTrait(CssStyleElementTrait::class)]
#[UsesTrait(DangerousAttributeValueTrait::class)]
#[UsesTrait(DefinitionElementTrait::class)]
#[UsesTrait(DocumentScriptTrait::class)]
#[UsesTrait(ElementListTrait::class)]
#[UsesTrait(ElementRemovalTrait::class)]
#[UsesTrait(ElementTrait::class)]
#[UsesTrait(EmptyElementRemovalTrait::class)]
#[UsesTrait(EntityReferenceTrait::class)]
#[UsesTrait(FragmentTargetTrait::class)]
#[UsesTrait(GradientLinkTrait::class)]
#[UsesTrait(IdReferenceTrait::class)]
#[UsesTrait(IdRenameTrait::class)]
#[UsesTrait(InheritanceBoundaryTrait::class)]
#[UsesTrait(NumberFormatterTrait::class)]
#[UsesTrait(NumberListTrait::class)]
#[UsesTrait(ParentValueTrait::class)]
#[UsesTrait(PathDataParserTrait::class)]
#[UsesTrait(PathDataSerializerTrait::class)]
#[UsesTrait(PathDataTokenTrait::class)]
#[UsesTrait(PresentationAttributeCleanupTrait::class)]
#[UsesTrait(RemoveElementsByTagNameTrait::class)]
#[UsesTrait(RenameElementTrait::class)]
#[UsesTrait(StyleSheetContextTrait::class)]
#[UsesTrait(StyleSheetTokenTrait::class)]
#[UsesTrait(Svg2AttributeMigrationTrait::class)]
#[UsesTrait(Svg2ElementMigrationTrait::class)]
#[UsesTrait(Svg2TinyMigrationTrait::class)]
#[UsesTrait(Svg2XlinkMigrationTrait::class)]
#[UsesTrait(SvgElementTrait::class)]
#[UsesTrait(TagMatcherTrait::class)]
#[UsesTrait(TextWhitespaceTrait::class)]
#[UsesTrait(TransformListTrait::class)]
#[UsesTrait(UnsafeAttributeTrait::class)]
#[UsesTrait(UseBombTrait::class)]
final class SampleFilesTest extends TestCase
{
    private const string SVG_NAMESPACE = 'http://www.w3.org/2000/svg';

    private const string XLINK_NAMESPACE = 'http://www.w3.org/1999/xlink';

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
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
     * @throws XmlProcessingException
     * @throws \LogicException
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
        $files = glob(__DIR__ . '/../../../../dev/resources/svg/*.svg');
        self::assertIsArray($files);
        self::assertNotSame([], $files);

        foreach ($files as $file) {
            yield basename($file) => [$file, false];

            yield basename($file) . ', risky' => [$file, true];
        }
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    private function optimize(string $file, bool $allowRisky): \DOMDocument
    {
        $content = file_get_contents($file);
        self::assertIsString($content);

        $optimized = SvgOptimizerFacade::fromString($content)->allowRisky($allowRisky)->withAllRules()->optimize()->getContent();
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
