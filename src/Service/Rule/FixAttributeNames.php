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
 * @no-named-arguments
 */
final readonly class FixAttributeNames implements SvgOptimizerRuleInterface
{
    #[\Override]
    public static function isRisky(): bool
    {
        return false;
    }

    /**
     * Normalize SVG attribute names to match the exact case from SvgAttribute enum.
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);

        /** @var \DOMNodeList<\DOMElement> $nodes */
        $nodes = $domXPath->query('//* | /*');

        static $lookup = null;
        if (null === $lookup) {
            $lookup = [];
            foreach (SvgAttribute::cases() as $case) {
                $normalized = mb_strtolower(str_replace('-', '', $case->value));
                $lookup[$normalized] = $case->value;
            }
        }

        foreach ($nodes as $node) {
            $attrs = iterator_to_array($node->attributes, false);

            foreach ($attrs as $attr) {
                $normalized = mb_strtolower(str_replace('-', '', $attr->name));

                if (\array_key_exists($normalized, $lookup) && $attr->name !== $lookup[$normalized]) {
                    $value = $attr->value;

                    if ($attr->namespaceURI) {
                        $node->removeAttributeNS($attr->namespaceURI, $attr->localName);
                        $node->setAttributeNS($attr->namespaceURI, $lookup[$normalized], $value);
                    } else {
                        $node->removeAttribute($attr->name);
                        $node->setAttribute($lookup[$normalized], $value);
                    }
                }
            }
        }
    }

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return false;
    }
}
