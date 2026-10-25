<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass\Trait\Paint;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait GradientLinkTrait
{
    use IdTrait;

    private const int MAX_LINK_DEPTH = 16;

    private const array GRADIENT_ELEMENTS = ['linearGradient', 'radialGradient'];

    /**
     * @param \DOMElement                $domElement the gradient to start from
     * @param array<string, \DOMElement> $gradients  the gradients by id
     *
     * @return list<\DOMElement> the gradient followed by the gradients it links to, following at most MAX_LINK_DEPTH
     *                           links, so a cycle cannot loop forever
     */
    private function gradientChain(\DOMElement $domElement, array $gradients): array
    {
        $chain = [$domElement];

        for ($depth = 0; $depth < self::MAX_LINK_DEPTH; ++$depth) {
            $linkedId = $this->linkedGradientId($chain[$depth]);

            if (!\array_key_exists($linkedId, $gradients)) {
                break;
            }

            $chain[] = $gradients[$linkedId];
        }

        return $chain;
    }

    /**
     * @param \DOMElement $domElement the gradient to read
     *
     * @return string the id of the gradient the element links to, or '' if it links to none or ambiguously
     */
    private function linkedGradientId(\DOMElement $domElement): string
    {
        $href = $domElement->getAttribute(Attribute::Href->value);
        $xlinkHref = $domElement->getAttributeNS(NamespaceUri::Xlink->value, Attribute::Href->value);
        $link = '' === $href ? $xlinkHref : $href;

        if (('' !== $href && '' !== $xlinkHref && $href !== $xlinkHref) || !str_starts_with($link, '#')) {
            return '';
        }

        return mb_substr($link, 1);
    }

    /**
     * @param list<\DOMElement> $chain     the gradient and the gradients it links to
     * @param string            $attribute the name of an attribute that a gradient inherits from the gradient it links to
     *
     * @return string the trimmed attribute value, inherited from a linked gradient when not set, or '' for the default
     */
    private function linkedGradientAttribute(array $chain, string $attribute): string
    {
        foreach ($chain as $gradient) {
            if ($gradient->hasAttribute($attribute)) {
                return trim($gradient->getAttribute($attribute));
            }
        }

        return '';
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to scan
     *
     * @return array<string, \DOMElement> the gradients whose id is unique in the document
     */
    private function gradientsById(\DOMDocument $domDocument): array
    {
        $idCounts = [];
        $gradients = [];

        foreach ($domDocument->getElementsByTagName('*') as $element) {
            foreach ([$element->getAttribute(Attribute::Id->value), $this->xmlId($element)] as $id) {
                $idCounts[$id] = ($idCounts[$id] ?? 0) + 1;
            }

            if (\in_array($element->localName, self::GRADIENT_ELEMENTS, true) && '' === $this->xmlId($element)) {
                $gradients[$element->getAttribute(Attribute::Id->value)] = $element;
            }
        }

        unset($gradients['']);

        return array_intersect_key($gradients, array_filter($idCounts, static fn (int $count): bool => 1 === $count));
    }
}
