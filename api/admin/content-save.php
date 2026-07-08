<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

bioinmed_admin_require_auth(['admin', 'editor']);

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($method !== 'POST') {
    bioinmed_admin_json_response([
        'ok' => false,
        'error' => 'Метод не поддерживается.',
    ], 405);
}

$body = bioinmed_admin_require_post_json();

if (!bioinmed_admin_verify_csrf((string)($body['csrf'] ?? ''))) {
    bioinmed_admin_json_response([
        'ok' => false,
        'error' => 'Некорректный CSRF токен.',
    ], 419);
}

$textKey = trim((string)($body['text_key'] ?? ''));
$value = (string)($body['value'] ?? '');

if ($textKey === '') {
    bioinmed_admin_json_response([
        'ok' => false,
        'error' => 'Не указан ключ текста.',
    ], 422);
}

$target = bioinmed_admin_target_from_key($textKey);
if (!$target) {
    bioinmed_admin_json_response([
        'ok' => false,
        'error' => 'Недопустимый ключ текста.',
    ], 422);
}

$path = $target['json_path'];
$payload = bioinmed_admin_read_json($path, []);
$oldValue = bioinmed_admin_get_nested_value($payload, $target['field_path']);

// Do not persist empty updates: keep the previously saved value.
if (trim($value) === '') {
    $value = $oldValue;
}

bioinmed_admin_set_nested_value($payload, $target['field_path'], $value);
if (!bioinmed_admin_write_json($path, $payload)) {
    bioinmed_admin_json_response([
        'ok' => false,
        'error' => 'Не удалось сохранить изменения.',
    ], 500);
}

if (!bioinmed_admin_sync_service_price_from_prices_key($textKey, $value)) {
    bioinmed_admin_json_response([
        'ok' => false,
        'error' => 'Цена в прайсе сохранена, но не удалось синхронизировать с услугой.',
    ], 500);
}

bioinmed_admin_json_response([
    'ok' => true,
    'text_key' => $textKey,
    'value' => $value,
    'old_value' => $oldValue,
    'message' => 'Изменения сохранены.',
]);
