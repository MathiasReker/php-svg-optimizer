<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\PHPUnit\AnnotationsToAttributes\Rector\Class_\AnnotationWithValueToAttributeRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;
use Rector\PHPUnit\PHPUnit100\Rector\Class_\RemoveNamedArgsInDataProviderRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamArrayDocblockBasedOnCallableNativeFuncCallRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnDocblockForScalarArrayFromAssignsRector;
use Rector\TypeDeclarationDocblocks\Rector\Class_\AddVarArrayDocblockFromDimFetchAssignRector;
use Rector\TypeDeclarationDocblocks\Rector\Class_\ClassMethodArrayDocblockParamFromLocalCallsRector;
use Rector\TypeDeclarationDocblocks\Rector\Class_\DocblockVarArrayFromGetterReturnRector;
use Rector\TypeDeclarationDocblocks\Rector\Class_\DocblockVarArrayFromPropertyDefaultsRector;
use Rector\TypeDeclarationDocblocks\Rector\Class_\DocblockVarFromParamDocblockInConstructorRector;
use Rector\TypeDeclarationDocblocks\Rector\ClassMethod\AddParamArrayDocblockFromAssignsParamToParamReferenceRector;
use Rector\TypeDeclarationDocblocks\Rector\ClassMethod\AddParamArrayDocblockFromDimFetchAccessRector;
use Rector\TypeDeclarationDocblocks\Rector\ClassMethod\AddReturnDocblockForArrayDimAssignedObjectRector;
use Rector\TypeDeclarationDocblocks\Rector\ClassMethod\AddReturnDocblockForCommonObjectDenominatorRector;
use Rector\TypeDeclarationDocblocks\Rector\ClassMethod\AddReturnDocblockForJsonArrayRector;
use Rector\TypeDeclarationDocblocks\Rector\ClassMethod\DocblockGetterReturnArrayFromPropertyDocblockVarRector;
use Rector\TypeDeclarationDocblocks\Rector\ClassMethod\DocblockReturnArrayFromDirectArrayInstanceRector;
use Rector\Visibility\Rector\ClassConst\ChangeConstantVisibilityRector;
use Rector\Visibility\Rector\ClassMethod\ChangeMethodVisibilityRector;

return RectorConfig::configure()
    ->withPhpSets(php83: true)
    ->withIndent()
    ->withImportNames()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        naming: true,
        instanceOf: true,
        earlyReturn: true,
        rectorPreset: true,
        phpunitCodeQuality: true,
        symfonyCodeQuality: true,
    )
    ->withAttributesSets()
    ->withSkipPath(__DIR__ . '/vendor')
    ->withPaths([__DIR__])
    ->withoutParallel()
    ->withSkip(
        [
            PreferPHPUnitThisCallRector::class,
        ]
    )
    ->withRules(
        [
            DocblockVarArrayFromGetterReturnRector::class,
            ClassMethodArrayDocblockParamFromLocalCallsRector::class,
            AddVarArrayDocblockFromDimFetchAssignRector::class,
            DocblockVarFromParamDocblockInConstructorRector::class,
            DocblockVarArrayFromPropertyDefaultsRector::class,
            AddReturnDocblockForArrayDimAssignedObjectRector::class,
            AddReturnDocblockForCommonObjectDenominatorRector::class,
            DocblockReturnArrayFromDirectArrayInstanceRector::class,
            AddReturnDocblockForJsonArrayRector::class,
            AddParamArrayDocblockFromAssignsParamToParamReferenceRector::class,
            DocblockGetterReturnArrayFromPropertyDocblockVarRector::class,
            AddParamArrayDocblockFromDimFetchAccessRector::class,
            ChangeConstantVisibilityRector::class,
            ChangeMethodVisibilityRector::class,
            AddReturnDocblockForScalarArrayFromAssignsRector::class,
            AddParamArrayDocblockBasedOnCallableNativeFuncCallRector::class,
            AnnotationWithValueToAttributeRector::class,
            RemoveNamedArgsInDataProviderRector::class,
        ]
    );
