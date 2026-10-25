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

| Version | PHP  | Documentation                                                      |
|---------|------|--------------------------------------------------------------------|
| ^9.0    | ^8.3 | [current](https://github.com/MathiasReker/php-svg-optimizer)       |
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
-c , --config             Path to a JSON file, or a raw JSON string, with the optimization rules to enable. Without --config and --with-all-rules, no rules are applied.
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

Options can be placed anywhere before a `--` argument; everything after `--` is treated as a path. The value of
`--config` (`-c`) can be given as `--config=value` or `--config value`.

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
    "mergeDuplicateGradients": true,
    "mergeStyles": true,
    "migrateToSvg2": true,
    "minifyListValues": true,
    "minifyNumericValues": true,
    "minifyPathData": true,
    "minifyStyles": true,
    "minifySvgCoordinates": true,
    "minifyTransformations": true,
    "moveCommonAttributesToGroup": true,
    "moveGroupAttributesToElements": true,
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
    "removeHiddenElements": true,
    "removeInkscapeFootprints": true,
    "removeInvisibleCharacters": true,
    "removeMetadata": true,
    "removeNonInheritableGroupAttributes": true,
    "removeNonStandardAttributes": false,
    "removeNonStandardTags": false,
    "removeStyleTypeAttributes": true,
    "removeTitleAndDesc": true,
    "removeUnnecessaryWhitespace": true,
    "removeUnsafeElements": false,
    "removeUnusedDefinitions": false,
    "removeUnusedIds": false,
    "removeUnusedMasks": true,
    "removeUnusedNamespaces": true,
    "removeUnusedTextAttributes": true,
    "removeUselessDefs": true,
    "removeUselessStrokeAndFill": true,
    "removeWidthHeightAttributes": false,
    "removeXmlSpaceDefault": true,
    "removeZeroSizeShapes": true,
    "scopeSvgStyles": false,
    "sortAttributes": true,
    "sortDefsChildren": true
}
```

Each value must be the JSON boolean `true` or `false`; any other value, such as `"false"` or `0`, is reported as an
error. Rules that are left out are not applied.

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
            mergeDuplicateGradients: true,
            mergeStyles: true,
            migrateToSvg2: true,
            minifyListValues: true,
            minifyNumericValues: true,
            minifyPathData: true,
            minifyStyles: true,
            minifySvgCoordinates: true,
            minifyTransformations: true,
            moveCommonAttributesToGroup: true,
            moveGroupAttributesToElements: true,
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
            removeHiddenElements: true,
            removeInkscapeFootprints: true,
            removeInvisibleCharacters: true,
            removeMetadata: true,
            removeNonInheritableGroupAttributes: true,
            removeNonStandardAttributes: false,
            removeNonStandardTags: false,
            removeStyleTypeAttributes: true,
            removeTitleAndDesc: true,
            removeUnnecessaryWhitespace: true,
            removeUnsafeElements: false,
            removeUnusedDefinitions: false,
            removeUnusedIds: false,
            removeUnusedMasks: true,
            removeUnusedNamespaces: true,
            removeUnusedTextAttributes: true,
            removeUselessDefs: true,
            removeUselessStrokeAndFill: true,
            removeWidthHeightAttributes: false,
            removeXmlSpaceDefault: true,
            removeZeroSizeShapes: true,
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
            removeDataAttributes: true,
            removeEnableBackgroundAttribute: true,
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
improving consistency, modernizing SVG output, or increasing security.

Rules that are not marked risky are lossless: the optimized SVG renders the same in current browsers, keeps every id,
link and animation that can be reached from inside or outside the document (sprite sheets keep working), and no number
is rounded. A few rules change the SVG on purpose, and say so in their description: the security rule
`removeUnsafeElements` removes unsafe content, also when it is visible; `removeNonStandardAttributes` removes event
handler attributes such as `onclick`, so interactive SVGs lose their scripts; `fixAttributeNames` activates misspelled
attributes that browsers ignore; and `removeTitleAndDesc` and `removeAriaAndRole` remove accessibility information.

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
a `title` (which may make them alternative style sheets) or with content other than text are kept as they are. The
document is left untouched when a style sheet outside the document may apply, or when a script, an event handler
attribute or an animation may change the classes.

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

### `convertEmptyTagsToSelfClosing`

Converts elements without child nodes into self-closing tags. This simplifies the markup structure and reduces
unnecessary characters, resulting in cleaner and more compact SVG output without affecting rendering.

```php
$svgOptimizer->withRules(convertEmptyTagsToSelfClosing: true);
```

---

### `convertInlineStylesToAttributes`

Extracts individual style declarations from inline `style` attributes and converts them into their corresponding SVG
presentation attributes. This makes attribute-level optimizations more effective downstream. Declarations that are
important, repeated, overridden by a related property, or for a property that the element already sets as an attribute
with another value stay in the `style` attribute, and documents with a style sheet (a `<style>` element, an
`xml-stylesheet` instruction or a linked style sheet) are left untouched.

```php
$svgOptimizer->withRules(convertInlineStylesToAttributes: true);
```

---

### `convertOneStopGradients`

Replaces a gradient that has a single `<stop>` with that stop's color where it is used as a `fill` or `stroke`, and
removes the gradient. Gradients referenced in any other way or by other documents (see the sprite targets of
`removeUnusedDefinitions`), stops with an opacity, a `style` or a color that is not a plain hex or named color,
gradients with a `style` attribute or a `gradientTransform` other than translations and rotations (a transform that
cannot be inverted paints nothing), and documents with a style sheet, a script or an event handler attribute are left
untouched. A gradient in object bounding box units is only replaced on rects, circles and ellipses, as it is not rendered on, for example, a straight line.

```php
$svgOptimizer->withRules(convertOneStopGradients: true);
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

### `fixAttributeNames`

Normalizes SVG attribute names to their canonical, specification-compliant casing and hyphenation. Attribute names are
matched against a known set of valid SVG attributes and corrected where case differences or missing hyphens occur, and
an attribute is left alone when the element already has the canonical one or already defines its name, such as the SVG 2
`playbackorder` next to the SVG Tiny `playbackOrder`. Event handler attributes in another case, such as `onLoad`, are
kept as they are, since renaming them would make an inert attribute run a script. Foreign content and attributes in a
namespace other than XLink are left untouched.

This rule changes rendering on purpose: browsers ignore a misspelled attribute, and renaming it makes it take effect.
For example, `viewbox` becomes `viewBox` and `Fill` becomes `fill`, so an image that ignored those attributes until now
will be scaled or painted by them.

```php
$svgOptimizer->withRules(fixAttributeNames: true);
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

### `mergeDuplicateGradients`

Merges gradients that are exactly identical (same element, same attributes apart from the `id`, same stops, and equal
ancestors) into the first one, and points every `url(#id)`, `href` and `xlink:href` reference at it. Gradients that
are animated, referenced in a way that cannot be rewritten, or that other documents may reference (see the sprite
targets of `removeUnusedDefinitions`), such as the gradients of a resource library, are kept, and documents with a
style sheet, a script or an event attribute are left untouched.

```php
$svgOptimizer->withRules(mergeDuplicateGradients: true);
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
`requiredFeatures`, `kerning` and `enable-background`, also from inline styles. References to removed elements fall
back to their fallback paint, and the SVG Tiny timeline attributes are renamed to their SVG 2 forms. Unused `xlink`
and XML Events namespace declarations are removed. Foreign content and anything author CSS may select are left
untouched. Dropped elements that a `<switch>` may render, or that contain an element with an `id`, a style sheet, a
script or an animation, are kept, and no element is removed when a style sheet uses sibling combinators or structural
pseudo-classes.

```php
$svgOptimizer->withRules(migrateToSvg2: true);
```

---

### `minifyListValues`

Shortens the notation of number lists such as `viewBox`, `points`, `stroke-dasharray`, `keyTimes` and the `values` of
numeric animations. Numbers are shortened without rounding and lists use the fewest separators the attribute allows.
Values with units, percentages, keywords or a number with a trailing decimal point are left untouched, and so is the
document when a style sheet may select elements by an attribute value.

```php
$svgOptimizer->withRules(minifyListValues: true);
```

---

### `minifyNumericValues`

Shortens the notation of single-number attribute values such as `opacity`, `stroke-width`, `x` or `font-size`: leading
and trailing zeros and a `+` sign are dropped, and `px` is removed from lengths, as one pixel equals one user unit. `px`
is kept where the attribute is a plain number on that element (such as `dx`/`dy` on `<feOffset>` or `x`/`y` on
`<fePointLight>`), and integer attributes such as `numOctaves` are only shortened when written as plain integers. Only
the notation changes; values are never rounded. Numbers with a trailing decimal point (`10.`), which browsers reject,
are left as they are, and so is the document when a style sheet may select elements by an attribute value.

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

### `minifySvgCoordinates`

Shortens the number literals in coordinates, path data, `points`, `viewBox` and `enable-background` without changing
their values. Leading and trailing zeros, redundant decimal points, explicit plus signs and zero exponents are removed,
and a separator is kept wherever two numbers would otherwise merge. Values that cannot be tokenized safely, that contain
a stray decimal point, or that would read differently once minified are left untouched, and so is the document when a
style sheet may select elements by an attribute value.

```php
$svgOptimizer->withRules(minifySvgCoordinates: true);
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

Removes all `data-*` attributes from elements. These attributes are often used for application-specific metadata or
scripting hooks, and removing them can significantly reduce file size.

**Why it is risky:** `data-*` attributes never affect rendering, but scripts, CSS selectors such as `[data-state=open]`
in the page that embeds the SVG, analytics, testing tools (`data-testid`) and design-tool round trips may read them.
Those break when the attributes are gone.

```php
$svgOptimizer->withRules(removeDataAttributes: true)->allowRisky();
```

---

### `removeDefaultAttributes`

Removes stroke attributes whose value is the default (`stroke="none"`, `stroke-width="1"`, `stroke-linecap="butt"`,
`stroke-linejoin="miter"` and `stroke-miterlimit="4"`) when the element would inherit the same value: no ancestor sets
another value, as an attribute or in its inline style, up to the root or an element that can be referenced by id.
Attributes that are animated or that the element's own inline style overrides are kept, and documents with a style sheet
are left untouched.

```php
$svgOptimizer->withRules(removeDefaultAttributes: true);
```

---

### `removeDeprecatedAttributes`

Removes attributes that SVG 2 deprecated and browsers ignore: `version`, `baseProfile`, `contentScriptType`,
`contentStyleType`, `externalResourcesRequired`, `requiredFeatures`, `zoomAndPan`, `viewTarget`,
`glyph-orientation-horizontal`, `glyph-orientation-vertical` and `xml:base`, and the SVG 1.1 view attributes. It
migrates the XLink attributes in the same way as `migrateToSvg2`: `xlink:href` becomes `href` (also in animations),
`xlink:show="new"` on a link becomes `target="_blank"`, `xlink:title` on a link becomes a `<title>` child unless the
link already has one or a style sheet uses sibling combinators or structural pseudo-classes, `xlink:title` on other
elements, which browsers ignore, and the other XLink attributes are removed, and an unused `xlink` namespace
declaration is removed.
`xml:lang` becomes `lang`. Foreign content, attributes that a style sheet may select, and documents with an external
style sheet are left untouched.

```php
$svgOptimizer->withRules(removeDeprecatedAttributes: true);
```

---

### `removeDoctype`

Removes the `<!DOCTYPE>` declaration from the SVG document. This is typically unnecessary for inline SVGs or SVGs
embedded in HTML and slightly reduces file size.

```php
$svgOptimizer->withRules(removeDoctype: true);
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

### `removeEmptyContainers`

Removes container elements such as `<defs>`, `<g>`, `<symbol>`, `<mask>`, `<clipPath>`, `<pattern>`, `<marker>` and
`<switch>` when they have no content. Containers that become empty are removed as well. Containers with an `id`, a
`role`, `aria-*` attributes, a `tabindex`, `autofocus` or an event handler, which can be referenced, are exposed to
assistive technology or can take focus, children of a `<switch>`, and groups with a `filter`, which can paint on its
own, are kept. Nothing is removed when a style sheet may apply a `filter`, or uses sibling combinators or structural
pseudo-classes (such as `g + rect` or `:nth-child()`), whose matches depend on the surrounding elements.

```php
$svgOptimizer->withRules(removeEmptyContainers: true);
```

---

### `removeEmptyGroups`

Deletes `<g>` elements that have no attributes and do not contain any child elements, text or comments. This simplifies
the DOM tree and removes unnecessary structural nodes. `removeEmptyContainers` applies the same clean-up to more
container types and also removes empty groups that carry attributes other than `id` and `filter`. Nothing is removed
when a style sheet may apply a `filter`, which can paint an empty group, or uses sibling combinators or structural
pseudo-classes (such as `g + rect` or `:nth-child()`), whose matches depend on the surrounding elements.

```php
$svgOptimizer->withRules(removeEmptyGroups: true);
```

---

### `removeEmptyTextElements`

Removes `<text>` and `<tspan>` elements that contain no text at all, and `<tref>` elements without a reference. These
elements have no visual impact and can safely be eliminated to reduce file size. Whitespace-only elements are kept,
since their whitespace may render as a space between the surrounding text, and so are elements with an `id`, a `role`,
`aria-*` attributes, a `tabindex` or an event handler, and children of a `<switch>`. Nothing is removed when a style
sheet uses sibling combinators or structural pseudo-classes.

```php
$svgOptimizer->withRules(removeEmptyTextElements: true);
```

---

### `removeEnableBackgroundAttribute` (**risky**)

Removes the `enable-background` attribute and inline style declaration, which editors such as Illustrator add to almost
every file and current browsers ignore. Foreign content, an attribute or `style` that a style sheet may select, and
documents with an external style sheet are left untouched.

**Why it is risky:** older renderers that implement SVG 1.1 filters, such as some versions of Inkscape, Batik and
librsvg, use `enable-background` to provide the `BackgroundImage` and `BackgroundAlpha` filter inputs. In those
renderers, filters that use them (for example a multiply blend with the background) change or disappear.

```php
$svgOptimizer->withRules(removeEnableBackgroundAttribute: true)->allowRisky();
```

---

### `removeHiddenElements`

Removes elements that cannot render anything: elements with `display="none"`, elements with `opacity="0"`, and
shapes whose fill and stroke both resolve to `none`. Elements inside definitions, clip paths, masks, patterns, markers
and symbols, elements with an `id` or a referenced descendant, animated elements, links and interactive or accessible
content are kept. Documents with a style sheet are left untouched.

```php
$svgOptimizer->withRules(removeHiddenElements: true);
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

### `removeNonInheritableGroupAttributes`

Removes attributes from `<g>` elements that are neither inherited by the children nor apply to the group itself, such
as `x`, `y`, `width`, `height`, `stop-color` or `flood-color`. Animated attributes are kept, and documents with a style
sheet are left untouched.

```php
$svgOptimizer->withRules(removeNonInheritableGroupAttributes: true);
```

---

### `removeNonStandardAttributes`

Removes SVG attributes that are non-standard, unsafe, or not part of the official SVG specification.
Each SVG element keeps only the attributes the specification defines for it, plus the shared attribute groups that
apply to it (core attributes such as `id`, `lang`, `tabindex`, `nonce`, `part` and `slot`, presentation, ARIA,
conditional processing, animation, filter primitive and similar), so for example `points` is removed from a `<rect>`.
Elements the specification does not define fall back to the full list of known SVG attributes, without HTML-only
attributes such as `src`, `srcset` and `action`. Event handler attributes (`on*`) and `xml:base` are always removed.
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

### `removeStyleTypeAttributes`

Removes the `type` attribute of `<style>` when it is `text/css` and of `<script>` when it is a JavaScript MIME type, as
these are the defaults. Other values, such as `module`, are kept, and so is the type of a script with a `language`
attribute, which would decide the script language without it.

```php
$svgOptimizer->withRules(removeStyleTypeAttributes: true);
```

---

### `removeTitleAndDesc`

Removes `<title>` and `<desc>` elements. Rendering stays the same, but by design the image loses its accessible name and
description: screen readers no longer announce them, and browsers no longer show the title as a tooltip. Only use this
rule for decorative SVGs, or when the page that embeds the image provides the text. Elements in foreign content,
children of a `<switch>`, and elements that contain an element with an `id`, an animation, a script or a style sheet
are kept, and nothing is removed when a style sheet uses sibling combinators or structural pseudo-classes.

```php
$svgOptimizer->withRules(removeTitleAndDesc: true);
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

### `removeUnusedNamespaces`

Removes XML namespace declarations that are never referenced within the document. This cleans up the root element,
reduces verbosity, and improves readability.

```php
$svgOptimizer->withRules(removeUnusedNamespaces: true);
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

### `removeUselessStrokeAndFill`

Removes stroke and fill attributes that have no effect: the other `stroke-*` attributes when the stroke is `none`,
the stroke attributes except `stroke-width` when the stroke width is zero, and `fill-opacity` and `fill-rule` when the
fill is `none`. Elements are skipped when a descendant could re-enable the stroke or fill, and documents with a style
sheet, markers or `pointer-events` are left untouched.

```php
$svgOptimizer->withRules(removeUselessStrokeAndFill: true);
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

### `removeXmlSpaceDefault`

Removes `xml:space="default"` where it has no effect, that is, when no ancestor sets `xml:space="preserve"` and no
CSS `white-space` could be affected by it.

```php
$svgOptimizer->withRules(removeXmlSpaceDefault: true);
```

---

### `removeZeroSizeShapes`

Removes shapes the SVG specification says are not rendered: circles and ellipses with a zero radius, rects with a
zero width or height, and paths, polylines and polygons without any data. Shapes with an `id`, child elements or a
`style` attribute, and documents with a `<style>` element, are kept.

```php
$svgOptimizer->withRules(removeZeroSizeShapes: true);
```

---

### `removeUnsafeElements`

Sanitizes SVG content by removing elements and attributes that can pose security risks. This includes scripting
capabilities, external resource references, and interactive event handlers. The rule is intended for use with untrusted
SVG input and helps prevent script execution, data exfiltration, and other attack vectors when embedding SVGs in web
applications. It also removes processing instructions, comments that could break out when the SVG is inlined in HTML,
elements from non-SVG namespaces (such as XHTML or MathML), animations that rewrite `href` or event handlers, SVG Tiny
event handlers, and `<use>` elements that reference themselves or form a cycle, or whose target would render 1,000
or more nested `<use>` instances. Elements and attributes whose names match a known SVG name in a different letter case
(such as `<STYLE>`, `<foreignobject>`, `attributename` or `HREF`), which are inert in XML but become the real thing when
an HTML parser reads the inlined SVG, are removed as well.

**Visible content it removes on purpose:** this is the only rule that is not risky but can change how an SVG looks,
because security comes first. It removes every `<foreignObject>` with HTML content (diagram tools such as draw.io
use it for text labels), images, fonts and links that load external resources, and the unsafe statements and
declarations of a style sheet (the whole `<style>` only when it cannot be parsed with confidence, is not `text/css`, or
contains markup). Do not enable it for trusted SVGs that rely on these features.

```php
$svgOptimizer->withRules(removeUnsafeElements: true);
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

### `removeUnusedMasks`

Removes `<mask>` elements that nothing in the document references. Masks that other documents may reference (see the
sprite targets of `removeUnusedDefinitions`), such as the masks of a resource library, and masks holding a style
sheet, a script, a font or an animation, which apply outside the mask, are kept, and documents with a script, an event
handler attribute, an external style sheet or a style sheet that depends on sibling elements are left untouched.

```php
$svgOptimizer->withRules(removeUnusedMasks: true);
```

---

### `removeUnusedTextAttributes`

Removes text-only presentation attributes, such as `font-family`, `font-size`, `letter-spacing` or `text-anchor`, from
elements whose whole subtree cannot render text. `direction`, `writing-mode` and `unicode-bidi` are kept on the root
element, where they also set the direction of the viewport. Documents that use font-relative units or have a style sheet
are left untouched.

```php
$svgOptimizer->withRules(removeUnusedTextAttributes: true);
```

---

### `scopeSvgStyles` (**risky**)

Scopes all IDs and class names within SVG elements to avoid style collisions.
Each embedded SVG gets unique identifiers, and CSS rules are rewritten accordingly.
This ensures multiple SVGs can coexist in the same document without interfering with each other.
Rules with a bare element selector (such as `rect`) or the universal selector (`*`) cannot be scoped and would style
the whole page, so they are removed; a style sheet left without rules is removed too.
Names that are already scoped are left unchanged, so optimizing a scoped SVG again does not change it.
Documents whose style sheets nest rules in at-rule blocks (such as `@media` or `@supports`) or use `@import`, or that
have a style element of another type than `text/css` or with content other than text, are left untouched, as those
rules cannot be rewritten.
Sprite targets (see `removeUnusedDefinitions`) keep their id, and the selectors and references that name them are left
unchanged, so `<use href="#icon">` from the page and `sprite.svg#icon` keep working; when a style sheet uses `:target`,
no id is renamed. Class names are still scoped.

**Why it is risky:** the renamed ids and class names no longer match anything outside the SVG that uses the original
names: page-level CSS such as `.icon path { fill: currentColor }` or `#logo:hover`, JavaScript (`getElementById`,
`querySelector`, toggling a class), analytics and test selectors, `url(#id)` references from other inline SVGs on the
page, and `file.svg#id` references to ids that are not sprite targets. Removing rules with a bare element or universal
selector also changes how the SVG itself renders when it relied on them.

```php
$svgOptimizer->withRules(scopeSvgStyles: true)->allowRisky();
```

---

### `sortAttributes`

Sorts element attributes alphabetically. This improves consistency, makes diffs easier to read in version control
systems, and results in more deterministic SVG output across optimization runs.

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
    convertEmptyTagsToSelfClosing: true,
    convertInlineStylesToAttributes: true,
    convertOneStopGradients: true,
    convertShapesToPath: true,
    fixAttributeNames: true,
    flattenGroups: true,
    mergeDuplicateGradients: true,
    mergeStyles: true,
    migrateToSvg2: true,
    minifyListValues: true,
    minifyNumericValues: true,
    minifyPathData: true,
    minifyStyles: true,
    minifySvgCoordinates: true,
    minifyTransformations: true,
    moveCommonAttributesToGroup: true,
    moveGroupAttributesToElements: true,
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
    removeHiddenElements: true,
    removeInkscapeFootprints: true,
    removeInvisibleCharacters: true,
    removeMetadata: true,
    removeNonInheritableGroupAttributes: true,
    removeNonStandardAttributes: true,
    removeNonStandardTags: true,
    removeStyleTypeAttributes: true,
    removeTitleAndDesc: true,
    removeUnnecessaryWhitespace: true,
    removeUnsafeElements: true,
    removeUnusedDefinitions: false,
    removeUnusedIds: false,
    removeUnusedMasks: true,
    removeUnusedNamespaces: true,
    removeUnusedTextAttributes: true,
    removeUselessDefs: true,
    removeUselessStrokeAndFill: true,
    removeWidthHeightAttributes: false,
    removeXmlSpaceDefault: true,
    removeZeroSizeShapes: true,
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

#### `allowRisky`

Allow risky rules. By default, `optimize()` throws a `RiskyRulesNotAllowedException` if a risky rule is enabled with
`withRules()`, and `withAllRules()` leaves the risky rules out.

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

Run the benchmark (`--save=<file>` stores checksums of the optimized output and `--compare=<file>` checks that a change
keeps the output identical):

```bash
composer test:benchmark
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
