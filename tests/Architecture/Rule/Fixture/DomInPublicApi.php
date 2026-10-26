<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer {
    /**
     * @no-named-arguments
     */
    final class LeakingApi
    {
        public function load(\DOMDocument $domDocument): string
        {
            return (string) $domDocument->saveXML();
        }

        public function element(): ?\DOMElement
        {
            return null;
        }

        public function content(string $svg): string
        {
            return $svg;
        }

        private function hidden(\DOMDocument $domDocument): \DOMNode
        {
            return $domDocument;
        }
    }
}

namespace MathiasReker\PhpSvgOptimizer\Pass {
    /**
     * @no-named-arguments
     */
    final class InternalPass
    {
        public function apply(\DOMDocument $domDocument): void
        {
        }
    }
}
