<?php
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Bioinmed-Instance: ' . substr(hash('sha256', (string)realpath(__DIR__ . '/../..')), 0, 12));

function bioinmed_weather_geocode_proxy_url() {
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

function bioinmed_weather_geocode_fetch($url, $proxyUrl = '') {
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

function bioinmed_weather_geocode_log($event, array $data = []) {
    $entry = array_merge([
        'time' => date(DATE_ATOM),
        'ip' => (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? ''),
        'ua' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 240),
        'event' => $event,
    ], $data);

    $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    $written = @file_put_contents(__DIR__ . '/../../data/logs/weather-widget.log', $line, FILE_APPEND | LOCK_EX);
    if ($written === false) {
        error_log('[bioinmed-weather-geocode] ' . trim($line));
    }
}

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$city = trim((string)($_GET['city'] ?? ''));
$countryCode = strtoupper(trim((string)($_GET['countryCode'] ?? '')));
if ($city === '') {
    http_response_code(400);
    echo json_encode(['error' => 'empty_city'], JSON_UNESCAPED_UNICODE);
    exit;
}

$city = mb_substr($city, 0, 120, 'UTF-8');
$countryCode = preg_replace('~[^A-Z]~', '', $countryCode);
$countryCode = substr((string)$countryCode, 0, 2);
$cacheDir = __DIR__ . '/../../data/cache/weather';
$cacheKey = 'geocode_' . sha1(mb_strtolower($city, 'UTF-8') . '|' . $countryCode);
$cacheFile = $cacheDir . '/' . $cacheKey . '.json';
$cacheTtl = 30 * 24 * 60 * 60;

if (is_file($cacheFile) && (time() - (int)@filemtime($cacheFile)) < $cacheTtl) {
    $cached = @file_get_contents($cacheFile);
    if (is_string($cached) && $cached !== '') {
        header('X-Bioinmed-Weather-Cache: fresh');
        echo $cached;
        exit;
    }
}

$query = [
    'name' => $city,
    'count' => 1,
    'language' => 'ru',
    'format' => 'json',
];
if ($countryCode !== '') {
    $query['countryCode'] = $countryCode;
}

$url = 'https://geocoding-api.open-meteo.com/v1/search?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
$proxyUrl = bioinmed_weather_geocode_proxy_url();
if ($proxyUrl !== '') {
    header('X-Bioinmed-Weather-Proxy: enabled');
}

$result = bioinmed_weather_geocode_fetch($url, $proxyUrl);
$decoded = json_decode($result['body'], true);
$name = '';
if ($result['status'] >= 200 && $result['status'] < 300 && is_array($decoded)) {
    $first = is_array($decoded['results'] ?? null) ? ($decoded['results'][0] ?? null) : null;
    if (is_array($first) && isset($first['name'])) {
        $name = trim((string)$first['name']);
    }
}

if ($name === '') {
    bioinmed_weather_geocode_log('geocode.error', [
        'city' => $city,
        'country_code' => $countryCode,
        'upstream_status' => $result['status'],
        'proxy_enabled' => $proxyUrl !== '',
    ]);
    echo json_encode(['city' => $city, 'localized' => false], JSON_UNESCAPED_UNICODE);
    exit;
}

$body = json_encode(['city' => $name, 'localized' => true], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if (is_dir($cacheDir) && is_writable($cacheDir)) {
    @file_put_contents($cacheFile, $body, LOCK_EX);
}

bioinmed_weather_geocode_log('geocode.success', [
    'city' => $city,
    'localized_city' => $name,
    'country_code' => $countryCode,
    'proxy_enabled' => $proxyUrl !== '',
]);
header('X-Bioinmed-Weather-Cache: miss');
echo $body;
