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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnsafeElements;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveUnsafeElements::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(XmlFormatter::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(SvgAttribute::class)]
#[CoversClass(SvgTag::class)]
final class RemoveUnsafeElementsTest extends TestCase
{
    /**
     * @throws SvgValidationException
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     */
    #[DataProvider('provideOptimizeCases')]
    #[Test]
    public function optimize(string $content, string $expectedSvg): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($content));
        $svgOptimizer->addRule(new RemoveUnsafeElements());

        $actual = $svgOptimizer
            ->allowRisky()
            ->optimize()
            ->getContent();

        self::assertSame($expectedSvg, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
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
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><circle cx="5" cy="5" r="3"/></svg>
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
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="128px" height="128px" version="1.1"/>
                XML,
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
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Removes javascript encoded with hex entities' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <a href="&#x6A;&#x61;&#x76;&#x61;&#x73;&#x63;&#x72;&#x69;&#x70;&#x74;&#x3A;alert(1)">x</a>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
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
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Removes javascript mixed decimal and hex entities' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <a href="&#106;&#x61;&#118;&#x61;&#115;&#99;&#114;&#105;&#112;&#x74;&#58;alert(1)">x</a>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Removes javascript with unicode homoglyphs' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <a href="ｊａｖａｓｃｒｉｐｔ:alert(1)">x</a>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Removes javascript with random casing' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <a href="JaVaScRiPt:alert(1)">x</a>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
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
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Removes javascript with mixed ASCII and fullwidth chars' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <a href="jａvａｓcｒiｐt:alert(1)">x</a>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
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
                <svg xmlns="http://www.w3.org/2000/svg"><set attributeName="href"/></svg>
                XML,
        ];

        yield 'Removes javascript with comments' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <a href="java/* comment */script:alert(1)">x</a>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
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
                <svg xmlns="http://www.w3.org/2000/svg"/>
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
                <svg xmlns="http://www.w3.org/2000/svg"/>
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
                <svg xmlns="http://www.w3.org/2000/svg"/>
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
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
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
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
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
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
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
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];
    }

    #[Test]
    public function ruleIsMarkedAsRisky(): void
    {
        self::assertTrue(RemoveUnsafeElements::isRisky());
    }
}
