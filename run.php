<?php
/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use MathiasReker\PhpSvgOptimizer\Services\Providers\FileProvider;
use MathiasReker\PhpSvgOptimizer\Services\SvgOptimizerBuilder;

$svgOptimizer = (new SvgOptimizerBuilder(new FileProvider('svgs/1.svg')))
    ->removeTitleAndDesc()
    ->removeComments()
    ->removeUnnecessaryWhitespace()
    ->removeDefaultAttributes()
    ->removeMetadata()
    ->flattenGroups()
    ->convertColorsToHex()
    ->minifySvgCoordinates()
    ->minifyTransformations()
    ->build();

echo $svgOptimizer->getContent();
