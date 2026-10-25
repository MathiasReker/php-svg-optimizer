<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\DefinitionElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\FragmentTargetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveUnusedIds implements PassInterface
{
    use DefinitionElementTrait;
    use FragmentTargetTrait;
    use IdReferenceTrait;

    private const array EXTERNALLY_TARGETED_ELEMENTS = ['symbol', 'view'];

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->maySelectAnyId($domDocument)) {
            return;
        }

        $referencedIds = $this->reachableReferencedIds($domDocument, self::REFERENCE_ONLY_ELEMENTS, self::EXTERNALLY_TARGETED_ELEMENTS, $this->fragmentTargetIds($domDocument));

        foreach ($domDocument->getElementsByTagName('*') as $element) {
            if ($element === $domDocument->documentElement) {
                continue;
            }

            if (!\array_key_exists($element->getAttribute(Attribute::Id->value), $referencedIds)) {
                $element->removeAttribute(Attribute::Id->value);
            }

            if (!\array_key_exists($element->getAttributeNS(NamespaceUri::Xml->value, Attribute::Id->value), $referencedIds)) {
                $element->removeAttributeNS(NamespaceUri::Xml->value, Attribute::Id->value);
            }
        }
    }
}
