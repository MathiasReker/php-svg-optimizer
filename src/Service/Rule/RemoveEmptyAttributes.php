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
    /**
     * Regex pattern to match multiple consecutive spaces.
     *
     * @see https://regex101.com/r/OuyK7V/1
     */
    private const string MULTIPLE_SPACES_REGEX = '/\s+/';

    /**
     * Optimizes the provided \DOMDocument by removing empty or whitespace-only attributes.
     *
     * This method iterates through all elements in the \DOMDocument and removes any attributes
     * that are empty or contain only whitespace. The modified content is then saved back into the
     * provided \DOMDocument object.
     *
     * @param \DOMDocument $domDocument The \DOMDocument object to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $this->removeEmptyAttributes($domDocument);
    }

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return false;
    }

    /**
     * Removes empty or whitespace-only attributes from all elements in the \DOMDocument.
     *
     * This method traverses all elements in the \DOMDocument and removes attributes that are
     * either empty or consist solely of whitespace characters.
     *
     * @param \DOMDocument $domDocument The \DOMDocument object to process
     */
    private function removeEmptyAttributes(\DOMDocument $domDocument): void
    {
        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            $this->removeEmptyAttributesFromElement($domNodeList);
        }
    }

    /**
     * Removes empty or whitespace-only attributes from a specific \DOMElement.
     *
     * This method checks each attribute of the given element and removes it if its value is
     * empty or contains only whitespace characters.
     *
     * @param \DOMElement $domElement The \DOMElement from which to remove empty attributes
     */
    private function removeEmptyAttributesFromElement(\DOMElement $domElement): void
    {
        /** @var \DOMAttr $domAttr */
        foreach (iterator_to_array($domElement->attributes ?? [], true) as $attrName => $domAttr) {
            if ($this->isEmptyOrWhitespace($domAttr->value)) {
                $domElement->removeAttribute($attrName);
            }
        }
    }

    /**
     * Checks if a string is empty or contains only whitespace characters.
     *
     * This method uses a regular expression to determine if the provided string is empty
     * or consists solely of whitespace characters (spaces, tabs, newlines, etc.).
     *
     * @param string $value The string to check
     *
     * @return bool True if the string is empty or contains only whitespace, false otherwise
     */
    private function isEmptyOrWhitespace(string $value): bool
    {
        return '' === preg_replace(self::MULTIPLE_SPACES_REGEX, '', $value);
    }
}
