<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Exception\SvgValidationException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Model\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Formatter\XmlFormatter;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\AbstractProvider;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDeprecatedAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveDeprecatedAttributes::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(AbstractProvider::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(XmlFormatter::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(SvgNamespace::class)]
final class RemoveDeprecatedAttributesTest extends TestCase
{
    /**
     * @throws SvgValidationException
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     */
    #[DataProvider('provideOptimizeCases')]
    #[Test]
    public function optimize(string $content, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($content));
        $svgOptimizer->addRule(new RemoveDeprecatedAttributes());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Removes baseProfile attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" baseProfile="tiny">
                    <rect width="100" height="100" fill="blue"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="blue"/></svg>
                XML,
        ];

        yield 'Removes zoomAndPan attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" zoomAndPan="disable">
                    <rect width="100" height="100" fill="red"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="red"/></svg>
                XML,
        ];

        yield 'Removes requiredFeatures attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" requiredFeatures="http://www.w3.org/TR/SVG11/feature#BasicStructure">
                    <rect width="100" height="100" fill="green"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="green"/></svg>
                XML,
        ];

        yield 'Removes version attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" version="1.1">
                    <rect width="100" height="100" fill="yellow"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="yellow"/></svg>
                XML,
        ];

        yield 'Replaces xlink:href with href' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <use xlink:href="#icon" />
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><use href="#icon"/></svg>
                XML,
        ];

        yield 'Replaces xlink:title with title' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <image xlink:title="Image Title" href="image.png"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><image href="image.png" title="Image Title"/></svg>
                XML,
        ];

        yield 'Removes xmlns:xlink namespace' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <rect width="100" height="100" fill="pink"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="pink"/></svg>
                XML,
        ];

        yield 'Handles combination of attributes and xlink' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" baseProfile="full" version="1.1">
                    <use xlink:href="#icon" zoomAndPan="disable" />
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><use href="#icon"/></svg>
                XML,
        ];

        yield 'Does not remove non-deprecated attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect fill="purple"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect fill="purple"/></svg>
                XML,
        ];

        yield 'Handles attributes with namespaces correctly' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <use xlink:href="image.svg"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><use href="image.svg"/></svg>
                XML,
        ];

        yield 'Handles no xlink attributes present' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="200" height="200">
                    <circle cx="100" cy="100" r="50" fill="orange"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><circle cx="100" cy="100" r="50" fill="orange"/></svg>
                XML,
        ];

        yield 'Removes xlink attributes in mixed content' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="300" height="300">
                    <circle cx="100" cy="100" r="50" fill="green"/>
                    <use xlink:href="#someIcon" />
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="300" height="300"><circle cx="100" cy="100" r="50" fill="green"/><use href="#someIcon"/></svg>
                XML,
        ];

        yield 'Does not replace if new attribute value is the same' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <use xlink:href="#icon" href="#icon"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><use href="#icon"/></svg>
                XML,
        ];

        yield 'Adobe Illustrator tiny base profile example' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" width="500" height="180" baseProfile="tiny" version="1.2"><switch><foreignObject width="1" height="1" x="0" y="0" requiredExtensions="http://ns.adobe.com/AdobeIllustrator/10.0/"/><g><g fill="#3AB879"><path d=""/></g></g></switch></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" width="500" height="180"><switch><foreignObject width="1" height="1" x="0" y="0" requiredExtensions="http://ns.adobe.com/AdobeIllustrator/10.0/"/><g><g fill="#3AB879"><path d=""/></g></g></switch></svg>
                XML,
        ];

        yield 'Leaves unrelated attributes intact' => [
            '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle fill="red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle fill="red"/></svg>',
        ];

        yield 'Empty SVG' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'SVG with no deprecated attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'SVG with all deprecated attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg" baseProfile="full" contentScriptType="text/ecmascript" contentStyleType="text/css" version="1.1" zoomAndPan="magnify"><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Replaces xml:lang with lang' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xml:lang="en"><text>Hello</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" lang="en"><text>Hello</text></svg>',
        ];
    }

    /**
     * @throws \ReflectionException
     */
    #[Test]
    public function replaceAttributesSkipsNonDomElementNodes(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg"><text>Some text</text></svg>');

        $domXPath = new \DOMXPath($domDocument);
        $domXPath->registerNamespace('xlink', 'http://www.w3.org/1999/xlink');

        $removeDeprecatedAttributes = new RemoveDeprecatedAttributes();

        $reflectionMethod = new \ReflectionMethod($removeDeprecatedAttributes, 'replaceAttributes');

        $attributes = ['xlink:href' => 'href'];

        $reflectionMethod->invoke($removeDeprecatedAttributes, $domXPath, $attributes);

        $xmlString = $domDocument->saveXML($domDocument->documentElement);
        self::assertNotFalse($xmlString, 'Failed to serialize XML');
        self::assertXmlStringEqualsXmlString(
            '<svg xmlns="http://www.w3.org/2000/svg"><text>Some text</text></svg>',
            $xmlString
        );
    }

    /**
     * @throws \ReflectionException
     */
    #[Test]
    public function removeAttributesSkipsNonDomElementNodes(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg"><text>Some text</text></svg>');

        $domXPath = new \DOMXPath($domDocument);

        $removeDeprecatedAttributes = new RemoveDeprecatedAttributes();
        $reflectionMethod = new \ReflectionMethod($removeDeprecatedAttributes, 'removeAttributes');

        $reflectionMethod->invoke($removeDeprecatedAttributes, $domXPath, ['baseProfile']);

        $xmlString = $domDocument->saveXML($domDocument->documentElement);
        self::assertNotFalse($xmlString, 'Failed to serialize XML');
        self::assertXmlStringEqualsXmlString(
            '<svg xmlns="http://www.w3.org/2000/svg"><text>Some text</text></svg>',
            $xmlString
        );
    }

    /**
     * @throws \ReflectionException
     */
    #[Test]
    public function removeNamespaceFromSvgTagsRemovesXlinkNamespace(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"></svg>');

        $removeDeprecatedAttributes = new RemoveDeprecatedAttributes();
        $reflectionMethod = new \ReflectionMethod($removeDeprecatedAttributes, 'removeNamespaceFromSvgTags');

        $reflectionMethod->invoke($removeDeprecatedAttributes, $domDocument);

        $root = $domDocument->documentElement;
        self::assertNotNull($root);
        self::assertFalse($root->hasAttribute('xmlns:xlink'));
    }

    /**
     * @throws \ReflectionException
     */
    #[Test]
    public function removeNamespaceFromSvgTagsDoesNothingIfNoXlink(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $removeDeprecatedAttributes = new RemoveDeprecatedAttributes();
        $reflectionMethod = new \ReflectionMethod($removeDeprecatedAttributes, 'removeNamespaceFromSvgTags');

        $reflectionMethod->invoke($removeDeprecatedAttributes, $domDocument);

        $root = $domDocument->documentElement;
        self::assertNotNull($root);
        self::assertFalse($root->hasAttribute('xmlns:xlink'));
    }

    /**
     * @throws \ReflectionException
     */
    #[Test]
    public function removeAttributesDoesNothingWhenAttributeListIsEmpty(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg" baseProfile="tiny"><rect/></svg>');

        $domXPath = new \DOMXPath($domDocument);

        $before = $domDocument->saveXML();

        $removeDeprecatedAttributes = new RemoveDeprecatedAttributes();
        $reflectionMethod = new \ReflectionMethod($removeDeprecatedAttributes, 'removeAttributes');

        $reflectionMethod->invoke($removeDeprecatedAttributes, $domXPath, []);

        self::assertSame($before, $domDocument->saveXML());
    }
}
