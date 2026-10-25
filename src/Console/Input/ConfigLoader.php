<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Input;

use MathiasReker\PhpSvgOptimizer\RuleSet;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class ConfigLoader
{
    private const string FILE_PATH_REGEX = '~^(?!\s*[{\[]).*(?:[/\\\]|\.json\s*$)~is';

    private const string UTF8_BOM = "\xEF\xBB\xBF";

    private const string RULE_SETS_KEY = 'sets';

    private const int MAXIMUM_DEPTH = 3;

    private const string MAXIMUM_DEPTH_MESSAGE = 'Maximum stack depth exceeded';

    /**
     * @param string $config The path to a config file or a JSON string
     *
     * @return array<array-key, bool> The rule flags of the configuration, without the rule sets
     *
     * @throws \InvalidArgumentException If the file cannot be read, the JSON is invalid or a value is not valid
     * @throws \JsonException            If the JSON decoding fails
     */
    public static function loadConfig(string $config): array
    {
        return self::parseConfig($config)[0];
    }

    /**
     * @param string $config The path to a config file or a JSON string
     *
     * @return list<RuleSet> The rule sets listed under the "sets" key of the configuration
     *
     * @throws \InvalidArgumentException If the file cannot be read, the JSON is invalid or a value is not valid
     * @throws \JsonException            If the JSON decoding fails
     */
    public static function loadRuleSets(string $config): array
    {
        return self::parseConfig($config)[1];
    }

    /**
     * @param string $config The path to a config file or a JSON string
     *
     * @return array{array<array-key, bool>, list<RuleSet>} The rule flags and the rule sets of the configuration
     *
     * @throws \InvalidArgumentException If the file cannot be read, the JSON is invalid or a value is not valid
     * @throws \JsonException            If the JSON decoding fails
     */
    private static function parseConfig(string $config): array
    {
        $ruleFlags = [];
        $ruleSets = [];

        foreach (self::decodeConfig($config) as $key => $value) {
            if (self::RULE_SETS_KEY === $key) {
                $ruleSets = self::parseRuleSets($value);

                continue;
            }

            $ruleFlags[$key] = self::parseRuleFlag($key, $value);
        }

        return [$ruleFlags, $ruleSets];
    }

    /**
     * @param array-key $key   The key of the value
     * @param mixed     $value The value of a rule key
     *
     * @return bool The rule flag
     *
     * @throws \InvalidArgumentException If the value is not a boolean
     */
    private static function parseRuleFlag(int|string $key, mixed $value): bool
    {
        if (!\is_bool($value)) {
            throw new \InvalidArgumentException(\sprintf('Configuration value for "%s" must be true or false.', $key));
        }

        return $value;
    }

    /**
     * @param mixed $value The value of the "sets" key
     *
     * @return list<RuleSet> The rule sets
     *
     * @throws \InvalidArgumentException If the value is not a list of rule set names
     */
    private static function parseRuleSets(mixed $value): array
    {
        if (!\is_array($value) || !array_is_list($value) || array_filter($value, is_string(...)) !== $value) {
            throw new \InvalidArgumentException(\sprintf('Configuration value for "%s" must be a list of rule set names.', self::RULE_SETS_KEY));
        }

        return RuleSetParser::parse($value);
    }

    /**
     * @param string $config The path to a config file or a JSON string
     *
     * @return array<array-key, mixed> The decoded configuration
     *
     * @throws \InvalidArgumentException If the file cannot be read or the content is not a JSON object or list
     * @throws \JsonException            If the JSON decoding fails
     */
    private static function decodeConfig(string $config): array
    {
        $configContent = self::readConfig($config);

        if (str_starts_with($configContent, self::UTF8_BOM)) {
            $configContent = mb_substr($configContent, mb_strlen(self::UTF8_BOM));
        }

        if ('' === $configContent) {
            throw new \InvalidArgumentException('Configuration must be a valid file path or a JSON string.');
        }

        $decodedConfig = json_decode(
            $configContent,
            true,
            self::MAXIMUM_DEPTH,
            \JSON_THROW_ON_ERROR
        );

        if (!\is_array($decodedConfig)) {
            throw new \InvalidArgumentException('Configuration must be a valid file path or a JSON string.');
        }

        if ([] !== array_filter(array_diff_key($decodedConfig, [self::RULE_SETS_KEY => true]), is_array(...))) {
            throw new \JsonException(self::MAXIMUM_DEPTH_MESSAGE, \JSON_ERROR_DEPTH);
        }

        return $decodedConfig;
    }

    /**
     * @param string $config The path to a config file or a JSON string
     *
     * @return string The configuration content
     *
     * @throws \InvalidArgumentException If the configuration is a directory, a missing file or cannot be read
     */
    private static function readConfig(string $config): string
    {
        if (is_dir($config)) {
            throw new \InvalidArgumentException(\sprintf('Configuration "%s" is a directory, not a file or a JSON string.', $config));
        }

        if (!file_exists($config) && 1 === preg_match(self::FILE_PATH_REGEX, $config)) {
            throw new \InvalidArgumentException(\sprintf('Configuration file "%s" does not exist.', $config));
        }

        $configContent = file_exists($config)
            ? file_get_contents($config)
            : $config;

        if (false === $configContent) {
            throw new \InvalidArgumentException('Failed to read configuration content.');
        }

        return $configContent;
    }
}
