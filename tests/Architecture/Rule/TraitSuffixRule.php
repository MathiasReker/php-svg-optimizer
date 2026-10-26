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
use PhpParser\Node\Stmt\Trait_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @internal
 *
 * @see TraitSuffixRuleTest
 *
 * @implements Rule<Trait_>
 */
final class TraitSuffixRule implements Rule
{
    private const string SUFFIX = 'Trait';

    #[\Override]
    public function getNodeType(): string
    {
        return Trait_::class;
    }

    #[\Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $name = (string) $node->namespacedName;

        if (!PackageLayer::isPackageClass($name) || str_ends_with($name, self::SUFFIX)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(\sprintf('The name of the trait %s must end in %s.', $name, self::SUFFIX))
                ->identifier('architecture.traitSuffix')
                ->build(),
        ];
    }
}
