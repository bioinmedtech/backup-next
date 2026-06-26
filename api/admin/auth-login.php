<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$body = bioinmed_admin_require_post_json();
$email = strtolower(trim((string)($body['email'] ?? '')));
$password = (string)($body['password'] ?? '');

if ($email === '' || $password === '') {
    bioinmed_admin_json_response([
        'ok' => false,
        'error' => 'Укажите email и пароль.',
    ], 422);
}

$usersPayload = bioinmed_admin_load_users();
$users = is_array($usersPayload['users'] ?? null) ? $usersPayload['users'] : [];

$found = null;
foreach ($users as &$user) {
    if (!is_array($user)) {
        continue;
    }

    if (strtolower((string)($user['email'] ?? '')) !== $email) {
        continue;
    }

    $found = &$user;
    break;
}
unset($user);

if (!$found || !bioinmed_admin_normalize_bool($found['is_active'] ?? false) || !password_verify($password, (string)($found['password_hash'] ?? ''))) {
    bioinmed_admin_json_response([
        'ok' => false,
        'error' => 'Неверный логин или пароль.',
    ], 401);
}

$found['last_login_at'] = gmdate('c');
$found['updated_at'] = gmdate('c');

bioinmed_admin_save_users($usersPayload);

session_regenerate_id(true);

$_SESSION['bioinmed_admin_user'] = [
    'id' => (string)($found['id'] ?? ''),
    'email' => (string)($found['email'] ?? ''),
    'name' => (string)($found['name'] ?? ''),
    'role' => (string)($found['role'] ?? ''),
];

// Set/refresh remember cookie and renew session cookie expiry
bioinmed_admin_set_remember_cookie($_SESSION['bioinmed_admin_user']);
setcookie(session_name(), session_id(), bioinmed_admin_cookie_params((int)($GLOBALS['bioinmedAdminSessionLifetime'] ?? 60*60*24*60)));

bioinmed_admin_json_response([
    'ok' => true,
    'config' => bioinmed_admin_client_config(),
]);
