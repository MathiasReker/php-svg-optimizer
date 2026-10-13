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
use MathiasReker\PhpSvgOptimizer\Service\Facade\SvgOptimizerFacade;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SvgOptimizerFacade::class)]
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
