<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/D64Parser.php';
require_once __DIR__ . '/src/DiskRepository.php';

$config = require __DIR__ . '/config/config.php';
$repository = new DiskRepository((new Database($config))->pdo());

$messages = [];

function importD64(
    string $path,
    string $displayName,
    DiskRepository $repository
): string {
    $data = file_get_contents($path);

    if ($data === false) {
        return "{$displayName}: kunde inte läsa filen.";
    }

    $md5 = md5($data);
    $existing = $repository->findByMd5($md5);

    if ($existing) {
        return "{$displayName}: redan importerad som disk #{$existing['id']}.";
    }

    try {
        $parsed = (new D64Parser($data))->parse();

        $status = empty($parsed['errors']) ? 'ok' : 'errors';

        $id = $repository->create(
            basename($displayName),
            $md5,
            $parsed['files'],
            $status,
            !empty($parsed['directory_art'])
                ? implode('', $parsed['directory_art'])
                : null
        );

        $result =
            "{$displayName}: importerad som disk #{$id} (" .
            count($parsed['files']) .
            " filer, status {$status})";

        if (!empty($parsed['errors'])) {
            $result .= ' - ' . implode('; ', $parsed['errors']);
        }

        return $result;

    } catch (Throwable $e) {
        return "{$displayName}: FEL - " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['d64']) && $_FILES['d64']['error'] === UPLOAD_ERR_OK) {
        $name = $_FILES['d64']['name'];

        if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) === 'd64') {
            $messages[] = importD64(
                $_FILES['d64']['tmp_name'],
                $name,
                $repository
            );
        } else {
            $messages[] = "{$name}: ignorerad, inte en .d64-fil.";
        }
    }

if (isset($_FILES['d64_files'])) {
    $files = $_FILES['d64_files'];

    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }

        $name = $files['name'][$i];

        if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'd64') {
            continue;
        }

        $messages[] = importD64(
            $files['tmp_name'][$i],
            $name,
            $repository
        );
    }
}


}
?>
<!doctype html>
<html lang="sv">
<head>
<meta charset="utf-8">
<title>Importera D64</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<h1>Importera D64</h1>

<p><a href="index.php">← Till registret</a></p>

<?php foreach ($messages as $message): ?>
<p><?= htmlspecialchars($message) ?></p>
<?php endforeach; ?>

<h2>En fil</h2>

<form method="post" enctype="multipart/form-data">
<input type="file" name="d64" accept=".d64" required>
<button type="submit">Importera fil</button>
</form>

<h2>Flera filer</h2>

<p>Välj en eller flera .d64-filer. Alla valda filer importeras.</p>

<form method="post" enctype="multipart/form-data">
<input type="file" name="d64_files[]" accept=".d64" multiple required>
<button type="submit">Importera filer</button>
</form>

</body>
</html>
