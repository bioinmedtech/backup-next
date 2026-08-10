<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$actor = bioinmed_admin_require_auth(['admin']);

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method === 'GET') {
    bioinmed_admin_json_response([
        'ok' => true,
        'pinSettings' => bioinmed_admin_load_pin_settings(),
    ]);
}

if ($method !== 'POST') {
    bioinmed_admin_json_response([
        'ok' => false,
        'error' => 'Метод не поддерживается.',
    ], 405);
}

$body = bioinmed_admin_request_json();
if (!bioinmed_admin_verify_csrf((string)($body['csrf'] ?? ''))) {
    bioinmed_admin_json_response([
        'ok' => false,
        'error' => 'Некорректный CSRF токен.',
    ], 419);
}

$enabled = bioinmed_admin_normalize_bool($body['enabled'] ?? true);
$pin = trim((string)($body['pin'] ?? ''));
$existing = bioinmed_admin_load_pin_settings();
$onlineBookingEnabled = array_key_exists('online_booking_enabled', $body)
    ? bioinmed_admin_normalize_bool($body['online_booking_enabled'])
    : bioinmed_admin_normalize_bool($existing['online_booking_enabled'] ?? true);

if ($enabled && $pin === '') {
    $pin = '1290';
}

if ($enabled && !preg_match('/^[0-9]{4,12}$/', $pin)) {
    bioinmed_admin_json_response([
        'ok' => false,
        'error' => 'PIN должен содержать от 4 до 12 цифр.',
    ], 422);
}

if (!$enabled) {
    $pin = $pin !== '' ? $pin : (string)($existing['pin'] ?? '');
}

$payload = [
    'enabled' => $enabled,
    'pin' => $pin !== '' ? $pin : (string)($existing['pin'] ?? ''),
    'online_booking_enabled' => $onlineBookingEnabled,
    'updated_at' => gmdate('c'),
    'updated_by' => [
        'id' => (string)($actor['id'] ?? ''),
        'email' => (string)($actor['email'] ?? ''),
        'name' => (string)($actor['name'] ?? ''),
    ],
];

if (!bioinmed_admin_save_pin_settings($payload)) {
    bioinmed_admin_json_response([
        'ok' => false,
        'error' => 'Не удалось сохранить настройки PIN.',
    ], 500);
}

bioinmed_admin_json_response([
    'ok' => true,
    'message' => 'Настройки PIN сохранены.',
    'pinSettings' => $payload,
]);
