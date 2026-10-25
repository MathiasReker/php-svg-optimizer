<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Svg;

/**
 * @internal
 */
enum NamespaceUri: string
{
    case Svg = 'http://www.w3.org/2000/svg';
    case Xlink = 'http://www.w3.org/1999/xlink';
    case Sodipodi = 'http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd';
    case Inkscape = 'http://www.inkscape.org/namespaces/inkscape';
    case Xml = 'http://www.w3.org/XML/1998/namespace';
    case XmlEvents = 'http://www.w3.org/2001/xml-events';

    public function prefix(): string
    {
        return match ($this->value) {
            self::Svg->value => 'svg',
            self::Xlink->value => 'xlink',
            self::Sodipodi->value => 'sodipodi',
            self::Inkscape->value => 'inkscape',
            self::Xml->value => 'xml',
            self::XmlEvents->value => 'ev',
        };
    }
}
