<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Svg2\AttributeMigrationTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveEnableBackground implements PassInterface
{
    use AttributeMigrationTrait;
    use ElementTrait;
    use StyleSheetTrait;

    private const array ENABLE_BACKGROUND = [Attribute::EnableBackground->value];

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        $css = $this->styleSheetText($domDocument);
        $removable = $this->unselectedAttributes($domDocument, $css, self::ENABLE_BACKGROUND);
        $migratesStyles = [] !== $this->unselectedAttributes($domDocument, $css, [Attribute::Style->value]);

        foreach ($domDocument->getElementsByTagName('*') as $element) {
            if ($this->isForeignContent($element)) {
                continue;
            }

            $this->removeObsoleteAttributes($element, $removable);

            if ($migratesStyles) {
                $this->migrateStyleAttribute($element, self::ENABLE_BACKGROUND, []);
            }
        }
    }
}
