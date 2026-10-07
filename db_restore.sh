#!/usr/bin/env bash

set -euo pipefail

DEFAULT_INSTALL_DIR="/var/www/html/c64diskmanager"

echo "=== C64DiskManager database restore ==="
echo

read -r -p "Installation directory [$DEFAULT_INSTALL_DIR]: " INSTALL_DIR
INSTALL_DIR="${INSTALL_DIR:-$DEFAULT_INSTALL_DIR}"

CONFIG_FILE="$INSTALL_DIR/config/config.php"

if [[ ! -f "$CONFIG_FILE" ]]; then
    echo
    echo "ERROR: Config file not found:"
    echo "  $CONFIG_FILE"
    exit 1
fi

if ! command -v mariadb >/dev/null 2>&1; then
    echo
    echo "ERROR: mariadb is not installed."
    exit 1
fi

read -r -p "Backup file: " BACKUP_FILE

if [[ -z "$BACKUP_FILE" ]]; then
    echo
    echo "ERROR: No backup file specified."
    exit 1
fi

if [[ ! -f "$BACKUP_FILE" ]]; then
    echo
    echo "ERROR: Backup file not found:"
    echo "  $BACKUP_FILE"
    exit 1
fi

DB_NAME="$(php -r '
$config = require $argv[1];
echo $config["db_name"];
' "$CONFIG_FILE")"

DB_USER="$(php -r '
$config = require $argv[1];
echo $config["db_user"];
' "$CONFIG_FILE")"

DB_PASSWORD="$(php -r '
$config = require $argv[1];
echo $config["db_password"];
' "$CONFIG_FILE")"

DB_HOST="$(php -r '
$config = require $argv[1];
echo $config["db_host"];
' "$CONFIG_FILE")"

echo
echo "Installation: $INSTALL_DIR"
echo "Database:     $DB_NAME"
echo "Backup file:  $BACKUP_FILE"
echo
echo "WARNING: This will replace the current database contents."
echo

read -r -p "Continue? [y/N]: " CONFIRM

if [[ "$CONFIRM" != "y" && "$CONFIRM" != "Y" ]]; then
    echo "Restore cancelled."
    exit 0
fi

echo
echo "Restoring database..."

MYSQL_PWD="$DB_PASSWORD" mariadb \
    --host="$DB_HOST" \
    --user="$DB_USER" \
    "$DB_NAME" < "$BACKUP_FILE"

echo
echo "Database restore completed successfully."
