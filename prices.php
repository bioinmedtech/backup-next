<?php
require_once __DIR__ . '/includes/pin_protection.php';
bioinmed_pin_require_access();

require_once 'config.php';
require_once 'includes/components/Components.php';

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
    ];
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
    <?php echo bioinmed_render_public_head_assets(); ?>
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
        .category-section td:last-child, .category-section th:last-child { font-size: 0.9rem; white-space: nowrap; }
        .prices-hero h1 { font-size: 1.76rem; line-height: 1.14; }
        .prices-hero p { font-size: 0.98rem; line-height: 1.5; }
        .prices-nav h3 { font-size: 0.82rem; margin-bottom: 0.65rem; }
        .prices-nav a { font-size: 0.8rem; padding: 0.42rem 0.78rem; }
        .category-section tbody tr:last-child td { border-bottom: none; }
        .prices-cta h3 { font-size: 1.42rem; line-height: 1.2; margin-bottom: 0.55rem; }
        .prices-cta p { font-size: 0.96rem; line-height: 1.5; }
        .prices-cta a { font-size: 0.94rem; }
        .price-service-link { color: #0a293c; text-decoration: underline; text-decoration-color: rgba(36, 140, 255, 0.45); text-underline-offset: 2px; transition: color .2s ease, text-decoration-color .2s ease; }
        .price-service-link:hover { color: #1977b2; text-decoration-color: rgba(36, 140, 255, 0.95); }
        @media (min-width: 768px) { .prices-hero h1 { font-size: 2.15rem; } }
        @media (max-width: 767px) {
            .category-section { padding: 0.78rem; }
            .category-section h2 { font-size: 1.24rem; }
            .prices-hero h1 { font-size: 1.55rem; }
        }
    </style>
    <?php echo bioinmed_uis_counter_head(); ?>
</head>
<body class="bg-[#e4f1fa] text-[#0f2749] antialiased">
    <?php echo bioinmed_yandex_metrika_noscript(); ?>
<?php
$header = new Header($brand_colors);
    echo $header->render();
    ?>

    <main class="mx-auto max-w-6xl px-6 py-8 md:px-10 md:py-10">
        <div class="prices-hero mb-6 p-5 md:p-6">
            <div class="absolute -right-14 -top-14 h-36 w-36 rounded-full bg-[#1977b21f] blur-2xl"></div>
            <div class="absolute -left-12 bottom-0 h-28 w-28 rounded-full bg-[#1977b214] blur-2xl"></div>
            <div class="relative" data-admin-block-root>
                <p class="text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-[#1977b2]"<?php echo bioinmed_page_text_attr($pricesPage, 'prices', 'meta.hero_eyebrow'); ?>><?php echo htmlspecialchars((string)($pricesMeta['hero_eyebrow'] ?? 'Прайс-лист'), ENT_QUOTES, 'UTF-8'); ?></p>
                <h1 class="mt-2 font-bold text-[#0f2749]"<?php echo bioinmed_page_text_attr($pricesPage, 'prices', 'meta.hero_title'); ?>><?php echo htmlspecialchars((string)($pricesMeta['hero_title'] ?? 'Стоимость услуг'), ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="mt-2 max-w-3xl text-[0.95rem] leading-relaxed text-[#0a293c] md:text-[1.02rem]"<?php echo $pricesHeroTextNode['attr']; ?>><?php echo htmlspecialchars((string)$pricesHeroTextNode['value'], ENT_QUOTES, 'UTF-8'); ?></p>
                <div class="mt-3.5 flex flex-wrap gap-2">
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
                    ?>
                    <a href="#<?php echo htmlspecialchars($sectionId, ENT_QUOTES, 'UTF-8'); ?>" class="inline-flex items-center rounded-full border border-[#cfe0ef] bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.08em] text-[#0a293c] hover:border-[#8bb7dc] hover:text-[#1977b2]"<?php echo $navLabelNode['attr']; ?>><?php echo htmlspecialchars($navLabelNode['value'], ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="space-y-6">
            <?php foreach ($sectionsMeta as $sectionMeta): ?>
                <?php
                $sectionId = (string)$sectionMeta['id'];
                $sectionRows = is_array($pricesRowsBySection[$sectionId] ?? null) ? $pricesRowsBySection[$sectionId] : [];
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
                ?>
                <section id="<?php echo htmlspecialchars($sectionId, ENT_QUOTES, 'UTF-8'); ?>" class="category-section">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b-2 border-[#1977b2]" data-admin-block-root>
                        <h2 class="text-2xl font-bold text-[#1977b2]"<?php echo $sectionTitleNode['attr']; ?>><?php echo htmlspecialchars((string)$sectionTitleNode['value'], ENT_QUOTES, 'UTF-8'); ?></h2>
                        <?php if ((string)$sectionBadgeNode['value'] !== ''): ?>
                            <span class="inline-flex items-center rounded-full bg-[#1977b2] px-2.5 py-1 text-[0.66rem] font-semibold uppercase tracking-[0.08em] text-white"<?php echo $sectionBadgeNode['attr']; ?>><?php echo htmlspecialchars((string)$sectionBadgeNode['value'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-[#f0f7fc]">
                                    <th class="text-left px-4 py-3 font-semibold text-[#1977b2]">Услуга</th>
                                    <th class="text-right px-4 py-3 font-semibold text-[#1977b2] whitespace-nowrap">Цена</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sectionRows as $rowIndex => $row): ?>
                                    <?php
                                    if (!is_array($row)) {
                                        continue;
                                    }

                                    $serviceId = trim((string)($row['service_id'] ?? ''));
                                    if ($serviceId === '' || !isset($servicesById[$serviceId])) {
                                        continue;
                                    }

                                    $serviceName = trim((string)($servicesById[$serviceId]['name'] ?? ''));
                                    $rowTitle = trim((string)($row['title'] ?? $serviceName));
                                    if ($rowTitle === '') {
                                        continue;
                                    }

                                    $rowDescription = trim((string)($row['description'] ?? ''));
                                    $rowPrice = trim((string)($row['price'] ?? ''));
                                    if ($rowPrice === '') {
                                        $rowPrice = $resolveServicePrice($serviceId);
                                    }
                                    if ($rowPrice === '') {
                                        continue;
                                    }

                                    $rowClass = trim((string)($row['row_class'] ?? ''));
                                    $rowClassAttr = $rowClass !== '' ? ' class="' . htmlspecialchars($rowClass, ENT_QUOTES, 'UTF-8') . '"' : '';
                                    $serviceHref = '/services/' . rawurlencode($serviceId);
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
                                    $rowPriceNode = bioinmed_page_text_node(
                                        $pricesPage,
                                        'prices',
                                        'sections.' . $sectionId . '.rows.' . $rowIndex . '.price',
                                        $rowPrice
                                    );
                                    ?>
                                    <tr<?php echo $rowClassAttr; ?> data-admin-block-root>
                                        <td class="px-4 py-3" data-service-id="<?php echo htmlspecialchars($serviceId, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php if ((string)$rowDescriptionNode['value'] !== ''): ?>
                                                <div class="font-semibold text-[#0f2749]"><a class="price-service-link" href="<?php echo htmlspecialchars($serviceHref, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $rowTitleNode['attr']; ?>><?php echo htmlspecialchars((string)$rowTitleNode['value'], ENT_QUOTES, 'UTF-8'); ?></a></div>
                                                <p class="text-sm text-[#0a293c] mt-1"<?php echo $rowDescriptionNode['attr']; ?>><?php echo htmlspecialchars((string)$rowDescriptionNode['value'], ENT_QUOTES, 'UTF-8'); ?></p>
                                            <?php else: ?>
                                                <a class="price-service-link" href="<?php echo htmlspecialchars($serviceHref, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $rowTitleNode['attr']; ?>><?php echo htmlspecialchars((string)$rowTitleNode['value'], ENT_QUOTES, 'UTF-8'); ?></a>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-right font-bold text-[#1977b2] whitespace-nowrap"<?php echo $rowPriceNode['attr']; ?>><?php echo htmlspecialchars((string)$rowPriceNode['value'], ENT_QUOTES, 'UTF-8'); ?></td>
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

    <?php
    $footer = new Footer($brand_colors);
    echo $footer->render();
    ?>

    <script>
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
