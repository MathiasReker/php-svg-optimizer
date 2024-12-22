<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\ValueObjects;

/**
 * Value Object that represents a single command line argument option.
 */
final readonly class ArgumentOptionValueObject
{
    /**
     * Constructor for ArgumentOptionValueObject.
     *
     * @param string $shorthand   The shorthand name of the argument option
     * @param string $full        The full name of the argument option
     * @param string $description The description of the argument option
     */
    public function __construct(private string $shorthand, private string $full, private string $description)
    {
    }

    /**
     * Get the shorthand name of the argument option.
     */
    public function getShorthand(): string
    {
        return $this->shorthand;
    }

    /**
     * Get the full name of the argument option.
     */
    public function getFull(): string
    {
        return $this->full;
    }

    /**
     * Get the description of the argument option.
     */
    public function getDescription(): string
    {
        return $this->description;
    }
}
