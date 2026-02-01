<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Data;

use MathiasReker\PhpSvgOptimizer\Contract\Service\Rule\SvgDataInterface;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\Trait\BaseEnumTrait;

enum SvgNamespace: string implements SvgDataInterface
{
    use BaseEnumTrait;

    case Svg = 'http://www.w3.org/2000/svg';
    case Xlink = 'http://www.w3.org/1999/xlink';
    case Sodipodi = 'http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd';
    case Inkscape = 'http://www.inkscape.org/namespaces/inkscape';

    /**
     * Returns all tag values as an array of strings.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return self::valuesFromCases(self::cases());
    }

    /**
     * Returns the standard prefix for the namespace.
     */
    public function prefix(): string
    {
        return match ($this->value) {
            self::Svg->value => 'svg',
            self::Xlink->value => 'xlink',
            self::Sodipodi->value => 'sodipodi',
            self::Inkscape->value => 'inkscape',
        };
    }
}
