<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$pricesPath = $root . '/data/content/ru/pages/prices.json';
$servicesPath = $root . '/config/services.php';

function extractPriceNumbers(string $value): array
{
    if ($value === '') {
        return [];
    }

    preg_match_all('/\d[\d\s]*$/mu', '', $matches);
    preg_match_all('/\d[\d\s]*/u', $value, $matches);
    $numbers = [];

    foreach (($matches[0] ?? []) as $match) {
        $normalized = preg_replace('/\s+/u', '', $match);
        if ($normalized === null || $normalized === '') {
            continue;
        }

        $numbers[] = (int)$normalized;
    }

    return array_values(array_unique($numbers));
}

function priceStringsMatch(string $rowPrice, string $servicePrice): bool
{
    if ($rowPrice === $servicePrice) {
        return true;
    }

    $rowValues = extractPriceNumbers($rowPrice);
    $serviceValues = extractPriceNumbers($servicePrice);

    if ($rowValues === [] || $serviceValues === []) {
        return false;
    }

    $hasFrom = mb_stripos($servicePrice, 'от', 0, 'UTF-8') !== false;
    $hasTo = mb_stripos($servicePrice, 'до', 0, 'UTF-8') !== false;

    if ($hasFrom && $hasTo && count($serviceValues) >= 2) {
        $min = min($serviceValues);
        $max = max($serviceValues);

        foreach ($rowValues as $value) {
            if ($value < $min || $value > $max) {
                return false;
            }
        }

        return true;
    }

    if ($hasFrom && count($serviceValues) >= 1) {
        $min = min($serviceValues);

        foreach ($rowValues as $value) {
            if ($value < $min) {
                return false;
            }
        }

        return true;
    }

    foreach ($rowValues as $value) {
        if (!in_array($value, $serviceValues, true)) {
            return false;
        }
    }

    return true;
}

if (!is_file($pricesPath) || !is_file($servicesPath)) {
    fwrite(STDERR, "Required files not found.\n");
    exit(2);
}

$pricesRaw = file_get_contents($pricesPath);
$pricesPayload = is_string($pricesRaw) ? json_decode($pricesRaw, true) : null;
if (!is_array($pricesPayload)) {
    fwrite(STDERR, "Invalid prices JSON.\n");
    exit(2);
}

$services = require $servicesPath;
if (!is_array($services)) {
    fwrite(STDERR, "Invalid services config.\n");
    exit(2);
}

$servicesById = [];
foreach ($services as $service) {
    if (!is_array($service)) {
        continue;
    }
    $id = trim((string)($service['id'] ?? ''));
    if ($id === '') {
        continue;
    }
    $price = trim((string)($service['price'] ?? ''));
    $note = trim((string)($service['price_note'] ?? ''));
    $servicesById[$id] = trim($price . ($note !== '' ? ' ' . $note : ''));
}

$sections = is_array($pricesPayload['sections'] ?? null) ? $pricesPayload['sections'] : [];
$issues = [];

foreach ($sections as $section) {
    if (!is_array($section)) {
        continue;
    }
    $sectionId = trim((string)($section['id'] ?? ''));
    $rows = is_array($section['rows'] ?? null) ? $section['rows'] : [];

    foreach ($rows as $idx => $row) {
        if (!is_array($row)) {
            continue;
        }

        $serviceId = trim((string)($row['service_id'] ?? ''));
        $rowPrice = trim((string)($row['price'] ?? ''));

        if ($serviceId === '') {
            $issues[] = "[{$sectionId}:{$idx}] missing service_id";
            continue;
        }

        if (!array_key_exists($serviceId, $servicesById)) {
            $issues[] = "[{$sectionId}:{$idx}] unknown service_id '{$serviceId}'";
            continue;
        }

        if ($rowPrice === '') {
            continue;
        }

        $servicePrice = $servicesById[$serviceId];
        if ($servicePrice === '') {
            $issues[] = "[{$sectionId}:{$idx}] row price '{$rowPrice}' set, but service '{$serviceId}' has empty price";
            continue;
        }

        if (!priceStringsMatch($rowPrice, $servicePrice)) {
            $issues[] = "[{$sectionId}:{$idx}] price mismatch for '{$serviceId}': prices.json='{$rowPrice}' services.php='{$servicePrice}'";
        }
    }
}

if (!$issues) {
    echo "OK: no price consistency issues found.\n";
    exit(0);
}

echo "Found " . count($issues) . " issue(s):\n";
foreach ($issues as $issue) {
    echo " - {$issue}\n";
}

exit(1);
