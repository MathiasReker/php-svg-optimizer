<h1 align="center">PHP SVG Optimizer</h1>

[![Packagist Version](https://img.shields.io/packagist/v/MathiasReker/php-svg-optimizer.svg)](https://packagist.org/packages/MathiasReker/php-svg-optimizer)
[![Packagist Downloads](https://img.shields.io/packagist/dt/MathiasReker/php-svg-optimizer.svg?color=%23ff007f)](https://packagist.org/packages/MathiasReker/php-svg-optimizer)
[![CI status](https://github.com/MathiasReker/php-svg-optimizer/actions/workflows/ci.yml/badge.svg?branch=develop)](https://github.com/MathiasReker/php-svg-optimizer/actions/workflows/ci.yml)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-Level%209-blue)](#)
[![Type Coverage](https://img.shields.io/badge/type%20coverage-100%25-brightgreen)](#)
[![Code Coverage](https://github.com/MathiasReker/php-svg-optimizer/blob/develop/dev/artifacts/coverage.svg)](#)
[![Filesize](https://github.com/MathiasReker/php-svg-optimizer/blob/develop/dev/artifacts/filesize.svg)](#)
[![Contributors](https://img.shields.io/github/contributors/MathiasReker/php-svg-optimizer.svg)](https://github.com/MathiasReker/php-svg-optimizer/graphs/contributors)
[![Forks](https://img.shields.io/github/forks/MathiasReker/php-svg-optimizer.svg)](https://github.com/MathiasReker/php-svg-optimizer/network/members)
[![Stargazers](https://img.shields.io/github/stars/MathiasReker/php-svg-optimizer.svg)](https://github.com/MathiasReker/php-svg-optimizer/stargazers)
[![Issues](https://img.shields.io/github/issues/MathiasReker/php-svg-optimizer.svg)](https://github.com/MathiasReker/php-svg-optimizer/issues)
[![MIT License](https://img.shields.io/github/license/MathiasReker/php-svg-optimizer.svg)](https://github.com/MathiasReker/php-svg-optimizer/blob/develop/LICENSE.txt)
[![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat&logo=php&logoColor=white)](#)

`php-svg-optimizer` is a lightweight PHP library designed to optimize SVG files by applying various transformations and
cleanup operations. The library ensures that the optimized SVG files are **compliant with SVG 2.0** specifications.

The tool strives to optimize as much as possible without losing any data that could distort the image's quality,
ensuring the resulting SVG remains visually identical to the original while being more efficient in terms of size and
performance.

### Versions & Dependencies

| Version | PHP  | Documentation                                                |
|---------|------|--------------------------------------------------------------|
| ^8.2    | ^8.3 | [current](https://github.com/MathiasReker/php-svg-optimizer) |

### Requirements

- `ext-dom`: Required PHP extension for XML handling.
- `ext-libxml`: Required PHP extension for XML error handling.
- `ext-mbstring`: Required PHP extension for multibyte string handling.

### Installation

To install the library, run:

```bash
composer require mathiasreker/php-svg-optimizer
```

![Demo GIF](dev/artifacts/demo.gif)

### Using the Library

You can use this library in two main ways:

1. Command-Line Interface (CLI): Run the optimizer directly from your terminal to process SVG files quickly. This is
   ideal for batch processing or integrating into build scripts.

2. Standalone Package: Use it as a PHP package in your project to optimize SVGs programmatically. This allows you to
   integrate SVG optimization directly into your application workflow or custom scripts.

## CLI tool

```bash
vendor/bin/svg-optimizer [options] process <path1> <path2> ...
```

```bash
Options:
-h , --help               Display help for the command.
-c , --config             Path to a JSON file with custom optimization rules. If not provided, all default optimizations will be applied.
-d , --dry-run            Only calculate potential savings without modifying the files.
-r , --allow-risky        Explicitly enables risky rules, allowing them to be applied.
-a , --with-all-rules     Enable all non-risky rules. Use --allow-risky to include risky rules as well.
-q , --quiet              Suppress all output except errors.
-v , --version            Display the version of the library.

Commands:
Process                   Provide a list of directories or files to process.
```

#### Examples:

```bash
vendor/bin/svg-optimizer --dry-run --with--all-rules process /path/to/svgs
vendor/bin/svg-optimizer --config=config.json process /path/to/file.svg
vendor/bin/svg-optimizer --config='{"removeUnsafeElements": true}' --allow-risky process /path/to/file.svg
vendor/bin/svg-optimizer --quiet --with--all-rules process /path/to/file.svg
vendor/bin/svg-optimizer --with-all-rules process /path/to/file.svg
```

#### Config file example:

```json
{
    "convertColorsToHex": true,
    "convertCssClassesToAttributes": true,
    "convertEmptyTagsToSelfClosing": true,
    "convertInlineStylesToAttributes": true,
    "flattenGroups": true,
    "minifySvgCoordinates": true,
    "minifyTransformations": true,
    "removeAriaAndRole": true,
    "removeComments": true,
    "removeDefaultAttributes": true,
    "removeDeprecatedAttributes": true,
    "removeDoctype": true,
    "removeDuplicateElements": true,
    "removeEmptyAttributes": true,
    "removeEmptyGroups": true,
    "removeEmptyTextElements": true,
    "removeEnableBackgroundAttribute": true,
    "removeInkscapeFootprints": true,
    "removeInvisibleCharacters": true,
    "removeMetadata": true,
    "removeTitleAndDesc": true,
    "removeUnnecessaryWhitespace": true,
    "removeUnsafeElements": false,
    "removeUnusedMasks": true,
    "removeUnusedNamespaces": true,
    "removeWidthHeightAttributes": false,
    "sortAttributes": true
}
```

### Example Workflow for GitHub Actions

```bash
name: Optimize SVGs

on: [push, pull_request]

jobs:
  run-optimizer:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v6
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.5'
      - run: composer install --no-dev --optimize-autoloader --no-interaction --no-progress
      - run: php vendor/bin/svg-optimizer -a -q process /path/to/svgs
```

## Package

> It is recommended to catch exceptions when using this library.
> Doing so ensures that your application can handle unexpected input gracefully and avoid unintended crashes.

### Example specifying rules

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use MathiasReker\PhpSvgOptimizer\Service\Facade\SvgOptimizerFacade;

try {
    $svgOptimizer = SvgOptimizerFacade::fromFile('path/to/source.svg')
        ->withRules(
            convertColorsToHex: true,
            convertCssClassesToAttributes: true,
            convertEmptyTagsToSelfClosing: true,
            convertInlineStylesToAttributes: true,
            flattenGroups: true,
            minifySvgCoordinates: true,
            minifyTransformations: true,
            removeAriaAndRole: true,
            removeComments: true,
            removeDefaultAttributes: true,
            removeDeprecatedAttributes: true,
            removeDoctype: true,
            removeDuplicateElements: true,
            removeEmptyAttributes: true,
            removeEmptyGroups: true,
            removeEmptyTextElements: true,
            removeEnableBackgroundAttribute: true,
            removeInkscapeFootprints: true,
            removeInvisibleCharacters: true,
            removeMetadata: true,
            removeTitleAndDesc: true,
            removeUnnecessaryWhitespace: true,
            removeUnsafeElements: false,
            removeUnusedMasks: true,
            removeUnusedNamespaces: true,
            removeWidthHeightAttributes: false,
            sortAttributes: true,
        )
        ->optimize()
        ->saveToFile('path/to/output.svg');
} catch (\Exception $exception) {
    echo $exception->getMessage();
}
```

### Example parsing from a file and saving to a file using default rules

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use MathiasReker\PhpSvgOptimizer\Service\Facade\SvgOptimizerFacade;

try {
    $svgOptimizer = SvgOptimizerFacade::fromFile('path/to/source.svg')
        ->withAllRules()
        ->optimize()
        ->saveToFile('path/to/output.svg');

    $metaData = $svgOptimizer->getMetaData();

    echo sprintf('Optimized size: %d bytes%s', $metaData->getOptimizedSize(), \PHP_EOL);
    echo sprintf('Original size: %d bytes%s', $metaData->getOriginalSize(), \PHP_EOL);
    echo sprintf('Size reduction: %d bytes%s', $metaData->getSavedBytes(), \PHP_EOL);
    echo sprintf('Reduction percentage: %s %%%s', $metaData->getSavedPercentage(), \PHP_EOL);
    echo sprintf('Processing time: %s seconds%s', $metaData->getOptimizationTime(), \PHP_EOL);
} catch (\Exception $exception) {
    echo $exception->getMessage();
}

```

### Example parsing from a file and returning the content using default rules

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use MathiasReker\PhpSvgOptimizer\Service\Facade\SvgOptimizerFacade;

try {
    $svgOptimizer = SvgOptimizerFacade::fromFile('path/to/source.svg')
        ->withAllRules()
        ->optimize();

    echo sprintf('Get content: ', $svgOptimizer->getContent(), \PHP_EOL);

    $metaData = $svgOptimizer->getMetaData();

    echo sprintf('Optimized size: %d bytes%s', $metaData->getOptimizedSize(), \PHP_EOL);
    echo sprintf('Original size: %d bytes%s', $metaData->getOriginalSize(), \PHP_EOL);
    echo sprintf('Size reduction: %d bytes%s', $metaData->getSavedBytes(), \PHP_EOL);
    echo sprintf('Reduction percentage: %s %%%s', $metaData->getSavedPercentage(), \PHP_EOL);
    echo sprintf('Processing time: %s seconds%s', $metaData->getOptimizationTime(), \PHP_EOL);
} catch (\Exception $exception) {
    echo $exception->getMessage();
}
```

### Example parsing from a string and returning the content using default rules

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use MathiasReker\PhpSvgOptimizer\Service\Facade\SvgOptimizerFacade;

try {
    $svgOptimizer = SvgOptimizerFacade::fromString('<svg>...</svg>')
        ->withAllRules()
        ->optimize();

    echo sprintf('Content: ', $svgOptimizer->getContent(), \PHP_EOL);

    $metaData = $svgOptimizer->getMetaData();

    echo sprintf('Optimized size: %d bytes%s', $metaData->getOptimizedSize(), \PHP_EOL);
    echo sprintf('Original size: %d bytes%s', $metaData->getOriginalSize(), \PHP_EOL);
    echo sprintf('Size reduction: %d bytes%s', $metaData->getSavedBytes(), \PHP_EOL);
    echo sprintf('Reduction percentage: %s %%%s', $metaData->getSavedPercentage(), \PHP_EOL);
    echo sprintf('Processing time: %s seconds%s', $metaData->getOptimizationTime(), \PHP_EOL);
} catch (\Exception $exception) {
    echo $exception->getMessage();
}
```

### Example applying risky rules

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use MathiasReker\PhpSvgOptimizer\Service\Facade\SvgOptimizerFacade;

try {
    $svgOptimizer = SvgOptimizerFacade::fromFile('path/to/source.svg')
        ->withRules(
            removeWidthHeightAttributes: true,
            removeUnsafeElements: true,
        )
        ->allowRisky()
        ->optimize()
        ->saveToFile('path/to/output.svg');

    echo sprintf('Content: ', $svgOptimizer->getContent(), \PHP_EOL);

    $metaData = $svgOptimizer->getMetaData();

    echo sprintf('Optimized size: %d bytes%s', $metaData->getOptimizedSize(), \PHP_EOL);
    echo sprintf('Original size: %d bytes%s', $metaData->getOriginalSize(), \PHP_EOL);
    echo sprintf('Size reduction: %d bytes%s', $metaData->getSavedBytes(), \PHP_EOL);
    echo sprintf('Reduction percentage: %s %%%s', $metaData->getSavedPercentage(), \PHP_EOL);
    echo sprintf('Processing time: %s seconds%s', $metaData->getOptimizationTime(), \PHP_EOL);
} catch (\Exception $exception) {
    echo $exception->getMessage();
}
```

### Documentation

Static factory method to create `SvgOptimizerFacade` from a file path.

```php
$svgOptimizer = SvgOptimizerFacade::fromFile('path/to/source.svg');
```

Static factory method to create `SvgOptimizerFacade` from a string.

```php
$svgOptimizer = SvgOptimizerFacade::fromString('<svg>...</svg>');
```

#### `withRules` Method

Configure which SVG optimization rules to apply. The method accepts boolean parameters that determine whether specific
rules should be enabled or disabled.

##### Parameters:

Converts all color values (e.g., `rgb()`) to hexadecimal (`#RRGGBB`) and uses shorthand where possible. Standardizes
colors and slightly reduces file size:

```php
$svgOptimizer->withRules(convertColorsToHex: true);
```

Replaces CSS class references with inline attributes so styles are applied directly to elements. Useful when external
CSS may not be available:

```php
$svgOptimizer->withRules(convertCssClassesToAttributes: true);
```

Converts empty tags into self-closing format (`<tag />`) to save space and improve readability:

```php
$svgOptimizer->withRules(convertEmptyTagsToSelfClosing: true);
```

Splits inline `style` attributes into individual element attributes. Improves compatibility with SVG renderers that do
not fully support CSS styles:

```php
$svgOptimizer->withRules(convertInlineStylesToAttributes: true);
```

Moves all child elements of nested `<g>` groups directly into the parent, removing unnecessary grouping. Simplifies the
SVG structure and reduces file size:

```php
$svgOptimizer->withRules(flattenGroups: true);
```

Reduces decimal precision in coordinates (e.g., `12.0000` → `12`) to shrink file size without noticeable visual
differences:

```php
$svgOptimizer->withRules(minifySvgCoordinates: true);
```

Optimizes `transform` attributes by removing redundant values or simplifying transforms. Reduces file size and improves
parsing efficiency:

```php
$svgOptimizer->withRules(minifyTransformations: true);
```

Removes ARIA (`aria-*`) and `role` attributes that may be unnecessary in static SVGs. Reduces file size and avoids
redundant accessibility information if not needed:

```php
$svgOptimizer->withRules(removeAriaAndRole: true);
```

Removes all comments except legal ones, reducing file size and eliminating unnecessary developer notes:

```php
$svgOptimizer->withRules(removeComments: true);
```

Deletes attributes that have default values (e.g., `stroke="none"` if not needed). Saves space without affecting
rendering:

```php
$svgOptimizer->withRules(removeDefaultAttributes: true);
```

Removes attributes that are deprecated in SVG 2.0, helping modernize the SVG for current standards:

```php
$svgOptimizer->withRules(removeDeprecatedAttributes: true);
```

Removes the `<!DOCTYPE>` declaration, which is unnecessary for inline or web-embedded SVGs:

```php
$svgOptimizer->withRules(removeDoctype: true);
```

Deletes identical duplicate elements to reduce file size without affecting the visual output:

```php
$svgOptimizer->withRules(removeDuplicateElements: true);
```

Removes attributes with empty values (`attr=""`) to reduce clutter and file size:

```php
$svgOptimizer->withRules(removeEmptyAttributes: true);
```

Deletes `<g>` elements that have no child elements. Simplifies the SVG tree and reduces size:

```php
$svgOptimizer->withRules(removeEmptyGroups: true);
```

Removes `<text>` elements that contain no content. Reduces file size and unnecessary nodes:

```php
$svgOptimizer->withRules(removeEmptyTextElements: true);
```

Removes the `enable-background` attribute from `<svg>`. May improve performance but could affect filters or animations
that depend on it (**risky**):

```php
$svgOptimizer->withRules(removeEnableBackgroundAttribute: true);
```

Deletes Inkscape-specific metadata and elements (e.g., `inkscape:label` or hidden layers). Cleans up SVGs exported from
Inkscape:

```php
$svgOptimizer->withRules(removeInkscapeFootprints: true);
```

Strips invisible Unicode characters (e.g., zero-width spaces) that may accidentally appear in SVGs:

```php
$svgOptimizer->withRules(removeInvisibleCharacters: true);
```

Removes `<metadata>` tags, which often contain author info or editor data, reducing file size:

```php
$svgOptimizer->withRules(removeMetadata: true);
```

Removes `<title>` and `<desc>` tags. Reduces file size but also removes accessibility descriptions, so safe only if not
required:

```php
$svgOptimizer->withRules(removeTitleAndDesc: true);
```

Cleans up extra spaces, tabs, and line breaks. Improves readability and reduces file size:

```php
$svgOptimizer->withRules(removeUnnecessaryWhitespace: true);
```

Removes XML namespaces that are declared but not used. Cleans up the SVG and reduces size:

```php
$svgOptimizer->withRules(removeUnusedNamespaces: true);
```

Removes the `width` and `height` attributes from the `<svg>` element to rely on `viewBox` scaling. Allows flexible
resizing but may break fixed-layout designs (**risky**):

```php
$svgOptimizer->withRules(removeWidthHeightAttributes: false);
```

The `removeUnsafeElements` rule is designed to **sanitize potentially dangerous content** in SVG files. SVG is XML-based
and can contain scripts, external references, and interactive elements that may pose **security risks** if untrusted
files are embedded in your application or website.

When enabled, this rule **removes elements and attributes that could be exploited**, including:

- `<script>` — prevents embedded JavaScript execution.
- `<foreignObject>` — blocks embedded HTML, which could contain malicious code.
- `<image>` with external references (`xlink:href`) — prevents loading potentially unsafe external resources.
- Event attributes like `onclick` or `onload` — disables interactive JavaScript triggers.

```php
$svgOptimizer->withRules(removeUnsafeElements: true);
```

Deletes `<mask>` elements that are defined but never used. Reduces file size and complexity:

```php
$svgOptimizer->withRules(removeUnusedMasks: true);
```

Sorts element attributes alphabetically to improve consistency, readability, and version control diffs:

```php
$svgOptimizer->withRules(sortAttributes: true);
```

Below you see the default configuration. You can configure each rule individually by passing the desired values to it:

```php
$svgOptimizer->withRules(
    convertColorsToHex: true,
    convertCssClassesToAttributes: true,
    convertEmptyTagsToSelfClosing: true,
    convertInlineStylesToAttributes: true,
    flattenGroups: true,
    minifySvgCoordinates: true,
    minifyTransformations: true,
    removeAriaAndRole: true,
    removeComments: true,
    removeDefaultAttributes: true,
    removeDeprecatedAttributes: true,
    removeDoctype: true,
    removeDuplicateElements: true,
    removeEmptyAttributes: true,
    removeEmptyGroups: true,
    removeEmptyTextElements: true,
    removeEnableBackgroundAttribute: true,
    removeInkscapeFootprints: true,
    removeInvisibleCharacters: true,
    removeMetadata: true,
    removeTitleAndDesc: true,
    removeUnnecessaryWhitespace: true,
    removeUnsafeElements: false,
    removeUnusedMasks: true,
    removeUnusedNamespaces: true,
    removeWidthHeightAttributes: false,
    sortAttributes: true,
);
```

#### `withAllRules` Method

Enable all rules. Risky rules remain disabled unless `allowRisky()` is explicitly set.

```php
$svgOptimizer->withAllRules();
```

#### `allowRisky` Method

By default, risky rules are not applied even if you add them unless explicitly allowed.

```php
$svgOptimizer->allowRisky();
```

#### `optimize` Method

Finalizes the optimization process and generates the optimized SVG file.

```php
$svgOptimizer->optimize();
```

#### `saveToFile` Method

Saves the optimized SVG file to the specified path.

```php
$svgOptimizer->saveToFile('path/to/output.svg');
```

#### `getContent` Method

Returns the optimized SVG content.

```php
$svgOptimizer->getContent();
```

#### `getOptimizedSize` Method

Returns the size of the optimized SVG file.

```php
$svgOptimizer->getMetaData()->getOptimizedSize();
```

#### `getOriginalSize` Method

Returns the size of the original SVG file.

```php
$svgOptimizer->getMetaData()->getOriginalSize();
```

#### `getSavedBytes` Method

Returns the number of bytes saved by the optimization process.

```php
$svgOptimizer->getMetaData()->getSavedBytes();
```

#### `getSavedPercentage` Method

Returns the percentage of bytes saved by the optimization process.

```php
$svgOptimizer->getMetaData()->getSavedPercentage();
```

#### `getOptimizedTime` Method

Returns the time taken to optimize the SVG file, in seconds.

```php
$svgOptimizer->getMetaData()->getOptimizedTime();
```

### Roadmap

For a complete list of proposed features and known issues, see
the [open issues](https://github.com/MathiasReker/php-svg-optimizer/issues).

### Contributing

We welcome all contributions! If you have ideas for improvements, feel free to fork the repository and submit a pull
request. You can also open an issue. If you find this project helpful, don’t forget to give it a star!

#### Library Structure and Contribution Guide

The library implements the Strategy Pattern, where strategies are encapsulated as "rules" located in the
`/src/Service/Rule` directory.

##### Adding a New Rule

### 1. **Create the Rule**

Create a new **final readonly class** in the `/src/Service/Rule` directory and implement
the `SvgOptimizerRuleInterface`. This interface will define the logic for your rule.

### 2. **Write Tests**

Write comprehensive **unit tests** for your rule in the `/tests/Unit/Service/Rule` directory. Ensure the tests cover
various scenarios to verify the correct behavior and edge cases for your rule.

### 3. **Integrate the Rule**

- **Add your rule to the rule enum** in `/src/Type/Rule.php`.
- **Register the rule** in the SVG optimizer builder located at `/src/Service/Facade/SvgOptimizerFacade.php`.

### 4. **Update Documentation**

Document the functionality and purpose of your rule in the `README.md` to ensure users understand its behavior and
usage.

#### Docker

To use the project with Docker, you can start the container using:

```bash
docker-compose up -d
```

Then, access the container:

```bash
docker exec -it php-svg-optimizer bash
```

#### Tools

Run static analysis:

```bash
composer analyze:all
```

Run tests:

```bash
composer test
```

Fix code style:

```bash
composer lint:all
```

Build badges:

```bash
composer build:badges
```

### License

This project is licensed under the MIT License. See
the [LICENSE](https://github.com/MathiasReker/php-svg-optimizer/blob/develop/LICENSE) file for more information.

### Disclaimer

Use of this tool is entirely at your own risk. The authors and maintainers make no warranties regarding the correctness,
reliability, or suitability of the tool for any particular purpose. Users are solely responsible for verifying the
results and ensuring that files are properly backed up before use. It is strongly recommended to test the tool on
non-critical files and confirm compatibility with your workflow prior to deploying it in any production environment.
