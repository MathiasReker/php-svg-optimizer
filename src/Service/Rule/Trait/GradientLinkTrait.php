<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Trait;

use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;

/**
 * Resolves gradient inheritance: a gradient inherits stops and attributes it does not set from the gradient its `href`
 * or `xlink:href` links to.
 *
 * @no-named-arguments
 */
trait GradientLinkTrait
{
    /**
     * Bounds the walk through linked gradients, so a cycle cannot loop forever.
     */
    private const int MAX_LINK_DEPTH = 16;

    private const array GRADIENT_TAGS = ['linearGradient', 'radialGradient'];

    /**
     * @param \DOMElement                $gradient  the gradient to start from
     * @param array<string, \DOMElement> $gradients the gradients by id
     *
     * @return list<\DOMElement> the gradient followed by the gradients it links to, following at most MAX_LINK_DEPTH
     *                           links, so a cycle cannot loop forever
     */
    private function gradientChain(\DOMElement $gradient, array $gradients): array
    {
        $chain = [$gradient];

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
     * @param \DOMElement $gradient the gradient to read
     *
     * @return string the id of the gradient the element links to, or '' if it links to none or ambiguously
     */
    private function linkedGradientId(\DOMElement $gradient): string
    {
        $href = $gradient->getAttribute(SvgAttribute::Href->value);
        $xlinkHref = $gradient->getAttributeNS(SvgNamespace::Xlink->value, SvgAttribute::Href->value);
        $link = '' === $href ? $xlinkHref : $href;

        if (('' !== $href && '' !== $xlinkHref && $href !== $xlinkHref) || !str_starts_with($link, '#')) {
            return '';
        }

        return mb_substr($link, 1);
    }

    /**
     * @param list<\DOMElement> $chain the gradient and the gradients it links to
     *
     * @return string the gradient units, inherited from a linked gradient when not set, or '' for the default
     */
    private function gradientUnits(array $chain): string
    {
        foreach ($chain as $gradient) {
            if ($gradient->hasAttribute(SvgAttribute::GradientUnits->value)) {
                return trim($gradient->getAttribute(SvgAttribute::GradientUnits->value));
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

        foreach ($domDocument->getElementsByTagName('*') as $domElement) {
            foreach ([$domElement->getAttribute(SvgAttribute::Id->value), $this->xmlId($domElement)] as $id) {
                $idCounts[$id] = ($idCounts[$id] ?? 0) + 1;
            }

            if (\in_array($domElement->localName, self::GRADIENT_TAGS, true) && '' === $this->xmlId($domElement)) {
                $gradients[$domElement->getAttribute(SvgAttribute::Id->value)] = $domElement;
            }
        }

        unset($gradients['']);

        return array_intersect_key($gradients, array_filter($idCounts, static fn (int $count): bool => 1 === $count));
    }

    /**
     * @param \DOMElement $domElement the element to read
     */
    private function xmlId(\DOMElement $domElement): string
    {
        return $domElement->getAttributeNS(SvgNamespace::Xml->value, SvgAttribute::Id->value);
    }
}
