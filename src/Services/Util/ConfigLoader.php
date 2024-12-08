<?php

/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Services\Util;

final class ConfigLoader
{
    /**
     * Loads a configuration file or JSON string and returns it as an array.
     *
     * This method checks if the input is a file path or a raw JSON string.
     * If it's a file path, it reads the file's content. The method then validates
     * the JSON and decodes it into an associative array.
     *
     * @param string $config The path to a config file or a JSON string
     *
     * @return array<string, bool> The configuration as an associative array
     *
     * @throws \InvalidArgumentException If the file cannot be read or the JSON is invalid
     */
    public static function loadConfig(string $config): array
    {
        $configContent = file_exists($config) ? file_get_contents($config) : $config;

        if (false === $configContent) {
            throw new \InvalidArgumentException('Error: Failed to read configuration content.');
        }

        if (!json_validate($configContent)) {
            throw new \InvalidArgumentException('Error: Invalid JSON configuration.');
        }

        $decodedConfig = json_decode($configContent, true);

        if (null === $decodedConfig) {
            throw new \InvalidArgumentException('Error: Failed to decode configuration JSON.');
        }

        if (!\is_array($decodedConfig)) {
            throw new \InvalidArgumentException('Error: Configuration must be an associative array.');
        }

        return array_combine(
            array_map('strval', array_keys($decodedConfig)),
            array_map('boolval', $decodedConfig)
        );
    }
}
