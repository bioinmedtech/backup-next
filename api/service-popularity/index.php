<?php
require_once __DIR__ . '/../../config.php';

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}

$type = trim((string)($_POST['type'] ?? ''));
$key = trim((string)($_POST['key'] ?? ''));

if ($type === '' || $key === '') {
    $raw = @file_get_contents('php://input');
    if (is_string($raw) && $raw !== '') {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $type = trim((string)($json['type'] ?? $type));
            $key = trim((string)($json['key'] ?? $key));
        } else {
            $parsed = [];
            parse_str($raw, $parsed);
            if (is_array($parsed)) {
                $type = trim((string)($parsed['type'] ?? $type));
                $key = trim((string)($parsed['key'] ?? $key));
            }
        }
    }
}

if (!bioinmed_service_popularity_record($type, $key)) {
    http_response_code(204);
    exit;
}

http_response_code(204);
