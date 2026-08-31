<?php
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

function bioinmed_weather_geoip_proxy_url() {
    $value = getenv('BIOINMED_WEATHER_PROXY_URL');
    if (is_string($value) && trim($value) !== '') {
        return trim($value);
    }

    $envFile = __DIR__ . '/../../.env';
    if (!is_readable($envFile)) {
        return '';
    }

    $lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return '';
    }

    foreach ($lines as $line) {
        $line = trim((string)$line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }

        [$key, $rawValue] = array_map('trim', explode('=', $line, 2));
        if ($key === 'BIOINMED_WEATHER_PROXY_URL') {
            return trim($rawValue, " \t\n\r\0\x0B\"'");
        }
    }

    return '';
}

function bioinmed_weather_geoip_fetch($url, $proxyUrl = '') {
    if (!function_exists('curl_init')) {
        return ['status' => 0, 'body' => ''];
    }

    $handle = curl_init($url);
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_USERAGENT => 'BioinmedWeather/1.0',
    ]);

    $proxyUrl = trim((string)$proxyUrl);
    if ($proxyUrl !== '') {
        $proxy = parse_url($proxyUrl);
        if (is_array($proxy) && !empty($proxy['host'])) {
            $scheme = strtolower((string)($proxy['scheme'] ?? 'http'));
            $host = (string)$proxy['host'];
            $port = isset($proxy['port']) ? (int)$proxy['port'] : ($scheme === 'https' ? 443 : 80);
            curl_setopt($handle, CURLOPT_PROXY, $host . ':' . $port);
            curl_setopt($handle, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
            curl_setopt($handle, CURLOPT_HTTPPROXYTUNNEL, true);

            if (isset($proxy['user']) || isset($proxy['pass'])) {
                curl_setopt(
                    $handle,
                    CURLOPT_PROXYUSERPWD,
                    rawurldecode((string)($proxy['user'] ?? '')) . ':' . rawurldecode((string)($proxy['pass'] ?? ''))
                );
            }
        }
    }

    $body = curl_exec($handle);
    $status = (int)curl_getinfo($handle, CURLINFO_HTTP_CODE);
    curl_close($handle);

    return ['status' => $status, 'body' => is_string($body) ? $body : ''];
}

function bioinmed_weather_geoip_log($event, array $data = []) {
    $entry = array_merge([
        'time' => date(DATE_ATOM),
        'ip' => (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? ''),
        'ua' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 240),
        'event' => $event,
    ], $data);

    $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    $written = @file_put_contents(__DIR__ . '/../../data/logs/weather-widget.log', $line, FILE_APPEND | LOCK_EX);
    if ($written === false) {
        error_log('[bioinmed-weather-geoip] ' . trim($line));
    }
}

function bioinmed_weather_geoip_client_ip() {
    $candidates = [
        (string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''),
        (string)($_SERVER['HTTP_X_REAL_IP'] ?? ''),
        (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''),
        (string)($_SERVER['REMOTE_ADDR'] ?? ''),
    ];

    foreach ($candidates as $candidate) {
        foreach (explode(',', $candidate) as $ip) {
            $ip = trim($ip);
            if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }

    return '';
}

function bioinmed_weather_geoip_from_ip_api($clientIp, $proxyUrl) {
    $query = [
        'lang' => 'ru',
        'fields' => 'status,message,countryCode,city,lat,lon,query',
    ];
    $url = 'http://ip-api.com/json/' . rawurlencode($clientIp) . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    if ($clientIp === '') {
        $url = 'http://ip-api.com/json/?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    $result = bioinmed_weather_geoip_fetch($url, $proxyUrl);
    $decoded = json_decode($result['body'], true);
    $success = $result['status'] >= 200 && $result['status'] < 300 && is_array($decoded) && ($decoded['status'] ?? '') === 'success';

    if (!$success) {
        return [
            'ok' => false,
            'status' => $result['status'],
            'message' => is_array($decoded) ? (string)($decoded['message'] ?? '') : '',
        ];
    }

    return [
        'ok' => true,
        'city' => trim((string)($decoded['city'] ?? '')),
        'country_code' => strtoupper(trim((string)($decoded['countryCode'] ?? ''))),
        'latitude' => (float)($decoded['lat'] ?? 0),
        'longitude' => (float)($decoded['lon'] ?? 0),
    ];
}

function bioinmed_weather_geoip_from_ipwhois($clientIp, $proxyUrl) {
    $url = 'https://ipwho.is/' . rawurlencode($clientIp) . '?lang=ru';
    if ($clientIp === '') {
        $url = 'https://ipwho.is/?lang=ru';
    }

    $result = bioinmed_weather_geoip_fetch($url, $proxyUrl);
    $decoded = json_decode($result['body'], true);
    $success = $result['status'] >= 200 && $result['status'] < 300 && is_array($decoded) && ($decoded['success'] ?? true);

    if (!$success) {
        return [
            'ok' => false,
            'status' => $result['status'],
            'message' => is_array($decoded) ? (string)($decoded['message'] ?? '') : '',
        ];
    }

    return [
        'ok' => true,
        'city' => trim((string)($decoded['city'] ?? '')),
        'country_code' => strtoupper(trim((string)($decoded['country_code'] ?? ''))),
        'latitude' => (float)($decoded['latitude'] ?? 0),
        'longitude' => (float)($decoded['longitude'] ?? 0),
    ];
}

function bioinmed_weather_geoip_has_location(array $location) {
    $city = trim((string)($location['city'] ?? ''));
    $latitude = (float)($location['latitude'] ?? 0);
    $longitude = (float)($location['longitude'] ?? 0);

    return $city !== '' && $latitude >= -90 && $latitude <= 90 && $longitude >= -180 && $longitude <= 180 && ($latitude != 0.0 || $longitude != 0.0);
}

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$clientIp = bioinmed_weather_geoip_client_ip();
$proxyUrl = bioinmed_weather_geoip_proxy_url();
if ($proxyUrl !== '') {
    header('X-Bioinmed-Weather-Proxy: enabled');
}

$location = bioinmed_weather_geoip_from_ip_api($clientIp, $proxyUrl);
$provider = 'ip-api';
if (!bioinmed_weather_geoip_has_location($location)) {
    $fallbackLocation = bioinmed_weather_geoip_from_ipwhois($clientIp, $proxyUrl);
    if (bioinmed_weather_geoip_has_location($fallbackLocation)) {
        $location = $fallbackLocation;
        $provider = 'ipwho.is';
    }
}

if (!bioinmed_weather_geoip_has_location($location)) {
    bioinmed_weather_geoip_log('geoip.error', [
        'client_ip' => $clientIp,
        'provider' => $provider,
        'upstream_status' => (int)($location['status'] ?? 0),
        'message' => (string)($location['message'] ?? ''),
        'proxy_enabled' => $proxyUrl !== '',
    ]);
    echo json_encode([
        'city' => 'Москва',
        'country_code' => 'RU',
        'latitude' => 55.7558,
        'longitude' => 37.6173,
        'fallback' => true,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

bioinmed_weather_geoip_log('geoip.success', [
    'client_ip' => $clientIp,
    'provider' => $provider,
    'city' => $location['city'],
    'latitude' => $location['latitude'],
    'longitude' => $location['longitude'],
    'proxy_enabled' => $proxyUrl !== '',
]);

echo json_encode([
    'city' => $location['city'],
    'country_code' => $location['country_code'],
    'latitude' => $location['latitude'],
    'longitude' => $location['longitude'],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
