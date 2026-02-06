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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;

/**
 * Remove non-default or dangerous SVG attributes.
 *
 * @no-named-arguments
 */
final readonly class removeNonStandardAttributes implements SvgOptimizerRuleInterface
{
    #[\Override]
    public static function isRisky(): bool
    {
        return false;
    }

    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);

        /** @var \DOMNodeList<\DOMElement> $domNodeList */
        $domNodeList = $domXPath->query('//*[@*]');

        $allowed = $this->getAllowedLookup();

        foreach ($domNodeList as $domElement) {
            $attributes = iterator_to_array($domElement->attributes, false);

            foreach ($attributes as $attribute) {
                $name = $attribute->name;
                if (str_starts_with($name, 'xml:')) {
                    continue;
                }

                if (str_starts_with($name, 'xlink:')) {
                    continue;
                }

                if (str_starts_with($name, 'data-')) {
                    continue;
                }

                if (!\array_key_exists($name, $allowed)) {
                    $domElement->removeAttributeNode($attribute);
                }
            }
        }
    }

    /**
     * Build a lookup table of allowed SVG attributes.
     *
     * @return array<string, true>
     */
    private function getAllowedLookup(): array
    {
        /** @var array<string, true>|null $lookup */
        static $lookup = null;

        if (null === $lookup) {
            $lookup = [];
            foreach (SvgAttribute::cases() as $attr) {
                $lookup[$attr->value] = true;
            }
        }

        return $lookup;
    }

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return false;
    }
}
