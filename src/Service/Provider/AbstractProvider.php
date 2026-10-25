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
        $this->assertUsablePath($path, 'Output');

        if (is_dir($path)) {
            throw new IOException(\sprintf('Output path is a directory: %s', $path));
        }

        if (!$this->ensureDirectoryExists(\dirname($path))) {
            throw new IOException(\sprintf('Failed to create directory for output file: %s', $path));
        }

        if (false === $this->withoutWarnings(fn (): false|int => file_put_contents($path, $this->getOutputContent()))) {
            throw new IOException(\sprintf('Failed to write optimized content to the output file: %s', $path));
        }

        return $this;
    }

    /**
     * @param string $path the file path to check
     * @param string $role the role of the file in messages, `Input` or `Output`
     *
     * @throws IOException If the path is empty or contains a null byte
     */
    final protected function assertUsablePath(string $path, string $role): void
    {
        if ('' === $path) {
            throw new IOException(\sprintf('%s path is empty.', $role));
        }

        if (str_contains($path, "\0")) {
            throw new IOException(\sprintf('%s path contains a null byte: %s', $role, str_replace("\0", '\0', $path)));
        }
    }

    /**
     * @param string $directoryPath The directory path to check/create
     */
    private function ensureDirectoryExists(string $directoryPath): bool
    {
        return is_dir($directoryPath)
            || $this->withoutWarnings(static fn (): bool => mkdir($directoryPath, self::DIRECTORY_PERMISSION, true))
            || is_dir($directoryPath);
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
