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
[![Filesize](https://raw.githubusercontent.com/MathiasReker/php-svg-optimizer/develop/dev/artifacts/filesize.svg)](#)
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

| Version | PHP  | Documentation                                                |
|---------|------|--------------------------------------------------------------|
| ^8.8    | ^8.3 | [current](https://github.com/MathiasReker/php-svg-optimizer) |

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
    --ansi                Force ANSI color output, even when output is not an interactive terminal.
    --no-ansi             Disable ANSI color output.

Commands:
Process                   Provide a list of directories or files to process.
```

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
```

#### Config file example:

```json
{
    "convertColorsToHex": true,
    "convertCssClassesToAttributes": true,
    "convertEllipseToCircle": true,
    "convertEmptyTagsToSelfClosing": true,
    "convertInlineStylesToAttributes": true,
    "convertOneStopGradients": true,
    "convertShapesToPath": true,
    "fixAttributeNames": false,
    "flattenGroups": true,
    "minifyListValues": true,
    "minifyNumericValues": true,
    "minifyPathData": true,
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
    "removeEmptyContainers": true,
    "removeEmptyGroups": true,
    "removeEmptyTextElements": true,
    "removeEnableBackgroundAttribute": false,
    "removeInkscapeFootprints": true,
    "removeInvisibleCharacters": true,
    "removeMetadata": true,
    "removeNonStandardAttributes": false,
    "removeNonStandardTags": false,
    "removeStyleTypeAttributes": true,
    "removeTitleAndDesc": true,
    "removeUnnecessaryWhitespace": true,
    "removeUnsafeElements": false,
    "removeUnusedIds": false,
    "removeUnusedMasks": true,
    "removeUnusedNamespaces": true,
    "removeUselessDefs": true,
    "removeWidthHeightAttributes": false,
    "removeXmlSpaceDefault": true,
    "removeZeroSizeShapes": true,
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
            convertEmptyTagsToSelfClosing: true,
            convertInlineStylesToAttributes: true,
            convertOneStopGradients: true,
            convertShapesToPath: true,
            fixAttributeNames: false,
            flattenGroups: true,
            minifyListValues: true,
            minifyNumericValues: true,
            minifyPathData: true,
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
            removeEmptyContainers: true,
            removeEmptyGroups: true,
            removeEmptyTextElements: true,
            removeEnableBackgroundAttribute: false,
            removeInkscapeFootprints: true,
            removeInvisibleCharacters: true,
            removeMetadata: true,
            removeNonStandardAttributes: false,
            removeNonStandardTags: false,
            removeStyleTypeAttributes: true,
            removeTitleAndDesc: true,
            removeUnnecessaryWhitespace: true,
            removeUnsafeElements: false,
            removeUnusedIds: false,
            removeUnusedMasks: true,
            removeUnusedNamespaces: true,
            removeUselessDefs: true,
            removeWidthHeightAttributes: false,
            removeXmlSpaceDefault: true,
            removeZeroSizeShapes: true,
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

Rules always run in the same order, whatever order they are enabled in: untrusted content is sanitized first, styles
are turned into attributes before attributes are cleaned up, and values are minified before shapes are converted, so
optimizing an optimized file again leaves it (almost always) unchanged. A rule enabled more than once, for example with
both `withAllRules()` and `withRules()`, runs once.

---

## Parameters

Below is a detailed description of each available optimization rule. Each rule focuses on either reducing file size,
improving consistency, modernizing SVG output, or increasing security. Some rules are marked **risky**, meaning they may
alter behavior or semantics in certain use cases and should be enabled with care.

---

### `convertColorsToHex`

Normalizes color definitions to their shortest exact notation. Functional formats (such as `rgb()` or `rgba()`) are
converted to hexadecimal, `#aabbcc` is shortened to `#abc`, and a CSS color name and its hexadecimal value are swapped
when the other form is shorter (for example `#ff0000` becomes `red` and `white` becomes `#fff`). `currentColor`,
`transparent`, `none` and `url()` references are left as they are.

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

### `convertEllipseToCircle`

Converts `<ellipse>` elements whose `rx` and `ry` are equal into `<circle>` elements with a single `r`. The radius is
copied as written, so no value is recalculated. Ellipses with child elements, a `style` attribute, or in a document
with a `<style>` element are kept.

```php
$svgOptimizer->convertEllipseToCircle();
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

### `convertOneStopGradients`

Replaces a gradient that has a single `<stop>` with that stop's color where it is used as a `fill` or `stroke`, and
removes the gradient. Gradients referenced in any other way, stops with an opacity, a `style` or a color that is not a
plain hex or named color, and documents with a `<style>` or `<script>` element are left untouched. A gradient in object
bounding box units is only replaced on rects, circles and ellipses, as it is not rendered on, for example, a straight
line.

```php
$svgOptimizer->convertOneStopGradients();
```

---

### `convertShapesToPath`

Converts `<rect>`, `<line>`, `<polyline>` and `<polygon>` elements into `<path>` elements when that makes the file
smaller. The path data reuses the original numbers (a rect is drawn with relative moves), so no coordinate is
recalculated or rounded. Rounded rects, values with units, `points` lists that are invalid or have an odd number of
coordinates, shapes with child elements or a `style` attribute, and documents with a `<style>` or `<marker>` element
are kept as they are.

```php
$svgOptimizer->convertShapesToPath();
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

### `minifyListValues`

Shortens the notation of number lists such as `viewBox`, `points`, `stroke-dasharray`, `keyTimes` and the `values` of
numeric animations. Numbers are shortened without rounding and lists use the fewest separators the attribute allows.
Values with units, percentages or keywords are left untouched.

```php
$svgOptimizer->minifyListValues();
```

---

### `minifyNumericValues`

Shortens the notation of single-number attribute values such as `opacity`, `stroke-width`, `x` or `font-size`:
leading and trailing zeros and a `+` sign are dropped, and `px` is removed from lengths, as one pixel equals one user
unit. `px` is kept where the attribute is a plain number on that element (such as `dx`/`dy` on `<feOffset>` or `x`/`y`
on `<fePointLight>`), and integer attributes such as `numOctaves` are only shortened when written as plain integers.
Only the notation changes; values are never rounded.

```php
$svgOptimizer->minifyNumericValues();
```

---

### `minifyPathData`

Rewrites path data (`d`) in its shortest equivalent form: repeated command letters and unneeded separators are
dropped, number literals are shortened, and relative linetos along one axis become `h`/`v`. Coordinates are never
recalculated or rounded, and path data that is not valid is left untouched.

```php
$svgOptimizer->minifyPathData();
```

---

### `minifySvgCoordinates`

Shortens the number literals in coordinates, path data, `points`, `viewBox` and `enable-background` without changing
their values. Leading and trailing zeros, redundant decimal points, explicit plus signs and zero exponents are removed,
and a separator is kept wherever two numbers would otherwise merge. Values that cannot be tokenized safely are left
untouched.

```php
$svgOptimizer->minifySvgCoordinates();
```

---

### `minifyTransformations`

Shortens `transform`, `gradientTransform` and `patternTransform` values without recalculating them: numbers and
separators are written in their shortest form, identity transformations such as `translate(0)`, `scale(1)` and
`rotate(0)` are removed, default arguments are dropped (`translate(10 0)` becomes `translate(10)`), and a matrix that
only translates or scales is written as `translate()` or `scale()`. An identity `gradientTransform` or
`patternTransform` on an element with `href` or `xlink:href` is kept (as `scale(1)` when shorter), since removing it
would inherit the transform of the referenced element. Values that do not parse as a transform list are left untouched.

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
redundancy in the SVG structure and helps minimize file size without altering appearance. Nothing is removed when a
style sheet uses sibling combinators or structural pseudo-classes (such as `g + rect` or `:nth-child()`), whose matches
depend on the surrounding elements.

```php
$svgOptimizer->removeDuplicateElements();
```

---

### `removeEmptyAttributes`

Removes attributes that have empty values. Such attributes add noise to the markup without affecting rendering and can
safely be removed to produce cleaner output. Empty conditional processing attributes (`systemLanguage`,
`requiredExtensions`, `requiredFeatures`), which keep an element from rendering, and attributes on elements outside the
SVG namespace, such as `<foreignObject>` content, are kept.

```php
$svgOptimizer->removeEmptyAttributes();
```

---

### `removeEmptyContainers`

Removes container elements such as `<defs>`, `<g>`, `<symbol>`, `<mask>`, `<clipPath>`, `<pattern>`, `<marker>` and
`<switch>` when they have no content and no `id`. Containers that become empty are removed as well. Groups with a
`filter` are kept, as a filter can paint on its own. Nothing is removed when a style sheet uses sibling combinators or
structural pseudo-classes (such as `g + rect` or `:nth-child()`), whose matches depend on the surrounding elements.

```php
$svgOptimizer->removeEmptyContainers();
```

---

### `removeEmptyGroups`

Deletes `<g>` elements that have no attributes and do not contain any child elements, text or comments. This simplifies
the DOM tree and removes unnecessary structural nodes. `removeEmptyContainers` applies the same clean-up to more
container types and also removes empty groups that carry attributes other than `id` and `filter`. Nothing is removed
when a style sheet uses sibling combinators or structural pseudo-classes (such as `g + rect` or `:nth-child()`), whose
matches depend on the surrounding elements.

```php
$svgOptimizer->removeEmptyGroups();
```

---

### `removeEmptyTextElements`

Removes `<text>` and `<tspan>` elements that contain no text at all, and `<tref>` elements without a reference. These
elements have no visual impact and can safely be eliminated to reduce file size. Whitespace-only elements are kept,
since their whitespace may render as a space between the surrounding text.

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

Strips editor-specific metadata, attributes, and elements that are added during export but are not required for
rendering. This covers Inkscape, Sodipodi, Adobe Illustrator, Sketch, Serif and Microsoft Visio, matched by namespace
URI, including Illustrator's private data in a `<foreignObject>` that requires an Illustrator extension.

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
Each SVG element keeps only the attributes the specification defines for it, plus the shared attribute groups that
apply to it (core, presentation, ARIA, conditional processing, animation, filter primitive and similar), so for example
`points` is removed from a `<rect>`. Elements the specification does not define fall back to the full list of known SVG
attributes, without HTML-only attributes such as `src`, `srcset` and `action`. Event handler attributes (`on*`) and
`xml:base` are always removed.
Prefixed attributes are matched by namespace: only known `xlink:`, `xml:` and `ev:` attributes are kept, and attributes bound to any
other namespace are removed. `data-*` attributes are kept when their name is valid. Elements outside the SVG namespace
and the content of a `<foreignObject>`, such as XHTML, are left untouched.

```php
$svgOptimizer->removeNonStandardAttributes();
```

---

### `removeNonStandardTags`

Removes elements (tags) that are not part of the official SVG specification, keeping their content.
This is useful for cleaning up SVGs that may contain unknown elements. Elements outside the SVG namespace, whose content
does not render, and the content of a `<foreignObject>`, such as XHTML, are left untouched.

```php
$svgOptimizer->removeNonStandardTags();
```

---

### `removeStyleTypeAttributes`

Removes the `type` attribute of `<style>` when it is `text/css` and of `<script>` when it is a JavaScript MIME type,
as these are the defaults. Other values, such as `module`, are kept.

```php
$svgOptimizer->removeStyleTypeAttributes();
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

### `removeUselessDefs`

Removes definitions that can never be used because neither they nor any descendant has an `id`: children of
`<defs>`, and gradients, patterns, clip paths, masks, markers, symbols and filters anywhere in the document. Definitions
that are or contain a `<style>`, a `<script>` or a font are kept, as these take effect anywhere. A `<defs>` left empty
is removed as well.

```php
$svgOptimizer->removeUselessDefs();
```

---

### `removeWidthHeightAttributes` (**risky**)

Removes the `width` and `height` attributes from the root `<svg>` element, relying solely on the `viewBox` for scaling.
This enables responsive resizing but may break layouts that depend on fixed dimensions.

```php
$svgOptimizer->removeWidthHeightAttributes();
```

---

### `removeXmlSpaceDefault`

Removes `xml:space="default"` where it has no effect, that is, when no ancestor sets `xml:space="preserve"` and no
CSS `white-space` could be affected by it.

```php
$svgOptimizer->removeXmlSpaceDefault();
```

---

### `removeZeroSizeShapes`

Removes shapes the SVG specification says are not rendered: circles and ellipses with a zero radius, rects with a
zero width or height, and paths, polylines and polygons without any data. Shapes with an `id`, child elements or a
`style` attribute, and documents with a `<style>` element, are kept.

```php
$svgOptimizer->removeZeroSizeShapes();
```

---

### `removeUnsafeElements`

Sanitizes SVG content by removing elements and attributes that can pose security risks. This includes scripting
capabilities, external resource references, and interactive event handlers. The rule is intended for use with untrusted
SVG input and helps prevent script execution, data exfiltration, and other attack vectors when embedding SVGs in web
applications. It also removes processing instructions, comments that could break out when the SVG is inlined in HTML,
elements from non-SVG namespaces (such as XHTML or MathML), and animations that rewrite `href`.

```php
$svgOptimizer->removeUnsafeElements();
```

---

### `removeUnusedIds` (**risky**)

Removes `id` attributes that nothing in the document references, through `url(#id)`, `href`, ARIA attributes,
animation timing or `<style>` rules (id selectors and attribute selectors such as `[id="a"]`). The ids of the root
`<svg>`, `<symbol>` and `<view>` elements are kept, and nothing is removed when the document contains a script, an event
handler attribute, or an attribute selector that may match several ids (such as `[id^="a"]`). The rule is risky because
CSS, JavaScript or links outside the SVG may target the removed ids.

```php
$svgOptimizer->removeUnusedIds();
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
Rules with a bare element selector (such as `rect`) or the universal selector (`*`) cannot be scoped and would style
the whole page, so they are removed; a style sheet left without rules is removed too.
Names that are already scoped are left unchanged, so optimizing a scoped SVG again does not change it.
Documents whose style sheets nest rules in at-rule blocks (such as `@media` or `@supports`) or use `@import` are left
untouched, as those rules cannot be rewritten.

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
    convertEllipseToCircle: true,
    convertEmptyTagsToSelfClosing: true,
    convertInlineStylesToAttributes: true,
    convertOneStopGradients: true,
    convertShapesToPath: true,
    fixAttributeNames: true,
    flattenGroups: true,
    minifyListValues: true,
    minifyNumericValues: true,
    minifyPathData: true,
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
    removeEmptyContainers: true,
    removeEmptyGroups: true,
    removeEmptyTextElements: true,
    removeEnableBackgroundAttribute: false,
    removeInkscapeFootprints: true,
    removeInvisibleCharacters: true,
    removeMetadata: true,
    removeNonStandardAttributes: true,
    removeNonStandardTags: true,
    removeStyleTypeAttributes: true,
    removeTitleAndDesc: true,
    removeUnnecessaryWhitespace: true,
    removeUnsafeElements: true,
    removeUnusedIds: false,
    removeUnusedMasks: true,
    removeUnusedNamespaces: true,
    removeUselessDefs: true,
    removeWidthHeightAttributes: false,
    removeXmlSpaceDefault: true,
    removeZeroSizeShapes: true,
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
`/src/Service/Rule` directory. Logic shared by several rules lives in traits in `/src/Service/Rule/Trait`. The
command-line tool lives in `/src/Console`. Classes tagged `@api` are the public API; classes tagged `@internal` may
change in any release.

##### Adding a New Rule

### 1. **Create the Rule**

Create a new **final readonly class** in the `/src/Service/Rule` directory and implement
the `SvgOptimizerRuleInterface`. This interface will define the logic for your rule.

### 2. **Write Tests**

Write comprehensive **unit tests** for your rule in the `/tests/Unit/Service/Rule` directory. Ensure the tests cover
various scenarios to verify the correct behavior and edge cases for your rule.

### 3. **Integrate the Rule**

- **Add your rule to the rule enum** in `/src/Type/Rule.php`, and add it to `Rule::EXECUTION_ORDER` after the rules it
  depends on.
- **Register the rule** in the SVG optimizer builder located at `/src/Service/Facade/SvgOptimizerFacade.php`. Append
  the new parameter to `withRules()`, so existing calls with positional arguments keep working.

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
