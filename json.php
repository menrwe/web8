<?php
// Бонус 1–2: усі відповіді анкет з ЛР №6 у форматі JSON (читаються і .json, і .txt файли)
require_once __DIR__ . '/includes/common.php';

header('Content-Type: application/json; charset=utf-8');
echo json_encode(load_survey_responses(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
