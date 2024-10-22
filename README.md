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
operations.

### Versions & Dependencies

| Version | PHP  | Documentation                                                |
|---------|------|--------------------------------------------------------------|
| ^4.0    | ^8.2 | [current](https://github.com/MathiasReker/php-svg-optimizer) |

### Requirements

- `ext-dom`: Required PHP extension for XML handling.
- `ext-libxml`: Required PHP extension for XML error handling.

### Installation

To install the library, run:

```bash
composer require mathiasreker/php-svg-optimizer
```

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
            removeTitleAndDesc: false,
            removeComments: true,
            removeUnnecessaryWhitespace: true,
            removeDefaultAttributes: false,
            removeMetadata: true,
            flattenGroups: true,
            convertColorsToHex: true,
            minifySvgCoordinates: true,
            minifyTransformations: false,
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

use MathiasReker\PhpSvgOptimizer\Services\Providers\FileProvider;
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

use MathiasReker\PhpSvgOptimizer\Services\Providers\StringProvider;
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

### Example parsing from a directory and optimizing all SVG files using default rules (overwriting the original files)

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use MathiasReker\PhpSvgOptimizer\Services\Providers\FileProvider;
use MathiasReker\PhpSvgOptimizer\Services\SvgOptimizerService;

$totalOriginalSize = 0;
$totalOptimizedSize = 0;
$optimizedFiles = 0;

$optimizeSvg = function (string $filePath) use (&$totalOriginalSize, &$totalOptimizedSize, &$optimizedFiles): void {
    try {
        $svgOptimizer = SvgOptimizerService::fromFile($filePath)
            ->optimize()
            ->saveToFile($filePath);

        $metaData = $svgOptimizer->getMetaData();
        $totalOriginalSize += $metaData->getOriginalSize();
        $totalOptimizedSize += $metaData->getOptimizedSize();
        ++$optimizedFiles;
    } catch (\Exception) {
        // Skip the file if an exception occurs
    }
};

$directoryPath = 'path/to/directory';

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($directoryPath, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($iterator as $fileInfo) {
    if ($fileInfo->isFile() && 'svg' === $fileInfo->getExtension()) {
        $optimizeSvg($fileInfo->getPathname());
    }
}

$reduction = $totalOriginalSize - $totalOptimizedSize;
$reductionPercentage = $totalOriginalSize > 0 ? ($reduction / $totalOriginalSize) * 100 : 0;

echo sprintf('Files optimized: %d%s', $optimizedFiles, \PHP_EOL);
echo sprintf('Total size reduction: %d bytes%s', $reduction, \PHP_EOL);
echo sprintf('Total reduction percentage: %s %%%s', number_format($reductionPercentage, 2), \PHP_EOL);
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

Removes default attribute values that match common defaults:

```php
$svgOptimizer->withRules(removeDefaultAttributes: true);
```

Removes `<metadata>` tags from the SVG:

```php
$svgOptimizer->withRules(removeMetadata: true);
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

All options are set to true by default. You can configure them individually by passing the desired values to it:

```php
$svgOptimizer->withRules(
    removeTitleAndDesc: false,
    removeComments: true,
    removeUnnecessaryWhitespace: true,
    removeDefaultAttributes: false,
    removeMetadata: true,
    flattenGroups: true,
    convertColorsToHex: true,
    minifySvgCoordinates: true,
    minifyTransformations: false,
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
request. You can also open an issue and tag it with "enhancement." If you find this project helpful, don’t forget to
give it a star!

#### Library Structure and Contribution Guide

The library implements the Strategy Pattern, where strategies are encapsulated as "rules" located in the
`/src/Services/Rules` directory.

##### Adding a New Rule

1) **Create the Rule**: Add a new class in the `/src/Services/Rules` directory that implements
   the `SvgOptimizerRuleInterface`.

2) **Write Tests**: Ensure your new rule is thoroughly tested by creating corresponding test cases
   in `/tests/Services/Rules`.

3) **Integrate the Rule**: Add your new rule to the builder in `/src/Services/SvgOptimizerService.php`.

4) **Update Documentation**: Finally, update the `README.md` to reflect the addition of the new rule and its purpose.

By following these steps, you'll ensure that your contribution is well-integrated into the project.

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

PHP Coding Standards Fixer:

```bash
composer cs-fix
```

PHP Coding Standards Checker:

```bash
composer cs-check
```

PHP Stan (level 9):

```bash
composer phpstan
```

Unit tests:

```bash
composer test
```

Magic number detector:

```bash
composer magic-number-detector
```

Run all formatting tools:

```bash
composer format
```

### License

This project is licensed under the MIT License. See
the [LICENSE](https://github.com/MathiasReker/php-svg-optimizer/blob/develop/LICENSE) file for more information.
