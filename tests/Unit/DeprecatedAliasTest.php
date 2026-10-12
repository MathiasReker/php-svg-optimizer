<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversNothing]
final class DeprecatedAliasTest extends TestCase
{
    private const string NAMESPACE = 'MathiasReker\PhpSvgOptimizer\\';

    /**
     * Old class name => moved class name, relative to the package namespace.
     */
    private const array ALIASES = [
        'Model\MetaDataAggregator' => 'Console\Output\MetaDataAggregator',
        'Service\Data\ArgumentData' => 'Console\Help\ArgumentData',
        'Service\Filesystem\Finder' => 'Console\Filesystem\Finder',
        'Service\Formatter\ByteFormatter' => 'Console\Output\Formatter\ByteFormatter',
        'Service\Processor\SvgFileProcessor' => 'Console\Processor\SvgFileProcessor',
        'Type\Command' => 'Console\Type\Command',
        'Type\Option' => 'Console\Type\Option',
        'ValueObject\CliOption' => 'Console\ValueObject\CliOption',
        'ValueObject\CommandHelp' => 'Console\ValueObject\CommandHelp',
        'ValueObject\CommandOption' => 'Console\ValueObject\CommandOption',
        'ValueObject\ExampleCommand' => 'Console\ValueObject\ExampleCommand',
    ];

    #[DataProvider('provideOldClassNameResolvesToTheMovedClassCases')]
    public function testOldClassNameResolvesToTheMovedClass(string $old, string $new): void
    {
        self::assertTrue(class_exists($old) || enum_exists($old), $old);
        self::assertSame($new, (new \ReflectionClass($old))->getName());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOldClassNameResolvesToTheMovedClassCases(): iterable
    {
        foreach (self::ALIASES as $old => $new) {
            yield $old => [self::NAMESPACE . $old, self::NAMESPACE . $new];
        }
    }

    public function testEveryShimFileHasAnAlias(): void
    {
        $directory = __DIR__ . '/../../deprecated/';
        $shims = array_merge($this->glob($directory . '*/*.php'), $this->glob($directory . '*/*/*.php'));

        $classes = array_map(
            static fn (string $file): string => strtr(mb_substr($file, mb_strlen($directory), -4), '/', '\\'),
            $shims,
        );
        sort($classes);

        $expected = array_keys(self::ALIASES);
        sort($expected);

        self::assertSame($expected, $classes);
    }

    /**
     * @return list<string>
     */
    private function glob(string $pattern): array
    {
        $files = glob($pattern);
        self::assertIsArray($files);

        return $files;
    }
}
