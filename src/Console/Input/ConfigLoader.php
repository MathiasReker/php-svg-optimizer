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
 * @no-named-arguments
 */
final readonly class ConfigLoader
{
    /**
     * @param string $config The path to a config file or a JSON string
     *
     * @return array<array-key, bool> The configuration as an associative array
     *
     * @throws \InvalidArgumentException If the file cannot be read or the JSON is invalid
     * @throws \JsonException            If the JSON decoding fails
     * @throws \ValueError               If the decoded JSON is not an array
     */
    public static function loadConfig(string $config): array
    {
        $configContent = file_exists($config)
            ? file_get_contents($config)
            : $config;

        if ('' === $configContent) {
            throw new \InvalidArgumentException('Configuration must be a valid file path or a JSON string.');
        }

        if (false === $configContent) {
            throw new \InvalidArgumentException('Failed to read configuration content.');
        }

        $decodedConfig = json_decode(
            $configContent,
            true,
            2,
            \JSON_THROW_ON_ERROR
        );

        if (!\is_array($decodedConfig)) {
            throw new \InvalidArgumentException('Configuration must be a valid file path or a JSON string.');
        }

        return array_combine(
            array_map(strval(...), array_keys($decodedConfig)),
            array_map(boolval(...), $decodedConfig)
        );
    }
}
