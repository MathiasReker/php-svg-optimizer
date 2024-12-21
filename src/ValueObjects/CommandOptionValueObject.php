<?php

/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\ValueObjects;

/**
 * Value Object that represents a single command line argument option.
 */
final readonly class CommandOptionValueObject
{
    /**
     * Constructor for CommandOptionValueObject.
     *
     * @param string $title       The title of the command line argument option
     * @param string $description The description of the command line argument option
     */
    public function __construct(private string $title, private string $description)
    {
    }

    /**
     * Get the title of the command line argument option.
     *
     * @return string The title of the command line argument option
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get the description of the command line argument option.
     *
     * @return string The description of the command line argument option
     */
    public function getDescription(): string
    {
        return $this->description;
    }
}
