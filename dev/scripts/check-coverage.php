<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

$cloverPath = $argv[1] ?? 'coverage.xml';
$clover = is_file($cloverPath) ? simplexml_load_file($cloverPath) : false;

if (false === $clover) {
    fwrite(\STDERR, 'Cannot read the Clover report "' . $cloverPath . '".' . \PHP_EOL);

    exit(2);
}

$uncovered = 0;

foreach ($clover->xpath('//file') ?: [] as $file) {
    foreach ($file->xpath('line[@type="stmt" and @count="0"]') ?: [] as $line) {
        ++$uncovered;
        $path = str_replace('\\', '/', (string) $file['name']);
        $relative = str_contains($path, '/src/') ? 'src/' . mb_substr($path, (int) mb_strrpos($path, '/src/') + 5) : $path;
        echo sprintf('::error file=%s,line=%d::This line is not covered by a test.', $relative, (int) $line['num']), \PHP_EOL;
    }
}

if ($uncovered > 0) {
    fwrite(\STDERR, sprintf('%d line(s) are not covered by a test.', $uncovered) . \PHP_EOL);

    exit(1);
}

echo 'Every line is covered by a test.', \PHP_EOL;
