<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Pass;

use MathiasReker\PhpSvgOptimizer\Exception\InvalidSvgException;
use MathiasReker\PhpSvgOptimizer\Pass\ConvertInlineStylesToAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Pass\PassInterface;
use MathiasReker\PhpSvgOptimizer\Pass\Pipeline;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveComments;
use MathiasReker\PhpSvgOptimizer\Pass\RevertIfLargerPassInterface;
use MathiasReker\PhpSvgOptimizer\Pass\SortAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AttributeNodeTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Xml\ParseException;
use MathiasReker\PhpSvgOptimizer\Xml\Parser;
use MathiasReker\PhpSvgOptimizer\Xml\Serializer;
use MathiasReker\PhpSvgOptimizer\Xml\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Pipeline::class)]
#[CoversClass(Parser::class)]
#[CoversClass(Serializer::class)]
#[CoversClass(SvgValidator::class)]
#[UsesClass(ConvertInlineStylesToAttributes::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(RemoveComments::class)]
#[UsesClass(SortAttributes::class)]
#[UsesTrait(AttributeNodeTrait::class)]
#[UsesTrait(ElementTrait::class)]
#[UsesTrait(StyleSheetTrait::class)]
final class PipelineTest extends TestCase
{
    #[Test]
    public function getPassesListsThePassesInExecutionOrder(): void
    {
        $sortAttributes = new SortAttributes();
        $removeComments = new RemoveComments();

        self::assertSame([$removeComments, $sortAttributes], (new Pipeline([$sortAttributes, $removeComments]))->getPasses());
    }

    #[Test]
    public function getPassesIsEmptyWithoutPasses(): void
    {
        self::assertSame([], (new Pipeline([]))->getPasses());
    }

    /**
     * @throws InvalidSvgException
     */
    #[Test]
    public function runWithoutPassesOnlyParsesAndSerializes(): void
    {
        self::assertSame(
            '<svg xmlns="http://www.w3.org/2000/svg"><!-- c --><rect/></svg>',
            (new Pipeline([]))->run("<svg xmlns=\"http://www.w3.org/2000/svg\">\n  <!-- c -->\n  <rect/>\n</svg>\n"),
        );
    }

    #[Test]
    public function runRejectsADocumentElementThatIsNotSvg(): void
    {
        try {
            (new Pipeline([]))->run('<html xmlns="http://www.w3.org/1999/xhtml"/>');
            self::fail('Expected InvalidSvgException was not thrown.');
        } catch (InvalidSvgException $invalidSvgException) {
            self::assertSame('The file does not appear to be a valid SVG file.', $invalidSvgException->getMessage());
            self::assertNotInstanceOf(ParseException::class, $invalidSvgException->getPrevious());
        }
    }

    #[Test]
    public function runKeepsTheReasonWhenTheInputIsNotWellFormed(): void
    {
        try {
            (new Pipeline([]))->run('<svg>');
            self::fail('Expected InvalidSvgException was not thrown.');
        } catch (InvalidSvgException $invalidSvgException) {
            self::assertSame('The file does not appear to be a valid SVG file.', $invalidSvgException->getMessage());
            self::assertSame(0, $invalidSvgException->getCode());
            self::assertInstanceOf(ParseException::class, $invalidSvgException->getPrevious());
        }
    }

    /**
     * @param non-empty-string $content
     *
     * @throws InvalidSvgException
     */
    #[DataProvider('provideRunRejectsInputThatIsNotASvgDocumentCases')]
    #[Test]
    public function runRejectsInputThatIsNotASvgDocument(string $content): void
    {
        $this->expectException(InvalidSvgException::class);

        (new Pipeline([]))->run($content);
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function provideRunRejectsInputThatIsNotASvgDocumentCases(): iterable
    {
        yield 'whitespace only' => [" \n\t"];

        yield 'byte order mark only' => ["\xEF\xBB\xBF"];

        yield 'truncated XML' => ['<svg xmlns="http://www.w3.org/2000/svg"><rect'];

        yield 'html root' => ['<html xmlns="http://www.w3.org/1999/xhtml"/>'];

        yield 'svg in the wrong namespace' => ['<svg xmlns="urn:x"/>'];

        yield 'plain text' => ['not xml'];
    }

    /**
     * @throws InvalidSvgException
     */
    #[Test]
    public function runsPassesInExecutionOrderWhateverTheirOrderInTheList(): void
    {
        $pipeline = new Pipeline([new SortAttributes(), new RemoveComments()]);

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"><rect a="1" b="2"/></svg>', $pipeline->run('<svg xmlns="http://www.w3.org/2000/svg"><!-- c --><rect b="2" a="1"/></svg>'));
    }

    /**
     * @throws InvalidSvgException
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

        $pipeline = new Pipeline([$styleRule, new ConvertInlineStylesToAttributes()]);

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg" style="fill:red"/>', $pipeline->run('<svg xmlns="http://www.w3.org/2000/svg"/>'));
    }

    /**
     * @throws InvalidSvgException
     */
    #[Test]
    public function revertsAPassThatMakesTheDocumentLarger(): void
    {
        $growingRule = new class implements RevertIfLargerPassInterface {
            #[\Override]
            public function apply(\DOMDocument $domDocument): void
            {
                $domDocument->documentElement?->setAttribute('data-padding', 'makes-the-serialized-content-longer');
            }
        };

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>', (new Pipeline([$growingRule]))->run('<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>'));
    }

    /**
     * @throws InvalidSvgException
     */
    #[Test]
    public function revertingAPassRestoresTheDocumentAsLoaded(): void
    {
        $content = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n<!DOCTYPE svg [<!ATTLIST rect fill CDATA \"red\">]>\n<svg xmlns=\"http://www.w3.org/2000/svg\"><text>\xE9 <tspan>a</tspan>\n<tspan>b</tspan></text><rect/></svg>";

        self::assertSame($this->documentSeenAfter($content, false), $this->documentSeenAfter($content, true));
    }

    /**
     * @throws InvalidSvgException
     */
    #[Test]
    public function revertingAPassKeepsXmlAttributesAfterAnElementWasCloned(): void
    {
        $pipeline = new Pipeline([
            new class implements PassInterface {
                #[\Override]
                public function apply(\DOMDocument $domDocument): void
                {
                    $domDocument->getElementsByTagName('text')->item(0)?->cloneNode(false);
                }
            },
            new class implements RevertIfLargerPassInterface {
                #[\Override]
                public function apply(\DOMDocument $domDocument): void
                {
                    $domDocument->documentElement?->setAttribute('data-grow', 'x');
                }
            },
        ]);

        self::assertSame(
            '<svg xmlns="http://www.w3.org/2000/svg"><text xml:space="preserve">a  b</text></svg>',
            $pipeline->run('<svg xmlns="http://www.w3.org/2000/svg"><text xml:space="preserve">a  b</text></svg>'),
        );
    }

    /**
     * @throws InvalidSvgException
     */
    #[Test]
    public function revertsOnlyThePassThatMadeTheDocumentLarger(): void
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

        $pipeline = new Pipeline([$growingRule, $checkedRule, $growingRule, $checkedRule]);

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg" fill="red"/>', $pipeline->run('<svg xmlns="http://www.w3.org/2000/svg"/>'));
    }

    /**
     * @throws InvalidSvgException
     */
    #[Test]
    public function keepsACheckedPassThatMakesTheDocumentSmaller(): void
    {
        $shrinkingRule = new class implements RevertIfLargerPassInterface {
            #[\Override]
            public function apply(\DOMDocument $domDocument): void
            {
                $domDocument->documentElement?->removeAttribute('width');
            }
        };

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"/>', (new Pipeline([$shrinkingRule]))->run('<svg xmlns="http://www.w3.org/2000/svg" width="1"/>'));
    }

    /**
     * @throws InvalidSvgException
     */
    #[Test]
    public function keepsACheckedPassThatKeepsTheSize(): void
    {
        $sameSizeRule = new class implements RevertIfLargerPassInterface {
            #[\Override]
            public function apply(\DOMDocument $domDocument): void
            {
                $domDocument->documentElement?->setAttribute('width', '2');
            }
        };

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg" width="2"/>', (new Pipeline([$sameSizeRule]))->run('<svg xmlns="http://www.w3.org/2000/svg" width="1"/>'));
    }

    /**
     * @throws InvalidSvgException
     */
    #[Test]
    public function revertingAPassDoesNotEmitPhpWarnings(): void
    {
        $growingRule = new class implements RevertIfLargerPassInterface {
            #[\Override]
            public function apply(\DOMDocument $domDocument): void
            {
                $domDocument->documentElement?->setAttribute('data-padding', 'makes-the-serialized-content-longer');
            }
        };

        $warnings = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$warnings): bool {
            $warnings[] = $errstr;

            return true;
        });

        try {
            $content = (new Pipeline([$growingRule]))->run('<svg><use xlink:href="#a"/></svg>');
        } finally {
            restore_error_handler();
        }

        self::assertSame([], $warnings);
        self::assertStringNotContainsString('data-padding', $content);
    }

    /**
     * @throws InvalidSvgException
     */
    #[Test]
    public function writesLatin1InputAsUtf8WithoutAnXmlDeclaration(): void
    {
        self::assertSame(
            '<svg xmlns="http://www.w3.org/2000/svg"><text>café</text></svg>',
            (new Pipeline([]))->run("<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\r\n<svg xmlns=\"http://www.w3.org/2000/svg\"><text>caf\xE9</text></svg>\r\n"),
        );
    }

    /**
     * @throws InvalidSvgException
     */
    #[Test]
    public function revertingAPassKeepsStyleSheetInstructionsAndPrefixes(): void
    {
        $checkedRule = new class implements RevertIfLargerPassInterface {
            #[\Override]
            public function apply(\DOMDocument $domDocument): void
            {
                $domDocument->documentElement?->appendChild($domDocument->createElementNS('http://www.w3.org/2000/svg', 'svg:g'));
            }
        };

        $svg = '<?xml-stylesheet href="a.css"?><svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:rect width="1"/></svg:svg>';

        self::assertSame($svg, (new Pipeline([$checkedRule, $checkedRule]))->run('<?xml version="1.0"?>' . $svg . '<!-- after -->'));
    }

    /**
     * @throws InvalidSvgException
     */
    #[Test]
    public function revertingAPassKeepsNonAsciiAttributesAsUtf8(): void
    {
        $checkedRule = new class implements RevertIfLargerPassInterface {
            #[\Override]
            public function apply(\DOMDocument $domDocument): void
            {
                $domDocument->documentElement?->setAttribute('data-x', 'grown');
            }
        };

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" font-family="日本 😀"><text>é</text></svg>';

        self::assertSame($svg, (new Pipeline([$checkedRule, $checkedRule]))->run($svg));
    }

    /**
     * @throws InvalidSvgException
     */
    #[Test]
    public function rejectsNestingDeeperThanTheParserLimit(): void
    {
        $this->expectException(InvalidSvgException::class);

        (new Pipeline([]))->run('<svg xmlns="http://www.w3.org/2000/svg">' . str_repeat('<g>', 300) . str_repeat('</g>', 300) . '</svg>');
    }

    /**
     * @throws InvalidSvgException
     */
    #[Test]
    public function handlesADeeplyNestedDocument(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg">' . str_repeat('<g>', 250) . '<rect/>' . str_repeat('</g>', 250) . '</svg>';

        self::assertSame($svg, (new Pipeline([]))->run($svg));
    }

    /**
     * @throws InvalidSvgException
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
        $passes = [$recorder];

        if ($grow) {
            $passes = [new class implements RevertIfLargerPassInterface {
                #[\Override]
                public function apply(\DOMDocument $domDocument): void
                {
                    $domDocument->documentElement?->setAttribute('data-grow', str_repeat('x', 50));
                }
            }, $recorder];
        }

        (new Pipeline($passes))->run($content);

        return implode('', $arrayObject->getArrayCopy());
    }
}
