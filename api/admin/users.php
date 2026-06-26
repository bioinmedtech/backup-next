<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$actor = bioinmed_admin_require_auth(['admin']);

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$payload = bioinmed_admin_load_users();
$users = is_array($payload['users'] ?? null) ? $payload['users'] : [];

$publicUser = static function (array $user): array {
    return [
        'id' => (string)($user['id'] ?? ''),
        'email' => (string)($user['email'] ?? ''),
        'name' => (string)($user['name'] ?? ''),
        'role' => (string)($user['role'] ?? 'editor'),
        'is_active' => bioinmed_admin_normalize_bool($user['is_active'] ?? false),
        'created_at' => (string)($user['created_at'] ?? ''),
        'updated_at' => (string)($user['updated_at'] ?? ''),
        'last_login_at' => isset($user['last_login_at']) ? (string)$user['last_login_at'] : null,
    ];
};

if ($method === 'GET') {
    $sanitized = [];
    foreach ($users as $user) {
        if (is_array($user)) {
            $sanitized[] = $publicUser($user);
        }
    }

    bioinmed_admin_json_response([
        'ok' => true,
        'users' => $sanitized,
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

$action = (string)($body['action'] ?? '');

if ($action === 'create') {
    $email = strtolower(trim((string)($body['email'] ?? '')));
    $name = trim((string)($body['name'] ?? ''));
    $role = (string)($body['role'] ?? 'editor');
    $password = (string)($body['password'] ?? '');

    if ($email === '' || $name === '' || $password === '') {
        bioinmed_admin_json_response([
            'ok' => false,
            'error' => 'Email, имя и пароль обязательны.',
        ], 422);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        bioinmed_admin_json_response([
            'ok' => false,
            'error' => 'Некорректный email.',
        ], 422);
    }

    if (!in_array($role, ['admin', 'editor'], true)) {
        bioinmed_admin_json_response([
            'ok' => false,
            'error' => 'Некорректная роль.',
        ], 422);
    }

    foreach ($users as $existing) {
        if (strtolower((string)($existing['email'] ?? '')) === $email) {
            bioinmed_admin_json_response([
                'ok' => false,
                'error' => 'Пользователь с таким email уже существует.',
            ], 409);
        }
    }

    $newUser = [
        'id' => 'u_' . bin2hex(random_bytes(5)),
        'email' => $email,
        'name' => $name,
        'role' => $role,
        'is_active' => true,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'created_at' => gmdate('c'),
        'updated_at' => gmdate('c'),
        'last_login_at' => null,
    ];

    $users[] = $newUser;
    $payload['users'] = $users;
    if (!bioinmed_admin_save_users($payload)) {
        bioinmed_admin_json_response([
            'ok' => false,
            'error' => 'Не удалось сохранить пользователя.',
        ], 500);
    }

    bioinmed_admin_json_response([
        'ok' => true,
        'message' => 'Пользователь создан.',
        'user' => $publicUser($newUser),
    ]);
}

if ($action === 'update') {
    $id = trim((string)($body['id'] ?? ''));
    if ($id === '') {
        bioinmed_admin_json_response([
            'ok' => false,
            'error' => 'Не указан пользователь.',
        ], 422);
    }

    $found = false;
    foreach ($users as &$user) {
        if (($user['id'] ?? '') !== $id) {
            continue;
        }

        $found = true;
        if (isset($body['email'])) {
            $newEmail = strtolower(trim((string)$body['email']));
            if ($newEmail === '' || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                bioinmed_admin_json_response([
                    'ok' => false,
                    'error' => 'Некорректный email.',
                ], 422);
            }

            foreach ($users as $existingUser) {
                $existingId = (string)($existingUser['id'] ?? '');
                $existingEmail = strtolower((string)($existingUser['email'] ?? ''));
                if ($existingId !== $id && $existingEmail === $newEmail) {
                    bioinmed_admin_json_response([
                        'ok' => false,
                        'error' => 'Пользователь с таким email уже существует.',
                    ], 409);
                }
            }

            $user['email'] = $newEmail;
        }

        if (isset($body['name'])) {
            $user['name'] = trim((string)$body['name']);
        }

        if (isset($body['role'])) {
            $role = (string)$body['role'];
            if (!in_array($role, ['admin', 'editor'], true)) {
                bioinmed_admin_json_response([
                    'ok' => false,
                    'error' => 'Некорректная роль.',
                ], 422);
            }
            $user['role'] = $role;
        }

        if (isset($body['is_active'])) {
            $user['is_active'] = bioinmed_admin_normalize_bool($body['is_active']);
        }

        if (isset($body['password']) && trim((string)$body['password']) !== '') {
            $user['password_hash'] = password_hash((string)$body['password'], PASSWORD_DEFAULT);
        }

        $user['updated_at'] = gmdate('c');
        break;
    }
    unset($user);

    if (!$found) {
        bioinmed_admin_json_response([
            'ok' => false,
            'error' => 'Пользователь не найден.',
        ], 404);
    }

    $payload['users'] = $users;
    if (!bioinmed_admin_save_users($payload)) {
        bioinmed_admin_json_response([
            'ok' => false,
            'error' => 'Не удалось сохранить изменения пользователя.',
        ], 500);
    }

    $updated = null;
    foreach ($users as $user) {
        if (($user['id'] ?? '') === $id) {
            $updated = $user;
            break;
        }
    }

    if (($actor['id'] ?? '') === $id && is_array($updated)) {
        $_SESSION['bioinmed_admin_user'] = [
            'id' => (string)$updated['id'],
            'email' => (string)$updated['email'],
            'name' => (string)$updated['name'],
            'role' => (string)$updated['role'],
        ];
    }

    bioinmed_admin_json_response([
        'ok' => true,
        'message' => 'Пользователь обновлён.',
        'user' => is_array($updated) ? $publicUser($updated) : null,
    ]);
}

if ($action === 'delete') {
    $id = trim((string)($body['id'] ?? ''));
    if ($id === '') {
        bioinmed_admin_json_response([
            'ok' => false,
            'error' => 'Не указан пользователь.',
        ], 422);
    }

    if (($actor['id'] ?? '') === $id) {
        bioinmed_admin_json_response([
            'ok' => false,
            'error' => 'Нельзя удалить текущего пользователя.',
        ], 422);
    }

    $before = count($users);
    $users = array_values(array_filter($users, static function ($user) use ($id) {
        return (string)($user['id'] ?? '') !== $id;
    }));

    if (count($users) === $before) {
        bioinmed_admin_json_response([
            'ok' => false,
            'error' => 'Пользователь не найден.',
        ], 404);
    }

    $payload['users'] = $users;
    if (!bioinmed_admin_save_users($payload)) {
        bioinmed_admin_json_response([
            'ok' => false,
            'error' => 'Не удалось удалить пользователя.',
        ], 500);
    }

    bioinmed_admin_json_response([
        'ok' => true,
        'message' => 'Пользователь удалён.',
    ]);
}

bioinmed_admin_json_response([
    'ok' => false,
    'error' => 'Неизвестное действие.',
], 422);
