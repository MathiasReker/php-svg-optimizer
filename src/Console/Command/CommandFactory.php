<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Command;

use MathiasReker\PhpSvgOptimizer\Console\Input\ArgumentParser;
use MathiasReker\PhpSvgOptimizer\Console\Output\Manager\OutputManager;
use MathiasReker\PhpSvgOptimizer\Contract\Console\Command\CommandInterface;
use MathiasReker\PhpSvgOptimizer\Contract\Console\Output\Stream\StreamInterface;
use MathiasReker\PhpSvgOptimizer\ValueObject\CommandOptionsValueObject;

/**
 * @no-named-arguments
 */
final readonly class CommandFactory
{
    /**
     * Constructor for CommandFactory.
     *
     * @param StreamInterface $stream         The output stream to use
     * @param ArgumentParser  $argumentParser The argument parser to use
     */
    public function __construct(
        public StreamInterface $stream,
        public ArgumentParser $argumentParser,
    ) {}

    /**
     * Create a new Command instance with the provided options.
     *
     * @param CommandOptionsValueObject $commandOptionsValueObject The options for the command
     *
     * @return CommandInterface The created command instance
     *
     * @throws \InvalidArgumentException If the options are not valid
     */
    public function create(CommandOptionsValueObject $commandOptionsValueObject): CommandInterface
    {
        return new Command(
            $this->argumentParser->getPaths(),
            $commandOptionsValueObject,
            $this->buildOutputHelper()
        );
    }

    /**
     * Helper to build a reusable OutputHelper.
     */
    private function buildOutputHelper(): OutputManager
    {
        return new OutputManager($this->stream);
    }
}
