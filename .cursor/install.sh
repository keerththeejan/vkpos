#!/usr/bin/env bash
# Cloud Agent install script for VK POS / UltimatePOS (Laravel 9).
#
# Idempotent bootstrap that runs after the repository is checked out:
#   1. installs the PHP 8.2 toolchain, MariaDB and Composer (system deps)
#   2. installs PHP dependencies
#   3. recreates the git-ignored Laravel storage directories
#   4. creates and configures the .env file for the local MariaDB service
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_ROOT"

# --- System dependencies (guarded so re-runs are fast) ---------------------
if ! command -v php >/dev/null 2>&1; then
  sudo apt-get update -qq
  sudo DEBIAN_FRONTEND=noninteractive apt-get install -y -qq \
    software-properties-common ca-certificates
  sudo add-apt-repository -y ppa:ondrej/php
  sudo apt-get update -qq
  sudo DEBIAN_FRONTEND=noninteractive apt-get install -y -qq \
    php8.2-cli php8.2-common php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip \
    php8.2-gd php8.2-bcmath php8.2-intl php8.2-mysql php8.2-gmp php8.2-soap \
    php8.2-tokenizer php8.2-readline unzip
fi

if ! command -v mariadbd >/dev/null 2>&1 && ! command -v mysqld >/dev/null 2>&1; then
  sudo DEBIAN_FRONTEND=noninteractive apt-get install -y -qq \
    mariadb-server mariadb-client
fi

if ! command -v composer >/dev/null 2>&1; then
  php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');"
  sudo php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
  rm -f /tmp/composer-setup.php
fi

# --- PHP dependencies ------------------------------------------------------
composer install --no-interaction --prefer-dist --no-progress

# --- Laravel runtime directories (the whole storage/ tree is git-ignored) --
mkdir -p \
  storage/app/public \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/framework/testing \
  storage/logs \
  bootstrap/cache

# --- Environment file ------------------------------------------------------
if [ ! -f .env ]; then
  cp .env.example .env
fi

sed -i 's/^DB_HOST=.*/DB_HOST=127.0.0.1/' .env
sed -i 's/^DB_PORT=.*/DB_PORT=3306/' .env
sed -i 's/^DB_DATABASE=.*/DB_DATABASE=ultimatepos/' .env
sed -i 's/^DB_USERNAME=.*/DB_USERNAME=ultimatepos/' .env
sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=secret/' .env
sed -i 's|^APP_URL=.*|APP_URL=http://localhost:8000|' .env
sed -i 's/^BROADCAST_DRIVER=.*/BROADCAST_DRIVER=log/' .env

grep -q '^APP_KEY=base64:' .env || php artisan key:generate --force

php artisan storage:link --force >/dev/null 2>&1 || true
php artisan config:clear >/dev/null 2>&1 || true

echo "install.sh completed successfully."
