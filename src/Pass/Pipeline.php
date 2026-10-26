<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

use MathiasReker\PhpSvgOptimizer\Exception\InvalidSvgException;
use MathiasReker\PhpSvgOptimizer\Xml\ParseException;
use MathiasReker\PhpSvgOptimizer\Xml\Parser;
use MathiasReker\PhpSvgOptimizer\Xml\Serializer;
use MathiasReker\PhpSvgOptimizer\Xml\SvgValidator;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class Pipeline
{
    private const string INVALID_SVG = 'The file does not appear to be a valid SVG file.';

    /** @var list<PassInterface> */
    private array $passes;

    private Parser $parser;

    private Serializer $serializer;

    private SvgValidator $svgValidator;

    /**
     * @param list<PassInterface> $passes the passes to run, in execution order whatever their order in the list
     */
    public function __construct(array $passes)
    {
        $this->passes = ExecutionOrder::sort($passes);
        $this->parser = new Parser();
        $this->serializer = new Serializer();
        $this->svgValidator = new SvgValidator();
    }

    /**
     * @param string $svg the SVG to optimize
     *
     * @return string the optimized SVG
     *
     * @throws InvalidSvgException If the content is not a well-formed SVG document
     */
    public function run(string $svg): string
    {
        try {
            $domDocument = $this->parser->parse($svg);

            if (!$this->svgValidator->isValidDocument($domDocument)) {
                throw new InvalidSvgException(self::INVALID_SVG);
            }

            return $this->serializer->serialize($this->applyPasses($domDocument));
        } catch (ParseException $parseException) {
            throw new InvalidSvgException(self::INVALID_SVG, 0, $parseException);
        }
    }

    /**
     * @param \DOMDocument $domDocument the document to optimize
     *
     * @return \DOMDocument the optimized document, which is a snapshot taken before a pass whose result was reverted
     *
     * @throws ParseException If a snapshot cannot be taken
     */
    private function applyPasses(\DOMDocument $domDocument): \DOMDocument
    {
        foreach ($this->passes as $pass) {
            if (!$pass instanceof RevertIfLargerPassInterface) {
                $pass->apply($domDocument);

                continue;
            }

            $snapshot = $this->parser->copy($domDocument);
            $sizeBeforePass = mb_strlen($this->serializer->serialize($domDocument), '8bit');
            $pass->apply($domDocument);

            if (mb_strlen($this->serializer->serialize($domDocument), '8bit') > $sizeBeforePass) {
                $domDocument = $snapshot;
            }
        }

        return $domDocument;
    }
}
