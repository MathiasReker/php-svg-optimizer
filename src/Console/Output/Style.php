<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Output;

/**
 * @internal
 *
 * @no-named-arguments
 */
enum Style: string
{
    case Bold = "\033[1m";

    case Cyan = "\033[36m";

    case Green = "\033[32m";

    case Red = "\033[31m";

    private const string RESET = "\033[0m";

    /**
     * @param string $text    the text to style
     * @param bool   $enabled whether the text is styled at all
     */
    public function apply(string $text, bool $enabled): string
    {
        return $enabled ? $this->value . $text . self::RESET : $text;
    }
}
