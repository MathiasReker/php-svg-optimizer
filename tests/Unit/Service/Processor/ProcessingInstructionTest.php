<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Processor;

use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Service\Optimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Processor\Trait\TextWhitespaceTrait;
use MathiasReker\PhpSvgOptimizer\Service\Processor\Trait\XmlLoaderTrait;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnsafeMarkup;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementListTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\EntityReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
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
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversTrait(EntityReferenceTrait::class)]
#[CoversTrait(TextWhitespaceTrait::class)]
#[CoversTrait(XmlLoaderTrait::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(RemoveUnsafeMarkup::class)]
#[UsesTrait(ElementListTrait::class)]
final class ProcessingInstructionTest extends TestCase
{
    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[DataProvider('provideKeepsStylesheetInstructionsCases')]
    #[Test]
    public function keepsStylesheetInstructions(string $content, string $expected): void
    {
        self::assertSame($expected, (new SvgOptimizer(new StringProvider($content)))->optimize()->getContent());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideKeepsStylesheetInstructionsCases(): iterable
    {
        yield 'Keeps a stylesheet instruction before the document element' => [
            '<?xml version="1.0"?><?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"/>',
            '<?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Keeps every stylesheet instruction in order, in any case' => [
            '<?xml-stylesheet href="a.css"?><?XML-STYLESHEET href="b.css"?><svg xmlns="http://www.w3.org/2000/svg"/>',
            '<?xml-stylesheet href="a.css"?><?XML-STYLESHEET href="b.css"?><svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Drops other processing instructions before the document element' => [
            '<?xpacket begin=""?><?xml-stylesheet href="a.css"?><?xpacket end="w"?><svg xmlns="http://www.w3.org/2000/svg"/>',
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Writes nothing extra without stylesheet instructions' => [
            '<!-- c --><svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[Test]
    public function sanitizerStillRemovesStylesheetInstructions(): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider('<?xml-stylesheet href="https://evil.test/a.css"?><svg xmlns="http://www.w3.org/2000/svg"/>'));
        $svgOptimizer->addPass(new RemoveUnsafeMarkup());

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"/>', $svgOptimizer->optimize()->getContent());
    }
}
