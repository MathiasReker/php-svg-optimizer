<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Output;

use MathiasReker\PhpSvgOptimizer\Console\Output\AbstractStream;
use MathiasReker\PhpSvgOptimizer\Tests\Double\BufferedStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AbstractStream::class)]
final class AbstractStreamTest extends TestCase
{
    /**
     * @param array<string, false|string> $environment the color related variables, false to leave one unset
     *
     * @throws \RuntimeException
     */
    #[DataProvider('provideHasColorSupportFollowsTheEnvironmentCases')]
    #[Test]
    public function hasColorSupportFollowsTheEnvironment(array $environment, bool $expected): void
    {
        $original = [];

        foreach ($environment as $name => $value) {
            $original[$name] = getenv($name);
            false === $value ? putenv($name) : putenv($name . '=' . $value);
        }

        try {
            self::assertSame($expected, $this->createStream()->hasColorSupport());
        } finally {
            foreach ($original as $name => $value) {
                false === $value ? putenv($name) : putenv($name . '=' . $value);
            }
        }
    }

    /**
     * @return iterable<string, array{array<string, false|string>, bool}>
     */
    public static function provideHasColorSupportFollowsTheEnvironmentCases(): iterable
    {
        $unset = ['NO_COLOR' => false, 'FORCE_COLOR' => false, 'CLICOLOR_FORCE' => false, 'TERM' => 'xterm'];

        yield 'a stream that is not a terminal' => [$unset, false];

        yield 'NO_COLOR set' => [['NO_COLOR' => '1', 'FORCE_COLOR' => '1'] + $unset, false];

        yield 'NO_COLOR empty' => [['NO_COLOR' => '', 'FORCE_COLOR' => '1'] + $unset, true];

        yield 'FORCE_COLOR set' => [['FORCE_COLOR' => '1'] + $unset, true];

        yield 'FORCE_COLOR zero' => [['FORCE_COLOR' => '0', 'CLICOLOR_FORCE' => '1'] + $unset, false];

        yield 'CLICOLOR_FORCE set' => [['CLICOLOR_FORCE' => '1'] + $unset, true];

        yield 'CLICOLOR_FORCE zero' => [['CLICOLOR_FORCE' => '0'] + $unset, false];
    }

    #[Test]
    public function throwsWhenTheStreamCannotBeOpened(): void
    {
        set_error_handler(static fn (): bool => true);

        try {
            new class('php://unknown-stream', 'r') extends AbstractStream {};
            self::fail('Expected a RuntimeException.');
        } catch (\RuntimeException $runtimeException) {
            self::assertSame('Unable to open the stream "php://unknown-stream".', $runtimeException->getMessage());
        } finally {
            restore_error_handler();
        }
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function writeAppendsToTheStream(): void
    {
        $bufferedStream = $this->createStream();

        $bufferedStream->write('Hello');
        $bufferedStream->write(' World');

        self::assertSame('Hello World', $bufferedStream->getContent());
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function writeLineAppendsALineBreak(): void
    {
        $bufferedStream = $this->createStream();

        $bufferedStream->writeLine('Line1');
        $bufferedStream->writeLine('Line2');

        self::assertSame('Line1' . \PHP_EOL . 'Line2' . \PHP_EOL, $bufferedStream->getContent());
    }

    /**
     * @throws \RuntimeException
     */
    private function createStream(): BufferedStream
    {
        return new BufferedStream();
    }
}
