# Contributing

Thank you for considering a contribution to PHP SVG Optimizer. Bug reports, ideas and pull requests are all welcome.

By participating in this project, you agree to abide by the [Code of Conduct](CODE_OF_CONDUCT.md).

## Reporting Bugs and Requesting Features

- Search the [existing issues](https://github.com/MathiasReker/php-svg-optimizer/issues) first.
- [Open an issue](https://github.com/MathiasReker/php-svg-optimizer/issues/new/choose) using one of the templates. For
  a bug, include the smallest SVG that shows it, the rules you enabled, and the expected and actual output.
- Report security vulnerabilities privately as described in the [Security Policy](SECURITY.md), never in a public issue.

## Development Setup

Requirements: PHP 8.3 or newer with the `dom`, `libxml` and `mbstring` extensions, and Composer 2.

```bash
git clone https://github.com/MathiasReker/php-svg-optimizer.git
cd php-svg-optimizer
composer install
composer test
```

Run the command line tool from the checkout with `php bin/svg-optimizer`. The directory `dev/samples` holds one SVG
export of each editor. The tests, the benchmark and the terminal demo use it.

```bash
php bin/svg-optimizer --dry-run dev/samples
```

### Docker

The Docker setup provides PHP, Composer and Xdebug, so nothing else needs to be installed. The project directory is
mounted into the container.

```bash
docker compose run --rm php composer install
docker compose run --rm php composer test
```

Use another PHP version by setting `PHP_VERSION`:

```bash
PHP_VERSION=8.5 docker compose run --rm --build php composer test
```

The container runs as user and group 1000. If your IDs differ, export them first so files created in the container
belong to you: `export UID; export GID=$(id -g)`.

## Making Changes

1. Fork the repository and create a branch from `develop`.
2. Make your change with tests. Keep each commit to one logical change and write commit messages in the
   [Conventional Commits](https://www.conventionalcommits.org/) format, for example
   `fix: keep arc flags separated in minifyPathData`. Mark a breaking change with `!` and a `BREAKING CHANGE:` line.
3. Run the checks below and make sure they pass.
4. Open a pull request against `develop` and describe what changed and why.

### Quality Requirements

Every pull request must keep the project at its current standard:

- 100 % line coverage and a 100 % mutation score.
- No PHPStan errors at the maximum level, and 100 % type coverage.
- Code style fixed with Rector and PHP CS Fixer.
- No comments other than PHPDoc tags: code explains itself through names.
- Logic shared by several passes lives in a trait.

```bash
composer fix            # Fix the code style with Rector and PHP CS Fixer
composer check          # Validate and normalize composer.json, PHPStan, Rector and PHP CS Fixer (dry runs), audit
composer test           # Test suite
composer test:coverage  # Test suite with a coverage report (requires Xdebug or PCOV)
composer test:mutation  # Mutation testing
composer benchmark      # Benchmark; --save=<file> and --compare=<file> check that the output is unchanged
composer build:schema   # Regenerate resources/svg-optimizer.schema.json from the Rule enum
```

Pass options to a script after `--`, for example `composer benchmark -- --iterations=1`.

`composer check` runs `check:composer-validate`, `check:composer-normalize`, `check:phpstan`, `check:rector`,
`check:php-cs-fixer` and `check:audit`. `check:autoload` and `check:platform` run separately.

### Rule Principles

- A rule that is not risky must be lossless: the optimized document renders exactly like the original in current
  browsers, keeps every id, link and animation that can be referenced from inside or outside the document, keeps sprite
  sheets working, and never rounds numbers.
- A rule that cannot guarantee this is risky, and the README explains why.
- Every rule is idempotent: running it twice gives the same result as running it once.
- Every rule produces well-formed XML and behaves the same on every supported PHP and libxml version.

### Rules and Passes

A **rule** is what a user selects: a case of the `Rule` enum, which is also the name in configuration files. A **pass**
is one transformation of a DOM document, a class in `src/Pass`. A rule runs one or more passes, for example
`removeEmptyElements` runs `RemoveEmptyContainers` and `RemoveEmptyTextElements`. The `ExecutionOrder` class lists every
pass in the order they run, whatever order the rules were selected in.

### Adding a Rule

1. Create a final readonly pass class in `src/Pass` that implements
   `MathiasReker\PhpSvgOptimizer\Pass\PassInterface` and its `apply(\DOMDocument $domDocument): void` method. Implement
   `RevertIfLargerPassInterface` instead when the pass can make the document larger, so the pipeline reverts it in that
   case. Put logic that several passes share in a trait in `src/Pass/Trait/<Topic>`, for example
   `src/Pass/Trait/Css/StyleSheetTrait.php`.
2. Add the pass to `src/Pass/ExecutionOrder.php`, after the passes it depends on. Security passes run first.
3. Add a case to `src/Rule.php`. The value of the case is the rule name, for example `case RemoveComments =
   'removeComments';`. Name the rule with a verb and its plural object, as the other rules do. Then add the case to the
   `match` expressions of `isRisky()` (a rule is risky when it can break references from outside the document),
   `getRuleSet()` (`null` if no set selects it), `getDescription()` and `getPassClasses()`, which lists its passes.
4. Run `composer build:schema` to regenerate `resources/svg-optimizer.schema.json`. `tests/Integration/SchemaTest.php`
   fails while the committed schema is out of date.
5. Write tests in `tests/Unit/Pass/<Pass>Test.php`, covering the edge cases and the documents the pass must leave
   untouched. Declare the coverage metadata the test needs: `#[CoversClass]` for the pass and the classes it executes,
   and `#[UsesClass]` for the classes it only uses. A test without it is reported as risky, which fails the run. Tests for a trait are in
   `tests/Unit/Pass/Trait/<Topic>`.
6. Run `composer test`. `tests/Integration/RuleInvariantsTest.php`, `SpriteTest.php` and `SampleFilesTest.php` run every
   rule over many documents and check that the output is valid, idempotent, keeps the ids of a rule that is not risky,
   and keeps sprite sheets working. `tests/Unit/RuleTest.php` and `tests/Unit/Pass/ExecutionOrderTest.php` check that
   every pass belongs to a rule and has a position.
7. Document the rule in `README.md`: add a section under `## Rules` in alphabetical order, with the set, the enum case,
   what it does, **Why it is risky** for a risky rule, and an input and output example that you ran. List it in the table
   under `## Rule sets`, and in the table under `### Risky rules` if it is risky.

## License

By contributing, you agree that your contributions are licensed under the [MIT License](LICENSE).
