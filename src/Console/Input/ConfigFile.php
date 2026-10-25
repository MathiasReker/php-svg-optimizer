<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Input;

use MathiasReker\PhpSvgOptimizer\Rule;
use MathiasReker\PhpSvgOptimizer\RuleSet;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class ConfigFile
{
    use RuleNameTrait;

    private const string SCHEMA_KEY = '$schema';

    private const string SETS_KEY = 'sets';

    private const string RULES_KEY = 'rules';

    private const string ALLOW_RISKY_KEY = 'allowRisky';

    private const array KEYS = [self::SCHEMA_KEY, self::SETS_KEY, self::RULES_KEY, self::ALLOW_RISKY_KEY];

    private const string UTF8_BOM = "\xEF\xBB\xBF";

    /**
     * @param list<Rule>    $rules           the rules the configuration enables
     * @param list<Rule>    $excludedRules   the rules the configuration disables
     * @param list<RuleSet> $ruleSets        the rule sets the configuration selects, without "default"
     * @param bool          $defaultSelected whether the configuration selects "default"
     * @param bool          $riskyAllowed    whether the configuration allows risky rules
     */
    private function __construct(
        private array $rules,
        private array $excludedRules,
        private array $ruleSets,
        private bool $defaultSelected,
        private bool $riskyAllowed,
    ) {}

    public static function empty(): self
    {
        return new self([], [], [], false, false);
    }

    /**
     * @param string $path the configuration file
     *
     * @throws \InvalidArgumentException If the file is missing, is a directory, cannot be read or is not valid
     */
    public static function fromFile(string $path): self
    {
        if (is_dir($path)) {
            throw new \InvalidArgumentException(\sprintf('Configuration "%s" is a directory, not a file.', $path));
        }

        if (!is_file($path)) {
            throw new \InvalidArgumentException(\sprintf('Configuration file "%s" does not exist.', $path));
        }

        set_error_handler(static fn (): bool => true);

        try {
            $json = file_get_contents($path);
        } finally {
            restore_error_handler();
        }

        if (false === $json) {
            throw new \InvalidArgumentException(\sprintf('Configuration file "%s" cannot be read.', $path));
        }

        return self::parse($json, \sprintf('configuration file "%s"', $path));
    }

    /**
     * @param string $json the configuration as JSON
     *
     * @throws \InvalidArgumentException If the JSON is not a valid configuration
     */
    public static function fromJson(string $json): self
    {
        return self::parse($json, 'inline configuration');
    }

    /**
     * @return list<Rule>
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @return list<Rule>
     */
    public function getExcludedRules(): array
    {
        return $this->excludedRules;
    }

    /**
     * @return list<RuleSet>
     */
    public function getRuleSets(): array
    {
        return $this->ruleSets;
    }

    public function isDefaultSelected(): bool
    {
        return $this->defaultSelected;
    }

    public function isRiskyAllowed(): bool
    {
        return $this->riskyAllowed;
    }

    /**
     * @param string $json   the configuration as JSON, which may start with a byte order mark
     * @param string $source what the configuration is, for the error messages
     *
     * @throws \InvalidArgumentException If the JSON is not a valid configuration
     */
    private static function parse(string $json, string $source): self
    {
        try {
            return self::fromValues(self::decode($json));
        } catch (\InvalidArgumentException $invalidArgumentException) {
            throw new \InvalidArgumentException(\sprintf('Invalid %s: %s', $source, $invalidArgumentException->getMessage()), 0, $invalidArgumentException);
        }
    }

    /**
     * @param string $json the configuration as JSON, which may start with a byte order mark
     *
     * @return array<string, mixed> the top level keys and values
     *
     * @throws \InvalidArgumentException If the JSON is invalid or not an object
     */
    private static function decode(string $json): array
    {
        try {
            $decoded = json_decode(str_starts_with($json, self::UTF8_BOM) ? mb_substr($json, mb_strlen(self::UTF8_BOM)) : $json, false, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $jsonException) {
            throw new \InvalidArgumentException('The JSON is invalid: ' . $jsonException->getMessage(), 0, $jsonException);
        }

        if (!$decoded instanceof \stdClass) {
            throw new \InvalidArgumentException('The configuration must be a JSON object.');
        }

        $values = [];

        foreach (get_object_vars($decoded) as $key => $value) {
            $values[(string) $key] = $value;
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $values the top level keys and values
     *
     * @throws \InvalidArgumentException If a key or value is not valid
     */
    private static function fromValues(array $values): self
    {
        self::assertKnownKeys($values);

        $sets = self::stringList($values[self::SETS_KEY] ?? [], self::SETS_KEY);
        $flags = self::ruleFlags($values[self::RULES_KEY] ?? new \stdClass());
        $riskyAllowed = $values[self::ALLOW_RISKY_KEY] ?? false;

        if (!\is_bool($riskyAllowed)) {
            throw new \InvalidArgumentException(\sprintf('The value of "%s" must be true or false.', self::ALLOW_RISKY_KEY));
        }

        if (!\is_string($values[self::SCHEMA_KEY] ?? '')) {
            throw new \InvalidArgumentException(\sprintf('The value of "%s" must be a string.', self::SCHEMA_KEY));
        }

        return new self(
            self::parseRules(array_map(strval(...), array_keys(array_filter($flags)))),
            self::parseRules(array_map(strval(...), array_keys(array_filter($flags, static fn (bool $enabled): bool => !$enabled)))),
            self::parseRuleSets($sets),
            self::includesDefaultSet($sets),
            $riskyAllowed,
        );
    }

    /**
     * @param array<string, mixed> $values the top level keys and values
     *
     * @throws \InvalidArgumentException If a key is not a key of the configuration
     */
    private static function assertKnownKeys(array $values): void
    {
        $unknown = array_values(array_diff(array_map(strval(...), array_keys($values)), self::KEYS));

        foreach ($unknown as $key) {
            if (self::isRuleName($key) && \is_bool($values[$key] ?? null)) {
                throw new \InvalidArgumentException(\sprintf('The 9.x format is no longer supported: "%s" belongs below "rules". See UPGRADE-10.0.md.', $key));
            }
        }

        if ([] !== $unknown) {
            throw new \InvalidArgumentException(\sprintf('Unknown key "%s".%s Valid keys are: %s.', $unknown[0], self::suggest($unknown[0], self::KEYS), implode(', ', self::KEYS)));
        }
    }

    /**
     * @param mixed  $value the value to check
     * @param string $key   the key that holds the value
     *
     * @return list<string> the value
     *
     * @throws \InvalidArgumentException If the value is not a list of strings
     */
    private static function stringList(mixed $value, string $key): array
    {
        if (!\is_array($value) || array_filter($value, \is_string(...)) !== $value) {
            throw new \InvalidArgumentException(\sprintf('The value of "%s" must be a list of strings.', $key));
        }

        return array_values($value);
    }

    /**
     * @param mixed $value the value of the "rules" key
     *
     * @return array<string, bool> whether each rule is enabled, by rule name
     *
     * @throws \InvalidArgumentException If the value is not an object of booleans
     */
    private static function ruleFlags(mixed $value): array
    {
        if (!$value instanceof \stdClass) {
            throw new \InvalidArgumentException(\sprintf('The value of "%s" must be an object.', self::RULES_KEY));
        }

        $flags = [];

        foreach (get_object_vars($value) as $name => $enabled) {
            if (!\is_bool($enabled)) {
                throw new \InvalidArgumentException(\sprintf('The value of rule "%s" must be true or false.', $name));
            }

            $flags[(string) $name] = $enabled;
        }

        return $flags;
    }
}
