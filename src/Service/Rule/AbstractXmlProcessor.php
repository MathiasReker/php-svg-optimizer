<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Processing\XmlProcessor;

/**
 * @no-named-arguments
 */
abstract readonly class AbstractXmlProcessor
{
    /**
     * The XML processor instance used to manipulate XML documents.
     */
    protected XmlProcessor $xmlProcessor;

    public function __construct()
    {
        $this->xmlProcessor = new XmlProcessor();
    }
}
