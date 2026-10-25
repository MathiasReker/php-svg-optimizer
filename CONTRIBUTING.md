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
   `fix(minify-path-data): keep arc flags separated`.
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
composer lint:all       # Fix the code style with Rector and PHP CS Fixer
composer analyze:all    # Static analysis and Composer checks
composer test           # Test suite
composer test:coverage  # Test suite with a coverage report (requires Xdebug or PCOV)
composer test:mutation  # Mutation testing
composer test:benchmark # Benchmark; --save=<file> and --compare=<file> check that the output is unchanged
```

### Rule Principles

- A rule that is not risky must be lossless: the optimized document renders exactly like the original in current
  browsers, keeps every id, link and animation that can be referenced from inside or outside the document, keeps sprite
  sheets working, and never rounds numbers.
- A rule that cannot guarantee this is risky, and the README explains why.
- Every rule is idempotent: running it twice gives the same result as running it once.
- Every rule produces well-formed XML and behaves the same on every supported PHP and libxml version.

### Adding a Rule

1. Create a final readonly pass class in `src/Service/Rule` that implements
   `MathiasReker\PhpSvgOptimizer\Pass\PassInterface`. Implement `RevertIfLargerPassInterface` instead when the pass can
   make the document larger, so the optimizer reverts it in that case.
2. Write tests in `tests/Unit/Service/Rule`, covering the edge cases and documents the pass must leave untouched.
3. Add a case to `src/Type/Rule.php`, map it to its passes in `Rule::passes()`, list it in `Rule::isRisky()` if it is
   risky, and add each pass to `src/Pass/ExecutionOrder.php` after the passes it depends on.
4. Register the rule in `src/Service/Facade/SvgOptimizerFacade.php`.
5. Document the rule in `README.md`.

## License

By contributing, you agree that your contributions are licensed under the [MIT License](LICENSE).
