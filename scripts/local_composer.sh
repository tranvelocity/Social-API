#!/bin/bash

set -eu

rm -rf ./src/vendor

echo "===> Start installing composer"
sh ./scripts/app composer config --global --auth github-oauth.github.com f478a7a813b9a77219efa34056f27639ac8698ac
sh ./scripts/app composer install
docker compose exec php composer dump-autoload --no-scripts
