# Testing & Static Analysis

All tooling is driven through Composer. Install the dependencies first:

```bash
composer install
```

## Unit tests (PHPUnit)

```bash
vendor/bin/phpunit
# or
composer test
```

The configuration lives in `phpunit.xml.dist` (PHPUnit 10/11). Tests are located in `tests/`.

## Static analysis (PHPStan)

```bash
vendor/bin/phpstan analyse --memory-limit=-1
# or
composer phpstan
```

The configuration lives in `phpstan.neon.dist` (level 5, `src` and `tests`). Note that a full analysis requires
the OpenDXP core (`open-dxp/opendxp`) and admin bundle to be installed, since most classes extend core classes.

When running from within an OpenDXP project you can point PHPStan at the bundle configuration:

```bash
vendor/bin/phpstan analyse -c vendor/iperson1337/opendxp-data-hub/phpstan.neon.dist --memory-limit=-1
```

## Coding standards (PHP CS Fixer)

```bash
# check only
vendor/bin/php-cs-fixer fix --dry-run --diff
# or
composer cs-check

# apply fixes
vendor/bin/php-cs-fixer fix
# or
composer cs-fix
```

The ruleset lives in `.php-cs-fixer.dist.php` (PSR-12 plus a subset of the Symfony rules).

## Continuous integration

The GitHub Actions workflow in `.github/workflows/ci.yml` runs a PHP syntax check, `composer validate --strict`,
PHPUnit, PHPStan and the PHP CS Fixer dry run on PHP 8.3 and 8.4.
