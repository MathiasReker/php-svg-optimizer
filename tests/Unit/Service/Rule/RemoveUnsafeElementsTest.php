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

        $actual = $svgOptimizer->allowRisky()->optimize()->getContent();
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
    }

    #[Test]
    public function ruleIsMarkedAsRisky(): void
    {
        self::assertTrue(RemoveUnsafeElements::isRisky());
    }
}
