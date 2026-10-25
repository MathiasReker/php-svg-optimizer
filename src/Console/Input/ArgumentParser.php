<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Input;

use MathiasReker\PhpSvgOptimizer\Console\Help\ArgumentData;
use MathiasReker\PhpSvgOptimizer\Console\Type\Command;
use MathiasReker\PhpSvgOptimizer\Console\Type\Option;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class ArgumentParser
{
    private const int MINIMUM_ARG_COUNT = 2;

    private const int OPTION_LIMIT = 2;

    private const string END_OF_OPTIONS_MARKER = '--';

    private const string OPTION_VALUE_SEPARATOR = '=';

    private ArgumentData $argumentData;

    /** @var list<array{name: string, value: string, hasValue: bool}> */
    private array $options;

    /** @var list<string> */
    private array $positionalArguments;

    /**
     * @param array<int, string> $args Command-line arguments passed to the script
     */
    public function __construct(
        private array $args,
    ) {
        $this->argumentData = new ArgumentData();

        $options = [];
        $positionalArguments = [];
        $remainingArgs = \array_slice($this->args, 1);

        while ([] !== $remainingArgs) {
            $arg = array_shift($remainingArgs);

            if (self::END_OF_OPTIONS_MARKER === $arg) {
                $positionalArguments = [...$positionalArguments, ...$remainingArgs];

                break;
            }

            if (!$this->isOption($arg)) {
                $positionalArguments[] = $arg;

                continue;
            }

            $option = $this->parseOption($arg, $remainingArgs[0] ?? self::END_OF_OPTIONS_MARKER);

            if ($option['consumesNextArgument']) {
                array_shift($remainingArgs);
            }

            $options[] = [
                'name' => $option['name'],
                'value' => $option['value'],
                'hasValue' => $option['hasValue'],
            ];
        }

        $this->options = $options;
        $this->positionalArguments = $positionalArguments;
    }

    /**
     * @param string $arg     The option argument, such as "--config=value" or "--config"
     * @param string $nextArg The argument following the option, or the end-of-options marker if there is none
     *
     * @return array{name: string, value: string, hasValue: bool, consumesNextArgument: bool}
     */
    private function parseOption(string $arg, string $nextArg): array
    {
        $parts = explode(self::OPTION_VALUE_SEPARATOR, $arg, self::OPTION_LIMIT);

        if (self::OPTION_LIMIT === \count($parts)) {
            return ['name' => $parts[0], 'value' => $parts[1], 'hasValue' => '' !== $parts[1], 'consumesNextArgument' => false];
        }

        $takesNextArgument = $this->requiresValue($arg) && self::END_OF_OPTIONS_MARKER !== $nextArg;

        return [
            'name' => $arg,
            'value' => $takesNextArgument ? $nextArg : '',
            'hasValue' => $takesNextArgument,
            'consumesNextArgument' => $takesNextArgument,
        ];
    }

    /**
     * @param string $name The name of the option, such as "--config" or "-c"
     *
     * @return bool True if the option requires a value, false otherwise
     */
    private function requiresValue(string $name): bool
    {
        return $this->matches($name, Option::Config) || $this->matches($name, Option::Set);
    }

    /**
     * @param string $name   The name of the option, such as "--config" or "-c"
     * @param Option $option The option to compare with
     *
     * @return bool True if the name is the full or shorthand name of the option, false otherwise
     */
    private function matches(string $name, Option $option): bool
    {
        return \in_array($name, [$option->getFull(), $option->getShorthand()], true);
    }

    /**
     * @return bool True if the option is present, false otherwise
     */
    public function hasOption(Option $option): bool
    {
        foreach ($this->options as $parsedOption) {
            if ($this->matches($parsedOption['name'], $option)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Option $option The option to get the value of
     *
     * @return string The value of the option
     *
     * @throws \InvalidArgumentException If the option is not found in the arguments or has no value
     */
    public function getOption(Option $option): string
    {
        foreach ($this->options as $parsedOption) {
            if (!$this->matches($parsedOption['name'], $option)) {
                continue;
            }

            if (!$parsedOption['hasValue']) {
                throw new \InvalidArgumentException(\sprintf('Option "%s" requires a value.', $parsedOption['name']));
            }

            return $parsedOption['value'];
        }

        throw new \InvalidArgumentException(\sprintf('Option "%s" not found in the command-line arguments.', $option->value));
    }

    /**
     * @param Option $option The option to get the values of
     *
     * @return list<string> The values of every occurrence of the option, in order
     *
     * @throws \InvalidArgumentException If an occurrence of the option has no value
     */
    public function getOptionValues(Option $option): array
    {
        $values = [];

        foreach ($this->options as $parsedOption) {
            if (!$this->matches($parsedOption['name'], $option)) {
                continue;
            }

            if (!$parsedOption['hasValue']) {
                throw new \InvalidArgumentException(\sprintf('Option "%s" requires a value.', $parsedOption['name']));
            }

            $values[] = $parsedOption['value'];
        }

        return $values;
    }

    /**
     * @return bool True if the argument is an option, false otherwise
     */
    private function isOption(string $option): bool
    {
        return str_starts_with($option, '-');
    }

    /**
     * @throws \InvalidArgumentException If any unsupported or unknown option is provided
     */
    public function validateOptions(): void
    {
        foreach ($this->options as $option) {
            if (!$this->isKnownOption($option['name'])) {
                throw new \InvalidArgumentException(\sprintf('Unknown option: "%s". Run with --help to see valid options.', $option['name']));
            }
        }
    }

    /**
     * @param string $name The name of the option, such as "--config" or "-c"
     *
     * @return bool True if the name is the full or shorthand name of a supported option, false otherwise
     */
    private function isKnownOption(string $name): bool
    {
        foreach (Option::cases() as $option) {
            if ($this->matches($name, $option)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool True if the argument list is empty, false otherwise
     */
    public function isEmpty(): bool
    {
        return \count($this->args) < self::MINIMUM_ARG_COUNT;
    }

    /**
     * @return list<string>
     *
     * @throws \InvalidArgumentException If the command is missing or unknown, or no valid paths are found
     */
    public function getPaths(): array
    {
        $this->validateCommand();

        $paths = \array_slice($this->positionalArguments, 1);

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
     * @throws \InvalidArgumentException If the command is missing or unknown
     */
    private function validateCommand(): void
    {
        if ([] === $this->positionalArguments) {
            throw new \InvalidArgumentException(\sprintf('No command given. Please follow the following format: %s', $this->argumentData->getFormat()));
        }

        if (!Command::tryFrom($this->positionalArguments[0]) instanceof Command) {
            throw new \InvalidArgumentException(\sprintf('Unknown command "%s". Please follow the following format: %s', $this->positionalArguments[0], $this->argumentData->getFormat()));
        }
    }
}
