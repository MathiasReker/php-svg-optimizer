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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\RemoveElementsByTagNameTrait;

/**
 * @no-named-arguments
 */
final readonly class RemoveTitleAndDesc implements SvgOptimizerRuleInterface
{
    use RemoveElementsByTagNameTrait;

    #[\Override]
    public static function isRisky(): bool
    {
        return false;
    }

    #[\Override]
    public static function shouldCheckSize(): bool
    {
        return false;
    }

    /**
     * Removes `<title>` and `<desc>` elements from the SVG document.
     *
     * These elements are primarily for accessibility and providing metadata.
     * While useful, they are not required for rendering and can be removed to
     * reduce file size if they are not needed.
     *
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $this->removeElementsByTagName($domDocument, SvgTag::Title->value);
        $this->removeElementsByTagName($domDocument, SvgTag::Desc->value);
    }
}
