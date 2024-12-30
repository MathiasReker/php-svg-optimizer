<h1 align="center">PHP SVG Optimizer</h1>

[![Packagist Version](https://img.shields.io/packagist/v/MathiasReker/php-svg-optimizer.svg)](https://packagist.org/packages/MathiasReker/php-svg-optimizer)
[![Packagist Downloads](https://img.shields.io/packagist/dt/MathiasReker/php-svg-optimizer.svg?color=%23ff007f)](https://packagist.org/packages/MathiasReker/php-svg-optimizer)
[![CI status](https://github.com/MathiasReker/php-svg-optimizer/actions/workflows/ci.yml/badge.svg?branch=develop)](https://github.com/MathiasReker/php-svg-optimizer/actions/workflows/ci.yml)
[![Contributors](https://img.shields.io/github/contributors/MathiasReker/php-svg-optimizer.svg)](https://github.com/MathiasReker/php-svg-optimizer/graphs/contributors)
[![Forks](https://img.shields.io/github/forks/MathiasReker/php-svg-optimizer.svg)](https://github.com/MathiasReker/php-svg-optimizer/network/members)
[![Stargazers](https://img.shields.io/github/stars/MathiasReker/php-svg-optimizer.svg)](https://github.com/MathiasReker/php-svg-optimizer/stargazers)
[![Issues](https://img.shields.io/github/issues/MathiasReker/php-svg-optimizer.svg)](https://github.com/MathiasReker/php-svg-optimizer/issues)
[![MIT License](https://img.shields.io/github/license/MathiasReker/php-svg-optimizer.svg)](https://github.com/MathiasReker/php-svg-optimizer/blob/develop/LICENSE.txt)

`php-svg-optimizer` is a PHP library designed to optimize SVG files by applying various transformations and cleanup
operations. The library ensures that the optimized SVG files are **compliant with SVG 2.0** specifications.

The tool strives to optimize as much as possible without losing any data that could distort the image's quality,
ensuring the resulting SVG remains visually identical to the original while being more efficient in terms of size and
performance.

### Versions & Dependencies

| Version | PHP  | Documentation                                                |
|---------|------|--------------------------------------------------------------|
| ^5.1    | ^8.3 | [current](https://github.com/MathiasReker/php-svg-optimizer) |

### Requirements

- `ext-dom`: Required PHP extension for XML handling.
- `ext-libxml`: Required PHP extension for XML error handling.

### Installation

To install the library, run:

```bash
composer require mathiasreker/php-svg-optimizer
```

### Using the Library

You can use this library either as a **command-line tool (CLI)** or as a **standalone package**.

---

## CLI tool

#### Usage

```bash
vendor/bin/svg-optimizer [options] process <path1> <path2> ...
```

```bash
Options:
-h , --help               Display help for the command.
-c , --config             Path to a JSON file with custom optimization rules. If not provided, all default optimizations will be applied.
-d , --dry-run            Only calculate potential savings without modifying the files.
-q , --quiet              Suppress all output except errors.
-v , --version            Display the version of the library.

Commands:
Process                   Provide a list of directories or files to process.
```

#### Examples:

```bash
vendor/bin/svg-optimizer --dry-run process /path/to/svgs
vendor/bin/svg-optimizer --config=config.json process /path/to/file.svg
vendor/bin/svg-optimizer --quiet process /path/to/file.svg
```

#### Config file example:

```json
{
  "convertColorsToHex": true,
  "flattenGroups": true,
  "minifySvgCoordinates": true,
  "minifyTransformations": true,
  "removeComments": true,
  "removeDefaultAttributes": true,
  "removeDeprecatedAttributes": true,
  "removeDoctype": true,
  "removeEnableBackgroundAttribute": true,
  "removeEmptyAttributes": true,
  "removeMetadata": true,
  "removeInvisibleCharacters": true,
  "removeTitleAndDesc": true,
  "removeUnnecessaryWhitespace": true,
  "sortAttributes": true
}
```

## Package

> To ensure robustness when using the library, it's crucial to handle exceptions, as invalid or malformed SVG files
> could lead to runtime errors. Catching these exceptions will allow you to manage potential issues gracefully and
> prevent
> your application from crashing.

### Example specifying rules

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use MathiasReker\PhpSvgOptimizer\Services\SvgOptimizerService;

try {
    $svgOptimizer = SvgOptimizerService::fromFile('path/to/source.svg')
        ->withRules(
            convertColorsToHex: true,
            flattenGroups: true,
            minifySvgCoordinates: true,
            minifyTransformations: true,
            removeComments: true,
            removeDefaultAttributes: true,
            removeDeprecatedAttributes: true,
            removeDoctype: true,
            removeEnableBackgroundAttribute: true,
            removeEmptyAttributes: true,
            removeMetadata: true,
            removeTitleAndDesc: false,
            removeUnnecessaryWhitespace: true,
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

use MathiasReker\PhpSvgOptimizer\Services\SvgOptimizerService;

try {
    $svgOptimizer = SvgOptimizerService::fromFile('path/to/source.svg')
        ->optimize()
        ->saveToFile('path/to/output.svg');

    $metaData = $svgOptimizer->getMetaData();

    echo sprintf('Optimized size: %d bytes%s', $metaData->getOptimizedSize(), \PHP_EOL);
    echo sprintf('Original size: %d bytes%s', $metaData->getOriginalSize(), \PHP_EOL);
    echo sprintf('Size reduction: %d bytes%s', $metaData->getSavedBytes(), \PHP_EOL);
    echo sprintf('Reduction percentage: %s %%%s', $metaData->getSavedPercentage(), \PHP_EOL);
} catch (\Exception $exception) {
    echo $exception->getMessage();
}

```

### Example parsing from a file and returning the content using default rules

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use MathiasReker\PhpSvgOptimizer\Services\SvgOptimizerService;

try {
    $svgOptimizer = SvgOptimizerService::fromFile('path/to/source.svg')
        ->optimize();

    echo sprintf('Get content: ', $svgOptimizer->getContent(), \PHP_EOL);
     
    $metaData = $svgOptimizer->getMetaData();

    echo sprintf('Optimized size: %d bytes%s', $metaData->getOptimizedSize(), \PHP_EOL);
    echo sprintf('Original size: %d bytes%s', $metaData->getOriginalSize(), \PHP_EOL);
    echo sprintf('Size reduction: %d bytes%s', $metaData->getSavedBytes(), \PHP_EOL);
    echo sprintf('Reduction percentage: %s %%%s', $metaData->getSavedPercentage(), \PHP_EOL);
} catch (\Exception $exception) {
    echo $exception->getMessage();
}
```

### Example parsing from a string and returning the content using default rules

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use MathiasReker\PhpSvgOptimizer\Services\SvgOptimizerService;

try {
    $svgOptimizer = SvgOptimizerService::fromString('<svg>...</svg>')
        ->optimize();

    echo sprintf('Content: ', $svgOptimizer->getContent(), \PHP_EOL);

    $metaData = $svgOptimizer->getMetaData();

    echo sprintf('Optimized size: %d bytes%s', $metaData->getOptimizedSize(), \PHP_EOL);
    echo sprintf('Original size: %d bytes%s', $metaData->getOriginalSize(), \PHP_EOL);
    echo sprintf('Size reduction: %d bytes%s', $metaData->getSavedBytes(), \PHP_EOL);
    echo sprintf('Reduction percentage: %s %%%s', $metaData->getSavedPercentage(), \PHP_EOL);
} catch (\Exception $exception) {
    echo $exception->getMessage();
}
```

### Documentation

Static factory method to create `SvgOptimizerService` from a file path.

```php
$svgOptimizer = SvgOptimizerService::fromFile('path/to/source.svg');
```

Static factory method to create `SvgOptimizerService` from a string.

```php
$svgOptimizer = SvgOptimizerService::fromString('<svg>...</svg>');
```

#### `withRules` Method

Configure which SVG optimization rules to apply. The method accepts boolean parameters that determine whether specific
rules should be enabled or disabled.

##### Parameters:

Removes `<title>` and `<desc>` tags from the SVG:

```php
$svgOptimizer->withRules(removeTitleAndDesc: true);
```

Removes all comments from the SVG:

```php
$svgOptimizer->withRules(removeComments: true);
```

Cleans up unnecessary whitespace in the SVG:

```php
$svgOptimizer->withRules(removeUnnecessaryWhitespace: true);
```

Sorts attributes within each element:

```php
$svgOptimizer->withRules(sortAttributes: true);
```

Removes default attribute values that match common defaults:

```php
$svgOptimizer->withRules(removeDefaultAttributes: true);
```

Removes deprecated attributes from the SVG:

```php
$svgOptimizer->withRules(removeDeprecatedAttributes: true);
```

Removes `<metadata>` tags from the SVG:

```php
$svgOptimizer->withRules(removeMetadata: true);
```

Removes invisible characters from the SVG:

```php
$svgOptimizer->withRules(removeInvisibleCharacters: true);
```

Flattens nested `<g>` elements, moving their child elements up to the parent node:

```php
$svgOptimizer->withRules(flattenGroups: true);
```

Converts `rgb()` color values to hexadecimal format:

```php
$svgOptimizer->withRules(convertColorsToHex: true);
```

Minifies coordinate values by removing unnecessary precision:

```php
$svgOptimizer->withRules(minifySvgCoordinates: true);
```

Minifies transformation attributes by removing redundant values:

```php
$svgOptimizer->withRules(minifyTransformations: true);
```

Removes the SVG doctype declaration:

```php
$svgOptimizer->withRules(removeDoctype: true);
```

Removes the `enable-background` attribute from the SVG:

```php
$svgOptimizer->withRules(removeEnableBackgroundAttribute: true);
```

Removes empty attributes from the SVG:

```php
$svgOptimizer->withRules(removeEmptyAttributes: true);
```

All options are set to true by default. You can configure them individually by passing the desired values to it:

```php
$svgOptimizer->withRules(
   convertColorsToHex: true,
   flattenGroups: true,
   minifySvgCoordinates: true,
   minifyTransformations: true,
   removeComments: true,
   removeDefaultAttributes: true,
   removeDeprecatedAttributes: true,
   removeDoctype: true,
   removeEmptyAttributes: true,
   removeMetadata: true,
   removeInvisibleCharacters: true,
   removeTitleAndDesc: true,
   removeUnnecessaryWhitespace: true,
   sortAttributes: true,
);
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

### Roadmap

For a complete list of proposed features and known issues, see
the [open issues](https://github.com/MathiasReker/php-svg-optimizer/issues).

### Contributing

We welcome all contributions! If you have ideas for improvements, feel free to fork the repository and submit a pull
request. You can also open an issue. If you find this project helpful, don’t forget to give it a star!

#### Library Structure and Contribution Guide

The library implements the Strategy Pattern, where strategies are encapsulated as "rules" located in the
`/src/Services/Rules` directory.

##### Adding a New Rule

### 1. **Create the Rule**

Create a new **final class** in the `/src/Services/Rules` directory and implement the `SvgOptimizerRuleInterface`. This
class will define the logic for your rule.

### 2. **Write Tests**

Write comprehensive **unit tests** for your rule in the `/tests/Unit/Services/Rules` directory. Ensure the tests cover
various scenarios to verify the correct behavior and edge cases for your rule.

### 3. **Integrate the Rule**

- **Register the rule** in the SVG optimizer builder located at `/src/Services/SvgOptimizerService.php`.
- **Add your rule to the rule enum** in `/src/Enums/Rule.php`.
- **Include the rule in the CLI tool** by updating `/src/Commands/SvgOptimizerCommand.php`.

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

Run PHPStan:

```bash
composer phpstan
```

Run tests:

```bash
composer test
```

Format code:

```bash
composer format
```

### License

This project is licensed under the MIT License. See
the [LICENSE](https://github.com/MathiasReker/php-svg-optimizer/blob/develop/LICENSE) file for more information.

### Disclaimer

Although the tool has been thoroughly tested and is built in a way that avoids risky changes, its use is at your own
risk. We cannot guarantee that it will be fully compatible with all SVG files or workflows. It is highly recommended to
test the tool with sample SVG files and ensure compatibility with your specific use case before using it in a production
environment.
