<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Contract\Service\Rule\SvgOptimizerRuleInterface;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\RemoveElementsByTagNameTrait;

/**
 * @no-named-arguments
 */
final readonly class RemoveTitleAndDesc implements SvgOptimizerRuleInterface
{
    use RemoveElementsByTagNameTrait;

    /**
     * Remove the `<title>` and `<desc>` elements from the SVG document.
     *
     * The `<title>` and `<desc>` elements are typically used for accessibility
     * and descriptive purposes but can be removed if not needed to reduce
     * the file size.
     *
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $this->removeElementsByTagName($domDocument, 'title');
        $this->removeElementsByTagName($domDocument, 'desc');
    }

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return false;
    }
}
