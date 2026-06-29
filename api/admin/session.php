<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

bioinmed_admin_json_response([
    'ok' => true,
    'config' => [
        'isAuthenticated' => (bool)bioinmed_admin_current_user(),
        'user' => bioinmed_admin_current_user() ? [
            'id' => bioinmed_admin_current_user()['id'],
            'email' => bioinmed_admin_current_user()['email'],
            'name' => bioinmed_admin_current_user()['name'],
            'role' => bioinmed_admin_current_user()['role'],
            'role_label' => bioinmed_admin_role_label((string)bioinmed_admin_current_user()['role']),
        ] : null,
        'canManageUsers' => bioinmed_admin_current_user() && (bioinmed_admin_current_user()['role'] ?? '') === 'admin',
        'apiBase' => '/api/admin',
    ],
]);
