# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html). Changes in earlier versions are listed in the
[GitHub releases](https://github.com/MathiasReker/php-svg-optimizer/releases).

## [8.8.0] - 2026-10-13

### Added

- Lossless optimization rules: `convertEllipseToCircle`, `convertOneStopGradients`, `convertShapesToPath`,
  `minifyListValues`, `minifyNumericValues`, `minifyPathData`, `removeEmptyContainers`, `removeStyleTypeAttributes`,
  `removeUselessDefs`, `removeXmlSpaceDefault` and `removeZeroSizeShapes`. They never round numbers.
- The risky `removeUnusedIds` rule.
- `Contract\Exception\SvgOptimizerExceptionInterface`, implemented by every exception of the library, so all of them
  can be caught at once.
- The command line warns about configuration keys that do not name a rule, such as a misspelled rule name.

### Changed

- Rules run in a fixed order, whatever order they are enabled in, and a rule enabled more than once runs once.
  Optimizing an optimized file again now almost never changes it, and files get smaller, so the optimized output of
  some files changes.
- `removeNonStandardAttributes` only keeps the attributes that the SVG specification allows on each element.
- `convertColorsToHex` writes the shortest form of a color, which may be a color name.
- `minifyTransformations` rewrites transforms exactly, without rounding.
- The public API (the facade, the `Metrics` value object, the `Rule` enum and the exceptions) is tagged `@api`;
  every other class is tagged `@internal`.

### Deprecated

- The command-line classes moved to the `Console` namespace. The old names keep working through class aliases, which
  will be removed in 9.0:
  - `Model\MetaDataAggregator` is now `Console\Output\MetaDataAggregator`.
  - `Service\Data\ArgumentData` is now `Console\Help\ArgumentData`.
  - `Service\Filesystem\Finder` is now `Console\Filesystem\Finder`.
  - `Service\Formatter\ByteFormatter` is now `Console\Output\Formatter\ByteFormatter`.
  - `Service\Processor\SvgFileProcessor` is now `Console\Processor\SvgFileProcessor`.
  - `Type\Command` and `Type\Option` are now `Console\Type\Command` and `Console\Type\Option`.
  - `ValueObject\CliOption`, `ValueObject\CommandHelp`, `ValueObject\CommandOption` and `ValueObject\ExampleCommand`
    are now in `Console\ValueObject`.
- `Console\Output\Stream\MemoryStream`, `Console\Output\Stream\StdoutStream` and `Service\Validator\SvgValidator` will
  be final in 9.0.

### Fixed

- Existing rules no longer change how a file renders, among others in the style conversion rules, `flattenGroups`,
  `removeDefaultAttributes`, `removeDuplicateElements` and `removeUnusedMasks`.
- `convertEmptyTagsToSelfClosing` no longer writes invalid markup such as `<g//>`.
- `removeUnnecessaryWhitespace` no longer collapses whitespace inside style values.
- `scopeSvgStyles` updates every reference, is idempotent, removes element and universal selectors from selector
  lists, and removes style sheets left without rules.
- `removeDeprecatedAttributes` keeps `cursor`, turns `xlink:title` into a `<title>` child and only turns
  `xlink:show="new"` into `target="_blank"` on links.
- Semicolons inside strings or parentheses, such as in `url(data:image/png;base64,...)`, no longer split a style
  declaration.
- The size check only reverts the rule it checks, instead of every rule that ran since the previous check.
- libxml diagnostics no longer reach the PHP error handler, so applications that turn warnings into exceptions no
  longer reject recoverable input.
- Whitespace inside `<text>` is preserved.

### Security

- Local DTDs are no longer loaded, and entities are expanded safely while loading.
- The sanitizer removes processing instructions, unsafe comments, HTML breakout elements, elements in foreign
  namespaces, event handler attributes, HTML-only attributes, `xml:base`, `name` attributes and animations of `href`.
- The sanitizer detects more dangerous URLs, including `data:` URLs outside images, CSS escapes, `image-set()`,
  unclosed `url(` and SMIL `values`, `from`, `to` and `by` attributes.

[8.8.0]: https://github.com/MathiasReker/php-svg-optimizer/compare/8.7.1...8.8.0
