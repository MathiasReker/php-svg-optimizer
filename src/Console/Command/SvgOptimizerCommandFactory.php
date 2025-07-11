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
use MathiasReker\PhpSvgOptimizer\Contract\Console\Command\CommandInterface;
use MathiasReker\PhpSvgOptimizer\Type\Application;
use MathiasReker\PhpSvgOptimizer\Type\Option;

/**
 * @no-named-arguments
 */
final class SvgOptimizerCommandFactory extends AbstractCommandFactory
{
    /**
     * Create a new SvgOptimizerCommand instance.
     *
     * @param array<int, string> $argv The command line arguments.
     *
     * @throws \InvalidArgumentException
     * @throws \JsonException
     */
    #[\Override]
    public function create(array $argv): CommandInterface
    {
        $outputHelper = $this->buildOutputHelper();
        $parser = new ArgumentParser($argv);

        if ($parser->isEmpty() || $parser->hasOption(Option::HELP)) {
            $outputHelper->printHelp();
            exit(0);
        }

        if ($parser->hasOption(Option::VERSION)) {
            $outputHelper->printVersion(Application::NAME->value, Application::VERSION->value, Application::AUTHOR->value);
            exit(0);
        }

        // Don't call getNextPositionalArgumentIndex() until after early exits
        $paths = \array_slice($argv, $parser->getNextPositionalArgumentStartIndex());
        $configPath = $parser->hasOption(Option::CONFIG) ? $parser->getOption(Option::CONFIG) : '';

        return new SvgOptimizerCommand(
            $paths,
            $configPath,
            $outputHelper,
            $parser->hasOption(Option::DRY_RUN),
            $parser->hasOption(Option::QUIET)
        );
    }
}
