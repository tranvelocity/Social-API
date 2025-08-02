#!/bin/bash

echo "Select the type of test:"
echo "1. PHPStan"
echo "2. PHPUnit Test"
echo "3. Artisan Test"
echo "4. Parallel Test"
echo "5. Coding Standards Check"

# shellcheck disable=SC2162
read -p "Select the type of test: " testType

case $testType in
  1)
    sh ./scripts/app ./vendor/bin/phpstan analyse
    ;;
  2)
    sh ./scripts/app php vendor/bin/phpunit
    ;;
  3)
    sh ./scripts/app php artisan test
    ;;
  4)
    sh ./scripts/app php artisan test --parallel
    ;;
  5)
    sh ./scripts/app vendor/bin/php-cs-fixer fix --dry-run --diff
    ;;
  *)
    sh ./scripts/app vendor/bin/php-cs-fixer fix --dry-run --diff
    sh ./scripts/app ./vendor/bin/phpstan analyse
    sh ./scripts/app php artisan test --parallel
    ;;
esac
