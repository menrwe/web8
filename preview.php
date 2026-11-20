<?php
// Бонус 3: preview.php отримує дані з json.php (file_get_contents / cURL) і виводить їх таблицею
require_once __DIR__ . '/includes/common.php';

$scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$jsonUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/json.php';

$mode = 'HTTP-запит до json.php';
$json = http_get($jsonUrl, 3);

if ($json === null) {
    // Вбудований сервер PHP (php -S) однопотоковий і не може обслужити запит до самого себе.
    // У такому разі виконуємо json.php локально, щоб сторінка все одно працювала.
    ob_start();
    require __DIR__ . '/json.php';
    $json = ob_get_clean();
    $mode = 'локальне виконання json.php (HTTP-запит до себе недоступний)';
}

$items = json_decode($json, true) ?: [];
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ЛР №8 — preview</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="top">
    <h1>Веб-технології · ЛР №8 (REST API, JSON)</h1>
    <nav><a href="index.php">Дані з API</a> <a href="json.php">json.php</a> <a href="preview.php">preview.php</a></nav>
</header>
<main>
    <div class="card">
        <h2>Відповіді анкети (<?= count($items) ?>)</h2>
        <p class="muted">Джерело: <code><?= e($jsonUrl) ?></code> · спосіб: <?= e($mode) ?></p>
        <?= render_table($items) ?>
    </div>
</main>
<footer class="foot">Виконав: ст. гр. 3КН-26б Іван Галіцький · ВНТУ · <?= date('Y') ?></footer>
</body>
</html>
