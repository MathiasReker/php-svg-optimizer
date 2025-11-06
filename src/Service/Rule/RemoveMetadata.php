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
final readonly class RemoveMetadata implements SvgOptimizerRuleInterface
{
    use RemoveElementsByTagNameTrait;

    /**
     * Remove the metadata elements from the SVG document.
     *
     * This method will find and remove all `<metadata>` elements within the SVG
     * document. Metadata elements typically contain information that is not
     * essential for rendering the SVG image.
     *
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $this->removeElementsByTagName($domDocument, 'metadata');
    }

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return false;
    }
}
