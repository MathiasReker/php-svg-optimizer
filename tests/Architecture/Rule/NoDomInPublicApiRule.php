<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Architecture\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;

/**
 * @internal
 *
 * @see NoDomInPublicApiRuleTest
 *
 * @implements Rule<InClassMethodNode>
 */
final class NoDomInPublicApiRule implements Rule
{
    #[\Override]
    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    #[\Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $phpMethodFromParserNodeReflection = $node->getMethodReflection();
        $className = $node->getClassReflection()->getName();

        if (!$phpMethodFromParserNodeReflection->isPublic() || !PackageLayer::isPackageClass($className) || !PackageLayer::isRootApi($className)) {
            return [];
        }

        $errors = [];

        foreach ($phpMethodFromParserNodeReflection->getVariants() as $extendedParametersAcceptor) {
            foreach ($this->domClasses($extendedParametersAcceptor) as $domClass) {
                $errors[] = RuleErrorBuilder::message(\sprintf(
                    '%s::%s() must not expose %s in its signature.',
                    $className,
                    $phpMethodFromParserNodeReflection->getName(),
                    $domClass,
                ))->identifier('architecture.domInPublicApi')->build();
            }
        }

        return $errors;
    }

    /**
     * @return list<string> the DOM classes that the parameters or the return type reference
     */
    private function domClasses(ExtendedParametersAcceptor $extendedParametersAcceptor): array
    {
        $types = [$extendedParametersAcceptor->getReturnType(), ...array_map(
            static fn (ParameterReflection $parameterReflection): Type => $parameterReflection->getType(),
            $extendedParametersAcceptor->getParameters(),
        )];

        $classes = array_merge(...array_map(static fn (Type $type): array => $type->getReferencedClasses(), $types));

        return array_values(array_filter(
            $classes,
            static fn (string $class): bool => 1 === preg_match('/^(?:DOM|Dom\\\)/', $class),
        ));
    }
}
