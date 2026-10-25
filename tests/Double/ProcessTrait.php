<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Double;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait ProcessTrait
{
    /**
     * @param list<string> $command          the program and its arguments
     * @param string       $workingDirectory the directory to run the program in
     *
     * @return array{exitCode: int, stdout: string, stderr: string}
     *
     * @throws \RuntimeException If the program cannot be started
     */
    private function runProcess(array $command, string $workingDirectory): array
    {
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $workingDirectory);

        if (!\is_resource($process)) {
            throw new \RuntimeException('Unable to start the process.');
        }

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return ['exitCode' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
    }
}
