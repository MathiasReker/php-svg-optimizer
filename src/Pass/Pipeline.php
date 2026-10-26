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

            return $this->serializer->serialize($this->applyPasses($domDocument, 0));
        } catch (ParseException $parseException) {
            throw new InvalidSvgException(self::INVALID_SVG, 0, $parseException);
        }
    }

    /**
     * @param \DOMDocument $domDocument the document to optimize
     * @param int          $firstPass   the position of the first pass to apply
     *
     * @return \DOMDocument the optimized document, which is a snapshot taken before a pass whose result was reverted
     *
     * @throws ParseException If a snapshot cannot be taken
     */
    private function applyPasses(\DOMDocument $domDocument, int $firstPass): \DOMDocument
    {
        foreach (\array_slice($this->passes, $firstPass) as $offset => $pass) {
            if ($pass instanceof SizeGuardedPassInterface) {
                return $this->applySmallerVariant($domDocument, $pass, $firstPass + $offset + 1);
            }

            $domDocument = $this->applyPass($domDocument, $pass);
        }

        return $domDocument;
    }

    /**
     * @param \DOMDocument  $domDocument the document to transform
     * @param PassInterface $pass        the pass to apply
     *
     * @return \DOMDocument the transformed document, or a snapshot taken before the pass if the pass is checked and
     *                      made the document larger
     *
     * @throws ParseException If a snapshot cannot be taken
     */
    private function applyPass(\DOMDocument $domDocument, PassInterface $pass): \DOMDocument
    {
        if (!$pass instanceof RevertIfLargerPassInterface) {
            $pass->apply($domDocument);

            return $domDocument;
        }

        $snapshot = $this->parser->copy($domDocument);
        $sizeBeforePass = $this->size($domDocument);
        $pass->apply($domDocument);

        return $this->size($domDocument) > $sizeBeforePass ? $snapshot : $domDocument;
    }

    /**
     * @param \DOMDocument             $domDocument     the document to transform
     * @param SizeGuardedPassInterface $sizeGuardedPass the pass to apply with and without its size guard
     * @param int                      $nextPass        the position of the first pass after it
     *
     * @return \DOMDocument the document after the remaining passes, from the variant of the pass that ends smaller;
     *                      the guarded variant when both end the same size
     *
     * @throws ParseException If a snapshot cannot be taken
     */
    private function applySmallerVariant(\DOMDocument $domDocument, SizeGuardedPassInterface $sizeGuardedPass, int $nextPass): \DOMDocument
    {
        $unguarded = $this->parser->copy($domDocument);
        $sizeGuardedPass->apply($domDocument);
        $sizeGuardedPass->applyWithoutSizeGuard($unguarded);

        if ($this->serializer->serialize($unguarded) === $this->serializer->serialize($domDocument)) {
            return $this->applyPasses($domDocument, $nextPass);
        }

        $guarded = $this->applyPasses($domDocument, $nextPass);
        $unguarded = $this->applyPasses($unguarded, $nextPass);

        return $this->size($unguarded) < $this->size($guarded) ? $unguarded : $guarded;
    }

    /**
     * @param \DOMDocument $domDocument the document to measure
     *
     * @return int the size of the serialized document in bytes
     */
    private function size(\DOMDocument $domDocument): int
    {
        return mb_strlen($this->serializer->serialize($domDocument), '8bit');
    }
}
