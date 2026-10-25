<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Contract\Service\Rule\SvgOptimizerRuleInterface;
use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Service\Optimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Type\Rule;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversNothing]
final class RuleInvariantsTest extends TestCase
{
    private const string SVG_NAMESPACE = 'http://www.w3.org/2000/svg';

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[DataProvider('provideRuleKeepsTheDocumentValidAndIsIdempotentCases')]
    #[Test]
    public function ruleKeepsTheDocumentValidAndIsIdempotent(Rule $rule, string $content): void
    {
        $once = $this->apply($rule, $content);
        self::assertNotSame('', $once);

        $domDocument = new \DOMDocument();
        self::assertTrue($domDocument->loadXML($once), $once);
        self::assertInstanceOf(\DOMElement::class, $domDocument->documentElement);
        self::assertSame('svg', $domDocument->documentElement->localName);
        self::assertSame(self::SVG_NAMESPACE, $domDocument->documentElement->namespaceURI);

        self::assertSame($once, $this->apply($rule, $once));
    }

    /**
     * @return iterable<string, array{Rule, string}>
     */
    public static function provideRuleKeepsTheDocumentValidAndIsIdempotentCases(): iterable
    {
        foreach (Rule::cases() as $rule) {
            foreach (self::provideAllRulesKeepTheDocumentValidAndAreIdempotentCases() as $name => [$content]) {
                yield $rule->name . ': ' . $name => [$rule, $content];
            }
        }
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[DataProvider('provideAllRulesKeepTheDocumentValidAndAreIdempotentCases')]
    #[Test]
    public function allRulesKeepTheDocumentValidAndAreIdempotent(string $content): void
    {
        $once = $this->applyAll($content);
        self::assertNotSame('', $once);

        $domDocument = new \DOMDocument();
        self::assertTrue($domDocument->loadXML($once), $once);

        self::assertSame($once, $this->applyAll($once));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideAllRulesKeepTheDocumentValidAndAreIdempotentCases(): iterable
    {
        yield 'empty root' => ['<svg xmlns="http://www.w3.org/2000/svg"/>'];

        yield 'root with text only' => ['<svg xmlns="http://www.w3.org/2000/svg">hello</svg>'];

        yield 'prefixed svg namespace' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg" xmlns:l="http://www.w3.org/1999/xlink"><svg:defs><svg:linearGradient id="g"><svg:stop offset="0" stop-color="#ff0000"/></svg:linearGradient></svg:defs><svg:g fill="red"><svg:rect width="10" height="10" fill="url(#g)"/><svg:use l:href="#g"/></svg:g></svg:svg>',
        ];

        yield 'deep nesting' => [
            '<svg xmlns="http://www.w3.org/2000/svg">' . str_repeat('<g fill="red">', 60) . '<rect width="1" height="1"/>' . str_repeat('</g>', 60) . '</svg>',
        ];

        yield 'foreign object with html' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject width="10" height="10"><div xmlns="http://www.w3.org/1999/xhtml" style="color:  red"><p>a  b</p><input value="  x  "/><span></span></div></foreignObject></svg>',
        ];

        yield 'foreign namespace elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><x:rect width="0" height="0"/><x:g/><x:data x:value="  1.50  ">  text  </x:data></svg>',
        ];

        yield 'default namespace reset' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><metadata><rdf xmlns=""><g fill="red"/></rdf></metadata><g/></svg>',
        ];

        yield 'style element with cdata' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style><![CDATA[ .a { fill : red } /* c */ rect > .b:not(.c) { stroke: url( "#g" ) } @media (min-width: 1px) { .a { fill: blue } } ]]></style><g class="a"><rect class="b c" width="10" height="10"/></g></svg>',
        ];

        yield 'several style elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style media="print">.a{fill:red}</style><style>.a{fill:blue}</style><style/><rect class="a" width="1" height="1"/></svg>',
        ];

        yield 'inline styles and inherit' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="fill: red; stroke: inherit" stroke="none"><rect style="fill:inherit;stroke-width:0" stroke-width="2" width="10" height="10"/><circle r="0" fill="currentColor" color="blue"/></g></svg>',
        ];

        yield 'animation' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="a" fill="red" transform="translate(1 1)"><rect id="r" width="10" height="10"><animate attributeName="width" from="10" to="0" dur="1s" begin="b.click"/></rect><set attributeName="fill" to="blue" begin="2s"/></g><animateTransform xlink:href="#a" attributeName="transform" type="rotate" to="90"/><animateColor attributeName="fill" from="red" to="blue"/><rect id="b" width="1" height="1"/></svg>',
        ];

        yield 'references in every form' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:l="http://www.w3.org/1999/xlink"><defs><linearGradient id="g1"><stop offset="0" stop-color="red"/></linearGradient><linearGradient id="g2"><stop offset="0" stop-color="red"/></linearGradient><linearGradient id="g3" xlink:href="#g1"/><clipPath id="c"><g><rect width="5" height="5"/></g></clipPath><mask id="m"><rect width="5" height="5" fill="white"/></mask><symbol id="s"><path d="M0 0L10 10"/></symbol><marker id="mk"><path d="M0 0h1"/></marker></defs><rect fill="url(#g1)" stroke="url(\'#g2\')" clip-path="url( &quot;#c&quot; )" mask="url(#m)" width="1" height="1"/><use l:href="#s"/><use href="#g3"/><path d="M0 0L5 5" marker-end="url(#mk)" style="fill:url(#g2)"/><text aria-labelledby="g1 s">t</text></svg>',
        ];

        yield 'entities and doctype' => [
            '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE svg [<!ENTITY color "red"><!ENTITY ns "http://www.w3.org/2000/svg">]><svg xmlns="&ns;"><rect fill="&color;" width="1" height="1"/><text>&amp;&lt;&#x1F600;&#160;</text></svg>',
        ];

        yield 'comments and processing instructions everywhere' => [
            '<?xml version="1.0"?><!-- a --><svg xmlns="http://www.w3.org/2000/svg"><!-- b --><?foo bar?><g><!-- c --><rect width="1" height="1"/><!-- d --></g><!-- e --></svg><!-- f -->',
        ];

        yield 'unicode text' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text x="1 2 3" font-family="\'Open  Sans\'">👩‍💻 می‌خواهم e&#x301; &#x200B;x&#xAD;y&#x2028;</text><title>  ☃  </title></svg>',
        ];

        yield 'preserved whitespace' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\" xml:space=\"preserve\"><text>  a   b  </text><text xml:space=\"default\">  c   d  </text>\n\t<tspan>  e  </tspan></svg>",
        ];

        yield 'numbers and path data' => [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0.0 -0 100.000 1e2" width="100.50px" height="1E+02%"><path d="M 0.50,-0.0 L 1e-7 .5.5 a1 1 0 01 1 1 Z m 1 1 2 2" stroke-width="0.0001234567890123456789"/><polygon points="0,0 10,0 10,10"/><polyline points=""/><rect x="+1" y="-0" width="10.0" height="010" rx="1" transform="matrix(1,0,0,1,0,0) rotate( 45 , 1 , 1 )"/></svg>',
        ];

        yield 'colors' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="RGB(255, 0, 0)" stroke="#FFFFFF" color="rgba(0,0,0,0.5)" stop-color="hsl(0, 100%, 50%)" flood-color="transparent" lighting-color="url(#g) red" width="1" height="1" style="--my-fill: white; fill: WHITE"/></svg>',
        ];

        yield 'shapes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="5" ry="5.0"/><ellipse rx="auto" ry="5"/><circle r="0"/><rect width="0" height="5"/><line x2="10" marker-start="url(#m)"/><rect width="50%" height="1em"/><path d=""/><polygon/></svg>',
        ];

        yield 'groups' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g/><g><g/></g><g opacity=".5"><rect width="1" height="1"/></g><g transform="scale(2)" fill="red"><g transform="translate(1)"><rect width="1" height="1" fill="red"/><circle r="1" fill="red"/></g></g><switch><g><rect width="1" height="1"/></g></switch><g display="none"><rect width="1" height="1"/></g><g x="1" y="2" font-size="10"><rect width="1" height="1"/></g></svg>',
        ];

        yield 'svg 1.1 features' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" baseProfile="full" enable-background="new 0 0 10 10" contentScriptType="text/ecmascript" zoomAndPan="magnify"><defs><font id="f"><glyph unicode=" "/></font><cursor id="cu" xlink:href="c.png"/></defs><text id="t">hi</text><text><tref xlink:href="#t"/><altGlyph x="1">a</altGlyph></text><a xlink:href="https://example.com" xlink:show="new" xlink:title="x"><rect requiredFeatures="http://www.w3.org/TR/SVG11/feature#Shape" kerning="0" glyph-orientation-vertical="0" width="1" height="1"/></a></svg>',
        ];

        yield 'editor footprints' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd" xmlns:i="http://ns.adobe.com/AdobeIllustrator/10.0/" inkscape:version="1"><sodipodi:namedview/><g inkscape:label="L" i:extraneous="self" data-name="x"><rect width="1" height="1"/></g><metadata><i:pgf>abc</i:pgf></metadata></svg>',
        ];

        yield 'unsafe content' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" onload="alert(1)"><script>alert(1)</script><a href="java&#x200B;script:alert(1)"><text>x</text></a><set attributeName="onclick" to="alert(1)"/><use xlink:href="data:image/svg+xml,&lt;svg/&gt;"/><foreignObject><iframe xmlns="http://www.w3.org/1999/xhtml" src="javascript:alert(1)"/></foreignObject><style>@import url(x.css); rect{fill:url(javascript:alert(1))}</style></svg>',
        ];

        yield 'external stylesheet instruction' => [
            '<?xml-stylesheet href="s.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect stroke="none" width="0" height="5"/><ellipse rx="1" ry="1"/></g></svg>',
        ];

        yield 'duplicate ids and empty attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="a" class="" style="" transform="" width="1" height="1"/><rect id="a" width="1" height="1"/><a href=""><text lang="">x</text></a></svg>',
        ];
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    private function apply(Rule $rule, string $content): string
    {
        /** @var class-string<SvgOptimizerRuleInterface> $ruleClass */
        $ruleClass = $rule->value;

        $svgOptimizer = new SvgOptimizer(new StringProvider($content));
        $svgOptimizer->addRule(new $ruleClass());

        if ($ruleClass::isRisky()) {
            $svgOptimizer->allowRisky();
        }

        return $svgOptimizer->optimize()->getContent();
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    private function applyAll(string $content): string
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($content));
        $svgOptimizer->allowRisky();
        $svgOptimizer->enableAllRules();

        return $svgOptimizer->optimize()->getContent();
    }
}
