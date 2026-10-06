<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/DiskRepository.php';
require_once __DIR__ . '/src/C64PetsciiRenderer.php';

$config = require __DIR__ . '/config/config.php';
$repository = new DiskRepository((new Database($config))->pdo());

$id = (int)($_GET['id'] ?? 0);
$disk = $repository->find($id);

if (!$disk) {
    http_response_code(404);
    exit('Disken finns inte.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? 'ok';

    if (!in_array($status, ['ok', 'errors', 'fault'], true)) {
        $status = 'ok';
    }

    $box = trim($_POST['box'] ?? '');
    $comment = trim($_POST['comment'] ?? '');

    $repository->update(
        $id,
        $status,
        $box !== '' ? $box : null,
        $comment !== '' ? $comment : null
    );

    header("Location: disk.php?id={$id}");
    exit;
}
?>
<!doctype html>
<html lang="sv">
<head>
<meta charset="utf-8">
<title>Disk #<?= (int)$disk['id'] ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<p><a href="index.php">← Till registret</a></p>

<h1>Disk #<?= (int)$disk['id'] ?></h1>

<table class="info">
<tr><th>Filnamn</th><td><?= htmlspecialchars($disk['filename']) ?></td></tr>
<tr><th>MD5</th><td><?= htmlspecialchars($disk['md5']) ?></td></tr>
<tr><th>Importerad</th><td><?= htmlspecialchars($disk['created_at']) ?></td></tr>
</table>

<h2>Information</h2>

<form method="post">

<p>
<label>Status<br>
<select name="status">
<?php foreach (['ok', 'errors', 'fault'] as $status): ?>
<option value="<?= $status ?>" <?= $disk['status'] === $status ? 'selected' : '' ?>>
<?= $status ?>
</option>
<?php endforeach; ?>
</select>
</label>
</p>

<p>
<label>Diskett-box<br>
<input type="text" name="box" value="<?= htmlspecialchars($disk['box'] ?? '') ?>">
</label>
</p>

<p>
<label>Kommentar<br>
<textarea name="comment" rows="5" cols="60"><?= htmlspecialchars($disk['comment'] ?? '') ?></textarea>
</label>
</p>

<button type="submit">Spara</button>

</form>

<h2>Innehåll</h2>

<table>
<thead>
<tr>
<th>Filnamn</th>
<th>Typ</th>
<th>Blocks</th>
<th>Start</th>
<th>Låst</th>
</tr>
</thead>
<tbody>
<?php foreach ($disk['files'] as $file): ?>
<tr>
<td><?= htmlspecialchars($file['filename']) ?></td>
<td><?= htmlspecialchars($file['file_type']) ?></td>
<td><?= (int)$file['blocks'] ?></td>
<td><?= (int)$file['start_track'] ?> / <?= (int)$file['start_sector'] ?></td>
<td><?= $file['locked'] ? 'Ja' : '' ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php if (!empty($disk['directory_art'])): ?>

<h2>Directory Art</h2>

<?php
$renderer = new C64PetsciiRenderer(
    __DIR__ . '/assets/c64-chargen.bin'
);

echo $renderer->render(
    $disk['directory_art'],
    4
);
?>

<?php endif; ?>

</body>
</html>
