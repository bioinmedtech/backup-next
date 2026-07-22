<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
bioinmed_admin_require_auth(['admin', 'editor']);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/admin/XlsxWriter.php';

$format = trim((string)($_GET['format'] ?? 'excel'));
if (!in_array($format, ['excel', 'yandex'], true)) {
    bioinmed_admin_json_response(['ok' => false, 'error' => 'Неизвестный формат экспорта.'], 422);
}

$payload = bioinmed_admin_read_json(BIOINMED_ADMIN_CONTENT_DIR . '/pages/prices.json', []);
$sections = is_array($payload['sections'] ?? null) ? $payload['sections'] : [];
$serviceMap = [];
foreach ($services as $service) {
    if (is_array($service) && trim((string)($service['id'] ?? '')) !== '') {
        $serviceMap[(string)$service['id']] = $service;
    }
}

$cell = static fn ($value, int $style = 0, string $type = 'string'): array => ['value' => $value, 'style' => $style, 'type' => $type];
$styleForCell = static function (string $className, bool $bold): int {
    $blue = strpos($className, 'price-row-background-blue') !== false || strpos($className, 'bg-[#f0f7fc]') !== false;
    $beige = strpos($className, 'price-row-background-beige') !== false || strpos($className, 'bg-[#f9f0e6]') !== false;
    if ($blue) return $bold ? 6 : 3;
    if ($beige) return $bold ? 7 : 4;
    return $bold ? 5 : 0;
};
$priceNumber = static function (string $value): ?int {
    if (!preg_match('/\d[\d\s]*/u', $value, $matches)) return null;
    $digits = preg_replace('/\D+/', '', (string)$matches[0]);
    return $digits !== '' ? (int)$digits : null;
};

if ($format === 'yandex') {
    $headers = ['Категория', 'Название', 'Идентификатор', 'Описание', 'Короткое описание', 'Цена', 'Фото', 'Популярный товар', 'В наличии', 'Количество', 'Единицы измерения', 'Ссылка'];
    $rows = [array_map(static fn (string $header): array => $cell($header, 1), $headers)];
    $usedIdentifiers = [];

    foreach ($sections as $sectionIndex => $section) {
        if (!is_array($section) || !empty($section['hidden'])) continue;
        $category = trim((string)($section['title'] ?? ''));
        $sectionId = trim((string)($section['id'] ?? ('section-' . ($sectionIndex + 1))));
        foreach ((array)($section['rows'] ?? []) as $rowIndex => $row) {
            if (!is_array($row) || !empty($row['hidden'])) continue;
            $name = trim((string)($row['title'] ?? ''));
            $price = $priceNumber((string)($row['price'] ?? ''));
            if ($name === '' || $price === null || $price <= 0) continue;

            $serviceId = trim((string)($row['service_id'] ?? ''));
            $service = is_array($serviceMap[$serviceId] ?? null) ? $serviceMap[$serviceId] : [];
            $description = trim((string)($row['description'] ?? '')) ?: trim((string)($service['description'] ?? ''));
            $shortDescription = trim((string)($service['card_description'] ?? '')) ?: mb_substr($description, 0, 250, 'UTF-8');
            $image = $service !== [] ? bioinmed_service_primary_image_url($service) : null;
            $imageUrl = $image ? rtrim(CLINIC_SITE_URL, '/') . '/' . ltrim($image, '/') : '';
            $url = $serviceId !== '' ? rtrim(CLINIC_SITE_URL, '/') . '/services/' . rawurlencode($serviceId) : '';
            $identifierSeed = $serviceId . '|' . $sectionId . '|' . ($rowIndex + 1);
            $identifierPrefix = preg_replace('/[^a-z0-9._-]+/i', '-', $serviceId !== '' ? $serviceId : $sectionId);
            $identifierPrefix = trim((string)$identifierPrefix, '-');
            if ($identifierPrefix === '') $identifierPrefix = 'price';
            $identifier = substr($identifierPrefix, 0, 60) . '-' . substr(hash('sha256', $identifierSeed), 0, 12);
            $identifierCandidate = $identifier;
            $identifierCounter = 2;
            while (isset($usedIdentifiers[$identifierCandidate])) {
                $identifierCandidate = substr($identifier, 0, 76) . '-' . $identifierCounter;
                $identifierCounter++;
            }
            $identifier = $identifierCandidate;
            $usedIdentifiers[$identifier] = true;

            $rows[] = [
                $cell(mb_substr($category, 0, 250, 'UTF-8')),
                $cell(mb_substr($name, 0, 250, 'UTF-8')),
                $cell(mb_substr($identifier, 0, 80, 'UTF-8')),
                $cell(mb_substr($description, 0, 3000, 'UTF-8')),
                $cell(mb_substr($shortDescription, 0, 250, 'UTF-8')),
                $cell($price, 0, 'number'),
                $cell($imageUrl),
                $cell('Нет'),
                $cell('Да'),
                $cell(''),
                $cell(''),
                $cell($url),
            ];
        }
    }

    BioinmedXlsxWriter::output('bioinmed-yandex-prices.xlsx', 'Прайс-лист', $rows, [24, 42, 24, 56, 40, 14, 52, 18, 14, 14, 20, 44]);
}

$headers = ['Раздел', 'Название услуги', 'Описание', 'Длительность', 'Цена', 'ID услуги', 'Ссылка', 'Скрыто'];
$rows = [array_map(static fn (string $header): array => $cell($header, 1), $headers)];
foreach ($sections as $section) {
    if (!is_array($section)) continue;
    $category = trim((string)($section['title'] ?? ''));
    foreach ((array)($section['rows'] ?? []) as $row) {
        if (!is_array($row)) continue;
        $serviceId = trim((string)($row['service_id'] ?? ''));
        $rowClass = (string)($row['row_class'] ?? '');
        $legacyBold = strpos($rowClass, 'price-row-emphasis') !== false || strpos($rowClass, 'font-semibold') !== false;
        $titleBold = $legacyBold || strpos($rowClass, 'price-row-title-emphasis') !== false;
        $descriptionBold = $legacyBold || strpos($rowClass, 'price-row-description-emphasis') !== false;
        $baseStyle = $styleForCell($rowClass, $legacyBold);
        $titleStyle = $styleForCell($rowClass, $titleBold);
        $descriptionStyle = $styleForCell($rowClass, $descriptionBold);
        $url = $serviceId !== '' ? rtrim(CLINIC_SITE_URL, '/') . '/services/' . rawurlencode($serviceId) : '';
        $rows[] = [
            $cell($category, $baseStyle),
            $cell((string)($row['title'] ?? ''), $titleStyle),
            $cell((string)($row['description'] ?? ''), $descriptionStyle),
            $cell((string)($row['duration'] ?? ''), $baseStyle),
            $cell((string)($row['price'] ?? ''), $baseStyle),
            $cell($serviceId, $baseStyle),
            $cell($url, $baseStyle),
            $cell((!empty($section['hidden']) || !empty($row['hidden'])) ? 'Да' : 'Нет', $baseStyle),
        ];
    }
}

BioinmedXlsxWriter::output('bioinmed-prices.xlsx', 'Прайс-лист', $rows, [28, 45, 58, 18, 20, 26, 48, 12]);
