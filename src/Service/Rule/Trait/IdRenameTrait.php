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

/**
 * @internal
 *
 * @no-named-arguments
 */
trait IdRenameTrait
{
    private const string ID_REFERENCE_QUERY = './/@*[local-name()="href" or local-name()="begin" or local-name()="end"'
        . ' or local-name()="to" or local-name()="from" or local-name()="values"'
        . ' or local-name()="aria-labelledby" or local-name()="aria-describedby" or local-name()="aria-controls"'
        . ' or local-name()="aria-owns" or local-name()="aria-flowto" or local-name()="aria-activedescendant"'
        . ' or local-name()="aria-details" or local-name()="aria-errormessage"]';

    private const string TIMING_REFERENCE_REGEX = '/((?:^|;)\s*)([\w\-]+)(?=\.)/';

    private const string ID_LIST_SEPARATOR_REGEX = '/\s+/';

    private const string SMIL_FRAGMENT_REGEX = '/((?:^|;)\s*)#([^;\s]+)(?=\s*(?:;|$))/';

    /**
     * @param array<string, string> $idReplacements
     */
    private function updateIdReferenceAttributes(
        \DOMXPath $domXPath,
        \DOMElement $domElement,
        array $idReplacements,
    ): void {
        $attributes = [] === $idReplacements ? false : $domXPath->query(self::ID_REFERENCE_QUERY, $domElement, false);

        foreach (false === $attributes ? [] : $attributes as $attribute) {
            if ($attribute instanceof \DOMAttr) {
                $attribute->textContent = match ($attribute->localName) {
                    SvgAttribute::Href->value => $this->replaceFragmentId($attribute->value, $idReplacements),
                    SvgAttribute::To->value, SvgAttribute::From->value, SvgAttribute::Values->value => $this->replaceAnimatedFragmentIds($attribute, $idReplacements),
                    SvgAttribute::Begin->value, SvgAttribute::End->value => preg_replace_callback(
                        self::TIMING_REFERENCE_REGEX,
                        static fn (array $match): string => $match[1] . ($idReplacements[$match[2]] ?? $match[2]),
                        $attribute->value,
                    ) ?? $attribute->value,
                    default => implode(' ', array_map(
                        static fn (string $id): string => $idReplacements[$id] ?? $id,
                        $this->idList($attribute->value),
                    )),
                };
            }
        }
    }

    /**
     * @param string                $href           the href value
     * @param array<string, string> $idReplacements
     */
    private function replaceFragmentId(string $href, array $idReplacements): string
    {
        $fragment = trim($href);
        $id = mb_substr($fragment, 1);

        return str_starts_with($fragment, '#') && \array_key_exists($id, $idReplacements) ? '#' . $idReplacements[$id] : $href;
    }

    /**
     * @param \DOMAttr              $domAttr        a `to`, `from` or `values` attribute
     * @param array<string, string> $idReplacements
     *
     * @return string the value with the ids replaced if the attribute animates a link, otherwise the value unchanged
     */
    private function replaceAnimatedFragmentIds(\DOMAttr $domAttr, array $idReplacements): string
    {
        $animatedAttribute = $domAttr->ownerElement?->getAttribute(SvgAttribute::AttributeName->value) ?? '';

        if (SvgAttribute::Href->value !== $animatedAttribute && !str_ends_with($animatedAttribute, ':' . SvgAttribute::Href->value)) {
            return $domAttr->value;
        }

        return preg_replace_callback(
            self::SMIL_FRAGMENT_REGEX,
            static fn (array $match): string => $match[1] . '#' . ($idReplacements[$match[2]] ?? $match[2]),
            $domAttr->value,
        ) ?? $domAttr->value;
    }

    /**
     * @param string $value a whitespace-separated list of ids
     *
     * @return list<string> the ids
     */
    private function idList(string $value): array
    {
        $ids = preg_split(self::ID_LIST_SEPARATOR_REGEX, trim($value));

        return false === $ids ? [] : $ids;
    }
}
