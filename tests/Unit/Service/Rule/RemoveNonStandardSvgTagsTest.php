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
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\removeNonStandardSvgTags;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(removeNonStandardSvgTags::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(XmlFormatter::class)]
final class RemoveNonStandardSvgTagsTest extends TestCase
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
        $svgOptimizer->addRule(new removeNonStandardSvgTags());

        $actual = $svgOptimizer->allowRisky()->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Removes single unknown tag' => [
            '<svg><unknown/></svg>',
            '<svg/>',
        ];

        yield 'Removes nested unknown tags' => [
            '<svg><g><foo><rect width="10" height="10"/></foo></g></svg>',
            '<svg><g><rect width="10" height="10"/></g></svg>',
        ];

        yield 'Removes multiple unknown tags at root' => [
            '<svg><bad/><worse/><rect width="5" height="5"/></svg>',
            '<svg><rect width="5" height="5"/></svg>',
        ];

        yield 'Keeps standard tags intact' => [
            '<svg><rect width="10" height="10"/><circle cx="5" cy="5" r="3"/></svg>',
            '<svg><rect width="10" height="10"/><circle cx="5" cy="5" r="3"/></svg>',
        ];

        yield 'Keep dangerous tags' => [
            '<svg><script>alert(1)</script><rect width="5" height="5"/></svg>',
            '<svg><script>alert(1)</script><rect width="5" height="5"/></svg>',
        ];

        yield 'Removes unknown tags mixed with standard tags' => [
            '<svg><rect width="10" height="10"/><foo/><circle cx="5" cy="5" r="3"/></svg>',
            '<svg><rect width="10" height="10"/><circle cx="5" cy="5" r="3"/></svg>',
        ];

        yield 'Handles empty SVG' => [
            '<svg></svg>',
            '<svg/>',
        ];

        yield 'Removes unknown tags in defs' => [
            '<svg><defs><bad><rect width="5" height="5"/></bad></defs></svg>',
            '<svg><defs><rect width="5" height="5"/></defs></svg>',
        ];

        yield 'Removes multiple nested unknown tags' => [
            '<svg><g><foo><bar><rect width="10" height="10"/></bar></foo></g></svg>',
            '<svg><g><rect width="10" height="10"/></g></svg>',
        ];

        yield 'Ignores text nodes' => [
            '<svg>Hello<rect width="5" height="5"/>World</svg>',
            '<svg>Hello<rect width="5" height="5"/>World</svg>',
        ];
    }
}
