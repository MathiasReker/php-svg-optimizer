<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Data;

use MathiasReker\PhpSvgOptimizer\Type\Command;
use MathiasReker\PhpSvgOptimizer\Type\Option;
use MathiasReker\PhpSvgOptimizer\ValueObject\ArgumentOptionValueObject;
use MathiasReker\PhpSvgOptimizer\ValueObject\ExampleCommandValueObject;
use MathiasReker\PhpSvgOptimizer\ValueObject\OptionValueObject;

/**
 * @no-named-arguments
 */
final class ArgumentData
{
    /**
     * The path to the binary.
     */
    private const string BINARY_PATH = 'vendor/bin/svg-optimizer';

    /** @var array<string, ArgumentOptionValueObject> */
    private array $options;

    /** @var array<string, OptionValueObject> */
    private readonly array $commands;

    /** @var list<ExampleCommandValueObject> */
    private readonly array $examples;

    /**
     * Constructor for ArgumentData.
     *
     * Initializes the options, commands, and examples for the command line interface.
     */
    public function __construct()
    {
        foreach (Option::cases() as $option) {
            $this->options[$option->value] = new ArgumentOptionValueObject(
                $option->getShorthand(),
                $option->getFull(),
                $option->getDescription()
            );
        }

        $this->commands = [
            Command::PROCESS->value => new OptionValueObject(
                Command::PROCESS->getTitle(),
                Command::PROCESS->getDescription()
            ),
        ];

        $this->examples = [
            new ExampleCommandValueObject(
                \sprintf(
                    '%s %s %s %s /path/to/svgs',
                    self::BINARY_PATH,
                    Option::WITH_ALL_RULES->getFull(),
                    Option::DRY_RUN->getFull(),
                    Command::PROCESS->value,
                )
            ),
            new ExampleCommandValueObject(
                \sprintf(
                    '%s %s=config.json %s /path/to/file.svg',
                    self::BINARY_PATH,
                    Option::CONFIG->getFull(),
                    Command::PROCESS->value,
                )
            ),
            new ExampleCommandValueObject(
                \sprintf(
                    '%s %s %s %s /path/to/file.svg',
                    self::BINARY_PATH,
                    Option::QUIET->getFull(),
                    Option::WITH_ALL_RULES->getFull(),
                    Command::PROCESS->value,
                )
            ),
            new ExampleCommandValueObject(
                \sprintf(
                    '%s %s %s %s /path/to/file.svg',
                    self::BINARY_PATH,
                    Option::WITH_ALL_RULES->getFull(),
                    Option::ALLOW_RISKY->getFull(),
                    Command::PROCESS->value,
                )
            ),
            new ExampleCommandValueObject(
                \sprintf(
                    '%s %s %s /path/to/file.svg',
                    self::BINARY_PATH,
                    Option::WITH_ALL_RULES->getFull(),
                    Command::PROCESS->value,
                )
            ),
        ];
    }

    /**
     * Returns the detailed options as an array of option names with their values.
     *
     * @return array<string, ArgumentOptionValueObject>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Retrieves a single option's details by its name.
     *
     * @return ArgumentOptionValueObject Returns the option details
     *
     * @throws \InvalidArgumentException If the option is not found
     */
    public function getOptionByName(string $name): ArgumentOptionValueObject
    {
        foreach ($this->options as $option) {
            if ($option->hasName($name)) {
                return $option;
            }
        }

        throw new \InvalidArgumentException(\sprintf('Option "%s" not found.', $name));
    }

    /**
     * Returns the detailed commands as an array of command names with their values.
     *
     * @return array<string, OptionValueObject>
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Retrieves a single option's details by its name.
     *
     * @param string $option The name of the option (e.g., 'help', 'config')
     *
     * @return ArgumentOptionValueObject Returns the option details
     *
     * @throws \InvalidArgumentException If the option is not found
     */
    public function getOption(string $option): ArgumentOptionValueObject
    {
        return $this->options[$option]
            ?? throw new \InvalidArgumentException(\sprintf('Option "%s" not found.', $option));
    }

    /**
     * Returns the detailed commands as an array of command names with their values.
     *
     * @return list<ExampleCommandValueObject>
     */
    public function getExamples(): array
    {
        return $this->examples;
    }

    /**
     * Retrieves the command format string.
     *
     * @return string The command format
     */
    public function getFormat(): string
    {
        return \sprintf(
            '%s [options] %s <path1> <path2> ...',
            self::BINARY_PATH,
            Command::PROCESS->value,
        );
    }
}
