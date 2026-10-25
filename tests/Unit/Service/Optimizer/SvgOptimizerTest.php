<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Optimizer;

use MathiasReker\PhpSvgOptimizer\Contract\Service\Provider\SvgProviderInterface;
use MathiasReker\PhpSvgOptimizer\Exception\IOException;
use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Exception\SvgValidationException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Pass\PassInterface;
use MathiasReker\PhpSvgOptimizer\Pass\RevertIfLargerPassInterface;
use MathiasReker\PhpSvgOptimizer\Service\Optimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
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
use MathiasReker\PhpSvgOptimizer\Type\RuleSet;
use MathiasReker\PhpSvgOptimizer\ValueObject\Metrics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

/**
 * @no-named-arguments
 *
 * @internal
 */
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(Metrics::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(Rule::class)]
#[CoversClass(RuleSet::class)]
#[UsesClass(AbstractProvider::class)]
#[UsesClass(ConvertColorsToHex::class)]
#[UsesClass(ConvertCssClassesToAttributes::class)]
#[UsesClass(ConvertEllipseToCircle::class)]
#[UsesClass(ConvertInlineStylesToAttributes::class)]
#[UsesClass(ConvertOneStopGradients::class)]
#[UsesClass(ConvertShapesToPath::class)]
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
#[UsesClass(SanitizeStyleSheets::class)]
#[UsesClass(ScopeSvgStyles::class)]
#[UsesClass(SortAttributes::class)]
#[UsesClass(SortDefsChildren::class)]
#[UsesClass(StringProvider::class)]
#[UsesClass(SvgAttribute::class)]
#[UsesClass(SvgElementAttributes::class)]
#[UsesClass(SvgNamespace::class)]
#[UsesClass(SvgTag::class)]
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
#[UsesTrait(TransformListTrait::class)]
#[UsesTrait(UnsafeAttributeTrait::class)]
#[UsesTrait(UseBombTrait::class)]
final class SvgOptimizerTest extends TestCase
{
    private SvgProviderInterface $svgProvider;

    #[Test]
    public function addPassAddsThePass(): void
    {
        $svgOptimizer = new SvgOptimizer($this->svgProvider);

        self::assertCount(0, $svgOptimizer->getPasses());

        $rule = new class implements PassInterface {
            #[\Override]
            public function apply(\DOMDocument $domDocument): void
            {
            }
        };

        $svgOptimizer->addPass($rule);
        self::assertCount(1, $svgOptimizer->getPasses());
    }

    #[Test]
    public function getPassesListsTheAddedPasses(): void
    {
        $svgOptimizer = new SvgOptimizer($this->svgProvider);

        self::assertSame([], $svgOptimizer->getPasses());

        $rule = new class implements PassInterface {
            #[\Override]
            public function apply(\DOMDocument $domDocument): void
            {
            }
        };

        $svgOptimizer->addPass($rule);

        self::assertSame([$rule], $svgOptimizer->getPasses());
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[Test]
    public function optimizeReturnsSelfAndContentIsSet(): void
    {
        $svgOptimizer = new SvgOptimizer($this->svgProvider);

        $result = $svgOptimizer->optimize();

        self::assertSame($svgOptimizer, $result);
        self::assertSame('<svg>optimized</svg>', $svgOptimizer->getContent());
    }

    /**
     * @throws SvgValidationException
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function optimizeThrowsExceptionOnInvalidSvg(): void
    {
        $invalidProvider = new class implements SvgProviderInterface {
            public function getInputContent(): string
            {
                return 'invalid svg';
            }

            public function loadContent(): \DOMDocument
            {
                throw new XmlProcessingException('Failed to load DOMDocument.');
            }

            public function optimize(\DOMDocument $domDocument): SvgProviderInterface
            {
                return $this;
            }

            public function getOutputContent(): string
            {
                return '';
            }

            public function saveToFile(string $path): SvgProviderInterface
            {
                return $this;
            }

            public function serialize(\DOMDocument $domDocument): string
            {
                return '';
            }
        };

        $svgOptimizer = new SvgOptimizer($invalidProvider);

        $this->expectException(SvgValidationException::class);
        $this->expectExceptionMessage('The file does not appear to be a valid SVG file.');

        $svgOptimizer->optimize();
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function optimizeThrowsExceptionWhenTheDocumentElementIsNotSvg(): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider('<html xmlns="http://www.w3.org/1999/xhtml"/>'));

        try {
            $svgOptimizer->optimize();
            self::fail('Expected SvgValidationException was not thrown.');
        } catch (SvgValidationException $svgValidationException) {
            self::assertSame('The file does not appear to be a valid SVG file.', $svgValidationException->getMessage());
            self::assertNotInstanceOf(XmlProcessingException::class, $svgValidationException->getPrevious());
        }
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function optimizeKeepsTheReasonWhenTheInputIsNotWellFormed(): void
    {
        try {
            (new SvgOptimizer(new StringProvider('<svg>')))->optimize();
            self::fail('Expected SvgValidationException was not thrown.');
        } catch (SvgValidationException $svgValidationException) {
            self::assertSame('The file does not appear to be a valid SVG file.', $svgValidationException->getMessage());
            self::assertSame(0, $svgValidationException->getCode());
            self::assertInstanceOf(XmlProcessingException::class, $svgValidationException->getPrevious());
        }
    }

    /**
     * @throws \LogicException
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function getMetaDataReturnsCorrectMetaData(): void
    {
        $svgOptimizer = new SvgOptimizer($this->svgProvider);

        $svgOptimizer->optimize();

        $metrics = $svgOptimizer->getMetaData();

        self::assertSame(11, $metrics->getOriginalSize());
        self::assertSame(20, $metrics->getOptimizedSize());
        self::assertGreaterThan(0, $metrics->getOptimizationTime());
        self::assertLessThan(60, $metrics->getOptimizationTime());
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     * @throws IOException
     */
    #[Test]
    public function saveToFileReturnsSelf(): void
    {
        $svgOptimizer = new SvgOptimizer($this->svgProvider);
        $svgOptimizer->optimize();

        $result = $svgOptimizer->saveToFile('/tmp/output.svg');

        self::assertSame($svgOptimizer, $result);
    }

    /**
     * @throws \LogicException
     * @throws IOException
     */
    #[Test]
    public function saveToFileThrowsBeforeOptimize(): void
    {
        $svgOptimizer = new SvgOptimizer($this->svgProvider);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Content cannot be saved before optimization.');

        $svgOptimizer->saveToFile('/tmp/output.svg');
    }

    /**
     * @throws \LogicException
     */
    #[Test]
    public function getContentThrowsBeforeOptimize(): void
    {
        $svgOptimizer = new SvgOptimizer($this->svgProvider);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Content is not available before optimization.');

        $svgOptimizer->getContent();
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[Test]
    public function optimizeWithNoRules(): void
    {
        $svgOptimizer = new SvgOptimizer($this->svgProvider);
        $result = $svgOptimizer->optimize();

        self::assertSame($svgOptimizer, $result);
        self::assertSame('<svg>optimized</svg>', $svgOptimizer->getContent());
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     * @throws IOException
     */
    #[Test]
    public function saveToFileChainingWithDifferentPaths(): void
    {
        $svgOptimizer = new SvgOptimizer($this->svgProvider);
        $svgOptimizer->optimize();

        $result1 = $svgOptimizer->saveToFile('/tmp/file1.svg');
        $result2 = $svgOptimizer->saveToFile('/tmp/file2.svg');

        self::assertSame($svgOptimizer, $result1);
        self::assertSame($svgOptimizer, $result2);
    }

    #[Test]
    public function enableRulesAddsThePassesOfEachRuleOnce(): void
    {
        $svgOptimizer = new SvgOptimizer($this->svgProvider);

        $svgOptimizer->enableRules(Rule::RemoveComments, Rule::SortAttributes);
        $svgOptimizer->enableRules(Rule::RemoveComments, Rule::RemoveEmptyElements);

        self::assertSame(
            [RemoveComments::class, RemoveEmptyTextElements::class, RemoveEmptyContainers::class, SortAttributes::class],
            array_map(static fn (PassInterface $pass): string => $pass::class, $svgOptimizer->getPasses()),
        );
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[DataProvider('provideARuleRunsEachOfItsPassesCases')]
    #[Test]
    public function aRuleRunsEachOfItsPasses(Rule $rule, string $content, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($content));
        $svgOptimizer->enableRules($rule);

        self::assertSame($expected, $svgOptimizer->optimize()->getContent());
    }

    /**
     * @return iterable<string, array{Rule, string, string}>
     */
    public static function provideARuleRunsEachOfItsPassesCases(): iterable
    {
        yield 'migrateToSvg2 migrates links, deprecated attributes and enable-background' => [Rule::MigrateToSvg2, '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" enable-background="new 0 0 10 10"><use xlink:href="#r"/><rect id="r" width="1" height="1"/></svg>', '<svg xmlns="http://www.w3.org/2000/svg"><use href="#r"/><rect id="r" width="1" height="1"/></svg>'];

        yield 'minifyNumericValues minifies numbers and number lists' => [Rule::MinifyNumericValues, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1.50" height="1"/><polygon points="0, 0, 1, 1"/></svg>', '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1.5" height="1"/><polygon points="0 0 1 1"/></svg>'];

        yield 'removeDefaultAttributes removes defaults and xml:space="default"' => [Rule::RemoveDefaultAttributes, '<svg xmlns="http://www.w3.org/2000/svg" stroke-linejoin="miter"><rect width="1" height="1" stroke-linejoin="miter"/><text xml:space="default">a</text></svg>', '<svg xmlns="http://www.w3.org/2000/svg" stroke-linejoin="miter"><rect width="1" height="1"/><text>a</text></svg>'];

        yield 'removeDefaultAttributes removes default style types' => [Rule::RemoveDefaultAttributes, '<svg xmlns="http://www.w3.org/2000/svg"><style type="text/css">rect{fill:red}</style><rect width="1" height="1"/></svg>', '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><rect width="1" height="1"/></svg>'];

        yield 'removeEmptyElements removes empty containers and text elements' => [Rule::RemoveEmptyElements, '<svg xmlns="http://www.w3.org/2000/svg"><g/><text/><rect width="1" height="1"/></svg>', '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1"/></svg>'];

        yield 'removeHiddenElements removes hidden elements and zero-size shapes' => [Rule::RemoveHiddenElements, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" display="none"/><rect width="0" height="1"/><rect width="2" height="2"/></svg>', '<svg xmlns="http://www.w3.org/2000/svg"><rect width="2" height="2"/></svg>'];

        yield 'removeIneffectiveAttributes removes useless strokes, non-inheritable group attributes and unused text attributes' => [Rule::RemoveIneffectiveAttributes, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" stroke="none" stroke-width="2"/><g x="1"><rect width="1" height="1"/></g><rect width="1" height="1" font-size="12"/></svg>', '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" stroke="none"/><g><rect width="1" height="1"/></g><rect width="1" height="1"/></svg>'];
    }

    #[Test]
    public function addedPassesAreNotAddedAgainByARule(): void
    {
        $svgOptimizer = new SvgOptimizer($this->svgProvider);
        $removeComments = new RemoveComments();

        $svgOptimizer->addPass($removeComments);
        $svgOptimizer->enableRules(Rule::RemoveComments);

        self::assertSame([$removeComments], $svgOptimizer->getPasses());
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[Test]
    public function enableRulesRunsPassesInExecutionOrderWhateverTheOrderTheyAreEnabledIn(): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider('<svg xmlns="http://www.w3.org/2000/svg" stroke-width="1"><rect style="stroke-width:1"/></svg>'));

        $svgOptimizer->enableRules(Rule::RemoveDefaultAttributes);
        $svgOptimizer->enableRules(Rule::ConvertInlineStylesToAttributes);
        $svgOptimizer->optimize();

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg" stroke-width="1"><rect/></svg>', $svgOptimizer->getContent());
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[Test]
    public function passesWithoutAPositionRunLast(): void
    {
        $styleRule = new class implements PassInterface {
            #[\Override]
            public function apply(\DOMDocument $domDocument): void
            {
                $domDocument->documentElement?->setAttribute('style', 'fill:red');
            }
        };

        $svgOptimizer = new SvgOptimizer(new StringProvider('<svg xmlns="http://www.w3.org/2000/svg"/>'));
        $svgOptimizer->addPass($styleRule);
        $svgOptimizer->enableRules(Rule::ConvertInlineStylesToAttributes);
        $svgOptimizer->optimize();

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg" style="fill:red"/>', $svgOptimizer->getContent());
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws SvgValidationException
     */
    #[Test]
    public function aRiskyRuleEnabledOneByOneNeedsRiskyRulesToBeAllowed(): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider('<svg xmlns="http://www.w3.org/2000/svg"/>'));
        $svgOptimizer->enableRules(Rule::RemoveComments, Rule::RemoveUnusedIds);

        $this->expectException(RiskyRulesNotAllowedException::class);

        $svgOptimizer->optimize();
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[Test]
    public function aRiskyRuleEnabledOneByOneRunsOnceRiskyRulesAreAllowed(): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider('<svg xmlns="http://www.w3.org/2000/svg"><rect id="a" width="1"/></svg>'));
        $svgOptimizer->enableRules(Rule::RemoveComments, Rule::RemoveUnusedIds);
        $svgOptimizer->allowRisky();

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"><rect width="1"/></svg>', $svgOptimizer->optimize()->getContent());
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[Test]
    public function optimizeRevertsWhenRuleGrowsContentAndShouldCheckSizeIsTrue(): void
    {
        $originalContent = '<svg><rect/></svg>';

        $provider = new class($originalContent) implements SvgProviderInterface {
            private string $lastOutput = '';

            /**
             * @param non-empty-string $testInput
             */
            public function __construct(
                private readonly string $testInput,
            ) {}

            public function getInputContent(): string
            {
                return $this->testInput;
            }

            public function loadContent(): \DOMDocument
            {
                $domDocument = new \DOMDocument();
                $domDocument->loadXML($this->testInput);

                return $domDocument;
            }

            /**
             * @throws XmlProcessingException
             */
            public function optimize(\DOMDocument $domDocument): SvgProviderInterface
            {
                $this->lastOutput = $this->serialize($domDocument);

                return $this;
            }

            public function getOutputContent(): string
            {
                return $this->lastOutput;
            }

            public function saveToFile(string $path): SvgProviderInterface
            {
                return $this;
            }

            public function serialize(\DOMDocument $domDocument): string
            {
                $xml = $domDocument->saveXML($domDocument->documentElement);

                return false === $xml ? '' : $xml;
            }
        };

        $growingRule = new class implements RevertIfLargerPassInterface {
            #[\Override]
            public function apply(\DOMDocument $domDocument): void
            {
                $domDocument->documentElement?->setAttribute(
                    'data-extra-padding-attribute',
                    'this-makes-the-serialized-content-significantly-longer-than-before'
                );
            }
        };

        $svgOptimizer = new SvgOptimizer($provider);
        $svgOptimizer->addPass($growingRule);

        $svgOptimizer->optimize();

        self::assertSame($originalContent, $svgOptimizer->getContent());
    }

    /**
     * @param list<Rule> $rules
     *
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[DataProvider('provideLaterPassesAcceptWhatEarlierMinifiersWriteCases')]
    #[Test]
    public function laterPassesAcceptWhatEarlierMinifiersWrite(array $rules, string $content, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($content));
        $svgOptimizer->enableRules(...$rules);

        self::assertSame($expected, $svgOptimizer->optimize()->getContent());
    }

    /**
     * @return iterable<string, array{list<Rule>, string, string}>
     */
    public static function provideLaterPassesAcceptWhatEarlierMinifiersWriteCases(): iterable
    {
        yield 'shapes become paths after their points are minified' => [
            [Rule::MinifyNumericValues, Rule::ConvertShapesToPath],
            '<svg xmlns="http://www.w3.org/2000/svg"><polyline points="0 0 10 -10 0.5 0.5" stroke="red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path stroke="red" d="M0 0 10-10 .5.5"/></svg>',
        ];

        yield 'groups with a minified transform are flattened' => [
            [Rule::FlattenGroups],
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(10-10)"><rect width="1" height="1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" transform="translate(10-10)"/></svg>',
        ];

        yield 'a minified group transform moves to the children' => [
            [Rule::MoveGroupAttributesToElements],
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(.5.5)"><rect width="1" height="1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect width="1" height="1" transform="translate(.5.5)"/></g></svg>',
        ];
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function revertingAPassRestoresTheDocumentAsLoaded(): void
    {
        $content = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n<!DOCTYPE svg [<!ATTLIST rect fill CDATA \"red\">]>\n<svg xmlns=\"http://www.w3.org/2000/svg\"><text>\xE9 <tspan>a</tspan>\n<tspan>b</tspan></text><rect/></svg>";

        self::assertSame($this->documentSeenAfter($content, false), $this->documentSeenAfter($content, true));
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[DataProvider('provideAllRulesGiveTheSameResultWhenPrefixingDropsRulesCases')]
    #[Test]
    public function allRulesGiveTheSameResultWhenPrefixingDropsRules(string $content): void
    {
        $once = (new SvgOptimizer(new StringProvider($content)))->enableAllRules()->allowRisky()->optimize()->getContent();
        $twice = (new SvgOptimizer(new StringProvider($once)))->enableAllRules()->allowRisky()->optimize()->getContent();

        self::assertSame($once, $twice);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideAllRulesGiveTheSameResultWhenPrefixingDropsRulesCases(): iterable
    {
        yield 'a dropped rule that blocked a class conversion' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50"><style>.a{stroke:red}[stroke]+path{stroke:purple}</style><path class="a" d="M10 10h20" stroke="red"/><path d="M10 20h20"/></svg>'];

        yield 'a dropped rule that selected deprecated attributes' => ['<svg version="1.1" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><style>svg[version="1.1"]{fill:blue}rect[clip]{fill:green}</style><rect x="10" y="10" width="80" height="80" clip="1"/></svg>'];
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[DataProvider('provideAllRulesGiveTheSameResultOnASecondRunCases')]
    #[Test]
    public function allRulesGiveTheSameResultOnASecondRun(string $content): void
    {
        $once = (new SvgOptimizer(new StringProvider($content)))->enableAllRules()->allowRisky()->optimize()->getContent();
        $twice = (new SvgOptimizer(new StringProvider($once)))->enableAllRules()->allowRisky()->optimize()->getContent();

        self::assertSame($once, $twice);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideAllRulesGiveTheSameResultOnASecondRunCases(): iterable
    {
        yield 'a mask that only an empty group references' => ['<svg xmlns="http://www.w3.org/2000/svg"><mask id="m"><path/></mask><path d="M0 0h1v1H0z"/><g mask="url(#m)"><g transform="translate(1 1)"/></g></svg>'];

        yield 'ids inside a group whose class no style sheet hides' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><style>.h{display:none}.f{fill:red}</style><rect class="h" id="r" width="10" height="10"/><g class="f"><path id="p" d="M0 0h5v5H0z"/></g></svg>'];

        yield 'a group attribute that the style of a child repeats' => ['<svg xmlns="http://www.w3.org/2000/svg"><g><g><g style="fill:#000095"><path style="font-size:20px;fill:#000095" d="M0 0h5v5H0z"/></g></g></g></svg>'];
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function revertingAPassKeepsXmlAttributesAfterAnElementWasCloned(): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider('<svg xmlns="http://www.w3.org/2000/svg"><text xml:space="preserve">a  b</text></svg>'));
        $svgOptimizer->addPass(new class implements PassInterface {
            #[\Override]
            public function apply(\DOMDocument $domDocument): void
            {
                $domDocument->getElementsByTagName('text')->item(0)?->cloneNode(false);
            }
        });
        $svgOptimizer->addPass(new class implements RevertIfLargerPassInterface {
            #[\Override]
            public function apply(\DOMDocument $domDocument): void
            {
                $domDocument->documentElement?->setAttribute('data-grow', 'x');
            }
        });

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"><text xml:space="preserve">a  b</text></svg>', $svgOptimizer->optimize()->getContent());
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    private function documentSeenAfter(string $content, bool $grow): string
    {
        $arrayObject = new \ArrayObject();
        $recorder = new /**
         * @no-named-arguments
         */
        readonly class($arrayObject) implements PassInterface {
            /**
             * @param \ArrayObject<int, string> $arrayObject
             */
            public function __construct(
                private \ArrayObject $arrayObject,
            ) {}

            #[\Override]
            public function apply(\DOMDocument $domDocument): void
            {
                $this->arrayObject->append((string) $domDocument->saveXML());
            }
        };
        $svgOptimizer = new SvgOptimizer(new StringProvider($content));

        if ($grow) {
            $svgOptimizer->addPass(new class implements RevertIfLargerPassInterface {
                #[\Override]
                public function apply(\DOMDocument $domDocument): void
                {
                    $domDocument->documentElement?->setAttribute('data-grow', str_repeat('x', 50));
                }
            });
        }

        $svgOptimizer->addPass($recorder);
        $svgOptimizer->optimize();

        return implode('', $arrayObject->getArrayCopy());
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function sizeCheckOnlyRevertsTheCheckedRule(): void
    {
        $growingRule = new class implements PassInterface {
            #[\Override]
            public function apply(\DOMDocument $domDocument): void
            {
                $domDocument->documentElement?->setAttribute('fill', 'red');
            }
        };

        $checkedRule = new class implements RevertIfLargerPassInterface {
            #[\Override]
            public function apply(\DOMDocument $domDocument): void
            {
                $domDocument->documentElement?->appendChild($domDocument->createElement('g'));
            }
        };

        $svgOptimizer = new SvgOptimizer(new StringProvider('<svg xmlns="http://www.w3.org/2000/svg"/>'));
        $svgOptimizer->addPass($growingRule);
        $svgOptimizer->addPass($checkedRule);
        $svgOptimizer->addPass($growingRule);
        $svgOptimizer->addPass($checkedRule);

        $svgOptimizer->optimize();

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg" fill="red"/>', $svgOptimizer->getContent());
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function sizeCheckKeepsACheckedRuleThatDoesNotGrowTheDocument(): void
    {
        $shrinkingRule = new class implements RevertIfLargerPassInterface {
            #[\Override]
            public function apply(\DOMDocument $domDocument): void
            {
                $domDocument->documentElement?->removeAttribute('width');
            }
        };

        $svgOptimizer = new SvgOptimizer(new StringProvider('<svg xmlns="http://www.w3.org/2000/svg" width="1"/>'));
        $svgOptimizer->addPass($shrinkingRule);

        $svgOptimizer->optimize();

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"/>', $svgOptimizer->getContent());
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function sizeCheckKeepsACheckedRuleThatKeepsTheSize(): void
    {
        $sameSizeRule = new class implements RevertIfLargerPassInterface {
            #[\Override]
            public function apply(\DOMDocument $domDocument): void
            {
                $domDocument->documentElement?->setAttribute('width', '2');
            }
        };

        $svgOptimizer = new SvgOptimizer(new StringProvider('<svg xmlns="http://www.w3.org/2000/svg" width="1"/>'));
        $svgOptimizer->addPass($sameSizeRule);

        $svgOptimizer->optimize();

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg" width="2"/>', $svgOptimizer->getContent());
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function revertingARuleDoesNotEmitPhpWarnings(): void
    {
        $growingRule = new class implements RevertIfLargerPassInterface {
            #[\Override]
            public function apply(\DOMDocument $domDocument): void
            {
                $domDocument->documentElement?->setAttribute('data-padding', 'makes-the-serialized-content-longer');
            }
        };

        $svgOptimizer = new SvgOptimizer(new StringProvider('<svg><use xlink:href="#a"/></svg>'));
        $svgOptimizer->addPass($growingRule);

        $warnings = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$warnings): bool {
            $warnings[] = $errstr;

            return true;
        });

        try {
            $svgOptimizer->optimize();
        } finally {
            restore_error_handler();
        }

        self::assertSame([], $warnings);
        self::assertStringNotContainsString('data-padding', $svgOptimizer->getContent());
    }

    /**
     * @throws \LogicException
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     */
    #[Test]
    public function getMetaDataMeasuresTheInputAndTheOutputInBytes(): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider("<svg xmlns=\"http://www.w3.org/2000/svg\"><!-- \u{e9} --></svg>"));
        $svgOptimizer->enableRules(Rule::RemoveComments);
        $svgOptimizer->optimize();

        $metrics = $svgOptimizer->getMetaData();

        self::assertSame(57, $metrics->getOriginalSize());
        self::assertSame(41, $metrics->getOptimizedSize());
        self::assertSame(16, $metrics->getSavedBytes());
        self::assertEqualsWithDelta(16 / 57 * 100, $metrics->getSavedPercentage(), \PHP_FLOAT_EPSILON);
        self::assertGreaterThan(0.0, $metrics->getOptimizationTime());
    }

    /**
     * @throws \LogicException
     */
    #[Test]
    public function getMetaDataThrowsIfCalledBeforeOptimize(): void
    {
        $svgOptimizer = new SvgOptimizer($this->svgProvider);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Metadata is not available before optimization.');

        $svgOptimizer->getMetaData();
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function getMetaDataReportsNegativeSavingsWhenTheOutputIsLarger(): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider('<svg xmlns="http://www.w3.org/2000/svg" a=\'"\'/>'));

        $metrics = $svgOptimizer->optimize()->getMetaData();

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg" a="&quot;"/>', $svgOptimizer->getContent());
        self::assertSame(47, $metrics->getOriginalSize());
        self::assertSame(52, $metrics->getOptimizedSize());
        self::assertSame(-5, $metrics->getSavedBytes());
        self::assertEqualsWithDelta(-5 / 47 * 100, $metrics->getSavedPercentage(), 0.000_001);
        self::assertFalse($metrics->hasSavedBytes());
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function getMetaDataCountsBytesNotCharacters(): void
    {
        $svg = "<svg xmlns=\"http://www.w3.org/2000/svg\"><text>\u{1F600}é</text></svg>";
        $svgOptimizer = new SvgOptimizer(new StringProvider("\xEF\xBB\xBF" . $svg));

        $metrics = $svgOptimizer->optimize()->getMetaData();

        self::assertSame($svg, $svgOptimizer->getContent());
        self::assertSame(68, $metrics->getOriginalSize());
        self::assertSame(65, $metrics->getOptimizedSize());
        self::assertSame(3, $metrics->getSavedBytes());
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function optimizeWritesLatin1InputAsUtf8WithoutAnXmlDeclaration(): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider("<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\r\n<svg xmlns=\"http://www.w3.org/2000/svg\"><text>caf\xE9</text></svg>\r\n"));

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"><text>café</text></svg>', $svgOptimizer->optimize()->getContent());
    }

    /**
     * @param non-empty-string $content
     *
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     * @throws XmlProcessingException
     */
    #[DataProvider('provideOptimizeRejectsInputThatIsNotASvgDocumentCases')]
    #[Test]
    public function optimizeRejectsInputThatIsNotASvgDocument(string $content): void
    {
        $this->expectException(SvgValidationException::class);

        (new SvgOptimizer(new StringProvider($content)))->optimize();
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function provideOptimizeRejectsInputThatIsNotASvgDocumentCases(): iterable
    {
        yield 'whitespace only' => [" \n\t"];

        yield 'byte order mark only' => ["\xEF\xBB\xBF"];

        yield 'truncated XML' => ['<svg xmlns="http://www.w3.org/2000/svg"><rect'];

        yield 'html root' => ['<html xmlns="http://www.w3.org/1999/xhtml"/>'];

        yield 'svg in the wrong namespace' => ['<svg xmlns="urn:x"/>'];

        yield 'plain text' => ['not xml'];
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function sizeCheckRevertKeepsStylesheetInstructionsAndPrefixes(): void
    {
        $checkedRule = new class implements RevertIfLargerPassInterface {
            #[\Override]
            public function apply(\DOMDocument $domDocument): void
            {
                $domDocument->documentElement?->appendChild($domDocument->createElementNS('http://www.w3.org/2000/svg', 'svg:g'));
            }
        };

        $svg = '<?xml-stylesheet href="a.css"?><svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:rect width="1"/></svg:svg>';
        $svgOptimizer = new SvgOptimizer(new StringProvider('<?xml version="1.0"?>' . $svg . '<!-- after -->'));
        $svgOptimizer->addPass($checkedRule);
        $svgOptimizer->addPass($checkedRule);

        self::assertSame($svg, $svgOptimizer->optimize()->getContent());
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function sizeCheckRevertKeepsNonAsciiAttributesAsUtf8(): void
    {
        $checkedRule = new class implements RevertIfLargerPassInterface {
            #[\Override]
            public function apply(\DOMDocument $domDocument): void
            {
                $domDocument->documentElement?->setAttribute('data-x', 'grown');
            }
        };

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" font-family="日本 😀"><text>é</text></svg>';
        $svgOptimizer = new SvgOptimizer(new StringProvider($svg));
        $svgOptimizer->addPass($checkedRule);
        $svgOptimizer->addPass($checkedRule);

        self::assertSame($svg, $svgOptimizer->optimize()->getContent());
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function optimizeRejectsNestingDeeperThanTheParserLimit(): void
    {
        $this->expectException(SvgValidationException::class);

        (new SvgOptimizer(new StringProvider('<svg xmlns="http://www.w3.org/2000/svg">' . str_repeat('<g>', 300) . str_repeat('</g>', 300) . '</svg>')))->optimize();
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws XmlProcessingException
     * @throws \LogicException
     */
    #[Test]
    public function optimizeHandlesADeeplyNestedDocument(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg">' . str_repeat('<g>', 250) . '<rect/>' . str_repeat('</g>', 250) . '</svg>';

        self::assertSame($svg, (new SvgOptimizer(new StringProvider($svg)))->optimize()->getContent());
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->svgProvider = new class implements SvgProviderInterface {
            public function getInputContent(): string
            {
                return '<svg></svg>';
            }

            public function loadContent(): \DOMDocument
            {
                $domDocument = new \DOMDocument();
                $domDocument->loadXML('<svg></svg>');

                return $domDocument;
            }

            public function optimize(\DOMDocument $domDocument): SvgProviderInterface
            {
                return $this;
            }

            public function getOutputContent(): string
            {
                return '<svg>optimized</svg>';
            }

            public function saveToFile(string $path): SvgProviderInterface
            {
                return $this;
            }

            public function serialize(\DOMDocument $domDocument): string
            {
                return '<svg>optimized</svg>';
            }
        };
    }

    #[Test]
    public function enableRuleSetsAddsTheRulesOfTheSetsAndTheirRiskyRulesOnceRiskyRulesAreAllowed(): void
    {
        $svgOptimizer = new SvgOptimizer($this->svgProvider);

        $svgOptimizer->enableRuleSets();
        self::assertCount(0, $svgOptimizer->getPasses());

        $svgOptimizer->enableRuleSets(RuleSet::Cleanup);
        self::assertCount($this->countRules(RuleSet::Cleanup, false), $svgOptimizer->getPasses());

        $svgOptimizer->enableRuleSets(RuleSet::Minification, RuleSet::Cleanup);
        self::assertCount($this->countRules(RuleSet::Cleanup, false) + $this->countRules(RuleSet::Minification, false), $svgOptimizer->getPasses());

        $svgOptimizer->allowRisky();
        self::assertCount($this->countRules(RuleSet::Cleanup, true) + $this->countRules(RuleSet::Minification, true), $svgOptimizer->getPasses());
    }

    #[Test]
    public function enableRuleSetsAfterAllowRiskyAddsTheRiskyRules(): void
    {
        $svgOptimizer = new SvgOptimizer($this->svgProvider);

        $svgOptimizer->allowRisky();
        self::assertCount(0, $svgOptimizer->getPasses());

        $svgOptimizer->enableRuleSets(RuleSet::Structure);
        self::assertCount($this->countRules(RuleSet::Structure, true), $svgOptimizer->getPasses());
        self::assertGreaterThan($this->countRules(RuleSet::Structure, false), $this->countRules(RuleSet::Structure, true));
    }

    #[Test]
    public function enableRuleSetsAddsNothingToAllRules(): void
    {
        $svgOptimizer = new SvgOptimizer($this->svgProvider);

        $svgOptimizer->enableAllRules();

        $allRulesCount = \count($svgOptimizer->getPasses());
        $svgOptimizer->enableRuleSets(...RuleSet::cases());

        self::assertCount($allRulesCount, $svgOptimizer->getPasses());
        self::assertSame(\count(array_merge(...array_map(static fn (Rule $rule): array => $rule->passes(), array_filter(Rule::cases(), static fn (Rule $rule): bool => !$rule->isRisky())))), $allRulesCount);
    }

    private function countRules(RuleSet $ruleSet, bool $withRisky): int
    {
        return \count(array_merge(...array_map(
            static fn (Rule $rule): array => $rule->passes(),
            array_filter($ruleSet->rules(), static fn (Rule $rule): bool => $withRisky || !$rule->isRisky()),
        )));
    }
}
