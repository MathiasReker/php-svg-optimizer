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
use MathiasReker\PhpSvgOptimizer\Pass\PassInterface;
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
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\DefinitionElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementNameTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementRemovalTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\FragmentTargetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdRenameTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberListTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberLiteralTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Paint\GradientLinkTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Presentation\IneffectiveAttributeTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Presentation\InheritedValueTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Security\CanonicalNameTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Security\DangerousUrlTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Security\UnsafeAttributeTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Svg2\AttributeMigrationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Svg2\XlinkMigrationTrait;
use MathiasReker\PhpSvgOptimizer\Rule;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
use MathiasReker\PhpSvgOptimizer\Svg\ElementAttributeMap;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;
use MathiasReker\PhpSvgOptimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Xml\ParseException;
use MathiasReker\PhpSvgOptimizer\Xml\Parser;
use MathiasReker\PhpSvgOptimizer\Xml\Serializer;
use MathiasReker\PhpSvgOptimizer\Xml\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
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
#[CoversClass(Attribute::class)]
#[CoversClass(Element::class)]
#[CoversClass(Parser::class)]
#[CoversClass(Pipeline::class)]
#[CoversClass(RemoveForeignContent::class)]
#[CoversClass(RemoveScripts::class)]
#[CoversClass(RemoveUnsafeMarkup::class)]
#[CoversClass(RemoveUnsafeStyles::class)]
#[CoversClass(RemoveUnsafeUrls::class)]
#[CoversClass(RemoveUseBombs::class)]
#[CoversClass(SvgValidator::class)]
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(CanonicalNameTrait::class)]
#[CoversTrait(DangerousUrlTrait::class)]
#[CoversTrait(DefinitionElementTrait::class)]
#[CoversTrait(ElementNameTrait::class)]
#[CoversTrait(ElementRemovalTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(IdTrait::class)]
#[CoversTrait(StyleElementTrait::class)]
#[CoversTrait(StyleSheetTrait::class)]
#[CoversTrait(UnsafeAttributeTrait::class)]
#[UsesClass(ConvertCssClassesToAttributes::class)]
#[UsesClass(ConvertEllipsesToCircles::class)]
#[UsesClass(ConvertInlineStylesToAttributes::class)]
#[UsesClass(ConvertOneStopGradientsToColors::class)]
#[UsesClass(ConvertShapesToPaths::class)]
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
#[UsesClass(RemoveHiddenElements::class)]
#[UsesClass(RemoveIneffectiveDefinitions::class)]
#[UsesClass(RemoveIneffectiveGroupAttributes::class)]
#[UsesClass(RemoveIneffectiveStrokeAndFill::class)]
#[UsesClass(RemoveIneffectiveTextAttributes::class)]
#[UsesClass(RemoveInvisibleCharacters::class)]
#[UsesClass(RemoveMetadata::class)]
#[UsesClass(RemoveNonStandardAttributes::class)]
#[UsesClass(RemoveNonStandardElements::class)]
#[UsesClass(RemoveTitles::class)]
#[UsesClass(RemoveUnusedDefinitions::class)]
#[UsesClass(RemoveUnusedIds::class)]
#[UsesClass(RemoveUnusedNamespaces::class)]
#[UsesClass(RemoveWidthHeightAttributes::class)]
#[UsesClass(RemoveZeroSizeShapes::class)]
#[UsesClass(Rule::class)]
#[UsesClass(Serializer::class)]
#[UsesClass(SortAttributes::class)]
#[UsesClass(SortDefinitions::class)]
#[UsesClass(SvgOptimizer::class)]
#[UsesTrait(AttributeMigrationTrait::class)]
#[UsesTrait(ClassSelectorTrait::class)]
#[UsesTrait(DeclarationTrait::class)]
#[UsesTrait(FragmentTargetTrait::class)]
#[UsesTrait(GradientLinkTrait::class)]
#[UsesTrait(IdReferenceTrait::class)]
#[UsesTrait(IdRenameTrait::class)]
#[UsesTrait(IneffectiveAttributeTrait::class)]
#[UsesTrait(InheritedValueTrait::class)]
#[UsesTrait(NumberListTrait::class)]
#[UsesTrait(NumberLiteralTrait::class)]
#[UsesTrait(ScriptingTrait::class)]
#[UsesTrait(XlinkMigrationTrait::class)]
final class SecurityRulesTest extends TestCase
{
    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideRemovesUnsafeContentCases')]
    #[Test]
    public function removesUnsafeContent(string $content, string $expectedSvg): void
    {
        $pipeline = new Pipeline($this->securityRules());

        $actual = $pipeline->run($content);

        self::assertSame($expectedSvg, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideRemovesUnsafeContentCases(): iterable
    {
        yield 'Removes external image-set() URLs after a url() inside image-set' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="background:image-set(url(a.png) 1x, &quot;https://evil.com/t.png&quot; 2x)"/><style>.a{background:image-set(url(a.png) 1x,"https://evil.com/t.png" 2x)}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/><style>.a{}</style></svg>',
        ];

        yield 'Removes a javascript: URL split by an escaped surrogate' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="java\d800script:alert(1)"><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>x</text></svg>',
        ];

        yield 'Removes a javascript: URL in url() quoted with an escaped entity' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="url(&amp;quot;javascript:alert(1)&amp;quot;)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes an external URL in an unclosed image-set()' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="background:image-set(&quot;https://evil.com/t.png&quot; 2x"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Keeps relative URLs in image-set()' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="background:image-set(url(a.png) 1x, &quot;b.png&quot; 2x)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="background:image-set(url(a.png) 1x, &quot;b.png&quot; 2x)"/></svg>',
        ];

        yield 'Removes dangerous markup that comes from an entity' => [
            '<!DOCTYPE svg [<!ENTITY js "<script>alert(1)</script><a href=\'javascript:alert(1)\'><text>x</text></a>">]><svg xmlns="http://www.w3.org/2000/svg">&js;</svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>x</text></svg>',
        ];

        yield 'Removes script and iframe elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <script>alert('xss')</script>
                    <iframe src="evil.html"></iframe>
                    <rect width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Removes foreignObject and embed elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <foreignObject>bad</foreignObject>
                    <embed src="evil.swf"/>
                    <circle cx="5" cy="5" r="3"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><circle cx="5" cy="5" r="3"/></svg>
                XML,
        ];

        yield 'Removes use and image elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <use xlink:href="#not-dangerous"/>
                    <image href="http://evil.com/img.svg"/>
                    <line x1="0" y1="0" x2="10" y2="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><use xlink:href="#not-dangerous"/><line x1="0" y1="0" x2="10" y2="10"/></svg>
                XML,
        ];

        yield 'Removes event handler attributes (on*)' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect onclick="alert('xss')" width="10" height="10"/>
                    <circle onload="evil()" cx="5" cy="5" r="3"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/><circle cx="5" cy="5" r="3"/></svg>
                XML,
        ];

        yield 'Removes capitalized or mixed-case event attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect OnClick="alert('xss')" OnLoad="bad()" width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Removes a, href and xlink:href with javascript:' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <a xlink:href="javascript:alert('xss')">link</a>
                    <circle href=" JAVASCRIPT:evil()" cx="5" cy="5" r="3"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">link<circle cx="5" cy="5" r="3"/></svg>
                XML,
        ];

        yield 'Preserves safe href/xlink:href attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <a xlink:href="#section1">link</a>
                    <circle href="/path/to/resource.svg" cx="5" cy="5" r="3"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:href="#section1">link</a><circle href="/path/to/resource.svg" cx="5" cy="5" r="3"/></svg>
                XML,
        ];

        yield 'Removes multiple dangerous attributes from one element' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect onclick="evil()" href="javascript:bad()" xlink:href="javascript:more()" width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Removes script inside SVG' => [
            <<<'XML'
                <svg>
                  <polygon id="triangle" points="0,0 0,50 50,0" fill="#009900" stroke="#004400"/>
                  <script type="text/javascript">alert("xss");</script>
                </svg>
                XML,
            <<<'XML'
                <svg><polygon id="triangle" points="0,0 0,50 50,0" fill="#009900" stroke="#004400"/></svg>
                XML,
        ];

        yield 'Removes script inside SVG two' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="400" height="400" viewBox="0 0 124 124" fill="none">
                <rect width="124" height="124" rx="24" fill="#000000"/>
                   <script type="text/javascript">
                        alert(0x539);
                   </script>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="400" height="400" viewBox="0 0 124 124" fill="none"><rect width="124" height="124" rx="24" fill="#000000"/></svg>
                XML,
        ];

        yield 'External image href' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <image xlink:href="https://example.com/image.jpg" height="200" width="200"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"/>
                XML,
        ];

        yield 'External use href' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <use xlink:href="https://example.com/file2.svg#foo"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"/>
                XML,
        ];

        yield 'CSS via link' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <link xmlns="http://www.w3.org/1999/xhtml" rel="stylesheet" href="http://example.com/style.css" type="text/css"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'CSS via @import' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>@import url(http://example.com/style.css);</style>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'CSS via bare @import without url()' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>@import "http://example.com/style.css";</style>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Removes style element with javascript url() outside @import' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>rect { fill: url(javascript:alert(1)); }</style>
                    <rect width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Removes style element with external http url() outside @import' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>rect { fill: url(http://evil.com/gradient.svg#grad); }</style>
                    <rect width="10" height="10"/>
                </svg>
                XML,
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect { }</style><rect width="10" height="10"/></svg>',
        ];

        yield 'Removes style element with expression()' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>rect { width: expression(alert(1)); }</style>
                    <rect width="10" height="10"/>
                </svg>
                XML,
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect { }</style><rect width="10" height="10"/></svg>',
        ];

        yield 'Removes style element with entity-encoded javascript url()' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>rect { fill: url(&#x6A;&#x61;&#x76;&#x61;&#x73;&#x63;&#x72;&#x69;&#x70;&#x74;&#x3A;alert(1)); }</style>
                    <rect width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Keeps style element with fragment url() reference' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>rect { fill: url(#grad); }</style>
                    <rect width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><style>rect { fill: url(#grad); }</style><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Keeps style element with data:image url()' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>rect { fill: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUA); }</style>
                    <rect width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><style>rect { fill: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUA); }</style><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Keeps plain safe CSS in style element' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>rect { fill: red; stroke: #00FF00; }</style>
                    <rect width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><style>rect { fill: red; stroke: #00FF00; }</style><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'CSS via xml-stylesheet' => [
            <<<'XML'
                <?xml-stylesheet href="http://example.com/style.css"?>
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'XSLT via xml-stylesheet' => [
            <<<'XML'
                <?xml-stylesheet href="http://example.com/style.xsl" type="text/xsl" ?>
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Inline script tag' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <script>alert("xss")</script>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'External script src' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <script src="http://example.com/script.js" type="text/javascript"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'onload attribute injection' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <image xlink:href="http://example.com/image.jpg" onload="alert(1)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"/>
                XML,
        ];

        yield 'foreignObject with iframe' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <foreignObject width="500" height="500">
                        <iframe xmlns="http://www.w3.org/1999/xhtml" src="http://example.com/"/>
                    </foreignObject>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"/>
                XML,
        ];

        yield 'External tref' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <text><tref xlink:href="http://example.com#text"/></text>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><text/></svg>
                XML,
        ];

        yield 'youtube-style CSS injection via style tag with CDATA' => [
            <<<'XML'
                <svg width="128px" height="128px" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1">
                    <style type="text/css">
                        <![CDATA[
                            s {
                                background : "<textarea></textarea><iframe/srcdoc=&lt;script/src=data:,try{parent.document.write(&#x27;\x3cb\x3eLocation:\x3c/b\x3e&#x27;+parent.location+&#x27;\x3cbr\x3e\x3cb\x3eUserAgent:\x3c/b\x3e&#x27;+navigator.userAgent+&#x27;\x3cbr\x3e\x3cb\x3eCookie:\x3c/b\x3e&#x27;+document.cookie+&#x27;\x3cbr\x3e\x3cb\x3eLocalStorage:\x3c/b\x3e&#x27;+JSON.stringify(localStorage)+&#x27;\x3cbr\x3e&#x27;)}catch(e){parent.document.write(e.message)}&gt;&lt;/script&gt;321></iframe>";
                            }
                        ]]>
                    </style>
                </svg>
                XML,
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="128px" height="128px" version="1.1"><style type="text/css">
                            s {
                            }
                        
                    </style></svg>
                EOD,
        ];

        yield 'Removes fill with http URL in url()' => [
            <<<'XML'
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve">
                        <rect fill="url('http://example.com/benis.svg')" x="0" y="0" width="1000" height="1000"></rect>
                    </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" xml:space="preserve"><rect x="0" y="0" width="1000" height="1000"/></svg>
                XML,
        ];

        yield 'Removes fill with https URL in url()' => [
            <<<'XML'
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve">
                        <rect fill="url('https://example.com/benis.svg')" x="0" y="0" width="1000" height="1000"></rect>
                    </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" xml:space="preserve"><rect x="0" y="0" width="1000" height="1000"/></svg>
                XML,
        ];

        yield 'Removes fill with spaced https URL in url()' => [
            <<<'XML'
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve">
                        <rect fill="  url(  ' https://example.com/benis.svg '  ) " x="0" y="0" width="1000" height="1000"></rect>
                    </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" xml:space="preserve"><rect x="0" y="0" width="1000" height="1000"/></svg>
                XML,
        ];

        yield 'Removes fill with ftp URL in url()' => [
            <<<'XML'
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve">
                        <rect fill="url('ftp://192.168.2.1/benis.svg')" x="0" y="0" width="1000" height="1000"></rect>
                    </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" xml:space="preserve"><rect x="0" y="0" width="1000" height="1000"/></svg>
                XML,
        ];

        yield 'Removes fill with protocol-relative URL in url()' => [
            <<<'XML'
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve">
                        <rect fill="url('//example.com/benis.svg')" x="0" y="0" width="1000" height="1000"></rect>
                    </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" xml:space="preserve"><rect x="0" y="0" width="1000" height="1000"/></svg>
                XML,
        ];

        yield 'Keeps fill with relative path in url()' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xml:space="preserve">
                    <rect fill="url('/benis.svg')" x="0" y="0" width="1000" height="1000"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xml:space="preserve"><rect fill="url('/benis.svg')" x="0" y="0" width="1000" height="1000"/></svg>
                XML,
        ];

        yield 'Removes script, onload attributes, namespaced script' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <test></test>
                    <image onload="alert(1)"></image>
                    <svg onload="alert(2)"></svg>
                    <script>alert(3)</script>
                    <defs onload="alert(4)"></defs>
                    <g onload="alert(5)">
                        <circle onload="alert(6)" />
                        <text onload="alert(7)"></text>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><test/><image/><svg/><defs/><g><circle/><text/></g></svg>
                XML,
        ];

        yield 'Removes onload attributes' => [
            <<<'XML'
                <svg version="1.1" baseProfile="full" xmlns="http://www.w3.org/2000/svg">
                    <animate onbegin="alert(1)" attributeName="x" dur="1s" />
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" version="1.1" baseProfile="full"><animate attributeName="x" dur="1s"/></svg>
                XML,
        ];

        yield 'Removes SMIL to/from/by javascript: hidden behind trailing url()' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <a><set attributeName="fill" to="javascript:alert(document.domain)//url()"/><text y="20">click</text></a>
                    <a><animate attributeName="fill" from="javascript:alert(1)//url()" to="javascript:alert(1)//url()" dur="1s"/></a>
                    <a><animate attributeName="fill" by="javascript:alert(1)" dur="1s"/></a>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><a><set attributeName="fill"/><text y="20">click</text></a><a><animate attributeName="fill" dur="1s"/></a><a><animate attributeName="fill" dur="1s"/></a></svg>
                XML,
        ];

        yield 'Removes fill when a later url() is dangerous' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect fill="url(#a) url(http://evil.com/x.svg#g)" width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Removes style attribute when a later url() is dangerous' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect style="fill:url(#a);background:url(https://evil.com/x)" width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Keeps SMIL to with safe value' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect><set attributeName="fill" to="url(#b)"/></rect>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect><set attributeName="fill" to="url(#b)"/></rect></svg>
                XML,
        ];

        yield 'Removes unsafe color value with javascript:' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect fill="javascript:alert(1)" width="10" height="10"/>
                    <circle stroke="javascript:evil()" cx="5" cy="5" r="3"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/><circle cx="5" cy="5" r="3"/></svg>
                XML,
        ];

        yield 'Removes unsafe color value with url() referencing http' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect fill="url('http://evil.com/gradient.svg#grad')" width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Keeps safe color names and hex values' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect fill="red" width="10" height="10"/>
                    <circle stroke="#00FF00" cx="5" cy="5" r="3"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect fill="red" width="10" height="10"/><circle stroke="#00FF00" cx="5" cy="5" r="3"/></svg>
                XML,
        ];

        yield 'Removes fill and stroke attributes with ftp URL in url()' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect fill="url('ftp://example.com/gradient.svg#grad')" stroke="url('ftp://example.com/stroke.svg#stroke')" width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Removes fill and stroke attributes with javascript URL in url()' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect fill="url('javascript:alert(1)')" stroke="url('javascript:alert(2)')" width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Removes fill and stroke attributes with data URL in url()' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect fill="url('data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==')" stroke="url('data:text/html;base64,PHNjcmlwdD5hbGVydCgyKTwvc2NyaXB0Pg==')" width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Removes dangerous URL values in multiple URL attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect
                        clip-path="url('javascript:alert(1)')"
                        mask="url('data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==')"
                        marker-start="url('javascript:alert(2)')"
                        marker-mid="url('http://example.com/mid.svg#marker')"
                        marker-end="url('ftp://example.com/end.svg#marker')"
                        begin="url('file:///etc/passwd')"
                        end="url('//example.com/end')"
                        from="url('javascript:alert(3)')"
                        to="url('data:application/octet-stream;base64,')"
                        values="url('https://example.com/values.svg#vals')"
                        width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Removes unsafe elements inside pattern' => [
            <<<'XML'
                    <svg xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <pattern id="safePattern" width="10" height="10">
                                <circle cx="5" cy="5" r="2" fill="blue"/>
                            </pattern>
                            <pattern id="unsafePattern" width="10" height="10">
                                <image href="http://evil.com/image.svg"/>
                                <script>alert('xss')</script>
                            </pattern>
                        </defs>
                        <rect width="50" height="50" fill="url(#safePattern)"/>
                        <rect width="50" height="50" fill="url(#unsafePattern)"/>
                    </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><pattern id="safePattern" width="10" height="10"><circle cx="5" cy="5" r="2" fill="blue"/></pattern><pattern id="unsafePattern" width="10" height="10"/></defs><rect width="50" height="50" fill="url(#safePattern)"/><rect width="50" height="50" fill="url(#unsafePattern)"/></svg>
                XML,
        ];

        yield 'Removes onerror' => [
            <<<'XML'
                <svg onerror="alert('xss')"></svg>
                XML,
            <<<'XML'
                <svg/>
                XML,
        ];

        yield 'Removes onmoouseover' => [
            <<<'XML'
                <svg onmoouseover="alert('xss')"></svg>
                XML,
            <<<'XML'
                <svg/>
                XML,
        ];

        yield 'Removes onfocus' => [
            <<<'XML'
                <svg onfocus="alert('xss')"></svg>
                XML,
            <<<'XML'
                <svg/>
                XML,
        ];

        yield 'Removes onload' => [
            <<<'XML'
                <svg onload="alert('xss')"></svg>
                XML,
            <<<'XML'
                <svg/>
                XML,
        ];

        yield 'Removes onclick' => [
            <<<'XML'
                <svg onclick="alert('xss')"></svg>
                XML,
            <<<'XML'
                <svg/>
                XML,
        ];

        yield 'Removes onmouseover' => [
            <<<'XML'
                <svg onmouseover="alert('xss')"></svg>
                XML,
            <<<'XML'
                <svg/>
                XML,
        ];

        yield 'Removes javascript encoded with HTML entities' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <a href="&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;alert(1)">x</a>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">x</svg>
                XML,
        ];

        yield 'Removes javascript encoded with hex entities' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <a href="&#x6A;&#x61;&#x76;&#x61;&#x73;&#x63;&#x72;&#x69;&#x70;&#x74;&#x3A;alert(1)">x</a>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">x</svg>
                XML,
        ];

        yield 'Removes base64 data URL with script' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <image href="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg=="/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Removes SMIL begin with javascript URL' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <animate attributeName="x" begin="javascript:alert(1)" dur="1s"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><animate attributeName="x" dur="1s"/></svg>
                XML,
        ];

        yield 'Removes values attribute containing javascript url()' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <animate attributeName="fill"
                        values="red;url(javascript:alert(1));blue"
                        dur="1s"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><animate attributeName="fill" dur="1s"/></svg>
                XML,
        ];

        yield 'Removes nested svg with onload' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <svg onload="alert(1)">
                        <circle r="5"/>
                    </svg>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><svg><circle r="5"/></svg></svg>
                XML,
        ];

        yield 'Removes javascript in style attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect style="fill: url(javascript:alert(1))" width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Removes expression() in style attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect style="width: expression(alert(1))"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>
                XML,
        ];

        yield 'Removes cursor with external URL' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect cursor="url(http://example.com/cursor.cur), auto" width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Removes background with external image URL' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect background="url(https://example.com/bg.png)" width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Removes border with external URL' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect border="url(http://example.com/border.svg)" width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Removes color-profile with external URL' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect color-profile="http://example.com/profile.icc" width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Removes marker attributes with external URLs' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <line
                        marker-start="url(http://example.com/start.svg#s)"
                        marker-mid="url(http://example.com/mid.svg#m)"
                        marker-end="url(http://example.com/end.svg#e)"
                        x1="0" y1="0" x2="10" y2="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><line x1="0" y1="0" x2="10" y2="10"/></svg>
                XML,
        ];

        yield 'Removes overlay with external URL' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect overlay="url(https://example.com/overlay.svg)" width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Removes javascript split by whitespace and entities' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <a href="j &#x61; v &#97; s &#99; r i p t : alert(1)">x</a>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">x</svg>
                XML,
        ];

        yield 'Removes javascript mixed decimal and hex entities' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <a href="&#106;&#x61;&#118;&#x61;&#115;&#99;&#114;&#105;&#112;&#x74;&#58;alert(1)">x</a>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">x</svg>
                XML,
        ];

        yield 'Removes javascript with unicode homoglyphs' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <a href="ｊａｖａｓｃｒｉｐｔ:alert(1)">x</a>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">x</svg>
                XML,
        ];

        yield 'Removes javascript with random casing' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <a href="JaVaScRiPt:alert(1)">x</a>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">x</svg>
                XML,
        ];

        yield 'Removes entity-encoded javascript inside url()' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect fill="url(&#x6A;&#x61;&#x76;&#x61;&#x73;&#x63;&#x72;&#x69;&#x70;&#x74;&#x3A;alert(1))"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>
                XML,
        ];

        yield 'Removes javascript in url() with whitespace and quotes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect fill="url(  ' j a v a s c r i p t : alert(1) ' )"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>
                XML,
        ];

        yield 'Removes obfuscated javascript in SMIL begin attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <animate begin="&#x6A;&#x61;&#x76;&#x61;&#x73;&#x63;&#x72;&#x69;&#x70;&#x74;&#x3A;alert(1)" dur="1s"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><animate dur="1s"/></svg>
                XML,
        ];

        yield 'Removes javascript in style attribute with entity encoding' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect style="background:url(&#x6A;&#x61;&#x76;&#x61;&#x73;&#x63;&#x72;&#x69;&#x70;&#x74;&#x3A;alert(1))"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>
                XML,
        ];

        yield 'Removes javascript split by newlines and tabs' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <a href="java
                script:alert(1)">x</a>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">x</svg>
                XML,
        ];

        yield 'Removes javascript with mixed ASCII and fullwidth chars' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <a href="jａvａｓcｒiｐt:alert(1)">x</a>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">x</svg>
                XML,
        ];

        yield 'Removes javascript via CSS hex escapes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect style="background:url(\6a\61\76\61\73\63\72\69\70\74:alert(1))"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>
                XML,
        ];

        yield 'Removes chained SMIL javascript via values' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <animate attributeName="x"
                        values="0;javascript:alert(1);10"
                        dur="1s"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><animate attributeName="x" dur="1s"/></svg>
                XML,
        ];

        yield 'Removes protocol-relative URL with whitespace' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <image href="  //example.com/evil.svg  "/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Removes javascript in set element' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <set attributeName="href" to="javascript:alert(1)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Removes javascript with comments' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <a href="java/* comment */script:alert(1)">x</a>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">x</svg>
                XML,
        ];

        yield 'Removes various on-event handlers' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect onfocus="alert(1)" onblur="alert(2)" onfocusin="alert(3)" onfocusout="alert(4)" onactivate="alert(5)" width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Removes vbscript protocol' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <a href="vbscript:alert(1)">x</a>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">x</svg>
                XML,
        ];

        yield 'Removes tel and sms protocols' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <a href="tel:1234567890">call</a>
                    <a href="sms:1234567890">text</a>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">calltext</svg>
                XML,
        ];

        yield 'Removes src attribute with javascript' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <script src="javascript:alert(1)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Removes mailto protocol' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <a href="mailto:test@example.com">email</a>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">email</svg>
                XML,
        ];

        yield 'Removes dangerous tags inside style' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>
                        @import url(http://example.com/style.css);
                        s {
                            background: "<script>alert(1)</script>";
                        }
                    </style>
                </svg>
                XML,
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg"><style>
                        s {
                            background: "";
                        }
                    </style></svg>
                EOD,
        ];

        yield 'Removes SMIL values with data URL' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <animate attributeName="fill"
                        values="red;data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==;blue"
                        dur="1s"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><animate attributeName="fill" dur="1s"/></svg>
                XML,
        ];

        yield 'Removes uppercase SCRIPT tag' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <SCRIPT>alert('xss')</SCRIPT>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Removes src attribute with http' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <script src="http://example.com/script.js"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Keeps data:image in href' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><image href="data:image/png;base64,..."/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><image href="data:image/png;base64,..."/></svg>',
        ];

        yield 'Keeps data:image in xlink:href' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><image xlink:href="data:image/png;base64,..."/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><image xlink:href="data:image/png;base64,..."/></svg>',
        ];

        yield 'Removes data:text/html in href' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><image href="data:text/html;base64,..."/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Keeps data:image in fill' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="url(data:image/png;base64,...)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="url(data:image/png;base64,...)"/></svg>',
        ];

        yield 'Removes data:text/html in fill' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="url(data:text/html;base64,...)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Keeps data image png in href' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <image href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUA"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><image href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUA"/></svg>
                XML,
        ];

        yield 'Keeps data image svg in href' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <image href="data:image/svg+xml;base64,PHN2Zy8+" />
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><image href="data:image/svg+xml;base64,PHN2Zy8+"/></svg>
                XML,
        ];

        yield 'Keeps data image gif in fill url()' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect fill="url(data:image/gif;base64,R0lGODlhAQABAIAAAP)" width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect fill="url(data:image/gif;base64,R0lGODlhAQABAIAAAP)" width="10" height="10"/></svg>
                XML,
        ];

        yield 'Keeps uppercase DATA IMAGE protocol' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <image href="DATA:IMAGE/JPEG;base64,/9j/4AAQSkZJRgABAQ"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><image href="DATA:IMAGE/JPEG;base64,/9j/4AAQSkZJRgABAQ"/></svg>
                XML,
        ];

        yield 'Keeps data image with whitespace around value' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <image href="  data:image/webp;base64,UklGRiIAAABXRUJQVlA4  "/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><image href="  data:image/webp;base64,UklGRiIAAABXRUJQVlA4  "/></svg>
                XML,
        ];

        yield 'Keeps data image inside style attribute url()' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect style="background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUA)" width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect style="background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUA)" width="10" height="10"/></svg>
                XML,
        ];

        yield 'Keeps data image in xlink:href' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <image xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUA"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><image xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUA"/></svg>
                XML,
        ];

        yield 'Keeps data image in SMIL values list' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <animate attributeName="fill"
                        values="red;data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUA;blue"
                        dur="1s"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><animate attributeName="fill" values="red;data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUA;blue" dur="1s"/></svg>
                XML,
        ];

        yield 'Removes foreignObject' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <foreignObject>
                        <body><script>alert(1)</script></body>
                    </foreignObject>
                    <rect width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Removes use with external href' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <use xlink:href="http://evil.com/attack.svg#foo"/>
                    <circle cx="5" cy="5" r="3"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><circle cx="5" cy="5" r="3"/></svg>
                XML,
        ];

        yield 'Removes image with data:text/html' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <image href="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg=="/>
                    <rect width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Removes numeric entity obfuscated javascript' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <a href="&#x6A;&#x61;&#x76;&#x61;script:alert(1)">click</a>
                    <rect width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">click<rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Removes inline style with url(javascript:...)' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect style="background-image: url('javascript:alert(1)'); width:100px; height:100px"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>
                XML,
        ];

        yield 'Removes style with behavior url()' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect style="behavior: url('http://evil.com/attack.htc'); width:100px; height:100px"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>
                XML,
        ];

        yield 'Removes nested @import in style' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>
                        @import url("http://evil.com/style.css");
                        rect { fill: red; }
                    </style>
                </svg>
                XML,
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg"><style>
                        rect { fill: red; }
                    </style></svg>
                EOD,
        ];

        yield 'Removes unsafe use href reference' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <use xlink:href="http://evil.com/bad"/>
                    <use xlink:href="#safe"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><use xlink:href="#safe"/></svg>
                XML,
        ];

        yield 'Removes images with external href' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <image href="http://evil.com/img.svg"/>
                    <image href="data:image/png;base64,abc"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><image href="data:image/png;base64,abc"/></svg>
                XML,
        ];

        yield 'Removes unsafe style content' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>
                        @import url("http://evil.com/style.css");
                        rect { fill: red; }
                    </style>
                    <rect width="10" height="10"/>
                </svg>
                XML,
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg"><style>
                        rect { fill: red; }
                    </style><rect width="10" height="10"/></svg>
                EOD,
        ];

        yield 'Removes link element referencing external resource' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <link href="http://evil.com/style.css"/>
                    <rect width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Removes style element with potential external import' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>@import url("http://evil.com/x.css");</style>
                    <rect width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Removes event handler attributes (XSS vector)' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect width="10" height="10" onclick="alert(1)" onload="evil()"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Removes javascript URLs in attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <a xlink:href="javascript:alert(1)">
                        <text>Click</text>
                    </a>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><text>Click</text></svg>
                XML,
        ];

        yield 'Removes base tag to prevent SSRF or URL rewriting' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <base href="http://evil.com/"/>
                    <rect width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><base/><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Removes processing instructions' => [
            <<<'XML'
                <?xml-stylesheet type="text/xsl" href="http://evil.com/x.xsl"?>
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Removes animate with dangerous href' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <animate attributeName="href" values="javascript:alert(1);"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Removes animate with dangerous href with data' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <animate attributeName="href" values="data:alert(1);"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Removes dangerous values on animation not targeting href' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <animate attributeName="fill" values="javascript:alert(1);"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><animate attributeName="fill"/></svg>
                XML,
        ];

        yield 'Unwraps a tag with javascript href' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <a href="javascript:alert(1)">click me</a>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">click me</svg>
                XML,
        ];

        yield 'Unwraps element with xlink:href dangerous value' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <use xlink:href="javascript:alert(1)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Preserves element with safe xlink:href' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <use xlink:href="#safe"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><use xlink:href="#safe"/></svg>
                XML,
        ];

        yield 'Removes style with @import' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>@import url("https://malicious.com/style.css");</style>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Removes style with dangerous HTML tags inside' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style><script>alert(1)</script></style>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><style/></svg>
                XML,
        ];

        yield 'Removes element with on* event attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect onclick="alert(1)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>
                XML,
        ];

        yield 'Unwraps element with dangerous srcset' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <image srcset="javascript:alert(1)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><image/></svg>
                XML,
        ];

        yield 'Unwraps element with dangerous style url()' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect style="fill: url('javascript:alert(1)')"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>
                XML,
        ];

        yield 'Unwraps animateMotion with dangerous values' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <animateMotion values="javascript:alert(1);"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><animateMotion/></svg>
                XML,
        ];

        yield 'Removes processing instruction xml-stylesheet' => [
            <<<'XML'
                <?xml-stylesheet type="text/css" href="http://malicious.com/style.css"?>
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>
                XML,
        ];

        yield 'Removes xml-stylesheet processing instruction nested inside the root element' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <?xml-stylesheet type="text/css" href="http://malicious.com/style.css"?>
                    <rect/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>
                XML,
        ];

        yield 'Removes case-varied xml-stylesheet processing instruction nested inside the root element' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <?XML-STYLESHEET type="text/css" href="http://malicious.com/style.css"?>
                    <rect/>
                </svg>
                EOD,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>
                XML,
        ];

        yield 'Removes non-stylesheet processing instruction nested inside the root element' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <?some-other-instruction data="harmless"?>
                    <rect/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>
                XML,
        ];

        yield 'Removes processing instruction that breaks out of foreign content when inlined in HTML' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><?foo x="><img src=x onerror=alert(1)>"?><rect/></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>
                XML,
        ];

        yield 'Removes comment that closes early when inlined in HTML' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><!--><img src=x onerror=alert(1)>--><!---><b>--><rect/></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>
                XML,
        ];

        yield 'Removes comment containing markup' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><!-- </style><img src=x onerror=alert(1)> --><rect/></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>
                XML,
        ];

        yield 'Keeps plain comment' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><!-- a > b --><rect/></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><!-- a > b --><rect/></svg>
                XML,
        ];

        yield 'Removes elements from foreign namespaces' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:h="http://www.w3.org/1999/xhtml" xmlns:m="http://www.w3.org/1998/Math/MathML"><h:form action="javascript:alert(1)"><h:input type="submit" formaction="javascript:alert(1)"/></h:form><m:math/><rect/></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:h="http://www.w3.org/1999/xhtml" xmlns:m="http://www.w3.org/1998/Math/MathML"><rect/></svg>
                XML,
        ];

        yield 'Keeps elements bound to the SVG namespace via a prefix' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:s="http://www.w3.org/2000/svg"><s:rect/></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:s="http://www.w3.org/2000/svg"><s:rect/></svg>
                XML,
        ];

        yield 'Removes animation elements targeting href regardless of value or casing' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a><set attributeName="xlink:href" to="x"/><animateMotion attributeName="HREF"/><animateTransform attributeName="href"/><animate attributeName="fill"/></a></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a><animate attributeName="fill"/></a></svg>
                XML,
        ];

        yield 'Removes URL-bearing attributes with dangerous protocols' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><g action="javascript:alert(1)" formaction="javascript:alert(1)" poster="http://evil.com/x.png" cite="//evil.com" background="https://evil.com/x.png" ping="https://evil.com"/></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><g/></svg>
                XML,
        ];

        yield 'Removes additional script-capable protocols' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><a href="livescript:alert(1)">a</a><a href="ms-its:x.chm::/x.htm">b</a><a href="jar:x!/y">c</a></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">abc</svg>
                XML,
        ];

        yield 'Removes data: href outside image elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><use href="data:image/svg+xml;base64,PHN2Zy8+"/><filter><feImage href="data:image/png;base64,iVBORw0KGgo"/></filter></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><use/><filter><feImage href="data:image/png;base64,iVBORw0KGgo"/></filter></svg>
                XML,
        ];

        yield 'Removes external image-set() URLs in CSS' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><style>rect { background: -webkit-image-set("https://evil.com/x.png" 1x); }</style><rect style="background-image:image-set('//evil.com/x.png' 1x)"/></svg>
                XML,
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect { }</style><rect/></svg>',
        ];

        yield 'Expands custom entity references so HTML named character references cannot rebuild a javascript: URL' => [
            <<<'XML'
                <!DOCTYPE svg [<!ENTITY Tab "#"><!ENTITY colon "/">]><svg xmlns="http://www.w3.org/2000/svg"><a href="&Tab;javascript:alert(1)"><text>x</text></a><a href="javascript&colon;alert(1)"><text>y</text></a></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><a href="#javascript:alert(1)"><text>x</text></a><a href="javascript/alert(1)"><text>y</text></a></svg>
                XML,
        ];

        yield 'Expands custom entity references in text and style content' => [
            <<<'XML'
                <!DOCTYPE svg [<!ENTITY u "x">]><svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:&u;}</style><text>&u;</text></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:x}</style><text>x</text></svg>
                XML,
        ];

        yield 'Removes entity that expands to a javascript: URL' => [
            <<<'XML'
                <!DOCTYPE svg [<!ENTITY js "javascript:alert(1)">]><svg xmlns="http://www.w3.org/2000/svg"><a href="&js;"><text>x</text></a></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><text>x</text></svg>
                XML,
        ];

        yield 'Removes HTML tags that break out of SVG foreign content' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><p/><meta http-equiv="refresh" content="0;url=https://evil.com"/><img src="x"/><div><span/></div><ø:script>alert(1)</ø:script><rect/></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>
                XML,
        ];

        yield 'Removes font only when it carries an HTML breakout attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><font color="red"/><font face="x"/><font size="1"/><font horiz-adv-x="1"/></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><font horiz-adv-x="1"/></svg>
                XML,
        ];

        yield 'Removes style element hiding an external url() behind a CSS escape' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:\75rl(https://evil.com/x)}</style><style>@\69mport "https://evil.com/x.css";</style><rect/></svg>
                XML,
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{}</style><rect/></svg>',
        ];

        yield 'Removes unclosed external url()' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:url(https://evil.com/x</style><rect style="fill:url(https://evil.com/x" fill="url('//evil.com/x"/></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>
                XML,
        ];

        yield 'Removes name attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect id="a" name="getElementById"/></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect id="a"/></svg>
                XML,
        ];

        yield 'Unwraps a tag with double-encoded HTML entity obfuscated javascript href' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <a href="&amp;#106;avascript:alert(1)">x</a>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">x</svg>
                XML,
        ];

        yield 'Removes style url() dangerous protocol only revealed after re-normalization' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect style="background:url(\26#106;avascript:alert1)" width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Preserves safe srcset attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <image srcset="a.png 1x, b.png 2x" width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><image srcset="a.png 1x, b.png 2x" width="10" height="10"/></svg>
                XML,
        ];

        yield 'Removes a tag carrying attributeName href directly on itself' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <a attributeName="href"></a>
                    <circle cx="5" cy="5" r="3"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><circle cx="5" cy="5" r="3"/></svg>
                XML,
        ];

        yield 'Removes use tag carrying attributeName xlink:href directly on itself' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <use attributeName="xlink:href"/>
                    <circle cx="5" cy="5" r="3"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><circle cx="5" cy="5" r="3"/></svg>
                XML,
        ];

        yield 'Removes a dangerous href tag processed after an attributeName-hijack tag of the same type' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <a href="javascript:alert(1)"/>
                    <a attributeName="href"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Reassembles and detects a javascript protocol split by a null-byte CSS hex escape' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <a href="java\00script:alert(1)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Removes a dangerous src attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect src="javascript:alert(1)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>
                XML,
        ];

        yield 'Keeps a safe src attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect src="safe.png"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect src="safe.png"/></svg>
                XML,
        ];

        yield 'Removes a javascript: URL split by a zero-width space' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="java&#x200B;script:alert(1)"><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>x</text></svg>',
        ];

        yield 'Removes a javascript: URL split by a soft hyphen' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="java&#xAD;script:alert(1)"><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>x</text></svg>',
        ];

        yield 'Removes a javascript: URL split by a byte order mark' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="java&#xFEFF;script:alert(1)"><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>x</text></svg>',
        ];

        yield 'Removes a javascript: URL split by a word joiner' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="java&#x2060;script:alert(1)"><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>x</text></svg>',
        ];

        yield 'Removes an external url() split by a zero-width space' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:url(ht&#x200B;tps://evil.com/x.svg#a)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes a set animation of an event handler attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect><set attributeName="onclick" to="alert(1)"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes an animation of an uppercase, padded and prefixed event handler attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect><animate attributeName=" ev:ONCLICK " values="alert(1)"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes an animateTransform of an event handler attribute written with a character reference' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect><animateTransform attributeName="on&#x6C;oad" to="alert(1)"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Keeps an animation of an attribute that only contains "on"' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect><animate attributeName="font-size" to="2"/><set attributeName="display" to="none"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect><animate attributeName="font-size" to="2"/><set attributeName="display" to="none"/></rect></svg>',
        ];

        yield 'Removes a network-path reference written with two backslashes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><image href="\\\evil.com/x.png"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Removes a network-path reference written with a slash and a backslash before hex digits' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><image href="/\abc.com/x.png"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Removes a network-path reference written with a backslash and a slash' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="\/evil.com"><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>x</text></svg>',
        ];

        yield 'Removes a network-path reference with backslashes from a filter image' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><filter><feImage href="\\\evil.com/x.png"/></filter></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><filter><feImage/></filter></svg>',
        ];

        yield 'Removes a network-path reference with backslashes from a src attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect src="\\\evil.com/x.png"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Keeps a relative URL containing a backslash' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="x\y.html"><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="x\y.html"><text>x</text></a></svg>',
        ];

        yield 'Removes an external url() hidden by CSS escapes of non-hex characters' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:u\rl(https\:\/\/evil.com/x.svg#a)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes a style element with an external url() hidden by a CSS escape' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:u\rl(https://evil.com/x.svg#a)}</style><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{}</style><rect/></svg>',
        ];

        yield 'Removes a style element with an @import hidden by a CSS escape' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@imp\ort "x.css";rect{fill:red}</style><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><rect/></svg>',
        ];

        yield 'Removes a network-path url() written with CSS-escaped backslashes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:url(\\\\\\\evil.com/x.svg#a)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Keeps a root-relative url() whose leading backslash is escaped' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:url(\\\evil.com/x.svg#a)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:url(\\\evil.com/x.svg#a)"/></svg>',
        ];

        yield 'Removes a dangerous style element and link in a document with a prefixed SVG namespace' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:style>@import url(x.css);</svg:style><svg:a href="javascript:alert(1)"><svg:text>x</svg:text></svg:a></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:text>x</svg:text></svg:svg>',
        ];

        yield 'Removes a javascript: URL bound to a custom XLink prefix' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:f="http://www.w3.org/1999/xlink"><a f:href="javascript:alert(1)"><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:f="http://www.w3.org/1999/xlink"><a><text>x</text></a></svg>',
        ];

        yield 'Escapes CDATA markup in a style element inside desc so it cannot break out when inlined in HTML' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><desc><style><![CDATA[</style><img src=x onerror=alert(1)>]]></style></desc></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><desc><style>&lt;/style&gt;&lt;img src=x onerror=alert(1)&gt;</style></desc></svg>',
        ];

        yield 'Removes a comment that closes a raw text element inside title' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><title><noscript><!--</noscript><img src=x onerror=alert(1)>--></noscript></title></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><title><noscript/></title></svg>',
        ];

        yield 'Removes markup expanded from an internal parameter entity' => [
            '<!DOCTYPE svg [<!ENTITY % p "<!ENTITY x \'&#60;script&#62;alert(1)&#60;/script&#62;\'>"> %p;]><svg xmlns="http://www.w3.org/2000/svg">&x;</svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Removes dangerous content deep in nested groups' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g><g><g><a href="javascript:alert(1)"><rect onclick="alert(1)"/></a><script>alert(1)</script></g></g></g></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g><g><g><rect/></g></g></g></g></g></svg>',
        ];

        yield 'Removes only the later of two links when the first is safe' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="#a"><text>a</text></a><a href="javascript:alert(1)"><text>b</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="#a"><text>a</text></a><text>b</text></svg>',
        ];

        yield 'Keeps multibyte text and safe attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text fill="currentColor" stroke="inherit">ÆØÅ 日本 😀</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text fill="currentColor" stroke="inherit">ÆØÅ 日本 😀</text></svg>',
        ];

        yield 'Keeps an empty document' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Handles an empty document' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"/>',
        ];

        yield 'Handles a root with only text' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">text</svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">text</svg>',
        ];

        yield 'Handles a 60 levels deep structure' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">' . str_repeat('<g>', 60) . '<rect onclick="x"/><script>x</script>' . str_repeat('</g>', 60) . '</svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">' . str_repeat('<g>', 60) . '<rect/>' . str_repeat('</g>', 60) . '</svg>',
        ];

        yield 'Handles a very long attribute value' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect id="' . str_repeat('a', 10_000) . '" onclick="x"/><script>x</script></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect id="' . str_repeat('a', 10_000) . '"/></svg>',
        ];

        yield 'Handles right-to-left, emoji, combining and astral text' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text onclick="x">שלום 😀 é 𝒜</text><script>x</script></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text>שלום 😀 é 𝒜</text></svg>',
        ];

        yield 'Handles values padded with tabs, newlines and no-break spaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect onclick="x" fill="&#9; red&#10;&#xA0;"/><script>x</script></svg>',
            "<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" xmlns:inkscape=\"http://www.inkscape.org/namespaces/inkscape\"><rect fill=\"&#9; red&#10;\u{a0}\"/></svg>",
        ];

        yield 'Handles comments between every node' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><!--1--><g><!--2--><script>x</script><!--3--><rect onclick="x"/><!--4--></g><!--5--></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><!--1--><g><!--2--><!--3--><rect/><!--4--></g><!--5--></svg>',
        ];

        yield 'Handles processing instructions' => [
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><?foo bar?><rect onclick="x"/><script>x</script></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect/></svg>',
        ];

        yield 'Handles CDATA sections' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text onclick="x"><![CDATA[a<b&c]]></text><script>x</script></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text>a&lt;b&amp;c</text></svg>',
        ];

        yield 'Handles a child that resets the default namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><g><x xmlns=""><rect onclick="x"/><script>x</script></x></g><rect onclick="x"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><g><x xmlns=""><rect/></x></g><rect/></svg>',
        ];

        yield 'Handles a prefix redeclared to another namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:s="http://www.w3.org/2000/svg"><s:g><s:rect onclick="x"/></s:g><g xmlns:s="urn:other"><s:rect onclick="x"/></g><script>x</script></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:s="http://www.w3.org/2000/svg"><s:g><s:rect/></s:g><g xmlns:s="urn:other"/></svg>',
        ];

        yield 'Handles entities and character references in values and text' => [
            '<!DOCTYPE svg [<!ENTITY e "v&#x41;">]><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text onclick="x" class="&e;">&e;&#x42;&#66;</text><script>x</script></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text class="vA">vABB</text></svg>',
        ];

        yield 'Handles an attribute in no namespace next to the same local name in another namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:o="urn:o"><use href="#a" xlink:href="#b" o:href="#c" onclick="x"/><script>x</script></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:o="urn:o"><use href="#a" xlink:href="#b" o:href="#c"/></svg>',
        ];

        yield 'Handles repeated elements where only a later one matches' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect/><rect fill="red"/><rect onclick="x"/><script>x</script></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect/><rect fill="red"/><rect/></svg>',
        ];

        yield 'Handles mixed-case element names' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><Rect onclick="x"/><RECT onclick="x"/><Script>x</Script><SCRIPT>x</SCRIPT><s:script xmlns:s="http://www.w3.org/2000/svg">x</s:script><script>x</script></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"/>',
        ];

        yield 'Removes a handler element with an ev:event attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:ev="http://www.w3.org/2001/xml-events"><handler type="application/ecmascript" ev:event="load">alert(1)</handler><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:ev="http://www.w3.org/2001/xml-events"><rect/></svg>',
        ];

        yield 'Removes a prefixed listener element' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:ev="http://www.w3.org/2001/xml-events"><s:listener xmlns:s="http://www.w3.org/2000/svg" event="click" handler="#h"/><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:ev="http://www.w3.org/2001/xml-events"><rect/></svg>',
        ];

        yield 'Removes XML Events attributes bound to any prefix' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:e="http://www.w3.org/2001/xml-events"><rect e:event="click" e:handler="#h" e:observer="r" id="r"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:e="http://www.w3.org/2001/xml-events"><rect id="r"/></svg>',
        ];

        yield 'Removes a style attribute with -o-link' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="-o-link:\'javascript:alert(1)\';-o-link-source:current"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes a style attribute with behavior' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="behavior:url(x.htc)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes a style attribute with behavior written with a CSS escape' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="beh\61vior : url(x.htc)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes a style attribute with a bare javascript: value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:javascript:alert(1)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes a style element with -moz-binding' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{-moz-binding:url(x.xml#b)}</style><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{}</style><rect/></svg>',
        ];

        yield 'Keeps a style element with scroll-behavior' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>svg{scroll-behavior:smooth}</style><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>svg{scroll-behavior:smooth}</style><rect/></svg>',
        ];

        yield 'Removes animations targeting event handlers and href in any case or prefix' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a><animate attributeName="OnClick" to="x"/><set attributeName="x:onload" to="x"/><animate attributeName="XLINK:HREF" to="#a"/><set attributeName="foo:Href" to="#a"/><animateMotion attributeName="onend"/><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a><text>x</text></a></svg>',
        ];

        yield 'Keeps an animation after a removed one' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect><set attributeName="onclick" to="x"/><set attributeName="fill" to="red"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect><set attributeName="fill" to="red"/></rect></svg>',
        ];

        yield 'Removes mixed-case script elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><Script>a</Script><SCRIPT>b</SCRIPT><s:script xmlns:s="http://www.w3.org/2000/svg">c</s:script><SCRIPT xmlns="">d</SCRIPT><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes mixed-case event handler attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect OnClick="a" ONLOAD="b" onMouseOver="c"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes a script in a child that resets the default namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g xmlns=""><script>a</script><rect onclick="b"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g xmlns=""><rect/></g></svg>',
        ];

        yield 'Removes elements of a prefix redeclared to another namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:s="http://www.w3.org/2000/svg"><s:rect/><g xmlns:s="http://www.w3.org/1999/xhtml"><s:img src="x"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:s="http://www.w3.org/2000/svg"><s:rect/><g xmlns:s="http://www.w3.org/1999/xhtml"/></svg>',
        ];

        yield 'Removes a use element whose XLink href is a javascript: URL next to a safe href' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use href="#a" xlink:href="javascript:alert(1)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"/>',
        ];

        yield 'Removes a javascript: URL written with character references, tabs and newlines' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="&#x09;&#106;ava&#x0A;scr&#x0D;ipt&#58;alert(1)"><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>x</text></svg>',
        ];

        yield 'Removes a javascript: URL padded with no-break spaces in an animation value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a><animate attributeName="fill" values="red;&#xA0;javascript:alert(1)"/><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a><animate attributeName="fill"/><text>x</text></a></svg>',
        ];

        yield 'Removes a javascript: URL from an entity in an attribute value' => [
            '<!DOCTYPE svg [<!ENTITY j "java&#x73;cript:">]><svg xmlns="http://www.w3.org/2000/svg"><a href="&j;alert(1)"><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>x</text></svg>',
        ];

        yield 'Removes a script element from an entity with CDATA' => [
            '<!DOCTYPE svg [<!ENTITY s "&#60;script&#62;&#60;![CDATA[alert(1)]]&#62;&#60;/script&#62;">]><svg xmlns="http://www.w3.org/2000/svg"><g>&s;</g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g/></svg>',
        ];

        yield 'Removes processing instructions between every node' => [
            '<?xml-stylesheet href="x.css"?><svg xmlns="http://www.w3.org/2000/svg"><?a?><g><?b?><rect/><?c?></g><?d?></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect/></g></svg>',
        ];

        yield 'Removes unsafe comments and keeps safe ones between every node' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><!--a--><g><!--<b>--><rect/><!---->--><!--c--></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><!--a--><g><rect/><!---->--&gt;<!--c--></g></svg>',
        ];

        yield 'Removes a dangerous URL after a safe one in repeated elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><image href="a.png"/><image href="b.png"/><image href="javascript:alert(1)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><image href="a.png"/><image href="b.png"/></svg>',
        ];

        yield 'Removes a dangerous URL with right-to-left override and emoji' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="java&#x202E;script:alert(1)"><text>שלום 😀 é 𝒜</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>שלום 😀 é 𝒜</text></svg>',
        ];

        yield 'Removes a dangerous URL from a very long attribute value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="javascript:' . str_repeat('a', 10_000) . '"><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>x</text></svg>',
        ];

        yield 'Keeps a very long safe attribute value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0' . str_repeat('a', 10_000) . '"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0' . str_repeat('a', 10_000) . '"/></svg>',
        ];

        yield 'Removes a use element that references its own ancestor' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g id="g"><rect/><use href="#g"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g id="g"><rect/></g></svg>',
        ];

        yield 'Removes a use element that references itself' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><use id="u" href="#u"/><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes the use element that closes a reference cycle' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><g id="a"><use href="#b"/></g><g id="b"><use href="#a"/></g></defs><use href="#a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><g id="a"><use href="#b"/></g><g id="b"/></defs><use href="#a"/></svg>',
        ];

        yield 'Removes a padded custom-prefixed XLink use cycle through an xml:id' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:f="http://www.w3.org/1999/xlink"><g xml:id="x"><use f:href=" #x "/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:f="http://www.w3.org/1999/xlink"><g xml:id="x"/></svg>',
        ];

        yield 'Keeps use elements that reference missing or external targets' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><use href="#missing"/><use href="sprite.svg#a"/><use/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><use href="#missing"/><use href="sprite.svg#a"/><use/></svg>',
        ];

        yield 'Resolves a use element by its plain href before its XLink href' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="g"><use href="#r" xlink:href="#g"/></g><rect id="r"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="g"><use href="#r" xlink:href="#g"/></g><rect id="r"/></svg>',
        ];

        yield 'Resolves a duplicate id to its first element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="d"/><g id="d"><use href="#d"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="d"/><g id="d"><use href="#d"/></g></svg>',
        ];

        yield 'Keeps the rest of a style sheet when removing an @import' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@import url(https://fonts.googleapis.com/css?family=Roboto);.st0{fill:red}</style><rect class="st0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.st0{fill:red}</style><rect class="st0"/></svg>',
        ];

        yield 'Keeps the other declarations when removing one with a data: URI' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{background-image:url(\'data:image/svg,&lt;svg/&gt;\');fill:red}.st0{fill:blue}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}.st0{fill:blue}</style><rect class="a"/></svg>',
        ];

        yield 'Removes a dangerous @media block and keeps the other rules' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@media screen{.a{fill:url(http://evil.com/x.svg#a)}}.b{fill:red}</style><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.b{fill:red}</style><rect/></svg>',
        ];

        yield 'Removes a rule whose selector holds markup and keeps the others' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a[title="&lt;svg"]{fill:red}.b{fill:blue}</style><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.b{fill:blue}</style><rect/></svg>',
        ];

        yield 'Keeps a declaration with a semicolon in a string' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{content:"a;b";fill:url(http://evil.com/x.svg#a)}</style><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{content:"a;b";}</style><rect/></svg>',
        ];

        yield 'Removes a declaration with -o-link and keeps the others' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{-o-link:\'javascript:alert(1)\';stroke:blue}</style><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{stroke:blue}</style><rect/></svg>',
        ];

        yield 'Removes a trailing comment that holds markup' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}/* &lt;script */</style><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}</style><rect/></svg>',
        ];

        yield 'Removes an @import after a comment' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>/* fonts */ @import \'x.css\'; .a{fill:red}</style><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style> .a{fill:red}</style><rect/></svg>',
        ];

        yield 'Removes a whole style sheet that cannot be tokenized' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@import url(x.css);.a{fill:red</style><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes a whole style element with a comment child' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@import url(x.css);<!-- c -->.a{fill:red}</style><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes a whole style element of another type' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style type="text/less">@import url(x.less);.a{fill:red}</style><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes a whole style element whose remaining statements are only whitespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style> @import url(x.css); </style><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Keeps the rest of a style sheet in CDATA when removing an expression()' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style><![CDATA[.a{width:expression(alert(1));fill:red}.b>.c{fill:blue}]]></style><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}.b&gt;.c{fill:blue}</style><rect/></svg>',
        ];

        yield 'Sanitizing a style sheet twice equals sanitizing it once' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@import url(a.css);.a{fill:red;behavior:url(x.htc)}</style><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red;}</style><rect/></svg>',
        ];

        yield 'Removes a STYLE element that an HTML parser would read as style' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><STYLE>@import url(https://evil.example/x.css);</STYLE><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect/></svg>',
        ];

        yield 'Removes a Style element even when its content is safe' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><Style>rect{fill:red}</Style><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect/></svg>',
        ];

        yield 'Removes an A element with its content' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><A href="#x"><text>x</text></A><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect/></svg>',
        ];

        yield 'Removes USE and IMAGE elements written in upper case' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><USE href="#r"/><IMAGE href="a.png"/><rect id="r"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect id="r"/></svg>',
        ];

        yield 'Removes foreignObject written in another case' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><FOREIGNOBJECT/><foreignobject/><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect/></svg>',
        ];

        yield 'Removes animation elements written in another case' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect><SET attributeName="fill" to="red"/><ANIMATE attributeName="x" to="1"/><ANIMATETRANSFORM attributeName="transform"/><animatetransform attributeName="transform"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect/></svg>',
        ];

        yield 'Removes lower-case camelCase elements that HTML adjusts' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><lineargradient id="g"/><clippath id="c"/><fegaussianblur/></defs><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs/><rect/></svg>',
        ];

        yield 'Removes a prefixed SVG element written in another case' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:s="http://www.w3.org/2000/svg"><s:SCRIPT>alert(1)</s:SCRIPT><s:Rect/><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:s="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes a lower-case attributename that animates an event handler' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect><set attributename="onclick" to="alert(1)"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect/></svg>',
        ];

        yield 'Removes an upper-case ATTRIBUTENAME that animates href' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a><set ATTRIBUTENAME="HREF" to="javascript:alert(1)"/><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a><text>x</text></a></svg>',
        ];

        yield 'Removes a lower-case attributename that animates href with values' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a><animate attributename="href" values="javascript:alert(1)"/><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a><text>x</text></a></svg>',
        ];

        yield 'Removes a safe animation attribute written in another case' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect><set attributename="fill" TO="red" Begin="1s"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect><set/></rect></svg>',
        ];

        yield 'Removes HREF and Href attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use HREF="#r"/><use Href="#r"/><rect id="r"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use/><use/><rect id="r"/></svg>',
        ];

        yield 'Removes an XLink href written in upper case under another namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a XLINK:HREF="javascript:alert(1)" xmlns:XLINK="urn:x"><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xmlns:XLINK="urn:x"><text>x</text></a></svg>',
        ];

        yield 'Removes an xlink:href whose prefix is bound to another namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g xmlns:xlink="urn:x"><use xlink:href="#r"/></g><rect id="r"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g xmlns:xlink="urn:x"><use/></g><rect id="r"/></svg>',
        ];

        yield 'Keeps an XLink href written with a canonical prefix in another case' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:XLink="http://www.w3.org/1999/xlink"><use XLink:href="#r"/><rect id="r"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:XLink="http://www.w3.org/1999/xlink"><use XLink:href="#r"/><rect id="r"/></svg>',
        ];

        yield 'Removes upper-case style, src, values, to, from, by and event handler attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect STYLE="fill:red" SRC="a.png" ONLOAD="x"/><animate VALUES="0;1" TO="1" FROM="0" BY="1" attributeName="x"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect/><animate attributeName="x"/></svg>',
        ];

        yield 'Removes camelCase attributes written in lower case' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewbox="0 0 1 1"><rect pathlength="1"/><path pathLength="1" d="M0 0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect/><path pathLength="1" d="M0 0"/></svg>',
        ];

        yield 'Keeps unknown attributes and elements in any case' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect Data-X="1" myAttr="2"/><myWidget/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect Data-X="1" myAttr="2"/><myWidget/></svg>',
        ];

        yield 'Removes an XLink href with a local name in upper case' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:XLink="http://www.w3.org/1999/xlink"><use XLink:HREF="#r"/><rect id="r"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:XLink="http://www.w3.org/1999/xlink"><use/><rect id="r"/></svg>',
        ];

        yield 'Removes a javascript: URL in an XLink href with a prefix in another case' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:XLink="http://www.w3.org/1999/xlink"><a XLink:href="javascript:alert(1)"><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:XLink="http://www.w3.org/1999/xlink"><a><text>x</text></a></svg>',
        ];

        yield 'Keeps a lower-case xlink:href with an unbound prefix and removes one in another case' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><use xlink:href="#r"/><use XLINK:href="#r"/><rect id="r"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><use xlink:href="#r"/><use/><rect id="r"/></svg>',
        ];

        yield 'Keeps prefixed attributes that are not XLink or XML attributes in any case' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><rect x:Label="a" x:href="#b"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><rect x:Label="a" x:href="#b"/></svg>',
        ];

        yield 'Removes a dangerous XLink href next to a plain href with the same local name' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><feImage xlink:href="javascript:alert(1)" href="#ok"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><feImage href="#ok"/></svg>',
        ];

        yield 'Removes an animation of an event handler behind a multi-byte prefix' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><set attributeName=\"\u{E9}:onclick\" to=\"alert(1)\"/><set attributeName=\"\u{E9}\u{E9}:x\" to=\"1\"/></svg>",
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><set attributeName=\"\u{E9}\u{E9}:x\" to=\"1\"/></svg>",
        ];

        yield 'Removes a dangerous style element that comes before a safe one' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@import url(x.css);</style><style>a{fill:red}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{fill:red}</style></svg>',
        ];

        yield 'Removes a dangerous style element nested in a style element of another type before the outer one' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style type="text/x">a{}<style>@import url(x.css);</style></style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style type="text/x">a{}</style></svg>',
        ];

        yield 'Removes names that fold to known names through the Kelvin sign' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><mas\u{212A}/><rect stro\u{212A}e=\"red\"/><rect xlin\u{212A}:href=\"#r\"/><rect id=\"r\"/></svg>",
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/><rect/><rect id="r"/></svg>',
        ];

        yield 'Removes a style that folds to a dangerous property through the Kelvin sign' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><rect style=\"-o-lin\u{212A}:x\"/></svg>",
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes a prefixed attribute whose local name folds to a URL attribute through the Kelvin sign' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:x=\"urn:x\"><rect x:stro\u{212A}e=\"url(javascript:alert(1))\" x:fill=\"red\"/></svg>",
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><rect x:fill="red"/></svg>',
        ];

        yield 'Keeps an animation whose other attributes look like event handlers' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><set attributeName="fill" to="red" id="online"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><set attributeName="fill" to="red" id="online"/></svg>',
        ];

        yield 'Removes an animation whose second attributeName in another case targets an event handler' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><set attributeName="fill" ATTRIBUTENAME="onclick"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Removes a style element whose safe statements join into an import' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{fill:red}&amp;#64;import url(x.css)</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Removes a dangerous statement whose block is nested in parentheses' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{fill:red}x({})expression(1);</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{fill:red}</style></svg>',
        ];

        yield 'Removes a dangerous at-rule that follows whitespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{fill:red} @media x{a{background:url("javascript:alert(1)")}}b{fill:blue}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{fill:red}b{fill:blue}</style></svg>',
        ];

        yield 'Removes a dangerous at-rule that follows a comment' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{fill:red}/* c */@media x{a{background:url("javascript:alert(1)")}}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{fill:red}</style></svg>',
        ];

        yield 'Keeps every safe declaration around a dangerous one' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{fill:red;background:url("javascript:alert(1)");stroke:blue}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{fill:red;stroke:blue}</style></svg>',
        ];

        yield 'Removes a data URI from an element that does not allow one after an image with the same data URI' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><image href="data:image/png;base64,AAAA"/><use href="data:image/png;base64,AAAA"/><image href="data:image/png;base64,AAAA"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><image href="data:image/png;base64,AAAA"/><use/><image href="data:image/png;base64,AAAA"/></svg>',
        ];

        yield 'Removes an xlink:href bound to another namespace next to a real XLink href with the same value' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="#a"/><g xmlns:xlink="urn:x"><use xlink:href="#a"/></g><use xlink:href="#a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="#a"/><g xmlns:xlink="urn:x"><use/></g><use xlink:href="#a"/></svg>',
        ];

        yield 'Removes attributes with the same value but another name' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="javascript:alert(1)" fill="javascript:alert(1)" class="javascript:alert(1)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="javascript:alert(1)" class="javascript:alert(1)"/></svg>',
        ];

        yield 'Removes a dangerous style element with a mismatched closing bracket' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{b:c(]}b{fill:blue}@import url(x.css);</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Removes a network-path reference split by an invisible character before a CSS escape' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="/&#x200B;\evil.com"><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>x</text></svg>',
        ];

        yield 'Keeps a link that references its own element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a id="a" href="#a"><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a id="a" href="#a"><text>x</text></a></svg>',
        ];

        yield 'Removes an attribute with the same name as a safe one but another value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="red"/><rect fill="javascript:alert(1)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="red"/><rect/></svg>',
        ];

        yield 'Removes a javascript: URL spelled with a full-width capital letter' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><a href=\"j\u{FF21}vascript:alert(1)\"><text>x</text></a></svg>",
            '<svg xmlns="http://www.w3.org/2000/svg"><text>x</text></svg>',
        ];
    }

    /**
     * @throws InvalidSvgException
     */
    #[Test]
    public function removesAJavascriptUrlThatRemovingInvisibleCharactersWouldJoin(): void
    {
        $input =
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="java&#x200B;script:alert(1)"><text>x</text></a></svg>';
        $pipeline = new Pipeline([...$this->securityRules(), new RemoveInvisibleCharacters()]);

        self::assertSame(
            '<svg xmlns="http://www.w3.org/2000/svg"><text>x</text></svg>',
            $pipeline->run($input)
        );
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws InvalidSvgException
     */
    #[Test]
    public function allRulesLeaveNoJavascriptUrlSplitByInvisibleCharacters(): void
    {
        $content = (new SvgOptimizer())
            ->withRules(...Rule::cases())
            ->withRiskyRulesAllowed()
            ->optimizeString('<svg xmlns="http://www.w3.org/2000/svg"><a href="java&#x200B;script:alert(1)"><text>x</text></a><a href="java&#xAD;script:alert(2)"><text>y</text></a></svg>')
            ->getContent();

        self::assertStringNotContainsStringIgnoringCase('script:', $content);
        self::assertStringNotContainsString('href', $content);
    }

    /**
     * @throws InvalidSvgException
     */
    #[Test]
    public function sanitizingTwiceEqualsSanitizingOnce(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><style>rect{fill:red}</style><a xlink:href="#a"><rect onload="x"/></a><a href="\\\evil.com"><text>x</text></a><set attributeName="onclick" to="x"/><!-- <b> --></svg>';

        $once = new Pipeline($this->securityRules());

        $first = $once->run($svg);

        $twice = new Pipeline($this->securityRules());

        self::assertSame($first, $twice->run($first));
    }

    /**
     * @throws \ReflectionException
     */
    #[Test]
    public function decodeUrlForCheckDecodesTheMaximumValidUnicodeCodepointHexEscape(): void
    {
        $removeUnsafeUrls = new RemoveUnsafeUrls();
        $reflectionMethod = new \ReflectionMethod($removeUnsafeUrls, 'decodeUrlForCheck');

        $result = $reflectionMethod->invoke($removeUnsafeUrls, 'a\10ffffb');

        self::assertSame('a' . mb_chr(0x10_FF_FF, 'UTF-8') . 'b', $result);
    }

    /**
     * @throws \ReflectionException
     */
    #[Test]
    public function decodeUrlForCheckRejectsOneAboveTheMaximumValidUnicodeCodepointHexEscape(): void
    {
        $removeUnsafeUrls = new RemoveUnsafeUrls();
        $reflectionMethod = new \ReflectionMethod($removeUnsafeUrls, 'decodeUrlForCheck');

        $result = $reflectionMethod->invoke($removeUnsafeUrls, 'a\110000b');

        self::assertSame('ab', $result);
    }

    /**
     * @throws \ReflectionException
     */
    #[Test]
    public function decodeUrlForCheckRemovesSurrogateAndNullHexEscapes(): void
    {
        $removeUnsafeUrls = new RemoveUnsafeUrls();
        $reflectionMethod = new \ReflectionMethod($removeUnsafeUrls, 'decodeUrlForCheck');

        $result = $reflectionMethod->invoke($removeUnsafeUrls, 'w\d800x\dfffy\000000z');

        self::assertSame('wxyz', $result);
    }

    /**
     * @throws \ReflectionException
     */
    #[Test]
    public function decodeUrlForCheckDecodesHexEscapesAroundTheSurrogates(): void
    {
        $removeUnsafeUrls = new RemoveUnsafeUrls();
        $reflectionMethod = new \ReflectionMethod($removeUnsafeUrls, 'decodeUrlForCheck');

        $result = $reflectionMethod->invoke($removeUnsafeUrls, 'w\d7ffx\e000y');

        self::assertSame('w' . mb_chr(0xD7_FF, 'UTF-8') . 'x' . mb_chr(0xE0_00, 'UTF-8') . 'y', $result);
    }

    /**
     * @throws \ReflectionException
     */
    #[Test]
    public function decodeUrlForCheckDecodesHexEscapesAsMultiByteUtf8(): void
    {
        $removeUnsafeUrls = new RemoveUnsafeUrls();
        $reflectionMethod = new \ReflectionMethod($removeUnsafeUrls, 'decodeUrlForCheck');

        $result = $reflectionMethod->invoke($removeUnsafeUrls, 'a\1f600z');

        self::assertSame('a' . mb_chr(0x1_F6_00, 'UTF-8') . 'z', $result);
    }

    /**
     * @throws \ReflectionException
     */
    #[Test]
    public function unwrapDoesNothingWhenElementHasNoParent(): void
    {
        $domDocument = new \DOMDocument();
        $domElement = $domDocument->createElement('a');

        self::assertNull($domElement->parentNode);

        $removeForeignContent = new RemoveForeignContent();
        $reflectionMethod = new \ReflectionMethod($removeForeignContent, 'unwrap');

        $reflectionMethod->invoke($removeForeignContent, $domElement);

        self::assertNull($domElement->parentNode);
    }

    #[Test]
    public function expandingEntityMarkupLeavesNoLibxmlErrors(): void
    {
        $previous = libxml_use_internal_errors(true);

        try {
            $domDocument = new \DOMDocument();
            $domDocument->substituteEntities = false;
            $domDocument->loadXML('<!DOCTYPE svg [<!ENTITY m "<z:a/>">]><svg xmlns="http://www.w3.org/2000/svg"><g>&m;</g></svg>');
            libxml_clear_errors();

            $this->applySecurityRules($domDocument);

            self::assertSame([], libxml_get_errors());
            self::assertTrue(libxml_use_internal_errors(true));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /**
     * @throws ParseException
     */
    #[Test]
    public function receivesDocumentsWithoutEntityReferences(): void
    {
        $domDocument = (new Parser())->parse('<!DOCTYPE svg [<!ENTITY a "&b;"><!ENTITY b "x"><!ENTITY c "<rect/>">]><svg xmlns="http://www.w3.org/2000/svg" a="&a;"><text>&a;</text>&c;</svg>');

        $this->applySecurityRules($domDocument);

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg" a="x"><text>x</text><rect/></svg>', $domDocument->saveXML($domDocument->documentElement));
    }

    /**
     * @throws InvalidSvgException
     */
    #[Test]
    public function removesUseElementsThatWouldRenderAnExponentialNumberOfInstances(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><rect id="a0" width="1" height="1"/>';

        for ($level = 1; $level <= 9; ++$level) {
            $attribute = 0 === $level % 2 ? 'xlink:href' : 'href';
            $svg .= '<g id="a' . $level . '">' . str_repeat('<use ' . $attribute . '="#a' . ($level - 1) . '"/>', 10) . '</g>';
        }

        $content = $this->sanitize($svg . '</defs><use href="#a9"/></svg>');

        self::assertSame(71, mb_substr_count($content, '<use'));
        self::assertStringNotContainsString('"#a3"', $content);
        self::assertStringNotContainsString('"#a7"', $content);
        self::assertStringContainsString('<use href="#a9"/>', $content);
    }

    /**
     * @throws InvalidSvgException
     */
    #[Test]
    public function keepsASpriteWithHundredsOfUseElements(): void
    {
        $content = $this->sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><symbol id="s"><path d="M0 0h1"/></symbol></defs>'
            . str_repeat('<use href="#s"/>', 300) . '</svg>'
        );

        self::assertSame(300, mb_substr_count($content, '<use href="#s"/>'));
    }

    /**
     * @throws InvalidSvgException
     */
    #[Test]
    public function keepsADeepChainOfUseElementsUnderTheLimit(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><defs><rect id="c0"/>';

        for ($level = 1; $level <= 30; ++$level) {
            $svg .= '<g id="c' . $level . '"><use href="#c' . ($level - 1) . '"/></g>';
        }

        $svg .= '</defs><use href="#c30"/></svg>';

        self::assertSame($svg, $this->sanitize($svg));
    }

    /**
     * @throws InvalidSvgException
     */
    #[Test]
    public function keepsUseElementsJustUnderTheInstanceLimit(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><defs><rect id="r"/><g id="g">'
            . str_repeat('<use href="#r"/>', 999) . '</g><g id="h">' . str_repeat('<use href="#r"/>', 1_000) . '</g></defs>'
            . '<use href="#g"/><use href="#h"/></svg>';

        $content = $this->sanitize($svg);

        self::assertStringContainsString('<use href="#g"/>', $content);
        self::assertStringNotContainsString('<use href="#h"/>', $content);
    }

    /**
     * @throws InvalidSvgException
     */
    #[Test]
    public function aRemovedUseElementDoesNotLowerTheInstancesOfItsAncestors(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><defs><rect id="r"/><g id="h">'
            . str_repeat('<use href="#r"/>', 1_000) . '</g><g id="g"><use href="#h"/>' . str_repeat('<use href="#r"/>', 1_000) . '</g></defs>'
            . '<use href="#g"/></svg>';

        $content = $this->sanitize($svg);

        self::assertStringNotContainsString('<use href="#h"/>', $content);
        self::assertStringNotContainsString('<use href="#g"/>', $content);
        self::assertSame(2_000, mb_substr_count($content, '<use href="#r"/>'));
    }

    /**
     * @throws \ReflectionException
     */
    #[Test]
    public function removeUseBombsIgnoresADocumentWithoutRootElement(): void
    {
        $domDocument = new \DOMDocument();

        $removeUseBombs = new RemoveUseBombs();
        (new \ReflectionMethod($removeUseBombs, 'removeUseBombs'))->invoke($removeUseBombs, $domDocument);

        self::assertNull($domDocument->documentElement);
    }

    /**
     * @param \Closure(int): string $build builds a document with the given number of repeated parts
     */
    #[DataProvider('provideSanitizesLargeDocumentsInLinearTimeCases')]
    #[Test]
    public function sanitizesLargeDocumentsInLinearTime(\Closure $build): void
    {
        $small = $this->secondsToSanitize($build(2_500));
        $large = $this->secondsToSanitize($build(10_000));

        self::assertLessThan(10 * max($small, 0.05), $large);
    }

    /**
     * @return iterable<string, array{\Closure(int): string}>
     */
    public static function provideSanitizesLargeDocumentsInLinearTimeCases(): iterable
    {
        yield 'many gradients referenced by many shapes' => [
            static fn (int $count): string => '<svg xmlns="http://www.w3.org/2000/svg"><defs>'
                . implode('', array_map(static fn (int $i): string => '<linearGradient id="g' . $i . '"><stop offset="0" stop-color="#' . $i . '"/></linearGradient>', range(1, $count)))
                . '</defs>' . implode('', array_map(static fn (int $i): string => '<rect fill="url(#g' . $i . ')"/>', range(1, $count))) . '</svg>',
        ];

        yield 'many nested groups' => [
            static fn (int $count): string => '<svg xmlns="http://www.w3.org/2000/svg">' . str_repeat('<g fill="red"><g><rect width="1" height="1"/><circle r="1"/></g></g>', $count) . '</svg>',
        ];

        yield 'many attributes on one element' => [
            static fn (int $count): string => '<svg xmlns="http://www.w3.org/2000/svg"><rect ' . implode(' ', array_map(static fn (int $i): string => 'a' . $i . '="' . $i . '"', range(1, $count))) . '/></svg>',
        ];

        yield 'many namespace declarations' => [
            static fn (int $count): string => '<svg xmlns="http://www.w3.org/2000/svg" ' . implode(' ', array_map(static fn (int $i): string => 'xmlns:n' . $i . '="urn:' . $i . '"', range(1, $count))) . '><rect/></svg>',
        ];

        yield 'many use elements' => [
            static fn (int $count): string => '<svg xmlns="http://www.w3.org/2000/svg"><defs><rect id="r"/>'
                . implode('', array_map(static fn (int $i): string => '<g id="u' . $i . '"><use href="#' . (1 === $i ? 'r' : 'u' . ($i - 1)) . '"/></g>', range(1, $count)))
                . '</defs></svg>',
        ];
    }

    private function secondsToSanitize(string $svg): float
    {
        self::assertNotSame('', $svg);

        $domDocument = new \DOMDocument();
        $domDocument->loadXML($svg);

        $start = hrtime(true);
        $this->applySecurityRules($domDocument);

        return (hrtime(true) - $start) / 1e9;
    }

    /**
     * @throws InvalidSvgException
     */
    private function sanitize(string $svg): string
    {
        $pipeline = new Pipeline($this->securityRules());

        return $pipeline->run($svg);
    }

    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideLeavesNothingDangerousWhenTheOutputIsParsedAsHtmlCases')]
    #[Test]
    public function leavesNothingDangerousWhenTheOutputIsParsedAsHtml(string $svg): void
    {
        $sanitized = $this->sanitize($svg);
        self::assertNotSame('', $sanitized);

        $domDocument = new \DOMDocument();
        $domDocument->loadXML($sanitized);

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            $tagName = mb_strtolower($domNodeList->nodeName);

            self::assertNotContains($tagName, ['script', 'foreignobject', 'iframe', 'handler', 'listener']);

            if ('style' === $tagName) {
                self::assertStringNotContainsStringIgnoringCase('@import', $domNodeList->textContent);
            }

            foreach ($domNodeList->attributes as $domAttr) {
                self::assertFalse($this->isDangerousForHtml($tagName, mb_strtolower($domAttr->nodeName), mb_strtolower($domAttr->value)), $domAttr->nodeName);
            }
        }
    }

    /**
     * @return iterable<array{string}>
     */
    public static function provideLeavesNothingDangerousWhenTheOutputIsParsedAsHtmlCases(): iterable
    {
        yield ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><STYLE>@import url(https://evil.example/x.css);</STYLE><rect/></svg>'];
        yield ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><Style>rect{fill:red}</Style><rect/></svg>'];
        yield ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><A href="#x"><text>x</text></A><rect/></svg>'];
        yield ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><USE href="#r"/><IMAGE href="a.png"/><rect id="r"/></svg>'];
        yield ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><FOREIGNOBJECT/><foreignobject/><rect/></svg>'];
        yield ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect><SET attributeName="fill" to="red"/><ANIMATE attributeName="x" to="1"/><ANIMATETRANSFORM attributeName="transform"/><animatetransform attributeName="transform"/></rect></svg>'];
        yield ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><lineargradient id="g"/><clippath id="c"/><fegaussianblur/></defs><rect/></svg>'];
        yield ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:s="http://www.w3.org/2000/svg"><s:SCRIPT>alert(1)</s:SCRIPT><s:Rect/><rect/></svg>'];
        yield ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect><set attributename="onclick" to="alert(1)"/></rect></svg>'];
        yield ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a><set ATTRIBUTENAME="HREF" to="javascript:alert(1)"/><text>x</text></a></svg>'];
        yield ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a><animate attributename="href" values="javascript:alert(1)"/><text>x</text></a></svg>'];
        yield ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect><set attributename="fill" TO="red" Begin="1s"/></rect></svg>'];
        yield ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use HREF="#r"/><use Href="#r"/><rect id="r"/></svg>'];
        yield ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a XLINK:HREF="javascript:alert(1)" xmlns:XLINK="urn:x"><text>x</text></a></svg>'];
        yield ['<svg xmlns="http://www.w3.org/2000/svg"><g xmlns:xlink="urn:x"><use xlink:href="#r"/></g><rect id="r"/></svg>'];
        yield ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:XLink="http://www.w3.org/1999/xlink"><use XLink:href="#r"/><rect id="r"/></svg>'];
        yield ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect STYLE="fill:red" SRC="a.png" ONLOAD="x"/><animate VALUES="0;1" TO="1" FROM="0" BY="1" attributeName="x"/></svg>'];
        yield ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewbox="0 0 1 1"><rect pathlength="1"/><path pathLength="1" d="M0 0"/></svg>'];
        yield ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect Data-X="1" myAttr="2"/><myWidget/></svg>'];
        yield ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:XLink="http://www.w3.org/1999/xlink"><use XLink:HREF="#r"/><rect id="r"/></svg>'];
        yield ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:XLink="http://www.w3.org/1999/xlink"><a XLink:href="javascript:alert(1)"><text>x</text></a></svg>'];
        yield ['<svg xmlns="http://www.w3.org/2000/svg"><use xlink:href="#r"/><use XLINK:href="#r"/><rect id="r"/></svg>'];
        yield ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a HREF="javascript:alert(1)"><text>x</text></a></svg>'];
        yield ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect ONCLICK="alert(1)" OnMouseOver="alert(2)"/></svg>'];
        yield ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><image SRC="javascript:alert(1)"/></svg>'];
        yield ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><SCRIPT>alert(1)</SCRIPT><Script>alert(2)</Script></svg>'];
        yield ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a><ANIMATE attributeName="href" values="javascript:alert(1)"/><text>x</text></a></svg>'];
        yield ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect STYLE="background:url(https://evil.example/t)"/></svg>'];
        yield ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a><set attributeName="ONCLICK" to="alert(1)"/><set attributename="xlink:HREF" to="javascript:alert(1)"/><text>x</text></a></svg>'];
    }

    private function isDangerousForHtml(string $tagName, string $attributeName, string $value): bool
    {
        $isAnimation = \in_array($tagName, ['set', 'animate', 'animatetransform', 'animatemotion', 'animatecolor'], true);

        return str_starts_with($attributeName, 'on')
            || (\in_array($attributeName, ['href', 'xlink:href', 'src', 'style', 'to', 'values', 'from', 'by'], true) && (str_contains($value, 'javascript:') || str_contains($value, 'https:')))
            || ($isAnimation && 'attributename' === $attributeName && (str_starts_with($value, 'on') || str_contains($value, 'href')));
    }

    #[Test]
    public function securityRulesRunInTheirExecutionOrder(): void
    {
        self::assertSame(
            array_map(static fn (PassInterface $pass): string => $pass::class, $this->securityRules()),
            \array_slice(ExecutionOrder::passes(), 0, 6),
        );
    }

    /**
     * @return list<PassInterface> the security rules in their execution order
     */
    private function securityRules(): array
    {
        return [new RemoveUnsafeMarkup(), new RemoveScripts(), new RemoveForeignContent(), new RemoveUnsafeUrls(), new RemoveUnsafeStyles(), new RemoveUseBombs()];
    }

    private function applySecurityRules(\DOMDocument $domDocument): void
    {
        foreach ($this->securityRules() as $pass) {
            $pass->apply($domDocument);
        }
    }
}
