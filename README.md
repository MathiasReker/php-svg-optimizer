<h1 align="center">PHP SVG Optimizer</h1>

[![Packagist Version](https://img.shields.io/packagist/v/MathiasReker/php-svg-optimizer.svg)](https://packagist.org/packages/MathiasReker/php-svg-optimizer)
[![Packagist Downloads](https://img.shields.io/packagist/dt/MathiasReker/php-svg-optimizer.svg?color=%23ff007f)](https://packagist.org/packages/MathiasReker/php-svg-optimizer)
[![CI status](https://github.com/MathiasReker/php-svg-optimizer/actions/workflows/ci.yml/badge.svg?branch=develop)](https://github.com/MathiasReker/php-svg-optimizer/actions/workflows/ci.yml)
[![Codacy Security Scan](https://github.com/MathiasReker/php-svg-optimizer/actions/workflows/codacy.yml/badge.svg)](https://github.com/MathiasReker/php-svg-optimizer/actions/workflows/codacy.yml)
[![Aikido Security Score](https://img.shields.io/badge/Aikido_Security-100%25-brightgreen)](https://intel.aikido.dev/packages/packagist/mathiasreker/php-svg-optimizer)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-Level%20max-blue)](#)
[![Type Coverage](https://raw.githubusercontent.com/MathiasReker/php-svg-optimizer/develop/dev/artifacts/type-coverage.svg)](#)
[![Code Coverage](https://raw.githubusercontent.com/MathiasReker/php-svg-optimizer/develop/dev/artifacts/coverage.svg)](#)
[![Mutation Score](https://raw.githubusercontent.com/MathiasReker/php-svg-optimizer/develop/dev/artifacts/mutation-score.svg)](#)
[![Mutation Coverage](https://raw.githubusercontent.com/MathiasReker/php-svg-optimizer/develop/dev/artifacts/mutation-coverage.svg)](#)
[![Tests](https://raw.githubusercontent.com/MathiasReker/php-svg-optimizer/develop/dev/artifacts/tests.svg)](#)
[![Assertions](https://raw.githubusercontent.com/MathiasReker/php-svg-optimizer/develop/dev/artifacts/assertions.svg)](#)
[![Package size](https://raw.githubusercontent.com/MathiasReker/php-svg-optimizer/develop/dev/artifacts/package-size.svg)](#)
[![Contributors](https://img.shields.io/github/contributors/MathiasReker/php-svg-optimizer.svg)](https://github.com/MathiasReker/php-svg-optimizer/graphs/contributors)
[![Forks](https://img.shields.io/github/forks/MathiasReker/php-svg-optimizer.svg?style=flat)](https://github.com/MathiasReker/php-svg-optimizer/network/members)
[![Stargazers](https://img.shields.io/github/stars/MathiasReker/php-svg-optimizer.svg?style=flat)](https://github.com/MathiasReker/php-svg-optimizer/stargazers)
[![Issues](https://img.shields.io/github/issues/MathiasReker/php-svg-optimizer.svg)](https://github.com/MathiasReker/php-svg-optimizer/issues)
[![MIT License](https://img.shields.io/github/license/MathiasReker/php-svg-optimizer.svg)](https://github.com/MathiasReker/php-svg-optimizer/blob/develop/LICENSE)
[![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat&logo=php&logoColor=white)](#)

`php-svg-optimizer` is a lightweight PHP library designed to **optimize, minify, and sanitize** SVG files.
It applies various transformations and cleanup operations while ensuring compliance with SVG 2.0 specifications.
The resulting SVGs remain visually identical to the original but are smaller, cleaner, and safer.

### Versions & Dependencies

| Version | PHP  | Documentation                                                      |
|---------|------|--------------------------------------------------------------------|
| ^9.1    | ^8.3 | [current](https://github.com/MathiasReker/php-svg-optimizer)       |
| ^8.7    | ^8.3 | [v8](https://github.com/MathiasReker/php-svg-optimizer/tree/8.7.1) |

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

1. **Command-Line Interface (CLI):** Run the optimizer directly from your terminal to process SVG files quickly. This is
   ideal for batch processing or integrating into build scripts.
2. **Standalone Package:** Use it as a PHP package in your project to optimize SVGs programmatically. This allows you to
   integrate SVG optimization directly into your application workflow or custom scripts.

## CLI tool

```bash
vendor/bin/svg-optimizer [options] process <path1> <path2> ...
```

```bash
Options:
-h , --help               Display help for the command.
-c , --config             Path to a JSON file, or a raw JSON string, with the optimization rules to enable. Without --config, --set and --with-all-rules, no rules are applied.
-d , --dry-run            Only calculate potential savings without modifying the files.
-r , --allow-risky        Explicitly enables risky rules, allowing them to be applied.
-a , --with-all-rules     Enable all non-risky rules. Use --allow-risky to include risky rules as well.
-s , --set                Comma-separated rule sets to enable (security, standards, cleanup, conversion, structure, minification). Can be repeated. Use --allow-risky to include their risky rules as well.
-q , --quiet              Suppress all output except errors.
-v , --version            Display the version of the library.
    --ansi                Force ANSI color output, even when output is not an interactive terminal.
    --no-ansi             Disable ANSI color output.

Commands:
Process                   Provide a list of directories or files to process.
```

Options can be placed anywhere before a `--` argument; everything after `--` is treated as a path. The value of
`--config` (`-c`) and `--set` (`-s`) can be given as `--config=value` or `--config value`.

`--set` enables one or more [rule sets](#rule-sets): give the set names separated by commas
(`--set=security,minification`), or repeat the option (`--set=security --set=minification`). An unknown set name is
reported as an error that lists the valid names. Rule sets add to the rules of `--config` and `--with-all-rules`.

Errors are written to stderr, also with `--quiet`. The command exits with code `1` if the arguments or the
configuration are invalid, or if any file could not be optimized, and with code `0` otherwise.

#### Examples:

```bash
vendor/bin/svg-optimizer --with-all-rules --dry-run process /path/to/svgs
vendor/bin/svg-optimizer --config=config.json process /path/to/file.svg
vendor/bin/svg-optimizer --config='{"removeInkscapeFootprints": true}' process /path/to/file.svg
vendor/bin/svg-optimizer --quiet --with-all-rules process /path/to/file.svg
vendor/bin/svg-optimizer --with-all-rules --allow-risky process /path/to/file.svg
vendor/bin/svg-optimizer --with-all-rules process /path/to/file.svg
vendor/bin/svg-optimizer --set=security,minification process /path/to/svgs
vendor/bin/svg-optimizer -s cleanup -s structure --allow-risky process /path/to/file.svg
```

#### Config file example:

```json
{
    "convertColorsToHex": true,
    "convertCssClassesToAttributes": true,
    "convertEllipseToCircle": true,
    "convertInlineStylesToAttributes": true,
    "convertOneStopGradients": false,
    "convertShapesToPath": true,
    "fixAttributeNames": false,
    "flattenGroups": true,
    "mergeDuplicateGradients": false,
    "mergeStyles": true,
    "migrateToSvg2": true,
    "minifyNumericValues": true,
    "minifyPathData": true,
    "minifyStyles": true,
    "minifyTransformations": true,
    "moveCommonAttributesToGroup": true,
    "moveGroupAttributesToElements": true,
    "removeAriaAndRole": true,
    "removeComments": true,
    "removeDataAttributes": false,
    "removeDefaultAttributes": true,
    "removeDescriptions": true,
    "removeDuplicateElements": true,
    "removeEmptyAttributes": true,
    "removeEmptyElements": true,
    "removeForeignContent": false,
    "removeHiddenElements": true,
    "removeIneffectiveAttributes": true,
    "removeInkscapeFootprints": true,
    "removeInvisibleCharacters": true,
    "removeMetadata": true,
    "removeNonStandardAttributes": false,
    "removeNonStandardTags": false,
    "removeScripts": false,
    "removeTitles": true,
    "removeUnnecessaryWhitespace": true,
    "removeUnsafeMarkup": false,
    "removeUnsafeUrls": false,
    "removeUnusedDefinitions": false,
    "removeUnusedIds": false,
    "removeUnusedNamespaces": true,
    "removeUseBombs": false,
    "removeUselessDefs": true,
    "removeWidthHeightAttributes": false,
    "sanitizeStyleSheets": false,
    "scopeSvgStyles": false,
    "sortAttributes": true,
    "sortDefsChildren": true
}
```

Each value must be the JSON boolean `true` or `false`; any other value, such as `"false"` or `0`, is reported as an
error. Rules that are left out are not applied.

The configuration can also enable [rule sets](#rule-sets) with the `sets` key, a list of set names. The sets add to
the rules enabled with `true` and to the sets given with `--set`:

```json
{
    "sets": ["cleanup"],
    "removeTitles": true
}
```

The value of `sets` must be a list of strings, and every string must name a rule set; anything else is reported as an
error.

### Example Workflow for GitHub Actions

```yaml
name: Optimize SVGs

on: [ push, pull_request ]

jobs:
    run-optimizer:
        runs-on: ubuntu-latest

        steps:
            -   uses: actions/checkout@v7
            -   uses: shivammathur/setup-php@v2
                with:
                    php-version: '8.5'
            -   run: composer install --no-dev --optimize-autoloader --no-interaction --no-progress
            -   run: php vendor/bin/svg-optimizer -a -q process /path/to/svgs
```

## Package

> It is recommended to catch exceptions when using this library.
> Doing so ensures that your application can handle unexpected input gracefully and avoid unintended crashes.
> Every exception thrown by the library implements
> `MathiasReker\PhpSvgOptimizer\Contract\Exception\SvgOptimizerExceptionInterface`, so you can catch them all at once.

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
            convertEllipseToCircle: true,
            convertInlineStylesToAttributes: true,
            convertOneStopGradients: false,
            convertShapesToPath: true,
            fixAttributeNames: false,
            flattenGroups: true,
            mergeDuplicateGradients: false,
            mergeStyles: true,
            migrateToSvg2: true,
            minifyNumericValues: true,
            minifyPathData: true,
            minifyStyles: true,
            minifyTransformations: true,
            moveCommonAttributesToGroup: true,
            moveGroupAttributesToElements: true,
            removeAriaAndRole: true,
            removeComments: true,
            removeDataAttributes: false,
            removeDefaultAttributes: true,
            removeDescriptions: true,
            removeDuplicateElements: true,
            removeEmptyAttributes: true,
            removeEmptyElements: true,
            removeForeignContent: false,
            removeHiddenElements: true,
            removeIneffectiveAttributes: true,
            removeInkscapeFootprints: true,
            removeInvisibleCharacters: true,
            removeMetadata: true,
            removeNonStandardAttributes: false,
            removeNonStandardTags: false,
            removeScripts: false,
            removeTitles: true,
            removeUnnecessaryWhitespace: true,
            removeUnsafeMarkup: false,
            removeUnsafeUrls: false,
            removeUnusedDefinitions: false,
            removeUnusedIds: false,
            removeUnusedNamespaces: true,
            removeUseBombs: false,
            removeUselessDefs: true,
            removeWidthHeightAttributes: false,
            sanitizeStyleSheets: false,
            scopeSvgStyles: false,
            sortAttributes: true,
            sortDefsChildren: true,
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

    echo sprintf('Get content: %s%s', $svgOptimizer->getContent(), \PHP_EOL);

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

    echo sprintf('Content: %s%s', $svgOptimizer->getContent(), \PHP_EOL);

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
            convertOneStopGradients: true,
            fixAttributeNames: true,
            mergeDuplicateGradients: true,
            removeDataAttributes: true,
            removeUnusedDefinitions: true,
            removeUnusedIds: true,
            removeWidthHeightAttributes: true,
            scopeSvgStyles: true,
        )
        ->allowRisky()
        ->optimize()
        ->saveToFile('path/to/output.svg');

    echo sprintf('Content: %s%s', $svgOptimizer->getContent(), \PHP_EOL);

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

### Rule sets

Rule sets enable a group of rules with the same purpose at once, so you do not have to list every rule. Each rule
belongs to at most one set, and sets can be combined with each other, with `withRules()` and with `withAllRules()`.

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use MathiasReker\PhpSvgOptimizer\Service\Facade\SvgOptimizerFacade;
use MathiasReker\PhpSvgOptimizer\Type\RuleSet;

try {
    $svgOptimizer = SvgOptimizerFacade::fromFile('path/to/source.svg')
        ->withRuleSets(RuleSet::Security, RuleSet::Minification)
        ->optimize()
        ->saveToFile('path/to/output.svg');
} catch (\Exception $exception) {
    echo $exception->getMessage();
}
```

The same sets can be enabled from the [CLI tool](#cli-tool) with `--set=security,minification`, or in a configuration
file with `"sets": ["security", "minification"]`.

| Set | `RuleSet` case | Purpose | Rules |
| --- | --- | --- | --- |
| `security` | `RuleSet::Security` | Remove scripts, event handlers, unsafe links and other active content from untrusted SVGs. | `removeUnsafeMarkup`, `removeScripts`, `removeForeignContent`, `removeUnsafeUrls`, `sanitizeStyleSheets`, `removeUseBombs` |
| `standards` | `RuleSet::Standards` | Bring the markup in line with the SVG specification. | `fixAttributeNames` (risky), `migrateToSvg2`, `removeNonStandardTags`, `removeNonStandardAttributes` |
| `cleanup` | `RuleSet::Cleanup` | Remove editor data, comments, unused definitions and content that has no effect. | `removeComments`, `removeMetadata`, `removeInkscapeFootprints`, `removeUnusedIds` (risky), `removeDataAttributes` (risky), `removeInvisibleCharacters`, `removeDefaultAttributes`, `removeWidthHeightAttributes` (risky), `removeEmptyAttributes`, `removeUnusedDefinitions` (risky), `removeIneffectiveAttributes`, `removeHiddenElements`, `removeUselessDefs`, `removeDuplicateElements`, `removeEmptyElements`, `removeUnusedNamespaces` |
| `conversion` | `RuleSet::Conversion` | Convert styles, colors, gradients and shapes into shorter equivalents. | `convertCssClassesToAttributes`, `convertInlineStylesToAttributes`, `convertColorsToHex`, `convertOneStopGradients` (risky), `convertEllipseToCircle`, `convertShapesToPath` |
| `structure` | `RuleSet::Structure` | Restructure styles, gradients and groups. | `mergeStyles`, `mergeDuplicateGradients` (risky), `flattenGroups`, `moveGroupAttributesToElements`, `moveCommonAttributesToGroup` |
| `minification` | `RuleSet::Minification` | Write numbers, paths, styles and whitespace in their shortest form and order the markup. | `minifyStyles`, `minifyNumericValues`, `minifyTransformations`, `minifyPathData`, `removeUnnecessaryWhitespace`, `sortDefsChildren`, `sortAttributes` |

Risky rules belong to the set of their purpose, but, like with `withAllRules()`, they are only applied when risky rules
are allowed with `allowRisky()` (or `--allow-risky`), whether `allowRisky()` is called before or after
`withRuleSets()`. Without it, the other rules of the set are applied and no exception is thrown.

`removeTitles`, `removeDescriptions` and `removeAriaAndRole` are deliberately in no set: they remove accessibility
information, so they are only applied when you enable them yourself with `withRules()` or in the configuration.
`scopeSvgStyles` (risky) is in no set either: it renames ids and class names, which is only wanted when several SVGs
share one page.

### Security: Sanitizing Untrusted SVGs

When accepting SVG uploads from users or other untrusted sources, it is **critical** to sanitize them to prevent
Cross-Site Scripting (XSS) attacks. Malicious SVGs can contain scripts, event handlers, or external references that can
execute code in the user's browser.

The `php-svg-optimizer` provides a powerful set of rules to mitigate these risks. Six security rules each remove one
kind of unsafe content: `removeScripts` (scripts and event handlers), `removeUnsafeUrls` (links and URLs that run
script or load external content), `removeForeignContent` (HTML, embedded documents and names that an HTML parser reads
differently), `sanitizeStyleSheets` (unsafe CSS), `removeUseBombs` (`<use>` cycles and explosions) and
`removeUnsafeMarkup` (processing instructions and unsafe comments). Enable all six to sanitize untrusted SVGs; each rule
also works on its own. For a stricter policy, you can combine them with `removeNonStandardTags` and
`removeNonStandardAttributes`.

Parsing is hardened for every rule set: external entities and DTDs are never loaded, and a document whose internal
entities would expand to more than 512 KiB, or more than five times the size of the document if that is larger, is
rejected on every libxml version. Elements may be nested at most 255 levels deep, the limit of libxml. A document that
cannot be parsed is rejected with an `SvgValidationException`; its previous exception says why, for example "Entity
expansion exceeds the allowed size." or "Failed to load DOMDocument: Excessive depth in document: 256 use
XML_PARSE_HUGE option on line 1, column 809.".

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use MathiasReker\PhpSvgOptimizer\Service\Facade\SvgOptimizerFacade;

try {
    $svgOptimizer = SvgOptimizerFacade::fromFile('path/to/source.svg')
        ->withRules(
            removeForeignContent: true,
            removeNonStandardAttributes: true,
            removeNonStandardTags: true,
            removeScripts: true,
            removeUnsafeMarkup: true,
            removeUnsafeUrls: true,
            removeUseBombs: true,
            sanitizeStyleSheets: true,
        )
        ->optimize()
        ->saveToFile('path/to/output.svg');
} catch (\Exception $exception) {
    echo $exception->getMessage();
}
```

---

### Documentation

### SvgOptimizerFacade

Static factory method to create `SvgOptimizerFacade` from a file path.

```php
$svgOptimizer = SvgOptimizerFacade::fromFile('path/to/source.svg');
```

Static factory method to create `SvgOptimizerFacade` from a string.

```php
$svgOptimizer = SvgOptimizerFacade::fromString('<svg>...</svg>');
```

---

### `withRules`

Configure which SVG optimization rules to apply. The method accepts boolean parameters that determine whether specific
rules should be enabled or disabled.

```php
$svgOptimizer->withRules();
```

Rules always run in the same order, whatever order they are enabled in: untrusted content is sanitized first, styles
are turned into attributes before attributes are cleaned up, and values are minified before shapes are converted, so
optimizing an optimized file again leaves it (almost always) unchanged. A rule enabled more than once, for example with
both `withAllRules()` and `withRules()`, runs once.

---

## Parameters

Below is a detailed description of each available optimization rule. Each rule focuses on either reducing file size,
improving consistency, modernizing SVG output, or increasing security.

Rules that are not marked risky are lossless: the optimized SVG renders the same in current browsers, keeps every id,
link and animation that can be reached from inside or outside the document (sprite sheets keep working), and no number
is rounded. A few rules change the SVG on purpose, and say so in their description: the security rules
`removeScripts`, `removeUnsafeUrls`, `removeForeignContent`, `sanitizeStyleSheets`, `removeUseBombs` and
`removeUnsafeMarkup` remove unsafe content, also when it is visible; and `removeTitles`, `removeDescriptions` and
`removeAriaAndRole` remove accessibility information.

The same holds when the SVG is used from outside: inlined in an HTML page or referenced with
`<use href="file.svg#id">`. The root element then inherits properties such as `fill`, `stroke` or `stroke-width` from
the page or from the `<use>` element, so a rule never assumes their initial values at the root: a value is only treated
as known when an element of the document sets it. `stroke="none"` on a shape is kept unless an ancestor in the document
also sets `stroke="none"`, for example.

Some rules are marked **risky**: they rely on assumptions about how the SVG is used that the optimizer cannot check,
such as no external CSS, JavaScript or `file.svg#id` reference depending on what they remove. Each risky rule says
under **Why it is risky** what it can break. Risky rules only run after `allowRisky()`.

---

### `convertColorsToHex`

Normalizes color definitions to their shortest exact notation. Functional formats (such as `rgb()` or `rgba()`) are
converted to hexadecimal, `#aabbcc` is shortened to `#abc`, and a CSS color name and its hexadecimal value are swapped
when the other form is shorter (for example `#ff0000` becomes `red` and `white` becomes `#fff`). `currentColor`,
`transparent`, `none` and `url()` references are left as they are.

```php
$svgOptimizer->withRules(convertColorsToHex: true);
```

---

### `convertCssClassesToAttributes`

Replaces CSS class-based styling with equivalent inline SVG attributes on each affected element. Declarations that have
to stay CSS, such as `!important` ones, ones that other rules may override, and properties that an element already sets
as an attribute with another value, are kept in the style sheet. Style elements for other media, of another type, with
a `title` (which may make them alternative style sheets) or with content other than text are kept as they are. Only
rules whose selector list consists of class selectors with valid identifiers are converted, and a property that two
rules set on the same element with different values stays CSS. A style sheet is rewritten only when a declaration
became an attribute. The document is left untouched when a style sheet outside the document may apply, or when a
script, an event handler attribute or an animation may change the classes.
The result renders the same as a standalone SVG. When the SVG is inlined in an HTML page, presentation attributes have
a lower priority than any CSS rule of the page, while the class rules they replace may have won over page rules with a
lower specificity.

```php
$svgOptimizer->withRules(convertCssClassesToAttributes: true);
```

---

### `convertEllipseToCircle`

Converts `<ellipse>` elements whose `rx` and `ry` are equal into `<circle>` elements with a single `r`. The radius is
copied as written, so no value is recalculated. Ellipses with child elements or a `style` attribute, and documents with
a style sheet, a script or an event handler attribute, are kept.

```php
$svgOptimizer->withRules(convertEllipseToCircle: true);
```

---

### `convertInlineStylesToAttributes`

Extracts individual style declarations from inline `style` attributes and converts them into their corresponding SVG
presentation attributes. This makes attribute-level optimizations more effective downstream. Declarations that are
important, repeated, overridden by a related property, or for a property that the element already sets as an attribute
with another value stay in the `style` attribute, and documents with a style sheet (a `<style>` element, an
`xml-stylesheet` instruction or a linked style sheet) are left untouched. An element is only changed when its markup
does not grow; the declarations that stay are written without spaces.
The result renders the same as a standalone SVG. When the SVG is inlined in an HTML page, presentation attributes have
a lower priority than any CSS rule of the page, while an inline style wins over page rules that are not `!important`.

```php
$svgOptimizer->withRules(convertInlineStylesToAttributes: true);
```

---

### `convertOneStopGradients` (**risky**)

Replaces a gradient that has a single `<stop>` with that stop's color where it is used as a `fill` or `stroke`, and
removes the gradient. Gradients referenced in any other way or by other documents (see the sprite targets of
`removeUnusedDefinitions`), stops with an opacity, a `style` or a color that is not a plain hex or named color,
gradients with a `style` attribute or a `gradientTransform` other than translations and rotations (a transform that
cannot be inverted paints nothing), and documents with a style sheet, a script or an event handler attribute are left
untouched. A gradient in object bounding box units is only replaced on rects, circles and ellipses, as it is not rendered on, for example, a straight line.

**Why it is risky:** the gradient and its `id` are removed. CSS, JavaScript or a `file.svg#id` reference in the page that embeds the SVG that uses the gradient breaks.

```php
$svgOptimizer->withRules(convertOneStopGradients: true)->allowRisky();
```

---

### `convertShapesToPath`

Converts `<rect>`, `<line>`, `<polyline>` and `<polygon>` elements into `<path>` elements when that makes the file
smaller. The path data reuses the original numbers (a rect is drawn with relative moves), so no coordinate is
recalculated or rounded. Rounded rects, values with units or a trailing decimal point, `points` lists that are invalid
or have an odd number of coordinates, shapes with child elements or a `style` attribute, and documents with a style
sheet, a `<marker>`, a script or an event handler attribute are kept as they are.

```php
$svgOptimizer->withRules(convertShapesToPath: true);
```

---

### `fixAttributeNames` (**risky**)

Normalizes SVG attribute names to their canonical, specification-compliant casing and hyphenation. Attribute names are
matched against a known set of valid SVG attributes and corrected where case differences or missing hyphens occur, and
an attribute is left alone when the element already has the canonical one or already defines its name, such as the SVG 2
`playbackorder` next to the SVG Tiny `playbackOrder`. Event handler attributes in another case, such as `onLoad`, are
kept as they are, since renaming them would make an inert attribute run a script. Foreign content and attributes in a
namespace other than XLink are left untouched.

This rule changes rendering on purpose: browsers ignore a misspelled attribute, and renaming it makes it take effect.
For example, `viewbox` becomes `viewBox` and `Fill` becomes `fill`, so an image that ignored those attributes until now
will be scaled or painted by them.

**Why it is risky:** browsers ignore a misspelled attribute, so renaming it changes rendering: `viewbox` becomes `viewBox` and the image is scaled, `Fill` becomes `fill` and the shape is painted. An image that looked right only because those attributes were ignored changes.

```php
$svgOptimizer->withRules(fixAttributeNames: true)->allowRisky();
```

---

### `flattenGroups`

Removes unnecessary nested `<g>` (group) elements by moving their child elements directly into the parent, together with
the group's inherited presentation attributes and transform. Groups with an `id`, a class, a style or attributes that do
not inherit, groups inside clip paths and `<switch>`, groups whose children use `inherit`, `unset`, `revert`, a custom
property or a font-relative unit, groups whose children override an inherited attribute with a value that the browser
may reject (which would fall back to the group value), and groups whose transform is invalid or cannot be merged are
kept. Documents with a style sheet, a script or an event handler attribute are left untouched. The change is reverted
when it would make the output larger.

```php
$svgOptimizer->withRules(flattenGroups: true);
```

---

### `mergeDuplicateGradients` (**risky**)

Merges gradients that are exactly identical (same element, same attributes apart from the `id`, same stops, and equal
ancestors) into the first one, and points every `url(#id)`, `href` and `xlink:href` reference at it. Gradients that
are animated, referenced in a way that cannot be rewritten, or that other documents may reference (see the sprite
targets of `removeUnusedDefinitions`), such as the gradients of a resource library, are kept, and documents with a
style sheet, a script or an event attribute are left untouched.

**Why it is risky:** the duplicate gradients and their ids are removed. CSS, JavaScript or a `file.svg#id` reference in the page that embeds the SVG that uses a removed gradient breaks.

```php
$svgOptimizer->withRules(mergeDuplicateGradients: true)->allowRisky();
```

---

### `mergeStyles`

Merges all `<style>` elements into the first one, keeping the CSS in document order, and removes empty `<style>`
elements. Style elements with a different `media` attribute are wrapped in `@media`. The document is left untouched
when a style element has other attributes, a type other than `text/css`, content other than text, sits in foreign
content or conditional processing, or contains `@import`, `@charset` or `@namespace`.

```php
$svgOptimizer->withRules(mergeStyles: true);
```

---

### `migrateToSvg2`

Migrates SVG 1.1 and SVG Tiny 1.2 markup to SVG 2 without changing how current browsers render it. `xlink:href`
becomes `href`, also in animations, and `xlink:show="new"` becomes `target="_blank"`. Elements and attributes that
SVG 2 dropped and browsers ignore are removed: SVG fonts, `tref`, `altGlyph`, `animateColor`, `cursor`,
`color-profile`, the SVG Tiny elements and attributes, `xml:id`, and properties such as `version`, `baseProfile`,
`requiredFeatures`, `kerning` and `enable-background`, also from inline styles. References to dropped elements fall
back to their fallback paint, and the SVG Tiny timeline attributes are renamed to their SVG 2 forms. Unused `xlink`
and XML Events namespace declarations are removed. Foreign content and anything author CSS may select are left
untouched. Dropped elements that have an `id`, that a `<switch>` may render, or that contain an element with an `id`, a style
sheet, a script or an animation, are kept, and no element is removed when a style sheet uses sibling combinators or structural
pseudo-classes.

It also removes the attributes that SVG 2 deprecated:

Removes attributes that SVG 2 deprecated and browsers ignore: `version`, `baseProfile`, `contentScriptType`,
`contentStyleType`, `externalResourcesRequired`, `requiredFeatures`, `zoomAndPan`, `viewTarget`,
`glyph-orientation-horizontal`, `glyph-orientation-vertical` and `xml:base`, and the SVG 1.1 view attributes. It
migrates the XLink attributes: `xlink:href` becomes `href` (also in animations),
`xlink:show="new"` on a link becomes `target="_blank"`, `xlink:title` on a link becomes a `<title>` child unless the
link already has one or a style sheet uses sibling combinators or structural pseudo-classes, `xlink:title` on other
elements, which browsers ignore, and the other XLink attributes are removed, and an unused `xlink` namespace
declaration is removed.
`xml:lang` becomes `lang`. Foreign content, attributes that a style sheet may select, and documents with an external
style sheet are left untouched.

And it removes the `enable-background` attribute and inline style declaration, which editors such as Illustrator add to almost
every file and current browsers ignore. Foreign content, an attribute or `style` that a style sheet may select, and
documents with an external style sheet are left untouched.

```php
$svgOptimizer->withRules(migrateToSvg2: true);
```

---

### `minifyNumericValues`

Shortens the notation of single-number attribute values such as `opacity`, `stroke-width`, `x` or `font-size`: leading
and trailing zeros and a `+` sign are dropped, and `px` is removed from lengths, as one pixel equals one user unit. `px`
is kept where the attribute is a plain number on that element (such as `dx`/`dy` on `<feOffset>` or `x`/`y` on
`<fePointLight>`), and integer attributes such as `numOctaves` are only shortened when written as plain integers. Only
the notation changes; values are never rounded. Numbers with a trailing decimal point (`10.`), which browsers reject,
are left as they are, and so is the document when a style sheet may select elements by an attribute value.

It also shortens number lists:

Shortens the notation of number lists such as `viewBox`, `points`, `stroke-dasharray`, `keyTimes` and the `values` of
numeric animations. Numbers are shortened without rounding and lists use the fewest separators the attribute allows.
Values with units, percentages, keywords or a number with a trailing decimal point are left untouched, and so is the
document when a style sheet may select elements by an attribute value.

```php
$svgOptimizer->withRules(minifyNumericValues: true);
```

---

### `minifyPathData`

Rewrites path data (`d`) in its shortest equivalent form: repeated command letters and unneeded separators are dropped,
number literals are shortened, and relative linetos along one axis become `h`/`v`. Coordinates are never recalculated or
rounded, and path data that is not valid is left untouched. The document is left untouched when path data is animated,
as interpolation needs matching commands, or when a style sheet may select elements by an attribute value.

```php
$svgOptimizer->withRules(minifyPathData: true);
```

---

### `minifyStyles`

Minifies the CSS inside `<style>` elements without restructuring it: comments (except `/*! ... */`) and unnecessary
whitespace are removed, and redundant semicolons are dropped. Strings, `url()` values, escapes and numbers are never
changed. A style sheet that cannot be read with confidence, for example one with an unclosed string or comment, is
left untouched.

```php
$svgOptimizer->withRules(minifyStyles: true);
```

---

### `minifyTransformations`

Shortens `transform`, `gradientTransform` and `patternTransform` values without recalculating them: numbers and
separators are written in their shortest form, identity transformations such as `translate(0)`, `scale(1)` and
`rotate(0)` are removed, default arguments are dropped (`translate(10 0)` becomes `translate(10)`), and a matrix that
only translates or scales is written as `translate()` or `scale()`. An identity `gradientTransform` or
`patternTransform` on an element with `href` or `xlink:href` is kept (as `scale(1)` when shorter), since removing it
would inherit the transform of the referenced element. Values that do not parse as a transform list are left untouched,
and so is the document when a style sheet may select elements by an attribute.

```php
$svgOptimizer->withRules(minifyTransformations: true);
```

---

### `moveCommonAttributesToGroup`

Moves inheritable presentation attributes, such as `fill`, `stroke` or `font-family`, that every element child of a
`<g>` sets to the same value onto the group, so the value is written once. Attributes that are animated, declared in an
inline style, use `inherit` or font-relative units, and children that are referenced by id, are left alone. `transform`
and `color` are never moved. The document is left untouched when it has a style sheet, a script or an event handler
attribute.

```php
$svgOptimizer->withRules(moveCommonAttributesToGroup: true);
```

---

### `moveGroupAttributesToElements`

Moves the `transform` of a `<g>` onto its children by writing it in front of each child's own transform, without any
arithmetic, so the group can be flattened afterwards. It only applies when every element child is a shape, `path`,
`text` or `use`, the group has no `id`, `clip-path`, `mask` or `filter`, and both transforms are valid transform lists.
Documents with a style sheet, a script or an event handler attribute are left untouched. The change is reverted when it would make the output larger.

```php
$svgOptimizer->withRules(moveGroupAttributesToElements: true);
```

---

### `removeAriaAndRole`

Removes ARIA (`aria-*`) and `role` attributes. Rendering stays the same, but by design the image loses its accessible
role, name and description (such as `role="img"` and `aria-label`), so screen readers no longer announce it as before.
Only use this rule for decorative SVGs that are hidden from assistive technologies or labelled by the page that embeds
them. Attributes used in an attribute selector of a style sheet are kept, and documents with an external style sheet are
left untouched.

```php
$svgOptimizer->withRules(removeAriaAndRole: true);
```

---

### `removeComments`

Deletes all non-essential XML comments from the SVG document, excluding legally required comments if applicable. This
eliminates developer notes and editor artifacts that do not affect rendering, reducing file size.

```php
$svgOptimizer->withRules(removeComments: true);
```

---

### `removeDataAttributes` (**risky**)

Removes `data-*` attributes from elements. These attributes are often used for application-specific metadata or
scripting hooks, and removing them can significantly reduce file size. Attributes that a style sheet of the document
names, for example in an attribute selector such as `[data-state="open"]` or in `attr()`, are kept.

**Why it is risky:** scripts, CSS in the page that embeds the SVG, analytics, testing tools (`data-testid`) and design-tool round trips may read `data-*` attributes. Those break when the attributes are gone.

```php
$svgOptimizer->withRules(removeDataAttributes: true)->allowRisky();
```

---

### `removeDefaultAttributes`

Removes stroke attributes whose value is the default (`stroke="none"`, `stroke-width="1"`, `stroke-linecap="butt"`,
`stroke-linejoin="miter"` and `stroke-miterlimit="4"`) when the element would inherit the same value: the nearest
ancestor that sets the attribute sets the same value, no ancestor in between sets the property in its inline style or
can be referenced by id, and that ancestor is inside the document (the root inherits from the page or `<use>`).
Attributes that are animated or that the element's own inline style overrides are kept, and documents with a style sheet
are left untouched.

It also removes default type attributes:

Removes the `type` attribute of `<style>` when it is `text/css` and of `<script>` when it is a JavaScript MIME type, as
these are the defaults. Other values, such as `module`, are kept, and so is the type of a script with a `language`
attribute, which would decide the script language without it.

And `xml:space="default"` where it has no effect:

Removes `xml:space="default"` where it has no effect, that is, when no ancestor sets `xml:space="preserve"` and no
CSS `white-space` could be affected by it.

```php
$svgOptimizer->withRules(removeDefaultAttributes: true);
```

---

### `removeDescriptions`

Removes `<desc>` elements. Rendering stays the same, but by design the image loses its accessible
description: screen readers no longer announce it. Only use this
rule for decorative SVGs, or when the page that embeds the image provides the text. Elements in foreign content,
children of a `<switch>`, and elements that contain an element with an `id`, an animation, a script or a style sheet
are kept, and nothing is removed when a style sheet uses sibling combinators or structural pseudo-classes.

```php
$svgOptimizer->withRules(removeDescriptions: true);
```

---

### `removeDuplicateElements`

Detects and removes identical duplicate elements that do not contribute additional visual output, such as repeated
`<stop>`, `<title>` or `<desc>` elements and duplicate children of `<defs>`. Elements that are or contain an animation,
a script or a style sheet are kept, as a copy can add to the effect. Nothing is removed when a style sheet uses sibling
combinators or structural pseudo-classes (such as `g + rect` or `:nth-child()`), whose matches depend on the surrounding
elements.

```php
$svgOptimizer->withRules(removeDuplicateElements: true);
```

---

### `removeEmptyAttributes`

Removes attributes that have empty values. Such attributes add noise to the markup without affecting rendering and can
safely be removed to produce cleaner output. Empty attributes that mean something, such as the conditional processing
attributes (`systemLanguage`, `requiredExtensions`, `requiredFeatures`), `lang`, `href`, `download`, `crossorigin` and
`autofocus`, attributes that a style sheet selects, attributes of animations, and attributes on elements outside the SVG
namespace, such as `<foreignObject>` content, are kept. Documents with an external style sheet (an `xml-stylesheet`
instruction, a `<link>` element or an `@import` rule) are left untouched.

```php
$svgOptimizer->withRules(removeEmptyAttributes: true);
```

---

### `removeEmptyElements`

Removes container elements such as `<defs>`, `<g>`, `<symbol>`, `<mask>`, `<clipPath>`, `<pattern>`, `<marker>` and
`<switch>` when they have no content. Containers that become empty are removed as well. Containers with an `id`, a
`role`, `aria-*` attributes, a `tabindex`, `autofocus` or an event handler, which can be referenced, are exposed to
assistive technology or can take focus, children of a `<switch>`, and groups with a `filter`, which can paint on its
own, are kept. Nothing is removed when a style sheet may apply a `filter`, or uses sibling combinators or structural
pseudo-classes (such as `g + rect` or `:nth-child()`), whose matches depend on the surrounding elements, or when the
document has a script or an event handler attribute, which may fill or query the empty elements.

It also removes empty text elements:

Removes `<text>` and `<tspan>` elements that contain no text at all, and `<tref>` elements without a reference. These
elements have no visual impact and can safely be eliminated to reduce file size. Whitespace-only elements are kept,
since their whitespace may render as a space between the surrounding text, and so are elements with an `id`, a `role`,
`aria-*` attributes, a `tabindex` or an event handler, and children of a `<switch>`. Nothing is removed when a style
sheet uses sibling combinators or structural pseudo-classes, or when the document has a script or an event handler
attribute.

```php
$svgOptimizer->withRules(removeEmptyElements: true);
```

---

### `removeForeignContent`

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

```php
$svgOptimizer->withRules(removeForeignContent: true);
```

---

### `removeHiddenElements`

Removes elements that cannot render anything: elements with `display="none"`, elements with `opacity="0"`, and
shapes whose fill and stroke both resolve to `none` through attributes set inside the document (the root inherits its
fill and stroke from the page or `<use>`). Elements inside definitions, clip paths, masks, patterns, markers
and symbols, elements with an `id` or a referenced descendant, animated elements, links and interactive or accessible
content are kept. Invisible elements inside an element with a `transform-box` or `transform-origin` (whose reference box
they help size) are kept, and so are transparent elements with a `<title>` (a tooltip), a `cursor` or `pointer-events`
in their subtree, as they still react to the pointer. Documents with a style sheet, a script or an event handler
attribute (which may show, resize or query the elements) are left untouched.

It also removes shapes with a zero size:

Removes shapes the SVG specification says are not rendered: circles and ellipses with a zero radius, rects with a
zero width or height, and paths, polylines and polygons without any data. Shapes with an `id`, child elements or a
`style` attribute, and documents with a `<style>` element, a script or an event handler attribute, are kept.

```php
$svgOptimizer->withRules(removeHiddenElements: true);
```

---

### `removeIneffectiveAttributes`

Removes attributes that have no effect.

Removes stroke and fill attributes that have no effect: the other `stroke-*` attributes when the stroke is `none`,
the stroke attributes except `stroke-width` when the stroke width is zero, and `fill-opacity` and `fill-rule` when the
fill is `none`. The stroke, stroke width or fill counts only when an element of the document sets it, as the root
inherits them from the page or `<use>`. Elements are skipped when a descendant could re-enable the stroke or fill, and
documents with a style sheet, markers or `pointer-events` are left untouched.

Removes attributes from `<g>` elements that are neither inherited by the children nor apply to the group itself, such
as `x`, `y`, `width`, `height`, `stop-color` or `flood-color`. Animated attributes are kept, and documents with a style
sheet are left untouched.

Removes text-only presentation attributes, such as `font-family`, `font-size`, `letter-spacing` or `text-anchor`, from
elements whose whole subtree cannot render text. `direction`, `writing-mode` and `unicode-bidi` are kept on the root
element, where they also set the direction of the viewport. Documents that use font-relative units or have a style sheet
are left untouched.

```php
$svgOptimizer->withRules(removeIneffectiveAttributes: true);
```

---

### `removeInkscapeFootprints`

Strips editor-specific metadata, attributes, and elements that are added during export but are not required for
rendering. This covers Inkscape, Sodipodi, Adobe Illustrator, Sketch, Serif and Microsoft Visio, matched by namespace
URI, including Illustrator's private data in a `<foreignObject>` that requires an Illustrator extension. Editor
attributes that a style sheet selects are kept, editor elements are kept when a style sheet uses sibling combinators or
structural pseudo-classes, and documents with an external style sheet are left untouched.

```php
$svgOptimizer->withRules(removeInkscapeFootprints: true);
```

---

### `removeInvisibleCharacters`

Removes invisible characters (soft hyphens, zero-width spaces and joiners, and the line and paragraph separators) from
text that is not rendered or read, such as whitespace between elements. Text inside `<text>`, `<title>`, `<desc>`,
`<style>`, `<script>`, `<foreignObject>` and elements outside the SVG namespace, and attribute values, are left as they
are. A text node that would become empty is kept when a style sheet uses structural pseudo-classes such as `:empty`.

```php
$svgOptimizer->withRules(removeInvisibleCharacters: true);
```

---

### `removeMetadata`

Deletes `<metadata>` elements, which often contain authoring information, editor data, or descriptive metadata not
required for rendering. Metadata in foreign content, children of a `<switch>` (which a switch may render instead of a
later child), and metadata that contains an element with an `id`, an animation, a script or a style sheet are kept.
Nothing is removed when a style sheet uses sibling combinators or structural pseudo-classes.

```php
$svgOptimizer->withRules(removeMetadata: true);
```

---

### `removeNonStandardAttributes`

Removes SVG attributes that are non-standard, unsafe, or not part of the official SVG specification.
Each SVG element keeps only the attributes the specification defines for it, plus the shared attribute groups that
apply to it (core attributes such as `id`, `lang`, `tabindex`, `nonce`, `part` and `slot`, presentation, ARIA,
conditional processing, animation, filter primitive and similar), so for example `points` is removed from a `<rect>`.
Elements the specification does not define fall back to the full list of known SVG attributes, without HTML-only
attributes such as `src`, `srcset` and `action`. Event handler attributes (`on*`), which `removeScripts` removes, are always kept, and `xml:base` is always removed.
Prefixed attributes are matched by namespace: only known `xlink:`, `xml:` and `ev:` attributes are kept, and attributes
bound to any other namespace are removed. `data-*` attributes are kept when their name is valid, and so is any attribute
that a style sheet selects. Elements outside the SVG namespace, the content of a `<foreignObject>`, such as XHTML, and
documents with an external style sheet are left untouched.

```php
$svgOptimizer->withRules(removeNonStandardAttributes: true);
```

---

### `removeNonStandardTags`

Removes SVG elements that are not part of the SVG specification, together with their content: browsers do not render the
children of an unknown element, so removing them does not change rendering. Unknown elements that have an `id` or
contain one, that are or contain an animation, a script or a style sheet, and children of a `<switch>` (which a switch
may render instead of a later child) are kept. Elements outside the SVG namespace, and the content of a
`<foreignObject>`, such as XHTML, are left untouched, and nothing is removed when a style sheet uses sibling combinators
or structural pseudo-classes.

```php
$svgOptimizer->withRules(removeNonStandardTags: true);
```

---

### `removeScripts`

Removes script: `<script>` elements, event handler attributes (every attribute whose name starts with `on`, such as
`onclick` or `onload`, in any letter case and with any prefix), the SVG Tiny `<handler>` and `<listener>` elements,
and the `name` attribute, which can clobber the DOM of the page the SVG is inlined in. It also removes animations whose
`attributeName` targets an event handler, `name` or a link (`href` or `xlink:href`), because animating a link can turn
it into a `javascript:` URL; the animation is removed with its children. XML Events attributes are removed by
`removeForeignContent`, and animation values that set an unsafe URL by `removeUnsafeUrls`.

**Visible content it removes on purpose:** interactive SVGs lose their scripts, and animations of links are removed.

```php
$svgOptimizer->withRules(removeScripts: true);
```

---

### `removeTitles`

Removes `<title>` elements. Rendering stays the same, but by design the image loses its accessible name: screen readers no
longer announce it, and browsers no longer show the title as a tooltip. Only use this
rule for decorative SVGs, or when the page that embeds the image provides the text. Elements in foreign content,
children of a `<switch>`, and elements that contain an element with an `id`, an animation, a script or a style sheet
are kept, and nothing is removed when a style sheet uses sibling combinators or structural pseudo-classes.

```php
$svgOptimizer->withRules(removeTitles: true);
```

---

### `removeUnnecessaryWhitespace`

Collapses runs of whitespace in attribute values to a single space and trims them, and removes the whitespace around
`;`, `:` and `,` in inline styles, outside quoted strings. Values whose whitespace is content are left as they are:
`id`, `href`, `download`, `target`, `title`, `attributeName`, `unicode`, `lang`, the filter primitive names `in`, `in2`
and `result`, `data-*` and event handler attributes, and inline styles with a CSS escape. Foreign content is left
untouched, and so is the document when a style sheet may select elements by an attribute value.

```php
$svgOptimizer->withRules(removeUnnecessaryWhitespace: true);
```

---

### `removeUnsafeMarkup`

Removes every processing instruction (such as `<?xml-stylesheet ?>`, which can load a style sheet or an XSLT
transformation, or `<?php ?>`), and the comments that would end early or turn into markup when the SVG is inlined in
HTML: comments that start with `>` or `->`, or contain `<` followed by a letter, a digit, `_`, `/`, `!` or `?`. Other
comments are kept.

```php
$svgOptimizer->withRules(removeUnsafeMarkup: true);
```

---

### `removeUnsafeUrls`

Removes attributes with a URL that can run script, load a resource from elsewhere or embed a document: a
`javascript:`, `vbscript:`, `data:` (other than a base64 PNG, GIF, JPEG, WebP, AVIF or SVG image), `http:`, `https:`,
`file:` or other unsafe protocol, or a network-path reference (`//host` or `\\host`). It checks `href`, `xlink:href`,
`src`, the candidates of `srcset` and the HTML URL attributes (such as `action`, `formaction`, `poster` and `data`);
the `url()` values of `fill`, `stroke`, `filter`, `clip-path`, `mask`, the marker properties, `cursor`, `style` and
similar attributes; and the animation values `values`, `from`, `to`, `by`, `begin` and `end`. It also removes `data:`
links on elements other than `<image>` and `<feImage>`. URLs are checked after decoding entities, CSS escapes and
comments, and ignoring whitespace, control and invisible characters and full-width letters, so obfuscated URLs are
caught. The element keeps its other attributes; `removeForeignContent` unwraps links with an unsafe URL instead.

**Visible content it removes on purpose:** images, fills and links that load external resources.

```php
$svgOptimizer->withRules(removeUnsafeUrls: true);
```

---

### `removeUnusedDefinitions` (**risky**)

Removes definitions that nothing renders: gradients, patterns, clip paths, masks, filters, markers and other elements
with an `id` inside `<defs>` that no rendered content references, following chains of references. Style sheets,
scripts, fonts and animations are kept, and a `<defs>` left empty is removed.

Sprite sheets keep working: sprite targets, and everything they reference, are never removed. Sprite targets are the
ids that another document may address through `sprite.svg#id` (with `<use>`, `<img>` or a CSS background):

- `<symbol>` and `<view>` elements;
- `<g>`, `<svg>` and `<symbol>` elements directly inside the root `<svg>`;
- elements that `<use>` can show (such as `<g>`, `<path>` or a nested `<svg>`) inside the root `<defs>` or inside a
  group hidden with `display: none`, and inside groups within those;
- every element with an id, when a style sheet uses `:target`;
- every top-level element of a document that renders nothing itself, such as an inline sprite hidden with
  `display: none` or sized `0`, or a library of gradients, filters and masks.

The trade-off: an unused gradient in the `<defs>` of an ordinary icon is still removed, but an unused `<g id>` or
`<path id>` in `<defs>` is kept, because another document may show it with `<use href="icon.svg#id">`.

**Why it is risky:** references from outside the document cannot be seen. A gradient, clip path, mask, filter or
marker of a document that renders content is removed when nothing inside the document uses it, even when CSS or another
document uses it through `url(file.svg#id)`, or through `url(#id)` from elsewhere in the page when the SVG is inlined
into HTML.

```php
$svgOptimizer->withRules(removeUnusedDefinitions: true)->allowRisky();
```

---

### `removeUnusedIds` (**risky**)

Removes `id` attributes that nothing in the document references, through `url(#id)`, `href`, ARIA attributes,
animation timing or `<style>` rules (id selectors and attribute selectors such as `[id="a"]`). The id of the root
`<svg>` and the ids of sprite targets (see `removeUnusedDefinitions`), such as `<symbol>` and `<view>` elements,
top-level groups and every id when a style sheet uses `:target`, are kept, together with every id they reference.
Nothing is removed when the document contains a script, an event handler attribute, or an attribute selector that may
match several ids (such as `[id^="a"]`).

**Why it is risky:** CSS, JavaScript, analytics or end-to-end tests in the page that embeds the SVG may select elements
by id (`#logo-text`, `getElementById`), and links or other documents may point at `file.svg#id`. Ids that are not
sprite targets, such as the id of a `<path>` inside a rendered group, are removed when nothing inside the SVG references
them, and those lookups then find nothing.

```php
$svgOptimizer->withRules(removeUnusedIds: true)->allowRisky();
```

---

### `removeUnusedNamespaces`

Removes XML namespace declarations of the root element whose prefix no element or attribute name uses and no attribute
value or text mentions (as in `attributeName="xlink:href"`). This cleans up the root element, reduces verbosity, and
improves readability.

```php
$svgOptimizer->withRules(removeUnusedNamespaces: true);
```

---

### `removeUseBombs`

Removes `<use>` elements that reference themselves or an ancestor, form a cycle with other `<use>` elements, or whose
target would render 1,000 or more nested `<use>` instances, which can freeze a browser. Sprite sheets with hundreds of
`<use>` elements and deep chains of references under the limit are kept, and references to other files are not
followed.

**Visible content it removes on purpose:** a `<use>` element over the limit is removed, also when a browser would
render it.

```php
$svgOptimizer->withRules(removeUseBombs: true);
```

---

### `removeUselessDefs`

Removes definitions that can never be used because neither they nor any descendant has an `id`: children of
`<defs>`, and gradients, patterns, clip paths, masks, markers, symbols and filters anywhere in the document. Definitions
that are or contain a `<style>`, a `<script>` or a font are kept, as these take effect anywhere. A `<defs>` left empty
is removed as well.

```php
$svgOptimizer->withRules(removeUselessDefs: true);
```

---

### `removeWidthHeightAttributes` (**risky**)

Removes the `width` and `height` attributes from the root `<svg>` element, relying solely on the `viewBox` for scaling.
This lets the SVG resize with its container. Roots without a valid `viewBox` (four numbers with a positive width and
height) are left untouched, as the SVG would otherwise lose its aspect ratio and fall back to 300×150 pixels.

**Why it is risky:** without `width` and `height` the SVG has no intrinsic size, only an aspect ratio. An inline SVG or
an `<img>` without CSS dimensions then stretches to the width of its container, and layouts, email clients and tools
that rely on the fixed size render it at a different size.

```php
$svgOptimizer->withRules(removeWidthHeightAttributes: true)->allowRisky();
```

---

### `sanitizeStyleSheets`

Removes unsafe CSS from `<style>` elements and `style` attributes: `@import`, `expression()`, `behavior`,
`-moz-binding`, `-o-link`, `javascript:`, and `url()` or `image-set()` with an unsafe URL (as defined by
`removeUnsafeUrls`), also when written with escapes, comments or entities. In a `<style>` element only the unsafe
statements and declarations are removed; the whole element is removed when it cannot be parsed with confidence, is not
`text/css`, contains markup, or nothing else is left. An unsafe `style` attribute is removed as a whole.

**Visible content it removes on purpose:** the styles that are removed no longer apply.

```php
$svgOptimizer->withRules(sanitizeStyleSheets: true);
```

---

### `scopeSvgStyles` (**risky**)

Scopes all IDs and class names within SVG elements to avoid style collisions.
Each embedded SVG gets unique identifiers, and CSS rules are rewritten accordingly.
This ensures multiple SVGs can coexist in the same document without interfering with each other.
Selectors without a class or id selector outside a functional pseudo-class (such as `rect`, `g rect`, `svg *`,
`[fill]` or `rect:not(.a)`) cannot be scoped and would style the whole page, so they are removed; a style sheet left
without rules is removed too.
Names that are already scoped are left unchanged, so optimizing a scoped SVG again does not change it.
The suffix is derived from the drawing, not from its formatting, so a pretty-printed and a minified copy of the same
SVG get the same suffix.
Documents whose style sheets nest rules in at-rule blocks (such as `@media` or `@supports`), use `@import` or CSS
escapes, select ids or class names with an attribute selector (such as `[id="a"]` or `[class~="b"]`), or that have a
style element of another type than `text/css` or with content other than text, are left untouched, as those rules
cannot be rewritten.
Sprite targets (see `removeUnusedDefinitions`) keep their id, and the selectors and references that name them are left
unchanged, so `<use href="#icon">` from the page and `sprite.svg#icon` keep working; when a style sheet uses `:target`,
no id is renamed. Class names are still scoped.

**Why it is risky:** the renamed ids and class names no longer match anything outside the SVG that uses the original
names: page-level CSS such as `.icon path { fill: currentColor }` or `#logo:hover`, JavaScript (`getElementById`,
`querySelector`, toggling a class), analytics and test selectors, `url(#id)` references from other inline SVGs on the
page, and `file.svg#id` references to ids that are not sprite targets. Removing rules whose selectors may match
elements of the page (no class or id selector) also changes how the SVG itself renders when it relied on them.

```php
$svgOptimizer->withRules(scopeSvgStyles: true)->allowRisky();
```

---

### `sortAttributes`

Sorts element attributes: namespace declarations come first, then `id`, `width` and `height` in that order, then the
other attributes alphabetically by name. This improves consistency, makes diffs easier to read in version control
systems, and results in more deterministic SVG output across optimization runs. An element where an attribute without a
namespace shares its local name with an attribute in a namespace (such as `href` and `xlink:href`) keeps its order.

```php
$svgOptimizer->withRules(sortAttributes: true);
```

---

### `sortDefsChildren`

Sorts the children of each `<defs>` so that elements with the same tag are next to each other, which helps gzip
compression without changing the raw size. A `<defs>` that holds anything other than elements, or content whose order
matters, is skipped, and documents with a style sheet, a script or duplicate ids are left untouched.

```php
$svgOptimizer->withRules(sortDefsChildren: true);
```

---

### `withRules`

Every parameter defaults to `false`, so only the rules you explicitly pass as `true` are applied. Below is the full list
of available rules, shown with the values that `withAllRules()` (without `allowRisky()`) would enable:

```php
$svgOptimizer->withRules(
    convertColorsToHex: true,
    convertCssClassesToAttributes: true,
    convertEllipseToCircle: true,
    convertInlineStylesToAttributes: true,
    convertOneStopGradients: false,
    convertShapesToPath: true,
    fixAttributeNames: false,
    flattenGroups: true,
    mergeDuplicateGradients: false,
    mergeStyles: true,
    migrateToSvg2: true,
    minifyNumericValues: true,
    minifyPathData: true,
    minifyStyles: true,
    minifyTransformations: true,
    moveCommonAttributesToGroup: true,
    moveGroupAttributesToElements: true,
    removeAriaAndRole: true,
    removeComments: true,
    removeDataAttributes: false,
    removeDefaultAttributes: true,
    removeDescriptions: true,
    removeDuplicateElements: true,
    removeEmptyAttributes: true,
    removeEmptyElements: true,
    removeForeignContent: true,
    removeHiddenElements: true,
    removeIneffectiveAttributes: true,
    removeInkscapeFootprints: true,
    removeInvisibleCharacters: true,
    removeMetadata: true,
    removeNonStandardAttributes: true,
    removeNonStandardTags: true,
    removeScripts: true,
    removeTitles: true,
    removeUnnecessaryWhitespace: true,
    removeUnsafeMarkup: true,
    removeUnsafeUrls: true,
    removeUnusedDefinitions: false,
    removeUnusedIds: false,
    removeUnusedNamespaces: true,
    removeUseBombs: true,
    removeUselessDefs: true,
    removeWidthHeightAttributes: false,
    sanitizeStyleSheets: true,
    scopeSvgStyles: false,
    sortAttributes: true,
    sortDefsChildren: true,
);
```

---

#### `withAllRules`

Enable all rules. Risky rules are only included when risky rules are allowed with `allowRisky()`, no matter whether
`allowRisky()` is called before or after `withAllRules()`.

```php
$svgOptimizer->withAllRules();
```

---

#### `withRuleSets`

Enable every rule of the given [rule sets](#rule-sets). Risky rules of the sets are only included when risky rules are
allowed with `allowRisky()`, no matter whether `allowRisky()` is called before or after `withRuleSets()`.

```php
$svgOptimizer->withRuleSets(RuleSet::Security, RuleSet::Minification);
```

---

#### `allowRisky`

Allow risky rules. By default, `optimize()` throws a `RiskyRulesNotAllowedException` if a risky rule is enabled with
`withRules()`, and `withAllRules()` and `withRuleSets()` leave the risky rules out.

```php
$svgOptimizer->allowRisky();
```

---

#### `optimize`

Finalizes the optimization process and generates the optimized SVG file.

```php
$svgOptimizer->optimize();
```

---

#### `saveToFile`

Saves the optimized SVG file to the specified path, creating missing directories. Throws a `\LogicException` if `optimize()` has not been called.

```php
$svgOptimizer->saveToFile('path/to/output.svg');
```

---

#### `getContent`

Returns the optimized SVG content. Throws a `\LogicException` if `optimize()` has not been called.

```php
$svgOptimizer->getContent();
```

---

#### `getOptimizedSize`

Returns the size of the optimized SVG file.

```php
$svgOptimizer->getMetaData()->getOptimizedSize();
```

---

#### `getOriginalSize`

Returns the size of the original SVG file.

```php
$svgOptimizer->getMetaData()->getOriginalSize();
```

---

#### `getSavedBytes`

Returns the number of bytes saved by the optimization process.

```php
$svgOptimizer->getMetaData()->getSavedBytes();
```

---

#### `hasSavedBytes`

Returns a boolean indicating whether any bytes were saved by the optimization process.

```php
$svgOptimizer->getMetaData()->hasSavedBytes();
```

---

#### `getSavedPercentage`

Returns the percentage of bytes saved by the optimization process. It is negative when the output is larger than the
input, which happens when expanding entities or character references grows the document.

```php
$svgOptimizer->getMetaData()->getSavedPercentage();
```

---

#### `getOptimizationTime`

Returns the time taken to optimize the SVG file, in seconds.

```php
$svgOptimizer->getMetaData()->getOptimizationTime();
```

---

### Roadmap

For a complete list of proposed features and known issues, see
the [open issues](https://github.com/MathiasReker/php-svg-optimizer/issues).

### Contributing

Contributions are welcome! See [CONTRIBUTING.md](CONTRIBUTING.md) for the development setup, the quality
requirements and how to add a rule.

### License

This project is licensed under the MIT License. See
the [LICENSE](https://github.com/MathiasReker/php-svg-optimizer/blob/develop/LICENSE) file for more information.

### Disclaimer

Use of this tool is entirely at your own risk. The authors and maintainers make no warranties regarding the correctness,
reliability, or suitability of the tool for any particular purpose. Users are solely responsible for verifying the
results and ensuring that files are properly backed up before use. It is strongly recommended to test the tool on
non-critical files and confirm compatibility with your workflow prior to deploying it in any production environment.
