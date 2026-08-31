<?php
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if (!in_array($method, ['GET', 'POST'], true)) {
    http_response_code(405);
    header('Allow: GET, POST');
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = [];
if ($method === 'POST') {
    $raw = @file_get_contents('php://input');
    $payload = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
    if (!is_array($payload)) {
        $payload = [];
    }
} else {
    $payload = $_GET;
}

$event = substr(preg_replace('~[^a-z0-9_.:-]+~i', '_', (string)($payload['event'] ?? 'unknown')), 0, 80);
$context = [
    'time' => date(DATE_ATOM),
    'ip' => (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? ''),
    'ua' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 240),
    'event' => $event,
    'page' => substr((string)($payload['page'] ?? ''), 0, 300),
    'city' => substr((string)($payload['city'] ?? ''), 0, 100),
    'latitude' => isset($payload['latitude']) ? (float)$payload['latitude'] : null,
    'longitude' => isset($payload['longitude']) ? (float)$payload['longitude'] : null,
    'status' => isset($payload['status']) ? (int)$payload['status'] : null,
    'message' => substr((string)($payload['message'] ?? ''), 0, 300),
];

$logFile = __DIR__ . '/../../data/logs/weather-widget.log';
$line = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
$written = @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
if ($written === false) {
    error_log('[bioinmed-weather-widget] ' . trim($line));
}

echo json_encode([
    'ok' => $written !== false,
    'written' => $written !== false,
    'bytes' => $written === false ? 0 : $written,
    'log' => 'data/logs/weather-widget.log',
    'instance' => substr(hash('sha256', (string)realpath(__DIR__ . '/../..')), 0, 12),
], JSON_UNESCAPED_UNICODE);
