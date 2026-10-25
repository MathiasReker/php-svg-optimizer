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
use MathiasReker\PhpSvgOptimizer\Rule;
use MathiasReker\PhpSvgOptimizer\SvgOptimizer;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversNothing]
final class SpriteTest extends TestCase
{
    private const string NS = 'xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"';

    private const string INKSCAPE_NS = 'xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:cc="http://creativecommons.org/ns#" xmlns:dc="http://purl.org/dc/elements/1.1/"';

    private const string XML_NAMESPACE = 'http://www.w3.org/XML/1998/namespace';

    private const array SYMBOL_ATTRIBUTES = ['viewBox', 'preserveAspectRatio', 'refX', 'refY', 'x', 'y', 'width', 'height'];

    private const string NUMBER_REGEX = '/[+-]?(?:\d+\.?\d*|\.\d+)(?:e[+-]?\d+)?/i';

    /**
     * @param list<string> $targets the ids that other documents address
     *
     * @throws RiskyRulesNotAllowedException
     * @throws InvalidSvgException
     */
    #[DataProvider('provideSpriteCases')]
    #[Test]
    public function allSafeRulesKeepTheSprite(string $content, array $targets): void
    {
        $optimized = (new SvgOptimizer())
            ->withRules(...Rule::cases())
            ->withoutRules(...array_filter(Rule::cases(), static fn (Rule $rule): bool => $rule->isRisky()))
            ->optimizeString($content)
            ->getContent();

        $this->assertSpriteSurvives($content, $optimized, $targets);
    }

    /**
     * @param list<string> $targets the ids that other documents address
     *
     * @throws RiskyRulesNotAllowedException
     * @throws InvalidSvgException
     */
    #[DataProvider('provideSpriteCases')]
    #[Test]
    public function allRulesIncludingRiskyKeepTheSprite(string $content, array $targets): void
    {
        $optimized = (new SvgOptimizer())
            ->withRules(...Rule::cases())
            ->withRiskyRulesAllowed()
            ->optimizeString($content)
            ->getContent();

        $this->assertSpriteSurvives($content, $optimized, $targets);
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function provideSpriteCases(): iterable
    {
        $ns = self::NS;

        yield 'hidden symbol sprite' => [
            "<svg {$ns} style=\"display:none\"><symbol id=\"home\" viewBox=\"0 0 24 24\" preserveAspectRatio=\"xMidYMid meet\"><title>Home</title><path d=\"M3 12L12 3l9 9v9H3z\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\"/></symbol><symbol id=\"star\" viewBox=\"0 0 24 24\"><polygon points=\"12,2 15,9 22,9 16,14 18,21 12,17 6,21 8,14 2,9 9,9\" fill=\"currentColor\"/></symbol><symbol id=\"grad\" viewBox=\"0 0 10 10\"><linearGradient id=\"lg\"><stop offset=\"0\" stop-color=\"red\"/><stop offset=\"1\" stop-color=\"blue\"/></linearGradient><rect width=\"10\" height=\"10\" fill=\"url(#lg)\"/></symbol><symbol id=\"refs\" viewBox=\"0 0 10 10\" refX=\"5\" refY=\"5\" x=\"0\" y=\"0\" width=\"10\" height=\"10\"><circle cx=\"5\" cy=\"5\" r=\"4\" fill=\"#00ff00\"/></symbol></svg>",
            ['home', 'star', 'grad', 'refs'],
        ];

        yield 'symbol and group in defs' => [
            "<svg {$ns}><defs><symbol id=\"a\" viewBox=\"0 0 10 10\"><g fill=\"red\"><rect width=\"10\" height=\"10\"/></g></symbol><g id=\"b\"><circle cx=\"20\" cy=\"20\" r=\"20\" fill=\"blue\"/></g></defs></svg>",
            ['a', 'b'],
        ];

        yield 'view sprite' => [
            "<svg {$ns} viewBox=\"0 0 200 100\" width=\"200\" height=\"100\"><view id=\"left\" viewBox=\"0 0 100 100\"/><view id=\"right\" viewBox=\"100 0 100 100\" preserveAspectRatio=\"none\"/><rect width=\"100\" height=\"100\" fill=\"red\"/><rect x=\"100\" width=\"100\" height=\"100\" fill=\"blue\"/></svg>",
            ['left', 'right'],
        ];

        yield 'target stack' => [
            "<svg {$ns} viewBox=\"0 0 10 10\"><style>.i{display:none}.i:target{display:inline}</style><g id=\"one\" class=\"i\"><rect width=\"10\" height=\"10\" fill=\"red\"/></g><g id=\"two\" class=\"i\"><circle cx=\"5\" cy=\"5\" r=\"5\" fill=\"blue\"/></g><rect id=\"three\" class=\"i\" width=\"5\" height=\"5\"/></svg>",
            ['one', 'two', 'three'],
        ];

        yield 'nested svg sprite' => [
            "<svg {$ns} width=\"40\" height=\"20\"><svg id=\"n1\" viewBox=\"0 0 10 10\" width=\"20\" height=\"20\"><rect width=\"10\" height=\"10\" fill=\"red\"/></svg><svg id=\"n2\" viewBox=\"0 0 10 10\" x=\"20\" width=\"20\" height=\"20\"><circle cx=\"5\" cy=\"5\" r=\"5\" fill=\"blue\"/></svg></svg>",
            ['n1', 'n2'],
        ];

        yield 'group inside a hidden group' => [
            "<svg {$ns}><g display=\"none\"><g id=\"h1\" fill=\"purple\"><path d=\"M0 0h40v40z\"/></g></g><rect width=\"1\" height=\"1\"/></svg>",
            ['h1'],
        ];

        yield 'icon font sprite' => [self::iconFont(), array_map(static fn (int $index): string => 'if-' . $index, range(0, 59))];

        yield 'symbols with paint servers, clip paths, masks, filters and markers' => [
            "<svg {$ns}><defs><linearGradient id=\"shared\" x1=\"0\" x2=\"1\"><stop offset=\"0\" stop-color=\"#ff0000\"/><stop offset=\"1\" stop-color=\"#00ff00\"/></linearGradient><marker id=\"arrow\" viewBox=\"0 0 10 10\" refX=\"5\" refY=\"5\" markerWidth=\"4\" markerHeight=\"4\" orient=\"auto\"><path d=\"M0 0L10 5L0 10z\"/></marker></defs>"
            . '<symbol id="p-lin" viewBox="0 0 10 10"><rect width="10" height="10" fill="url(#shared)"/></symbol>'
            . '<symbol id="p-rad" viewBox="0 0 10 10"><radialGradient id="rg"><stop offset="0" stop-color="yellow"/></radialGradient><circle cx="5" cy="5" r="5" fill="url(#rg)"/></symbol>'
            . '<symbol id="p-clip" viewBox="0 0 10 10"><clipPath id="cp"><circle cx="5" cy="5" r="3"/></clipPath><rect width="10" height="10" fill="red" clip-path="url(#cp)"/></symbol>'
            . '<symbol id="p-mask" viewBox="0 0 10 10"><mask id="mk"><rect width="10" height="10" fill="white"/></mask><rect width="10" height="10" fill="blue" mask="url(#mk)"/></symbol>'
            . '<symbol id="p-pat" viewBox="0 0 10 10"><pattern id="pt" width="2" height="2" patternUnits="userSpaceOnUse"><rect width="1" height="1" fill="green"/></pattern><rect width="10" height="10" fill="url(#pt)"/></symbol>'
            . '<symbol id="p-filter" viewBox="0 0 10 10"><filter id="fl"><feGaussianBlur stdDeviation="1"/></filter><rect x="2" y="2" width="6" height="6" filter="url(#fl)"/></symbol>'
            . '<symbol id="p-marker" viewBox="0 0 20 20"><path d="M2 10H15" stroke="black" marker-end="url(#arrow)"/></symbol>'
            . '<symbol id="p-style" viewBox="0 0 10 10"><rect width="10" height="10" style="fill:url(#shared)"/></symbol></svg>',
            ['p-lin', 'p-rad', 'p-clip', 'p-mask', 'p-pat', 'p-filter', 'p-marker', 'p-style', 'shared', 'arrow'],
        ];

        yield 'symbols that use each other' => [
            "<svg {$ns}><defs><path id=\"shape\" d=\"M0 0h10v10z\"/></defs><symbol id=\"base\" viewBox=\"0 0 10 10\"><circle cx=\"5\" cy=\"5\" r=\"5\"/></symbol><symbol id=\"double\" viewBox=\"0 0 20 10\"><use href=\"#base\" width=\"10\" height=\"10\"/><use xlink:href=\"#base\" x=\"10\" width=\"10\" height=\"10\"/></symbol><symbol id=\"usesShape\" viewBox=\"0 0 10 10\"><use href=\"#shape\" fill=\"green\"/></symbol><symbol id=\"deep\" viewBox=\"0 0 20 10\"><use href=\"#double\" width=\"20\" height=\"10\"/></symbol></svg>",
            ['base', 'double', 'usesShape', 'deep', 'shape'],
        ];

        yield 'Inkscape sprite' => [
            '<svg ' . $ns . ' ' . self::INKSCAPE_NS . ' width="40mm" height="20mm" viewBox="0 0 40 20" version="1.1" id="svg8" inkscape:version="1.2.2" sodipodi:docname="sprite.svg"><defs id="defs2"><symbol id="ink-a" viewBox="0 0 10 10"><path id="path10" style="fill:#ff0000;stroke:none;stroke-width:0.264583" d="M 0,0 H 10 V 10 Z" inkscape:connector-curvature="0"/></symbol><symbol id="ink-b" viewBox="0 0 10 10"><circle id="circle12" style="fill:#0000ff" cx="5" cy="5" r="4"/></symbol></defs><sodipodi:namedview id="base" pagecolor="#ffffff" inkscape:zoom="1" inkscape:current-layer="layer1"/><metadata id="metadata5"><rdf:RDF><cc:Work rdf:about=""><dc:format>image/svg+xml</dc:format></cc:Work></rdf:RDF></metadata><g inkscape:label="Layer 1" inkscape:groupmode="layer" id="layer1" style="display:none"><g id="ink-c"><rect id="rect14" style="fill:#00aa00" width="20" height="20"/></g></g><g inkscape:label="Layer 2" inkscape:groupmode="layer" id="layer2"><use xlink:href="#ink-a" id="use20" width="10" height="10"/></g></svg>',
            ['ink-a', 'ink-b', 'ink-c', 'layer1', 'layer2'],
        ];

        yield 'Illustrator sprite' => [
            "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<!-- Generator: Adobe Illustrator 27.0.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)  -->\n<svg version=\"1.1\" id=\"Layer_1\" {$ns} x=\"0px\" y=\"0px\" viewBox=\"0 0 100 50\" style=\"enable-background:new 0 0 100 50;\" xml:space=\"preserve\"><style type=\"text/css\">\n\t.st0{fill:#FF0000;}\n\t.st1{fill:#0000FF;stroke:#000000;stroke-miterlimit:10;}\n</style><symbol id=\"ai-a\" viewBox=\"-5 -5 10 10\"><rect x=\"-5\" y=\"-5\" class=\"st0\" width=\"10\" height=\"10\"/></symbol><symbol id=\"ai-b\" viewBox=\"0 0 10 10\"><circle class=\"st1\" cx=\"5\" cy=\"5\" r=\"4\"/></symbol><use xlink:href=\"#ai-a\" width=\"10\" height=\"10\" x=\"-5\" y=\"-5\" transform=\"matrix(5 0 0 5 25 25)\" style=\"overflow:visible;\"/><g id=\"Group_1\"><use xlink:href=\"#ai-b\" width=\"10\" height=\"10\" transform=\"matrix(5 0 0 5 50 0)\"/></g></svg>",
            ['ai-a', 'ai-b', 'Group_1'],
        ];

        yield 'symbols with titles' => [
            "<svg {$ns}><symbol id=\"t-a\" viewBox=\"0 0 10 10\" aria-labelledby=\"t-a-title\" role=\"img\"><title id=\"t-a-title\">Square</title><desc>A red square</desc><rect width=\"10\" height=\"10\" fill=\"red\"/></symbol><symbol id=\"t-b\" viewBox=\"0 0 10 10\"><title>Circle</title><circle cx=\"5\" cy=\"5\" r=\"5\" fill=\"blue\"/></symbol></svg>",
            ['t-a', 't-b'],
        ];

        yield 'symbols styled by class and id selectors' => [
            "<svg {$ns}><style>.red{fill:red}.blue{fill:blue}#cls-b circle{stroke:black;stroke-width:2px}.big{transform:scale(1)}</style><symbol id=\"cls-a\" viewBox=\"0 0 10 10\"><rect class=\"red big\" width=\"10\" height=\"10\"/></symbol><symbol id=\"cls-b\" viewBox=\"0 0 10 10\"><circle class=\"blue\" cx=\"5\" cy=\"5\" r=\"4\"/></symbol></svg>",
            ['cls-a', 'cls-b'],
        ];

        yield 'inline sprite for an HTML page' => [
            "<svg {$ns} aria-hidden=\"true\" style=\"position: absolute; width: 0; height: 0; overflow: hidden;\" version=\"1.1\"><defs><symbol id=\"in-a\" viewBox=\"0 0 32 32\"><path d=\"M16 0l16 32H0z\"/></symbol><symbol id=\"in-b\" viewBox=\"0 0 32 32\"><path fill=\"url(#in-grad)\" d=\"M0 0h32v32H0z\"/></symbol><linearGradient id=\"in-grad\"><stop offset=\"0\" stop-color=\"orange\"/></linearGradient><linearGradient id=\"in-page\"><stop offset=\"0\" stop-color=\"pink\"/></linearGradient></defs></svg>",
            ['in-a', 'in-b', 'in-grad', 'in-page'],
        ];

        yield 'zero-sized library of paint servers and filters' => [
            "<svg {$ns} width=\"0\" height=\"0\"><defs><linearGradient id=\"brand\"><stop offset=\"0\" stop-color=\"red\"/></linearGradient><filter id=\"blur\"><feGaussianBlur stdDeviation=\"2\"/></filter><clipPath id=\"round\"><circle r=\"1\"/></clipPath></defs></svg>",
            ['brand', 'blur', 'round'],
        ];

        yield 'library of paint servers without rendered content' => [
            "<svg {$ns}><linearGradient id=\"lib-a\"><stop offset=\"0\" stop-color=\"red\"/><stop offset=\"1\" stop-color=\"blue\"/></linearGradient><filter id=\"lib-b\"><feGaussianBlur stdDeviation=\"1\"/></filter></svg>",
            ['lib-a', 'lib-b'],
        ];

        yield 'nested svg elements in defs' => [
            "<svg {$ns}><defs><svg id=\"d-a\" viewBox=\"0 0 10 10\"><rect width=\"10\" height=\"10\" fill=\"red\"/></svg><svg id=\"d-b\" viewBox=\"0 0 10 10\"><circle cx=\"5\" cy=\"5\" r=\"5\" fill=\"blue\"/></svg></defs></svg>",
            ['d-a', 'd-b'],
        ];

        yield 'nested svg elements with views' => [
            "<svg {$ns} width=\"10\" height=\"20\" viewBox=\"0 0 10 20\"><svg id=\"v-a\" viewBox=\"0 0 10 10\" width=\"10\" height=\"10\"><rect width=\"10\" height=\"10\" fill=\"red\"/></svg><view id=\"v-a-view\" viewBox=\"0 0 10 10\"/><svg id=\"v-b\" viewBox=\"0 0 10 10\" width=\"10\" height=\"10\" y=\"10\"><circle cx=\"5\" cy=\"5\" r=\"5\" fill=\"blue\"/></svg><view id=\"v-b-view\" viewBox=\"0 10 10 10\"/></svg>",
            ['v-a', 'v-b', 'v-a-view', 'v-b-view'],
        ];

        yield 'stack of nested svg elements shown by target' => [
            "<svg {$ns} viewBox=\"0 0 10 10\"><style>:root&gt;svg{display:none}:root&gt;svg:target{display:inline}</style><svg id=\"st-a\" viewBox=\"0 0 10 10\"><rect width=\"10\" height=\"10\" fill=\"red\"/></svg><svg id=\"st-b\" viewBox=\"0 0 10 10\"><circle cx=\"5\" cy=\"5\" r=\"5\" fill=\"blue\"/></svg></svg>",
            ['st-a', 'st-b'],
        ];

        yield 'group hidden through a class' => [
            "<svg {$ns} viewBox=\"0 0 10 10\"><style>.hide{display:none}</style><g class=\"hide\"><g id=\"hc-a\"><rect width=\"10\" height=\"10\" fill=\"red\"/></g></g><circle cx=\"5\" cy=\"5\" r=\"2\" fill=\"green\"/></svg>",
            ['hc-a'],
        ];

        yield 'groups of icons in defs' => [
            "<svg {$ns}><defs><linearGradient id=\"ng\"><stop offset=\"0\" stop-color=\"red\"/><stop offset=\"1\" stop-color=\"blue\"/></linearGradient><g id=\"set\"><g id=\"set-a\"><rect width=\"20\" height=\"20\" fill=\"blue\"/></g><g id=\"set-b\"><rect width=\"20\" height=\"20\" fill=\"url(#ng)\"/></g></g></defs></svg>",
            ['set', 'set-a', 'set-b', 'ng'],
        ];

        yield 'rendered document with a symbol and groups in defs' => [
            "<svg {$ns} viewBox=\"0 0 10 10\"><defs><g id=\"spare\"><rect width=\"1\" height=\"1\" fill=\"url(#spare-fill)\"/></g><linearGradient id=\"spare-fill\"><stop offset=\"0\" stop-color=\"red\"/></linearGradient><path id=\"spare-path\" d=\"M0 0h1\"/></defs><symbol id=\"extra\" viewBox=\"0 0 1 1\"><rect width=\"1\" height=\"1\"/></symbol><g id=\"layer\"><rect width=\"10\" height=\"10\"/></g></svg>",
            ['spare', 'spare-path', 'extra', 'layer'],
        ];

        yield 'empty symbols' => [
            "<svg {$ns}><symbol id=\"blank\" viewBox=\"0 0 10 10\"/><symbol id=\"empty-group\" viewBox=\"0 0 1 1\"><g/></symbol></svg>",
            ['blank', 'empty-group'],
        ];
    }

    /**
     * @param list<string> $targets the ids that other documents address
     *
     * @throws RiskyRulesNotAllowedException
     * @throws InvalidSvgException
     */
    #[DataProvider('provideEachRuleKeepsTheSpriteCases')]
    #[Test]
    public function eachRuleKeepsTheSprite(Rule $rule, string $content, array $targets): void
    {
        $optimized = (new SvgOptimizer())
            ->withRules($rule)
            ->withRiskyRulesAllowed($rule->isRisky())
            ->optimizeString($content)
            ->getContent();

        $this->assertSpriteSurvives($content, $optimized, $targets);
    }

    /**
     * @return iterable<string, array{Rule, string, list<string>}>
     */
    public static function provideEachRuleKeepsTheSpriteCases(): iterable
    {
        foreach (Rule::cases() as $rule) {
            foreach (self::provideSpriteCases() as $name => [$content, $targets]) {
                yield $rule->name . ': ' . $name => [$rule, $content, $targets];
            }
        }
    }

    private static function iconFont(): string
    {
        $symbols = '';

        foreach (range(0, 59) as $index) {
            $shape = match ($index % 5) {
                0 => '<path d="M' . ($index % 7) . ' 2h' . (10 + $index % 9) . 'v12H2z" fill="currentColor"/>',
                1 => '<circle cx="12" cy="12" r="' . (4 + $index % 8) . '.0" fill="none" stroke="currentColor" stroke-width="2.00"/>',
                2 => '<g fill="#ff0000"><rect x="2" y="2" width="' . (5 + $index % 15) . '" height="20" rx="0"/><ellipse cx="18" cy="18" rx="4" ry="4"/></g>',
                3 => '<polygon points="12,2 ' . (15 + $index % 6) . ',9 22,9 16,14 18,21 12,17 6,21 8,14 2,9 9,9" opacity="1"/>',
                default => '<g transform="translate(0,0)"><line x1="2" y1="2" x2="22" y2="' . (4 + $index % 18) . '" stroke="#0000ff"/></g>',
            };
            $symbols .= '<symbol id="if-' . $index . '" viewBox="0 0 24 24">' . $shape . '</symbol>';
        }

        return '<svg ' . self::NS . ' style="display: none;"><defs>' . $symbols . '</defs></svg>';
    }

    /**
     * @param list<string> $targets the ids that other documents address
     */
    private function assertSpriteSurvives(string $content, string $optimized, array $targets): void
    {
        $domDocument = $this->load($content);
        $result = $this->load($optimized);
        $domxPath = new \DOMXPath($result);

        $elements = (new \DOMXPath($domDocument))->query('//*[local-name()="symbol" or local-name()="view"][@id]');
        self::assertNotFalse($elements);

        foreach ($elements as $element) {
            self::assertInstanceOf(\DOMElement::class, $element);
            $id = $element->getAttribute('id');
            $survivor = $this->elementById($domxPath, $id, $optimized);
            self::assertSame($element->localName, $survivor->localName, $optimized);

            foreach (self::SYMBOL_ATTRIBUTES as $attribute) {
                self::assertSame($element->hasAttribute($attribute), $survivor->hasAttribute($attribute), $id . ' ' . $attribute . ': ' . $optimized);
                self::assertSame(
                    $this->normalize($element->getAttribute($attribute)),
                    $this->normalize($survivor->getAttribute($attribute)),
                    $id . ' ' . $attribute . ': ' . $optimized,
                );
            }
        }

        foreach ($targets as $target) {
            $this->elementById($domxPath, $target, $optimized);
        }
    }

    private function load(string $content): \DOMDocument
    {
        self::assertNotSame('', $content);

        $domDocument = new \DOMDocument();
        self::assertTrue($domDocument->loadXML($content), $content);

        return $domDocument;
    }

    private function elementById(\DOMXPath $domXPath, string $id, string $optimized): \DOMElement
    {
        $domXPath->registerNamespace('xml', self::XML_NAMESPACE);
        $nodes = $domXPath->query(\sprintf('//*[@id="%1$s" or @xml:id="%1$s"]', $id));
        self::assertNotFalse($nodes);
        self::assertSame(1, $nodes->length, 'Missing fragment target "' . $id . '" in ' . $optimized);

        $node = $nodes->item(0);
        self::assertInstanceOf(\DOMElement::class, $node);

        return $node;
    }

    /**
     * @return list<string>|string the numbers of a numeric value, or the trimmed value
     */
    private function normalize(string $value): array|string
    {
        if (1 !== preg_match('/^[\s\d.,eE+-]*$/', $value) || '' === trim($value)) {
            return trim($value);
        }

        preg_match_all(self::NUMBER_REGEX, $value, $matches);

        return array_map(static fn (string $number): string => (string) (float) $number, $matches[0]);
    }
}
