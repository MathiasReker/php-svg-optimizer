<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Input;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class CommandLineParser
{
    use RuleNameTrait;

    private const string LIST_SEPARATOR = ',';

    /**
     * @param list<string> $arguments the arguments after the script name
     *
     * @throws \InvalidArgumentException If an option is unknown, misses its value, is given a value it does not take
     *                                   or has a value that is not valid
     */
    public function parse(array $arguments): CommandLine
    {
        $items = (new OptionScanner())->scan($arguments);
        $flags = [];
        $values = [];

        foreach (array_filter($items, \is_array(...)) as [$option, $value]) {
            if (\array_key_exists($option->value, $flags) && !$option->isRepeatable()) {
                throw new \InvalidArgumentException(\sprintf('Option "--%s" can be given only once.', $option->value));
            }

            $flags[$option->value] = $option;
            $values[$option->value][] = $value;
        }

        $sets = $this->listOf(Option::Sets, $values);

        return new CommandLine(
            array_values(array_filter($items, \is_string(...))),
            array_values($flags),
            $values[Option::Config->value][0] ?? '',
            self::parseRules($this->listOf(Option::Rules, $values)),
            self::parseRules($this->listOf(Option::WithoutRules, $values)),
            self::parseRuleSets($sets),
            self::includesDefaultSet($sets),
        );
    }

    /**
     * @param Option                      $option the option whose values are joined
     * @param array<string, list<string>> $values the values of every option, by option name
     *
     * @return list<string> the comma separated items of every occurrence of the option, in order
     *
     * @throws \InvalidArgumentException If an item is empty
     */
    private function listOf(Option $option, array $values): array
    {
        $items = [];

        foreach ($values[$option->value] ?? [] as $value) {
            foreach (explode(self::LIST_SEPARATOR, $value) as $item) {
                $items[] = trim($item);
            }
        }

        if (\in_array('', $items, true)) {
            throw new \InvalidArgumentException(\sprintf('Option "--%s" contains an empty value.', $option->value));
        }

        return $items;
    }
}
