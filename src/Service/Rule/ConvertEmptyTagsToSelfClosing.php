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

final readonly class ConvertEmptyTagsToSelfClosing extends AbstractXmlProcessor implements SvgOptimizerRuleInterface
{
    /**
     * Matches empty tags like `<rect></rect>` for conversion to self-closing tags.
     *
     * @see https://regex101.com/r/HolZXY/1
     */
    private const string EMPTY_TAG_REGEX = '/<([a-zA-Z][a-zA-Z0-9-]*)([^>]*)>\s*<\/\1>/';

    /**
     * Matches self-closing tags with spaces before the slash (e.g., `<rect />`) to remove the space.
     *
     * @see https://regex101.com/r/Le1XFu/1
     */
    private const string SELF_CLOSING_SPACE_REGEX = '/<([a-zA-Z][a-zA-Z0-9-]*)([^>]*)\s+\/>/';

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
     * Converts empty tags to their self-closing form (e.g., `<tag></tag>` to `<tag/>`).
     *
     * This rule also cleans up self-closing tags by removing any space before
     * the closing slash (e.g., `<tag />` to `<tag/>`), further reducing file size.
     *
     * @param \DOMDocument $domDocument the DOM document to optimize
     *
     * @throws XmlProcessingException if the XML content cannot be processed
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $this->process($domDocument, $this->convertEmptyTagsToSelfClosing(...));
    }

    /**
     * Applies regex transformations to convert empty tags and clean up self-closing tags.
     *
     * @param string $content the raw SVG content
     *
     * @return string the modified SVG content
     */
    private function convertEmptyTagsToSelfClosing(string $content): string
    {
        $content = preg_replace(self::EMPTY_TAG_REGEX, '<$1$2/>', $content) ?? $content;

        return preg_replace(self::SELF_CLOSING_SPACE_REGEX, '<$1$2/>', $content) ?? $content;
    }
}
