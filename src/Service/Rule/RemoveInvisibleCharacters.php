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
final readonly class RemoveInvisibleCharacters extends AbstractXmlProcessor implements SvgOptimizerRuleInterface
{
    /**
     * Regex pattern for removing invisible characters in HTML entity format.
     *
     * This regex removes all invisible or non-printing characters, including
     * control characters, whitespace, tabs, newlines, zero-width spaces, soft hyphens, etc.
     *
     * @see https://regex101.com/r/7HAFNv/1
     */
    private const string INVISIBLE_CHARACTERS_REGEX = '/&#x(?:200B|200C|200D|2028|2029|AD|0A|0D|09|D);/u';

    /**
     * Remove invisible characters from the SVG document.
     *
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     *
     * @throws XmlProcessingException When XML content cannot be saved or loaded
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $this->process($domDocument, $this->removeInvisibleCharacters(...));
    }

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return false;
    }

    /**
     * Remove invisible characters from the SVG content.
     *
     * @param string $content The SVG content to process
     *
     * @return string The processed SVG content with invisible characters removed
     */
    private function removeInvisibleCharacters(string $content): string
    {
        return preg_replace(self::INVISIBLE_CHARACTERS_REGEX, '', $content) ?? $content;
    }
}
