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
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @internal
 *
 * @see NoStaticPropertyRuleTest
 *
 * @implements Rule<ClassPropertyNode>
 */
final class NoStaticPropertyRule implements Rule
{
    #[\Override]
    public function getNodeType(): string
    {
        return ClassPropertyNode::class;
    }

    #[\Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $name = $node->getClassReflection()->getName();

        if (!$node->isStatic() || !PackageLayer::isPackageClass($name)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(\sprintf('%s::$%s must not be static.', $name, $node->getName()))
                ->identifier('architecture.staticProperty')
                ->build(),
        ];
    }
}
