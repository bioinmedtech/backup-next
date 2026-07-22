<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
bioinmed_admin_require_auth(['admin', 'editor']);

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    bioinmed_admin_json_response(['ok' => false, 'error' => 'Метод не поддерживается.'], 405);
}

$body = bioinmed_admin_require_post_json();
if (!bioinmed_admin_verify_csrf((string)($body['csrf'] ?? ''))) {
    bioinmed_admin_json_response(['ok' => false, 'error' => 'Некорректный CSRF токен.'], 419);
}

$page = trim((string)($body['page'] ?? ''));
$listKey = trim((string)($body['list_key'] ?? ''));
$rawItems = is_array($body['items'] ?? null) ? $body['items'] : [];

if (!preg_match('/^[a-zA-Z0-9_-]+$/', $page) || !in_array($page, ['doctor', 'service', 'problem', 'about', 'index', 'services', 'doctors', 'sterility', 'vacancies'], true)) {
    bioinmed_admin_json_response(['ok' => false, 'error' => 'Недопустимая страница списка.'], 422);
}
if ($listKey === '' || strlen($listKey) > 240 || !preg_match('/^[a-zA-Z0-9_.:-]+$/', $listKey)) {
    bioinmed_admin_json_response(['ok' => false, 'error' => 'Недопустимый ключ списка.'], 422);
}
if (count($rawItems) > 500) {
    bioinmed_admin_json_response(['ok' => false, 'error' => 'В списке слишком много элементов.'], 422);
}

$items = [];
$usedIds = [];
foreach ($rawItems as $index => $entry) {
    if (!is_array($entry)) continue;
    $text = trim((string)($entry['text'] ?? ''));
    if ($text === '') continue;
    if (mb_strlen($text, 'UTF-8') > 5000) $text = mb_substr($text, 0, 5000, 'UTF-8');
    $secondary = trim((string)($entry['secondary'] ?? ''));
    if (mb_strlen($secondary, 'UTF-8') > 10000) $secondary = mb_substr($secondary, 0, 10000, 'UTF-8');
    $url = trim((string)($entry['url'] ?? ''));
    if (mb_strlen($url, 'UTF-8') > 1000) $url = mb_substr($url, 0, 1000, 'UTF-8');
    if ($url !== '' && strpos($url, '/') !== 0 && !filter_var($url, FILTER_VALIDATE_URL)) $url = '';

    $id = preg_replace('/[^a-zA-Z0-9_-]+/', '-', trim((string)($entry['id'] ?? ''))) ?? '';
    $id = trim($id, '-_');
    if ($id === '') $id = 'item-' . substr(hash('sha256', $listKey . '|' . $index . '|' . $text . '|' . microtime(true)), 0, 12);
    $baseId = $id;
    $counter = 2;
    while (isset($usedIds[$id])) $id = $baseId . '-' . $counter++;
    $usedIds[$id] = true;

    $icon = trim((string)($entry['icon'] ?? ''));
    if (!preg_match('/^[a-zA-Z0-9 _-]{0,120}$/', $icon)) $icon = '';
    $items[] = ['id' => $id, 'text' => $text, 'secondary' => $secondary, 'url' => $url, 'icon' => $icon, 'hidden' => !empty($entry['hidden'])];
}

$path = BIOINMED_ADMIN_CONTENT_DIR . '/pages/' . $page . '.json';
$payload = bioinmed_admin_read_json($path, []);
if (!is_array($payload['editable_lists'] ?? null)) $payload['editable_lists'] = [];
$payload['editable_lists'][$listKey] = $items;

if (!bioinmed_admin_write_json($path, $payload)) {
    bioinmed_admin_json_response(['ok' => false, 'error' => 'Не удалось сохранить список.'], 500);
}

bioinmed_admin_json_response(['ok' => true, 'items' => $items, 'message' => 'Список сохранён.']);
