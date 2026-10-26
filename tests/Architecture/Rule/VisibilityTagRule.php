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
use PhpParser\Node\Stmt\ClassLike;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @internal
 *
 * @see VisibilityTagRuleTest
 *
 * @implements Rule<ClassLike>
 */
final class VisibilityTagRule implements Rule
{
    #[\Override]
    public function getNodeType(): string
    {
        return ClassLike::class;
    }

    #[\Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $name = (string) $node->namespacedName;

        if (!PackageLayer::isPackageClass($name)) {
            return [];
        }

        $tag = PackageLayer::isRootApi($name) ? '@api' : '@internal';
        $docComment = $node->getDocComment()?->getText() ?? '';

        if (1 === preg_match('/^\s*\*\s*' . $tag . '\b/m', $docComment)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(\sprintf('%s must carry the %s tag.', $name, $tag))
                ->identifier('architecture.visibilityTag')
                ->build(),
        ];
    }
}
