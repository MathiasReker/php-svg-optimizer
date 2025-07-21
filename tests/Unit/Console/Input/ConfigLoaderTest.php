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
