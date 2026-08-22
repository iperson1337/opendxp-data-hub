# Testing

## Perform PHPStan Analysis

### data-hub only context

´´´bash
.github/ci/scripts/setup-opendxp-environment.sh
composer install
vendor/bin/phpstan analyse --memory-limit=-1
´´´

### OpenDxp context

´´´bash
composer require "phpstan/phpstan:^1.4" --dev
vendor/bin/phpstan analyse -c vendor/open-dxp/data-hub-bundle/phpstan.neon --memory-limit=-1
´´´
