# Security Policy

## Supported Versions

Security fixes are released for the latest major version only.

| Version | Supported |
|---------|-----------|
| 10.x    | Yes       |
| < 10.0  | No        |

## Reporting a Vulnerability

Please do not report security vulnerabilities through public GitHub issues, discussions or pull requests.

Report them privately instead, in one of these ways:

- [Open a private security advisory](https://github.com/MathiasReker/php-svg-optimizer/security/advisories/new) on
  GitHub (preferred).
- Email [github@reker.dk](mailto:github@reker.dk).

Please include:

- the affected version and PHP and libxml versions (`php -v`, `php -r 'echo LIBXML_DOTTED_VERSION;'`);
- the rules or rule sets that were enabled;
- a minimal SVG that reproduces the issue, the optimized output, and where it was rendered (browser and version, and
  whether it was used through `<img>`, inline `<svg>`, `<object>` or opened directly);
- the impact you observed.

You will receive an acknowledgement of your report, followed by updates as it is investigated and fixed. Please keep
the details confidential until a fixed version is released. Reporters are credited in the security advisory unless they
prefer to remain anonymous.

## Scope

In scope:

- An optimized document that can run script or load an external resource in a browser while every rule of the
  `security` set is enabled (`removeUnsafeMarkup`, `removeScripts`, `removeForeignContent`, `removeUnsafeUrls`,
  `sanitizeStyleSheets`, `removeUseBombs`).
- Loading a document that reads local files, makes network requests, or exhausts memory or CPU in a way that is out of
  proportion to its size.

Out of scope:

- Documents optimized without the `security` set. Only those rules remove active content.
- A rendering difference in the output of a rule. Please report that as a regular bug.
