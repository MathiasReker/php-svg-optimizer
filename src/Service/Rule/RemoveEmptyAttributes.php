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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\CssDeclarationTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveEmptyAttributes implements SvgOptimizerRuleInterface
{
    use CssDeclarationTrait;
    use ElementTrait;

    private const array CONDITIONAL_ATTRIBUTES = [
        SvgAttribute::RequiredExtensions->value,
        SvgAttribute::RequiredFeatures->value,
        SvgAttribute::SystemLanguage->value,
    ];

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
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $css = implode(' ', array_map(
            static fn (\DOMElement $domElement): string => $domElement->textContent,
            $this->elementsNamed($domDocument, SvgTag::Style->value),
        ));

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            if (!$this->isSvgElement($domNodeList)) {
                continue;
            }

            foreach (iterator_to_array($domNodeList->attributes, false) as $domAttr) {
                if ('' === trim($domAttr->value)
                    && !\in_array($domAttr->nodeName, self::CONDITIONAL_ATTRIBUTES, true)
                    && !$this->hasAttributeSelector($css, $domAttr->name)
                ) {
                    $domNodeList->removeAttributeNode($domAttr);
                }
            }
        }
    }
}
