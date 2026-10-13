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
final readonly class ConfigLoader
{
    private const string UTF8_BOM = "\xEF\xBB\xBF";

    /**
     * @param string $config The path to a config file or a JSON string
     *
     * @return array<array-key, bool> The configuration as an associative array
     *
     * @throws \InvalidArgumentException If the file cannot be read, the JSON is invalid or a value is not a boolean
     * @throws \JsonException            If the JSON decoding fails
     */
    public static function loadConfig(string $config): array
    {
        if (is_dir($config)) {
            throw new \InvalidArgumentException(\sprintf('Configuration "%s" is a directory, not a file or a JSON string.', $config));
        }

        $configContent = file_exists($config)
            ? file_get_contents($config)
            : $config;

        if (false === $configContent) {
            throw new \InvalidArgumentException('Failed to read configuration content.');
        }

        if (str_starts_with($configContent, self::UTF8_BOM)) {
            $configContent = mb_substr($configContent, mb_strlen(self::UTF8_BOM));
        }

        if ('' === $configContent) {
            throw new \InvalidArgumentException('Configuration must be a valid file path or a JSON string.');
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

        $ruleFlags = [];

        foreach ($decodedConfig as $key => $value) {
            if (!\is_bool($value)) {
                throw new \InvalidArgumentException(\sprintf('Configuration value for "%s" must be true or false.', $key));
            }

            $ruleFlags[$key] = $value;
        }

        return $ruleFlags;
    }
}
