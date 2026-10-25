<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Input;

use MathiasReker\PhpSvgOptimizer\Console\Input\ConfigLoader;
use MathiasReker\PhpSvgOptimizer\Tests\Unit\Support\UnreadableFileStreamWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ConfigLoader::class)]
final class ConfigLoaderTest extends TestCase
{
    private string $configFile;

    /**
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function loadConfigWithValidJsonString(): void
    {
        $jsonString = '{"key1": true, "key2": false}';

        $result = ConfigLoader::loadConfig($jsonString);

        $expected = [
            'key1' => true,
            'key2' => false,
        ];

        self::assertSame($expected, $result);
    }

    /**
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function loadConfigWithValidJsonFile(): void
    {
        $jsonContent = '{"key1": true, "key2": false}';
        file_put_contents($this->configFile, $jsonContent);

        $result = ConfigLoader::loadConfig($this->configFile);

        self::assertSame(['key1' => true, 'key2' => false], $result);
    }

    /**
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function loadConfigWithInvalidJsonString(): void
    {
        $this->expectException(\JsonException::class);

        $invalidJsonString = '{"key1": true, "key2": }';

        ConfigLoader::loadConfig($invalidJsonString);
    }

    /**
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function loadConfigWithInvalidJsonFile(): void
    {
        $invalidJsonContent = '{"key1": true, "key2": }';
        file_put_contents($this->configFile, $invalidJsonContent);

        $this->expectException(\JsonException::class);

        ConfigLoader::loadConfig($this->configFile);
    }

    /**
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function loadConfigWithEmptyJsonContent(): void
    {
        file_put_contents($this->configFile, '');

        $this->expectException(\InvalidArgumentException::class);

        ConfigLoader::loadConfig($this->configFile);
    }

    /**
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    #[DataProvider('provideLoadConfigWithNonBooleanValueThrowsExceptionNamingTheKeyCases')]
    #[Test]
    public function loadConfigWithNonBooleanValueThrowsExceptionNamingTheKey(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration value for "removeComments" must be true or false.');

        ConfigLoader::loadConfig(\sprintf('{"flattenGroups": true, "removeComments": %s}', $value));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideLoadConfigWithNonBooleanValueThrowsExceptionNamingTheKeyCases(): iterable
    {
        yield 'string "false"' => ['"false"'];
        yield 'string "true"' => ['"true"'];
        yield 'zero' => ['0'];
        yield 'one' => ['1'];
        yield 'null' => ['null'];
        yield 'string "yes"' => ['"yes"'];
    }

    /**
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function loadConfigFromFileWithUtf8ByteOrderMark(): void
    {
        file_put_contents($this->configFile, "\xEF\xBB\xBF{\"removeComments\": true, \"flattenGroups\": false}");

        self::assertSame(['removeComments' => true, 'flattenGroups' => false], ConfigLoader::loadConfig($this->configFile));
    }

    /**
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function loadConfigWithNumericKeys(): void
    {
        $jsonString = '{"0": true, "1": false}';

        $result = ConfigLoader::loadConfig($jsonString);

        self::assertSame([0 => true, 1 => false], $result);
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \JsonException
     */
    #[Test]
    public function loadConfigWithEmptyStringThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration must be a valid file path or a JSON string.');

        ConfigLoader::loadConfig('');
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \JsonException
     */
    #[Test]
    public function loadConfigWithNestedJsonObject(): void
    {
        $this->expectException(\JsonException::class);
        $this->expectExceptionMessage('Maximum stack depth exceeded');

        $json = '{"key1": {"subkey": true}, "key2": false}';

        ConfigLoader::loadConfig($json);
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \JsonException
     */
    #[Test]
    public function loadConfigWithInvalidStringThrowsException(): void
    {
        $this->expectException(\JsonException::class);

        ConfigLoader::loadConfig('not a valid json or file path');
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \JsonException
     */
    #[Test]
    public function loadConfigWithEmptyJsonObjectReturnsEmptyArray(): void
    {
        $json = '{}';
        $result = ConfigLoader::loadConfig($json);
        self::assertSame([], $result);
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \JsonException
     */
    #[Test]
    public function itFailsOnTooDeeplyNestedJson(): void
    {
        $this->expectException(\JsonException::class);

        $json = '{"foo": {"bar": false}, "baz": 0}';

        ConfigLoader::loadConfig($json);
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \JsonException
     */
    #[Test]
    public function loadConfigWithNonArrayJsonThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration must be a valid file path or a JSON string.');

        ConfigLoader::loadConfig('true');
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \JsonException
     */
    #[Test]
    public function loadConfigWithNonExistentFilePathReportsTheMissingFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration file "/path/to/non-existent-file.json" does not exist.');

        $nonExistentFile = '/path/to/non-existent-file.json';

        ConfigLoader::loadConfig($nonExistentFile);
    }

    /**
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    #[DataProvider('provideLoadConfigRejectsValuesThatAreNotBooleansCases')]
    #[Test]
    public function loadConfigRejectsValuesThatAreNotBooleans(string $json, string $key): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Configuration value for "%s" must be true or false.', $key));

        ConfigLoader::loadConfig($json);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideLoadConfigRejectsValuesThatAreNotBooleansCases(): iterable
    {
        yield 'string "true"' => ['{"removeComments": "true"}', 'removeComments'];

        yield 'null' => ['{"removeComments": null}', 'removeComments'];

        yield 'number 0' => ['{"removeComments": 0}', 'removeComments'];

        yield 'later key after a valid one' => ['{"removeComments": true, "sortAttributes": 1.0}', 'sortAttributes'];

        yield 'list of numbers' => ['[1]', '0'];
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \JsonException
     */
    #[DataProvider('provideLoadConfigRejectsJsonThatIsNotStrictCases')]
    #[Test]
    public function loadConfigRejectsJsonThatIsNotStrict(string $json): void
    {
        $this->expectException(\JsonException::class);

        ConfigLoader::loadConfig($json);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideLoadConfigRejectsJsonThatIsNotStrictCases(): iterable
    {
        yield 'trailing comma' => ['{"removeComments": true,}'];

        yield 'single quotes' => ["{'removeComments': true}"];

        yield 'unquoted key' => ['{removeComments: true}'];

        yield 'comment' => ['{"removeComments": true /* c */}'];

        yield 'truncated' => ['{"removeComments": true'];
    }

    /**
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function loadConfigStripsAByteOrderMarkFromAJsonString(): void
    {
        self::assertSame(['removeComments' => true], ConfigLoader::loadConfig("\xEF\xBB\xBF{\"removeComments\": true}"));
    }

    /**
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function loadConfigReadsAFileWithCrlfLineEndingsAndAPathWithSpacesAndUnicode(): void
    {
        $directory = sys_get_temp_dir() . '/config dir ü ' . uniqid();
        mkdir($directory);
        $path = $directory . '/my.config.json';
        file_put_contents($path, "{\r\n  \"removeComments\": true,\r\n  \"sortAttributes\": false\r\n}\r\n");

        try {
            self::assertSame(['removeComments' => true, 'sortAttributes' => false], ConfigLoader::loadConfig($path));
        } finally {
            unlink($path);
            rmdir($directory);
        }
    }

    /**
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function loadConfigKeepsTheLastValueOfADuplicateKey(): void
    {
        self::assertSame(['removeComments' => false], ConfigLoader::loadConfig('{"removeComments": true, "removeComments": false}'));
    }

    /**
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function loadConfigRejectsAFileThatHoldsOnlyAByteOrderMark(): void
    {
        file_put_contents($this->configFile, "\xEF\xBB\xBF");

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration must be a valid file path or a JSON string.');

        ConfigLoader::loadConfig($this->configFile);
    }

    /**
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function loadConfigReportsAMissingFileGivenWithAWindowsPath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration file "missing\config" does not exist.');

        ConfigLoader::loadConfig('missing\config');
    }

    /**
     * @throws \TypeError
     */
    #[\Override]
    protected function setUp(): void
    {
        $this->configFile = tempnam(sys_get_temp_dir(), 'config_test_');
    }

    #[\Override]
    protected function tearDown(): void
    {
        if (file_exists($this->configFile)) {
            unlink($this->configFile);
        }
    }

    /**
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function loadConfigRejectsADirectory(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Configuration "%s" is a directory, not a file or a JSON string.', sys_get_temp_dir()));

        ConfigLoader::loadConfig(sys_get_temp_dir());
    }

    /**
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function loadConfigRejectsAFileThatCannotBeRead(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to read configuration content.');

        UnreadableFileStreamWrapper::run(static fn (string $path): array => ConfigLoader::loadConfig($path));
    }
}
