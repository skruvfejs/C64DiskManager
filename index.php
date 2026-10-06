<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/DiskRepository.php';

$config = require __DIR__ . '/config/config.php';
$repository = new DiskRepository((new Database($config))->pdo());

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$total = $repository->count();
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$disks = $repository->getPage($page, $perPage);
?>
<!doctype html>
<html lang="sv">
<head>
<meta charset="utf-8">
<title>C64 DiskManager</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<h1>C64 DiskManager</h1>

<p><a class="button" href="import.php">Importera D64</a></p>

<table>
<thead>
<tr>
<th>ID</th>
<th>Filnamn</th>
<th>Filer</th>
<th>Status</th>
<th>Box</th>
<th>Kommentar</th>
<th>Importerad</th>
</tr>
</thead>
<tbody>
<?php foreach ($disks as $disk): ?>
<tr>
<td><a href="disk.php?id=<?= (int)$disk['id'] ?>">#<?= (int)$disk['id'] ?></a></td>
<td><?= htmlspecialchars($disk['filename']) ?></td>
<td><?= (int)$disk['file_count'] ?></td>
<td class="status-<?= htmlspecialchars($disk['status']) ?>">
    <?= htmlspecialchars($disk['status']) ?>
</td>
<td><?= htmlspecialchars($disk['box'] ?? '') ?></td>
<td><?= htmlspecialchars($disk['comment'] ?? '') ?></td>
<td><?= htmlspecialchars($disk['created_at']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<div class="pagination">
<?php if ($page > 1): ?>
<a href="?page=<?= $page - 1 ?>">&laquo; Föregående</a>
<?php endif; ?>

<span>Sida <?= $page ?> / <?= $totalPages ?></span>

<?php if ($page < $totalPages): ?>
<a href="?page=<?= $page + 1 ?>">Nästa &raquo;</a>
<?php endif; ?>
</div>

<p>Totalt <?= $total ?> diskar.</p>
</body>
</html>
