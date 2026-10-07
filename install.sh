#!/usr/bin/env bash

set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONFIG_FILE="$PROJECT_DIR/config/config.php"

echo "=== C64DiskManager installer ==="
echo

if [[ $EUID -ne 0 ]]; then
    SUDO="sudo"
else
    SUDO=""
fi

echo "Checking required software..."

if ! command -v mariadb >/dev/null 2>&1; then
    echo "ERROR: MariaDB is not installed."
    exit 1
fi

if ! command -v php >/dev/null 2>&1; then
    echo "ERROR: PHP is not installed."
    exit 1
fi

if ! command -v apache2 >/dev/null 2>&1; then
    echo "ERROR: Apache2 is not installed."
    exit 1
fi

if ! php -m | grep -q '^pdo_mysql$'; then
    echo "ERROR: PHP PDO MySQL extension is not installed."
    exit 1
fi

echo
echo "MariaDB status:"

if ! $SUDO systemctl is-active --quiet mariadb; then
    echo "Starting MariaDB..."
    $SUDO systemctl start mariadb
fi

echo
read -r -p "Database password for c64diskmanager: " DB_PASSWORD
echo

if [[ -z "$DB_PASSWORD" ]]; then
    echo "Password may not be empty."
    exit 1
fi

echo "Creating database..."

SQL_FILE="$PROJECT_DIR/database/schema.sql"
TMP_SQL="$(mktemp)"

sed "s/CHANGE_THIS_PASSWORD/$(printf '%s' "$DB_PASSWORD" | sed 's/[&/\]/\\&/g')/" \
    "$SQL_FILE" > "$TMP_SQL"

$SUDO mariadb < "$TMP_SQL"
rm -f "$TMP_SQL"

echo
echo "Available /var/www directories:"
find /var/www -mindepth 1 -maxdepth 1 -type d -printf '  %p\n' 2>/dev/null || true
echo

read -r -p "Install web application into [/var/www/html/c64diskmanager]: " WEB_ROOT
WEB_ROOT="${WEB_ROOT:-/var/www/html/c64diskmanager}"

if [[ "$WEB_ROOT" != /var/www/html/* ]]; then
    echo "The web directory must be below /var/www/."
    exit 1
fi

echo
echo "Installing to $WEB_ROOT ..."

$SUDO mkdir -p "$WEB_ROOT"
$SUDO mkdir -p "$WEB_ROOT/config"

$SUDO cp "$PROJECT_DIR/index.php" "$WEB_ROOT/"
$SUDO cp "$PROJECT_DIR/import.php" "$WEB_ROOT/"
$SUDO cp "$PROJECT_DIR/disk.php" "$WEB_ROOT/"
$SUDO cp "$PROJECT_DIR/style.css" "$WEB_ROOT/"

$SUDO cp -r "$PROJECT_DIR/src" "$WEB_ROOT/"
$SUDO cp -r "$PROJECT_DIR/assets" "$WEB_ROOT/"

$SUDO cp "$PROJECT_DIR/config/config.php.example" \
    "$WEB_ROOT/config/config.php"

$SUDO sed -i \
    "s/CHANGE_THIS_PASSWORD/$(printf '%s' "$DB_PASSWORD" | sed 's/[&/\]/\\&/g')/" \
    "$WEB_ROOT/config/config.php"

$SUDO chown -R www-data:www-data "$WEB_ROOT"

echo
echo "Installation complete."
echo
echo "Web root:"
echo "  $WEB_ROOT"
echo
echo "If your existing web server points at this directory, open:"
echo "  http://<server>$(echo "$WEB_ROOT" | sed 's#^/var/www/html##')/"
echo
echo "Note: if the web server uses a different PHP user, adjust ownership accordingly."
