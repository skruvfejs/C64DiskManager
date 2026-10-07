# C64DiskManager

Simple PHP/MariaDB register for Commodore 64 D64 disks.

## Features

- Import one `.d64` file or a complete directory of `.d64` files
- MD5 duplicate detection
- Reads the D64 directory
- Stores filename, file type, block count and file start position
- Unique disk ID
- Disk name and DOS type
- Free block count
- Status: `ok`, `errors`, `fault`
- Diskette box field
- Comment field
- Disk list with simple pagination
- Disk detail page
- MariaDB
- Database backup and restore scripts
- Installs into an existing `/var/www/` directory

## Requirements

- Linux
- Apache2
- PHP
- PHP PDO MySQL extension
- MariaDB
- Bash

## Install

Copy the project to:

```text
~/Projects/C64DiskManager
```

Then run:

```bash
cd ~/Projects/C64DiskManager
./install.sh
```

The installer:

1. Checks that Apache, PHP, PDO MySQL and MariaDB are installed.
2. Starts MariaDB if necessary.
3. Creates the `c64diskmanager` database and database user.
4. Asks which `/var/www/` directory should be used.
5. Installs the PHP application there.
6. Creates the local database configuration.
7. Prints the web URL/path.

The default installation directory is:

```text
/var/www/html/c64diskmanager
```

The installer needs sudo/root access.

## Database backup

The database can be backed up using:

```bash
./db_backup.sh
```

The script asks for the installation directory and reads the database connection information from:

```text
<installation directory>/config/config.php
```

Backups are stored in:

```text
backup/
```

The `backup/` directory is excluded from Git.

Example:

```text
backup/c64diskmanager-2026-10-07_11-36-59.sql
```

## Database restore

To restore a database backup:

```bash
./db_restore.sh
```

The script asks for:

- Installation directory
- Backup file

A confirmation is required before the restore is performed.

## Project structure

```text
C64DiskManager/
├── config/
│   └── config.php.example
├── database/
│   └── schema.sql
├── src/
│   ├── D64Parser.php
│   ├── Database.php
│   └── DiskRepository.php
├── db_backup.sh
├── db_restore.sh
├── disk.php
├── import.php
├── index.php
├── install.sh
└── style.css
```

The actual `config/config.php` is created during installation and is not stored in Git.

## GitHub

Repository:

```text
git@github.com:skruvfejs/C64DiskManager.git
```

Clone with:

```bash
git clone git@github.com:skruvfejs/C64DiskManager.git
```

Or add the repository to an existing checkout:

```bash
git remote add origin git@github.com:skruvfejs/C64DiskManager.git
git push -u origin main
```
