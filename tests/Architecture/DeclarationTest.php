<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Architecture;

use MathiasReker\PhpSvgOptimizer\Exception\ExceptionInterface;
use MathiasReker\PhpSvgOptimizer\Exception\IOException;
use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Pass\PassInterface;
use MathiasReker\PhpSvgOptimizer\Pass\Pipeline;
use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use PHPat\Test\PHPat;

/**
 * @internal
 */
final class DeclarationTest
{
    private const string PACKAGE = 'MathiasReker\PhpSvgOptimizer';

    #[TestRule]
    public function exceptionsImplementTheExceptionInterface(): BuildStep
    {
        return PHPat::rule()
            ->classes(Selector::AllOf($this->package(), Selector::isException()))
            ->should()->implement()
            ->classes(Selector::classname(ExceptionInterface::class))
            ->because('callers catch every exception of the package with one type');
    }

    #[TestRule]
    public function passesImplementThePassInterface(): BuildStep
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('/^' . preg_quote(self::PACKAGE . '\Pass', '/') . '$/', true))
            ->excluding(
                Selector::isInterface(),
                Selector::classname(ExecutionOrder::class),
                Selector::classname(Pipeline::class),
            )
            ->should()->implement()
            ->classes(Selector::classname(PassInterface::class))
            ->because('the pipeline runs every pass through the same interface');
    }

    #[TestRule]
    public function interfaceNamesEndInInterface(): BuildStep
    {
        return PHPat::rule()
            ->classes(Selector::AllOf($this->package(), Selector::isInterface()))
            ->should()->beNamed('/Interface$/', true)
            ->because('the suffix tells interfaces apart from classes');
    }

    #[TestRule]
    public function exceptionNamesEndInException(): BuildStep
    {
        return PHPat::rule()
            ->classes(Selector::AllOf($this->package(), Selector::isException()))
            ->should()->beNamed('/Exception$/', true)
            ->because('the suffix tells exceptions apart from other classes');
    }

    #[TestRule]
    public function classesAreFinal(): BuildStep
    {
        return PHPat::rule()
            ->classes($this->package())
            ->excluding(
                Selector::isAbstract(),
                Selector::isInterface(),
                Selector::isTrait(),
                Selector::isEnum(),
                Selector::classname(IOException::class),
            )
            ->should()->beFinal()
            ->because('classes are closed for extension; IOException is the documented base of FileNotFoundException');
    }

    private function package(): SelectorInterface
    {
        return Selector::AllOf(
            Selector::inNamespace(self::PACKAGE),
            Selector::Not(Selector::inNamespace(self::PACKAGE . '\Tests')),
        );
    }
}
