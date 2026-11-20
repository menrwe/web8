<?php
// ЛР №8, завдання 1–3: отримання JSON з API, json_decode(), об’єднання записів, HTML-таблиця
require_once __DIR__ . '/includes/common.php';

// 1. URL із GET-параметрами user та pass
$url = API_URL . '?' . http_build_query(['user' => API_USER, 'pass' => API_PASS]);
$json = http_get($url);                               // file_get_contents() / cURL

$error = null;
$people = [];
if ($json === null) {
    $error = 'Не вдалося отримати дані з API. Перевірте підключення до Інтернету та адресу: ' . $url;
} else {
    // 2. JSON -> об’єкти PHP (без другого аргументу json_decode повертає stdClass)
    $data = json_decode($json);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $error = 'Помилка розбору JSON: ' . json_last_error_msg();
    } else {
        // 3. усі записи про людей – в один масив
        collect_records($data, $people);
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ЛР №8 — дані з REST API</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="top">
    <h1>Веб-технології · ЛР №8 (REST API, JSON)</h1>
    <nav><a href="index.php">Дані з API</a> <a href="json.php">json.php</a> <a href="preview.php">preview.php</a></nav>
</header>
<main>
    <div class="card">
        <h2>Люди з API (<?= count($people) ?>)</h2>
        <p class="muted">Запит: <code>GET <?= e($url) ?></code></p>
        <?php if ($error): ?>
            <div class="errors"><?= e($error) ?></div>
        <?php else: ?>
            <?= render_table($people) ?>
        <?php endif; ?>
    </div>
</main>
<footer class="foot">Виконав: ст. гр. 3КН-26б Іван Галіцький · ВНТУ · <?= date('Y') ?></footer>
</body>
</html>
