<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';

$pricesPath = dirname(__DIR__, 2) . '/data/content/ru/pages/prices.json';
$raw = is_file($pricesPath) ? file_get_contents($pricesPath) : false;
$payload = is_string($raw) ? json_decode($raw, true) : null;
if (!is_array($payload)) {
    http_response_code(503);
    header('Content-Type: application/xml; charset=UTF-8');
    echo '<?xml version="1.0" encoding="UTF-8"?><error>Price list is temporarily unavailable</error>';
    exit;
}

$modifiedAt = (int)(filemtime($pricesPath) ?: time());
$etag = '"prices-yml-' . $modifiedAt . '-' . strlen((string)$raw) . '"';
if (trim((string)($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $etag) {
    http_response_code(304);
    exit;
}

$serviceMap = [];
foreach ($services as $service) {
    if (is_array($service) && trim((string)($service['id'] ?? '')) !== '') {
        $serviceMap[(string)$service['id']] = $service;
    }
}

$priceNumber = static function (string $value): ?int {
    if (!preg_match('/\d[\d\s]*/u', $value, $matches)) return null;
    $digits = preg_replace('/\D+/', '', (string)$matches[0]);
    return $digits !== '' ? (int)$digits : null;
};

$categories = [];
$offers = [];
$usedCategoryIds = [];
$usedOfferIds = [];

foreach ((array)($payload['sections'] ?? []) as $sectionIndex => $section) {
    if (!is_array($section) || !empty($section['hidden'])) continue;

    $categoryName = trim((string)($section['title'] ?? ''));
    $sectionId = trim((string)($section['id'] ?? ('section-' . ($sectionIndex + 1))));
    if ($categoryName === '') continue;

    $categoryId = (int)sprintf('%u', crc32($sectionId));
    if ($categoryId <= 0) $categoryId = $sectionIndex + 1;
    while (isset($usedCategoryIds[(string)$categoryId]) && $usedCategoryIds[(string)$categoryId] !== $sectionId) {
        $categoryId++;
    }

    $sectionOffers = [];
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
        $url = $serviceId !== ''
            ? rtrim(CLINIC_SITE_URL, '/') . '/services/' . rawurlencode($serviceId)
            : rtrim(CLINIC_SITE_URL, '/') . '/prices#' . rawurlencode($sectionId);

        $identifierSeed = $serviceId . '|' . $sectionId . '|' . ($rowIndex + 1);
        $identifierPrefix = preg_replace('/[^a-z0-9._-]+/i', '-', $serviceId !== '' ? $serviceId : $sectionId);
        $identifierPrefix = trim((string)$identifierPrefix, '-');
        if ($identifierPrefix === '') $identifierPrefix = 'price';
        $identifier = substr($identifierPrefix, 0, 60) . '-' . substr(hash('sha256', $identifierSeed), 0, 12);
        $identifierCandidate = $identifier;
        $identifierCounter = 2;
        while (isset($usedOfferIds[$identifierCandidate])) {
            $identifierCandidate = substr($identifier, 0, 76) . '-' . $identifierCounter;
            $identifierCounter++;
        }
        $identifier = $identifierCandidate;
        $usedOfferIds[$identifier] = true;

        $sectionOffers[] = [
            'id' => $identifier,
            'name' => mb_substr($name, 0, 250, 'UTF-8'),
            'price' => $price,
            'category_id' => (string)$categoryId,
            'description' => mb_substr($description, 0, 3000, 'UTF-8'),
            'short_description' => mb_substr($shortDescription, 0, 250, 'UTF-8'),
            'picture' => $imageUrl,
            'url' => mb_substr($url, 0, 512, 'UTF-8'),
        ];
    }

    if ($sectionOffers === []) continue;
    $usedCategoryIds[(string)$categoryId] = $sectionId;
    $categories[(string)$categoryId] = mb_substr($categoryName, 0, 250, 'UTF-8');
    array_push($offers, ...$sectionOffers);
}

$xml = new XMLWriter();
$xml->openMemory();
$xml->startDocument('1.0', 'UTF-8');
$xml->setIndent(true);
$xml->startElement('yml_catalog');
$xml->startElement('shop');
$xml->startElement('categories');
foreach ($categories as $categoryId => $categoryName) {
    $xml->startElement('category');
    $xml->writeAttribute('id', (string)$categoryId);
    $xml->text($categoryName);
    $xml->endElement();
}
$xml->endElement();
$xml->startElement('offers');
foreach ($offers as $offer) {
    $xml->startElement('offer');
    $xml->writeAttribute('id', $offer['id']);
    $xml->writeAttribute('available', 'true');
    $xml->writeElement('name', $offer['name']);
    $xml->writeElement('vendor', CLINIC_NAME);
    $xml->writeElement('price', (string)$offer['price']);
    $xml->writeElement('currencyId', 'RUB');
    $xml->writeElement('categoryId', $offer['category_id']);
    if ($offer['picture'] !== '') $xml->writeElement('picture', $offer['picture']);
    if ($offer['description'] !== '') $xml->writeElement('description', $offer['description']);
    if ($offer['short_description'] !== '') $xml->writeElement('shortDescription', $offer['short_description']);
    if ($offer['url'] !== '') $xml->writeElement('url', $offer['url']);
    $xml->endElement();
}
$xml->endElement();
$xml->endElement();
$xml->endElement();
$xml->endDocument();

header('Content-Type: application/xml; charset=UTF-8');
header('Cache-Control: public, max-age=300');
header('ETag: ' . $etag);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $modifiedAt) . ' GMT');
header('X-Content-Type-Options: nosniff');
echo $xml->outputMemory();
