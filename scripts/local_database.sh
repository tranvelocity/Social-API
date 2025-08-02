#!/bin/bash

set -eu

echo "===> Database migration"
sh ./scripts/app php artisan migrate:fresh --seed --force --env=local # Refresh all modules migrations.
sh ./scripts/app php artisan module:seed # Seed all modules

sh ./scripts/app php artisan config:clear --env=local
