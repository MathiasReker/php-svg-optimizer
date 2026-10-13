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
use MathiasReker\PhpSvgOptimizer\Service\Optimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveStyleTypeAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveStyleTypeAttributes::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class RemoveStyleTypeAttributesTest extends TestCase
{
    private const array SCRIPT_TYPES = [
        'application/ecmascript',
        'application/javascript',
        'application/x-ecmascript',
        'application/x-javascript',
        'text/ecmascript',
        'text/javascript',
        'text/javascript1.0',
        'text/javascript1.1',
        'text/javascript1.2',
        'text/javascript1.3',
        'text/javascript1.4',
        'text/javascript1.5',
        'text/jscript',
        'text/livescript',
        'text/x-ecmascript',
        'text/x-javascript',
    ];

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
        $svgOptimizer->addRule(new RemoveStyleTypeAttributes());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Removes text/css from style' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style type="text/css">rect{fill:red}</style><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><rect/></svg>',
        ];

        yield 'Removes text/css from style ignoring case and surrounding whitespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style type="  TEXT/Css "/><style type="text/CSS"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style/><style/></svg>',
        ];

        yield 'Keeps style types with parameters' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style type="text/css; charset=utf-8"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style type="text/css; charset=utf-8"/></svg>',
        ];

        yield 'Keeps other style types' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style type="text/less"/><style type="text/javascript"/><style type="text/c ss"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style type="text/less"/><style type="text/javascript"/><style type="text/c ss"/></svg>',
        ];

        yield 'Keeps empty style type' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style type=""/><style type=" "/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style type=""/><style type=" "/></svg>',
        ];

        foreach (self::SCRIPT_TYPES as $scriptType) {
            yield 'Removes ' . $scriptType . ' from script' => [
                '<svg xmlns="http://www.w3.org/2000/svg"><script type="' . $scriptType . '">alert(1)</script></svg>',
                '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>',
            ];

            yield 'Removes ' . $scriptType . ' from script ignoring case and surrounding whitespace' => [
                '<svg xmlns="http://www.w3.org/2000/svg"><script type=" ' . mb_strtoupper($scriptType) . ' "/></svg>',
                '<svg xmlns="http://www.w3.org/2000/svg"><script/></svg>',
            ];

            yield 'Keeps ' . $scriptType . ' on style' => [
                '<svg xmlns="http://www.w3.org/2000/svg"><style type="' . $scriptType . '"/></svg>',
                '<svg xmlns="http://www.w3.org/2000/svg"><style type="' . $scriptType . '"/></svg>',
            ];
        }

        yield 'Keeps text/css on script' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><script type="text/css"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><script type="text/css"/></svg>',
        ];

        yield 'Keeps module scripts' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><script type="module">import a from "./a.js"</script></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><script type="module">import a from "./a.js"</script></svg>',
        ];

        yield 'Keeps other script types' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><script type="text/javascript1.6"/><script type="application/json"/><script type="text/javascript; charset=utf-8"/><script type="importmap"/><script type="text/plain"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><script type="text/javascript1.6"/><script type="application/json"/><script type="text/javascript; charset=utf-8"/><script type="importmap"/><script type="text/plain"/></svg>',
        ];

        yield 'Keeps empty script type' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><script type=""/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><script type=""/></svg>',
        ];

        yield 'Keeps type on other elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><animateTransform type="rotate"/><feColorMatrix type="saturate"/><a type="text/css"/><stylesheet type="text/css"/><scripts type="text/javascript"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><animateTransform type="rotate"/><feColorMatrix type="saturate"/><a type="text/css"/><stylesheet type="text/css"/><scripts type="text/javascript"/></svg>',
        ];

        yield 'Keeps style elements with a different case' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><STYLE type="text/css"/><Script type="text/javascript"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><STYLE type="text/css"/><Script type="text/javascript"/></svg>',
        ];

        yield 'Keeps style and script without type' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><script>b()</script></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><script>b()</script></svg>',
        ];

        yield 'Removes types in documents without a namespace' => [
            '<svg><style type="text/css"/><script type="text/ecmascript"/></svg>',
            '<svg><style/><script/></svg>',
        ];

        yield 'Removes types on XHTML elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><style xmlns="http://www.w3.org/1999/xhtml" type="text/css"/><h:script xmlns:h="http://www.w3.org/1999/xhtml" type="text/javascript"/></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><style xmlns="http://www.w3.org/1999/xhtml"/><h:script xmlns:h="http://www.w3.org/1999/xhtml"/></foreignObject></svg>',
        ];

        yield 'Keeps types on elements from other namespaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:c="http://www.customnamespace.com"><c:style type="text/css"/><c:script type="text/javascript"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:c="http://www.customnamespace.com"><c:style type="text/css"/><c:script type="text/javascript"/></svg>',
        ];

        yield 'Removes types after elements from other namespaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:c="http://www.customnamespace.com"><c:style type="text/css"/><style type="text/css"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:c="http://www.customnamespace.com"><c:style type="text/css"/><style/></svg>',
        ];

        yield 'Keeps namespaced type attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:c="http://www.customnamespace.com"><style c:type="text/css"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:c="http://www.customnamespace.com"><style c:type="text/css"/></svg>',
        ];

        yield 'Removes types on nested elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><style type="text/css"/></defs><g><g><script type="application/javascript"/></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><style/></defs><g><g><script/></g></g></svg>',
        ];
    }

    #[Test]
    public function ruleIsMarkedAsNotRisky(): void
    {
        self::assertFalse(RemoveStyleTypeAttributes::isRisky());
    }

    #[Test]
    public function shouldCheckSizeReturnsFalse(): void
    {
        self::assertFalse(RemoveStyleTypeAttributes::shouldCheckSize());
    }
}
