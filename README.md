<h1 align="center">PHP SVG Optimizer</h1>

[![Packagist Version](https://img.shields.io/packagist/v/MathiasReker/php-svg-optimizer.svg)](https://packagist.org/packages/MathiasReker/php-svg-optimizer)
[![Packagist Downloads](https://img.shields.io/packagist/dt/MathiasReker/php-svg-optimizer.svg?color=%23ff007f)](https://packagist.org/packages/MathiasReker/php-svg-optimizer)
[![CI status](https://github.com/MathiasReker/php-svg-optimizer/actions/workflows/ci.yml/badge.svg?branch=develop)](https://github.com/MathiasReker/php-svg-optimizer/actions/workflows/ci.yml)
[![Codacy Security Scan](https://github.com/MathiasReker/php-svg-optimizer/actions/workflows/codacy.yml/badge.svg)](https://github.com/MathiasReker/php-svg-optimizer/actions/workflows/codacy.yml)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-Level%20max-blue)](#)
[![Type Coverage](https://github.com/MathiasReker/php-svg-optimizer/blob/develop/dev/artifacts/type-coverage.svg)](#)
[![Code Coverage](https://github.com/MathiasReker/php-svg-optimizer/blob/develop/dev/artifacts/coverage.svg)](#)
[![Mutation Score](https://github.com/MathiasReker/php-svg-optimizer/blob/develop/dev/artifacts/mutation-score.svg)](#)
[![Mutation Coverage](https://github.com/MathiasReker/php-svg-optimizer/blob/develop/dev/artifacts/mutation-coverage.svg)](#)
[![Tests](https://github.com/MathiasReker/php-svg-optimizer/blob/develop/dev/artifacts/tests.svg)](#)
[![Assertions](https://github.com/MathiasReker/php-svg-optimizer/blob/develop/dev/artifacts/assertions.svg)](#)
[![Filesize](https://github.com/MathiasReker/php-svg-optimizer/blob/develop/dev/artifacts/filesize.svg)](#)
[![Contributors](https://img.shields.io/github/contributors/MathiasReker/php-svg-optimizer.svg)](https://github.com/MathiasReker/php-svg-optimizer/graphs/contributors)
[![Forks](https://img.shields.io/github/forks/MathiasReker/php-svg-optimizer.svg)](https://github.com/MathiasReker/php-svg-optimizer/network/members)
[![Stargazers](https://img.shields.io/github/stars/MathiasReker/php-svg-optimizer.svg)](https://github.com/MathiasReker/php-svg-optimizer/stargazers)
[![Issues](https://img.shields.io/github/issues/MathiasReker/php-svg-optimizer.svg)](https://github.com/MathiasReker/php-svg-optimizer/issues)
[![MIT License](https://img.shields.io/github/license/MathiasReker/php-svg-optimizer.svg)](https://github.com/MathiasReker/php-svg-optimizer/blob/develop/LICENSE)
[![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat&logo=php&logoColor=white)](#)

`php-svg-optimizer` is a lightweight PHP library designed to **optimize, minify, and sanitize** SVG files.
It applies various transformations and cleanup operations while ensuring compliance with SVG 2.0 specifications.
The resulting SVGs remain visually identical to the original but are smaller, cleaner, and safer.

### Versions & Dependencies

| Version | PHP  | Documentation                                                |
|---------|------|--------------------------------------------------------------|
| ^8.6    | ^8.3 | [current](https://github.com/MathiasReker/php-svg-optimizer) |

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
-c , --config             Path to a JSON file, or a raw JSON string, with custom optimization rules. If not provided, all default optimizations will be applied.
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
vendor/bin/svg-optimizer --with-all-rules --dry-run process /path/to/svgs
vendor/bin/svg-optimizer --config=config.json process /path/to/file.svg
vendor/bin/svg-optimizer --config='{"removeInkscapeFootprints": true}' process /path/to/file.svg
vendor/bin/svg-optimizer --quiet --with-all-rules process /path/to/file.svg
vendor/bin/svg-optimizer --with-all-rules --allow-risky process /path/to/file.svg
vendor/bin/svg-optimizer --with-all-rules process /path/to/file.svg
```

#### Config file example:

```json
{
    "convertColorsToHex": true,
    "convertCssClassesToAttributes": true,
    "convertEmptyTagsToSelfClosing": true,
    "convertInlineStylesToAttributes": true,
    "fixAttributeNames": false,
    "flattenGroups": true,
    "minifySvgCoordinates": true,
    "minifyTransformations": true,
    "removeAriaAndRole": true,
    "removeComments": true,
    "removeDataAttributes": false,
    "removeDefaultAttributes": true,
    "removeDeprecatedAttributes": true,
    "removeDoctype": true,
    "removeDuplicateElements": true,
    "removeEmptyAttributes": true,
    "removeEmptyGroups": true,
    "removeEmptyTextElements": true,
    "removeEnableBackgroundAttribute": false,
    "removeInkscapeFootprints": true,
    "removeInvisibleCharacters": true,
    "removeMetadata": true,
    "removeNonStandardAttributes": false,
    "removeNonStandardTags": false,
    "removeTitleAndDesc": true,
    "removeUnnecessaryWhitespace": true,
    "removeUnsafeElements": false,
    "removeUnusedMasks": true,
    "removeUnusedNamespaces": true,
    "removeWidthHeightAttributes": false,
    "scopeSvgStyles": false,
    "sortAttributes": true
}
```

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
            fixAttributeNames: false,
            flattenGroups: true,
            minifySvgCoordinates: true,
            minifyTransformations: true,
            removeAriaAndRole: true,
            removeComments: true,
            removeDataAttributes: false,
            removeDefaultAttributes: true,
            removeDeprecatedAttributes: true,
            removeDoctype: true,
            removeDuplicateElements: true,
            removeEmptyAttributes: true,
            removeEmptyGroups: true,
            removeEmptyTextElements: true,
            removeEnableBackgroundAttribute: false,
            removeInkscapeFootprints: true,
            removeInvisibleCharacters: true,
            removeMetadata: true,
            removeNonStandardAttributes: false,
            removeNonStandardTags: false,
            removeTitleAndDesc: true,
            removeUnnecessaryWhitespace: true,
            removeUnsafeElements: false,
            removeUnusedMasks: true,
            removeUnusedNamespaces: true,
            removeWidthHeightAttributes: false,
            scopeSvgStyles: false,
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
            fixAttributeNames: true,
            removeDataAttributes: true,
            removeEnableBackgroundAttribute: true,
            removeNonStandardAttributes: true,
            removeNonStandardTags: true,
            removeUnsafeElements: true,
            removeWidthHeightAttributes: true,
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

### Security: Sanitizing Untrusted SVGs

When accepting SVG uploads from users or other untrusted sources, it is **critical** to sanitize them to prevent
Cross-Site Scripting (XSS) attacks. Malicious SVGs can contain scripts, event handlers, or external references that can
execute code in the user's browser.

The `php-svg-optimizer` provides a powerful set of rules to mitigate these risks. The most important rule is
`removeUnsafeElements`, which strips out known dangerous tags and attributes. For a stricter policy, you can combine it
with `removeNonStandardTags` and `removeNonStandardAttributes`.

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use MathiasReker\PhpSvgOptimizer\Service\Facade\SvgOptimizerFacade;

try {
    $svgOptimizer = SvgOptimizerFacade::fromFile('path/to/source.svg')
        ->withRules(
            removeNonStandardAttributes: true,
            removeNonStandardTags: true,
            removeUnsafeElements: true,
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

---

## Parameters

Below is a detailed description of each available optimization rule. Each rule focuses on either reducing file size,
improving consistency, modernizing SVG output, or increasing security. Some rules are marked **risky**, meaning they may
alter behavior or semantics in certain use cases and should be enabled with care.

---

### `convertColorsToHex`

Normalizes all color definitions to hexadecimal notation. Color values expressed as functional formats (such as `rgb()`
or `rgba()`) are converted to their hexadecimal equivalents, using shorthand notation where possible.
This improves consistency across the SVG, reduces textual variation, and can slightly decrease file size while
maintaining visual fidelity.

```php
$svgOptimizer->convertColorsToHex();
```

---

### `convertCssClassesToAttributes`

Replaces CSS class-based styling with equivalent inline SVG attributes on each affected element.

```php
$svgOptimizer->convertCssClassesToAttributes();
```

---

### `convertEmptyTagsToSelfClosing`

Converts elements without child nodes into self-closing tags. This simplifies the markup structure and reduces
unnecessary characters, resulting in cleaner and more compact SVG output without affecting rendering.

```php
$svgOptimizer->convertEmptyTagsToSelfClosing();
```

---

### `convertInlineStylesToAttributes`

Extracts individual style declarations from inline `style` attributes and converts them into their corresponding SVG
presentation attributes. This improves compatibility with SVG renderers and tooling that do not fully support CSS
styling, and makes attribute-level optimizations more effective downstream.

```php
$svgOptimizer->convertInlineStylesToAttributes();
```

---

### `fixAttributeNames`

Normalizes SVG attribute names to their canonical, specification-compliant casing and hyphenation. Attribute names are
matched against a known set of valid SVG attributes and corrected where case differences or missing hyphens occur. This
improves standards compliance, avoids duplicate or conflicting attributes, and ensures consistent output across
different SVG sources and editors.

```php
$svgOptimizer->fixAttributeNames();
```

---

### `flattenGroups`

Removes unnecessary nested `<g>` (group) elements by moving their child elements directly into the parent group. This
reduces DOM depth, simplifies the SVG structure, and can marginally reduce file size while preserving visual output.

```php
$svgOptimizer->flattenGroups();
```

---

### `minifySvgCoordinates`

Reduces numeric precision in coordinate values and other numeric attributes where excessive precision is unnecessary.
Trailing zeros and redundant decimal places are removed without introducing visible rendering differences, leading to
smaller file sizes and cleaner markup.

```php
$svgOptimizer->minifySvgCoordinates();
```

---

### `minifyTransformations`

Optimizes `transform` attributes by simplifying expressions, removing redundant transformations, and normalizing
transform syntax. This reduces attribute length and improves parsing efficiency while maintaining equivalent geometric
transformations.

```php
$svgOptimizer->minifyTransformations();
```

---

### `removeAriaAndRole`

Removes ARIA (`aria-*`) and `role` attributes that are often unnecessary in static or decorative SVGs. This reduces file
size and avoids redundant accessibility metadata when SVGs are not intended to be interactive or exposed to assistive
technologies.

```php
$svgOptimizer->removeAriaAndRole();
```

---

### `removeComments`

Deletes all non-essential XML comments from the SVG document, excluding legally required comments if applicable. This
eliminates developer notes and editor artifacts that do not affect rendering, reducing file size.

```php
$svgOptimizer->removeComments();
```

---

### `removeDataAttributes` (**risky**)

Removes all `data-*` attributes from elements. These attributes are often used for application-specific metadata or
scripting hooks; removing them can significantly reduce file size but may break integrations or runtime behavior that
relies on them.

```php
$svgOptimizer->removeDataAttributes();
```

---

### `removeDefaultAttributes`

Eliminates attributes whose values match SVG specification defaults and therefore do not affect rendering. By omitting
redundant information, this rule reduces clutter and minimizes output size while preserving visual correctness.

```php
$svgOptimizer->removeDefaultAttributes();
```

---

### `removeDeprecatedAttributes`

Removes attributes that are deprecated or obsolete according to modern SVG specifications. This helps modernize SVG
output, improves forward compatibility, and reduces reliance on legacy behavior.

```php
$svgOptimizer->removeDeprecatedAttributes();
```

---

### `removeDoctype`

Removes the `<!DOCTYPE>` declaration from the SVG document. This is typically unnecessary for inline SVGs or SVGs
embedded in HTML and slightly reduces file size.

```php
$svgOptimizer->removeDoctype();
```

---

### `removeDuplicateElements`

Detects and removes identical duplicate elements that do not contribute additional visual output. This reduces
redundancy in the SVG structure and helps minimize file size without altering appearance.

```php
$svgOptimizer->removeDuplicateElements();
```

---

### `removeEmptyAttributes`

Removes attributes that have empty values. Such attributes add noise to the markup without affecting rendering and can
safely be removed to produce cleaner output.

```php
$svgOptimizer->removeEmptyAttributes();
```

---

### `removeEmptyGroups`

Deletes `<g>` elements that do not contain any child elements. This simplifies the DOM tree and removes unnecessary
structural nodes.

```php
$svgOptimizer->removeEmptyGroups();
```

---

### `removeEmptyTextElements`

Removes `<text>` elements that contain no textual content. These elements have no visual impact and can safely be
eliminated to reduce file size.

```php
$svgOptimizer->removeEmptyTextElements();
```

---

### `removeEnableBackgroundAttribute` (**risky**)

Removes the `enable-background` attribute from the root `<svg>` element. While often unnecessary, this attribute can
affect certain filters or compositing behaviors. Removing it may improve performance and reduce size but could alter
rendering in advanced use cases.

```php
$svgOptimizer->removeEnableBackgroundAttribute();
```

---

### `removeInkscapeFootprints`

Strips Inkscape-specific metadata, attributes, and elements that are added during export but are not required for
rendering. This cleans up SVGs generated by Inkscape and significantly reduces noise and file size.

```php
$svgOptimizer->removeInkscapeFootprints();
```

---

### `removeInvisibleCharacters`

Removes invisible or non-printable Unicode characters, such as zero-width spaces, that may inadvertently appear in SVG
files. This prevents subtle rendering or parsing issues and ensures clean textual content.

```php
$svgOptimizer->removeInvisibleCharacters();
```

---

### `removeMetadata`

Deletes `<metadata>` elements, which often contain authoring information, editor data, or descriptive metadata not
required for rendering. This reduces file size and removes non-essential content.

```php
$svgOptimizer->removeMetadata();
```

---

### `removeNonStandardAttributes`

Removes SVG attributes that are non-standard, unsafe, or not part of the official SVG specification.
This includes attributes not in the SvgAttribute enum, excluding safe prefixes like xml:, xlink:, or data-*.

```php
$svgOptimizer->removeNonStandardAttributes();
```

---

### `removeNonStandardTags`

Removes elements (tags) that are not part of the official SVG specification.
This is useful for sanitizing SVGs that may contain editor-specific or foreign XML elements.

```php
$svgOptimizer->removeNonStandardTags();
```

---

### `removeTitleAndDesc`

Removes `<title>` and `<desc>` elements. While these elements provide accessibility and descriptive information, they
are optional in many contexts. Removing them reduces file size but should only be done when accessibility metadata is
not required.

```php
$svgOptimizer->removeTitleAndDesc();
```

---

### `removeUnnecessaryWhitespace`

Collapses or removes redundant whitespace such as extra spaces, tabs, and line breaks. This produces more compact SVG
output while preserving semantic structure and rendering.

```php
$svgOptimizer->removeUnnecessaryWhitespace();
```

---

### `removeUnusedNamespaces`

Removes XML namespace declarations that are never referenced within the document. This cleans up the root element,
reduces verbosity, and improves readability.

```php
$svgOptimizer->removeUnusedNamespaces();
```

---

### `removeWidthHeightAttributes` (**risky**)

Removes the `width` and `height` attributes from the root `<svg>` element, relying solely on the `viewBox` for scaling.
This enables responsive resizing but may break layouts that depend on fixed dimensions.

```php
$svgOptimizer->removeWidthHeightAttributes();
```

---

### `removeUnsafeElements`

Sanitizes SVG content by removing elements and attributes that can pose security risks. This includes scripting
capabilities, external resource references, and interactive event handlers. The rule is intended for use with untrusted
SVG input and helps prevent script execution, data exfiltration, and other attack vectors when embedding SVGs in web
applications.

```php
$svgOptimizer->removeUnsafeElements();
```

---

### `removeUnusedMasks`

Removes `<mask>` elements that are defined but never referenced by any element in the document. This reduces file size
and eliminates unused definitions without affecting rendering.

```php
$svgOptimizer->removeUnusedMasks();
```

---

### `scopeSvgStyles` (**risky**)

Scopes all IDs and class names within SVG elements to avoid style collisions.
Each embedded SVG gets unique identifiers, and CSS rules are rewritten accordingly.
This ensures multiple SVGs can coexist in the same document without interfering with each other.

```php
$svgOptimizer->scopeSvgStyles();
```

---

### `sortAttributes`

Sorts element attributes alphabetically. This improves consistency, makes diffs easier to read in version control
systems, and results in more deterministic SVG output across optimization runs.

```php
$svgOptimizer->sortAttributes();
```

---

### `withRules`

Every parameter defaults to `false`, so only the rules you explicitly pass as `true` are applied. Below is the full list
of available rules, shown with the values that `withAllRules()` (without `allowRisky()`) would enable:

```php
$svgOptimizer->withRules(
    convertColorsToHex: true,
    convertCssClassesToAttributes: true,
    convertEmptyTagsToSelfClosing: true,
    convertInlineStylesToAttributes: true,
    fixAttributeNames: true,
    flattenGroups: true,
    minifySvgCoordinates: true,
    minifyTransformations: true,
    removeAriaAndRole: true,
    removeComments: true,
    removeDataAttributes: false,
    removeDefaultAttributes: true,
    removeDeprecatedAttributes: true,
    removeDoctype: true,
    removeDuplicateElements: true,
    removeEmptyAttributes: true,
    removeEmptyGroups: true,
    removeEmptyTextElements: true,
    removeEnableBackgroundAttribute: false,
    removeInkscapeFootprints: true,
    removeInvisibleCharacters: true,
    removeMetadata: true,
    removeNonStandardAttributes: true,
    removeNonStandardTags: true,
    removeTitleAndDesc: true,
    removeUnnecessaryWhitespace: true,
    removeUnsafeElements: true,
    removeUnusedMasks: true,
    removeUnusedNamespaces: true,
    removeWidthHeightAttributes: false,
    scopeSvgStyles: false,
    sortAttributes: true,
);
```

---

#### `withAllRules`

Enable all rules. Risky rules remain disabled unless `allowRisky()` is explicitly set.

```php
$svgOptimizer->withAllRules();
```

---

#### `allowRisky`

By default, risky rules are not applied even if you add them unless explicitly allowed.

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

Saves the optimized SVG file to the specified path.

```php
$svgOptimizer->saveToFile('path/to/output.svg');
```

---

#### `getContent`

Returns the optimized SVG content.

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

Returns the percentage of bytes saved by the optimization process.

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
docker-compose up --build -d
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

Run mutation testing:

```bash
composer test:mutation
```

Fix code style:

```bash
composer lint:all
```

Build badges:

```bash
composer build:badges
```

Build baseline:

```bash
composer build:baseline
```

### License

This project is licensed under the MIT License. See
the [LICENSE](https://github.com/MathiasReker/php-svg-optimizer/blob/develop/LICENSE) file for more information.

### Disclaimer

Use of this tool is entirely at your own risk. The authors and maintainers make no warranties regarding the correctness,
reliability, or suitability of the tool for any particular purpose. Users are solely responsible for verifying the
results and ensuring that files are properly backed up before use. It is strongly recommended to test the tool on
non-critical files and confirm compatibility with your workflow prior to deploying it in any production environment.
