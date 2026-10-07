CREATE DATABASE IF NOT EXISTS c64diskmanager
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'c64diskmanager'@'localhost'
    IDENTIFIED BY 'CHANGE_THIS_PASSWORD';

GRANT ALL PRIVILEGES ON c64diskmanager.* TO 'c64diskmanager'@'localhost';

FLUSH PRIVILEGES;

USE c64diskmanager;

CREATE TABLE IF NOT EXISTS disks (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    filename VARCHAR(255) NOT NULL,
    md5 CHAR(32) NOT NULL,
    disk_name VARCHAR(16) DEFAULT NULL,
    disk_id CHAR(7) DEFAULT NULL,
    dos_type CHAR(2) DEFAULT NULL,
    blocks_free INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('ok', 'errors', 'fault') NOT NULL DEFAULT 'ok',
    box VARCHAR(100) DEFAULT NULL,
    comment TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_disks_md5 (md5)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS disk_files (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    disk_id INT UNSIGNED NOT NULL,
    filename VARCHAR(16) NOT NULL,
    file_type VARCHAR(3) NOT NULL,
    blocks INT UNSIGNED NOT NULL DEFAULT 0,
    locked TINYINT(1) NOT NULL DEFAULT 0,
    closed TINYINT(1) NOT NULL DEFAULT 1,
    start_track TINYINT UNSIGNED DEFAULT NULL,
    start_sector TINYINT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_disk_files_disk (disk_id),
    CONSTRAINT fk_disk_files_disk
        FOREIGN KEY (disk_id) REFERENCES disks(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;
