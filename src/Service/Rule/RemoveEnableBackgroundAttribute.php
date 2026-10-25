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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\Svg2AttributeMigrationTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveEnableBackgroundAttribute implements SvgOptimizerRuleInterface
{
    use Svg2AttributeMigrationTrait;

    private const array ENABLE_BACKGROUND = [SvgAttribute::EnableBackground->value];

    #[\Override]
    public static function isRisky(): bool
    {
        return true;
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
        $css = $this->styleSheetText($domDocument);
        $removable = $this->removableAttributes($domDocument, $css, self::ENABLE_BACKGROUND);
        $migratesStyles = [] !== $this->removableAttributes($domDocument, $css, [SvgAttribute::Style->value]);

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            if ($this->isForeignContent($domNodeList)) {
                continue;
            }

            $this->removeObsoleteAttributes($domNodeList, $removable);

            if ($migratesStyles) {
                $this->migrateStyleAttribute($domNodeList, self::ENABLE_BACKGROUND, []);
            }
        }
    }
}
