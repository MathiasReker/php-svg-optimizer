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
 * Keeps references intact when ids are renamed.
 *
 * @internal
 *
 * @no-named-arguments
 */
trait IdRenameTrait
{
    /**
     * Attributes that reference elements by id other than through `url(#id)`: `href` fragments, animation timing and
     * ARIA id lists.
     */
    private const string ID_REFERENCE_QUERY = './/@*[local-name()="href" or local-name()="begin" or local-name()="end"'
        . ' or local-name()="aria-labelledby" or local-name()="aria-describedby" or local-name()="aria-controls"'
        . ' or local-name()="aria-owns" or local-name()="aria-flowto" or local-name()="aria-activedescendant"'
        . ' or local-name()="aria-details" or local-name()="aria-errormessage"]';

    /**
     * The id before the event of a timing value such as `b.click` or `b.end+1s`, at the start of each `;` item.
     */
    private const string TIMING_REFERENCE_REGEX = '/((?:^|;)\s*)([\w\-]+)(?=\.)/';

    private const string ID_LIST_SEPARATOR_REGEX = '/\s+/';

    /**
     * Updates references to renamed ids in `href` fragments, animation timing (`begin="id.click"`) and ARIA id lists.
     *
     * @param array<string, string> $idReplacements
     */
    private function updateIdReferenceAttributes(
        \DOMXPath $domXPath,
        \DOMElement $domElement,
        array $idReplacements,
    ): void {
        $attributes = [] === $idReplacements ? false : $domXPath->query(self::ID_REFERENCE_QUERY, $domElement);

        foreach (false === $attributes ? [] : $attributes as $attribute) {
            if ($attribute instanceof \DOMAttr) {
                $attribute->value = match ((string) $attribute->localName) {
                    SvgAttribute::Href->value => $this->replaceFragmentId($attribute->value, $idReplacements),
                    SvgAttribute::Begin->value, SvgAttribute::End->value => (string) preg_replace_callback(
                        self::TIMING_REFERENCE_REGEX,
                        static fn (array $match): string => $match[1] . ($idReplacements[$match[2]] ?? $match[2]),
                        $attribute->value,
                    ),
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
        $id = mb_substr($href, 1);

        return str_starts_with($href, '#') && \array_key_exists($id, $idReplacements) ? '#' . $idReplacements[$id] : $href;
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
