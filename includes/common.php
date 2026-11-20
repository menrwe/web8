<?php
/** Спільні функції ЛР №8 */
date_default_timezone_set('Europe/Kyiv');

// Адреса API з завдання 1 та GET-параметри доступу
define('API_URL', getenv('LAB8_API_URL') ?: 'http://lab.vntu.org/api-server/lab8.php');
define('API_USER', 'student');
define('API_PASS', 'p@ssw0rd');
// Папка з відповідями анкети з ЛР №6 (можна вказати папку survey вашого сайту з ЛР №6)
define('SURVEY_DIR', __DIR__ . '/../survey');

function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Отримання вмісту за URL: спочатку file_get_contents(), якщо не вийшло – cURL (curl_exec).
 * Повертає рядок або null у разі помилки.
 */
function http_get(string $url, int $timeout = 8): ?string
{
    // 1) file_get_contents() з таймаутом
    $ctx  = stream_context_create(['http' => ['timeout' => $timeout, 'ignore_errors' => false]]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body !== false) {
        return $body;
    }
    // 2) резервний варіант – cURL
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $body = curl_exec($ch);
        $ok   = $body !== false && curl_getinfo($ch, CURLINFO_RESPONSE_CODE) < 400;
        curl_close($ch);
        if ($ok) {
            return $body;
        }
    }
    return null;
}

/**
 * Рекурсивно збирає всі «записи про людей» з розпарсеного JSON в один плоский масив.
 * Запис – це об’єкт, що містить хоча б одне скалярне поле (ім’я, фракція тощо).
 * Якщо в JSON записи згруповані (наприклад, по факультетах/групах) – вони все одно потраплять в один масив.
 */
function collect_records($node, array &$out): void
{
    if (is_array($node)) {                       // JSON-масив
        foreach ($node as $item) {
            collect_records($item, $out);
        }
        return;
    }
    if (is_object($node)) {                      // JSON-об’єкт (stdClass після json_decode)
        $hasScalar = false;
        foreach (get_object_vars($node) as $value) {
            if (is_scalar($value) || $value === null) {
                $hasScalar = true;
                break;
            }
        }
        if ($hasScalar) {
            $out[] = $node;                      // це і є запис
        } else {
            foreach (get_object_vars($node) as $child) {
                collect_records($child, $out);   // група – йдемо углиб
            }
        }
    }
}

/** Рядок для комірки таблиці (масиви/об’єкти – у вигляді JSON) */
function cell_text($v): string
{
    if (is_array($v) && array_values($v) === $v && !array_filter($v, 'is_array') && !array_filter($v, 'is_object')) {
        return implode('; ', $v);                // простий список → "a; b"
    }
    if (is_array($v) || is_object($v)) {
        return json_encode($v, JSON_UNESCAPED_UNICODE);
    }
    return $v === null ? '' : (string)$v;
}

/** Виводить HTML-таблицю з масиву записів (об’єктів або асоціативних масивів) */
function render_table(array $records): string
{
    if (!$records) {
        return '<p>Даних немає.</p>';
    }
    $rows = array_map(fn($r) => (array)$r, $records);
    $cols = [];
    foreach ($rows as $r) {
        foreach (array_keys($r) as $k) {
            if (!in_array($k, $cols, true)) {
                $cols[] = $k;
            }
        }
    }
    $html = '<div class="scroll"><table><tr><th>№</th>';
    foreach ($cols as $c) {
        $html .= '<th>' . e($c) . '</th>';
    }
    $html .= '</tr>';
    foreach ($rows as $i => $r) {
        $html .= '<tr><td>' . ($i + 1) . '</td>';
        foreach ($cols as $c) {
            $html .= '<td>' . e(cell_text($r[$c] ?? '')) . '</td>';
        }
        $html .= '</tr>';
    }
    return $html . '</table></div>';
}

/* ---------- Анкети з ЛР №6 (бонус 1–2): читання .json та .txt ---------- */

/** Відповідність підписів у .txt-файлах та ключів JSON */
function txt_label_map(): array
{
    return [
        'дата'                 => 'submitted_at',
        'ім’я'                 => 'name',
        "ім'я"                 => 'name',
        'email'                => 'email',
        'частота використання' => 'usage_frequency',
        'завдання'             => 'tasks',
        'корисність'           => 'usefulness',
        'покращення'           => 'improvement',
    ];
}

/** Парсинг текстового файлу виду "Ключ: значення" (бонус 2) */
function parse_txt_survey(string $text): array
{
    $map = txt_label_map();
    $out = ['submitted_at' => '', 'name' => '', 'email' => '', 'usage_frequency' => '',
            'tasks' => [], 'usefulness' => '', 'improvement' => ''];
    $lastKey = null;
    foreach (preg_split('/\R/u', $text) as $line) {
        if (preg_match('/^\s*([^:]+?)\s*:\s*(.*)$/u', $line, $m) && isset($map[mb_strtolower($m[1])])) {
            $lastKey = $map[mb_strtolower($m[1])];
            $out[$lastKey] = $m[2];
        } elseif ($lastKey && trim($line) !== '') {
            $out[$lastKey] .= ' ' . trim($line);   // продовження багаторядкової відповіді
        }
    }
    $out['tasks'] = array_values(array_filter(array_map('trim', explode(';', (string)$out['tasks']))));
    return $out;
}

/** Зчитує всі файли анкет (.json та .txt) та повертає масив анкет: кожна – окремий елемент */
function load_survey_responses(): array
{
    $result = [];
    $files  = array_merge(glob(SURVEY_DIR . '/*.json') ?: [], glob(SURVEY_DIR . '/*.txt') ?: []);
    sort($files);
    foreach ($files as $file) {
        $raw = file_get_contents($file);
        if (str_ends_with($file, '.json')) {
            $d = json_decode($raw, true);
            if (!is_array($d)) {
                continue;                           // пошкоджений файл пропускаємо
            }
            $a = $d['answers'] ?? [];
            $item = [
                'submitted_at'    => $d['submitted_at'] ?? '',
                'name'            => $d['name'] ?? '',
                'email'           => $d['email'] ?? '',
                'usage_frequency' => $a['usage_frequency'] ?? '',
                'tasks'           => (array)($a['tasks'] ?? []),
                'usefulness'      => $a['usefulness'] ?? '',
                'improvement'     => $a['improvement'] ?? '',
            ];
        } else {
            $item = parse_txt_survey($raw);
        }
        $result[] = ['file' => basename($file)] + $item;
    }
    usort($result, fn($x, $y) => strcmp($x['submitted_at'], $y['submitted_at']));
    return $result;
}
