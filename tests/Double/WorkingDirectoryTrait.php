<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Double;

use PHPUnit\Framework\Attributes\After;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait WorkingDirectoryTrait
{
    private string $originalWorkingDirectory = '';

    /**
     * @param string $directory the working directory for the rest of the test, which is restored after it
     */
    private function changeWorkingDirectory(string $directory): void
    {
        if ('' === $this->originalWorkingDirectory) {
            $this->originalWorkingDirectory = (string) getcwd();
        }

        chdir($directory);
    }

    #[After(1)]
    protected function restoreWorkingDirectory(): void
    {
        if ('' !== $this->originalWorkingDirectory) {
            chdir($this->originalWorkingDirectory);
            $this->originalWorkingDirectory = '';
        }
    }
}
