<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

bioinmed_admin_require_auth(['admin', 'editor']);

function bioinmed_admin_prices_manage_path(): string {
    return BIOINMED_ADMIN_CONTENT_DIR . '/pages/prices.json';
}

function bioinmed_admin_prices_manage_normalize_id(string $value): string {
    $value = mb_strtolower(trim($value), 'UTF-8');
    $value = preg_replace('/[^a-z0-9_-]+/u', '-', $value) ?? '';
    $value = trim($value, '-_');

    return $value;
}

function bioinmed_admin_prices_manage_bool($value): bool {
    if (is_bool($value)) {
        return $value;
    }

    if (is_numeric($value)) {
        return (int)$value !== 0;
    }

    if (!is_string($value)) {
        return false;
    }

    return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
}

function bioinmed_admin_prices_manage_row(array $row): array {
    return [
        'service_id' => trim((string)($row['service_id'] ?? '')),
        'title' => trim((string)($row['title'] ?? '')),
        'description' => trim((string)($row['description'] ?? '')),
        'duration' => trim((string)($row['duration'] ?? '')),
        'price' => trim((string)($row['price'] ?? '')),
        'row_class' => trim((string)($row['row_class'] ?? '')),
        'link' => bioinmed_admin_prices_manage_bool($row['link'] ?? true),
        'hidden' => bioinmed_admin_prices_manage_bool($row['hidden'] ?? false),
    ];
}

function bioinmed_admin_prices_manage_section(array $section, int $index, array &$usedIds): ?array {
    $rawId = trim((string)($section['id'] ?? ''));
    $title = trim((string)($section['title'] ?? ''));
    $navLabel = trim((string)($section['nav_label'] ?? ''));

    $id = bioinmed_admin_prices_manage_normalize_id($rawId);
    if ($id === '') {
        $id = 'section-' . ($index + 1);
    }

    $baseId = $id;
    $n = 2;
    while (isset($usedIds[$id])) {
        $id = $baseId . '-' . $n;
        $n += 1;
    }
    $usedIds[$id] = true;

    if ($title === '') {
        $title = 'Новый раздел';
    }

    if ($navLabel === '') {
        $navLabel = $title;
    }

    $rows = is_array($section['rows'] ?? null) ? $section['rows'] : [];
    $normalizedRows = [];

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $nextRow = bioinmed_admin_prices_manage_row($row);
        if ($nextRow['title'] === '' && $nextRow['price'] === '' && $nextRow['service_id'] === '') {
            continue;
        }

        $normalizedRows[] = $nextRow;
    }

    return [
        'id' => $id,
        'title' => $title,
        'badge' => trim((string)($section['badge'] ?? '')),
        'nav_label' => $navLabel,
        'hidden' => bioinmed_admin_prices_manage_bool($section['hidden'] ?? false),
        'rows' => $normalizedRows,
    ];
}

function bioinmed_admin_prices_manage_sync_prices(array $sections): bool {
    foreach ($sections as $section) {
        if (!is_array($section)) {
            continue;
        }

        $sectionId = trim((string)($section['id'] ?? ''));
        if ($sectionId === '') {
            continue;
        }

        $rows = is_array($section['rows'] ?? null) ? $section['rows'] : [];
        foreach ($rows as $rowIndex => $row) {
            if (!is_array($row)) {
                continue;
            }

            $serviceId = trim((string)($row['service_id'] ?? ''));
            $price = trim((string)($row['price'] ?? ''));
            if ($serviceId === '' || $price === '') {
                continue;
            }

            $textKey = 'pages.prices.sections.' . $sectionId . '.rows.' . $rowIndex . '.price';
            if (!bioinmed_admin_sync_service_price_from_prices_key($textKey, $price)) {
                return false;
            }
        }
    }

    return true;
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$pricesPath = bioinmed_admin_prices_manage_path();
$payload = bioinmed_admin_read_json($pricesPath, []);
$meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
$sections = is_array($payload['sections'] ?? null) ? $payload['sections'] : [];

if ($method === 'GET') {
    $servicesRaw = bioinmed_admin_read_services_config();
    $services = [];

    foreach ($servicesRaw as $service) {
        if (!is_array($service)) {
            continue;
        }

        $id = trim((string)($service['id'] ?? ''));
        $name = trim((string)($service['name'] ?? ''));
        if ($id === '' || $name === '') {
            continue;
        }

        $services[] = [
            'id' => $id,
            'name' => $name,
        ];
    }

    usort($services, static function (array $a, array $b): int {
        return strcmp($a['name'], $b['name']);
    });

    bioinmed_admin_json_response([
        'ok' => true,
        'sections' => $sections,
        'meta' => $meta,
        'services' => $services,
    ]);
}

if ($method !== 'POST') {
    bioinmed_admin_json_response([
        'ok' => false,
        'error' => 'Метод не поддерживается.',
    ], 405);
}

$body = bioinmed_admin_require_post_json();
if (!bioinmed_admin_verify_csrf((string)($body['csrf'] ?? ''))) {
    bioinmed_admin_json_response([
        'ok' => false,
        'error' => 'Некорректный CSRF токен.',
    ], 419);
}

$action = trim((string)($body['action'] ?? ''));
if ($action !== 'save_structure') {
    bioinmed_admin_json_response([
        'ok' => false,
        'error' => 'Неизвестное действие.',
    ], 422);
}

$incomingSections = is_array($body['sections'] ?? null) ? $body['sections'] : null;
if ($incomingSections === null) {
    bioinmed_admin_json_response([
        'ok' => false,
        'error' => 'Не переданы разделы прайса.',
    ], 422);
}

$normalizedSections = [];
$usedIds = [];
foreach ($incomingSections as $idx => $section) {
    if (!is_array($section)) {
        continue;
    }

    $normalized = bioinmed_admin_prices_manage_section($section, (int)$idx, $usedIds);
    if ($normalized === null) {
        continue;
    }

    $normalizedSections[] = $normalized;
}

$nextPayload = [
    'meta' => $meta,
    'sections' => $normalizedSections,
];

if (!bioinmed_admin_write_json($pricesPath, $nextPayload)) {
    bioinmed_admin_json_response([
        'ok' => false,
        'error' => 'Не удалось сохранить структуру прайса.',
    ], 500);
}

if (!bioinmed_admin_prices_manage_sync_prices($normalizedSections)) {
    bioinmed_admin_json_response([
        'ok' => false,
        'error' => 'Прайс сохранён, но не удалось синхронизировать цены услуг.',
    ], 500);
}

bioinmed_admin_json_response([
    'ok' => true,
    'sections' => $normalizedSections,
    'message' => 'Прайс-лист сохранён.',
]);
