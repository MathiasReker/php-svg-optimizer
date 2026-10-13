<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Trait;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait PathDataParserTrait
{
    private const array PATH_ARGUMENT_TYPES = [
        'm' => 'nn', 'l' => 'nn', 'h' => 'n', 'v' => 'n', 'c' => 'nnnnnn', 's' => 'nnnn', 'q' => 'nnnn', 't' => 'nn',
        'a' => 'nnnffnn', 'z' => '',
    ];

    private const string PATH_ASCII_REGEX = '/^[\x00-\x7F]*$/';

    private const string PATH_COMMAND_REGEX = '/([MmLlHhVvCcSsQqTtAaZz])([^MmLlHhVvCcSsQqTtAaZz]*)/';

    private const string PATH_WHITESPACE = '[\x20\x09\x0A\x0C\x0D]*';

    private const string PATH_NUMBER_CAPTURE = '((?>' . self::NUMBER_PATTERN . '))';

    private const string PATH_FLAG_CAPTURE = '([01])';

    /**
     * @param string $data the path data
     *
     * @return list<array{string, list<string>}> the segments as [command, arguments], or an empty list if invalid
     */
    private function parsePathData(string $data): array
    {
        $data = trim($data, " \t\n\f\r");

        if (1 !== preg_match(self::PATH_ASCII_REGEX, $data)
            || false === preg_match_all(self::PATH_COMMAND_REGEX, $data, $commands, \PREG_SET_ORDER)
            || implode('', array_column($commands, 0)) !== $data
            || [] === $commands
            || 'm' !== mb_strtolower($commands[0][1])
        ) {
            return [];
        }

        $segments = [];

        foreach ($commands as [, $command, $arguments]) {
            $commandSegments = $this->parsePathCommand($command, trim($arguments, " \t\n\f\r"));

            if ([] === $commandSegments) {
                return [];
            }

            array_push($segments, ...$commandSegments);
        }

        return $segments;
    }

    /**
     * @param string $command   the command letter
     * @param string $arguments the argument text, without surrounding whitespace
     *
     * @return list<array{string, list<string>}> the segments, or an empty list if the arguments are invalid
     */
    private function parsePathCommand(string $command, string $arguments): array
    {
        $types = self::PATH_ARGUMENT_TYPES[mb_strtolower($command)];

        if ('' === $types) {
            return '' === $arguments ? [[$command, []]] : [];
        }

        $group = $this->pathArgumentGroupPattern($types);
        $separator = self::PATH_WHITESPACE . ',?' . self::PATH_WHITESPACE;

        if (1 !== preg_match('/^' . $group . '(?:' . $separator . $group . ')*$/', $arguments)) {
            return [];
        }

        preg_match_all('/' . $group . '/', $arguments, $groups, \PREG_SET_ORDER);
        $segments = [];

        foreach ($groups as $index => $match) {
            $segments[] = [$this->repeatedPathCommand($command, $index), array_values(\array_slice($match, 1))];
        }

        return $segments;
    }

    /**
     * @param string $types the argument types of a command
     *
     * @return string a regex fragment matching one group of arguments, capturing each argument
     */
    private function pathArgumentGroupPattern(string $types): string
    {
        $captures = array_map(
            static fn (string $type): string => 'f' === $type ? self::PATH_FLAG_CAPTURE : self::PATH_NUMBER_CAPTURE,
            mb_str_split($types),
        );

        return implode(self::PATH_WHITESPACE . ',?' . self::PATH_WHITESPACE, $captures);
    }

    /**
     * @param string $command the command letter
     * @param int    $index   the index of the argument group within the command
     */
    private function repeatedPathCommand(string $command, int $index): string
    {
        if (0 === $index) {
            return $command;
        }

        return match ($command) {
            'M' => 'L',
            'm' => 'l',
            default => $command,
        };
    }
}
