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
    public function loadConfigWithNonExistentFilePathThrowsJsonException(): void
    {
        $this->expectException(\JsonException::class);

        $nonExistentFile = '/path/to/non-existent-file.json';

        ConfigLoader::loadConfig($nonExistentFile);
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
}
