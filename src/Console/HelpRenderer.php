<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console;

use MathiasReker\PhpSvgOptimizer\Console\Input\Option;
use MathiasReker\PhpSvgOptimizer\Console\Output\Style;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class HelpRenderer
{
    private const string BINARY = 'svg-optimizer';

    private const string USAGE = 'svg-optimizer [options] [--] <path>...';

    private const array EXIT_CODES = [
        ExitCode::Success->value => 'Success',
        ExitCode::Failure->value => 'A file failed',
        ExitCode::InvalidUsage->value => 'Invalid usage or configuration',
        ExitCode::ChangesDetected->value => '--check found a file that would change',
    ];

    private const array EXAMPLES = [
        'svg-optimizer icons',
        'svg-optimizer --dry-run icons logo.svg',
        'svg-optimizer --check icons',
        'svg-optimizer --sets=security,minification icons',
        'svg-optimizer --rules=removeComments,minifyColors logo.svg',
        'svg-optimizer --allow-risky --without-rules=removeUnusedIds icons',
        'svg-optimizer --config=svg-optimizer.json icons',
    ];

    /**
     * @param bool $ansi whether the headings and the options are styled with ANSI escape sequences
     *
     * @return string the help text, ending with a line break
     */
    public function render(bool $ansi): string
    {
        $lines = [
            self::BINARY,
            '',
            Style::Bold->apply('Usage:', $ansi),
            '  ' . self::USAGE,
            '',
            Style::Bold->apply('Options:', $ansi),
            ...$this->optionLines($ansi),
            '',
            Style::Bold->apply('Exit codes:', $ansi),
            ...array_map(static fn (int $code, string $meaning): string => \sprintf('  %d  %s', $code, $meaning), array_keys(self::EXIT_CODES), self::EXIT_CODES),
            '',
            Style::Bold->apply('Examples:', $ansi),
            ...array_map(static fn (string $example): string => '  ' . $example, self::EXAMPLES),
        ];

        return implode(\PHP_EOL, $lines) . \PHP_EOL;
    }

    /**
     * @param bool $ansi whether the options are styled
     *
     * @return list<string> one line per option, with the descriptions aligned
     */
    private function optionLines(bool $ansi): array
    {
        $labels = array_map($this->label(...), Option::cases());
        $width = max(array_map(mb_strlen(...), $labels));
        $lines = [];

        foreach (Option::cases() as $index => $option) {
            $label = $labels[$index];
            $lines[] = '  ' . Style::Cyan->apply($label, $ansi) . str_repeat(' ', $width - mb_strlen($label)) . '  ' . $option->getDescription();
        }

        return $lines;
    }

    private function label(Option $option): string
    {
        $short = '' === $option->getShortName() ? '    ' : '-' . $option->getShortName() . ', ';
        $value = '' === $option->getValueName() ? '' : \sprintf('=<%s>', $option->getValueName());

        return $short . '--' . $option->value . $value;
    }
}
