<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
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

unset($_SESSION['bioinmed_admin_user']);
bioinmed_admin_clear_remember_cookie();

bioinmed_admin_json_response([
    'ok' => true,
    'config' => bioinmed_admin_client_config(),
]);
