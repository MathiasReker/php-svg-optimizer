<?php

/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Commands\Helpers;

final readonly class ArgumentParser
{
    /**
     * Mapping of shorthand options to their full equivalents.
     *
     * @var array<string, string>
     */
    private const array OPTIONS = [
        '-h' => '--help',
        '-c' => '--config',
        '-d' => '--dry-run',
        '-q' => '--quiet',
    ];

    /**
     * Number of parts in an option string.
     */
    private const int OPTION_PARTS_COUNT = 2;

    /**
     * Index of the first positional argument.
     */
    private const int FIRST_POSITIONAL_ARGUMENT_INDEX = 0;

    /**
     * Constructor for the ArgumentParser class.
     *
     * @param array<string> $args Command-line arguments passed to the script
     */
    public function __construct(private array $args)
    {
    }

    /**
     * Checks if a specific option is present in the arguments.
     *
     * This method looks for both the full option (e.g., `--help`) and its shorthand equivalent (e.g., `-h`).
     *
     * @param string $option The option to check for
     *
     * @return bool True if the option is present, false otherwise
     */
    public function hasOption(string $option): bool
    {
        return \in_array($option, $this->args, true) || \in_array(array_search($option, self::OPTIONS, true), $this->args, true);
    }

    /**
     * Retrieves the value of a specific option, if it exists.
     *
     * @param string $option The option whose value needs to be retrieved
     *
     * @return string|null The value of the option, or null if it is not set
     */
    public function getOption(string $option): ?string
    {
        foreach ($this->args as $arg) {
            if (str_starts_with($arg, $option)) {
                $parts = explode('=', $arg, self::OPTION_PARTS_COUNT);
                if (self::OPTION_PARTS_COUNT === \count($parts)) {
                    return $parts[1];
                }
            }
        }

        return null;
    }

    /**
     * Get the index of the next positional argument after options/subcommands.
     *
     * @return int The index of the first positional argument
     *
     * @throws \InvalidArgumentException If no positional argument is found
     */
    public function getNextPositionalArgumentIndex(): int
    {
        foreach ($this->args as $index => $arg) {
            if (!str_starts_with($arg, '-') && self::FIRST_POSITIONAL_ARGUMENT_INDEX !== $index) {
                return $index;
            }
        }

        echo 'Error: No positional argument found.';
        exit(1);
    }
}
