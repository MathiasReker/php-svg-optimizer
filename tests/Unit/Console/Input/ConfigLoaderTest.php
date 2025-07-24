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
    public function testLoadConfigWithValidJsonString(): void
    {
        $jsonString = '{"key1": true, "key2": false}';

        $result = ConfigLoader::loadConfig($jsonString);

        self::assertSame(['key1' => true, 'key2' => false], $result);
    }

    /**
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    public function testLoadConfigWithValidJsonFile(): void
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
    public function testLoadConfigWithInvalidJsonString(): void
    {
        $this->expectException(\JsonException::class);

        $invalidJsonString = '{"key1": true, "key2": }';

        ConfigLoader::loadConfig($invalidJsonString);
    }

    /**
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    public function testLoadConfigWithInvalidJsonFile(): void
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
    public function testLoadConfigWithEmptyJsonContent(): void
    {
        file_put_contents($this->configFile, '');

        $this->expectException(\InvalidArgumentException::class);

        ConfigLoader::loadConfig($this->configFile);
    }

    /**
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    public function testLoadConfigWithBooleanAndNumericValues(): void
    {
        $jsonString = '{"flag": true, "disabled": 0, "enabled": 1}';

        $result = ConfigLoader::loadConfig($jsonString);

        self::assertSame(
            [
                'flag' => true,
                'disabled' => false,
                'enabled' => true,
            ],
            $result
        );
    }

    /**
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    public function testLoadConfigWithNonBooleanValues(): void
    {
        $jsonString = '{"key1": "yes", "key2": 123, "key3": null}';

        $result = ConfigLoader::loadConfig($jsonString);

        self::assertSame(
            [
                'key1' => true,  // "yes" casts to true
                'key2' => true,  // 123 casts to true
                'key3' => false, // null casts to false
            ],
            $result
        );
    }

    /**
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    public function testLoadConfigWithNumericKeys(): void
    {
        $jsonString = '{"0": true, "1": false}';

        $result = ConfigLoader::loadConfig($jsonString);

        /*
         * @phpstan-ignore-next-line
         */
        self::assertSame(['0' => true, '1' => false], $result);
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \JsonException
     */
    public function testLoadConfigWithEmptyStringThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration must be a valid file path or a JSON string.');

        ConfigLoader::loadConfig('');
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \JsonException
     */
    public function testLoadConfigWithNestedJsonObject(): void
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
    public function testLoadConfigWithInvalidStringThrowsException(): void
    {
        $this->expectException(\JsonException::class);

        ConfigLoader::loadConfig('not a valid json or file path');
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \JsonException
     */
    public function testLoadConfigWithEmptyJsonObjectReturnsEmptyArray(): void
    {
        $json = '{}';
        $result = ConfigLoader::loadConfig($json);
        self::assertSame([], $result);
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \JsonException
     */
    public function testItFailsOnTooDeeplyNestedJson(): void
    {
        $this->expectException(\JsonException::class);

        $json = '{"foo": {"bar": false}, "baz": 0}';

        ConfigLoader::loadConfig($json);
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \JsonException
     */
    public function testLoadConfigWithNonArrayJsonThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration must be a valid file path or a JSON string.');

        ConfigLoader::loadConfig('true');
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \JsonException
     */
    public function testLoadConfigWithNonExistentFilePathThrowsJsonException(): void
    {
        $this->expectException(\JsonException::class);

        $nonExistentFile = '/path/to/non-existent-file.json';

        ConfigLoader::loadConfig($nonExistentFile);
    }

    protected function setUp(): void
    {
        $this->configFile = tempnam(sys_get_temp_dir(), 'config_test_');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->configFile)) {
            unlink($this->configFile);
        }
    }
}
