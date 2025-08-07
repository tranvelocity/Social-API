#!/bin/bash

# Function to execute commands inside the PHP container
execute_php_command() {
  docker compose exec php bash -c "$1"
}

# Function to create directories and files
create_files() {
  execute_php_command "cd /var/www/Modules/${MODULE_NAME}/$1 && mkdir -p $2"
  for file in "${@:3}"; do
    execute_php_command "cd /var/www/Modules/${MODULE_NAME}/$1/$2 && touch $file"
  done
}

# Function to create migration files
create_migration_files() {
  # shellcheck disable=SC2162
  read -p "Enter table name: " tableName
  sh ./scripts/app php artisan module:make-migration "create_${tableName}_table ${MODULE_NAME}"
  sh ./scripts/app php artisan module:make-factory "${MODULE_NAME} ${MODULE_NAME}"
  sh ./scripts/app php artisan module:seed "${MODULE_NAME}"
}

# Function to create middleware
create_middleware() {
  # shellcheck disable=SC2162
  read -p "Do you want to create a middleware (y/n)? " middlewareCreationAnswer
  if [[ $middlewareCreationAnswer =~ ^[Yy]$ ]]; then
    sh ./scripts/app php artisan module:make-middleware "${MODULE_NAME}Middleware ${MODULE_NAME}"
  fi
}

# Function to create requests
create_requests() {
  for requestType in "Search" "Create" "Update"; do
    sh ./scripts/app php artisan module:make-request "${requestType}${MODULE_NAME}Request ${MODULE_NAME}"
  done
}

# Create module using module:make command
# shellcheck disable=SC2162
read -p "Enter module name you want to make: " MODULE_NAME
sh ./scripts/app php artisan module:make "${MODULE_NAME}"

# Create Lang files
# shellcheck disable=SC2162
read -p "Do you want to create Lang files (y/n) ? " langFileCreationAnswer
if [[ $langFileCreationAnswer =~ ^[Yy]$ ]]; then
  execute_php_command "cd /var/www/Modules/${MODULE_NAME} && mkdir -p lang/en lang/ja"
  execute_php_command "cd /var/www/Modules/${MODULE_NAME}/lang/en && touch messages.php errors.php"
  execute_php_command "cd /var/www/Modules/${MODULE_NAME}/lang/ja && touch messages.php errors.php"
else
  execute_php_command "cd /var/www/Modules/${MODULE_NAME} && rm -rf lang"
fi

# Create files relevant to the database
read -p "Do you want to create a migration file (y/n)? " migrationFileCreationAnswer
if [[ $migrationFileCreationAnswer =~ ^[Yy]$ ]]; then
  create_migration_files
else
  execute_php_command "cd /var/www/Modules/${MODULE_NAME} && rm -rf database"
fi

# Create model
sh ./scripts/app php artisan module:make-model "${MODULE_NAME} ${MODULE_NAME}"

# Create middleware
create_middleware

# Create requests
create_requests

# Create Repositories directory and repository files
create_files "app" "Repositories" "${MODULE_NAME}Repository.php" "${MODULE_NAME}RepositoryInterface.php"

# Create Service
create_files "app" "Services" "${MODULE_NAME}Service.php"

# Create Resource file
sh ./scripts/app php artisan module:make-resource "${MODULE_NAME}Resource ${MODULE_NAME}"

# Remove unused files
execute_php_command "cd /var/www/Modules/${MODULE_NAME} && rm -rf resources package.json webpack.mix.js routes/web.php vite.config.js"
execute_php_command "cd /var/www && find . -name '*.gitkeep' -type f -delete"
execute_php_command "cd /var/www && find . -name '*.DS_Store' -type f -delete"

echo "Finished creating ${MODULE_NAME} module"
