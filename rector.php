<?php

/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPhpSets(php82: true)
    ->withIndent()
    ->withPreparedSets(
        deadCode: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        instanceOf: true,
        earlyReturn: true,
        strictBooleans: true,
        rectorPreset: true,
        symfonyCodeQuality: true,
        codeQuality: true,
        naming: true,
        phpunit: true,
        phpunitCodeQuality: true,
    )
    ->withSkipPath(__DIR__ . '/vendor')
    ->withPaths([__DIR__])
    ->withoutParallel();
