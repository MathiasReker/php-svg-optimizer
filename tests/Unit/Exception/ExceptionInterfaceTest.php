<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Exception;

use MathiasReker\PhpSvgOptimizer\Exception\ExceptionInterface;
use MathiasReker\PhpSvgOptimizer\Exception\FileNotFoundException;
use MathiasReker\PhpSvgOptimizer\Exception\InvalidSvgException;
use MathiasReker\PhpSvgOptimizer\Exception\IOException;
use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Xml\ParseException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversNothing]
final class ExceptionInterfaceTest extends TestCase
{
    /**
     * @param class-string<\Throwable> $exceptionClass
     */
    #[DataProvider('provideEveryExceptionImplementsTheInterfaceCases')]
    #[Test]
    public function everyExceptionImplementsTheInterface(string $exceptionClass): void
    {
        self::assertTrue(is_subclass_of($exceptionClass, ExceptionInterface::class));
    }

    /**
     * @return iterable<string, array{class-string<\Throwable>}>
     */
    public static function provideEveryExceptionImplementsTheInterfaceCases(): iterable
    {
        yield 'file not found' => [FileNotFoundException::class];

        yield 'io' => [IOException::class];

        yield 'risky rules not allowed' => [RiskyRulesNotAllowedException::class];

        yield 'invalid svg' => [InvalidSvgException::class];
    }

    #[Test]
    public function everyClassInTheExceptionNamespaceIsListed(): void
    {
        $files = glob(__DIR__ . '/../../../src/Exception/*Exception.php');
        self::assertIsArray($files);

        $classes = array_map(static fn (string $file): string => basename($file, '.php'), $files);
        sort($classes);

        $listed = array_map(
            static fn (array $case): string => (new \ReflectionClass($case[0]))->getShortName(),
            iterator_to_array(self::provideEveryExceptionImplementsTheInterfaceCases(), false),
        );
        sort($listed);

        self::assertSame($listed, $classes);
    }

    /**
     * @throws RiskyRulesNotAllowedException
     * @throws InvalidSvgException
     */
    #[Test]
    public function libraryErrorsCanBeCaughtThroughTheInterface(): void
    {
        $this->expectException(ExceptionInterface::class);

        (new SvgOptimizer())->optimizeString('<html/>');
    }

    /**
     * @param class-string<\Throwable> $exceptionClass
     * @param class-string<\Throwable> $parentClass
     */
    #[DataProvider('provideExtendsItsParentCases')]
    #[Test]
    public function extendsItsParent(string $exceptionClass, string $parentClass): void
    {
        self::assertSame($parentClass, get_parent_class($exceptionClass));
    }

    /**
     * @return iterable<string, array{class-string<\Throwable>, class-string<\Throwable>}>
     */
    public static function provideExtendsItsParentCases(): iterable
    {
        yield 'file not found' => [FileNotFoundException::class, \RuntimeException::class];

        yield 'io' => [IOException::class, \RuntimeException::class];

        yield 'invalid svg' => [InvalidSvgException::class, \RuntimeException::class];

        yield 'risky rules not allowed' => [RiskyRulesNotAllowedException::class, \LogicException::class];

        yield 'parse' => [ParseException::class, \RuntimeException::class];
    }
}
