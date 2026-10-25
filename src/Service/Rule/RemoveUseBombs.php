<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Pass\PassInterface;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\EntityReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\TagMatcherTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\UseBombTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveUseBombs implements PassInterface
{
    use EntityReferenceTrait;
    use TagMatcherTrait;
    use UseBombTrait;

    /**
     * @param \DOMDocument $domDocument the DOM document to sanitize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        $this->expandEntityReferences($domDocument);
        $this->removeUseBombs($domDocument);
    }
}
