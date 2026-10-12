<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Contract\Exception;

use MathiasReker\PhpSvgOptimizer\Contract\Exception\SvgOptimizerExceptionInterface;
use MathiasReker\PhpSvgOptimizer\Exception\FileNotFoundException;
use MathiasReker\PhpSvgOptimizer\Exception\IOException;
use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Exception\SvgValidationException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Service\Facade\SvgOptimizerFacade;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversNothing]
final class SvgOptimizerExceptionInterfaceTest extends TestCase
{
    /**
     * @param class-string<\Throwable> $exceptionClass
     */
    #[DataProvider('provideEveryExceptionImplementsTheInterfaceCases')]
    #[Test]
    public function everyExceptionImplementsTheInterface(string $exceptionClass): void
    {
        self::assertTrue(is_subclass_of($exceptionClass, SvgOptimizerExceptionInterface::class));
    }

    /**
     * @return iterable<string, array{class-string<\Throwable>}>
     */
    public static function provideEveryExceptionImplementsTheInterfaceCases(): iterable
    {
        yield 'file not found' => [FileNotFoundException::class];

        yield 'io' => [IOException::class];

        yield 'risky rules not allowed' => [RiskyRulesNotAllowedException::class];

        yield 'svg validation' => [SvgValidationException::class];

        yield 'xml processing' => [XmlProcessingException::class];
    }

    #[Test]
    public function everyClassInTheExceptionNamespaceIsListed(): void
    {
        $files = glob(__DIR__ . '/../../../../src/Exception/*Exception.php');
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
     * @throws SvgValidationException
     * @throws XmlProcessingException
     */
    #[Test]
    public function libraryErrorsCanBeCaughtThroughTheInterface(): void
    {
        $this->expectException(SvgOptimizerExceptionInterface::class);

        SvgOptimizerFacade::fromString('<html/>')->optimize();
    }
}
