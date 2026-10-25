<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Support;

/**
 * @internal
 */
final class UnreadableDirectoryStreamWrapper
{
    public const string PROTOCOL = 'unreadabledir';

    public const string READABLE_DIRECTORY = self::PROTOCOL . '://readable';

    public const string UNREADABLE_DIRECTORY = self::PROTOCOL . '://unreadable';

    private const array ENTRIES = [
        'readable' => ['icon.svg', 'locked', 'nested'],
        'readable/nested' => ['inner.svg'],
    ];

    private const array FILES = ['readable/icon.svg', 'readable/nested/inner.svg'];

    /** @var resource|null */
    public $context;

    private string $directory = '';

    /** @var list<string> */
    private array $entries = [];

    /**
     * @template T
     *
     * @param \Closure(): T $callback runs while the protocol is registered
     *
     * @param-immediately-invoked-callable $callback
     *
     * @return T
     */
    public static function run(\Closure $callback): mixed
    {
        stream_wrapper_register(self::PROTOCOL, self::class);
        set_error_handler(static fn (): bool => true);

        try {
            return $callback();
        } finally {
            restore_error_handler();
            stream_wrapper_unregister(self::PROTOCOL);
        }
    }

    /**
     * @return array<string, int>
     */
    public function url_stat(string $path): array
    {
        $mode = \in_array($this->name($path), self::FILES, true) ? 0o100_644 : 0o040_755;

        return ['mode' => $mode, 'size' => 1, 'uid' => (int) getmyuid(), 'gid' => (int) getmygid()];
    }

    public function dir_opendir(string $path): bool
    {
        $name = $this->name($path);

        if (!\array_key_exists($name, self::ENTRIES)) {
            return false;
        }

        $this->directory = $name;
        $this->entries = self::ENTRIES[$name];

        return true;
    }

    public function dir_readdir(): false|string
    {
        return array_shift($this->entries) ?? false;
    }

    public function dir_rewinddir(): bool
    {
        $this->entries = self::ENTRIES[$this->directory];

        return true;
    }

    public function dir_closedir(): bool
    {
        return true;
    }

    private function name(string $path): string
    {
        return trim(str_replace([self::PROTOCOL . '://', '\\'], ['', '/'], $path), '/');
    }
}
