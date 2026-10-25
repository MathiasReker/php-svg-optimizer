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
trait PathDataSerializerTrait
{
    private const array PATH_IMPLICIT_AFTER_MOVETO = ['M' => 'L', 'm' => 'l'];

    private const string PATH_CLOSE = 'z';

    private const string PATH_CLOSE_UPPERCASE = 'Z';

    private const array PATH_ARC_COMMANDS = ['A', 'a'];

    private const array PATH_ARGUMENTS_AFTER_ARC_FLAGS = [4, 5];

    /**
     * @param list<array{string, list<string>}> $segments the segments as [command, arguments]
     */
    private function serializePathData(array $segments): string
    {
        $output = '';
        $previousCommand = '';
        $previousArgument = '';

        foreach ($segments as [$command, $arguments]) {
            [$command, $arguments] = $this->shortenPathSegment($command, $arguments);

            if ($this->needsPathCommandLetter($command, $previousCommand)) {
                $output .= $command;
                $previousArgument = '';
            }

            foreach ($arguments as $index => $argument) {
                $output .= ($this->followsArcFlag($command, $index) ? '' : $this->pathSeparatorBefore($previousArgument, $argument)) . $argument;
                $previousArgument = $argument;
            }

            $previousCommand = $command;
        }

        return $output;
    }

    /**
     * @param string       $command   the command letter
     * @param list<string> $arguments the arguments
     *
     * @return array{string, list<string>}
     */
    private function shortenPathSegment(string $command, array $arguments): array
    {
        if (self::PATH_CLOSE_UPPERCASE === $command) {
            return [self::PATH_CLOSE, []];
        }

        $arguments = array_map($this->minifyNumber(...), $arguments);

        if ('l' !== $command || $this->isZeroNumber($arguments[0]) === $this->isZeroNumber($arguments[1])) {
            return [$command, $arguments];
        }

        return $this->isZeroNumber($arguments[0]) ? ['v', [$arguments[1]]] : ['h', [$arguments[0]]];
    }

    /**
     * @param string $command         the command letter
     * @param string $previousCommand the command letter of the previous segment
     */
    private function needsPathCommandLetter(string $command, string $previousCommand): bool
    {
        $implicit = self::PATH_IMPLICIT_AFTER_MOVETO[$previousCommand] ?? $previousCommand;

        return self::PATH_CLOSE === $command || $command !== $implicit;
    }

    /**
     * @param string $command the command letter
     * @param int    $index   the position of the argument within the segment
     *
     * @return bool true if the argument follows an arc flag, which is always one character, so it needs no separator
     */
    private function followsArcFlag(string $command, int $index): bool
    {
        return \in_array($command, self::PATH_ARC_COMMANDS, true) && \in_array($index, self::PATH_ARGUMENTS_AFTER_ARC_FLAGS, true);
    }

    /**
     * @param string $previousArgument the argument written before, or an empty string after a command letter
     * @param string $argument         the argument about to be written
     */
    private function pathSeparatorBefore(string $previousArgument, string $argument): string
    {
        if ('' === $previousArgument || str_starts_with($argument, '-')) {
            return '';
        }

        $previousHasDecimalPart = str_contains($previousArgument, '.') || str_contains($previousArgument, 'e');

        return str_starts_with($argument, '.') && $previousHasDecimalPart ? '' : ' ';
    }
}
