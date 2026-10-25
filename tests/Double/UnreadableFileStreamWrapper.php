<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Double;

/**
 * @internal
 */
final class UnreadableFileStreamWrapper
{
    public const string PROTOCOL = 'unreadable';

    /** @var resource|null */
    public $context;

    /**
     * @template T
     *
     * @param \Closure(string): T $callback receives the path of a readable file that cannot be opened
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
            return $callback(self::PROTOCOL . '://file.svg');
        } finally {
            restore_error_handler();
            stream_wrapper_unregister(self::PROTOCOL);
        }
    }

    /**
     * @return array<string, int>
     */
    public function url_stat(): array
    {
        return ['mode' => 0o100_444, 'size' => 1, 'uid' => (int) getmyuid(), 'gid' => (int) getmygid()];
    }

    public function stream_open(): bool
    {
        return false;
    }
}
