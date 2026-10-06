<?php

declare(strict_types=1);

class DiskRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByMd5(string $md5): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, filename FROM disks WHERE md5 = ?'
        );
        $stmt->execute([$md5]);

        return $stmt->fetch() ?: null;
    }

public function create(
    string $filename,
    string $md5,
    array $files,
    string $status = 'ok',
    ?string $directoryArt = null
): int
    {
        $this->pdo->beginTransaction();

        try {
	    $stmt = $this->pdo->prepare(
	    'INSERT INTO disks (filename, md5, status, directory_art) VALUES (?, ?, ?, ?)'
	    );
	    $stmt->execute([$filename, $md5, $status, $directoryArt]);

            $diskId = (int)$this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare(
                'INSERT INTO disk_files
                (disk_id, filename, file_type, blocks, locked, closed,
                 start_track, start_sector)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );

            foreach ($files as $file) {
                $stmt->execute([
                    $diskId,
                    $file['filename'],
                    $file['file_type'],
                    $file['blocks'],
                    $file['locked'] ? 1 : 0,
                    $file['closed'] ? 1 : 0,
                    $file['start_track'],
                    $file['start_sector'],
                ]);
            }

            $this->pdo->commit();

            return $diskId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getPage(int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare(
            'SELECT d.*, COUNT(f.id) AS file_count
             FROM disks d
             LEFT JOIN disk_files f ON f.disk_id = d.id
             GROUP BY d.id
             ORDER BY d.id DESC
             LIMIT :limit OFFSET :offset'
        );

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function count(): int
    {
        return (int)$this->pdo
            ->query('SELECT COUNT(*) FROM disks')
            ->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM disks WHERE id = ?'
        );
        $stmt->execute([$id]);

        $disk = $stmt->fetch();

        if (!$disk) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT * FROM disk_files
             WHERE disk_id = ?
             ORDER BY id'
        );
        $stmt->execute([$id]);

        $disk['files'] = $stmt->fetchAll();

        return $disk;
    }

    public function update(int $id, string $status, ?string $box, ?string $comment): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE disks
             SET status = ?, box = ?, comment = ?
             WHERE id = ?'
        );

        $stmt->execute([$status, $box, $comment, $id]);
    }
}
