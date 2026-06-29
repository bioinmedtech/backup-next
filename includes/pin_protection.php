<?php

function bioinmed_pin_settings_file_path() {
    return __DIR__ . '/../data/admin/pin-settings.json';
}

function bioinmed_pin_default_settings() {
    return [
        'enabled' => true,
        'pin' => '1290',
    ];
}

function bioinmed_pin_load_settings() {
    $path = bioinmed_pin_settings_file_path();
    if (!is_file($path)) {
        return bioinmed_pin_default_settings();
    }

    $raw = @file_get_contents($path);
    if (!is_string($raw) || trim($raw) === '') {
        return bioinmed_pin_default_settings();
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return bioinmed_pin_default_settings();
    }

    return array_merge(bioinmed_pin_default_settings(), $decoded);
}

function bioinmed_pin_protection_enabled() {
    $settings = bioinmed_pin_load_settings();
    return !empty($settings['enabled']);
}

function bioinmed_pin_cookie_name() {
    return 'bioinmed_pin_access';
}

function bioinmed_pin_skip_requested() {
    return isset($_GET['skip_pin']) && (string)$_GET['skip_pin'] === '1';
}

function bioinmed_pin_strip_skip_pin_from_uri($requestUri) {
    $parts = parse_url($requestUri);
    if (!is_array($parts)) {
        return '/';
    }

    $path = isset($parts['path']) && $parts['path'] !== '' ? $parts['path'] : '/';
    if (empty($parts['query'])) {
        return $path;
    }

    parse_str($parts['query'], $query);
    if (isset($query['skip_pin'])) {
        unset($query['skip_pin']);
    }

    if (empty($query)) {
        return $path;
    }

    return $path . '?' . http_build_query($query);
}

function bioinmed_pin_token_secret() {
    $settings = bioinmed_pin_load_settings();
    $pin = trim((string)($settings['pin'] ?? ''));
    if ($pin !== '') {
        return hash('sha256', 'bioinmed-pin|' . $pin);
    }

    // Fallback secret for local/dev environments.
    return hash('sha256', __DIR__ . '|' . PHP_VERSION . '|bioinmed-pin-fallback');
}

function bioinmed_pin_base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function bioinmed_pin_base64url_decode($data) {
    $data = strtr($data, '-_', '+/');
    $padding = strlen($data) % 4;
    if ($padding > 0) {
        $data .= str_repeat('=', 4 - $padding);
    }
    $decoded = base64_decode($data, true);
    return $decoded === false ? '' : $decoded;
}

function bioinmed_pin_build_token($expiresAt) {
    $payload = json_encode([
        'exp' => (int)$expiresAt,
        'v' => 1,
    ], JSON_UNESCAPED_SLASHES);

    if (!is_string($payload) || $payload === '') {
        return '';
    }

    $payloadEncoded = bioinmed_pin_base64url_encode($payload);
    $signature = hash_hmac('sha256', $payloadEncoded, bioinmed_pin_token_secret(), true);
    $signatureEncoded = bioinmed_pin_base64url_encode($signature);

    return $payloadEncoded . '.' . $signatureEncoded;
}

function bioinmed_pin_parse_token($token) {
    if (!is_string($token) || $token === '' || strpos($token, '.') === false) {
        return null;
    }

    [$payloadEncoded, $signatureEncoded] = explode('.', $token, 2);
    if ($payloadEncoded === '' || $signatureEncoded === '') {
        return null;
    }

    $expectedSignature = hash_hmac('sha256', $payloadEncoded, bioinmed_pin_token_secret(), true);
    $actualSignature = bioinmed_pin_base64url_decode($signatureEncoded);

    if ($actualSignature === '' || !hash_equals($expectedSignature, $actualSignature)) {
        return null;
    }

    $payloadRaw = bioinmed_pin_base64url_decode($payloadEncoded);
    if ($payloadRaw === '') {
        return null;
    }

    $payload = json_decode($payloadRaw, true);
    if (!is_array($payload)) {
        return null;
    }

    $exp = isset($payload['exp']) ? (int)$payload['exp'] : 0;
    if ($exp <= time()) {
        return null;
    }

    return $payload;
}

function bioinmed_pin_set_cookie($token, $expiresAt) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);

    setcookie(bioinmed_pin_cookie_name(), $token, [
        'expires' => (int)$expiresAt,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function bioinmed_pin_clear_cookie() {
    setcookie(bioinmed_pin_cookie_name(), '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function bioinmed_pin_grant_access($ttlSeconds = 2592000) {
    $ttl = max(300, (int)$ttlSeconds);
    $expiresAt = time() + $ttl;

    $token = bioinmed_pin_build_token($expiresAt);
    if ($token !== '') {
        bioinmed_pin_set_cookie($token, $expiresAt);
    }
}

function bioinmed_pin_has_access() {
    if (!bioinmed_pin_protection_enabled()) {
        return true;
    }

    $token = isset($_COOKIE[bioinmed_pin_cookie_name()]) ? (string)$_COOKIE[bioinmed_pin_cookie_name()] : '';
    $payload = bioinmed_pin_parse_token($token);
    if ($payload === null) {
        if ($token !== '') {
            bioinmed_pin_clear_cookie();
        }
        return false;
    }

    return true;
}

function bioinmed_pin_require_access() {
    if (bioinmed_pin_skip_requested()) {
        bioinmed_pin_grant_access();

        $requestUri = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '/';
        $cleanUri = bioinmed_pin_strip_skip_pin_from_uri($requestUri);
        if ($cleanUri !== '' && $cleanUri !== $requestUri) {
            header('Location: ' . $cleanUri);
            exit;
        }

        return;
    }

    if (bioinmed_pin_has_access()) {
        return;
    }

    $requestUri = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '/';
    header('Location: /splash.php?redirect=' . urlencode($requestUri));
    exit;
}
