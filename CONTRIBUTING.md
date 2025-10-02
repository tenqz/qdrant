# Contributing Guide

Thank you for contributing to the Qdrant PHP Client Library! This guide outlines quality expectations, coding style, and the contribution process.

## Quick Start

**Requirements:** PHP 7.2+, ext-curl, ext-json

```bash
# Install dependencies
composer install

# Run all checks (style + static analysis + tests)
composer run test

# Or separately:
composer run check-style      # Check code style
composer run cs-fix            # Auto-fix code style  
composer run analyze           # Static analysis (PHPStan)
composer run phpunit           # Run tests
```

## Commits — Iterative and Atomic

- Keep changes **small and atomic**: one commit = one logical change
- Every commit must pass checks (`composer test`)
- Prefer small, focused PRs
- Each commit should be buildable and testable on its own

## Mandatory Comments and Tests

- Changes to **public interfaces** and non-trivial logic must include concise PHPDoc/comments (explain the **"why"**, not the "how")
- New features **must include unit tests**; bug fixes should include regression tests
- **Do not regress quality**: run `composer run test` before pushing
- Aim for **100% test coverage** for new code in the Domain and Infrastructure layers

## Commit Message Convention

The commit message must clearly convey the change and start with a type:

| Prefix | Description |
|--------|-------------|
| `FEAT` | Add new functionality |
| `FIX` | Bug fixes |
| `REFACTOR` | Refactoring without changing behavior |
| `PERF` | Performance improvements |
| `TEST` | Add or fix tests |
| `CHORE` | Technical changes not affecting code behavior (configs, deps) |
| `DOCS` | Documentation updates |
| `STYLE` | Code style (spaces, formatting) |
| `BUILD` | Build-related changes |
| `CI` | CI/CD configuration |

### Message Format

We follow [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>: <imperative short summary>

<optional body: details why and what changed, notable nuances, any BC impact>

<optional footer: Refs #<issue>, Related #<issue>, Breaking Change notice>
```

### Examples

**Documentation:**
```
DOCS: Add library architecture section and error handling guide to README

Added Clean Architecture structure diagram with Domain/Infrastructure layers.
Added comprehensive error handling section with exception types table.
Added custom HTTP client implementation example using Guzzle.
Updated version to v0.2.0 and revised feature descriptions.
```

**Refactoring:**
```
REFACTOR: Remove integration test suite from phpunit.xml

Removed <testsuite name="Integration"> section as integration tests are not yet implemented.
Added exclude pattern for src/Exception directory in code coverage.
Updated coverage processUncoveredFiles setting to false for faster runs.
```

**Feature:**
```
FEAT: Add HTTPS support to CurlHttpClientFactory

Added 'scheme' parameter to factory create() method (defaults to 'http').
Updated CurlHttpClient to build base URL with configurable protocol scheme.
Allows secure connections to Qdrant cloud instances via HTTPS.

Refs #15
```

**Bug Fix:**
```
FIX: Add curl_init() failure handling with NetworkException

Added false check after curl_init() to catch initialization failures.
Throws NetworkException with descriptive message when cURL fails to initialize.
Added unit test for curl_init() failure scenario.

Fixes #23
```

**Testing:**
```
TEST: Add @testdox annotations to CurlHttpClientFactoryTest

Added testdox annotations to all test methods for readable test documentation.
Changed createParametersProvider() visibility from private to public static.
All 10 tests now display human-readable descriptions in --testdox output.
```

**Breaking Change:**
```
FEAT!: Change HttpClientInterface signature to return Response object

Changed request() return type from array to Response object.
Updated CurlHttpClient to return Response with status, headers, and body.
Updated all existing usages in QdrantClient and tests.

BREAKING CHANGE: request() now returns Response instead of array.
See UPGRADE.md for migration guide.

Refs #42
```

## Code Style and Checks

- **Style:** PSR-12 compatible (enforced by `php-cs-fixer` and `phpcs`)
- **Static analysis:** PHPStan level 5 (`phpstan analyse`)
- **Type safety:** Use strict types (`declare(strict_types=1)`) and comprehensive PHPDoc
- **Unified check:** `composer run test` runs all quality checks

### Branch Naming

- `feature/<short-description>` — new features

## Security

- **Never commit** secrets/keys/passwords
- Use **environment variables** and local configs outside the repo
- Add sensitive files to `.gitignore`
- Report security issues privately to: smmartbiz@gmail.com

## Documentation

- Update **README.md** for user-facing changes
- Update **inline PHPDoc** for all public APIs
- Add **examples** for new features in `examples/` directory
- Keep **architecture documentation** current

## Getting Help

- **Questions:** Open a discussion on GitHub
- **Bugs:** Open an issue with reproduction steps
- **Features:** Open an issue to discuss before implementing
- **Security:** Email smmartbiz@gmail.com privately

## Code of Conduct

- Be respectful and constructive
- Welcome newcomers and help them learn
- Focus on what is best for the community
- Show empathy towards other contributors

---

**Thank you for your contribution!** 🙏

Every contribution, no matter how small, helps make this library better for everyone.

