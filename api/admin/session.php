<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

bioinmed_admin_json_response([
    'ok' => true,
    'config' => bioinmed_admin_client_config(),
]);
