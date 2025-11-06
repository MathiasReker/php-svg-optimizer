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
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Service\Processor\AbstractXmlProcessor;

/**
 * @no-named-arguments
 */
final readonly class RemoveDoctype extends AbstractXmlProcessor implements SvgOptimizerRuleInterface
{
    /**
     * Regular expression to match the DOCTYPE declaration.
     *
     * This regex pattern is used to identify and remove DOCTYPE declarations
     * from the SVG content.
     *
     * @see https://regex101.com/r/DIe4La/1
     */
    private const string DOCTYPE_REGEX = '/<!DOCTYPE[^>]*>/i';

    /**
     * Optimizes the given \DOMDocument by removing the DOCTYPE declaration.
     *
     * @param \DOMDocument $domDocument The \DOMDocument to optimize
     *
     * @throws XmlProcessingException If an error occurs during processing
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $this->process($domDocument, $this->removeDoctype(...));
    }

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return false;
    }

    /**
     * Removes the DOCTYPE declaration from the SVG content.
     *
     * @param string $content The SVG content as a string
     *
     * @return string The SVG content without the DOCTYPE declaration
     */
    private function removeDoctype(string $content): string
    {
        return (string) preg_replace(self::DOCTYPE_REGEX, '', $content);
    }
}
