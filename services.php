<?php
// PIN-защита сайта
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Проверяем, предоставлен ли доступ через splash.php
if (!isset($_SESSION['site_access_granted'])) {
    // Проверяем переменную окружения для отключения PIN-защиты
    // По умолчанию защита ВКЛЮЧЕНА, отключается только при PIN_PROTECTION_ENABLED=0
    $pin_protection_disabled = in_array(getenv('PIN_PROTECTION_ENABLED'), ['0', 'false'], true);
    if (!$pin_protection_disabled) {
        // PIN-защита включена, перенаправляем на splash.php
        header('Location: /splash.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

require_once 'config.php';
require_once 'includes/components/Components.php';

$siteUrl = rtrim(CLINIC_SITE_URL, '/');
$iconPath = CLINIC_ICON_PATH;
$iconUrl = $siteUrl . $iconPath;
$socialImageUrl = bioinmed_default_social_image_url();
$canonicalUrl = $siteUrl . '/services';
$pageTitle = 'Все услуги клиники | ' . CLINIC_NAME;
$pageDescription = 'Полный каталог услуг клиники БИОИНМЕД с переходом на детальные страницы: диагностика, остеопатия, рефлексотерапия, физиотерапия и другие направления.';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$categoryLabels = [
    'diagnostics' => 'Диагностика',
    'musculoskeletal' => 'Опорно-двигательный аппарат',
    'manual_therapy' => 'Остеопатия и мануальные методики',
    'therapy' => 'Терапевтические программы',
    'integrative' => 'Интегративное сопровождение',
    'chief_doctor' => 'Приём главного врача',
    'psychology' => 'Психология',
    'osteopathy' => 'Остеопатия',
    'physiotherapy' => 'Физиотерапия',
    'reflexotherapy' => 'Рефлексотерапия',
    'infusion_therapy' => 'Инфузионная терапия',
    'ozone_therapy' => 'Озонотерапия',
    'injection_therapy' => 'Инъекционная терапия',
    'taping' => 'Тейпирование и банки',
    'other' => 'Другие услуги',
];

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
    ['name' => 'Главная', 'url' => '/'],
    ['name' => 'Услуги', 'url' => '/services'],
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
    <meta name="theme-color" content="#2fbdef">
    <?php echo bioinmed_render_social_meta($pageTitle, $pageDescription, $canonicalUrl, [
        'image' => $socialImageUrl,
    ]); ?>
    <?php echo bioinmed_render_favicon_links($iconPath); ?>
    <script type="application/ld+json"><?php echo json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script type="application/ld+json"><?php echo json_encode($organizationStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script type="application/ld+json"><?php echo json_encode($breadcrumbStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        html {
            font-size: clamp(16px, 0.4vw + 14px, 18px);
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
<body class="bg-[linear-gradient(to_bottom,#f9fcff_0%,#f3f8fd_45%,#eef4fb_100%)] text-[#0f2749] antialiased">
<?php
$header = new Header($brand_colors);
echo $header->render();
?>

    <main class="mx-auto max-w-6xl px-6 py-8 md:px-10 md:py-10">
    <section class="relative overflow-hidden rounded-2xl border border-[#d7e4ef] bg-[linear-gradient(120deg,#eef6fd_0%,#e4f1fb_45%,#dff0fb_100%)] p-5 shadow-[0_10px_24px_rgba(6,29,60,0.07)] md:p-7">
        <div class="pointer-events-none absolute -right-16 -top-16 h-40 w-40 rounded-full bg-[#2fbdef1f] blur-3xl"></div>
        <div class="pointer-events-none absolute -left-14 bottom-0 h-32 w-32 rounded-full bg-[#2fbdef14] blur-3xl"></div>
        <div class="relative">
            <p class="text-[0.78rem] font-semibold uppercase tracking-[0.16em] text-[#2fbdef]">Каталог услуг</p>
            <h1 class="mt-2 text-[1.68rem] font-bold leading-tight text-[#0f2749] md:text-[2.15rem]">Все услуги клиники БИОИНМЕД</h1>
            <p class="mt-2 max-w-3xl text-[0.97rem] leading-relaxed text-[#214a7f] md:text-[1.03rem]">
                Выберите направление и перейдите на подробную страницу услуги: описание, показания, цена и запись на приём.
            </p>
            <div class="mt-4 inline-flex items-center gap-2 rounded-full border border-[#c7ddf0] bg-white/80 px-3 py-1.5 text-[0.82rem] font-semibold text-[#2a5a94]">
                <i class="fa-solid fa-list-check text-[#2fbdef]" aria-hidden="true"></i>
                Всего услуг: <?php echo intval($totalServices); ?>
            </div>
        </div>
    </section>

    <?php if (!empty($servicesByCategory)): ?>
        <section class="mt-5 rounded-2xl border border-[#dce8f5] bg-white p-3.5 md:p-4">
            <p class="mb-3 text-[0.78rem] font-semibold uppercase tracking-[0.12em] text-[#2fbdef]">Быстрый переход по направлениям</p>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($servicesByCategory as $categoryKey => $categoryItems): ?>
                    <?php
                    $categoryTitle = $categoryLabels[$categoryKey] ?? ucfirst(str_replace(['_', '-'], ' ', $categoryKey));
                    $count = count($categoryItems);
                    ?>
                    <a href="#cat-<?php echo e($categoryKey); ?>" class="inline-flex items-center gap-2 rounded-full border border-[#cfe0ef] bg-[#f8fcff] px-3 py-1.5 text-[0.8rem] font-semibold text-[#2a5a94] transition hover:border-[#8bb7dc] hover:text-[#2fbdef]">
                        <span><?php echo e($categoryTitle); ?></span>
                        <span class="rounded-full bg-white px-1.5 py-0.5 text-[0.72rem] text-[#2fbdef]"><?php echo intval($count); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="mt-6 space-y-5">
            <?php foreach ($servicesByCategory as $categoryKey => $categoryItems): ?>
                <?php
                $categoryTitle = $categoryLabels[$categoryKey] ?? ucfirst(str_replace(['_', '-'], ' ', $categoryKey));
                $categoryIcon = $categoryIcons[$categoryKey] ?? 'fa-stethoscope';
                ?>
                <section id="cat-<?php echo e($categoryKey); ?>" class="services-anchor rounded-2xl border border-[#dce8f5] bg-white p-4 shadow-[0_6px_16px_rgba(10,43,80,0.05)] md:p-5">
                    <div class="mb-4 flex items-center gap-2.5 border-b border-[#e6eef7] pb-3">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#e8f4fd] text-[#2fbdef]">
                            <i class="fa-solid <?php echo e($categoryIcon); ?> text-[0.82rem]" aria-hidden="true"></i>
                        </span>
                        <h2 class="text-[1.18rem] font-bold leading-tight text-[#0f2749] md:text-[1.42rem]"><?php echo e($categoryTitle); ?></h2>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <?php foreach ($categoryItems as $service): ?>
                            <?php
                            $serviceId = trim((string)($service['id'] ?? ''));
                            $serviceName = trim((string)($service['name'] ?? ''));
                            $serviceDesc = trim((string)($service['description'] ?? ''));
                            $servicePrice = trim((string)($service['price'] ?? ''));
                            $servicePriceNote = trim((string)($service['price_note'] ?? ''));
                            $priceLabel = $servicePrice !== '' && $servicePrice !== 'По запросу'
                                ? trim($servicePrice . ($servicePriceNote !== '' ? ' ' . $servicePriceNote : ''))
                                : '';
                            ?>
                            <a href="/services/<?php echo e($serviceId); ?>"
                               class="service-card group flex flex-col rounded-xl border border-[#deebf6] bg-white p-4 no-underline cursor-pointer">
                                <div class="flex items-start justify-between gap-2">
                                    <h3 class="text-[0.93rem] font-semibold leading-snug text-[#173b64] group-hover:text-[#2fbdef]"
                                        style="transition:color 0.15s"><?php echo e($serviceName); ?></h3>
                                    <i class="service-card-arrow fa-solid fa-arrow-right mt-0.5 shrink-0 text-[0.65rem] text-[#9bbdd8]" aria-hidden="true"></i>
                                </div>
                                <?php if ($serviceDesc !== ''): ?>
                                    <p class="mt-2 line-clamp-2 text-[0.9rem] leading-relaxed text-[#4a6e99]"><?php echo e($serviceDesc); ?></p>
                                <?php endif; ?>
                                <div class="mt-auto pt-3">
                                    <?php if ($priceLabel !== ''): ?>
                                        <span class="inline-block rounded-full bg-[#e9f6ff] px-2.5 py-0.5 text-[0.78rem] font-semibold text-[#1a7dbf]"><?php echo e($priceLabel); ?></span>
                                    <?php else: ?>
                                        <span class="inline-block rounded-full bg-[#f3f6fb] px-2.5 py-0.5 text-[0.78rem] font-medium text-[#7093b8]">По запросу</span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <section class="mt-6 rounded-2xl border border-[#dce8f5] bg-white p-6 text-center">
            <h2 class="text-[1.15rem] font-bold text-[#0f2749]">Раздел услуг временно недоступен</h2>
            <p class="mt-2 text-[0.9rem] text-[#355b89]">Пожалуйста, обновите страницу позже или перейдите в прайс-лист.</p>
            <a href="/prices" class="mt-4 inline-flex items-center rounded-lg bg-[#2fbdef] px-4 py-2 text-[0.82rem] font-semibold text-white hover:bg-[#1fb3d8]">Перейти к ценам</a>
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
