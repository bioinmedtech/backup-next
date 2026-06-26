<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/admin/bootstrap.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

function bioinmed_admin_require_post_json(): array {
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        bioinmed_admin_json_response([
            'ok' => false,
            'error' => 'Метод не поддерживается.',
        ], 405);
    }

    return bioinmed_admin_request_json();
}
