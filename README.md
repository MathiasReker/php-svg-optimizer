<h1 align="center">PHP SVG Optimizer</h1>

[![Packagist Version](https://img.shields.io/packagist/v/MathiasReker/php-svg-optimizer.svg)](https://packagist.org/packages/MathiasReker/php-svg-optimizer)
[![Packagist Downloads](https://img.shields.io/packagist/dt/MathiasReker/php-svg-optimizer.svg?color=%23ff007f)](https://packagist.org/packages/MathiasReker/php-svg-optimizer)
[![CI status](https://github.com/MathiasReker/php-svg-optimizer/actions/workflows/ci.yml/badge.svg?branch=develop)](https://github.com/MathiasReker/php-svg-optimizer/actions/workflows/ci.yml)
[![Codacy Security Scan](https://github.com/MathiasReker/php-svg-optimizer/actions/workflows/codacy.yml/badge.svg)](https://github.com/MathiasReker/php-svg-optimizer/actions/workflows/codacy.yml)
[![Aikido Security Score](https://img.shields.io/badge/Aikido_Security-100%25-brightgreen)](https://intel.aikido.dev/packages/packagist/mathiasreker/php-svg-optimizer)
![PHPStan Level](https://img.shields.io/badge/PHPStan-Level%20max-blue)
![Type Coverage](https://raw.githubusercontent.com/MathiasReker/php-svg-optimizer/develop/dev/artifacts/type-coverage.svg)
![Code Coverage](https://raw.githubusercontent.com/MathiasReker/php-svg-optimizer/develop/dev/artifacts/coverage.svg)
![Mutation Score](https://raw.githubusercontent.com/MathiasReker/php-svg-optimizer/develop/dev/artifacts/mutation-score.svg)
![Mutation Coverage](https://raw.githubusercontent.com/MathiasReker/php-svg-optimizer/develop/dev/artifacts/mutation-coverage.svg)
![Tests](https://raw.githubusercontent.com/MathiasReker/php-svg-optimizer/develop/dev/artifacts/tests.svg)
![Assertions](https://raw.githubusercontent.com/MathiasReker/php-svg-optimizer/develop/dev/artifacts/assertions.svg)
![Package size](https://raw.githubusercontent.com/MathiasReker/php-svg-optimizer/develop/dev/artifacts/package-size.svg)
[![Contributors](https://img.shields.io/github/contributors/MathiasReker/php-svg-optimizer.svg)](https://github.com/MathiasReker/php-svg-optimizer/graphs/contributors)
[![Forks](https://img.shields.io/github/forks/MathiasReker/php-svg-optimizer.svg?style=flat)](https://github.com/MathiasReker/php-svg-optimizer/network/members)
[![Stargazers](https://img.shields.io/github/stars/MathiasReker/php-svg-optimizer.svg?style=flat)](https://github.com/MathiasReker/php-svg-optimizer/stargazers)
[![Issues](https://img.shields.io/github/issues/MathiasReker/php-svg-optimizer.svg)](https://github.com/MathiasReker/php-svg-optimizer/issues)
[![MIT License](https://img.shields.io/github/license/MathiasReker/php-svg-optimizer.svg)](https://github.com/MathiasReker/php-svg-optimizer/blob/develop/LICENSE)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat&logo=php&logoColor=white)

`php-svg-optimizer` is a PHP library and command line tool that **optimizes, minifies and sanitizes** SVG files.
It applies small, independent rules to an SVG. The result renders the same as the original, but it is smaller, cleaner
and, with the `security` rule set, free of known active content.

- 48 rules: 44 in six [rule sets](#rule-sets), and four that you enable one by one.
- Rules that are not [risky](#risky-rules) are lossless: no number is rounded, and no id, link or animation that can be
  reached from inside or outside the document is removed.
- Every rule is idempotent: optimizing an optimized SVG changes nothing.
- An immutable builder for PHP and a command line tool for build scripts and CI.

## Requirements

| Version | PHP  | Documentation                                                      |
|---------|------|--------------------------------------------------------------------|
| ^10.0   | ^8.3 | [current](https://github.com/MathiasReker/php-svg-optimizer)       |
| ^9.0    | ^8.3 | [v9](https://github.com/MathiasReker/php-svg-optimizer/tree/9.0.0) |
| ^8.7    | ^8.3 | [v8](https://github.com/MathiasReker/php-svg-optimizer/tree/8.7.1) |

- `ext-dom`: XML handling.
- `ext-libxml`: XML error handling.
- `ext-mbstring`: multibyte string handling.

## Installation

```bash
composer require mathiasreker/php-svg-optimizer
```

![Demo GIF](dev/artifacts/demo.gif)

Upgrading from 9.x? Read [UPGRADE-10.0.md](UPGRADE-10.0.md). It lists every renamed, merged, split and removed rule,
class, method, option and configuration key.

## Quick start

### Library

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use MathiasReker\PhpSvgOptimizer\SvgOptimizer;

$result = (new SvgOptimizer())
    ->withDefaultRules()
    ->optimizeFile('logo.svg');

$result->saveToFile('optimized/logo.svg');

echo $result->getContent(), \PHP_EOL;
```

```text
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><title>Logo</title><path d="M2 2h20v20h-20z" data-name="logo" fill="red"/></svg>
```

`withDefaultRules()` selects the [default rules](#the-default-rules). Without a selected rule, the SVG is only parsed
and written again.

### Command line

```text
$ vendor/bin/svg-optimizer icons
icons/logo.svg (20.87%)
icons/nested/arrow.svg (9.87%)

Summary:
  Files:             2
  Original size:     358 B
  Optimized size:    300 B
  Saved:             58 B (16.20%)
  Optimization time: 0.0566 s
```

Every SVG file in the directory is optimized in place. Pass `--dry-run` to see the savings without writing, and
`--check` to fail when a file would change.

## Library

All public classes are in the namespace `MathiasReker\PhpSvgOptimizer`. Everything else is internal and can change in
any release.

| Class                                | Purpose                                                                         |
|--------------------------------------|---------------------------------------------------------------------------------|
| `SvgOptimizer`                       | Immutable builder. Selects rules and optimizes files and strings.               |
| `OptimizationResult`                 | The optimized SVG, `saveToFile()` and the `Metrics`.                            |
| `Metrics`                            | Sizes in bytes, the saved percentage and the duration in seconds.               |
| `Rule`                               | Enum of every rule. The value is the name used in configuration files.          |
| `RuleSet`                            | Enum of the six rule sets.                                                      |
| `Exception\ExceptionInterface`       | Implemented by every exception of the library.                                  |

The methods do not accept named arguments.

### SvgOptimizer

| Method                                                     | Purpose                                                                  |
|------------------------------------------------------------|--------------------------------------------------------------------------|
| `withRules(Rule ...$rules)`                                | Select rules.                                                            |
| `withoutRules(Rule ...$rules)`                             | Leave rules out, even if a set or `withRules()` selects them.            |
| `withRuleSets(RuleSet ...$ruleSets)`                       | Select every rule of the sets.                                           |
| `withDefaultRules()`                                       | Select the [default rules](#the-default-rules).                          |
| `withRiskyRulesAllowed(bool $allowed = true)`              | Run the selected [risky rules](#risky-rules).                            |
| `optimizeFile(string $path): OptimizationResult`           | Optimize a file. It does not change the file.                            |
| `optimizeString(string $svg): OptimizationResult`          | Optimize a string.                                                       |

Every `with*` method returns a new instance and leaves the original unchanged, so a configured optimizer can be kept
and reused for any number of SVGs.

### Optimizing a file or a string

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use MathiasReker\PhpSvgOptimizer\SvgOptimizer;

$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" fill="#ff0000"/></svg>';

$result = (new SvgOptimizer())
    ->withDefaultRules()
    ->optimizeString($svg);

$metrics = $result->getMetrics();

echo $result->getContent(), \PHP_EOL;
echo 'Original size: ', $metrics->getOriginalSizeInBytes(), ' bytes', \PHP_EOL;
echo 'Optimized size: ', $metrics->getOptimizedSizeInBytes(), ' bytes', \PHP_EOL;
echo 'Saved: ', $metrics->getSavedSizeInBytes(), ' bytes', \PHP_EOL;
echo 'Saved: ', round($metrics->getSavedPercentage(), 2), ' %', \PHP_EOL;
echo 'Has savings: ', $metrics->hasSavings() ? 'yes' : 'no', \PHP_EOL;
echo 'Duration: ', round($metrics->getDurationInSeconds(), 4), ' seconds', \PHP_EOL;
```

```text
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M2 2h20v20h-20z" fill="red"/></svg>
Original size: 123 bytes
Optimized size: 104 bytes
Saved: 19 bytes
Saved: 15.45 %
Has savings: yes
Duration: 0.0516 seconds
```

`OptimizationResult` has three methods:

- `getContent(): string` returns the optimized SVG.
- `saveToFile(string $path): void` writes it to a file and creates missing directories. It throws an `IOException` if
  the file cannot be written.
- `getMetrics(): Metrics` returns the numbers below.

| `Metrics` method                  | Returns                                                                                    |
|-----------------------------------|--------------------------------------------------------------------------------------------|
| `getOriginalSizeInBytes(): int`   | The size of the input.                                                                     |
| `getOptimizedSizeInBytes(): int`  | The size of the output.                                                                    |
| `getSavedSizeInBytes(): int`      | The original size minus the optimized size. Negative if the output is larger.              |
| `getSavedPercentage(): float`     | The saved size as a percentage of the original size. Negative if the output is larger.     |
| `getDurationInSeconds(): float`   | The time the optimization took.                                                            |
| `hasSavings(): bool`              | `true` if the output is smaller than the input.                                            |

The output can be larger than the input. For example, an internal entity that is used many times is replaced by its
value, and the optimizer never keeps a DTD.

Whatever rules are selected, the output is written by the serializer of the library: the XML declaration and the
doctype are not written, whitespace between elements is removed, runs of whitespace in text are collapsed to one
space, empty elements are self-closing, and entity references are replaced by their value.

### Selecting rules

The selected rules are the rules of the default set (if `withDefaultRules()` is called), plus the rules of the sets
from `withRuleSets()`, plus the rules from `withRules()`, minus the rules from `withoutRules()`. Calling a method twice
adds to what the first call selected. A rule that is selected more than once runs once.

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use MathiasReker\PhpSvgOptimizer\Rule;
use MathiasReker\PhpSvgOptimizer\SvgOptimizer;

$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><!-- icon --><rect width="20" height="20" fill="#ff0000"/><title>Square</title></svg>';

$result = (new SvgOptimizer())
    ->withRules(Rule::RemoveComments, Rule::MinifyColors)
    ->optimizeString($svg);

echo $result->getContent(), \PHP_EOL;
```

```text
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="20" height="20" fill="red"/><title>Square</title></svg>
```

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use MathiasReker\PhpSvgOptimizer\Rule;
use MathiasReker\PhpSvgOptimizer\RuleSet;
use MathiasReker\PhpSvgOptimizer\SvgOptimizer;

$result = (new SvgOptimizer())
    ->withRuleSets(RuleSet::Security, RuleSet::Minification)
    ->withRules(Rule::RemoveTitles)
    ->withoutRules(Rule::SortAttributes)
    ->optimizeFile('logo.svg');

echo $result->getContent(), \PHP_EOL;
```

```text
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><!-- Logo --><rect x="2" y="2" width="20" height="20" fill="#ff0000" data-name="logo"/></svg>
```

`withoutRules()` always wins: a rule that is left out does not run, however it was selected.

Rules always run in the same order, whatever order they are selected in. Untrusted content is sanitized first, styles
are turned into attributes before attributes are cleaned up, and values are minified before shapes are converted, so
optimizing an optimized file again leaves it unchanged.

#### The default rules

`withDefaultRules()` selects the rule sets `standards`, `cleanup`, `conversion`, `structure` and `minification`. It
leaves out:

- the `security` set. The default rules do not sanitize an SVG.
  See [Sanitizing untrusted SVGs](#sanitizing-untrusted-svgs).
- the rules that are in no set: `removeTitles`, `removeDescriptions`, `removeAriaAttributes` and
  `scopeIdsAndClasses`. The first three remove accessibility information, and the last one renames ids and class
  names.
- the [risky rules](#risky-rules), unless they are allowed.

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use MathiasReker\PhpSvgOptimizer\Rule;
use MathiasReker\PhpSvgOptimizer\RuleSet;
use MathiasReker\PhpSvgOptimizer\SvgOptimizer;

$result = (new SvgOptimizer())
    ->withDefaultRules()
    ->withRuleSets(RuleSet::Security)
    ->withoutRules(Rule::RemoveComments)
    ->optimizeFile('logo.svg');

echo $result->getContent(), \PHP_EOL;
```

```text
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><!-- Logo --><title>Logo</title><path d="M2 2h20v20h-20z" data-name="logo" fill="red"/></svg>
```

### Risky rules

A rule is risky if it relies on how the SVG is used, which the optimizer cannot check: whether CSS, JavaScript or
another document refers to an id, a class, a `data-*` attribute or the size of the image. Each risky rule says in its
section under **Why it is risky** what it can break.

| Rule                              | What it can break                                                                                                      |
|-----------------------------------|------------------------------------------------------------------------------------------------------------------------|
| `convertOneStopGradientsToColors` | The gradient and its id are removed. CSS, JavaScript or a `file.svg#id` reference that uses the gradient breaks.       |
| `fixAttributeNames`               | A misspelled attribute is ignored by browsers. Renaming it makes it take effect and changes the rendering.             |
| `mergeDuplicateGradients`         | The duplicate gradients and their ids are removed. CSS, JavaScript or a `file.svg#id` reference to one of them breaks. |
| `removeDataAttributes`            | Scripts, page CSS, analytics and test tools may read `data-*` attributes.                                              |
| `removeUnusedDefinitions`         | References from outside the document cannot be seen. A definition that only CSS or another document uses is removed.   |
| `removeUnusedIds`                 | CSS, JavaScript, tests and `file.svg#id` links may use an id that nothing inside the SVG references.                   |
| `removeWidthHeightAttributes`     | The SVG loses its intrinsic size and only keeps its aspect ratio. Layouts that rely on the fixed size change.          |
| `scopeIdsAndClasses`              | Ids and class names change. Page CSS, JavaScript, tests and references from other SVGs no longer match.                |

Risky rules only run after `withRiskyRulesAllowed()`. Their treatment depends on how they were selected:

- A risky rule that a **set** or `withDefaultRules()` selects is skipped without an error unless risky rules are
  allowed. The other rules of the set run.
- A risky rule that `withRules()` selects throws a `RiskyRulesNotAllowedException` when the SVG is optimized, unless
  risky rules are allowed. The exception lists the rules.

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Rule;
use MathiasReker\PhpSvgOptimizer\SvgOptimizer;

$optimizer = (new SvgOptimizer())->withDefaultRules();

echo $optimizer->optimizeFile('logo.svg')->getContent(), \PHP_EOL;

echo $optimizer->withRiskyRulesAllowed()->optimizeFile('logo.svg')->getContent(), \PHP_EOL;

try {
    (new SvgOptimizer())->withRules(Rule::RemoveDataAttributes)->optimizeFile('logo.svg');
} catch (RiskyRulesNotAllowedException $exception) {
    echo $exception->getMessage(), \PHP_EOL;
}
```

```text
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><title>Logo</title><path d="M2 2h20v20h-20z" data-name="logo" fill="red"/></svg>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><title>Logo</title><path d="M2 2h20v20h-20z" fill="red"/></svg>
Risky rules are not allowed: removeDataAttributes. Allow risky rules to use them.
```

The first output keeps `data-name`, because `removeDataAttributes` is risky and was selected by the cleanup set. The
second removes it, and also removes the width and height of the root element.

### Exceptions

Every exception of the library implements `MathiasReker\PhpSvgOptimizer\Exception\ExceptionInterface`.

```text
ExceptionInterface
├── InvalidSvgException           (RuntimeException)
├── IOException                   (RuntimeException)
│   └── FileNotFoundException
└── RiskyRulesNotAllowedException (LogicException)
```

| Exception                       | Thrown when                                                                                                |
|---------------------------------|------------------------------------------------------------------------------------------------------------|
| `InvalidSvgException`           | The input is not a well-formed SVG document. `getPrevious()` says why.                                     |
| `FileNotFoundException`         | `optimizeFile()` gets a path that does not exist.                                                          |
| `IOException`                   | A path is empty, is a directory, cannot be read or cannot be written.                                      |
| `RiskyRulesNotAllowedException` | `withRules()` selects a risky rule and risky rules are not allowed. `getRules()` returns the rules.        |

Catch `ExceptionInterface` for the errors that depend on the input. `RiskyRulesNotAllowedException` is a mistake in
the rule selection, so fix the selection instead of catching it.

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use MathiasReker\PhpSvgOptimizer\Exception\ExceptionInterface;
use MathiasReker\PhpSvgOptimizer\Exception\FileNotFoundException;
use MathiasReker\PhpSvgOptimizer\Exception\InvalidSvgException;
use MathiasReker\PhpSvgOptimizer\Exception\IOException;
use MathiasReker\PhpSvgOptimizer\SvgOptimizer;

$optimizer = (new SvgOptimizer())->withDefaultRules();

try {
    $optimizer->optimizeFile('logo.svg')->saveToFile('notes.txt/logo.svg');
} catch (IOException $exception) {
    echo $exception::class, ': ', $exception->getMessage(), \PHP_EOL;
}

foreach (['missing.svg', 'broken.svg'] as $path) {
    try {
        $optimizer->optimizeFile($path);
    } catch (FileNotFoundException $exception) {
        echo $exception::class, ': ', $exception->getMessage(), \PHP_EOL;
    } catch (InvalidSvgException $exception) {
        echo $exception::class, ': ', $exception->getMessage(), \PHP_EOL;
        echo 'Reason: ', $exception->getPrevious()?->getMessage(), \PHP_EOL;
    } catch (ExceptionInterface $exception) {
        echo 'Any other exception of the library: ', $exception->getMessage(), \PHP_EOL;
    }
}
```

```text
MathiasReker\PhpSvgOptimizer\Exception\IOException: Failed to create directory for output file: notes.txt/logo.svg
MathiasReker\PhpSvgOptimizer\Exception\FileNotFoundException: Input file does not exist: missing.svg
MathiasReker\PhpSvgOptimizer\Exception\InvalidSvgException: The file does not appear to be a valid SVG file.
Reason: Failed to load DOMDocument: Opening and ending tag mismatch: rect line 1 and svg on line 1, column 53.
```

Parsing is hardened for every rule set. External entities and DTDs are never loaded. A document whose internal
entities would expand to more than 512 KiB, or to more than five times the size of the document if that is larger, is
rejected on every libxml version, with the reason "Entity expansion exceeds the allowed size." in `getPrevious()`.
Elements nested more than 256 levels deep below the root element are rejected too.

## Command line

```text
vendor/bin/svg-optimizer [options] [--] <path>...
```

A path is an SVG file or a directory. Directories are searched recursively for files with the extension `.svg`, in name
order. While a directory is searched, `.git`, `.hg`, `.svn`, `node_modules` and `vendor` directories and symbolic links
to files or directories are skipped. A path that you name is always used, even a symbolic link to a file, so
`node_modules/icons/a.svg` is optimized when you name it. The paths are printed as you wrote them, followed by what
leads to each file. Options can be placed anywhere before `--`, and `--config=value` and `--config value` are the same.

| Option                      | Short | Purpose                                                                                          |
|-----------------------------|-------|--------------------------------------------------------------------------------------------------|
| `--config=<file\|json>`     | `-c`  | Configuration file or inline JSON. Defaults to `svg-optimizer.json` in the working directory.    |
| `--sets=<sets>`             | `-s`  | Comma-separated rule sets: `default`, `security`, `standards`, `cleanup`, `conversion`, `structure`, `minification`. Can be repeated. |
| `--rules=<rules>`           | `-r`  | Comma-separated rules. Can be repeated. See `--list-rules`.                                      |
| `--without-rules=<rules>`   |       | Comma-separated rules to leave out, even if a set or `--rules` selects them. Can be repeated.    |
| `--allow-risky`             |       | Allow the risky rules that `--rules`, `--sets` or the configuration select.                      |
| `--dry-run`                 | `-d`  | Report the savings without writing files.                                                        |
| `--check`                   |       | Write nothing and exit with code 3 if a file would change.                                       |
| `--list-rules`              |       | List the rules with their set, whether they are risky, and what they do.                         |
| `--quiet`                   | `-q`  | Suppress all output except errors.                                                               |
| `--version`                 | `-V`  | Show the version.                                                                                |
| `--help`                    | `-h`  | Show the help.                                                                                   |
| `--ansi`, `--no-ansi`       |       | Force or disable ANSI colors. Without either, colors are used on a terminal.                     |

Errors are written to stderr, also with `--quiet`.

### Selecting rules

The command line follows the same [selection rules](#selecting-rules) as the library, with these additions:

- Without any selected set or rule, the `default` set is used. `--sets=default` selects it explicitly and can be
  combined with other sets.
- `--sets`, `--rules` and the configuration file add up. `--without-rules` and the rules that the configuration turns
  off are subtracted.
- The command line overrides the configuration file for the same item. A rule that the file turns off with `false`
  stays off for everything that the file selects with its own `sets` and `rules`, but a rule that `--rules` names, or
  that a set of `--sets` contains, runs regardless of the `false` in the file. `--without-rules` always wins over
  everything.
- Risky rules from sets are skipped without an error. A risky rule that `--rules` or the configuration names makes the
  command fail with exit code 2 unless `--allow-risky` or `"allowRisky": true` is given.

```text
$ vendor/bin/svg-optimizer --dry-run icons logo.svg
icons/logo.svg (20.87%)
icons/nested/arrow.svg (9.87%)
logo.svg (20.87%)

Summary:
  Files:             3
  Original size:     564 B
  Optimized size:    463 B
  Saved:             101 B (17.91%)
  Optimization time: 0.0582 s

$ vendor/bin/svg-optimizer --sets=security,minification icons
icons/logo.svg (5.34%)
icons/nested/arrow.svg (7.89%)

Summary:
  Files:             2
  Original size:     358 B
  Optimized size:    335 B
  Saved:             23 B (6.42%)
  Optimization time: 0.0247 s

$ vendor/bin/svg-optimizer --rules=removeComments,minifyColors logo.svg
logo.svg (13.59%)

Summary:
  Files:             1
  Original size:     206 B
  Optimized size:    178 B
  Saved:             28 B (13.59%)
  Optimization time: 0.0096 s

$ vendor/bin/svg-optimizer --allow-risky --without-rules=removeUnusedIds icons
icons/logo.svg (36.92%)
icons/nested/arrow.svg (2.14%)

Summary:
  Files:             2
  Original size:     335 B
  Optimized size:    260 B
  Saved:             75 B (22.39%)
  Optimization time: 0.0603 s
```

### Exit codes

| Code | Meaning                                                                                  |
|------|------------------------------------------------------------------------------------------|
| 0    | Every file was optimized, or `--check` found nothing to change.                          |
| 1    | A file failed: it does not exist, cannot be read or written, or is not a valid SVG.      |
| 2    | Invalid usage or configuration, including a risky rule that is not allowed.              |
| 3    | `--check` found at least one file that would change.                                     |

```text
$ vendor/bin/svg-optimizer --rules=removeDataAttributes logo.svg
Error: Risky rules need --allow-risky, or "allowRisky" in the configuration: removeDataAttributes.
# exit code 2

$ vendor/bin/svg-optimizer --rules=removeComment logo.svg
Error: Unknown rule "removeComment". Did you mean "removeComments"? Run svg-optimizer --list-rules to see the rules.
# exit code 2

$ vendor/bin/svg-optimizer missing.svg
Error: "missing.svg" does not exist.
# exit code 1

$ vendor/bin/svg-optimizer broken
Error: "broken/broken.svg" is not a valid SVG file: Failed to load DOMDocument: Opening and ending tag mismatch: rect line 1 and svg on line 1, column 53.
# exit code 1
```

### What happens to a file

- A file is only written if the content changes. Files are written atomically: the content goes to a temporary file in
  the same directory, which then replaces the original. A symbolic link keeps pointing at its target.
- A file that would get larger is skipped and reported as `skipped, no gain`.
- `--dry-run` and `--check` never write.

```text
$ vendor/bin/svg-optimizer grow.svg
grow.svg (skipped, no gain)

Summary:
  Files:             1
  Original size:     190 B
  Optimized size:    190 B
  Saved:             0 B (0.00%)
  Optimization time: 0.0455 s
```

### Checking in CI

`--check` writes nothing and fails with exit code 3 when a file would change, so a pipeline can require that every SVG
in the repository is optimized.

```text
$ vendor/bin/svg-optimizer --check icons
icons/logo.svg (20.87%)
icons/nested/arrow.svg (9.87%)

Summary:
  Files:             2
  Original size:     358 B
  Optimized size:    300 B
  Saved:             58 B (16.20%)
  Optimization time: 0.0486 s
Error: 2 files would change.
# exit code 3

$ vendor/bin/svg-optimizer icons
icons/logo.svg (20.87%)
icons/nested/arrow.svg (9.87%)

Summary:
  Files:             2
  Original size:     358 B
  Optimized size:    300 B
  Saved:             58 B (16.20%)
  Optimization time: 0.0703 s

$ vendor/bin/svg-optimizer --check icons
icons/logo.svg (0.00%)
icons/nested/arrow.svg (0.00%)

Summary:
  Files:             2
  Original size:     300 B
  Optimized size:    300 B
  Saved:             0 B (0.00%)
  Optimization time: 0.0523 s
```

```yaml
name: Check SVGs

on: [push, pull_request]

jobs:
  svg:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v7
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.5'
      - run: composer install --no-dev --optimize-autoloader --no-interaction --no-progress
      - run: vendor/bin/svg-optimizer --check --quiet icons
```

### Listing the rules

```text
$ vendor/bin/svg-optimizer --list-rules
Rule                             Set           Risky  Description
removeUnsafeMarkup               security      no     Remove processing instructions and unsafe comments.
removeScripts                    security      no     Remove scripts, event handlers and animations that rewrite links or event handlers.
removeForeignContent             security      no     Remove foreign, embedding and HTML breakout elements and non-canonical names.
removeUnsafeUrls                 security      no     Remove attributes with URLs that can run script or load external content.
removeUnsafeStyles               security      no     Remove unsafe statements and declarations from style sheets and style attributes.
removeUseBombs                   security      no     Remove use elements that form cycles or render too many instances.
fixAttributeNames                standards     yes    Fix the letter case of attribute names.
...
```

## Configuration file

The command line reads `svg-optimizer.json` from the working directory, or the file or inline JSON given with
`--config`. All keys are optional.

```json
{
    "$schema": "https://raw.githubusercontent.com/MathiasReker/php-svg-optimizer/develop/resources/svg-optimizer.schema.json",
    "sets": ["default"],
    "rules": {
        "removeTitles": true,
        "sortAttributes": false
    },
    "allowRisky": false
}
```

| Key          | Value                                                                                                          |
|--------------|----------------------------------------------------------------------------------------------------------------|
| `$schema`    | The JSON schema of the file. Editors use it for completion and validation.                                     |
| `sets`       | A list of rule set names. `default` selects the default rules.                                                 |
| `rules`      | An object of rule names. `true` selects a rule, `false` leaves it out even if a set of the file selects it.   |
| `allowRisky` | `true` allows the risky rules that are selected. The default is `false`.                                       |

The schema is generated from the `Rule` enum and ships with the package. Use the URL above, or the path
`vendor/mathiasreker/php-svg-optimizer/resources/svg-optimizer.schema.json` in your project. In the configuration above,
the default rules are selected, `removeTitles` is added, `sortAttributes` is left out, and risky rules stay off.

The command line overrides the file. With `{"rules": {"removeScripts": false}}` in `svg-optimizer.json`:

```text
$ vendor/bin/svg-optimizer icons
$ vendor/bin/svg-optimizer --sets=security icons
$ vendor/bin/svg-optimizer --sets=security --without-rules=removeScripts icons
```

The first command and the third leave `<script>` in the files. The second runs `removeScripts`, because
`--sets=security` selects it on the command line. `--rules=removeScripts` turns it on in the same way, and
`--without-rules` always wins.

A configuration with `rules` or `sets` and no `default` runs only what it names. A configuration with only
`allowRisky`, or only `false` rules, runs the default rules. Unknown keys, unknown rules and unknown sets are errors
and come with a suggestion or the valid names. The flat format of 9.x is reported with a pointer to the
[upgrade guide](UPGRADE-10.0.md).

```text
$ vendor/bin/svg-optimizer icons
icons/logo.svg (30.10%)
icons/nested/arrow.svg (9.87%)

Summary:
  Files:             2
  Original size:     358 B
  Optimized size:    281 B
  Saved:             77 B (21.51%)
  Optimization time: 0.0534 s

$ vendor/bin/svg-optimizer --config='{"rules":{"minifyColors":true}}' logo.svg
logo.svg (7.28%)

Summary:
  Files:             1
  Original size:     206 B
  Optimized size:    191 B
  Saved:             15 B (7.28%)
  Optimization time: 0.0094 s
```

A configuration file in the 9.x format, with the rule names at the top level, fails with exit code 2:

```text
$ vendor/bin/svg-optimizer --dry-run logo.svg
Error: Invalid configuration file "svg-optimizer.json": The 9.x format is no longer supported: "removeComments" belongs below "rules". See UPGRADE-10.0.md.
# exit code 2
```

## Rule sets

A rule set is a group of rules with the same purpose. Each rule is in at most one set.

| Set | `RuleSet` case | Purpose | Rules |
| --- | --- | --- | --- |
| `security` | `RuleSet::Security` | Remove scripts, event handlers, unsafe links and other active content from untrusted SVGs. | `removeUnsafeMarkup`, `removeScripts`, `removeForeignContent`, `removeUnsafeUrls`, `removeUnsafeStyles`, `removeUseBombs` |
| `standards` | `RuleSet::Standards` | Bring the markup in line with the SVG specification. | `fixAttributeNames` (risky), `migrateToSvg2`, `removeNonStandardElements`, `removeNonStandardAttributes` |
| `cleanup` | `RuleSet::Cleanup` | Remove editor data, comments, unused definitions and content that has no effect. | `removeComments`, `removeMetadata`, `removeEditorMarkup`, `removeUnusedIds` (risky), `removeDataAttributes` (risky), `removeInvisibleCharacters`, `removeDefaultAttributes`, `removeWidthHeightAttributes` (risky), `removeEmptyAttributes`, `removeIneffectiveAttributes`, `removeHiddenElements`, `removeIneffectiveDefinitions`, `removeDuplicateElements`, `removeEmptyElements`, `removeUnusedDefinitions` (risky), `removeUnusedNamespaces` |
| `conversion` | `RuleSet::Conversion` | Convert styles, colors, gradients and shapes into shorter equivalents. | `convertCssClassesToAttributes`, `convertInlineStylesToAttributes`, `minifyColors`, `convertOneStopGradientsToColors` (risky), `convertEllipsesToCircles`, `convertShapesToPaths` |
| `structure` | `RuleSet::Structure` | Restructure styles, gradients and groups. | `mergeStyleSheets`, `mergeDuplicateGradients` (risky), `flattenGroups`, `moveGroupTransformsToChildren`, `moveCommonAttributesToGroups` |
| `minification` | `RuleSet::Minification` | Write numbers, paths, styles and whitespace in their shortest form and order the markup. | `minifyStyleSheets`, `minifyNumericValues`, `minifyTransforms`, `minifyPathData`, `minifyAttributeWhitespace`, `sortDefinitions`, `sortAttributes` |

The default rules are all sets except `security`. The rules `removeTitles`, `removeDescriptions`,
`removeAriaAttributes` and `scopeIdsAndClasses` are in no set. They have to be selected one by one.

Sets can be used with `withRuleSets()`, with `--sets` and with the `sets` key of the configuration file.

### Reading rules and sets in code

`Rule` and `RuleSet` are enums, so `Rule::cases()` and `RuleSet::cases()` list them all.

| Method                              | Returns                                                                                   |
| ----------------------------------- | ----------------------------------------------------------------------------------------- |
| `Rule::isRisky()`                   | `true` if the rule is [risky](#risky-rules).                                              |
| `Rule::getRuleSet()`                | The `RuleSet` of the rule, or `null` for a rule that is in no set.                        |
| `Rule::getDescription()`            | One sentence that says what the rule does.                                                |
| `RuleSet::getRules()`               | The rules of the set, in the order in which they run.                                     |
| `RuleSet::contains(Rule $rule)`     | `true` if the rule is in the set.                                                         |
| `RuleSet::defaults()`               | The sets that `withDefaultRules()` selects: every set except `security`.                  |

```php
$rule = Rule::RemoveUnusedIds;

echo $rule->value, ' (risky: ', var_export($rule->isRisky(), true), ', set: ', $rule->getRuleSet()?->value, ')', \PHP_EOL;
echo $rule->getDescription(), \PHP_EOL;
```

```text
removeUnusedIds (risky: true, set: cleanup)
Remove ids that are not referenced.
```

## Sanitizing untrusted SVGs

An SVG can contain scripts, event handlers and references to other resources, so an SVG that comes from a user can run
code in the browser of whoever views it. The `security` set removes the known kinds of active content. The default rules
do not include it, so select it explicitly.

| Rule                  | Removes                                                                                             |
|-----------------------|-----------------------------------------------------------------------------------------------------|
| `removeScripts`       | Scripts, event handlers, and animations that rewrite links or event handlers.                       |
| `removeUnsafeUrls`    | Attributes with URLs that run script or load external content.                                      |
| `removeForeignContent` | HTML, embedded documents, and names that an HTML parser reads differently from an XML parser.       |
| `removeUnsafeStyles`  | Unsafe statements and declarations in style sheets and `style` attributes.                          |
| `removeUseBombs`      | `<use>` cycles and `<use>` elements that render too many instances.                                 |
| `removeUnsafeMarkup`  | Processing instructions, and comments that turn into markup when the SVG is inlined in HTML.        |

The set removes content on purpose, also when it is visible: interactive SVGs lose their scripts, images and links that
load external resources are removed, and so are `<foreignObject>` elements with HTML content. For a stricter policy,
add `removeNonStandardElements` and `removeNonStandardAttributes`, which are in the `standards` set.

Untrusted input before:

```xml
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 24 24" onload="alert(1)">
  <script>alert(document.domain)</script>
  <a xlink:href="javascript:alert(1)"><rect width="10" height="10" fill="red"/></a>
  <foreignObject width="10" height="10"><iframe xmlns="http://www.w3.org/1999/xhtml" src="https://example.com"/></foreignObject>
  <style>@import url(https://example.com/a.css); .a { fill: blue }</style>
  <circle class="a" cx="18" cy="18" r="4"/>
</svg>
```

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use MathiasReker\PhpSvgOptimizer\RuleSet;
use MathiasReker\PhpSvgOptimizer\SvgOptimizer;

$result = (new SvgOptimizer())
    ->withRuleSets(RuleSet::Security)
    ->optimizeFile('untrusted.svg');

echo $result->getContent(), \PHP_EOL;
```

After:

```text
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 24 24"><rect width="10" height="10" fill="red"/><style> .a { fill: blue }</style><circle class="a" cx="18" cy="18" r="4"/></svg>
```

The `security` set removes the kinds of content its rules describe. It is not a proof that an SVG is safe. Combine it
with the other measures your application needs, and see [SECURITY.md](SECURITY.md) to report a bypass.

## Rules

Each section lists the set of the rule, the case of the `Rule` enum, what the rule does and an example. The examples
were produced by running only that rule on the input shown. The serializer always removes the whitespace between
elements, so the outputs have none. Rules that are in no set have to be selected with `withRules()`, `--rules` or the
`rules` key.

Rules that are not marked risky are lossless. The optimized SVG renders the same in current browsers, keeps every id,
link and animation that can be reached from inside or outside the document (sprite sheets keep working), and no number
is rounded. A few rules remove content on purpose, and say so in their description: the security rules
`removeScripts`, `removeUnsafeUrls`, `removeForeignContent`, `removeUnsafeStyles`, `removeUseBombs` and
`removeUnsafeMarkup` remove unsafe content, also when it is visible; and `removeTitles`, `removeDescriptions` and
`removeAriaAttributes` remove accessibility information.

The same holds when the SVG is used from outside: inlined in an HTML page or referenced with
`<use href="file.svg#id">`. The root element then inherits properties such as `fill`, `stroke` or `stroke-width` from
the page or from the `<use>` element, so a rule never assumes their initial values at the root: a value is only treated
as known when an element of the document sets it. `stroke="none"` on a shape is kept unless an ancestor in the document
also sets `stroke="none"`, for example.

### `convertCssClassesToAttributes`

Set: `conversion`. Enum case: `Rule::ConvertCssClassesToAttributes`. Risky: no.

Replaces CSS class-based styling with equivalent inline SVG attributes on each affected element. Declarations that have
to stay CSS, such as `!important` ones, ones that other rules may override, and properties that an element already sets
as an attribute with another value, are kept in the style sheet. Style elements for other media, of another type, with a
`title` (which may make them alternative style sheets) or with content other than text are kept as they are. Only rules
whose selector list consists of class selectors with valid identifiers are converted, and a property that two rules set
on the same element with different values stays CSS. A style sheet is rewritten only when a declaration became an
attribute. The document is left untouched when a style sheet outside the document may apply, or when a script, an event
handler attribute or an animation may change the classes. The result renders the same as a standalone SVG. When the SVG
is inlined in an HTML page, presentation attributes have a lower priority than any CSS rule of the page, while the class
rules they replace may have won over page rules with a lower specificity.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <style>.a{fill:red}</style>
  <rect class="a" width="10" height="10"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10" height="10" fill="red"/></svg>
```

### `convertEllipsesToCircles`

Set: `conversion`. Enum case: `Rule::ConvertEllipsesToCircles`. Risky: no.

Converts `<ellipse>` elements whose `rx` and `ry` are equal into `<circle>` elements with a single `r`. The radius is
copied as written, so no value is recalculated. Ellipses with child elements or a `style` attribute, and documents with
a style sheet, a script or an event handler attribute, are kept.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <ellipse cx="12" cy="12" rx="5" ry="5"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/></svg>
```

### `convertInlineStylesToAttributes`

Set: `conversion`. Enum case: `Rule::ConvertInlineStylesToAttributes`. Risky: no.

Extracts individual style declarations from inline `style` attributes and converts them into their corresponding SVG
presentation attributes. This makes attribute-level optimizations more effective downstream. Declarations that are
important, repeated, overridden by a related property, or for a property that the element already sets as an attribute
with another value stay in the `style` attribute, and documents with a style sheet (a `<style>` element, an
`xml-stylesheet` instruction or a linked style sheet) are left untouched. An element is only changed when its markup
does not grow, unless converting the styles of every element makes the optimized document smaller: the optimizer then
runs the rules that follow on both versions and keeps the smaller one. The declarations that stay are written without
spaces. The result renders the same as a standalone SVG.
When the SVG is inlined in an HTML page, presentation attributes have a lower priority than any CSS rule of the page,
while an inline style wins over page rules that are not `!important`.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <rect width="10" height="10" style="fill:red;stroke:blue"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10" height="10" fill="red" stroke="blue"/></svg>
```

### `convertOneStopGradientsToColors` (**risky**)

Set: `conversion`. Enum case: `Rule::ConvertOneStopGradientsToColors`. Risky: yes.

Replaces a gradient that has a single `<stop>` with that stop's color where it is used as a `fill` or `stroke`, and
removes the gradient. Gradients referenced in any other way or by other documents (see the sprite targets of
`removeUnusedDefinitions`), stops with an opacity, a `style` or a color that is not a plain hex or named color,
gradients with a `style` attribute or a `gradientTransform` other than translations and rotations (a transform that
cannot be inverted paints nothing), and documents with a style sheet, a script or an event handler attribute are left
untouched. A gradient in object bounding box units is only replaced on rects, circles and ellipses, as it is not
rendered on, for example, a straight line.

**Why it is risky:** the gradient and its `id` are removed. CSS, JavaScript or a `file.svg#id` reference in the page
that embeds the SVG that uses the gradient breaks.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <defs>
    <linearGradient id="g"><stop offset="0" stop-color="#336699"/></linearGradient>
  </defs>
  <rect width="10" height="10" fill="url(#g)"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10" height="10" fill="#336699"/></svg>
```

### `convertShapesToPaths`

Set: `conversion`. Enum case: `Rule::ConvertShapesToPaths`. Risky: no.

Converts `<rect>`, `<line>`, `<polyline>` and `<polygon>` elements into `<path>` elements when that makes the file
smaller. The path data reuses the original numbers (a rect is drawn with relative moves), so no coordinate is
recalculated or rounded. Rounded rects, values with units or a trailing decimal point, `points` lists that are invalid
or have an odd number of coordinates, shapes with child elements or a `style` attribute, and documents with a style
sheet, a `<marker>`, a script or an event handler attribute are kept as they are.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <rect x="2" y="3" width="10" height="5"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M2 3h10v5h-10z"/></svg>
```

### `fixAttributeNames` (**risky**)

Set: `standards`. Enum case: `Rule::FixAttributeNames`. Risky: yes.

Normalizes SVG attribute names to their canonical, specification-compliant casing and hyphenation. Attribute names are
matched against a known set of valid SVG attributes and corrected where case differences or missing hyphens occur, and
an attribute is left alone when the element already has the canonical one or already defines its name, such as the SVG 2
`playbackorder` next to the SVG Tiny `playbackOrder`. Event handler attributes in another case, such as `onLoad`, are
kept as they are, since renaming them would make an inert attribute run a script. Foreign content and attributes in a
namespace other than XLink are left untouched.

This rule changes rendering on purpose: browsers ignore a misspelled attribute, and renaming it makes it take effect.
For example, `viewbox` becomes `viewBox` and `Fill` becomes `fill`, so an image that ignored those attributes until now
will be scaled or painted by them.

**Why it is risky:** browsers ignore a misspelled attribute, so renaming it changes rendering: `viewbox` becomes
`viewBox` and the image is scaled, `Fill` becomes `fill` and the shape is painted. An image that looked right only
because those attributes were ignored changes.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewbox="0 0 24 24">
  <rect Fill="red" width="10" height="10"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10" height="10" fill="red"/></svg>
```

### `flattenGroups`

Set: `structure`. Enum case: `Rule::FlattenGroups`. Risky: no.

Removes unnecessary nested `<g>` (group) elements by moving their child elements directly into the parent, together with
the group's inherited presentation attributes and transform. Groups with an `id`, a class, a style or attributes that do
not inherit, groups inside clip paths and `<switch>`, groups whose children use `inherit`, `unset`, `revert`, a custom
property or a font-relative unit, groups whose children override an inherited attribute with a value that the browser
may reject (which would fall back to the group value), and groups whose transform is invalid or cannot be merged are
kept. Documents with a style sheet, a script or an event handler attribute are left untouched. The change is reverted
when it would make the output larger.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <g><g fill="red"><rect width="10" height="10"/></g></g>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10" height="10" fill="red"/></svg>
```

### `mergeDuplicateGradients` (**risky**)

Set: `structure`. Enum case: `Rule::MergeDuplicateGradients`. Risky: yes.

Merges gradients that are exactly identical (same element, same attributes apart from the `id`, same stops, and equal
ancestors) into the first one, and points every `url(#id)`, `href` and `xlink:href` reference at it. Gradients that are
animated, referenced in a way that cannot be rewritten, or that other documents may reference (see the sprite targets of
`removeUnusedDefinitions`), such as the gradients of a resource library, are kept, and documents with a style sheet, a
script or an event attribute are left untouched.

**Why it is risky:** the duplicate gradients and their ids are removed. CSS, JavaScript or a `file.svg#id` reference in
the page that embeds the SVG that uses a removed gradient breaks.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <defs>
    <linearGradient id="a"><stop offset="0" stop-color="red"/><stop offset="1" stop-color="blue"/></linearGradient>
    <linearGradient id="b"><stop offset="0" stop-color="red"/><stop offset="1" stop-color="blue"/></linearGradient>
  </defs>
  <rect width="10" height="10" fill="url(#a)"/>
  <rect y="12" width="10" height="10" fill="url(#b)"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><defs><linearGradient id="a"><stop offset="0" stop-color="red"/><stop offset="1" stop-color="blue"/></linearGradient></defs><rect width="10" height="10" fill="url(#a)"/><rect y="12" width="10" height="10" fill="url(#a)"/></svg>
```

### `mergeStyleSheets`

Set: `structure`. Enum case: `Rule::MergeStyleSheets`. Risky: no.

Merges all `<style>` elements into the first one, keeping the CSS in document order, and removes empty `<style>`
elements. Style elements with a different `media` attribute are wrapped in `@media`. The document is left untouched when
a style element has other attributes, a type other than `text/css`, content other than text, sits in foreign content or
conditional processing, or contains `@import`, `@charset` or `@namespace`.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <style>.a{fill:red}</style>
  <style>.b{fill:blue}</style>
  <rect class="a" width="10" height="10"/>
  <rect class="b" y="12" width="10" height="10"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><style>.a{fill:red}.b{fill:blue}</style><rect class="a" width="10" height="10"/><rect class="b" y="12" width="10" height="10"/></svg>
```

### `migrateToSvg2`

Set: `standards`. Enum case: `Rule::MigrateToSvg2`. Risky: no.

Migrates SVG 1.1 and SVG Tiny 1.2 markup to SVG 2 without changing how current browsers render it. `xlink:href` becomes
`href`, also in animations, and `xlink:show="new"` becomes `target="_blank"`. Elements and attributes that SVG 2 dropped
and browsers ignore are removed: SVG fonts, `tref`, `altGlyph`, `animateColor`, `cursor`, `color-profile`, the SVG Tiny
elements and attributes, `xml:id`, and properties such as `version`, `baseProfile`, `requiredFeatures`, `kerning` and
`enable-background`, also from inline styles. References to dropped elements fall back to their fallback paint, and the
SVG Tiny timeline attributes are renamed to their SVG 2 forms. Unused `xlink` and XML Events namespace declarations are
removed. Foreign content and anything author CSS may select are left untouched. Dropped elements that have an `id`, that
a `<switch>` may render, or that contain an element with an `id`, a style sheet, a script or an animation, are kept, and
no element is removed when a style sheet uses sibling combinators or structural pseudo-classes.

It also removes the attributes that SVG 2 deprecated and browsers ignore: `version`, `baseProfile`, `contentScriptType`,
`contentStyleType`, `externalResourcesRequired`, `requiredFeatures`, `zoomAndPan`, `viewTarget`,
`glyph-orientation-horizontal`, `glyph-orientation-vertical` and `xml:base`, and the SVG 1.1 view attributes.
`xlink:title` on a link becomes a `<title>` child unless the link already has one or a style sheet uses sibling
combinators or structural pseudo-classes. `xlink:title` on other elements, which browsers ignore, and the other XLink
attributes are removed, and an unused `xlink` namespace declaration is removed. `xml:lang` becomes `lang`. Foreign
content, attributes that a style sheet may select, and documents with an external style sheet are left untouched.

And it removes the `enable-background` attribute and inline style declaration, which editors such as Illustrator add to
almost every file and current browsers ignore. Foreign content, an attribute or `style` that a style sheet may select,
and documents with an external style sheet are left untouched.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 24 24" enable-background="new 0 0 24 24">
  <defs><path id="p" d="M0 0h10v10z"/></defs>
  <use xlink:href="#p"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><defs><path id="p" d="M0 0h10v10z"/></defs><use href="#p"/></svg>
```

### `minifyAttributeWhitespace`

Set: `minification`. Enum case: `Rule::MinifyAttributeWhitespace`. Risky: no.

Collapses runs of whitespace in attribute values to a single space and trims them, and removes the whitespace around
`;`, `:` and `,` in inline styles, outside quoted strings. Values whose whitespace is content are left as they are:
`id`, `href`, `download`, `target`, `title`, `attributeName`, `unicode`, `lang`, the filter primitive names `in`, `in2`
and `result`, `data-*` and event handler attributes, and inline styles with a CSS escape. Foreign content is left
untouched, and so is the document when a style sheet may select elements by an attribute value.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <rect width="10" height="10" class="  a   b " style="fill : red ; stroke : blue"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10" height="10" class="a b" style="fill:red;stroke:blue"/></svg>
```

### `minifyColors`

Set: `conversion`. Enum case: `Rule::MinifyColors`. Risky: no.

Writes each color in its shortest exact notation. Functional formats such as `rgb()` or `rgba()` become hexadecimal,
`#aabbcc` becomes `#abc`, and a CSS color name and its hexadecimal value are swapped when the other form is shorter (for
example `#ff0000` becomes `red` and `white` becomes `#fff`). `currentColor`, `transparent`, `none` and `url()`
references are left as they are.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <rect width="10" height="10" fill="rgb(255, 0, 0)" stroke="#aabbcc"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10" height="10" fill="red" stroke="#abc"/></svg>
```

### `minifyNumericValues`

Set: `minification`. Enum case: `Rule::MinifyNumericValues`. Risky: no.

Shortens the notation of single-number attribute values such as `opacity`, `stroke-width`, `x` or `font-size`: leading
and trailing zeros and a `+` sign are dropped, and `px` is removed from lengths, as one pixel equals one user unit. `px`
is kept where the attribute is a plain number on that element (such as `dx`/`dy` on `<feOffset>` or `x`/`y` on
`<fePointLight>`), and integer attributes such as `numOctaves` are only shortened when written as plain integers. Only
the notation changes; values are never rounded. Numbers with a trailing decimal point (`10.`), which browsers reject,
are left as they are, and so is the document when a style sheet may select elements by an attribute value.

It also shortens the notation of number lists such as `viewBox`, `points`, `stroke-dasharray`, `keyTimes` and the
`values` of numeric animations. Numbers are shortened without rounding and lists use the fewest separators the attribute
allows. Values with units, percentages, keywords or a number with a trailing decimal point are left untouched, and so is
the document when a style sheet may select elements by an attribute value.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <rect width="10.500" height="010" opacity="0.50" stroke-width="2px" x="+5"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10.5" height="10" opacity=".5" stroke-width="2" x="5"/></svg>
```

### `minifyPathData`

Set: `minification`. Enum case: `Rule::MinifyPathData`. Risky: no.

Rewrites path data (`d`) in its shortest equivalent form: repeated command letters and unneeded separators are dropped,
number literals are shortened, and relative linetos along one axis become `h`/`v`. Coordinates are never recalculated or
rounded, and path data that is not valid is left untouched. The document is left untouched when path data is animated,
as interpolation needs matching commands, or when a style sheet may select elements by an attribute value.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <path d="M 10,10 L 20,10 L 20,20 Z"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M10 10 20 10 20 20z"/></svg>
```

### `minifyStyleSheets`

Set: `minification`. Enum case: `Rule::MinifyStyleSheets`. Risky: no.

Minifies the CSS inside `<style>` elements without restructuring it: comments (except `/*! ... */`) and unnecessary
whitespace are removed, and redundant semicolons are dropped. Strings, `url()` values, escapes and numbers are never
changed. A style sheet that cannot be read with confidence, for example one with an unclosed string or comment, is left
untouched.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <style>
    /* colors */
    .a { fill : red ; }
  </style>
  <rect class="a" width="10" height="10"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><style>.a{fill:red}</style><rect class="a" width="10" height="10"/></svg>
```

### `minifyTransforms`

Set: `minification`. Enum case: `Rule::MinifyTransforms`. Risky: no.

Shortens `transform`, `gradientTransform` and `patternTransform` values without recalculating them: numbers and
separators are written in their shortest form, identity transformations such as `translate(0)`, `scale(1)` and
`rotate(0)` are removed, default arguments are dropped (`translate(10 0)` becomes `translate(10)`), and a matrix that
only translates or scales is written as `translate()` or `scale()`. An identity `gradientTransform` or
`patternTransform` on an element with `href` or `xlink:href` is kept (as `scale(1)` when shorter), since removing it
would inherit the transform of the referenced element. Values that do not parse as a transform list are left untouched,
and so is the document when a style sheet may select elements by an attribute.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <rect width="10" height="10" transform="translate(10 0) scale(1) rotate(0)"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10" height="10" transform="translate(10)"/></svg>
```

### `moveCommonAttributesToGroups`

Set: `structure`. Enum case: `Rule::MoveCommonAttributesToGroups`. Risky: no.

Moves inheritable presentation attributes, such as `fill`, `stroke` or `font-family`, that every element child of a
`<g>` sets to the same value onto the group, so the value is written once. Attributes that are animated, declared in an
inline style, use `inherit` or font-relative units, and children that are referenced by id, are left alone. `transform`
and `color` are never moved. The document is left untouched when it has a style sheet, a script or an event handler
attribute.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <g>
    <rect width="10" height="10" fill="red"/>
    <rect y="12" width="10" height="10" fill="red"/>
  </g>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="red"><rect width="10" height="10"/><rect y="12" width="10" height="10"/></g></svg>
```

### `moveGroupTransformsToChildren`

Set: `structure`. Enum case: `Rule::MoveGroupTransformsToChildren`. Risky: no.

Moves the `transform` of a `<g>` onto its children by writing it in front of each child's own transform, without any
arithmetic, so the group can be flattened afterwards. It only applies when every element child is a shape, `path`,
`text` or `use`, the group has no `id`, `clip-path`, `mask` or `filter`, and both transforms are valid transform lists.
Documents with a style sheet, a script or an event handler attribute are left untouched. The change is reverted when it
would make the output larger.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <g transform="translate(5 5)">
    <rect width="10" height="10" transform="scale(2)"/>
  </g>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g><rect width="10" height="10" transform="translate(5 5) scale(2)"/></g></svg>
```

### `removeAriaAttributes`

Set: none, so select it explicitly. Enum case: `Rule::RemoveAriaAttributes`. Risky: no.

Removes ARIA (`aria-*`) and `role` attributes. Rendering stays the same, but by design the image loses its accessible
role, name and description (such as `role="img"` and `aria-label`), so screen readers no longer announce it as before.
Only use this rule for decorative SVGs that are hidden from assistive technologies or labelled by the page that embeds
them. Attributes used in an attribute selector of a style sheet are kept, and documents with an external style sheet are
left untouched.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <rect role="img" aria-label="Square" width="10" height="10"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10" height="10"/></svg>
```

### `removeComments`

Set: `cleanup`. Enum case: `Rule::RemoveComments`. Risky: no.

Deletes all non-essential XML comments from the SVG document, excluding legally required comments if applicable. This
eliminates developer notes and editor artifacts that do not affect rendering, reducing file size.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <!-- made by hand -->
  <rect width="10" height="10"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10" height="10"/></svg>
```

### `removeDataAttributes` (**risky**)

Set: `cleanup`. Enum case: `Rule::RemoveDataAttributes`. Risky: yes.

Removes `data-*` attributes from elements. These attributes are often used for application-specific metadata or
scripting hooks, and removing them can significantly reduce file size. Attributes that a style sheet of the document
names, for example in an attribute selector such as `[data-state="open"]` or in `attr()`, are kept.

**Why it is risky:** scripts, CSS in the page that embeds the SVG, analytics, testing tools (`data-testid`) and
design-tool round trips may read `data-*` attributes. Those break when the attributes are gone.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <rect data-name="square" data-id="1" width="10" height="10"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10" height="10"/></svg>
```

### `removeDefaultAttributes`

Set: `cleanup`. Enum case: `Rule::RemoveDefaultAttributes`. Risky: no.

Removes stroke attributes whose value is the default (`stroke="none"`, `stroke-width="1"`, `stroke-linecap="butt"`,
`stroke-linejoin="miter"` and `stroke-miterlimit="4"`) when the element would inherit the same value: the nearest
ancestor that sets the attribute sets the same value, no ancestor in between sets the property in its inline style or
can be referenced by id, and that ancestor is inside the document (the root inherits from the page or `<use>`).
Attributes that are animated or that the element's own inline style overrides are kept, and documents with a style sheet
are left untouched.

It also removes the `type` attribute of `<style>` when it is `text/css` and of `<script>` when it is a JavaScript MIME
type, as these are the defaults. Other values, such as `module`, are kept, and so is the type of a script with a
`language` attribute, which would decide the script language without it.

And it removes `xml:space="default"` where it has no effect, that is, when no ancestor sets `xml:space="preserve"` and
no CSS `white-space` could be affected by it.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" xml:space="default">
  <g stroke="none" stroke-linecap="butt"><rect stroke="none" stroke-linecap="butt" width="10" height="10"/></g>
  <script type="text/javascript">draw()</script>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g stroke="none" stroke-linecap="butt"><rect width="10" height="10"/></g><script>draw()</script></svg>
```

### `removeDescriptions`

Set: none, so select it explicitly. Enum case: `Rule::RemoveDescriptions`. Risky: no.

Removes `<desc>` elements. Rendering stays the same, but by design the image loses its accessible description: screen
readers no longer announce it. Only use this rule for decorative SVGs, or when the page that embeds the image provides
the text. Elements in foreign content, children of a `<switch>`, and elements that contain an element with an `id`, an
animation, a script or a style sheet are kept, and nothing is removed when a style sheet uses sibling combinators or
structural pseudo-classes.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <desc>A square</desc>
  <rect width="10" height="10"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10" height="10"/></svg>
```

### `removeDuplicateElements`

Set: `cleanup`. Enum case: `Rule::RemoveDuplicateElements`. Risky: no.

Detects and removes identical duplicate elements that do not contribute additional visual output, such as repeated
`<stop>`, `<title>` or `<desc>` elements and duplicate children of `<defs>`. Elements that are or contain an animation,
a script or a style sheet are kept, as a copy can add to the effect. Nothing is removed when a style sheet uses sibling
combinators or structural pseudo-classes (such as `g + rect` or `:nth-child()`), whose matches depend on the surrounding
elements.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <defs>
    <linearGradient id="g"><stop offset="0" stop-color="red"/><stop offset="0" stop-color="red"/></linearGradient>
  </defs>
  <rect width="10" height="10" fill="url(#g)"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><defs><linearGradient id="g"><stop offset="0" stop-color="red"/></linearGradient></defs><rect width="10" height="10" fill="url(#g)"/></svg>
```

### `removeEditorMarkup`

Set: `cleanup`. Enum case: `Rule::RemoveEditorMarkup`. Risky: no.

Removes what editors add in their own namespaces: elements, attributes and namespace declarations. This covers Inkscape,
Sodipodi, Adobe Illustrator, Sketch, Serif and Microsoft Visio, matched by namespace URI, including Illustrator's
private data in a `<foreignObject>` that requires an Illustrator extension. Editor attributes that a style sheet selects
are kept, editor elements are kept when a style sheet uses sibling combinators or structural pseudo-classes, and
documents with an external style sheet are left untouched.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd" viewBox="0 0 24 24">
  <sodipodi:namedview id="base" inkscape:zoom="2"/>
  <rect inkscape:label="square" width="10" height="10"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10" height="10"/></svg>
```

### `removeEmptyAttributes`

Set: `cleanup`. Enum case: `Rule::RemoveEmptyAttributes`. Risky: no.

Removes attributes that have empty values. Such attributes add noise to the markup without affecting rendering and can
safely be removed to produce cleaner output. Empty attributes that mean something, such as the conditional processing
attributes (`systemLanguage`, `requiredExtensions`, `requiredFeatures`), `lang`, `href`, `download`, `crossorigin` and
`autofocus`, attributes that a style sheet selects, attributes of animations, and attributes on elements outside the SVG
namespace, such as `<foreignObject>` content, are kept. Documents with an external style sheet (an `xml-stylesheet`
instruction, a `<link>` element or an `@import` rule) are left untouched.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <rect class="" id="" width="10" height="10"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10" height="10"/></svg>
```

### `removeEmptyElements`

Set: `cleanup`. Enum case: `Rule::RemoveEmptyElements`. Risky: no.

Removes container elements such as `<defs>`, `<g>`, `<symbol>`, `<mask>`, `<clipPath>`, `<pattern>`, `<marker>` and
`<switch>` when they have no content. Containers that become empty are removed as well. Containers with an `id`, a
`role`, `aria-*` attributes, a `tabindex`, `autofocus` or an event handler, which can be referenced, are exposed to
assistive technology or can take focus, children of a `<switch>`, and groups with a `filter`, which can paint on its
own, are kept. Nothing is removed when a style sheet may apply a `filter`, or uses sibling combinators or structural
pseudo-classes (such as `g + rect` or `:nth-child()`), whose matches depend on the surrounding elements, or when the
document has a script or an event handler attribute, which may fill or query the empty elements.

It also removes empty text elements: `<text>` and `<tspan>` elements that contain no text at all, and `<tref>` elements
without a reference. These elements have no visual impact and can safely be eliminated to reduce file size.
Whitespace-only elements are kept, since their whitespace may render as a space between the surrounding text, and so are
elements with an `id`, a `role`, `aria-*` attributes, a `tabindex` or an event handler, and children of a `<switch>`.
Nothing is removed when a style sheet uses sibling combinators or structural pseudo-classes, or when the document has a
script or an event handler attribute.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <g><g/></g>
  <text></text>
  <rect width="10" height="10"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10" height="10"/></svg>
```

### `removeForeignContent`

Set: `security`. Enum case: `Rule::RemoveForeignContent`. Risky: no.

Removes content that does not belong in a sanitized SVG, or that an HTML parser reads differently when the SVG is
inlined in HTML: elements from other namespaces (such as XHTML or MathML), `<foreignObject>`, `<iframe>`, `<object>`,
`<embed>` and `<link>`, HTML elements that break out of SVG in an HTML parser (such as `<p>`, `<div>`, `<img>`,
`<table>`, and `<font>` with a `color`, `face` or `size` attribute), and elements and attributes whose name matches a
known name in a different letter case or namespace (such as `<STYLE>`, `<foreignobject>`, `attributename`, `HREF` or an
`xlink:href` in the wrong namespace), which are inert in XML but become the real thing when an HTML parser reads the
inlined SVG. It also removes XML Events attributes (such as `ev:event`), and unwraps `<a>`, `<use>`, `<tref>`,
`<image>`, gradients and patterns whose `href` or `xlink:href` holds an unsafe URL (as defined by `removeUnsafeUrls`),
keeping their children.

**Visible content it removes on purpose:** every `<foreignObject>` with HTML content (diagram tools such as draw.io
use it for text labels), embedded documents, and images and links that load external resources.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <foreignObject width="10" height="10"><div xmlns="http://www.w3.org/1999/xhtml">Hi</div></foreignObject>
  <rect width="10" height="10"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10" height="10"/></svg>
```

### `removeHiddenElements`

Set: `cleanup`. Enum case: `Rule::RemoveHiddenElements`. Risky: no.

Removes elements that cannot render anything: elements with `display="none"`, elements with `opacity="0"`, and shapes
whose fill and stroke both resolve to `none` through attributes set inside the document (the root inherits its fill and
stroke from the page or `<use>`). Elements inside definitions, clip paths, masks, patterns, markers and symbols,
elements with an `id` or a referenced descendant, animated elements, links and interactive or accessible content are
kept. Invisible elements inside an element with a `transform-box` or `transform-origin` (whose reference box they help
size) are kept, and so are transparent elements with a `<title>` (a tooltip), a `cursor` or `pointer-events` in their
subtree, as they still react to the pointer. Documents with a style sheet, a script or an event handler attribute (which
may show, resize or query the elements) are left untouched.

It also removes shapes with a zero size, which the SVG specification says are not rendered: circles and ellipses with a
zero radius, rects with a zero width or height, and paths, polylines and polygons without any data. Shapes with an `id`,
child elements or a `style` attribute, and documents with a `<style>` element, a script or an event handler attribute,
are kept.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <rect width="10" height="10" display="none"/>
  <circle r="0"/>
  <rect y="12" width="10" height="10"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect y="12" width="10" height="10"/></svg>
```

### `removeIneffectiveAttributes`

Set: `cleanup`. Enum case: `Rule::RemoveIneffectiveAttributes`. Risky: no.

Removes attributes that have no effect:

- Stroke and fill attributes: the other `stroke-*` attributes when the stroke is `none`, the stroke attributes except
  `stroke-width` when the stroke width is zero, and `fill-opacity` and `fill-rule` when the fill is `none`. The stroke,
  stroke width or fill counts only when an element of the document sets it, as the root inherits them from the page or
  `<use>`. Elements are skipped when a descendant could re-enable the stroke or fill, and documents with a style sheet,
  markers or `pointer-events` are left untouched.
- Group attributes: attributes of `<g>` elements that are neither inherited by the children nor apply to the group
  itself, such as `x`, `y`, `width`, `height`, `stop-color` or `flood-color`. Animated attributes are kept, and
  documents with a style sheet are left untouched.
- Text attributes: text-only presentation attributes, such as `font-family`, `font-size`, `letter-spacing` or
  `text-anchor`, on elements whose whole subtree cannot render text. `direction`, `writing-mode` and `unicode-bidi` are
  kept on the root element, where they also set the direction of the viewport. Documents that use font-relative units or
  have a style sheet are left untouched.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <rect width="10" height="10" fill="none" stroke="none" stroke-width="3" fill-opacity="0.5"/>
  <g x="5" y="5" fill="red"><rect width="10" height="10"/></g>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10" height="10" fill="none" stroke="none"/><g fill="red"><rect width="10" height="10"/></g></svg>
```

### `removeIneffectiveDefinitions`

Set: `cleanup`. Enum case: `Rule::RemoveIneffectiveDefinitions`. Risky: no.

Removes definitions that can never be used because neither they nor any descendant has an `id`: children of `<defs>`,
and gradients, patterns, clip paths, masks, markers, symbols and filters anywhere in the document. Definitions that are
or contain a `<style>`, a `<script>` or a font are kept, as these take effect anywhere. A `<defs>` left empty is removed
as well.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <defs>
    <linearGradient><stop offset="0" stop-color="red"/></linearGradient>
  </defs>
  <rect width="10" height="10"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10" height="10"/></svg>
```

### `removeInvisibleCharacters`

Set: `cleanup`. Enum case: `Rule::RemoveInvisibleCharacters`. Risky: no.

Removes invisible characters (soft hyphens, zero-width spaces and joiners, and the line and paragraph separators) from
text that is not rendered or read, such as whitespace between elements. Text inside `<text>`, `<title>`, `<desc>`,
`<style>`, `<script>`, `<foreignObject>` and elements outside the SVG namespace, and attribute values, are left as they
are. A text node that would become empty is kept when a style sheet uses structural pseudo-classes such as `:empty`.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  ​<rect width="10" height="10"/>­
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10" height="10"/></svg>
```

### `removeMetadata`

Set: `cleanup`. Enum case: `Rule::RemoveMetadata`. Risky: no.

Deletes `<metadata>` elements, which often contain authoring information, editor data, or descriptive metadata not
required for rendering. Metadata in foreign content, children of a `<switch>` (which a switch may render instead of a
later child), and metadata that contains an element with an `id`, an animation, a script or a style sheet are kept.
Nothing is removed when a style sheet uses sibling combinators or structural pseudo-classes.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <metadata>Created in an editor</metadata>
  <rect width="10" height="10"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10" height="10"/></svg>
```

### `removeNonStandardAttributes`

Set: `standards`. Enum case: `Rule::RemoveNonStandardAttributes`. Risky: no.

Removes SVG attributes that are non-standard, unsafe, or not part of the official SVG specification. Each SVG element
keeps only the attributes the specification defines for it, plus the shared attribute groups that apply to it (core
attributes such as `id`, `lang`, `tabindex`, `nonce`, `part` and `slot`, presentation, ARIA, conditional processing,
animation, filter primitive and similar), so for example `points` is removed from a `<rect>`. Elements the specification
does not define fall back to the full list of known SVG attributes, without HTML-only attributes such as `src`, `srcset`
and `action`. Event handler attributes (`on*`), which `removeScripts` removes, are always kept, and `xml:base` is always
removed. Prefixed attributes are matched by namespace: only known `xlink:`, `xml:` and `ev:` attributes are kept, and
attributes bound to any other namespace are removed. `data-*` attributes are kept when their name is valid, and so is
any attribute that a style sheet selects. Elements outside the SVG namespace, the content of a `<foreignObject>`, such
as XHTML, and documents with an external style sheet are left untouched.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <rect width="10" height="10" points="0,0" foo="bar"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10" height="10"/></svg>
```

### `removeNonStandardElements`

Set: `standards`. Enum case: `Rule::RemoveNonStandardElements`. Risky: no.

Removes SVG elements that are not part of the SVG specification, together with their content: browsers do not render the
children of an unknown element, so removing them does not change rendering. Unknown elements that have an `id` or
contain one, that are or contain an animation, a script or a style sheet, and children of a `<switch>` (which a switch
may render instead of a later child) are kept. Elements outside the SVG namespace, and the content of a
`<foreignObject>`, such as XHTML, are left untouched, and nothing is removed when a style sheet uses sibling combinators
or structural pseudo-classes.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <blink>Hi</blink>
  <rect width="10" height="10"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10" height="10"/></svg>
```

### `removeScripts`

Set: `security`. Enum case: `Rule::RemoveScripts`. Risky: no.

Removes script: `<script>` elements, event handler attributes (every attribute whose name starts with `on`, such as
`onclick` or `onload`, in any letter case and with any prefix), the SVG Tiny `<handler>` and `<listener>` elements, and
the `name` attribute, which can clobber the DOM of the page the SVG is inlined in. It also removes animations whose
`attributeName` targets an event handler, `name` or a link (`href` or `xlink:href`), because animating a link can turn
it into a `javascript:` URL; the animation is removed with its children. XML Events attributes are removed by
`removeForeignContent`, and animation values that set an unsafe URL by `removeUnsafeUrls`.

**Visible content it removes on purpose:** interactive SVGs lose their scripts, and animations of links are removed.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <script>alert(1)</script>
  <rect width="10" height="10" onclick="alert(1)"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10" height="10"/></svg>
```

### `removeTitles`

Set: none, so select it explicitly. Enum case: `Rule::RemoveTitles`. Risky: no.

Removes `<title>` elements. Rendering stays the same, but by design the image loses its accessible name: screen readers
no longer announce it, and browsers no longer show the title as a tooltip. Only use this rule for decorative SVGs, or
when the page that embeds the image provides the text. Elements in foreign content, children of a `<switch>`, and
elements that contain an element with an `id`, an animation, a script or a style sheet are kept, and nothing is removed
when a style sheet uses sibling combinators or structural pseudo-classes.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <title>Square</title>
  <rect width="10" height="10"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10" height="10"/></svg>
```

### `removeUnsafeMarkup`

Set: `security`. Enum case: `Rule::RemoveUnsafeMarkup`. Risky: no.

Removes every processing instruction (such as `<?xml-stylesheet ?>`, which can load a style sheet or an XSLT
transformation, or `<?php ?>`), and the comments that would end early or turn into markup when the SVG is inlined in
HTML: comments that start with `>` or `->`, or contain `<` followed by a letter, a digit, `_`, `/`, `!` or `?`. Other
comments are kept.

Input:

```xml
<?xml-stylesheet href="style.css"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <rect width="10" height="10"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10" height="10"/></svg>
```

### `removeUnsafeStyles`

Set: `security`. Enum case: `Rule::RemoveUnsafeStyles`. Risky: no.

Removes unsafe CSS from `<style>` elements and `style` attributes: `@import`, `expression()`, `behavior`,
`-moz-binding`, `-o-link`, `javascript:`, and `url()` or `image-set()` with an unsafe URL (as defined by
`removeUnsafeUrls`), also when written with escapes, comments or entities. In a `<style>` element only the unsafe
statements and declarations are removed; the whole element is removed when it cannot be parsed with confidence, is not
`text/css`, contains markup, or nothing else is left. An unsafe `style` attribute is removed as a whole.

**Visible content it removes on purpose:** the styles that are removed no longer apply.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <style>@import url(//example.com/a.css); .a{fill:red}</style>
  <rect class="a" width="10" height="10"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><style> .a{fill:red}</style><rect class="a" width="10" height="10"/></svg>
```

### `removeUnsafeUrls`

Set: `security`. Enum case: `Rule::RemoveUnsafeUrls`. Risky: no.

Removes attributes with a URL that can run script, load a resource from elsewhere or embed a document: a `javascript:`,
`vbscript:`, `data:` (other than a base64 PNG, GIF, JPEG, WebP, AVIF or SVG image), `http:`, `https:`, `file:` or other
unsafe protocol, or a network-path reference (`//host` or `\\host`). It checks `href`, `xlink:href`, `src`, the
candidates of `srcset` and the HTML URL attributes (such as `action`, `formaction`, `poster` and `data`); the `url()`
values of `fill`, `stroke`, `filter`, `clip-path`, `mask`, the marker properties, `cursor`, `style` and similar
attributes; and the animation values `values`, `from`, `to`, `by`, `begin` and `end`. It also removes `data:` links on
elements other than `<image>` and `<feImage>`. URLs are checked after decoding entities, CSS escapes and comments, and
ignoring whitespace, control and invisible characters and full-width letters, so obfuscated URLs are caught. The element
keeps its other attributes; `removeForeignContent` unwraps links with an unsafe URL instead.

**Visible content it removes on purpose:** images, fills and links that load external resources.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 24 24">
  <a href="javascript:alert(1)"><rect width="10" height="10"/></a>
  <image href="https://example.com/a.png" width="10" height="10"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 24 24"><a><rect width="10" height="10"/></a><image width="10" height="10"/></svg>
```

### `removeUnusedDefinitions` (**risky**)

Set: `cleanup`. Enum case: `Rule::RemoveUnusedDefinitions`. Risky: yes.

Removes definitions that nothing renders: gradients, patterns, clip paths, masks, filters, markers and other elements
with an `id` inside `<defs>` that no rendered content references, following chains of references. Style sheets, scripts,
fonts and animations are kept, and a `<defs>` left empty is removed.

Sprite sheets keep working: sprite targets, and everything they reference, are never removed. Sprite targets are the ids
that another document may address through `sprite.svg#id` (with `<use>`, `<img>` or a CSS background):

- `<symbol>` and `<view>` elements;
- `<g>`, `<svg>` and `<symbol>` elements directly inside the root `<svg>`;
- elements that `<use>` can show (such as `<g>`, `<path>` or a nested `<svg>`) inside the root `<defs>` or inside a
  group hidden with `display: none` (as an attribute, in its `style`, or through a class that a style sheet may
  hide), and inside groups within those;
- every element with an id, when a style sheet uses `:target`;
- every top-level element of a document that renders nothing itself, such as an inline sprite hidden with
  `display: none` or sized `0`, or a library of gradients, filters and masks.

The trade-off: an unused gradient in the `<defs>` of an ordinary icon is still removed, but an unused `<g id>` or `<path
id>` in `<defs>` is kept, because another document may show it with `<use href="icon.svg#id">`.

**Why it is risky:** references from outside the document cannot be seen. A gradient, clip path, mask, filter or
marker of a document that renders content is removed when nothing inside the document uses it, even when CSS or another
document uses it through `url(file.svg#id)`, or through `url(#id)` from elsewhere in the page when the SVG is inlined
into HTML.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <defs>
    <linearGradient id="used"><stop offset="0" stop-color="red"/></linearGradient>
    <linearGradient id="unused"><stop offset="0" stop-color="blue"/></linearGradient>
  </defs>
  <rect width="10" height="10" fill="url(#used)"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><defs><linearGradient id="used"><stop offset="0" stop-color="red"/></linearGradient></defs><rect width="10" height="10" fill="url(#used)"/></svg>
```

### `removeUnusedIds` (**risky**)

Set: `cleanup`. Enum case: `Rule::RemoveUnusedIds`. Risky: yes.

Removes `id` attributes that nothing in the document references, through `url(#id)`, `href`, ARIA attributes, animation
timing or `<style>` rules (id selectors and attribute selectors such as `[id="a"]`). The id of the root `<svg>` and the
ids of sprite targets (see `removeUnusedDefinitions`), such as `<symbol>` and `<view>` elements, top-level groups and
every id when a style sheet uses `:target`, are kept, together with every id they reference. Nothing is removed when the
document contains a script, an event handler attribute, or an attribute selector that may match several ids (such as
`[id^="a"]`).

**Why it is risky:** CSS, JavaScript, analytics or end-to-end tests in the page that embeds the SVG may select elements
by id (`#logo-text`, `getElementById`), and links or other documents may point at `file.svg#id`. Ids that are not
sprite targets, such as the id of a `<path>` inside a rendered group, are removed when nothing inside the SVG references
them, and those lookups then find nothing.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <rect id="a" width="10" height="10"/>
  <rect id="b" y="12" width="10" height="10"/>
  <use href="#b" x="12"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10" height="10"/><rect id="b" y="12" width="10" height="10"/><use href="#b" x="12"/></svg>
```

### `removeUnusedNamespaces`

Set: `cleanup`. Enum case: `Rule::RemoveUnusedNamespaces`. Risky: no.

Removes XML namespace declarations of the root element whose prefix no element or attribute name uses and no attribute
value or text mentions (as in `attributeName="xlink:href"`). This cleans up the root element, reduces verbosity, and
improves readability.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:dc="http://purl.org/dc/elements/1.1/" viewBox="0 0 24 24">
  <rect width="10" height="10"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10" height="10"/></svg>
```

### `removeUseBombs`

Set: `security`. Enum case: `Rule::RemoveUseBombs`. Risky: no.

Removes `<use>` elements that reference themselves or an ancestor, form a cycle with other `<use>` elements, or whose
target would render 1,000 or more nested `<use>` instances, which can freeze a browser. Sprite sheets with hundreds of
`<use>` elements and deep chains of references under the limit are kept, and references to other files are not followed.

**Visible content it removes on purpose:** a `<use>` element over the limit is removed, also when a browser would
render it.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <defs><g id="a"><use href="#a"/></g></defs>
  <rect width="10" height="10"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><defs><g id="a"/></defs><rect width="10" height="10"/></svg>
```

### `removeWidthHeightAttributes` (**risky**)

Set: `cleanup`. Enum case: `Rule::RemoveWidthHeightAttributes`. Risky: yes.

Removes the `width` and `height` attributes from the root `<svg>` element, relying solely on the `viewBox` for scaling.
This lets the SVG resize with its container. Roots without a valid `viewBox` (four numbers with a positive width and
height) are left untouched, as the SVG would otherwise lose its aspect ratio and fall back to 300×150 pixels.

**Why it is risky:** without `width` and `height` the SVG has no intrinsic size, only an aspect ratio. An inline SVG or
an `<img>` without CSS dimensions then stretches to the width of its container, and layouts, email clients and tools
that rely on the fixed size render it at a different size.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
  <rect width="10" height="10"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="10" height="10"/></svg>
```

### `scopeIdsAndClasses` (**risky**)

Set: none, so select it explicitly. Enum case: `Rule::ScopeIdsAndClasses`. Risky: yes.

Adds a suffix to every id and class name, for example `r` becomes `r-5415dd3099`, and rewrites the style rules and
references that use them. Each SVG gets its own names, so several SVGs can share one page without style collisions.
Selectors without a class or id selector outside a functional pseudo-class (such as `rect`, `g rect`, `svg *`, `[fill]`
or `rect:not(.a)`) cannot be renamed and would style the whole page, so they are removed; a style sheet left without
rules is removed too. Names that already have a suffix are left unchanged, so optimizing an SVG again does not change
it. The suffix is derived from the drawing, not from its formatting, so a pretty-printed and a minified copy of the same
SVG get the same suffix. Documents whose style sheets nest rules in at-rule blocks (such as `@media` or `@supports`),
use `@import` or CSS escapes, select ids or class names with an attribute selector (such as `[id="a"]` or
`[class~="b"]`), or that have a style element of another type than `text/css` or with content other than text, are left
untouched, as those rules cannot be rewritten. Sprite targets (see `removeUnusedDefinitions`) keep their id, and the
selectors and references that name them are left unchanged, so `<use href="#icon">` from the page and `sprite.svg#icon`
keep working; when a style sheet uses `:target`, no id is renamed. Class names are still renamed.

**Why it is risky:** the renamed ids and class names no longer match anything outside the SVG that uses the original
names: page-level CSS such as `.icon path { fill: currentColor }` or `#logo:hover`, JavaScript (`getElementById`,
`querySelector`, toggling a class), analytics and test selectors, `url(#id)` references from other inline SVGs on the
page, and `file.svg#id` references to ids that are not sprite targets. Removing rules whose selectors may match
elements of the page (no class or id selector) also changes how the SVG itself renders when it relied on them.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <style>.a{fill:red}</style>
  <rect id="r" class="a" width="10" height="10"/>
  <use href="#r" y="12"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><style>.a-5415dd3099{fill:red}</style><rect id="r-5415dd3099" class="a-5415dd3099" width="10" height="10"/><use href="#r-5415dd3099" y="12"/></svg>
```

### `sortAttributes`

Set: `minification`. Enum case: `Rule::SortAttributes`. Risky: no.

Sorts element attributes: namespace declarations come first, then `id`, `width` and `height` in that order, then the
other attributes alphabetically by name. This improves consistency, makes diffs easier to read in version control
systems, and results in more deterministic SVG output across optimization runs. An element where an attribute without a
namespace shares its local name with an attribute in a namespace (such as `href` and `xlink:href`) keeps its order.

Input:

```xml
<svg height="24" viewBox="0 0 24 24" width="24" id="i" xmlns="http://www.w3.org/2000/svg">
  <rect y="1" x="2" height="10" width="10" fill="red"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" id="i" width="24" height="24" viewBox="0 0 24 24"><rect width="10" height="10" fill="red" x="2" y="1"/></svg>
```

### `sortDefinitions`

Set: `minification`. Enum case: `Rule::SortDefinitions`. Risky: no.

Sorts the children of each `<defs>` so that elements with the same name are next to each other, the most frequent name
first, which helps gzip compression without changing the raw size. A `<defs>` that holds anything other than elements,
or content whose order matters, is skipped, and documents with a style sheet, a script or duplicate ids are left
untouched.

Input:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <defs>
    <linearGradient id="g"><stop offset="0"/></linearGradient>
    <clipPath id="c"><rect width="5" height="5"/></clipPath>
    <linearGradient id="h"><stop offset="0"/></linearGradient>
  </defs>
  <rect width="10" height="10" clip-path="url(#c)" fill="url(#g)"/>
</svg>
```

Output:

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><defs><linearGradient id="g"><stop offset="0"/></linearGradient><linearGradient id="h"><stop offset="0"/></linearGradient><clipPath id="c"><rect width="5" height="5"/></clipPath></defs><rect width="10" height="10" clip-path="url(#c)" fill="url(#g)"/></svg>
```

## Contributing

Contributions are welcome. See [CONTRIBUTING.md](CONTRIBUTING.md) for the development setup, the quality requirements
and how to add a rule. For a list of proposed features and known issues, see the
[open issues](https://github.com/MathiasReker/php-svg-optimizer/issues).

## License

This project is licensed under the MIT License. See
the [LICENSE](https://github.com/MathiasReker/php-svg-optimizer/blob/develop/LICENSE) file for more information.

## Disclaimer

Use of this tool is entirely at your own risk. The authors and maintainers make no warranties regarding the correctness,
reliability, or suitability of the tool for any particular purpose. Users are solely responsible for verifying the
results and ensuring that files are properly backed up before use. It is strongly recommended to test the tool on
non-critical files and confirm compatibility with your workflow prior to deploying it in any production environment.
