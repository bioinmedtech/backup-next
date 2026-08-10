<?php

declare(strict_types=1);

if (defined('BIOINMED_ADMIN_BOOTSTRAPPED')) {
    return;
}
define('BIOINMED_ADMIN_BOOTSTRAPPED', true);

$bioinmedAdminSessionLifetime = 60 * 60 * 24 * 60; // 60 days
$bioinmedAdminRememberCookie = 'bioinmed_admin_remember';
$bioinmedAdminCsrfCookie = 'bioinmed_admin_csrf';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', (string)$bioinmedAdminSessionLifetime);
    session_set_cookie_params([
        'lifetime' => $bioinmedAdminSessionLifetime,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

define('BIOINMED_ADMIN_DATA_DIR', __DIR__ . '/../../data');
define('BIOINMED_ADMIN_USERS_FILE', BIOINMED_ADMIN_DATA_DIR . '/users/users.json');
define('BIOINMED_ADMIN_CONTENT_DIR', BIOINMED_ADMIN_DATA_DIR . '/content/ru');
define('BIOINMED_ADMIN_SETTINGS_DIR', BIOINMED_ADMIN_DATA_DIR . '/admin');
define('BIOINMED_ADMIN_PIN_SETTINGS_FILE', BIOINMED_ADMIN_SETTINGS_DIR . '/pin-settings.json');
define('BIOINMED_ADMIN_SERVICES_FILE', __DIR__ . '/../../config/services.php');

function bioinmed_admin_cookie_secret(): string {
    static $secret = null;
    if (is_string($secret) && $secret !== '') {
        return $secret;
    }

    $secret = (string)(getenv('BIOINMED_ADMIN_COOKIE_SECRET') ?: '9fb5a30d-663b-4d1f-825a-8985c67e3b85');
    return $secret;
}

function bioinmed_admin_cookie_params(int $lifetime): array {
    return [
        'expires' => time() + $lifetime,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function bioinmed_admin_csrf_cookie_params(int $lifetime): array {
    return [
        'expires' => time() + $lifetime,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => false,
        'samesite' => 'Lax',
    ];
}

function bioinmed_admin_sign_remember_payload(string $userId, int $expiresAt): string {
    return hash_hmac('sha256', $userId . '|' . $expiresAt, bioinmed_admin_cookie_secret());
}

function bioinmed_admin_set_remember_cookie(array $user): void {
    global $bioinmedAdminSessionLifetime, $bioinmedAdminRememberCookie;

    $userId = (string)($user['id'] ?? '');
    if ($userId === '') {
        return;
    }

    $expiresAt = time() + $bioinmedAdminSessionLifetime;
    $payload = [
        'id' => $userId,
        'exp' => $expiresAt,
        'sig' => bioinmed_admin_sign_remember_payload($userId, $expiresAt),
    ];

    $encoded = base64_encode(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    if (!is_string($encoded) || $encoded === '') {
        return;
    }

    setcookie($bioinmedAdminRememberCookie, $encoded, bioinmed_admin_cookie_params($bioinmedAdminSessionLifetime));
}

function bioinmed_admin_clear_remember_cookie(): void {
    global $bioinmedAdminRememberCookie;

    setcookie($bioinmedAdminRememberCookie, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function bioinmed_admin_set_csrf_cookie(string $token): void {
    if ($token === '') {
        return;
    }

    setcookie('bioinmed_admin_csrf', $token, bioinmed_admin_csrf_cookie_params(60 * 60 * 24 * 60));
}

function bioinmed_admin_restore_user_from_remember_cookie(): void {
    global $bioinmedAdminRememberCookie;

    if (isset($_SESSION['bioinmed_admin_user']) && is_array($_SESSION['bioinmed_admin_user'])) {
        return;
    }

    $raw = $_COOKIE[$bioinmedAdminRememberCookie] ?? '';
    if (!is_string($raw) || $raw === '') {
        return;
    }

    $decoded = base64_decode($raw, true);
    if (!is_string($decoded) || $decoded === '') {
        bioinmed_admin_clear_remember_cookie();
        return;
    }

    $payload = json_decode($decoded, true);
    if (!is_array($payload)) {
        bioinmed_admin_clear_remember_cookie();
        return;
    }

    $userId = (string)($payload['id'] ?? '');
    $expiresAt = (int)($payload['exp'] ?? 0);
    $signature = (string)($payload['sig'] ?? '');
    if ($userId === '' || $expiresAt <= time() || $signature === '') {
        bioinmed_admin_clear_remember_cookie();
        return;
    }

    $expected = bioinmed_admin_sign_remember_payload($userId, $expiresAt);
    if (!hash_equals($expected, $signature)) {
        bioinmed_admin_clear_remember_cookie();
        return;
    }

    $usersPayload = bioinmed_admin_load_users();
    $users = is_array($usersPayload['users'] ?? null) ? $usersPayload['users'] : [];
    foreach ($users as $user) {
        if (!is_array($user)) {
            continue;
        }

        if ((string)($user['id'] ?? '') !== $userId) {
            continue;
        }

        if (!bioinmed_admin_normalize_bool($user['is_active'] ?? false)) {
            bioinmed_admin_clear_remember_cookie();
            return;
        }

        $_SESSION['bioinmed_admin_user'] = [
            'id' => (string)($user['id'] ?? ''),
            'email' => (string)($user['email'] ?? ''),
            'name' => (string)($user['name'] ?? ''),
            'role' => (string)($user['role'] ?? ''),
        ];
        return;
    }

    bioinmed_admin_clear_remember_cookie();
}

function bioinmed_admin_ensure_directories(): void {
    $dirs = [
        dirname(BIOINMED_ADMIN_USERS_FILE),
        BIOINMED_ADMIN_SETTINGS_DIR,
    ];

    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }
}

function bioinmed_admin_json_response(array $payload, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function bioinmed_admin_request_json(): array {
    $raw = file_get_contents('php://input');
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function bioinmed_admin_csrf_token(): string {
    global $bioinmedAdminCsrfCookie;

    $cookieToken = $_COOKIE[$bioinmedAdminCsrfCookie] ?? '';
    if (is_string($cookieToken) && preg_match('/^[a-f0-9]{48}$/', $cookieToken)) {
        $_SESSION['bioinmed_admin_csrf'] = $cookieToken;
        bioinmed_admin_set_csrf_cookie($cookieToken);
        return $cookieToken;
    }

    if (!isset($_SESSION['bioinmed_admin_csrf']) || !is_string($_SESSION['bioinmed_admin_csrf']) || !preg_match('/^[a-f0-9]{48}$/', $_SESSION['bioinmed_admin_csrf'])) {
        $_SESSION['bioinmed_admin_csrf'] = bin2hex(random_bytes(24));
    }

    bioinmed_admin_set_csrf_cookie($_SESSION['bioinmed_admin_csrf']);
    return $_SESSION['bioinmed_admin_csrf'];
}

function bioinmed_admin_verify_csrf(?string $token): bool {
    if (!is_string($token) || $token === '') {
        return false;
    }

    global $bioinmedAdminCsrfCookie;

    $cookieToken = $_COOKIE[$bioinmedAdminCsrfCookie] ?? '';
    if (is_string($cookieToken) && $cookieToken !== '') {
        return hash_equals($cookieToken, $token);
    }

    $stored = $_SESSION['bioinmed_admin_csrf'] ?? '';
    return is_string($stored) && $stored !== '' && hash_equals($stored, $token);
}

function bioinmed_admin_read_json(string $path, $fallback): array {
    if (!is_file($path)) {
        return $fallback;
    }

    $raw = @file_get_contents($path);
    if (!is_string($raw) || $raw === '') {
        return $fallback;
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $fallback;
}

function bioinmed_admin_write_json(string $path, array $data): bool {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($json)) {
        return false;
    }

    $tmp = $path . '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(4));
    if (@file_put_contents($tmp, $json . PHP_EOL, LOCK_EX) === false) {
        return false;
    }

    if (@rename($tmp, $path)) {
        @chmod($path, 0664);
        clearstatcache(true, $path);
        return true;
    }

    if (@copy($tmp, $path)) {
        @unlink($tmp);
        @chmod($path, 0664);
        clearstatcache(true, $path);
        return true;
    }

    @unlink($tmp);
    return false;
}

function bioinmed_admin_default_users(): array {
    return [
        'users' => [
            [
                'id' => 'u_admin',
                'email' => 'admin@bioinmed.ru',
                'name' => 'Главный администратор',
                'role' => 'admin',
                'is_active' => true,
                'password_hash' => '$2y$10$.vHJkTI3GFG7aiwwG6cgcegxLelU3gQmP9kLDklAS0Wx2BS5EL7r6',
                'created_at' => gmdate('c'),
                'updated_at' => gmdate('c'),
                'last_login_at' => null,
            ],
            [
                'id' => 'u_editor',
                'email' => 'editor@bioinmed.ru',
                'name' => 'Контент-редактор',
                'role' => 'editor',
                'is_active' => true,
                'password_hash' => '$2y$10$d33.m/2zbEMxShah9.Hkl.HIy9aZlldZjtmhgwOuWjR5l...sEjNq',
                'created_at' => gmdate('c'),
                'updated_at' => gmdate('c'),
                'last_login_at' => null,
            ],
        ],
    ];
}

function bioinmed_admin_load_users(): array {
    bioinmed_admin_ensure_directories();

    if (!is_file(BIOINMED_ADMIN_USERS_FILE)) {
        $seed = bioinmed_admin_default_users();
        bioinmed_admin_write_json(BIOINMED_ADMIN_USERS_FILE, $seed);
        return $seed;
    }

    return bioinmed_admin_read_json(BIOINMED_ADMIN_USERS_FILE, ['users' => []]);
}

function bioinmed_admin_save_users(array $payload): bool {
    return bioinmed_admin_write_json(BIOINMED_ADMIN_USERS_FILE, $payload);
}

function bioinmed_admin_normalize_bool($value): bool {
    if (is_bool($value)) {
        return $value;
    }

    if (is_int($value) || is_float($value)) {
        return (int)$value === 1;
    }

    if (is_string($value)) {
        $normalized = strtolower(trim($value));
        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    return false;
}

function bioinmed_admin_current_user(): ?array {
    $user = $_SESSION['bioinmed_admin_user'] ?? null;
    return is_array($user) ? $user : null;
}

bioinmed_admin_restore_user_from_remember_cookie();

// Refresh session cookie expiry on each request when user is present (keeps session alive on activity)
if (isset($_SESSION['bioinmed_admin_user']) && is_array($_SESSION['bioinmed_admin_user'])) {
    // Send a Set-Cookie for the session cookie with renewed expiration
    setcookie(session_name(), session_id(), bioinmed_admin_cookie_params($bioinmedAdminSessionLifetime));
}

function bioinmed_admin_require_auth(?array $requiredRoles = null): array {
    $user = bioinmed_admin_current_user();
    if (!$user) {
        bioinmed_admin_json_response([
            'ok' => false,
            'error' => 'Требуется авторизация.',
        ], 401);
    }

    if (is_array($requiredRoles) && !in_array($user['role'] ?? '', $requiredRoles, true)) {
        bioinmed_admin_json_response([
            'ok' => false,
            'error' => 'Недостаточно прав.',
        ], 403);
    }

    return $user;
}

function bioinmed_admin_role_label(string $role): string {
    if ($role === 'admin') {
        return 'Администратор';
    }

    if ($role === 'editor') {
        return 'Редактор';
    }

    return 'Пользователь';
}

function bioinmed_admin_target_from_key(string $textKey): ?array {
    if (!preg_match('/^[a-zA-Z0-9_\.\-]+$/', $textKey)) {
        return null;
    }

    if (strpos($textKey, 'site.') === 0) {
        $fieldPath = substr($textKey, 5);
        if ($fieldPath === '') {
            return null;
        }

        return [
            'type' => 'site',
            'page' => null,
            'json_path' => BIOINMED_ADMIN_CONTENT_DIR . '/site.json',
            'field_path' => $fieldPath,
            'source_key' => $textKey,
        ];
    }

    if (strpos($textKey, 'links.') === 0) {
        $fieldPath = substr($textKey, 6);
        if ($fieldPath === '') {
            return null;
        }

        return [
            'type' => 'links',
            'page' => null,
            'json_path' => BIOINMED_ADMIN_CONTENT_DIR . '/links.json',
            'field_path' => $fieldPath,
            'source_key' => $textKey,
        ];
    }

    if (strpos($textKey, 'pages.') === 0) {
        $parts = explode('.', $textKey);
        if (count($parts) < 3) {
            return null;
        }

        $pageName = $parts[1];
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $pageName)) {
            return null;
        }

        $jsonPath = BIOINMED_ADMIN_CONTENT_DIR . '/pages/' . $pageName . '.json';
        $fieldPath = implode('.', array_slice($parts, 2));

        return [
            'type' => 'page',
            'page' => $pageName,
            'json_path' => $jsonPath,
            'field_path' => $fieldPath,
            'source_key' => $textKey,
        ];
    }

    $jsonPath = BIOINMED_ADMIN_CONTENT_DIR . '/texts.json';
    return [
        'type' => 'texts',
        'page' => null,
        'json_path' => $jsonPath,
        'field_path' => $textKey,
        'source_key' => $textKey,
    ];
}

function bioinmed_admin_collect_text_nodes(): array {
    $items = [];

    $collect = static function (array $payload, string $prefix = '') use (&$collect, &$items): void {
        foreach ($payload as $key => $value) {
            if (!is_string($key) || $key === '') {
                continue;
            }

            $nextKey = $prefix === '' ? $key : ($prefix . '.' . $key);
            if (is_array($value)) {
                $collect($value, $nextKey);
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $items[$nextKey] = is_scalar($value) ? (string)$value : '';
            }
        }
    };

    $texts = bioinmed_admin_read_json(BIOINMED_ADMIN_CONTENT_DIR . '/texts.json', []);
    if (is_array($texts)) {
        $collect($texts, '');
    }

    $pagesDir = BIOINMED_ADMIN_CONTENT_DIR . '/pages';
    if (is_dir($pagesDir)) {
        $files = glob($pagesDir . '/*.json');
        if (is_array($files)) {
            foreach ($files as $file) {
                $pageName = pathinfo($file, PATHINFO_FILENAME);
                if (!is_string($pageName) || $pageName === '') {
                    continue;
                }

                $pagePayload = bioinmed_admin_read_json($file, []);
                if (!is_array($pagePayload)) {
                    continue;
                }

                $collect($pagePayload, 'pages.' . $pageName);
            }
        }
    }

    return $items;
}

function bioinmed_admin_set_nested_value(array &$data, string $path, string $value): void {
    $parts = array_values(array_filter(explode('.', $path), static function ($item) {
        return $item !== '';
    }));

    if (!$parts) {
        return;
    }

    $cursor = &$data;
    $lastIndex = count($parts) - 1;

    foreach ($parts as $index => $part) {
        if ($index === $lastIndex) {
            $cursor[$part] = $value;
            break;
        }

        if (!isset($cursor[$part]) || !is_array($cursor[$part])) {
            $cursor[$part] = [];
        }

        $cursor = &$cursor[$part];
    }
}

function bioinmed_admin_get_nested_value(array $data, string $path): string {
    $parts = array_values(array_filter(explode('.', $path), static function ($item) {
        return $item !== '';
    }));

    $cursor = $data;
    foreach ($parts as $part) {
        if (!is_array($cursor) || !array_key_exists($part, $cursor)) {
            return '';
        }
        $cursor = $cursor[$part];
    }

    return is_scalar($cursor) ? (string)$cursor : '';
}

function bioinmed_admin_read_services_config(): array {
    if (!is_file(BIOINMED_ADMIN_SERVICES_FILE)) {
        return [];
    }

    $services = require BIOINMED_ADMIN_SERVICES_FILE;
    return is_array($services) ? $services : [];
}

function bioinmed_admin_write_services_config(array $services): bool {
    $php = "<?php\n\nreturn " . var_export($services, true) . ";\n";

    $tmp = BIOINMED_ADMIN_SERVICES_FILE . '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(4));
    if (@file_put_contents($tmp, $php, LOCK_EX) === false) {
        return false;
    }

    return @rename($tmp, BIOINMED_ADMIN_SERVICES_FILE);
}

function bioinmed_admin_default_pin_settings(): array {
    return [
        'enabled' => true,
        'pin' => '1290',
        'online_booking_enabled' => true,
        'updated_at' => gmdate('c'),
        'updated_by' => null,
    ];
}

function bioinmed_admin_load_pin_settings(): array {
    bioinmed_admin_ensure_directories();

    if (!is_file(BIOINMED_ADMIN_PIN_SETTINGS_FILE)) {
        $seed = bioinmed_admin_default_pin_settings();
        bioinmed_admin_write_json(BIOINMED_ADMIN_PIN_SETTINGS_FILE, $seed);
        return $seed;
    }

    $payload = bioinmed_admin_read_json(BIOINMED_ADMIN_PIN_SETTINGS_FILE, []);
    if (!is_array($payload)) {
        return bioinmed_admin_default_pin_settings();
    }

    return array_merge(bioinmed_admin_default_pin_settings(), $payload);
}

function bioinmed_admin_save_pin_settings(array $payload): bool {
    $normalized = [
        'enabled' => bioinmed_admin_normalize_bool($payload['enabled'] ?? true),
        'pin' => trim((string)($payload['pin'] ?? '')),
        'online_booking_enabled' => bioinmed_admin_normalize_bool($payload['online_booking_enabled'] ?? true),
        'updated_at' => (string)($payload['updated_at'] ?? gmdate('c')),
        'updated_by' => isset($payload['updated_by']) && is_array($payload['updated_by']) ? $payload['updated_by'] : null,
    ];

    if (!$normalized['enabled']) {
        $normalized['pin'] = '';
    }

    return bioinmed_admin_write_json(BIOINMED_ADMIN_PIN_SETTINGS_FILE, $normalized);
}

function bioinmed_admin_split_price_and_note(string $value): array {
    $value = trim($value);
    if ($value === '') {
        return ['', ''];
    }

    if (preg_match('/^(.+?)(\s*\/.*)$/u', $value, $matches)) {
        return [trim((string)$matches[1]), trim((string)$matches[2])];
    }

    return [$value, ''];
}

function bioinmed_admin_sync_service_price_from_prices_key(string $textKey, string $value): bool {
    $serviceId = '';

    if (preg_match('/^pages\.prices\.sections\.([a-zA-Z0-9_-]+)\.rows\.([0-9]+)\.price$/', $textKey, $matches)) {
        $sectionId = (string)$matches[1];
        $rowIndex = (int)$matches[2];
        $pricesPath = BIOINMED_ADMIN_CONTENT_DIR . '/pages/prices.json';
        $pricesPayload = bioinmed_admin_read_json($pricesPath, []);
        $sections = is_array($pricesPayload['sections'] ?? null) ? $pricesPayload['sections'] : [];

        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }

            if ((string)($section['id'] ?? '') !== $sectionId) {
                continue;
            }

            $rows = is_array($section['rows'] ?? null) ? $section['rows'] : [];
            $row = is_array($rows[$rowIndex] ?? null) ? $rows[$rowIndex] : [];
            $serviceId = trim((string)($row['service_id'] ?? ''));
            break;
        }
    }

    if ($serviceId === '') {
        return true;
    }

    $services = bioinmed_admin_read_services_config();
    if (!$services) {
        return false;
    }

    $updated = false;
    [$nextPrice, $nextPriceNote] = bioinmed_admin_split_price_and_note($value);
    foreach ($services as &$service) {
        if (!is_array($service)) {
            continue;
        }

        if ((string)($service['id'] ?? '') !== $serviceId) {
            continue;
        }

        $service['price'] = $nextPrice;
        $service['price_note'] = $nextPriceNote;
        $updated = true;
        break;
    }
    unset($service);

    if (!$updated) {
        return true;
    }

    return bioinmed_admin_write_services_config($services);
}

function bioinmed_admin_is_price_locked_text_key(string $textKey): bool {
    if (strpos($textKey, 'pages.prices.') === 0) {
        return false;
    }

    return (bool)preg_match(
        '/\.(?:price|price_note|price_current|price_before|price_saving|price_on_request)$/',
        $textKey
    ) || (bool)preg_match(
        '/^pages\.services\.catalog\.items\.[a-zA-Z0-9_-]+\.price_label$/',
        $textKey
    );
}


function bioinmed_admin_client_config(): array {
    $user = bioinmed_admin_current_user();
    return [
        'isAuthenticated' => (bool)$user,
        'user' => $user ? [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role'],
            'role_label' => bioinmed_admin_role_label((string)$user['role']),
        ] : null,
        'csrf' => bioinmed_admin_csrf_token(),
        'canManageUsers' => $user && ($user['role'] ?? '') === 'admin',
        'apiBase' => '/api/admin',
        'pinSettings' => bioinmed_admin_load_pin_settings(),
    ];
}

bioinmed_admin_ensure_directories();
