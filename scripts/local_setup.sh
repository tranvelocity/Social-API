#!/bin/bash

set -eu

URL="dev-social.api.com"

docker compose up -d --build
echo "===> Starting up Docker containers..."

# Check if there are any running containers with specific names and stop them
containers=$(docker ps -q -f "name=php" -f "name=nginx" -f "name=mysql" -f "name=cache" -f "name=datastore")

if [ -n "$containers" ]; then
  echo "===> Stopping all running containers"
  docker stop $containers
else
  echo "===> No containers to stop"
fi

# Start and build the containers using docker compose
docker compose up -d --build

echo "===> Create .env file"
sh ./scripts/app cp .env.local .env

echo "===> Start installing composer"
#sh ./scripts/app composer config --global --auth github-oauth.github.com f478a7a813b9a77219efa34056f27639ac8698ac

docker compose exec php composer clear-cache
docker compose exec php rm -rf /root/.composer/cache
sh ./scripts/app composer install
docker compose exec php composer dump-autoload --no-scripts

echo "===> Generate Laravel application key"
sh ./scripts/app php artisan key:generate

echo "===> Database migration"
sh ./scripts/app php artisan config:clear --env=local
sh ./scripts/app php artisan migrate:fresh --seed --force --env=local # Refresh all modules migrations.
sh ./scripts/app php artisan module:seed # Seed all modules

echo "===> Restart Docker containers..."
docker compose restart

sh ./scripts/app php artisan test --parallel
echo "===> Tests are done!"

echo "===> Add domain to hosts file"
sudo -- sh -c "grep -q '127.0.0.1 ${URL}' /etc/hosts || echo '127.0.0.1 ${URL}' >> /etc/hosts"

echo "===> Local setup successfully finished!"
find . -name "*.DS_Store" -type f -delete
