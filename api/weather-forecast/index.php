<?php
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Bioinmed-Instance: ' . substr(hash('sha256', (string)realpath(__DIR__ . '/../..')), 0, 12));

function bioinmed_weather_log($event, array $data = []) {
    $entry = array_merge([
        'time' => date(DATE_ATOM),
        'ip' => (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? ''),
        'ua' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 240),
        'event' => $event,
    ], $data);

    $logFile = __DIR__ . '/../../data/logs/weather-widget.log';
    $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    $written = @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    if ($written === false) {
        error_log('[bioinmed-weather-forecast] ' . trim($line));
    }
}

function bioinmed_weather_fetch_json($url, $proxyUrl = '') {
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
                $user = rawurldecode((string)($proxy['user'] ?? ''));
                $pass = rawurldecode((string)($proxy['pass'] ?? ''));
                curl_setopt($handle, CURLOPT_PROXYUSERPWD, $user . ':' . $pass);
            }
        }
    }

    $body = curl_exec($handle);
    $status = (int)curl_getinfo($handle, CURLINFO_HTTP_CODE);
    curl_close($handle);

    return [
        'status' => $status,
        'body' => is_string($body) ? $body : '',
    ];
}

function bioinmed_weather_proxy_url() {
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
        if ($key !== 'BIOINMED_WEATHER_PROXY_URL') {
            continue;
        }

        return trim($rawValue, " \t\n\r\0\x0B\"'");
    }

    return '';
}

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$latitude = filter_input(INPUT_GET, 'latitude', FILTER_VALIDATE_FLOAT);
$longitude = filter_input(INPUT_GET, 'longitude', FILTER_VALIDATE_FLOAT);

if ($latitude === false || $latitude === null || $longitude === false || $longitude === null) {
    bioinmed_weather_log('forecast.invalid_coordinates', [
        'raw_latitude' => (string)($_GET['latitude'] ?? ''),
        'raw_longitude' => (string)($_GET['longitude'] ?? ''),
    ]);
    http_response_code(400);
    echo json_encode(['error' => 'invalid_coordinates'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
    bioinmed_weather_log('forecast.coordinates_out_of_range', [
        'latitude' => $latitude,
        'longitude' => $longitude,
    ]);
    http_response_code(400);
    echo json_encode(['error' => 'coordinates_out_of_range'], JSON_UNESCAPED_UNICODE);
    exit;
}

$cacheDir = __DIR__ . '/../../data/cache/weather';
$cacheKey = sprintf('%.3F_%.3F', $latitude, $longitude);
$cacheFile = $cacheDir . '/' . preg_replace('~[^0-9_.-]+~', '_', $cacheKey) . '.json';
$cacheTtl = 15 * 60;

if (is_file($cacheFile) && (time() - (int)@filemtime($cacheFile)) < $cacheTtl) {
    $cached = @file_get_contents($cacheFile);
    if (is_string($cached) && $cached !== '') {
        bioinmed_weather_log('forecast.cache_fresh', [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
        header('X-Bioinmed-Weather-Cache: fresh');
        echo $cached;
        exit;
    }
}

$query = http_build_query([
    'latitude' => $latitude,
    'longitude' => $longitude,
    'current' => 'temperature_2m,apparent_temperature,relative_humidity_2m,precipitation,weather_code,wind_speed_10m',
    'daily' => 'weather_code,temperature_2m_max,temperature_2m_min',
    'forecast_days' => 7,
    'timezone' => 'auto',
    'wind_speed_unit' => 'ms',
], '', '&', PHP_QUERY_RFC3986);

$url = 'https://api.open-meteo.com/v1/forecast?' . $query;
$weatherProxyUrl = bioinmed_weather_proxy_url();
if ($weatherProxyUrl !== '') {
    header('X-Bioinmed-Weather-Proxy: enabled');
}
$result = bioinmed_weather_fetch_json($url, $weatherProxyUrl);
$body = $result['body'];
$status = $result['status'];

if (!is_string($body) || $body === '' || $status < 200 || $status >= 300) {
    if (is_file($cacheFile)) {
        $stale = @file_get_contents($cacheFile);
        if (is_string($stale) && $stale !== '') {
            bioinmed_weather_log('forecast.cache_stale', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'upstream_status' => $status,
                'proxy_enabled' => $weatherProxyUrl !== '',
            ]);
            header('X-Bioinmed-Weather-Cache: stale');
            echo $stale;
            exit;
        }
    }

    bioinmed_weather_log('forecast.upstream_error', [
        'latitude' => $latitude,
        'longitude' => $longitude,
        'upstream_status' => $status,
        'proxy_enabled' => $weatherProxyUrl !== '',
        'body_length' => is_string($body) ? strlen($body) : 0,
    ]);
    http_response_code(502);
    echo json_encode(['error' => 'weather_unavailable'], JSON_UNESCAPED_UNICODE);
    exit;
}

$decoded = json_decode($body, true);
if (!is_array($decoded)) {
    bioinmed_weather_log('forecast.invalid_response', [
        'latitude' => $latitude,
        'longitude' => $longitude,
        'upstream_status' => $status,
        'proxy_enabled' => $weatherProxyUrl !== '',
        'body_length' => strlen($body),
    ]);
    http_response_code(502);
    echo json_encode(['error' => 'invalid_weather_response'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (is_dir($cacheDir) && is_writable($cacheDir)) {
    @file_put_contents($cacheFile, $body, LOCK_EX);
}

header('X-Bioinmed-Weather-Cache: miss');
bioinmed_weather_log('forecast.success', [
    'latitude' => $latitude,
    'longitude' => $longitude,
    'upstream_status' => $status,
    'proxy_enabled' => $weatherProxyUrl !== '',
]);
echo $body;
