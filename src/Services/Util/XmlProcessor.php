<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Services\Util;

use DOMDocument;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Services\Validators\SvgValidator;

final readonly class XmlProcessor
{
    private SvgValidator $svgValidator;

    public function __construct()
    {
        $this->svgValidator = new SvgValidator();
    }

    /**
     * Processes the SVG content by applying a callback and validating the result.
     *
     * This method saves the XML content of the provided DOMDocument, applies a callback
     * function to optimize the SVG, checks the callback's result type, validates the
     * optimized content, and loads the content back into the DOMDocument.
     *
     * @param \DOMDocument $domDocument The DOMDocument containing the SVG content to be processed.
     * @param callable     $callback    A callable function that will be applied to optimize the SVG content.
     *
     * @return string The optimized SVG content.
     *
     * @throws XmlProcessingException If any error occurs while processing, validating, or loading the XML content.
     */
    public function process(\DOMDocument $domDocument, callable $callback): string
    {
        $svgContent = $domDocument->saveXML();
        if (false === $svgContent) {
            throw new XmlProcessingException('Failed to save SVG XML content.');
        }

        try {
            $svgContent = $callback($svgContent);

            if (!\is_string($svgContent)) {
                throw new XmlProcessingException('Callback must return a string.');
            }

            if (!$this->svgValidator->isValid($svgContent)) {
                throw new XmlProcessingException('Optimized SVG content is not valid.');
            }
        } catch (\Exception $exception) {
            throw new XmlProcessingException('Failed to process the XML content.', 0, $exception);
        }

        if (!$domDocument->loadXML($svgContent)) {
            throw new XmlProcessingException('Failed to load optimized XML content.');
        }

        return $svgContent;
    }
}
