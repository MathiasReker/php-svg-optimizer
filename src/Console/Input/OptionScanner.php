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
final readonly class OptionScanner
{
    use SuggestionTrait;

    private const string END_OF_OPTIONS = '--';

    private const string LONG_PREFIX = '--';

    private const string SHORT_PREFIX = '-';

    private const string VALUE_SEPARATOR = '=';

    private const array REMOVED_OPTIONS = [
        '--with-all-rules' => 'was removed; without --sets, --rules or a configuration the default rules run',
        '-a' => 'was removed; without --sets, --rules or a configuration the default rules run',
    ];

    /**
     * @param list<string> $arguments the arguments after the script name
     *
     * @return list<string|array{Option, string}> the paths and the options with their values, in the order given
     *
     * @throws \InvalidArgumentException If an option is unknown, misses its value or is given a value it does not take
     */
    public function scan(array $arguments): array
    {
        $items = [];
        $count = \count($arguments);

        for ($index = 0; $index < $count; ++$index) {
            $argument = $arguments[$index];

            if (self::END_OF_OPTIONS === $argument) {
                return [...$items, ...\array_slice($arguments, $index + 1)];
            }

            if (!str_starts_with($argument, self::SHORT_PREFIX)) {
                $items[] = $argument;

                continue;
            }

            [$options, $consumed] = $this->scanOptions($argument, $arguments[$index + 1] ?? '');
            $items = [...$items, ...$options];
            $index += $consumed;
        }

        return $items;
    }

    /**
     * @param string $argument the argument, such as "--config=file" or "-dq"
     * @param string $next     the argument after it, or an empty string if there is none
     *
     * @return array{list<array{Option, string}>, int} the options with their values, and 1 if the next argument was a value
     *
     * @throws \InvalidArgumentException If an option is unknown, misses its value or is given a value it does not take
     */
    private function scanOptions(string $argument, string $next): array
    {
        return str_starts_with($argument, self::LONG_PREFIX) ? $this->scanLong($argument, $next) : $this->scanShort($argument, $next);
    }

    /**
     * @param string $argument the argument, such as "--config=file"
     * @param string $next     the argument after it, or an empty string if there is none
     *
     * @return array{list<array{Option, string}>, int} the option with its value, and 1 if the next argument was the value
     *
     * @throws \InvalidArgumentException If the option is unknown, misses its value or is given a value it does not take
     */
    private function scanLong(string $argument, string $next): array
    {
        $separator = mb_strpos($argument, self::VALUE_SEPARATOR);
        $attached = false !== $separator;
        $name = mb_substr($argument, mb_strlen(self::LONG_PREFIX), $attached ? $separator - mb_strlen(self::LONG_PREFIX) : null);
        $option = Option::tryFrom($name) ?? throw $this->unknownOption(self::LONG_PREFIX . $name, self::suggest(self::LONG_PREFIX . $name, array_map(static fn (Option $option): string => self::LONG_PREFIX . $option->value, Option::cases())));

        return $this->readValue($option, self::LONG_PREFIX . $name, $attached, $attached ? mb_substr($argument, $separator + 1) : '', $next);
    }

    /**
     * @param string $argument the argument, such as "-dq" or "-cfile"
     * @param string $next     the argument after it, or an empty string if there is none
     *
     * @return array{list<array{Option, string}>, int} the options with their values, and 1 if the next argument was a value
     *
     * @throws \InvalidArgumentException If an option is unknown, misses its value or is given a value it does not take
     */
    private function scanShort(string $argument, string $next): array
    {
        $characters = mb_substr($argument, mb_strlen(self::SHORT_PREFIX));
        $options = [];

        if ('' === $characters) {
            throw $this->unknownOption(self::SHORT_PREFIX, '');
        }

        foreach (mb_str_split($characters) as $position => $character) {
            $option = $this->findByShortName($character);

            if (!$option->takesValue()) {
                $options[] = [$option, ''];

                continue;
            }

            $rest = mb_substr($characters, $position + 1);
            [$valued, $consumed] = $this->readValue($option, self::SHORT_PREFIX . $character, '' !== $rest, ltrim($rest, self::VALUE_SEPARATOR), $next);

            return [[...$options, ...$valued], $consumed];
        }

        return [$options, 0];
    }

    /**
     * @param string $name the short name of an option, without the dash
     *
     * @throws \InvalidArgumentException If no option has the short name
     */
    private function findByShortName(string $name): Option
    {
        foreach (Option::cases() as $option) {
            if ($name === $option->getShortName()) {
                return $option;
            }
        }

        throw $this->unknownOption(self::SHORT_PREFIX . $name, '');
    }

    private function unknownOption(string $name, string $suggestion): \InvalidArgumentException
    {
        return new \InvalidArgumentException(
            \array_key_exists($name, self::REMOVED_OPTIONS)
                ? \sprintf('Option "%s" %s.', $name, self::REMOVED_OPTIONS[$name])
                : \sprintf('Unknown option "%s".%s Run with --help to see valid options.', $name, $suggestion)
        );
    }

    /**
     * @param Option $option        the option
     * @param string $name          the option as the user wrote it, such as "--config" or "-c"
     * @param bool   $attached      whether a value was written right after the option
     * @param string $attachedValue the value written right after the option
     * @param string $next          the argument after the option, or an empty string if there is none
     *
     * @return array{list<array{Option, string}>, int} the option with its value, and 1 if the next argument was the value
     *
     * @throws \InvalidArgumentException If the option misses its value or is given a value it does not take
     */
    private function readValue(Option $option, string $name, bool $attached, string $attachedValue, string $next): array
    {
        if (!$option->takesValue()) {
            return $attached
                ? throw new \InvalidArgumentException(\sprintf('Option "%s" does not take a value.', $name)) : [[[$option, '']], 0];
        }

        $consumesNext = !$attached && !str_starts_with($next, self::SHORT_PREFIX);
        $value = $consumesNext ? $next : $attachedValue;

        if ('' === $value) {
            throw new \InvalidArgumentException(\sprintf('Option "%s" requires a value.', $name));
        }

        return [[[$option, $value]], $consumesNext ? 1 : 0];
    }
}
