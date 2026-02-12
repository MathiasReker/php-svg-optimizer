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

/**
 * @no-named-arguments
 */
final readonly class RemoveEmptyAttributes implements SvgOptimizerRuleInterface
{
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
     * Removes attributes that have an empty or whitespace-only value.
     *
     * This method iterates through all elements in the document and removes any
     * attribute whose value is an empty string or consists only of whitespace.
     *
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            if (!$domNodeList->hasAttributes()) {
                continue;
            }

            $toRemove = [];
            foreach ($domNodeList->attributes as $attr) {
                if ('' === trim($attr->value)) {
                    $toRemove[] = $attr->name;
                }
            }

            foreach ($toRemove as $name) {
                $domNodeList->removeAttribute($name);
            }
        }
    }
}
