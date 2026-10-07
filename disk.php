<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/DiskRepository.php';

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

<div class="c64-directory">



<div class="c64-directory-header">
    <span class="c64-directory-title">
        <?= htmlspecialchars($disk['disk_name'] ?? '') ?>
    </span>

    <span class="c64-directory-id">
        <?= htmlspecialchars($disk['disk_id'] ?? '') ?>
        <?= htmlspecialchars($disk['dos_type'] ?? '') ?>
    </span>
</div>


    <div class="c64-directory-files">
        <?php foreach ($disk['files'] as $file): ?>
        <div class="c64-directory-row">
            <span class="c64-directory-blocks">
                <?= (int)$file['blocks'] ?>
            </span>

            <span class="c64-directory-name">
                "<?= htmlspecialchars($file['filename']) ?>"
            </span>

            <span class="c64-directory-type">
                <?= htmlspecialchars($file['file_type']) ?>
            </span>
        </div>
        <?php endforeach; ?>
    </div>

<div class="c64-directory-row c64-directory-free">
    <span class="c64-directory-blocks">
        <?= (int)$disk['blocks_free'] ?>
    </span>

    <span class="c64-directory-name">
        BLOCKS FREE.
    </span>

    <span class="c64-directory-type"></span>
</div>


</div>

</body>
</html>
