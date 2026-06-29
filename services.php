<?php
require_once __DIR__ . '/includes/pin_protection.php';
bioinmed_pin_require_access();


require_once 'config.php';
require_once 'includes/components/Components.php';

$servicesPage = bioinmed_read_json_file('pages/services.json');
$servicesMeta = is_array($servicesPage['meta'] ?? null) ? $servicesPage['meta'] : [];
$servicesPageLink = bioinmed_link('pages.services');

$siteUrl = rtrim(CLINIC_SITE_URL, '/');
$iconPath = CLINIC_ICON_PATH;
$iconUrl = $siteUrl . $iconPath;
$socialImageUrl = bioinmed_default_social_image_url();
$canonicalUrl = $siteUrl . '/services';
$pageTitle = trim((string)($servicesMeta['title'] ?? '')) . ' | ' . CLINIC_NAME;
$pageDescription = trim((string)($servicesMeta['description'] ?? ''));

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$categoryLabels = is_array($servicesPage['categories'] ?? null) ? $servicesPage['categories'] : [];

$categoryIcons = [
    'diagnostics' => 'fa-microscope',
    'musculoskeletal' => 'fa-bone',
    'manual_therapy' => 'fa-hand-holding-medical',
    'therapy' => 'fa-heart-pulse',
    'integrative' => 'fa-staff-snake',
    'chief_doctor' => 'fa-user-doctor',
    'psychology' => 'fa-brain',
    'osteopathy' => 'fa-hand-sparkles',
    'physiotherapy' => 'fa-wave-square',
    'reflexotherapy' => 'fa-bullseye',
    'infusion_therapy' => 'fa-droplet',
    'injection_therapy' => 'fa-syringe',
    'taping' => 'fa-bandage',
    'other' => 'fa-stethoscope',
];

$servicesByCategory = [];
foreach ($services as $service) {
    if (!is_array($service)) {
        continue;
    }
    $id = trim((string)($service['id'] ?? ''));
    $name = trim((string)($service['name'] ?? ''));
    if ($id === '' || $name === '') {
        continue;
    }

    $categoryKey = strtolower(trim((string)($service['category'] ?? 'other')));
    if ($categoryKey === '') {
        $categoryKey = 'other';
    }

    if (!isset($servicesByCategory[$categoryKey])) {
        $servicesByCategory[$categoryKey] = [];
    }
    $servicesByCategory[$categoryKey][] = $service;
}

$totalServices = 0;
foreach ($servicesByCategory as $groupItems) {
    $totalServices += count($groupItems);
}

$servicesListElements = [];
$position = 1;
foreach ($services as $service) {
    if (!is_array($service)) {
        continue;
    }
    $id = trim((string)($service['id'] ?? ''));
    $name = trim((string)($service['name'] ?? ''));
    if ($id === '' || $name === '') {
        continue;
    }
    $servicesListElements[] = [
        '@type' => 'ListItem',
        'position' => $position++,
        'url' => $siteUrl . '/services/' . rawurlencode($id),
        'name' => $name,
    ];
}

$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $pageTitle,
    'description' => $pageDescription,
    'url' => $canonicalUrl,
    'inLanguage' => 'ru-RU',
    'mainEntity' => [
        '@type' => 'ItemList',
        'itemListElement' => $servicesListElements,
    ],
];

$organizationStructuredData = bioinmed_medical_organization_schema();
$breadcrumbStructuredData = bioinmed_breadcrumb_schema([
    ['name' => (string)($servicesPage['breadcrumbs']['home'] ?? ''), 'url' => '/'],
    ['name' => (string)($servicesPage['breadcrumbs']['services'] ?? ''), 'url' => $servicesPageLink['url']],
]);
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?></title>
    <meta name="description" content="<?php echo e($pageDescription); ?>">
    <meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
    <link rel="canonical" href="<?php echo e($canonicalUrl); ?>">
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
        html {
            font-size: clamp(17px, 0.5vw + 15px, 19px);
        }

        body {
            line-height: 1.72;
        }

        .services-anchor {
            scroll-margin-top: 130px;
        }
        .service-card {
            transition: box-shadow 0.18s, border-color 0.18s, transform 0.18s;
        }
        .service-card:hover {
            box-shadow: 0 8px 28px rgba(10,43,80,0.11);
            border-color: #a8cde7;
            transform: translateY(-2px);
        }
        .service-card:hover .service-card-arrow {
            transform: translateX(3px);
        }
        .service-card-arrow {
            transition: transform 0.18s;
        }

        .services-anchor h2 {
            font-size: 1.45rem;
        }

        .services-anchor p,
        .service-card p {
            font-size: 0.95rem;
            line-height: 1.58;
        }

        .service-card h3 {
            font-size: 1.02rem;
            line-height: 1.25;
        }
    </style>
    <?php echo bioinmed_uis_counter_head(); ?>
</head>
<body class="bg-[#e4f1fa] text-[#0f2749] antialiased">
<?php
$header = new Header($brand_colors);
echo $header->render();
?>

    <main class="mx-auto max-w-6xl px-6 py-8 md:px-10 md:py-10">
    <section class="relative overflow-hidden rounded-2xl border border-[#d7e4ef] bg-[linear-gradient(120deg,#eef6fd_0%,#e4f1fb_45%,#dff0fb_100%)] p-5 shadow-[0_10px_24px_rgba(6,29,60,0.07)] md:p-7" data-admin-block-root>
        <div class="pointer-events-none absolute -right-16 -top-16 h-40 w-40 rounded-full bg-[#1977b21f] blur-3xl"></div>
        <div class="pointer-events-none absolute -left-14 bottom-0 h-32 w-32 rounded-full bg-[#1977b214] blur-3xl"></div>
        <div class="relative">
            <p class="text-[0.78rem] font-semibold uppercase tracking-[0.16em] text-[#1977b2]"<?php echo bioinmed_page_text_attr($servicesPage, 'services', 'meta.catalog_eyebrow'); ?>><?php echo e($servicesMeta['catalog_eyebrow'] ?? ''); ?></p>
            <h1 class="mt-2 text-[1.68rem] font-bold leading-tight text-[#0f2749] md:text-[2.15rem]"<?php echo bioinmed_page_text_attr($servicesPage, 'services', 'meta.catalog_heading'); ?>><?php echo e($servicesMeta['catalog_heading'] ?? ''); ?></h1>
            <p class="mt-2 max-w-3xl text-[0.97rem] leading-relaxed text-[#0a293c] md:text-[1.03rem]"<?php echo bioinmed_page_text_attr($servicesPage, 'services', 'meta.catalog_text'); ?>>
                <?php echo e($servicesMeta['catalog_text'] ?? ''); ?>
            </p>
            <div class="mt-4 inline-flex items-center gap-2 rounded-full border border-[#c7ddf0] bg-white/80 px-3 py-1.5 text-[0.82rem] font-semibold text-[#0a293c]">
                <i class="fa-solid fa-list-check text-[#1977b2]" aria-hidden="true"></i>
                <span<?php echo bioinmed_page_text_attr($servicesPage, 'services', 'meta.total_services_label'); ?>><?php echo e($servicesMeta['total_services_label'] ?? ''); ?></span> <?php echo intval($totalServices); ?>
            </div>
        </div>
    </section>

    <?php if (!empty($servicesByCategory)): ?>
        <section class="mt-5 px-0 py-2" data-admin-block-root>
            <p class="mb-3 text-[0.78rem] font-semibold uppercase tracking-[0.12em] text-[#1977b2]"<?php echo bioinmed_page_text_attr($servicesPage, 'services', 'meta.quick_nav_label'); ?>><?php echo e($servicesMeta['quick_nav_label'] ?? ''); ?></p>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($servicesByCategory as $categoryKey => $categoryItems): ?>
                    <?php
                    $categoryTitle = $categoryLabels[$categoryKey] ?? ucfirst(str_replace(['_', '-'], ' ', $categoryKey));
                    $count = count($categoryItems);
                    $categoryTitleNode = bioinmed_page_text_node($servicesPage, 'services', 'categories.' . $categoryKey, $categoryTitle);
                    ?>
                    <a href="#cat-<?php echo e($categoryKey); ?>" class="inline-flex items-center gap-2 rounded-full border border-[#cfe0ef] bg-white px-3 py-1.5 text-[0.8rem] font-semibold text-[#0a293c] transition hover:border-[#8bb7dc] hover:text-[#1977b2]">
                        <span<?php echo $categoryTitleNode['attr']; ?>><?php echo e($categoryTitleNode['value']); ?></span>
                        <span class="rounded-full bg-white px-1.5 py-0.5 text-[0.72rem] text-[#1977b2]"><?php echo intval($count); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="mt-6 space-y-5">
            <?php foreach ($servicesByCategory as $categoryKey => $categoryItems): ?>
                <?php
                $categoryTitle = $categoryLabels[$categoryKey] ?? ucfirst(str_replace(['_', '-'], ' ', $categoryKey));
                $categoryIcon = $categoryIcons[$categoryKey] ?? 'fa-stethoscope';
                $categoryHeaderNode = bioinmed_page_text_node($servicesPage, 'services', 'categories.' . $categoryKey, $categoryTitle);
                ?>
                <section id="cat-<?php echo e($categoryKey); ?>" class="services-anchor pt-2 md:pt-3">
                    <div class="mb-4 flex items-center gap-2.5 border-b border-[#e6eef7] pb-3" data-admin-block-root>
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#e8f4fd] text-[#1977b2]">
                            <i class="fa-solid <?php echo e($categoryIcon); ?> text-[0.82rem]" aria-hidden="true"></i>
                        </span>
                        <h2 class="text-[1.18rem] font-bold leading-tight text-[#0f2749] md:text-[1.42rem]"<?php echo $categoryHeaderNode['attr']; ?>><?php echo e($categoryHeaderNode['value']); ?></h2>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <?php foreach ($categoryItems as $service): ?>
                            <?php
                            $serviceId = trim((string)($service['id'] ?? ''));
                            $serviceName = trim((string)($service['name'] ?? ''));
                            $servicePrice = trim((string)($service['price'] ?? ''));
                            $servicePriceNote = trim((string)($service['price_note'] ?? ''));
                            $priceLabel = $servicePrice !== '' && $servicePrice !== 'По запросу'
                                ? trim($servicePrice . ($servicePriceNote !== '' ? ' ' . $servicePriceNote : ''))
                                : '';
                            $serviceNameNode = bioinmed_page_text_node($servicesPage, 'services', 'catalog.items.' . $serviceId . '.name', $serviceName);
                            $servicePriceLabelNode = bioinmed_page_text_node($servicesPage, 'services', 'catalog.items.' . $serviceId . '.price_label', $priceLabel);
                            $servicePriceOnRequestNode = bioinmed_page_text_node($servicesPage, 'services', 'meta.price_on_request', (string)($servicesMeta['price_on_request'] ?? ''));
                            ?>
                                     <a href="/services/<?php echo e($serviceId); ?>"
                                         class="service-card group flex flex-col rounded-xl border border-[#deebf6] bg-white p-4 no-underline cursor-pointer" data-admin-block-root>
                                <div class="flex items-start justify-between gap-2">
                                    <h3 class="text-[0.93rem] font-semibold leading-snug text-[#0a293c] group-hover:text-[#1977b2]"
                                        style="transition:color 0.15s"<?php echo $serviceNameNode['attr']; ?>><?php echo e($serviceNameNode['value']); ?></h3>
                                    <i class="service-card-arrow fa-solid fa-arrow-right mt-0.5 shrink-0 text-[0.65rem] text-[#9bbdd8]" aria-hidden="true"></i>
                                </div>
                                <div class="mt-auto pt-3">
                                    <?php if ($priceLabel !== ''): ?>
                                        <span class="inline-block rounded-full bg-[#e9f6ff] px-2.5 py-0.5 text-[0.78rem] font-semibold text-[#1a7dbf]"<?php echo $servicePriceLabelNode['attr']; ?>><?php echo e($servicePriceLabelNode['value']); ?></span>
                                    <?php else: ?>
                                        <span class="inline-block rounded-full bg-[#f3f6fb] px-2.5 py-0.5 text-[0.78rem] font-medium text-[#7093b8]"<?php echo $servicePriceOnRequestNode['attr']; ?>><?php echo e($servicePriceOnRequestNode['value']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <section class="mt-6 rounded-2xl border border-[#dce8f5] bg-[#e4f1fa] p-6 text-center">
            <h2 class="text-[1.15rem] font-bold text-[#0f2749]"<?php echo bioinmed_page_text_attr($servicesPage, 'services', 'meta.empty_title'); ?>><?php echo e($servicesMeta['empty_title'] ?? ''); ?></h2>
            <p class="mt-2 text-[0.9rem] text-[#0a293c]"<?php echo bioinmed_page_text_attr($servicesPage, 'services', 'meta.empty_text'); ?>><?php echo e($servicesMeta['empty_text'] ?? ''); ?></p>
            <a href="<?php echo e(bioinmed_link('nav.prices')['url']); ?>" class="mt-4 inline-flex items-center rounded-lg bg-[#1977b2] px-4 py-2 text-[0.82rem] font-semibold text-white hover:bg-[#16658f]"<?php echo bioinmed_page_text_attr($servicesPage, 'services', 'meta.empty_button'); ?>><?php echo e($servicesMeta['empty_button'] ?? ''); ?></a>
        </section>
    <?php endif; ?>
</main>

<?php
$contactSection = new ContactSection($brand_colors);
echo $contactSection->render();

$footer = new Footer($brand_colors);
echo $footer->render();
?>
</body>
</html>
