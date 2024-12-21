<?php

/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Services\Data;

use MathiasReker\PhpSvgOptimizer\ValueObjects\ArgumentOptionValueObject;
use MathiasReker\PhpSvgOptimizer\ValueObjects\CommandOptionValueObject;
use MathiasReker\PhpSvgOptimizer\ValueObjects\ExampleCommandValueObject;

/**
 * Class that represents the available argument options for the command.
 */
final class ArgumentData
{
    /**
     * @var array<string, ArgumentOptionValueObject>
     */
    private array $options;

    /**
     * @var array<string, CommandOptionValueObject>
     */
    private readonly array $commands;

    /**
     * @var array<ExampleCommandValueObject>
     */
    private readonly array $examples;

    public function __construct()
    {
        $this->options = [
            'help' => new ArgumentOptionValueObject('-h', '--help', 'Display help for the command.'),
            'config' => new ArgumentOptionValueObject('-c', '--config', 'Path to a JSON file with custom optimization rules. If not provided, all default optimizations will be applied.'),
            'dryRun' => new ArgumentOptionValueObject('-d', '--dry-run', 'Only calculate potential savings without modifying the files.'),
            'quiet' => new ArgumentOptionValueObject('-q', '--quiet', 'Suppress all output except errors.'),
            'version' => new ArgumentOptionValueObject('-v', '--version', 'Display the version of the library.'),
        ];

        $this->commands = [
            'process' => new CommandOptionValueObject('Process', 'Provide a list of directories or files to process.'),
        ];

        $this->examples = [
            new ExampleCommandValueObject('vendor/bin/svg-optimizer --dry-run process /path/to/svgs'),
            new ExampleCommandValueObject('vendor/bin/svg-optimizer --config=config.json process /path/to/file.svg'),
            new ExampleCommandValueObject('vendor/bin/svg-optimizer --quiet process /path/to/file.svg'),
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
     * @return ArgumentOptionValueObject|null Returns the option details or null if not found
     */
    public function getOptionByName(string $name): ?ArgumentOptionValueObject
    {
        foreach ($this->options as $option) {
            if ($option->getShorthand() === $name || $option->getFull() === $name) {
                return $option;
            }
        }

        return null;
    }

    /**
     * Returns the detailed commands as an array of command names with their values.
     *
     * @return array<string, CommandOptionValueObject>
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Retrieves a single option's details by its name.
     *
     * @param string $option The name of the option (e.g., 'help', 'config').
     *
     * @return ArgumentOptionValueObject|null Returns the option details or null if not found
     */
    public function getOption(string $option): ?ArgumentOptionValueObject
    {
        return $this->options[$option] ?? null;
    }

    /**
     * Returns the detailed commands as an array of command names with their values.
     *
     * @return array<ExampleCommandValueObject>
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
        return 'vendor/bin/svg-optimizer [options] process <path1> <path2> ...';
    }
}
