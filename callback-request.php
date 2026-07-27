<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Метод не поддерживается.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!function_exists('curl_init')) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'На сервере недоступен модуль cURL.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$consent = (string)($_POST['consent'] ?? '');
if ($consent !== '1') {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Подтвердите согласие с условиями обработки данных.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$raw_phone = trim((string)($_POST['phone'] ?? ''));
$phone = preg_replace('/\D+/', '', $raw_phone);

if (strlen($phone) === 11 && strpos($phone, '8') === 0) {
    $phone = '7' . substr($phone, 1);
} elseif (strlen($phone) === 10) {
    $phone = '7' . $phone;
}

if (strlen($phone) < 11) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Укажите корректный номер телефона.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$source_label = trim((string)($_POST['source_label'] ?? 'Заявка с сайта'));
$page_title = trim((string)($_POST['page_title'] ?? ''));
$page_url = trim((string)($_POST['page_url'] ?? ''));

if ($page_url !== '' && !preg_match('~^https?://~i', $page_url)) {
    $page_url = bioinmed_absolute_url($page_url);
}

$lead_source_parts = [];
foreach ([$source_label, $page_title, $page_url] as $part) {
    $value = trim((string)$part);
    if ($value !== '' && !in_array($value, $lead_source_parts, true)) {
        $lead_source_parts[] = $value;
    }
}

$lead_source = implode(' | ', $lead_source_parts);
if ($lead_source === '') {
    $lead_source = 'Сайт БИОИНМЕД';
}

if (function_exists('mb_substr')) {
    $lead_source = mb_substr($lead_source, 0, 250, 'UTF-8');
} else {
    $lead_source = substr($lead_source, 0, 250);
}

$first_name = 'Клиент ' . $phone;
$api_url = rtrim((string)KLIENTIKS_API_BASE_URL, '/')
    . '/add/a/' . rawurlencode((string)KLIENTIKS_API_ACCOUNT_ID)
    . '/u/' . rawurlencode((string)KLIENTIKS_API_USER_ID)
    . '/m/Clients/';

$payload = http_build_query([
    'phone' => $phone,
    'first_name' => $first_name,
    'lead_source' => $lead_source,
], '', '&', PHP_QUERY_RFC3986);

function bioinmed_klientiks_request(string $url, array $data): array {
    $payload = http_build_query($data, '', '&', PHP_QUERY_RFC3986);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . KLIENTIKS_API_TOKEN,
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
        ],
    ]);

    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    return [
        'response' => $response,
        'curl_error' => $curl_error,
        'http_code' => $http_code,
        'decoded' => is_string($response) ? json_decode($response, true) : null,
    ];
}

function bioinmed_klientiks_error_message($decoded): string {
    if (!is_array($decoded)) {
        return '';
    }

    if (!empty($decoded['error'])) {
        return is_string($decoded['error']) ? $decoded['error'] : 'API вернул ошибку.';
    }

    if (!empty($decoded['errors']) && is_array($decoded['errors'])) {
        return implode('; ', array_map('strval', $decoded['errors']));
    }

    return '';
}

function bioinmed_extract_client_id($decoded): int {
    if (!is_array($decoded)) {
        return 0;
    }

    $variants = [
        $decoded['id'] ?? null,
        $decoded['client_id'] ?? null,
        $decoded['order_id'] ?? null,
        $decoded['object']['id'] ?? null,
        $decoded['object']['client_id'] ?? null,
        $decoded['object']['new_data']['id'] ?? null,
        $decoded['object']['new_data']['client_id'] ?? null,
        $decoded['data']['id'] ?? null,
        $decoded['data']['client_id'] ?? null,
        $decoded['result']['id'] ?? null,
        $decoded['result']['client_id'] ?? null,
        $decoded['response']['id'] ?? null,
        $decoded['response']['client_id'] ?? null,
    ];

    foreach ($variants as $value) {
        if (is_numeric($value) && (int)$value > 0) {
            return (int)$value;
        }
    }

    return 0;
}

$client_request = bioinmed_klientiks_request($api_url, [
    'phone' => $phone,
    'first_name' => $first_name,
    'lead_source' => $lead_source,
]);

$response = $client_request['response'];
$curl_error = $client_request['curl_error'];
$http_code = $client_request['http_code'];
$decoded = $client_request['decoded'];

if ($response === false) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'message' => 'Не удалось отправить заявку в Клиентикс.',
        'details' => $curl_error !== '' ? $curl_error : null,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$api_error = bioinmed_klientiks_error_message($decoded);

if ($http_code >= 400 || $api_error !== '') {
    http_response_code($http_code >= 400 ? $http_code : 502);
    echo json_encode([
        'success' => false,
        'message' => 'Заявка не была принята Клиентикс. Попробуйте ещё раз или позвоните нам.',
        'details' => $api_error !== '' ? $api_error : null,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$client_id = bioinmed_extract_client_id($decoded);
if ($client_id <= 0) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'message' => 'Клиент создан, но Клиентикс не вернул его идентификатор для листа ожидания.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$waiting_list_date = (new DateTimeImmutable('now', new DateTimeZone('Europe/Moscow')))->format('Y-m-d');
$waiting_list_url = rtrim((string)KLIENTIKS_API_BASE_URL, '/')
    . '/add/a/' . rawurlencode((string)KLIENTIKS_API_ACCOUNT_ID)
    . '/u/' . rawurlencode((string)KLIENTIKS_API_USER_ID)
    . '/m/Appointments/s/waitingListItemAdd';

$waiting_list_request = bioinmed_klientiks_request($waiting_list_url, [
    'client_id' => $client_id,
    'start_datetime' => $waiting_list_date,
    'appointment_source' => $lead_source,
    'client_memo' => $lead_source,
]);

if ($waiting_list_request['response'] === false) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'message' => 'Клиент создан, но не удалось добавить запись в лист ожидания.',
        'details' => $waiting_list_request['curl_error'] !== '' ? $waiting_list_request['curl_error'] : null,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$waiting_list_error = bioinmed_klientiks_error_message($waiting_list_request['decoded']);
if ($waiting_list_request['http_code'] >= 400 || $waiting_list_error !== '') {
    http_response_code($waiting_list_request['http_code'] >= 400 ? $waiting_list_request['http_code'] : 502);
    echo json_encode([
        'success' => false,
        'message' => 'Клиент создан, но запись в лист ожидания не была принята Клиентикс.',
        'details' => $waiting_list_error !== '' ? $waiting_list_error : null,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Заявка отправлена. Мы свяжемся с Вами в течение 15 минут.',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
