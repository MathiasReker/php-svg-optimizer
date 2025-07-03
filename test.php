<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use MathiasReker\PhpSvgOptimizer\Services\SvgOptimizerService;

try {
    $svgOptimizer = SvgOptimizerService::fromFile('./assets/logos/logo-1.svg')
        ->withRules(
            convertColorsToHex: true,
            convertEmptyTagsToSelfClosing: true,
            flattenGroups: true,
            minifySvgCoordinates: true,
            minifyTransformations: true,
            removeComments: true,
            removeDefaultAttributes: true,
            removeDeprecatedAttributes: true,
            removeDoctype: true,
            removeEmptyAttributes: true,
            removeEnableBackgroundAttribute: true,
            removeInkscapeFootprints: true,
            removeMetadata: true,
            removeTitleAndDesc: true,
            removeUnnecessaryWhitespace: true,
            removeUnsafeElements: false,
            removeUnusedNamespaces: true,
            sortAttributes: true,
        )
        ->optimize()
        ->saveToFile('./assets/logos/logo-1.svg');
} catch (\Exception $exception) {
    echo $exception->getMessage();
}
