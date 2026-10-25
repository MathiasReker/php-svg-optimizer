<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchMethodCallReturnTypeRector;
use Rector\PHPUnit\AnnotationsToAttributes\Rector\Class_\AnnotationWithValueToAttributeRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;
use Rector\Visibility\Rector\ClassConst\ChangeConstantVisibilityRector;
use Rector\Visibility\Rector\ClassMethod\ChangeMethodVisibilityRector;

return RectorConfig::configure()
    ->withPhpSets()
    ->withComposerBased(phpunit: true)
    ->withIndent()
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        typeDeclarationDocblocks: true,
        privatization: true,
        naming: true,
        instanceOf: true,
        earlyReturn: true,
        rectorPreset: true,
        phpunitCodeQuality: true,
        phpunitNarrowAsserts: true,
        symfonyCodeQuality: true,
    )
    ->withAttributesSets()
    ->withTreatClassesAsFinal()
    ->withSkipPath(__DIR__ . '/vendor')
    ->withPaths([__DIR__, __DIR__ . '/bin/svg-optimizer'])
    ->withoutParallel()
    ->withSkip(
        [
            PreferPHPUnitThisCallRector::class,
            RenameForeachValueVariableToMatchMethodCallReturnTypeRector::class => [__DIR__ . '/src/Pass'],
        ]
    )
    ->withRules(
        [
            ChangeConstantVisibilityRector::class,
            ChangeMethodVisibilityRector::class,
            AnnotationWithValueToAttributeRector::class,
        ]
    );
