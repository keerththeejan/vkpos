#!/usr/bin/env bash
# Cloud Agent start script for VK POS / UltimatePOS (Laravel 9).
#
# Runs on every boot. Brings up MariaDB, guarantees the application database
# and user exist, applies pending migrations and seeds the baseline reference
# data (permissions, currencies, barcodes) the first time only.
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_ROOT"

# --- Start MariaDB and wait until it accepts connections -------------------
sudo service mariadb start || true
for _ in $(seq 1 30); do
  if sudo mysqladmin ping >/dev/null 2>&1; then
    break
  fi
  sleep 1
done

# --- Ensure the application database and user exist (idempotent) -----------
sudo mysql <<'SQL'
CREATE DATABASE IF NOT EXISTS ultimatepos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'ultimatepos'@'localhost' IDENTIFIED BY 'secret';
CREATE USER IF NOT EXISTS 'ultimatepos'@'127.0.0.1' IDENTIFIED BY 'secret';
GRANT ALL PRIVILEGES ON ultimatepos.* TO 'ultimatepos'@'localhost';
GRANT ALL PRIVILEGES ON ultimatepos.* TO 'ultimatepos'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

# --- Apply migrations (only pending ones run) ------------------------------
php artisan migrate --force

# --- Seed baseline reference data once -------------------------------------
currency_count="$(mysql -uultimatepos -psecret -h127.0.0.1 -N \
  -e 'SELECT COUNT(*) FROM ultimatepos.currencies' 2>/dev/null || echo 0)"
if [ "${currency_count:-0}" = "0" ]; then
  php artisan db:seed --force
fi

echo "start.sh completed successfully."
