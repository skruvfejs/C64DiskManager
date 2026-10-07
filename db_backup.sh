#!/usr/bin/env bash

set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEFAULT_INSTALL_DIR="/var/www/html/c64diskmanager"

echo "=== C64DiskManager database backup ==="
echo

read -r -p "Installation directory [$DEFAULT_INSTALL_DIR]: " INSTALL_DIR
INSTALL_DIR="${INSTALL_DIR:-$DEFAULT_INSTALL_DIR}"

CONFIG_FILE="$INSTALL_DIR/config/config.php"
BACKUP_DIR="$PROJECT_DIR/backup"

if [[ ! -f "$CONFIG_FILE" ]]; then
    echo
    echo "ERROR: Config file not found:"
    echo "  $CONFIG_FILE"
    exit 1
fi

if ! command -v mariadb-dump >/dev/null 2>&1; then
    echo
    echo "ERROR: mariadb-dump is not installed."
    exit 1
fi

mkdir -p "$BACKUP_DIR"

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

TIMESTAMP="$(date '+%Y-%m-%d_%H-%M-%S')"
BACKUP_FILE="$BACKUP_DIR/c64diskmanager-$TIMESTAMP.sql"

echo
echo "Installation: $INSTALL_DIR"
echo "Database:     $DB_NAME"
echo "Backup:       $BACKUP_FILE"
echo


if MYSQL_PWD="$DB_PASSWORD" mariadb-dump \
    --host="$DB_HOST" \
    --user="$DB_USER" \
    --single-transaction \
    --routines \
    --triggers \
    "$DB_NAME" > "$BACKUP_FILE"
then
    echo "Backup completed successfully."
    echo
    echo "File:"
    echo "  $BACKUP_FILE"
else
    echo
    echo "ERROR: Database backup failed."
    rm -f "$BACKUP_FILE"
    exit 1
fi
