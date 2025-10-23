<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Input;

use MathiasReker\PhpSvgOptimizer\Service\Data\ArgumentData;
use MathiasReker\PhpSvgOptimizer\Type\Option;
use MathiasReker\PhpSvgOptimizer\ValueObject\ArgumentOptionValueObject;

/**
 * @no-named-arguments
 */
final readonly class ArgumentParser
{
    /**
     * Index of the first positional argument.
     */
    private const int OPTION_KEY_INDEX = 0;

    /**
     * Index of the second positional argument.
     */
    private const int OPTION_VALUE_INDEX = 1;

    /**
     * Minimum number of arguments required.
     */
    private const int MINIMUM_ARG_COUNT = 2;

    /**
     * Limit for key and value.
     */
    private const int OPTION_LIMIT = 2;

    /**
     * The ArgumentData instance.
     */
    private ArgumentData $argumentData;

    /**
     * Constructor for the ArgumentParser class.
     *
     * @param array<int, string> $args Command-line arguments passed to the script
     */
    public function __construct(
        private array $args,
    ) {
        $this->argumentData = new ArgumentData();
    }

    /**
     * Check if the given option is present in the command-line arguments.
     *
     * @return bool True if the option is present, false otherwise
     */
    public function hasOption(Option $option): bool
    {
        try {
            $arguments = array_map(
                fn (string $arg): ArgumentOptionValueObject => $this->argumentData->getOptionByName($this->getOptionKey($arg)),
                array_filter(
                    \array_slice($this->args, 1),
                    $this->isOption(...)
                )
            );

            return \in_array($this->argumentData->getOption($option->value), $arguments, true);
        } catch (\InvalidArgumentException) {
            return false;
        }
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
        return explode('=', $option)[self::OPTION_KEY_INDEX];
    }

    /**
     * Get the value of the given option from the command-line arguments.
     *
     * @param Option $option The option to get the value of
     *
     * @return string The value of the option
     *
     * @throws \InvalidArgumentException If the option is not found in the arguments
     */
    public function getOption(Option $option): string
    {
        foreach ($this->args as $arg) {
            if ($this->isOption($arg)
                && $this->argumentData->getOptionByName($this->getOptionKey($arg)) === $this->argumentData->getOption($option->value)
            ) {
                return $this->getOptionValue($arg);
            }
        }

        throw new \InvalidArgumentException(\sprintf('Option "%s" not found in the command-line arguments.', $option->value));
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
     * Get the value of the given option from the command-line arguments.
     *
     * @param string $option The option to get the value of
     *
     * @return string The value of the option
     *
     * @throws \InvalidArgumentException If the option is missing a value
     */
    private function getOptionValue(string $option): string
    {
        $parts = explode('=', $option, self::OPTION_LIMIT);
        if (\count($parts) < self::OPTION_LIMIT) {
            throw new \InvalidArgumentException(\sprintf('Option "%s" requires a value.', $parts[self::OPTION_KEY_INDEX]));
        }

        return $parts[self::OPTION_VALUE_INDEX];
    }

    /**
     * Validates that all options passed via the command-line arguments
     * are recognized and supported by the application.
     *
     * This method checks both long-form options (e.g., --dry-run) and
     * shorthand versions (e.g., -d). If an unknown option is found,
     * an \InvalidArgumentException is thrown with a helpful message.
     *
     * @throws \InvalidArgumentException If any unsupported or unknown option is provided
     */
    public function validateOptions(): void
    {
        $validOptionKeys = [];

        foreach (Option::cases() as $option) {
            $validOptionKeys[] = $option->getFull();
            $validOptionKeys[] = $option->getShorthand();
        }

        foreach ($this->args as $arg) {
            if ($this->isOption($arg)) {
                $optionName = $this->getOptionKey($arg);

                if (!\in_array($optionName, $validOptionKeys, true)) {
                    throw new \InvalidArgumentException(\sprintf('Unknown option: "%s". Run with --help to see valid options.', $optionName));
                }
            }
        }
    }

    /**
     * Check if the argument list is empty.
     *
     * @return bool True if the argument list is empty, false otherwise
     */
    public function isEmpty(): bool
    {
        return \count($this->args) < self::MINIMUM_ARG_COUNT;
    }

    /**
     * Get the list of positional arguments (file paths) from the command-line arguments.
     *
     * @return list<string>
     *
     * @throws \InvalidArgumentException If no positional arguments are found
     */
    public function getPaths(): array
    {
        $paths = \array_slice($this->args, $this->getArgumentStartIndex());

        if ([] === $paths) {
            throw new \InvalidArgumentException('No positional arguments found. Please provide at least one SVG file or directory.');
        }

        foreach ($paths as $path) {
            if (!is_dir($path) && !is_file($path)) {
                throw new \InvalidArgumentException(\sprintf('"%s" is not a valid directory or file.', $path));
            }
        }

        $svgFiles = (new FileCollector())->collectSvgFiles($paths);

        if ([] === $svgFiles) {
            throw new \InvalidArgumentException('No valid .svg files found to optimize.');
        }

        return $svgFiles;
    }

    /**
     * Get the index of the next positional argument after options/subcommands.
     *
     * @return int The index of the first positional argument
     *
     * @throws \InvalidArgumentException If no positional argument is found
     */
    public function getArgumentStartIndex(): int
    {
        return $this->getArgumentIndex() + 1;
    }

    /**
     * Get the index of the next positional argument after options/subcommands.
     *
     * @return int The index of the first positional argument
     *
     * @throws \InvalidArgumentException If no positional argument is found
     */
    public function getArgumentIndex(): int
    {
        foreach ($this->args as $index => $arg) {
            if (!str_starts_with($arg, '-') && self::OPTION_KEY_INDEX !== $index) {
                return $index;
            }
        }

        throw new \InvalidArgumentException(\sprintf('Please follow the following format: %s', $this->argumentData->getFormat()));
    }
}
