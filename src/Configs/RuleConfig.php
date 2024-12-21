<?php

/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Configs;

final class RuleConfig
{
    /**
     * Default optimization rules.
     *
     * @var array<string, bool>
     */
    public const array DEFAULT_RULES = [
        'convertColorsToHex' => true,
        'flattenGroups' => true,
        'minifySvgCoordinates' => true,
        'minifyTransformations' => true,
        'removeComments' => true,
        'removeDefaultAttributes' => true,
        'removeDeprecatedAttributes' => true,
        'removeDoctype' => true,
        'removeMetadata' => true,
        'removeTitleAndDesc' => true,
        'removeUnnecessaryWhitespace' => true,
        'sortAttributes' => true,
    ];
}
