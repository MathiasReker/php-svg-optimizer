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
| ^1.0    | ^8.2 | [current](https://github.com/MathiasReker/php-svg-optimizer) |

### Requirements

- `ext-dom`: Required PHP extension for XML handling.

### Installation

To install the library, run:

```bash
composer require mathiasreker/php-svg-optimizer
```

### Example

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use MathiasReker\PhpSvgOptimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Services\FileProvider;

$svgOptimizer = new SvgOptimizer(new FileProvider('path/to/source.svg', 'path/to/output.svg'));

$result = $svgOptimizer
    ->removeTitleAndDesc()
    ->removeComments()
    ->removeUnnecessaryWhitespace()
    ->removeDefaultAttributes()
    ->flattenGroups()
    ->convertColorsToHex()
    ->minifySvgCoordinates()
    ->minifyTransformations()
    ->build();

echo json_encode($result, \JSON_PRETTY_PRINT);
```

### Documentation

The constructor initializes the optimizer with the source SVG file and the output file:

```php
$result = new SvgOptimizer('path/to/source.svg', 'path/to/output.svg');
```

`removeTitleAndDesc` Removes `<title>` and `<desc>` tags from the SVG.

```php
$svgOptimizer->removeTitleAndDesc();
```

`removeComments` Removes all comments from the SVG.

```php
$svgOptimizer->removeComments();
```

`removeUnnecessaryWhitespace` Cleans up unnecessary whitespace in the SVG.

```php
$svgOptimizer->removeUnnecessaryWhitespace();
```

`removeDefaultAttributes` Removes default attribute values that match common defaults.

```php
$svgOptimizer->removeDefaultAttributes();
```

`flattenGroups` Flattens nested `<g>` elements, moving their child elements up to the parent node.

```php
$svgOptimizer->flattenGroups();
```

`convertColorsToHex` Converts `rgb()` color values to hexadecimal format.

```php
$svgOptimizer->convertColorsToHex();
```

`minifySvgCoordinates` Minifies coordinate values by removing unnecessary precision.

```php
$svgOptimizer->minifySvgCoordinates();
```

`minifyTransformations` Minifies transformation attributes by removing redundant values.

```php
$svgOptimizer->minifyTransformations();
```

`optimize` Finalizes the optimization process and generates the optimized SVG file.

```php
$svgOptimizer->build();
```

### Roadmap

For a complete list of proposed features and known issues, see
the [open issues](https://github.com/MathiasReker/php-svg-optimizer/issues).

### Contributing

Contributions are welcome! If you have suggestions for improvements, please fork the repository and create a pull
request. You can also open an issue with the tag "enhancement." Don’t forget to give the project a star if you find it
useful!

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
the [LICENSE](https://github.com/MathiasReker/php-svg-optimizer/blob/develop/LICENSE.txt) file for more information.
