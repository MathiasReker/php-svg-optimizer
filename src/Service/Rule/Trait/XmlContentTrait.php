<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Trait;

use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Service\Processor\Trait\XmlLoaderTrait;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait XmlContentTrait
{
    use XmlLoaderTrait;

    /**
     * @param \DOMDocument             $domDocument the document whose markup is rewritten
     * @param \Closure(string): string $callback    rewrites the markup of the document
     *
     * @param-immediately-invoked-callable $callback
     *
     * @return string the rewritten markup, which the document is loaded from
     *
     * @throws XmlProcessingException if the markup cannot be written, rewritten or loaded, or is not a valid SVG
     */
    private function processXmlContent(\DOMDocument $domDocument, \Closure $callback): string
    {
        $original = (string) $domDocument->saveXML();

        try {
            $content = $callback($original);
        } catch (XmlProcessingException $xmlProcessingException) {
            throw $xmlProcessingException;
        } catch (\Exception $exception) {
            throw new XmlProcessingException('Failed to process the XML content.', 0, $exception);
        }

        if ($content === $original) {
            return $content;
        }

        if ('' === $content || !(new SvgValidator())->isValid($content) || !$this->loadXmlSilently($domDocument, $content)) {
            throw new XmlProcessingException('Optimized SVG content is not valid.');
        }

        return $content;
    }
}
