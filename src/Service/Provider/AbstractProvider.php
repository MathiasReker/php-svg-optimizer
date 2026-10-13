<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Provider;

use MathiasReker\PhpSvgOptimizer\Contract\Service\Provider\SvgProviderInterface;
use MathiasReker\PhpSvgOptimizer\Exception\IOException;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;

/**
 * @internal
 *
 * @no-named-arguments
 */
abstract class AbstractProvider implements SvgProviderInterface
{
    private const int DIRECTORY_PERMISSION = 0o755;

    protected string $outputContent = '';

    protected readonly DomDocumentWrapper $domDocumentWrapper;

    protected string $inputContent = '';

    public function __construct()
    {
        $this->domDocumentWrapper = new DomDocumentWrapper();
    }

    #[\Override]
    final public function optimize(\DOMDocument $domDocument): self
    {
        $this->outputContent = $this->domDocumentWrapper->saveToString($domDocument);

        return $this;
    }

    abstract public function loadContent(): \DOMDocument;

    abstract public function getInputContent(): string;

    /**
     * @param string $path The path to save the optimized SVG content to
     *
     * @throws IOException If the output file cannot be written
     */
    #[\Override]
    final public function saveToFile(string $path): self
    {
        if (!$this->ensureDirectoryExists(\dirname($path))) {
            throw new IOException(\sprintf('Failed to create directory for output file: %s', $path));
        }

        if (is_dir($path) || false === $this->withoutWarnings(fn (): false|int => file_put_contents($path, $this->getOutputContent()))) {
            throw new IOException(\sprintf('Failed to write optimized content to the output file: %s', $path));
        }

        return $this;
    }

    /**
     * @param string $directoryPath The directory path to check/create
     */
    private function ensureDirectoryExists(string $directoryPath): bool
    {
        if (is_dir($directoryPath)) {
            return true;
        }

        if (file_exists($directoryPath)) {
            return false;
        }

        $existingAncestor = \dirname($directoryPath);
        while (!file_exists($existingAncestor) && \dirname($existingAncestor) !== $existingAncestor) {
            $existingAncestor = \dirname($existingAncestor);
        }

        if (!is_dir($existingAncestor)) {
            return false;
        }

        return $this->withoutWarnings(static fn (): bool => mkdir($directoryPath, self::DIRECTORY_PERMISSION, true)) || is_dir($directoryPath);
    }

    /**
     * @template T
     *
     * @param \Closure(): T $callback a file system operation that reports a failure through its return value
     *
     * @param-immediately-invoked-callable $callback
     *
     * @return T
     */
    private function withoutWarnings(\Closure $callback): mixed
    {
        set_error_handler(static fn (): bool => true);

        try {
            return $callback();
        } finally {
            restore_error_handler();
        }
    }

    #[\Override]
    final public function getOutputContent(): string
    {
        return $this->outputContent;
    }

    /**
     * @param \DOMDocument $domDocument The \DOMDocument to serialize
     *
     * @return string The serialized XML content
     */
    final public function serialize(\DOMDocument $domDocument): string
    {
        return $this->domDocumentWrapper->saveToString($domDocument);
    }
}
