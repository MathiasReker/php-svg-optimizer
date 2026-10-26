# Upgrading from 9.x to 10.0

Version 10.0 renames, merges and removes rules, replaces the facade with an immutable builder, and rewrites the command
line tool and the configuration file. PHP 8.3 is still the minimum version. This guide lists every change.

Names that were added to `develop` after 9.0.0 and renamed before 10.0 are listed too, so you can also upgrade from a
development build. The command line tool and the configuration file report a renamed, merged or removed rule by its
9.x name, with the new name.

## The three changes most projects need

**1. Replace the facade with `SvgOptimizer`.**

```php
// 9.x
$svgOptimizer = SvgOptimizerFacade::fromFile('logo.svg')
    ->withAllRules()
    ->optimize()
    ->saveToFile('logo.min.svg');

echo $svgOptimizer->getMetaData()->getSavedBytes();

// 10.0
$result = (new SvgOptimizer())
    ->withDefaultRules()
    ->optimizeFile('logo.svg');

$result->saveToFile('logo.min.svg');

echo $result->getMetrics()->getSavedSizeInBytes();
```

**2. Select the `security` set if you sanitize untrusted SVGs.** In 9.x, `withAllRules()` and `--with-all-rules`
included `removeUnsafeElements`. In 10.0 the default rules leave out the security rules, so sanitizing is never a side
effect of optimizing:

```php
(new SvgOptimizer())->withDefaultRules()->withRuleSets(RuleSet::Security);
```

On the command line, use `--sets=default,security`.

**3. Update the command line and the configuration file.** Drop `process`, replace `-a` by nothing (the default rules
run when no rule is selected), and move the rule names below `"rules"`, using the new names:

```json
{
    "rules": {
        "removeComments": true,
        "minifyColors": true
    }
}
```

Then run `svg-optimizer --check <path>` once. It writes nothing, and it fails with exit code 2 and a message if the
configuration is not valid.

## What changed

- The 44 rules of 9.0.0 are now 48 rules. Rules were renamed after what they do, rules that did the same were merged,
  rules without an effect were removed, and new rules were added. See [Rules](#rules).
- The facade is gone. The public API is `SvgOptimizer`, `OptimizationResult`, `Metrics`, `Rule`, `RuleSet` and the
  exceptions, all in the root namespace of the library. See [Library API](#library-api).
- The rules are grouped into six rule sets, and `withDefaultRules()` selects five of them.
- Risky rules are skipped silently when they come from a set, and throw when you select them yourself. See
  [Behavior changes](#behavior-changes).
- The command line tool has no `process` command, new options, and the exit codes 2 and 3. See
  [Command line](#command-line).
- The configuration file is nested and has a JSON schema. See [Configuration file](#configuration-file).
- Non-risky rules are stricter: they never remove an id, they treat values that the root element inherits as unknown,
  and they leave documents with scripts alone where a script could notice the change.

## Rules

Every rule of 9.0.0, and what it is in 10.0.

| 9.0 | Change | 10.0 | Reason or note |
|---|---|---|---|
| `convertColorsToHex` | renamed | `minifyColors` | It writes the shortest of a name, `#rgb` and `#rrggbb`, not always hexadecimal. |
| `convertCssClassesToAttributes` | unchanged | `convertCssClassesToAttributes` |  |
| `convertEllipseToCircle` | renamed | `convertEllipsesToCircles` | Plural objects. |
| `convertEmptyTagsToSelfClosing` | removed | - | It had no effect: the serializer always writes empty elements as self-closing tags. |
| `convertInlineStylesToAttributes` | unchanged | `convertInlineStylesToAttributes` |  |
| `convertOneStopGradients` | renamed | `convertOneStopGradientsToColors` | Now risky: it removes the gradient and its id. |
| `convertShapesToPath` | renamed | `convertShapesToPaths` | Plural objects. |
| `fixAttributeNames` | unchanged | `fixAttributeNames` | Now risky: renaming a misspelled attribute changes how the SVG renders. |
| `flattenGroups` | unchanged | `flattenGroups` |  |
| `minifyListValues` | merged into | `minifyNumericValues` | One rule shortens numbers and number lists. |
| `minifyNumericValues` | unchanged | `minifyNumericValues` | It also shortens number lists. |
| `minifyPathData` | unchanged | `minifyPathData` |  |
| `minifySvgCoordinates` | removed | - | `minifyNumericValues` and `minifyPathData` cover it. |
| `minifyTransformations` | renamed | `minifyTransforms` | "Transform" is the name in the SVG specification. |
| `removeAriaAndRole` | renamed | `removeAriaAttributes` | It removes `role` and `aria-*`. Both are ARIA attributes. |
| `removeComments` | unchanged | `removeComments` |  |
| `removeDataAttributes` | unchanged | `removeDataAttributes` | It keeps the `data-*` attributes that a style sheet of the document selects. |
| `removeDefaultAttributes` | unchanged | `removeDefaultAttributes` | It also removes the default `type` of `style` and `script` and `xml:space="default"`. |
| `removeDeprecatedAttributes` | merged into | `migrateToSvg2` | Deprecated attributes are part of the migration to SVG 2. |
| `removeDoctype` | removed | - | It had no effect: the serializer never writes a doctype. |
| `removeDuplicateElements` | unchanged | `removeDuplicateElements` |  |
| `removeEmptyAttributes` | unchanged | `removeEmptyAttributes` |  |
| `removeEmptyContainers` | merged into | `removeEmptyElements` |  |
| `removeEmptyGroups` | merged into | `removeEmptyElements` | Groups are containers. |
| `removeEmptyTextElements` | merged into | `removeEmptyElements` |  |
| `removeEnableBackgroundAttribute` | merged into | `migrateToSvg2` | It was risky. Current browsers ignore `enable-background`, so the rule is not risky any more. |
| `removeInkscapeFootprints` | renamed | `removeEditorMarkup` | It covers more editors than Inkscape. |
| `removeInvisibleCharacters` | unchanged | `removeInvisibleCharacters` |  |
| `removeMetadata` | unchanged | `removeMetadata` |  |
| `removeNonStandardAttributes` | unchanged | `removeNonStandardAttributes` | It keeps event handler attributes. `removeScripts` removes them. |
| `removeNonStandardTags` | renamed | `removeNonStandardElements` | "Element" is the name in the SVG specification. |
| `removeStyleTypeAttributes` | merged into | `removeDefaultAttributes` | It also removes the default `type` of `script`. |
| `removeTitleAndDesc` | split into | `removeTitles`, `removeDescriptions` | The two can now be selected separately. |
| `removeUnnecessaryWhitespace` | renamed | `minifyAttributeWhitespace` | It only changes attribute values. |
| `removeUnsafeElements` | split into | `removeUnsafeMarkup`, `removeScripts`, `removeForeignContent`, `removeUnsafeUrls`, `removeUnsafeStyles`, `removeUseBombs` | One rule for each kind of unsafe content. They form the `security` set. |
| `removeUnusedIds` | unchanged | `removeUnusedIds` |  |
| `removeUnusedMasks` | merged into | `removeUnusedDefinitions` | Now risky: a definition may be used from outside the document. |
| `removeUnusedNamespaces` | unchanged | `removeUnusedNamespaces` |  |
| `removeUselessDefs` | renamed | `removeIneffectiveDefinitions` | It removes definitions without an id, which have no effect. |
| `removeWidthHeightAttributes` | unchanged | `removeWidthHeightAttributes` |  |
| `removeXmlSpaceDefault` | merged into | `removeDefaultAttributes` |  |
| `removeZeroSizeShapes` | merged into | `removeHiddenElements` | A shape with a zero size renders nothing. |
| `scopeSvgStyles` | renamed | `scopeIdsAndClasses` | It renames ids and class names. |
| `sortAttributes` | unchanged | `sortAttributes` | It puts `id`, `width` and `height` first, after the namespace declarations. |

Rules that were added to `develop` after 9.0.0 and renamed before the release:

| Name on `develop` after 9.0 | 10.0 |
|---|---|
| `mergeStyles` | `mergeStyleSheets` |
| `minifyStyles` | `minifyStyleSheets` |
| `moveCommonAttributesToGroup` | `moveCommonAttributesToGroups` |
| `moveGroupAttributesToElements` | `moveGroupTransformsToChildren` |
| `removeNonInheritableGroupAttributes` | `removeIneffectiveAttributes` |
| `removeUnusedTextAttributes` | `removeIneffectiveAttributes` |
| `removeUselessStrokeAndFill` | `removeIneffectiveAttributes` |
| `sanitizeStyleSheets` | `removeUnsafeStyles` |
| `sortDefsChildren` | `sortDefinitions` |

Rules that do not have a 9.0 rule of the same purpose. They are new, or they are the result of a merge or a split:

| Rule | Set | What it does |
|---|---|---|
| `mergeDuplicateGradients` | `structure` | Merge identical gradients into one. |
| `mergeStyleSheets` | `structure` | Merge style elements into one. |
| `migrateToSvg2` | `standards` | Migrate SVG 1.1 and SVG Tiny markup to SVG 2, including deprecated attributes and enable-background. |
| `minifyStyleSheets` | `minification` | Remove comments and whitespace from style elements. |
| `moveCommonAttributesToGroups` | `structure` | Move attributes shared by all children of a group onto the group. |
| `moveGroupTransformsToChildren` | `structure` | Move the transform of a group onto its children. |
| `removeDescriptions` | none | Remove desc elements. |
| `removeEmptyElements` | `cleanup` | Remove empty containers and empty text elements. |
| `removeForeignContent` | `security` | Remove foreign, embedding and HTML breakout elements and non-canonical names. |
| `removeHiddenElements` | `cleanup` | Remove elements that cannot render anything, including shapes with a zero size. |
| `removeIneffectiveAttributes` | `cleanup` | Remove attributes that have no effect: useless strokes and fills, group attributes that are neither inherited nor apply to groups, and text attributes on elements without text. |
| `removeScripts` | `security` | Remove scripts, event handlers and animations that rewrite links or event handlers. |
| `removeTitles` | none | Remove title elements. |
| `removeUnsafeMarkup` | `security` | Remove processing instructions and unsafe comments. |
| `removeUnsafeStyles` | `security` | Remove unsafe statements and declarations from style sheets and style attributes. |
| `removeUnsafeUrls` | `security` | Remove attributes with URLs that can run script or load external content. |
| `removeUnusedDefinitions` | `cleanup` | Remove definitions that are not referenced. |
| `removeUseBombs` | `security` | Remove use elements that form cycles or render too many instances. |
| `sortDefinitions` | `minification` | Group the children of defs elements by element name. |

Four rules changed their risk:

| Rule | 9.0 | 10.0 |
|---|---|---|
| `fixAttributeNames` | no | yes |
| `convertOneStopGradientsToColors` | no | yes |
| `removeUnusedDefinitions` | no (as `removeUnusedMasks`) | yes |
| `migrateToSvg2` | yes (as `removeEnableBackgroundAttribute`) | no |

Risky rules only run when risky rules are allowed. `withAllRules()` used to skip the risky rules too, so a project that
relied on `fixAttributeNames`, `convertOneStopGradients` or `removeUnusedMasks` through `withAllRules()` now needs
`withRiskyRulesAllowed()`.

## Library API

| 9.x | 10.0 |
|---|---|
| `\MathiasReker\PhpSvgOptimizer\Service\Facade\SvgOptimizerFacade` | `SvgOptimizer`, an immutable builder. Every `with*` method returns a new instance. |
| `SvgOptimizerFacade::fromFile($path)`, then `->optimize()` | `(new SvgOptimizer())->optimizeFile($path)`, see `optimizeFile()` |
| `SvgOptimizerFacade::fromString($svg)`, then `->optimize()` | `(new SvgOptimizer())->optimizeString($svg)`, see `optimizeString()` |
| `->withRules(removeComments: true, minifyNumericValues: true)` | `withRules()` takes `Rule` cases: `->withRules(Rule::RemoveComments, Rule::MinifyNumericValues)` |
| `->withRules(removeComments: false)` | `withoutRules()`: `->withoutRules(Rule::RemoveComments)` |
| `->withAllRules()` | `withDefaultRules()`. It does not include the `security` set any more. |
| `->withRuleSets(RuleSet ...$ruleSets)` | `withRuleSets()`. `RuleSet` moved to the root namespace. |
| `->allowRisky()` | `withRiskyRulesAllowed()` |
| `->saveToFile($path)` on the facade | `saveToFile()` on the result. It cannot be called before optimizing. |
| `->getContent()` on the facade | `getContent()` on the result |
| `->getMetaData()` | `getMetrics()` |
| `MathiasReker\PhpSvgOptimizer\ValueObject\Metrics` | `Metrics` |
| `Metrics::getOriginalSize()` | `getOriginalSizeInBytes()` |
| `Metrics::getOptimizedSize()` | `getOptimizedSizeInBytes()` |
| `Metrics::getSavedBytes()` | `getSavedSizeInBytes()` |
| `Metrics::getSavedPercentage()` | `getSavedPercentage()`, unchanged |
| `Metrics::getOptimizationTime()` | `getDurationInSeconds()` |
| `Metrics::hasSavedBytes()` | `hasSavings()` |
| `MathiasReker\PhpSvgOptimizer\Type\Rule` | `Rule`. The cases follow the new rule names, and the value of a case is the name used in configuration files. |
| `MathiasReker\PhpSvgOptimizer\Type\RuleSet` (develop after 9.0) | `RuleSet` |

The 9.x facade changed itself and returned `$this`. `SvgOptimizer` is immutable: a call whose result you drop has no
effect.

```php
// 9.x: the facade changed itself
$facade->withRules(removeComments: true);

// 10.0: use the returned instance
$optimizer = $optimizer->withRules(Rule::RemoveComments);
```

Methods do not accept named arguments. A rule is selected by its `Rule` case, and its name in configuration files is
`$rule->value`.

### Exceptions

| 9.x | 10.0 |
|---|---|
| `Contract\Exception\SvgOptimizerExceptionInterface` | `Exception\ExceptionInterface` |
| `Exception\SvgValidationException` (extended `InvalidArgumentException`) | `Exception\InvalidSvgException` (extends `RuntimeException`) |
| `Exception\XmlProcessingException`, the previous exception of `SvgValidationException` | Internal. `getPrevious()` of `InvalidSvgException` still explains the reason, including excessive entity expansion. |
| `Exception\IOException` (final) | `Exception\IOException` (not final) |
| `Exception\FileNotFoundException` (extended `RuntimeException`) | `Exception\FileNotFoundException` extends `IOException` |
| `Exception\RiskyRulesNotAllowedException` (extended `Exception`) | `Exception\RiskyRulesNotAllowedException` extends `LogicException` and has `getRules()` |
| `LogicException` when `saveToFile()` or `getContent()` ran before `optimize()` | Cannot happen. The result only exists after optimizing. |

Catch `ExceptionInterface` to catch everything the library throws. Code that caught `InvalidArgumentException` for an
invalid SVG must catch `InvalidSvgException` instead.

### Namespaces

Only the root namespace `MathiasReker\PhpSvgOptimizer` is public. The namespaces `Service`, `Contract`, `Type`,
`ValueObject` and `Support` of 9.x are gone, and everything below the root namespace except `Exception` is internal and
can change in any release. Code that used a class from one of them must use the public API above.

## Command line

| 9.x | 10.0 |
|---|---|
| `svg-optimizer [options] process <path>...` | `svg-optimizer [options] <path>...`. There is no `process` command. `process` is read as a path and fails with "does not exist". |
| `-c`, `--config=<file\|json>` | `--config` and `-c`. Without it, `svg-optimizer.json` in the working directory is used if it exists. The file format changed. |
| `-r`, `--allow-risky` | `--allow-risky`. `-r` now selects rules: `--rules`. |
| `-a`, `--with-all-rules` | Removed. `-a` fails with exit code 2. Without a selection the default rules run, and `--sets=default` selects them explicitly. |
| `-s`, `--set=<sets>` (develop after 9.0) | `--sets` and `-s` |
| `-v`, `--version` | `--version` and `-V`. `-v` is not an option any more. |
| `-d`, `--dry-run` | `--dry-run` and `-d`, unchanged |
| `-q`, `--quiet` | `--quiet` and `-q`, unchanged |
| `-h`, `--help` | `--help` and `-h`, unchanged |
| `--ansi`, `--no-ansi` | `--ansi` and `--no-ansi`, unchanged |
| - | `--without-rules`: leave rules out |
| - | `--check`: write nothing and exit with 3 if a file would change |
| - | `--list-rules`: list the rules, their set, whether they are risky, and what they do |

Exit codes:

| Code | 9.x | 10.0 |
|---|---|---|
| 0 | Success. | Success. |
| 1 | Invalid usage or configuration, or a file failed. | A file failed. |
| 2 | - | Invalid usage or configuration, including a risky rule that is not allowed. |
| 3 | - | `--check` found a file that would change. |

Other changes:

- Without any selected rule or set, the default rules run. In 9.x, nothing ran except parsing and writing the file.
- Paths are printed as you wrote them. 9.x printed absolute paths.
- Unknown rules and keys in the configuration are errors with exit code 2. 9.x printed a warning and ignored them.
- A rule that is risky and named by `--rules` or the configuration needs `--allow-risky` or `"allowRisky": true`.
  Otherwise the command fails with exit code 2. A risky rule that a set selects is skipped without an error.

## Configuration file

The configuration file has the keys `$schema`, `sets`, `rules` and `allowRisky`. The 9.x file was a flat object of rule
names with `true` or `false`, and in later 9.x development builds a `sets` key. The flat format is rejected with a
message that points to this guide.

```json
{
    "removeComments": true,
    "removeInkscapeFootprints": true,
    "convertColorsToHex": true,
    "removeNonStandardTags": false,
    "removeUnsafeElements": true
}
```

The same configuration in 10.0:

```json
{
    "$schema": "https://raw.githubusercontent.com/MathiasReker/php-svg-optimizer/develop/resources/svg-optimizer.schema.json",
    "sets": ["security"],
    "rules": {
        "removeComments": true,
        "removeEditorMarkup": true,
        "minifyColors": true
    }
}
```

- Rule names move below `"rules"` and follow the [rule table](#rules). `removeUnsafeElements` becomes the `security`
  set, or the six rules it was split into.
- `false` still means that the rule does not run. It now also overrides a set of the file: a rule that is `false` is
  left out even if a set in the file selects it. A `rules` object with only `false` values and no `sets` runs the
  default rules without them.
- The command line overrides the file for the same item. A rule that `--rules` names, or that a set of `--sets`
  contains, runs even if the file has `false` for it. `--without-rules` always wins. With
  `{"rules": {"removeScripts": false}}` in `svg-optimizer.json`, `svg-optimizer --sets=security icons` still runs
  `removeScripts`, and `svg-optimizer icons` does not.
- `"sets"` takes `default`, `security`, `standards`, `cleanup`, `conversion`, `structure` and `minification`.
- `"allowRisky": true` replaces `--allow-risky` in the file.
- The schema is `resources/svg-optimizer.schema.json`. It is generated from the `Rule` enum, so the rule names in it
  are always current.

## Behavior changes

- **Default rules.** `withDefaultRules()` selects the sets `standards`, `cleanup`, `conversion`, `structure` and
  `minification`. It does not select `security`, and it does not select `removeTitles`, `removeDescriptions`,
  `removeAriaAttributes` or `scopeIdsAndClasses`, which are in no set.
- **Selection.** The selected rules are the default rules (if asked for), plus the sets, plus the rules of
  `withRules()`, minus the rules of `withoutRules()`. `withoutRules()` always wins. 9.x could only add rules.
- **Risky rules.** A risky rule from a set or from the default rules is skipped without an error. A risky rule that
  `withRules()` selects throws `RiskyRulesNotAllowedException` when the SVG is optimized, unless
  `withRiskyRulesAllowed()` was called.
- **Stricter lossless rules.** A value that the root element inherits from the page or from a `<use>` element is
  unknown, so for example `stroke-width="1"` on a shape is kept unless an ancestor in the document sets it. Rules that
  could change what a script or an event handler finds leave such documents alone.
- **Ids are kept.** A rule that is not risky never removes or renames an id. Rules that did are now risky:
  `convertOneStopGradientsToColors` and `removeUnusedDefinitions`, which includes the old `removeUnusedMasks`.
- **Memory.** The command line tool no longer sets `memory_limit` to `-1`. Raise the limit with
  `php -d memory_limit=...` if you optimize very large files.
- **Directories.** While searching a directory, the command line tool skips `.git`, `.hg`, `.svn`, `node_modules` and
  `vendor`, and symbolic links to files or directories. A path that you name is always used.
- **Writing.** Files are replaced atomically. A file that would get larger is not written and is reported as
  `skipped, no gain`. 9.x wrote it.
- **Saving.** `OptimizationResult::saveToFile()` creates missing directories, like 9.x.

## Contributors

Composer scripts:

| 9.x | 10.0 |
|---|---|
| `analyze:all` | `check` |
| `analyze:audit` | `check:audit` |
| `analyze:autoload` | `check:autoload` |
| `analyze:check` | `check:platform` |
| `analyze:normalize` | `check:composer-normalize` |
| `analyze:php-cs-fixer` | `check:php-cs-fixer` |
| `analyze:phpstan` | `check:phpstan` |
| `analyze:rector` | `check:rector` |
| `analyze:validate` | `check:composer-validate` |
| `analyze:dependency`, `analyze:type-coverage` | Removed. PHPStan reports type coverage in `check:phpstan`. |
| `lint:all` | `fix` |
| `lint:normalize` | `fix:composer-normalize` |
| `lint:php-cs-fixer` | `fix:php-cs-fixer` |
| `lint:rector` | `fix:rector` |
| `lint:phpcbf` | Removed. PHP_CodeSniffer is not used any more. Rector and PHP CS Fixer fix the code style. |
| `test:benchmark` | `benchmark` |
| `build:badges-fast` | `build:badges-without-mutation` |
| - | `build:schema`: regenerate `resources/svg-optimizer.schema.json` after changing a rule |

Files and directories:

| 9.x | 10.0 |
|---|---|
| `dev/resources/svg` | `dev/samples` |
| `dev/scripts/badges.sh` | `dev/scripts/build-badges.sh` |
| `dev/scripts/demo.sh` | `dev/scripts/record-demo.sh` |
| `dev/artifacts/filesize.svg` | `dev/artifacts/package-size.svg` |
| `docker-compose.yml` | `compose.yaml`, with the service `php` |
| `phpunit.xml` | `phpunit.xml.dist` |
| `src/Service/Rule/<Rule>.php` | `src/Pass`, as `src/Pass/<Pass>.php` |
| `tests/Unit/Service/...` | The tests mirror `src`. The integration tests are in `tests/Integration`. |

A rule is a case of `Rule` that runs one or more passes. A pass is a class in `src/Pass` that changes one DOM document.
`CONTRIBUTING.md` describes how to add a rule.
