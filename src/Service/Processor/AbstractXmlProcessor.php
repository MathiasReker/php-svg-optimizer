<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Processor;

use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;

/**
 * @no-named-arguments
 */
abstract readonly class AbstractXmlProcessor
{
    /**
     * Processes the SVG content by applying a callback and validating the result.
     *
     * This method saves the XML content of the provided \DOMDocument, applies a callback
     * function to optimize the SVG, checks the callback's result type, validates the
     * optimized content, and loads the content back into the \DOMDocument.
     *
     * @param \DOMDocument $domDocument the \DOMDocument containing the SVG content to be processed
     * @param callable     $callback    a callable function that will be applied to optimize the SVG content
     *
     * @param-immediately-invoked-callable $callback
     *
     * @return string the optimized SVG content
     *
     * @throws XmlProcessingException if any error occurs while processing, validating, or loading the XML content
     * @throws \ErrorException        When an error occurs during processing
     */
    final public function process(\DOMDocument $domDocument, callable $callback): string
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

            if (!$this->getValidator()->isValid($svgContent)) {
                throw new XmlProcessingException('Optimized SVG content is not valid.');
            }
        } catch (XmlProcessingException $e) {
            throw $e;
        } catch (\Exception $exception) {
            throw new XmlProcessingException('Failed to process the XML content.', 0, $exception);
        }

        // Convert warnings to exceptions during loadXML
        set_error_handler(
            static function (int $severity, string $message): never {
                throw new \ErrorException($message, 0, $severity);
            }
        );

        try {
            if (!$domDocument->loadXML($svgContent)) {
                throw new XmlProcessingException('Failed to load optimized XML content.');
            }
        } catch (\Throwable $e) {
            throw new XmlProcessingException('Failed to load optimized XML content.', 0, $e);
        } finally {
            restore_error_handler();
        }

        return $svgContent;
    }

    /**
     * Returns an instance of SvgValidator for validating SVG content.
     *
     * This method provides a dedicated SvgValidator instance to validate the SVG content
     * after processing. It can be overridden in subclasses if a different validator is needed.
     *
     * @return SvgValidator An instance of SvgValidator for validating SVG content
     */
    final protected function getValidator(): SvgValidator
    {
        return new SvgValidator();
    }
}
