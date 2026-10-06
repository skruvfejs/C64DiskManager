# C64DiskManager

Simple PHP/MariaDB register for Commodore 64 D64 disks.

Features:
- Import one `.d64` file or a complete directory of `.d64` files
- MD5 duplicate detection
- Reads the D64 directory
- Unique disk ID
- Status: `ok`, `errors`, `fault`
- Diskette box field
- Comment field
- Disk list with simple pagination
- Disk detail page
- MariaDB
- Installs into an existing `/var/www/` directory

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
1. Installs MariaDB if it is missing.
2. Creates the `c64diskmanager` database and database user.
3. Asks which `/var/www/` directory should be used.
4. Installs the PHP application there.
5. Prints the web URL/path.

The installer needs sudo/root access.

## GitHub

Create the GitHub repository `C64DiskManager` under `skruvfejs`, then:

```bash
git remote add origin git@github.com:skruvfejs/C64DiskManager.git
git push -u origin main
```
