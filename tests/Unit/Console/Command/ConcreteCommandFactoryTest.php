<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Command;

use MathiasReker\PhpSvgOptimizer\Console\Command\AbstractCommandFactory;
use MathiasReker\PhpSvgOptimizer\Console\Output\OutputHelper;
use MathiasReker\PhpSvgOptimizer\Contract\Console\Command\CommandInterface;
use MathiasReker\PhpSvgOptimizer\Contract\Console\Input\Stream\StreamInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AbstractCommandFactory::class)]
#[CoversClass(OutputHelper::class)]
final class ConcreteCommandFactoryTest extends TestCase
{
    public function testConstructorStoresStream(): void
    {
        $dummyStream = $this->createMock(StreamInterface::class);
        $dummyCommand = $this->createMock(CommandInterface::class);

        $factory = new class($dummyStream, $dummyCommand) extends AbstractCommandFactory {
            private CommandInterface $command;

            public function __construct(StreamInterface $stream, CommandInterface $command)
            {
                parent::__construct($stream);
                $this->command = $command;
            }

            #[\Override]
            public function create(array $argv): CommandInterface
            {
                return $this->command;
            }

            public function getOutputStream(): StreamInterface
            {
                /*
                 * @var StreamInterface
                 * @phpstan-ignore-next-line
                 */
                return (new \ReflectionProperty(AbstractCommandFactory::class, 'output'))->getValue($this);
            }
        };

        self::assertSame($dummyStream, $factory->getOutputStream());
    }

    public function testIsCliReturnsTrueWhenInCliEnvironment(): void
    {
        $dummyStream = $this->createMock(StreamInterface::class);
        $dummyCommand = $this->createMock(CommandInterface::class);

        $factory = new class($dummyStream, $dummyCommand) extends AbstractCommandFactory {
            private CommandInterface $command;

            public function __construct(StreamInterface $stream, CommandInterface $command)
            {
                parent::__construct($stream);
                $this->command = $command;
            }

            #[\Override]
            public function create(array $argv): CommandInterface
            {
                return $this->command;
            }

            public function callIsCli(): bool
            {
                return $this->isCli();
            }
        };

        self::assertTrue($factory->callIsCli());
    }
}
