<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementNameTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveUseBombs implements PassInterface
{
    use ElementNameTrait;
    use IdTrait;

    private const int MAX_USE_INSTANCES = 1_000;

    private const string LOCAL_REFERENCE_PREFIX = '#';

    /**
     * @param \DOMDocument $domDocument the DOM document to sanitize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        $this->removeUseBombs($domDocument);
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to clean
     */
    private function removeUseBombs(\DOMDocument $domDocument): void
    {
        if (!$domDocument->documentElement instanceof \DOMElement) {
            return;
        }

        /** @var \SplObjectStorage<\DOMElement, int> $instances */
        $instances = new \SplObjectStorage();
        /** @var \SplObjectStorage<\DOMElement, null> $unsafe */
        $unsafe = new \SplObjectStorage();

        $this->countUseInstances($domDocument->documentElement, $this->elementsById($domDocument), $instances, $unsafe);

        foreach ($unsafe as $domElement) {
            $domElement->remove();
        }
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to read
     *
     * @return array<string, \DOMElement> the first element with each id or xml:id
     */
    private function elementsById(\DOMDocument $domDocument): array
    {
        $ids = [];

        foreach ($domDocument->getElementsByTagName('*') as $element) {
            foreach ($this->elementIds($element) as $id) {
                $ids[$id] ??= $element;
            }
        }

        return $ids;
    }

    /**
     * @param \DOMElement                          $domElement the element whose subtree to count
     * @param array<string, \DOMElement>           $ids        the elements by id
     * @param \SplObjectStorage<\DOMElement, int>  $instances  the use instances counted so far, by element, with -1 while counting
     * @param \SplObjectStorage<\DOMElement, null> $unsafe     the use elements that form a cycle or render too many instances
     *
     * @return int the number of use instances the subtree renders, capped at the limit
     */
    private function countUseInstances(\DOMElement $domElement, array $ids, \SplObjectStorage $instances, \SplObjectStorage $unsafe): int
    {
        if ($instances->offsetExists($domElement)) {
            return $instances[$domElement];
        }

        $instances[$domElement] = -1;
        $count = 0;

        foreach ($domElement->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $count += $this->countUseInstances($child, $ids, $instances, $unsafe);
            }
        }

        $count += $this->countReferencedInstances($domElement, $ids, $instances, $unsafe);

        return $instances[$domElement] = min($count, self::MAX_USE_INSTANCES);
    }

    /**
     * @param \DOMElement $domElement the element to read
     *
     * @return string the id a use element references within the document, or an empty string
     */
    private function localUseReference(\DOMElement $domElement): string
    {
        if (!$this->hasLocalNameIgnoringCase($domElement, [Element::Use->value])) {
            return '';
        }

        $href = trim($domElement->hasAttribute(Attribute::Href->value)
            ? $domElement->getAttribute(Attribute::Href->value)
            : $domElement->getAttributeNS(NamespaceUri::Xlink->value, Attribute::Href->value));

        return str_starts_with($href, self::LOCAL_REFERENCE_PREFIX) ? mb_substr($href, 1) : '';
    }

    /**
     * @param \DOMElement                          $domElement the element that may reference a target
     * @param array<string, \DOMElement>           $ids        the elements by id
     * @param \SplObjectStorage<\DOMElement, int>  $instances  the use instances counted so far, by element, with -1 while counting
     * @param \SplObjectStorage<\DOMElement, null> $unsafe     the use elements that form a cycle or render too many instances
     *
     * @return int the number of use instances the element renders through its reference
     */
    private function countReferencedInstances(\DOMElement $domElement, array $ids, \SplObjectStorage $instances, \SplObjectStorage $unsafe): int
    {
        $target = $ids[$this->localUseReference($domElement)] ?? null;

        if (!$target instanceof \DOMElement) {
            return 0;
        }

        $targetInstances = $instances->offsetExists($target) && -1 === $instances[$target]
            ? self::MAX_USE_INSTANCES
            : $this->countUseInstances($target, $ids, $instances, $unsafe);

        if ($targetInstances >= self::MAX_USE_INSTANCES) {
            $unsafe->offsetSet($domElement);

            return 0;
        }

        return 1 + $targetInstances;
    }
}
