<?php
require_once __DIR__ . '/includes/pin_protection.php';
bioinmed_pin_require_access();

require_once 'config.php';
require_once 'includes/components/Components.php';
require_once __DIR__ . '/includes/admin/bootstrap.php';

$pricesAdminUser = bioinmed_admin_current_user();
$pricesCanExport = is_array($pricesAdminUser) && (string)($pricesAdminUser['role'] ?? '') === 'admin';

$pricesPage = bioinmed_read_json_file('pages/prices.json');
$pricesMeta = is_array($pricesPage['meta'] ?? null) ? $pricesPage['meta'] : [];
$pricesSectionsConfig = is_array($pricesPage['sections'] ?? null) ? $pricesPage['sections'] : [];
$pricesRowsBySection = [];
foreach ($pricesSectionsConfig as $sectionConfig) {
    if (!is_array($sectionConfig)) {
        continue;
    }

    $sectionId = trim((string)($sectionConfig['id'] ?? ''));
    if ($sectionId === '') {
        continue;
    }

    $rows = is_array($sectionConfig['rows'] ?? null) ? $sectionConfig['rows'] : [];
    $pricesRowsBySection[$sectionId] = $rows;
}

$siteUrl = rtrim(CLINIC_SITE_URL, '/');
$iconPath = CLINIC_ICON_PATH;
$socialImageUrl = bioinmed_default_social_image_url();
$canonicalUrl = $siteUrl . '/prices';
$pricesPageLink = bioinmed_link('pages.prices');
$pageTitle = (string)($pricesMeta['title'] ?? 'Прайс-лист услуг и цены') . ' | ' . CLINIC_NAME;
$pageDescription = (string)($pricesMeta['description'] ?? 'Полный прайс-лист с ценами на все услуги клиники БИОИНМЕД. Диагностика, остеопатия, рефлексотерапия, физиотерапия и другие методики.');

$pricesHeroTextNode = bioinmed_page_text_node(
    $pricesPage,
    'prices',
    'meta.hero_text',
    'Актуальные цены по направлениям лечения: от первичной консультации до комплексных программ восстановления. Поможем подобрать специалиста и оптимальный формат терапии.'
);
$pricesHeroBadgePrimaryNode = bioinmed_page_text_node($pricesPage, 'prices', 'meta.hero_badge_primary', 'Актуальные цены');
$pricesHeroBadgeSecondaryNode = bioinmed_page_text_node($pricesPage, 'prices', 'meta.hero_badge_secondary', 'Ежедневно 9:00-21:00');
$pricesQuickNavTitleNode = bioinmed_page_text_node($pricesPage, 'prices', 'meta.quick_nav_title', 'Быстрая навигация:');

$servicesById = [];
foreach ($services as $serviceItem) {
    if (!is_array($serviceItem)) {
        continue;
    }

    $id = trim((string)($serviceItem['id'] ?? ''));
    if ($id === '') {
        continue;
    }

    $servicesById[$id] = $serviceItem;
}

$priceListElements = [];
$position = 1;
foreach ($services as $serviceItem) {
    if (!is_array($serviceItem)) {
        continue;
    }

    $id = trim((string)($serviceItem['id'] ?? ''));
    $name = trim((string)($serviceItem['name'] ?? ''));
    if ($id === '' || $name === '') {
        continue;
    }

    $priceListElements[] = [
        '@type' => 'ListItem',
        'position' => $position++,
        'name' => $name,
        'url' => $siteUrl . '/services/' . rawurlencode($id),
    ];
}

$sectionsMeta = [];
foreach ($pricesSectionsConfig as $sectionConfig) {
    if (!is_array($sectionConfig)) {
        continue;
    }

    $sectionId = trim((string)($sectionConfig['id'] ?? ''));
    if ($sectionId === '') {
        continue;
    }

    $title = trim((string)($sectionConfig['title'] ?? ''));
    $navLabel = trim((string)($sectionConfig['nav_label'] ?? $title));
    if ($title === '' || $navLabel === '') {
        continue;
    }

    $sectionsMeta[] = [
        'id' => $sectionId,
        'title' => $title,
        'badge' => trim((string)($sectionConfig['badge'] ?? '')),
        'nav_label' => $navLabel,
        'hidden' => !empty($sectionConfig['hidden']),
    ];
}

$serviceSelectOptionsHtml = '<option value="">Без привязки</option>';
foreach ($services as $serviceItem) {
    if (!is_array($serviceItem)) {
        continue;
    }

    $serviceOptionId = trim((string)($serviceItem['id'] ?? ''));
    $serviceOptionName = trim((string)($serviceItem['name'] ?? ''));
    if ($serviceOptionId === '' || $serviceOptionName === '') {
        continue;
    }

    $serviceSelectOptionsHtml .= '<option value="' . htmlspecialchars($serviceOptionId, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($serviceOptionName, ENT_QUOTES, 'UTF-8') . '</option>';
}

$resolveServicePrice = static function (string $serviceId) use ($servicesById): string {
    if (!isset($servicesById[$serviceId]) || !is_array($servicesById[$serviceId])) {
        return '';
    }

    $item = $servicesById[$serviceId];
    $price = trim((string)($item['price'] ?? ''));
    $note = trim((string)($item['price_note'] ?? ''));

    if ($price === '') {
        return '';
    }

    return trim($price . ($note !== '' ? ' ' . $note : ''));
};

$normalizeForCompare = static function (string $value): string {
    $value = mb_strtolower($value, 'UTF-8');
    $value = str_replace(['ё', '«', '»', '"', '(', ')', '.', ',', ':', ';', '-', '+', '/'], ['е', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' '], $value);
    $value = preg_replace('/\s+/u', ' ', $value);

    return trim((string)$value);
};

$extractPriceNumbers = static function (string $value): array {
    if ($value === '') {
        return [];
    }

    preg_match_all('/\d[\d\s]*/u', $value, $matches);
    $numbers = [];

    foreach (($matches[0] ?? []) as $match) {
        $normalized = preg_replace('/\s+/u', '', (string)$match);
        if ($normalized === null || $normalized === '') {
            continue;
        }

        $numbers[] = (int)$normalized;
    }

    return array_values(array_unique($numbers));
};

$shouldLinkService = static function (
    string $rowTitle,
    string $serviceName,
    string $rowPrice,
    string $servicePrice
) use ($normalizeForCompare, $extractPriceNumbers): bool {
    $normalizedRowTitle = $normalizeForCompare($rowTitle);
    $normalizedServiceName = $normalizeForCompare($serviceName);

    if ($normalizedRowTitle === '' || $normalizedServiceName === '') {
        return false;
    }

    if ($normalizedRowTitle === $normalizedServiceName) {
        return true;
    }

    if (mb_stripos($normalizedRowTitle, $normalizedServiceName, 0, 'UTF-8') !== false || mb_stripos($normalizedServiceName, $normalizedRowTitle, 0, 'UTF-8') !== false) {
        return true;
    }

    $rowTokens = array_values(array_filter(explode(' ', $normalizedRowTitle), static fn (string $token): bool => mb_strlen($token, 'UTF-8') >= 4));
    $serviceTokens = array_values(array_filter(explode(' ', $normalizedServiceName), static fn (string $token): bool => mb_strlen($token, 'UTF-8') >= 4));

    if ($rowTokens !== [] && $serviceTokens !== []) {
        $shared = count(array_intersect($rowTokens, $serviceTokens));
        if ($shared >= 2) {
            return true;
        }
    }

    $rowPriceNumbers = $extractPriceNumbers($rowPrice);
    $servicePriceNumbers = $extractPriceNumbers($servicePrice);
    if ($rowPriceNumbers !== [] && $servicePriceNumbers !== []) {
        if (count(array_intersect($rowPriceNumbers, $servicePriceNumbers)) > 0) {
            return true;
        }
    }

    return false;
};

$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $pageTitle,
    'description' => $pageDescription,
    'url' => $canonicalUrl,
    'inLanguage' => 'ru-RU',
    'mainEntity' => [
        '@type' => 'ItemList',
        'itemListElement' => $priceListElements,
    ],
];

$organizationStructuredData = bioinmed_medical_organization_schema();
$breadcrumbStructuredData = bioinmed_breadcrumb_schema([
    ['name' => 'Главная', 'url' => '/'],
    ['name' => 'Прайс-лист', 'url' => $pricesPageLink['url']],
]);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="theme-color" content="#1977b2">
    <?php echo bioinmed_render_social_meta($pageTitle, $pageDescription, $canonicalUrl, [
        'image' => $socialImageUrl,
    ]); ?>
    <?php echo bioinmed_render_favicon_links($iconPath); ?>
    <script type="application/ld+json"><?php echo json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script type="application/ld+json"><?php echo json_encode($organizationStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script type="application/ld+json"><?php echo json_encode($breadcrumbStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <?php if ($pricesCanExport): ?>
        <script>
            (function () {
                try {
                    if (localStorage.getItem('bioinmed:prices-print-mode') === '1') {
                        window.BioinmedDisableUis = true;
                        document.documentElement.classList.add('prices-print-mode-pending');
                    }
                } catch (error) {}
            })();
        </script>
    <?php endif; ?>
    <?php echo bioinmed_render_public_head_assets(['include_uis_hints' => false]); ?>
    <style>
        html { font-size: clamp(17px, 0.5vw + 15px, 19px); }
        body { line-height: 1.72; }
        .category-section { scroll-margin-top: 120px; border: 1px solid #dce8f5; border-radius: 0.9rem; background: #fff; padding: 0.95rem; box-shadow: 0 6px 16px rgba(10, 43, 80, 0.05); }
        .prices-hero { position: relative; overflow: hidden; border: 1px solid #d7e4ef; border-radius: 1rem; background: linear-gradient(120deg, #eef6fd 0%, #e4f1fb 45%, #dff0fb 100%); box-shadow: 0 10px 24px rgba(6, 29, 60, 0.07); }
        .prices-nav { position: static; border: 1px solid #dce8f5; border-radius: 0.8rem; background: rgba(248, 252, 255, 0.95); backdrop-filter: blur(6px); box-shadow: 0 6px 16px rgba(10, 43, 80, 0.06); }
        .category-section table { width: 100%; border-collapse: separate; border-spacing: 0; overflow: hidden; border-radius: 0.85rem; border: 1px solid #e1edf8; }
        .category-section thead th { background: #eef6fd; }
        .category-section tbody tr:hover { background: #f3faff; }
        .category-section td, .category-section th { border-bottom: 1px solid #e9f2fb; padding: 0.76rem 0.92rem; font-size: 0.96rem; line-height: 1.45; }
        .category-section h2 { font-size: 1.48rem; line-height: 1.18; }
        .category-section > div:first-child { margin-bottom: 0.95rem; padding-bottom: 0.62rem; }
        .category-section td p { font-size: 0.9rem; line-height: 1.45; margin-top: 0.3rem; }
        .category-section td div.font-semibold { font-size: 1rem; line-height: 1.3; }
        .category-section th:nth-child(2), .category-section td:nth-child(2) { text-align: center; white-space: nowrap; }
        .category-section td:last-child, .category-section th:last-child { font-size: 0.9rem; white-space: nowrap; }
        .prices-hero h1 { font-size: 1.76rem; line-height: 1.14; }
        .prices-hero p { font-size: 0.98rem; line-height: 1.5; }
        .prices-nav h3 { font-size: 0.82rem; margin-bottom: 0.65rem; }
        .prices-nav a { font-size: 0.8rem; padding: 0.42rem 0.78rem; }
        .category-section tbody tr:last-child td { border-bottom: none; }
        .prices-cta h3 { font-size: 1.42rem; line-height: 1.2; margin-bottom: 0.55rem; }
        .prices-cta p { font-size: 0.96rem; line-height: 1.5; }
        .prices-cta a { font-size: 0.94rem; }
        .prices-hero .prices-document-tools { position: absolute !important; z-index: 6; top: 0.75rem; right: 0.75rem; left: auto; display: flex; flex-wrap: wrap; justify-content: flex-end; align-items: center; gap: 0.45rem; max-width: calc(100% - 1.5rem); margin: 0; padding: 0.45rem; border: 1px solid rgba(188,212,232,.9); border-radius: 0.8rem; background: rgba(255,255,255,.94); box-shadow: 0 8px 22px rgba(15,39,73,.12); backdrop-filter: blur(8px); }
        .prices-print-toggle { display: inline-flex; align-items: center; gap: 0.55rem; min-height: 2.35rem; padding: 0.35rem 0.7rem; border: 1px solid #bcd4e8; border-radius: 0.65rem; background: rgba(255,255,255,0.86); color: #17446f; font-size: 0.78rem; font-weight: 700; cursor: pointer; }
        .prices-print-toggle input { position: absolute; opacity: 0; pointer-events: none; }
        .prices-print-toggle-track { position: relative; width: 2.3rem; height: 1.3rem; border-radius: 999px; background: #b8c9d9; transition: background .18s ease; }
        .prices-print-toggle-track::after { content: ''; position: absolute; top: 0.15rem; left: 0.15rem; width: 1rem; height: 1rem; border-radius: 50%; background: #fff; box-shadow: 0 1px 4px rgba(15,39,73,.25); transition: transform .18s ease; }
        .prices-print-toggle input:checked + .prices-print-toggle-track { background: #1977b2; }
        .prices-print-toggle input:checked + .prices-print-toggle-track::after { transform: translateX(1rem); }
        .prices-signature-toggle { display: inline-flex; align-items: center; gap: 0.5rem; min-height: 2.35rem; padding: 0.35rem 0.7rem; border: 1px solid #bcd4e8; border-radius: 0.65rem; background: #fff; color: #17446f; font-size: 0.76rem; font-weight: 700; cursor: pointer; }
        .prices-signature-toggle input { position: absolute; opacity: 0; pointer-events: none; }
        .prices-signature-toggle-box { display: inline-flex; align-items: center; justify-content: center; width: 1.2rem; height: 1.2rem; border: 1px solid #9fbad2; border-radius: 0.3rem; background: #fff; color: transparent; font-size: 0.75rem; }
        .prices-signature-toggle input:checked + .prices-signature-toggle-box { border-color: #1977b2; background: #1977b2; color: #fff; }
        .prices-document-button { display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; min-height: 2.35rem; padding: 0.45rem 0.75rem; border: 1px solid #1977b2; border-radius: 0.65rem; background: #1977b2; color: #fff; font-size: 0.76rem; font-weight: 700; line-height: 1.2; text-decoration: none; cursor: pointer; }
        .prices-document-button:hover { background: #16658f; color: #fff; }
        .prices-document-button.is-secondary { border-color: #bcd4e8; background: #fff; color: #17446f; }
        .prices-document-button.is-secondary:hover { border-color: #1977b2; background: #f5faff; color: #1977b2; }
        .prices-export-menu { position: relative; }
        .prices-export-toggle-chevron { margin-left: 0.15rem; font-size: 0.62rem; transition: transform .18s ease; }
        .prices-export-menu.is-open .prices-export-toggle-chevron { transform: rotate(180deg); }
        .prices-export-dropdown { position: absolute; z-index: 12; top: calc(100% + 0.45rem); right: 0; display: grid; gap: 0.3rem; width: max-content; min-width: 12.5rem; padding: 0.4rem; border: 1px solid #d5e3ef; border-radius: 0.75rem; background: #fff; box-shadow: 0 12px 30px rgba(15,39,73,.18); }
        .prices-export-dropdown[hidden] { display: none !important; }
        .prices-export-dropdown .prices-document-button { width: 100%; justify-content: flex-start; border-color: transparent; background: #fff; color: #17446f; }
        .prices-export-dropdown .prices-document-button:hover { border-color: #d8e8f5; background: #eef6fd; color: #1977b2; }
        .prices-print-header, .prices-print-footer, .prices-signature-zone { display: none; }
        .price-service-link { color: #0a293c; text-decoration: underline; text-decoration-color: rgba(36, 140, 255, 0.45); text-underline-offset: 2px; transition: color .2s ease, text-decoration-color .2s ease; }
        .price-service-link:hover { color: #1977b2; text-decoration-color: rgba(36, 140, 255, 0.95); }
        tr.price-row-background-blue, tr[data-price-row-class~="bg-[#f0f7fc]"] { background-color: #f0f7fc; }
        tr.price-row-background-beige, tr[data-price-row-class~="bg-[#f9f0e6]"] { background-color: #f9f0e6; }
        tr.price-row-emphasis, tr[data-price-row-class~="font-semibold"] { font-weight: 600; }
        .price-section-hidden, tr.price-row-hidden, .price-nav-link-hidden { display: none; }
        .price-admin-section-toolbar, .price-admin-row-actions { display: none; }
        body.bioinmed-edit-mode .price-section-hidden { display: block; }
        body.bioinmed-edit-mode tr.price-row-hidden { display: table-row; }
        body.bioinmed-edit-mode .price-nav-link-hidden { display: inline-flex; opacity: 0.58; }
        @media (min-width: 768px) { .prices-hero h1 { font-size: 2.15rem; } }
        @media (max-width: 767px) {
            .category-section { padding: 0.78rem; }
            .category-section h2 { font-size: 1.24rem; }
            .prices-hero h1 { font-size: 1.55rem; }
            .prices-document-tools { justify-content: flex-start; }
            .prices-document-button, .prices-print-toggle, .prices-signature-toggle { flex: 1 1 auto; }
            .prices-hero.has-document-tools .prices-hero-content { padding-top: 7.2rem; }
        }

        body.prices-print-mode,
        body.prices-print-mode.bioinmed-admin-authenticated { padding-top: 0 !important; background: #fff !important; color: #111827; }
        body.prices-print-mode #site-header,
        body.prices-print-mode #mob-backdrop,
        body.prices-print-mode #mob-menu,
        body.prices-print-mode .desktop-menu-bar,
        body.prices-print-mode > footer:not(.prices-print-footer),
        body.prices-print-mode .prices-nav,
        body.prices-print-mode .prices-cta,
        body.prices-print-mode .bioinmed-admin-toolbar,
        body.prices-print-mode .bioinmed-block-edit-badge,
        body.prices-print-mode .price-admin-section-toolbar,
        body.prices-print-mode .price-admin-row-actions { display: none !important; }
        body.prices-print-mode.bioinmed-edit-mode .price-section-hidden,
        body.prices-print-mode.bioinmed-edit-mode tr.price-row-hidden { display: none !important; }
        body.prices-print-mode .prices-print-header,
        body.prices-print-mode .prices-print-footer { display: flex; }
        body.prices-print-mode .prices-hero .prices-document-tools { position: fixed !important; top: 0; right: 0; left: 0; width: 100%; max-width: none; justify-content: center; border-width: 0 0 1px; border-radius: 0; box-shadow: 0 8px 24px rgba(15,39,73,.16); }
        body.prices-print-mode .prices-print-header { margin-top: 4.25rem; }
        body.prices-print-mode .prices-main { box-sizing: border-box; width: 100%; max-width: 210mm; padding: 10mm 0; }
        body.prices-print-mode .prices-hero { margin-bottom: 5mm; border: 0; border-radius: 0; background: #fff; box-shadow: none; padding: 0; text-align: center; }
        body.prices-print-mode .prices-hero-decoration,
        body.prices-print-mode .prices-hero-eyebrow,
        body.prices-print-mode .prices-hero-description,
        body.prices-print-mode .prices-hero-badges { display: none !important; }
        body.prices-print-mode .prices-hero h1 { margin: 0; color: #0f2749; font-size: 0; line-height: 0; }
        body.prices-print-mode .prices-hero h1::after { content: 'ПРАЙС-ЛИСТ'; display: block; box-sizing: border-box; width: 100%; padding: 2.4mm 8mm; border-radius: 2.5mm; background: linear-gradient(90deg, #f2f9fd 0%, #dceefa 50%, #f2f9fd 100%); box-shadow: inset 0 -1px 0 rgba(25,119,178,.18); color: #1977b2; font-size: 19pt; font-weight: 800; line-height: 1.15; letter-spacing: 0.12em; }
        body.prices-print-mode .price-service-link { color: inherit; text-decoration: none; }
        body.prices-print-mode .category-section { margin-bottom: 5mm; border: 1px solid white; border-radius: 4mm; background: #fff; box-shadow: none; padding: 0; }
        body.prices-print-mode .category-section > div:first-child { margin-bottom: 2mm; padding-bottom: 2mm; }
        body.prices-print-mode .category-section h2 { font-size: 16pt; line-height: 1.3; }
        body.prices-print-mode .category-section .overflow-x-auto { overflow: hidden; border-radius: 3mm; }
        body.prices-print-mode .category-section table { table-layout: fixed; border-collapse: separate; border-spacing: 0; border: 1px solid #e1edf8; border-radius: 3mm; background: #fff; overflow: hidden; }
        body.prices-print-mode .category-section th:nth-child(2),
        body.prices-print-mode .category-section td:nth-child(2) { width: 34mm; }
        body.prices-print-mode .category-section th:nth-child(3),
        body.prices-print-mode .category-section td:nth-child(3) { width: 38mm; white-space: normal !important; overflow-wrap: anywhere; }
        body.prices-print-mode .category-section td,
        body.prices-print-mode .category-section th { padding: 1.7mm 2.8mm; border: 0; border-bottom: 1px solid #e9f2fb; font-size: 11pt; line-height: 1.3; }
        body.prices-print-mode .category-section thead th { border-top: 1px solid #e1edf8; background: #eef6fd !important; color: #1977b2 !important; font-weight: 700; }
        body.prices-print-mode .category-section thead th:first-child { border-top-left-radius: 3mm; }
        body.prices-print-mode .category-section thead th:last-child { border-top-right-radius: 3mm; }
        body.prices-print-mode .category-section tbody tr:last-child td { border-bottom: 1px solid #e1edf8; }
        body.prices-print-mode .category-section tbody tr:last-child td:first-child { border-bottom-left-radius: 3mm; }
        body.prices-print-mode .category-section tbody tr:last-child td:last-child { border-bottom-right-radius: 3mm; }
        body.prices-print-mode .category-section tbody tr:hover { background: inherit; }
        body.prices-print-mode .category-section [data-price-row-title-view] { font-size: 11.5pt !important; line-height: 1.3 !important; font-weight: 600; }
        body.prices-print-mode .category-section td div.font-semibold { font-size: 11.5pt !important; line-height: 1.3 !important; }
        body.prices-print-mode .category-section td p { font-size: 10.5pt; line-height: 1.35; }
        body.prices-print-mode .category-section td:last-child { font-size: 12pt; font-weight: 700; }
        .prices-print-header { width: 100%; max-width: 210mm; margin: 0 auto; padding: 10mm 10mm 4mm; align-items: center; justify-content: space-between; gap: 1rem; border-bottom: 1px solid #cbd5df; }
        .prices-print-header img { display: block !important; visibility: visible !important; width: auto; height: 3.1rem; object-fit: contain; }
        .prices-print-header-copy { display: grid; gap: 0.1rem; }
        .prices-print-header strong { color: #0f2749; font-size: 1rem; }
        .prices-print-header span { color: #52677c; font-size: 0.75rem; text-align: right; }
        .prices-print-footer { width: 100%; max-width: 210mm; margin: 0 auto; padding: 4mm 0 10mm; justify-content: space-between; gap: 1rem; border-top: 1px solid #cbd5df; color: #52677c; font-size: 0.7rem; }
        body.prices-print-mode.prices-show-signature .prices-signature-zone { display: block; }
        .prices-signature-zone { width: 100%; max-width: 210mm; margin: 0 auto; padding: 0 0 12mm; color: #0f2749; break-inside: avoid; page-break-inside: avoid; }
        .prices-signature-card { position: relative; min-height: 58mm; padding: 8mm 56mm 8mm 8mm; border: 1px solid #b9c8d6; border-radius: 3mm; background: #fff; }
        .prices-signature-role { max-width: 112mm; font-size: 10pt; font-weight: 600; line-height: 1.45; }
        .prices-signature-fields { display: grid; grid-template-columns: minmax(48mm, 1fr) auto; align-items: end; gap: 8mm; margin-top: 15mm; }
        .prices-signature-fields > div:first-child { position: relative; height: 8mm; }
        .prices-signature-line { display: block; height: 8mm; border-bottom: 1px solid #52677c; }
        .prices-signature-caption { position: absolute; top: 9mm; right: 0; left: 0; display: block; color: #718397; font-size: 7pt; line-height: 1; text-align: center; }
        .prices-signature-name { padding-bottom: 1.5mm; font-size: 10pt; font-weight: 700; white-space: nowrap; }
        .prices-signature-seal { position: absolute; right: 8mm; top: 8mm; display: flex; align-items: center; justify-content: center; width: 40mm; height: 40mm; border: 1px dashed #9babb9; border-radius: 50%; color: #718397; font-size: 8pt; font-weight: 700; }

        @media (max-width: 767px) {
            body.prices-print-mode .prices-print-header { margin-top: 9rem; }
        }

        @media print {
            @page { size: A4 portrait; margin: 10mm; }
            html, body, body.bioinmed-admin-authenticated { padding-top: 0 !important; background: #fff !important; color: #111827 !important; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            #site-header,
            #mob-backdrop,
            #mob-menu,
            .desktop-menu-bar,
            body > footer:not(.prices-print-footer),
            .prices-nav,
            .prices-cta,
            .prices-document-tools,
            .bioinmed-admin-toolbar,
            .bioinmed-admin-overlay,
            .bioinmed-block-edit-badge,
            .price-admin-section-toolbar,
            .price-admin-row-actions { display: none !important; }
            body.bioinmed-edit-mode .price-section-hidden,
            body.bioinmed-edit-mode tr.price-row-hidden { display: none !important; }
            .prices-print-header { display: flex !important; margin-top: 0 !important; padding: 0 0 4mm !important; }
            .prices-print-footer { display: flex !important; padding: 4mm 0 0 !important; }
            body.prices-show-signature .prices-signature-zone { display: block !important; padding: 8mm 0 0 !important; }
            .prices-main { box-sizing: border-box; width: 190mm !important; max-width: 190mm !important; margin-right: auto !important; margin-left: auto !important; padding: 10mm 0 !important; }
            .prices-hero { margin-bottom: 5mm !important; border: 0 !important; border-radius: 0 !important; background: #fff !important; box-shadow: none !important; padding: 0 !important; text-align: center !important; }
            .prices-hero-decoration, .prices-hero-eyebrow, .prices-hero-description, .prices-hero-badges { display: none !important; }
            .prices-hero.has-document-tools .prices-hero-content { padding-top: 0 !important; }
            .prices-hero h1 { margin: 0 !important; font-size: 0 !important; line-height: 0 !important; color: #0f2749 !important; }
            .prices-hero h1::after { content: 'ПРАЙС-ЛИСТ'; display: block; box-sizing: border-box; width: 100%; padding: 2.4mm 8mm; border-radius: 2.5mm; background: linear-gradient(90deg, #f2f9fd 0%, #dceefa 50%, #f2f9fd 100%); box-shadow: inset 0 -1px 0 rgba(25,119,178,.18); color: #1977b2; font-size: 19pt; font-weight: 800; line-height: 1.15; letter-spacing: 0.12em; }
            .price-service-link { color: inherit !important; text-decoration: none !important; }
            [data-prices-page-root] { display: block !important; }
            .category-section { margin: 0 0 5mm !important; padding: 0 !important; border: 1px solid white !important; border-radius: 4mm !important; background: #fff !important; box-shadow: none !important; break-inside: auto; page-break-inside: auto; }
            .category-section > div:first-child { margin-bottom: 2mm !important; padding-bottom: 2mm !important; }
            .category-section h2 { font-size: 16pt !important; line-height: 1.3 !important; break-after: avoid; page-break-after: avoid; }
            .category-section .overflow-x-auto { overflow: visible !important; }
            .category-section table { table-layout: fixed !important; border-collapse: separate !important; border-spacing: 0 !important; border: 1px solid #e1edf8 !important; border-radius: 3mm !important; background: #fff !important; overflow: visible !important; }
            .category-section th:nth-child(2), .category-section td:nth-child(2) { width: 34mm !important; }
            .category-section th:nth-child(3), .category-section td:nth-child(3) { width: 38mm !important; white-space: normal !important; overflow-wrap: anywhere; }
            .category-section thead { display: table-header-group; }
            .category-section tr { break-inside: avoid; page-break-inside: avoid; }
            .category-section td, .category-section th { padding: 1.7mm 2.8mm !important; border: 0 !important; border-bottom: 1px solid #e9f2fb !important; font-size: 11pt !important; line-height: 1.3 !important; }
            .category-section thead th { border-top: 1px solid #e1edf8 !important; background: #eef6fd !important; color: #1977b2 !important; font-weight: 700 !important; }
            .category-section thead th:first-child { border-top-left-radius: 3mm !important; }
            .category-section thead th:last-child { border-top-right-radius: 3mm !important; }
            .category-section tbody tr:last-child td { border-bottom: 1px solid #e1edf8 !important; }
            .category-section tbody tr:last-child td:first-child { border-bottom-left-radius: 3mm !important; }
            .category-section tbody tr:last-child td:last-child { border-bottom-right-radius: 3mm !important; }
            .category-section [data-price-row-title-view] { font-size: 11.5pt !important; line-height: 1.3 !important; font-weight: 600 !important; }
            .category-section td div.font-semibold { font-size: 11.5pt !important; line-height: 1.3 !important; }
            .category-section td p { font-size: 10.5pt !important; line-height: 1.35 !important; }
            .category-section td:last-child { font-size: 12pt !important; font-weight: 700 !important; }
            .prices-print-footer { break-before: avoid; page-break-before: avoid; }
        }
    </style>
    <?php echo bioinmed_uis_counter_head(); ?>
</head>
<body class="bg-[#e4f1fa] text-[#0f2749] antialiased">
    <script>if (window.BioinmedDisableUis) document.body.classList.add('prices-print-mode');</script>
    <?php echo bioinmed_yandex_metrika_noscript(); ?>
<?php
$header = new Header($brand_colors);
    echo $header->render();
    ?>

    <div class="prices-print-header">
        <img src="<?php echo htmlspecialchars(bioinmed_versioned_asset_path('/public/images/brand/main-logotype.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars(CLINIC_NAME, ENT_QUOTES, 'UTF-8'); ?>" width="1348" height="400">
        <div class="prices-print-header-copy">
            <strong><?php echo htmlspecialchars(CLINIC_NAME, ENT_QUOTES, 'UTF-8'); ?></strong>
            <span><?php echo htmlspecialchars(CLINIC_PHONE . ' · ' . CLINIC_ADDRESS, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
    </div>

    <main class="prices-main mx-auto max-w-6xl px-6 py-8 md:px-10 md:py-10">
        <div class="prices-hero<?php echo $pricesCanExport ? ' has-document-tools' : ''; ?> mb-6 p-5 md:p-6">
            <?php if ($pricesCanExport): ?>
                <div class="prices-document-tools" aria-label="Документы и печать">
                    <label class="prices-print-toggle" for="prices-print-mode-toggle">
                        <span>Режим печати</span>
                        <input id="prices-print-mode-toggle" type="checkbox" role="switch" aria-label="Включить режим печати">
                        <span class="prices-print-toggle-track" aria-hidden="true"></span>
                    </label>
                    <button id="prices-save-pdf" type="button" class="prices-document-button"><i class="fa-solid fa-file-pdf" aria-hidden="true"></i><span>Сохранить в PDF</span></button>
                    <label class="prices-signature-toggle" for="prices-signature-toggle">
                        <input id="prices-signature-toggle" type="checkbox">
                        <span class="prices-signature-toggle-box" aria-hidden="true">✓</span>
                        <span>Подпись/печать</span>
                    </label>
                    <div class="prices-export-menu" id="prices-export-menu">
                        <button id="prices-export-toggle" type="button" class="prices-document-button is-secondary" aria-haspopup="menu" aria-expanded="false"><i class="fa-solid fa-file-export" aria-hidden="true"></i><span>Экспорт</span><i class="fa-solid fa-chevron-down prices-export-toggle-chevron" aria-hidden="true"></i></button>
                        <div class="prices-export-dropdown" id="prices-export-dropdown" role="menu" hidden>
                            <button type="button" class="prices-document-button" role="menuitem" data-prices-export-url="/api/admin/prices-export/?format=excel" data-prices-export-filename="bioinmed-prices.xlsx"><i class="fa-solid fa-file-excel" aria-hidden="true"></i><span>Excel</span></button>
                            <button type="button" class="prices-document-button" role="menuitem" data-prices-export-url="/api/admin/prices-export/?format=yandex" data-prices-export-filename="bioinmed-yandex-prices.xlsx"><i class="fa-solid fa-building" aria-hidden="true"></i><span>Яндекс Бизнес</span></button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="prices-hero-decoration absolute -right-14 -top-14 h-36 w-36 rounded-full bg-[#1977b21f] blur-2xl"></div>
            <div class="prices-hero-decoration absolute -left-12 bottom-0 h-28 w-28 rounded-full bg-[#1977b214] blur-2xl"></div>
            <div class="prices-hero-content relative" data-admin-block-root>
                <p class="prices-hero-eyebrow text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-[#1977b2]"<?php echo bioinmed_page_text_attr($pricesPage, 'prices', 'meta.hero_eyebrow'); ?>><?php echo htmlspecialchars((string)($pricesMeta['hero_eyebrow'] ?? 'Прайс-лист'), ENT_QUOTES, 'UTF-8'); ?></p>
                <h1 class="mt-2 font-bold text-[#0f2749]"<?php echo bioinmed_page_text_attr($pricesPage, 'prices', 'meta.hero_title'); ?>><?php echo htmlspecialchars((string)($pricesMeta['hero_title'] ?? 'Стоимость услуг'), ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="prices-hero-description mt-2 max-w-3xl text-[0.95rem] leading-relaxed text-[#0a293c] md:text-[1.02rem]"<?php echo $pricesHeroTextNode['attr']; ?>><?php echo htmlspecialchars((string)$pricesHeroTextNode['value'], ENT_QUOTES, 'UTF-8'); ?></p>
                <div class="prices-hero-badges mt-3.5 flex flex-wrap gap-2">
                    <span class="inline-flex rounded-full border border-[#c7ddf0] bg-white/75 px-3 py-1 text-[0.74rem] font-semibold text-[#0a293c]"<?php echo $pricesHeroBadgePrimaryNode['attr']; ?>><?php echo htmlspecialchars((string)$pricesHeroBadgePrimaryNode['value'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="inline-flex rounded-full border border-[#c7ddf0] bg-white/75 px-3 py-1 text-[0.74rem] font-semibold text-[#0a293c]"<?php echo $pricesHeroBadgeSecondaryNode['attr']; ?>><?php echo htmlspecialchars((string)$pricesHeroBadgeSecondaryNode['value'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>
        </div>

        <div class="prices-nav mb-6 p-3.5 md:p-4" data-admin-block-root>
            <h3 class="text-sm font-bold uppercase tracking-[0.1em] text-[#1977b2] mb-4"<?php echo $pricesQuickNavTitleNode['attr']; ?>><?php echo htmlspecialchars((string)$pricesQuickNavTitleNode['value'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($sectionsMeta as $sectionMeta): ?>
                    <?php
                    $sectionId = (string)$sectionMeta['id'];
                    $navLabelNode = bioinmed_page_text_node(
                        $pricesPage,
                        'prices',
                        'sections.' . $sectionId . '.nav_label',
                        (string)$sectionMeta['nav_label']
                    );
                    $navLinkClass = 'inline-flex items-center rounded-full border border-[#cfe0ef] bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.08em] text-[#0a293c] hover:border-[#8bb7dc] hover:text-[#1977b2]' . (!empty($sectionMeta['hidden']) ? ' price-nav-link-hidden' : '');
                    ?>
                    <a href="#<?php echo htmlspecialchars($sectionId, ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo htmlspecialchars($navLinkClass, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $navLabelNode['attr']; ?>><?php echo htmlspecialchars($navLabelNode['value'], ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="space-y-6" data-prices-page-root>
            <?php foreach ($sectionsMeta as $sectionMeta): ?>
                <?php
                $sectionId = (string)$sectionMeta['id'];
                $sectionRows = is_array($pricesRowsBySection[$sectionId] ?? null) ? $pricesRowsBySection[$sectionId] : [];
                $sectionHidden = !empty($sectionMeta['hidden']);
                $sectionTitleNode = bioinmed_page_text_node(
                    $pricesPage,
                    'prices',
                    'sections.' . $sectionId . '.title',
                    (string)$sectionMeta['title']
                );
                $sectionBadgeNode = bioinmed_page_text_node(
                    $pricesPage,
                    'prices',
                    'sections.' . $sectionId . '.badge',
                    (string)($sectionMeta['badge'] ?? '')
                );
                $sectionClasses = 'category-section' . ($sectionHidden ? ' price-section-hidden' : '');
                ?>
                <section id="<?php echo htmlspecialchars($sectionId, ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo htmlspecialchars($sectionClasses, ENT_QUOTES, 'UTF-8'); ?> price-admin-section-host" data-price-section-id="<?php echo htmlspecialchars($sectionId, ENT_QUOTES, 'UTF-8'); ?>" data-price-section-hidden="<?php echo $sectionHidden ? '1' : '0'; ?>" data-price-section-nav-label="<?php echo htmlspecialchars((string)$sectionMeta['nav_label'], ENT_QUOTES, 'UTF-8'); ?>" data-price-section-badge="<?php echo htmlspecialchars((string)$sectionBadgeNode['value'], ENT_QUOTES, 'UTF-8'); ?>" data-admin-disable-block-edit="1">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b-2 border-[#1977b2]" data-admin-disable-block-edit="1">
                        <h2 class="text-2xl font-bold text-[#1977b2]" data-price-section-title-view<?php echo $sectionTitleNode['attr']; ?>><?php echo htmlspecialchars((string)$sectionTitleNode['value'], ENT_QUOTES, 'UTF-8'); ?></h2>
                        <div class="price-admin-section-toolbar" data-admin-disable-block-edit="1">
                            <button type="button" class="price-admin-inline-btn" data-price-section-action="move-up" title="Поднять раздел выше"><span aria-hidden="true">↑</span><span>Выше</span></button>
                            <button type="button" class="price-admin-inline-btn" data-price-section-action="move-down" title="Опустить раздел ниже"><span aria-hidden="true">↓</span><span>Ниже</span></button>
                            <button type="button" class="price-admin-inline-btn" data-price-section-action="toggle-settings" title="Открыть редактирование раздела">Редактировать</button>
                            <button type="button" class="price-admin-inline-btn" data-price-section-action="add-row" title="Добавить цену в раздел">Добавить цену</button>
                            <button type="button" class="price-admin-inline-btn" data-price-section-action="add-section-below" title="Добавить новый раздел ниже">Добавить раздел ниже</button>
                            <button type="button" class="price-admin-inline-btn" data-price-section-action="toggle-hidden" title="Скрыть или показать раздел"><?php echo $sectionHidden ? 'Показать' : 'Скрыть'; ?></button>
                            <button type="button" class="price-admin-inline-btn price-admin-inline-btn-danger" data-price-section-action="delete-section" title="Удалить раздел">Удалить</button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-[#f0f7fc]">
                                    <th class="text-left px-4 py-3 font-semibold text-[#1977b2]">Наименование услуги</th>
                                    <th class="px-4 py-3 font-semibold text-[#1977b2] whitespace-nowrap">Длительность</th>
                                    <th class="text-right px-4 py-3 font-semibold text-[#1977b2] whitespace-nowrap">Цена, руб.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sectionRows as $rowIndex => $row): ?>
                                    <?php
                                    if (!is_array($row)) {
                                        continue;
                                    }

                                    $serviceId = trim((string)($row['service_id'] ?? ''));
                                    $serviceExists = $serviceId !== '' && isset($servicesById[$serviceId]);
                                    $serviceName = $serviceExists ? trim((string)($servicesById[$serviceId]['name'] ?? '')) : '';
                                    $rowTitle = trim((string)($row['title'] ?? $serviceName));
                                    if ($rowTitle === '') {
                                        continue;
                                    }

                                    $rowDescription = trim((string)($row['description'] ?? ''));
                                    $rowDuration = trim((string)($row['duration'] ?? ''));
                                    $rowPrice = trim((string)($row['price'] ?? ''));
                                    if ($rowPrice === '' && $serviceExists) {
                                        $rowPrice = $resolveServicePrice($serviceId);
                                    }
                                    if ($rowPrice === '') {
                                        continue;
                                    }

                                    $rowHidden = !empty($row['hidden']);
                                    $serviceResolvedPrice = $serviceExists ? $resolveServicePrice($serviceId) : '';
                                    $allowServiceLink = true;
                                    if (array_key_exists('link', $row)) {
                                        $allowServiceLink = (bool)$row['link'];
                                    } else {
                                        $allowServiceLink = $shouldLinkService($rowTitle, $serviceName, $rowPrice, $serviceResolvedPrice);
                                    }

                                    $rowClass = trim((string)($row['row_class'] ?? ''));
                                    $rowClasses = trim($rowClass . ($rowHidden ? ' price-row-hidden' : ''));
                                    $rowClassAttr = $rowClasses !== '' ? ' class="' . htmlspecialchars($rowClasses, ENT_QUOTES, 'UTF-8') . '"' : '';
                                    $serviceHref = $serviceExists ? '/services/' . rawurlencode($serviceId) : '';
                                    $rowTitleNode = bioinmed_page_text_node(
                                        $pricesPage,
                                        'prices',
                                        'sections.' . $sectionId . '.rows.' . $rowIndex . '.title',
                                        $rowTitle
                                    );
                                    $rowDescriptionNode = bioinmed_page_text_node(
                                        $pricesPage,
                                        'prices',
                                        'sections.' . $sectionId . '.rows.' . $rowIndex . '.description',
                                        $rowDescription
                                    );
                                    $rowDurationNode = bioinmed_page_text_node(
                                        $pricesPage,
                                        'prices',
                                        'sections.' . $sectionId . '.rows.' . $rowIndex . '.duration',
                                        $rowDuration
                                    );
                                    $rowPriceNode = bioinmed_page_text_node(
                                        $pricesPage,
                                        'prices',
                                        'sections.' . $sectionId . '.rows.' . $rowIndex . '.price',
                                        $rowPrice
                                    );
                                    $displayDuration = trim((string)$rowDurationNode['value']);
                                    if ($displayDuration === '') {
                                        $displayDuration = '—';
                                    }
                                    $displayPrice = trim((string)$rowPriceNode['value']);
                                    ?>
                                    <tr<?php echo $rowClassAttr; ?> data-price-row-index="<?php echo (int)$rowIndex; ?>" data-price-row-hidden="<?php echo $rowHidden ? '1' : '0'; ?>" data-price-row-title="<?php echo htmlspecialchars((string)$rowTitleNode['value'], ENT_QUOTES, 'UTF-8'); ?>" data-price-row-description="<?php echo htmlspecialchars((string)$rowDescriptionNode['value'], ENT_QUOTES, 'UTF-8'); ?>" data-price-row-duration="<?php echo htmlspecialchars((string)$rowDurationNode['value'], ENT_QUOTES, 'UTF-8'); ?>" data-price-row-price="<?php echo htmlspecialchars($displayPrice, ENT_QUOTES, 'UTF-8'); ?>" data-price-row-class="<?php echo htmlspecialchars($rowClass, ENT_QUOTES, 'UTF-8'); ?>" data-price-row-link="<?php echo $allowServiceLink ? '1' : '0'; ?>" data-admin-disable-block-edit="1">
                                        <td class="px-4 py-3 price-admin-row-host" data-service-id="<?php echo htmlspecialchars($serviceId, ENT_QUOTES, 'UTF-8'); ?>">
                                            <div class="price-admin-row-actions" data-admin-disable-block-edit="1">
                                                <button type="button" class="price-admin-inline-btn" data-price-row-action="move-up" title="Поднять строку выше"><span aria-hidden="true">↑</span><span>Выше</span></button>
                                                <button type="button" class="price-admin-inline-btn" data-price-row-action="move-down" title="Опустить строку ниже"><span aria-hidden="true">↓</span><span>Ниже</span></button>
                                                <button type="button" class="price-admin-inline-btn" data-price-row-action="add-after" title="Добавить цену ниже">Добавить цену ниже</button>
                                                <button type="button" class="price-admin-inline-btn" data-price-row-action="toggle-editor" title="Открыть редактирование строки">Редактировать</button>
                                                <button type="button" class="price-admin-inline-btn" data-price-row-action="toggle-hidden" title="Скрыть или показать"><?php echo $rowHidden ? 'Показать' : 'Скрыть'; ?></button>
                                                <button type="button" class="price-admin-inline-btn price-admin-inline-btn-danger" data-price-row-action="delete-row" title="Удалить цену">Удалить</button>
                                            </div>
                                            <?php if ((string)$rowDescriptionNode['value'] !== ''): ?>
                                                <div class="font-semibold text-[#0f2749]">
                                                    <?php if ($allowServiceLink && $serviceExists): ?>
                                                        <a class="price-service-link" data-price-row-title-view href="<?php echo htmlspecialchars($serviceHref, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $rowTitleNode['attr']; ?>><?php echo htmlspecialchars((string)$rowTitleNode['value'], ENT_QUOTES, 'UTF-8'); ?></a>
                                                    <?php else: ?>
                                                        <span data-price-row-title-view<?php echo $rowTitleNode['attr']; ?>><?php echo htmlspecialchars((string)$rowTitleNode['value'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <p class="text-sm text-[#0a293c] mt-1" data-price-row-description-view<?php echo $rowDescriptionNode['attr']; ?>><?php echo htmlspecialchars((string)$rowDescriptionNode['value'], ENT_QUOTES, 'UTF-8'); ?></p>
                                            <?php else: ?>
                                                <?php if ($allowServiceLink && $serviceExists): ?>
                                                    <a class="price-service-link" data-price-row-title-view href="<?php echo htmlspecialchars($serviceHref, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $rowTitleNode['attr']; ?>><?php echo htmlspecialchars((string)$rowTitleNode['value'], ENT_QUOTES, 'UTF-8'); ?></a>
                                                <?php else: ?>
                                                    <span data-price-row-title-view<?php echo $rowTitleNode['attr']; ?>><?php echo htmlspecialchars((string)$rowTitleNode['value'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                <?php endif; ?>
                                                <p class="text-sm text-[#0a293c] mt-1" data-price-row-description-view style="display:none"></p>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-[#0a293c]" data-price-row-duration-view<?php echo $rowDurationNode['attr']; ?>><?php echo htmlspecialchars($displayDuration, ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="px-4 py-3 text-right font-bold text-[#1977b2] whitespace-nowrap" data-price-row-price-view<?php echo $rowPriceNode['attr']; ?>><?php echo htmlspecialchars($displayPrice, ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>

        <div class="prices-cta mt-9 rounded-2xl border border-[#1977b2]/20 bg-gradient-to-r from-[#1977b2] to-[#1977b2] p-5 text-white shadow-[0_12px_28px_rgba(25,119,178,0.2)] md:p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                <div>
                    <h3 class="font-bold">Не уверены, какая услуга вам нужна?</h3>
                    <p class="text-[rgba(255,255,255,0.9)] mb-4">
                        Позвоните нам, и наши специалисты подберут оптимальный план лечения именно для вас.
                    </p>
                    <p class="text-sm text-[rgba(255,255,255,0.8)]">Персональный подход гарантирован!</p>
                </div>
                <div class="flex flex-col gap-3">
                    <a href="tel:<?php echo preg_replace('/\D/', '', CLINIC_PHONE); ?>" class="inline-flex items-center justify-center gap-2 rounded-full bg-white px-5 py-2.5 font-semibold text-[#1977b2] hover:bg-[#f0f7fc] transition-colors">
                        <i class="fas fa-phone"></i>
                        Позвонить: <?php echo CLINIC_PHONE; ?>
                    </a>
                    <button type="button" class="jsClientix_openWidget inline-flex items-center justify-center gap-2 rounded-full border border-white bg-[rgba(255,255,255,0.2)] px-5 py-2.5 font-semibold text-white hover:bg-[rgba(255,255,255,0.3)] transition-colors">
                        <i class="fas fa-calendar"></i>
                        Записаться онлайн
                    </button>
                </div>
            </div>
        </div>
    </main>

    <footer class="prices-print-footer" aria-hidden="true">
        <span><?php echo htmlspecialchars(rtrim(CLINIC_SITE_URL, '/'), ENT_QUOTES, 'UTF-8'); ?></span>
        <span>Прайс-лист от <?php echo htmlspecialchars(date('d.m.Y'), ENT_QUOTES, 'UTF-8'); ?></span>
    </footer>

    <section class="prices-signature-zone" aria-label="Подпись и печать генерального директора">
        <div class="prices-signature-card">
            <div class="prices-signature-role">Генеральный директор ООО «Клиника „БИОИНМЕД“»</div>
            <div class="prices-signature-fields">
                <div>
                    <span class="prices-signature-line"></span>
                    <span class="prices-signature-caption">подпись</span>
                </div>
                <div class="prices-signature-name">Костромина И.В.</div>
            </div>
            <div class="prices-signature-seal">М.П.</div>
        </div>
    </section>

    <?php
    $footer = new Footer($brand_colors);
    echo $footer->render();
    ?>

    <script>
        (function () {
            const toggle = document.getElementById('prices-print-mode-toggle');
            const pdfButton = document.getElementById('prices-save-pdf');
            const signatureToggle = document.getElementById('prices-signature-toggle');
            const exportMenu = document.getElementById('prices-export-menu');
            const exportToggle = document.getElementById('prices-export-toggle');
            const exportDropdown = document.getElementById('prices-export-dropdown');
            const storageKey = 'bioinmed:prices-print-mode';
            const signatureStorageKey = 'bioinmed:prices-signature';

            if (!toggle) {
                document.body.classList.remove('prices-print-mode');
                window.BioinmedDisableUis = false;
                if (typeof window.BioinmedLoadUis === 'function') window.BioinmedLoadUis();
                return;
            }

            function setPrintMode(enabled, persist = true) {
                document.body.classList.toggle('prices-print-mode', enabled);
                document.documentElement.classList.remove('prices-print-mode-pending');
                window.BioinmedDisableUis = enabled;
                if (toggle) {
                    toggle.checked = enabled;
                    toggle.setAttribute('aria-checked', enabled ? 'true' : 'false');
                }
                if (persist) {
                    try { localStorage.setItem(storageKey, enabled ? '1' : '0'); } catch (error) {}
                }
                if (!enabled && typeof window.BioinmedLoadUis === 'function') {
                    window.BioinmedLoadUis();
                }
            }

            function setSignature(enabled, persist = true) {
                document.body.classList.toggle('prices-show-signature', enabled);
                if (signatureToggle) signatureToggle.checked = enabled;
                if (persist) {
                    try { localStorage.setItem(signatureStorageKey, enabled ? '1' : '0'); } catch (error) {}
                }
            }

            let initialPrintMode = false;
            try { initialPrintMode = localStorage.getItem(storageKey) === '1'; } catch (error) {}
            setPrintMode(initialPrintMode, false);
            let initialSignature = false;
            try { initialSignature = localStorage.getItem(signatureStorageKey) === '1'; } catch (error) {}
            setSignature(initialSignature, false);

            if (toggle) {
                toggle.addEventListener('change', function () {
                    setPrintMode(toggle.checked);
                });
            }
            if (pdfButton) {
                pdfButton.addEventListener('click', function () {
                    setPrintMode(true);
                    requestAnimationFrame(function () { window.print(); });
                });
            }
            if (signatureToggle) {
                signatureToggle.addEventListener('change', function () {
                    setSignature(signatureToggle.checked);
                });
            }

            function setExportMenu(open) {
                if (!exportMenu || !exportToggle || !exportDropdown) return;
                exportMenu.classList.toggle('is-open', open);
                exportToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                exportDropdown.hidden = !open;
            }

            if (exportToggle) {
                exportToggle.addEventListener('click', function () {
                    setExportMenu(exportToggle.getAttribute('aria-expanded') !== 'true');
                });
                document.addEventListener('click', function (event) {
                    if (exportMenu && !exportMenu.contains(event.target)) setExportMenu(false);
                });
                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && exportToggle.getAttribute('aria-expanded') === 'true') {
                        setExportMenu(false);
                        exportToggle.focus();
                    }
                });
            }

            document.querySelectorAll('[data-prices-export-url]').forEach(function (button) {
                button.addEventListener('click', async function () {
                    if (button.disabled) return;
                    setExportMenu(false);
                    button.disabled = true;
                    button.setAttribute('aria-busy', 'true');

                    try {
                        const response = await fetch(button.dataset.pricesExportUrl, {
                            credentials: 'same-origin',
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        if (!response.ok) {
                            let message = 'Не удалось сформировать файл.';
                            try {
                                const payload = await response.json();
                                if (payload && payload.error) message = payload.error;
                            } catch (error) {}
                            throw new Error(message);
                        }

                        const blob = await response.blob();
                        if (!blob.size) throw new Error('Сервер вернул пустой файл.');
                        const objectUrl = URL.createObjectURL(blob);
                        const downloadLink = document.createElement('a');
                        downloadLink.href = objectUrl;
                        downloadLink.download = button.dataset.pricesExportFilename || 'prices.xlsx';
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        downloadLink.remove();
                        setTimeout(function () { URL.revokeObjectURL(objectUrl); }, 1000);
                    } catch (error) {
                        window.alert(error && error.message ? error.message : 'Не удалось скачать файл.');
                    } finally {
                        button.disabled = false;
                        button.removeAttribute('aria-busy');
                    }
                });
            });
        })();

        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>
</html>
