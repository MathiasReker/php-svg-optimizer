<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Pass\PassInterface;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveInvisibleCharacters implements PassInterface
{
    use ElementTrait;

    private const string INVISIBLE_CHARACTERS_REGEX = '/[\x{AD}\x{200B}-\x{200D}\x{2028}\x{2029}]/u';

    private const string XPATH_TEXT_NODES = '//text()';

    private const array TEXT_CONTENT_TAGS = [
        SvgTag::Desc->value,
        SvgTag::ForeignObject->value,
        SvgTag::Metadata->value,
        SvgTag::Script->value,
        SvgTag::Style->value,
        SvgTag::Text->value,
        SvgTag::TextPath->value,
        SvgTag::Title->value,
        SvgTag::Tref->value,
        SvgTag::Tspan->value,
    ];

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        /** @var \DOMNodeList<\DOMText> $domNodeList */
        $domNodeList = (new \DOMXPath($domDocument))->query(self::XPATH_TEXT_NODES, null, false);

        $keepsElementsNonEmpty = $this->hasSiblingSensitiveStyle($domDocument);

        foreach ($domNodeList as $domText) {
            $data = preg_replace(self::INVISIBLE_CHARACTERS_REGEX, '', $domText->data) ?? $domText->data;

            if ($this->isTextContent($domText) || ('' === $data && $keepsElementsNonEmpty)) {
                continue;
            }

            $domText->data = $data;

            if ('' === $data) {
                $domText->remove();
            }
        }
    }

    /**
     * @param \DOMText $domText the text node to check
     *
     * @return bool true if the text renders, is read out or is processed, as inside a `<text>`, a `<title>`, a
     *              `<style>` or an element outside the SVG namespace
     */
    private function isTextContent(\DOMText $domText): bool
    {
        for ($node = $domText->parentNode; $node instanceof \DOMElement; $node = $node->parentNode) {
            if (!$this->isSvgElement($node) || \in_array($node->localName, self::TEXT_CONTENT_TAGS, true)) {
                return true;
            }
        }

        return false;
    }
}
