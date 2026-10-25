<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Output;

use MathiasReker\PhpSvgOptimizer\Console\Output\Style;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Style::class)]
final class StyleTest extends TestCase
{
    #[DataProvider('provideApplyWrapsTheTextInTheEscapeSequenceCases')]
    #[Test]
    public function applyWrapsTheTextInTheEscapeSequence(Style $style, string $sequence): void
    {
        self::assertSame($sequence . "text\033[0m", $style->apply('text', true));
    }

    /**
     * @return iterable<string, array{Style, string}>
     */
    public static function provideApplyWrapsTheTextInTheEscapeSequenceCases(): iterable
    {
        yield 'bold' => [Style::Bold, "\033[1m"];

        yield 'cyan' => [Style::Cyan, "\033[36m"];

        yield 'green' => [Style::Green, "\033[32m"];

        yield 'red' => [Style::Red, "\033[31m"];
    }

    #[Test]
    public function applyKeepsTheTextWhenStylingIsDisabled(): void
    {
        self::assertSame('text', Style::Red->apply('text', false));
    }
}
