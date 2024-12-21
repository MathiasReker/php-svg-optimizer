<?php

/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Services\Util;

use MathiasReker\PhpSvgOptimizer\Enums\Option;
use MathiasReker\PhpSvgOptimizer\Models\ArgumentOptionValueObject;
use MathiasReker\PhpSvgOptimizer\Services\Data\ArgumentData;

final readonly class ArgumentParser
{
    /**
     * Index of the first positional argument.
     */
    private const int FIRST_POSITIONAL_ARGUMENT_INDEX = 0;

    /**
     * The ArgumentData instance.
     */
    private ArgumentData $argumentData;

    /**
     * Constructor for the ArgumentParser class.
     *
     * @param array<string> $args Command-line arguments passed to the script
     */
    public function __construct(private array $args)
    {
        $this->argumentData = new ArgumentData();
    }

    /**
     * Check if the given option is present in the command-line arguments.
     *
     * @return bool True if the option is present, false otherwise
     */
    public function hasOption(Option $option): bool
    {
        $argsObject = array_filter(array_map(fn (string $arg): ?ArgumentOptionValueObject => $this->isOption($arg)
            ? $this->argumentData->getOptionByName($this->getOptionKey($arg))
            : null, $this->args));

        return \in_array($this->argumentData->getOption($option->value), $argsObject, true);
    }

    /**
     * Check if the given argument is an option.
     *
     * @return bool True if the argument is an option, false otherwise
     */
    private function isOption(string $option): bool
    {
        return str_starts_with($option, '-');
    }

    /**
     * Get the key of the given option from the command-line arguments.
     *
     * @param string $option The option to get the key of
     *
     * @return string The key of the option
     */
    private function getOptionKey(string $option): string
    {
        return explode('=', $option)[0];
    }

    /**
     * Get the value of the given option from the command-line arguments.
     *
     * @param Option $option The option to get the value of
     *
     * @return string|null The value of the option, or null if the option is not present
     */
    public function getOption(Option $option): ?string
    {
        foreach ($this->args as $arg) {
            if ($this->isOption($arg) && $this->argumentData->getOptionByName($this->getOptionKey($arg)) === $this->argumentData->getOption($option->value)) {
                return $this->getOptionValue($arg);
            }
        }

        return null;
    }

    /**
     * Get the value of the given option from the command-line arguments.
     *
     * @param string $option The option to get the value of
     *
     * @return string The value of the option
     */
    private function getOptionValue(string $option): string
    {
        return explode('=', $option)[1];
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

        throw new \InvalidArgumentException(\sprintf('Error: Please follow the following format: %s', $this->argumentData->getFormat()));
    }
}
