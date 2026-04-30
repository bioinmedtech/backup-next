<?php
require_once 'config.php';
require_once 'includes/components/Components.php';

$siteUrl = rtrim(CLINIC_SITE_URL, '/');
$iconPath = CLINIC_ICON_PATH;
$iconUrl = $siteUrl . $iconPath;
$socialImageUrl = bioinmed_default_social_image_url();
$canonicalUrl = $siteUrl . '/prices';
$pageTitle = 'Прайс-лист услуг и цены | ' . CLINIC_NAME;
$pageDescription = 'Полный прайс-лист с ценами на все услуги клиники БИОИНМЕД. Диагностика, остеопатия, рефлексотерапия, физиотерапия и другие методики.';
$serviceIds = [];
foreach ($services as $serviceItem) {
    $id = trim((string)($serviceItem['id'] ?? ''));
    if ($id !== '') {
        $serviceIds[$id] = true;
    }
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
    ['name' => 'Прайс-лист', 'url' => '/prices'],
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
    <meta name="theme-color" content="#2fbdef">
    <meta property="og:locale" content="ru_RU">
    <meta property="og:site_name" content="<?php echo htmlspecialchars(CLINIC_NAME, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image" content="<?php echo $socialImageUrl; ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:image" content="<?php echo $socialImageUrl; ?>">
    <link rel="icon" type="image/png" href="<?php echo $iconPath; ?>">
    <link rel="apple-touch-icon" href="<?php echo $iconPath; ?>">
    <script type="application/ld+json"><?php echo json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script type="application/ld+json"><?php echo json_encode($organizationStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script type="application/ld+json"><?php echo json_encode($breadcrumbStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .category-section {
            scroll-margin-top: 120px;
        }

        .prices-hero {
            position: relative;
            overflow: hidden;
            border: 1px solid #d7e4ef;
            border-radius: 1rem;
            background: linear-gradient(120deg, #eef6fd 0%, #e4f1fb 45%, #dff0fb 100%);
            box-shadow: 0 10px 24px rgba(6, 29, 60, 0.07);
        }

        .prices-nav {
            position: static;
            border: 1px solid #dce8f5;
            border-radius: 0.8rem;
            background: rgba(248, 252, 255, 0.95);
            backdrop-filter: blur(6px);
            box-shadow: 0 6px 16px rgba(10, 43, 80, 0.06);
        }

        .category-section {
            border: 1px solid #dce8f5;
            border-radius: 0.9rem;
            background: #ffffff;
            padding: 0.95rem;
            box-shadow: 0 6px 16px rgba(10, 43, 80, 0.05);
        }

        .category-section table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            overflow: hidden;
            border-radius: 0.85rem;
            border: 1px solid #e1edf8;
        }

        .category-section thead th {
            background: #eef6fd;
        }

        .category-section tbody tr:hover {
            background: #f3faff;
        }

        .category-section td,
        .category-section th {
            border-bottom: 1px solid #e9f2fb;
            padding: 0.62rem 0.82rem;
            font-size: 0.88rem;
            line-height: 1.35;
        }

        .category-section h2 {
            font-size: 1.36rem;
            line-height: 1.2;
        }

        .category-section > div:first-child {
            margin-bottom: 0.95rem;
            padding-bottom: 0.62rem;
        }

        .category-section td p {
            font-size: 0.8rem;
            line-height: 1.35;
            margin-top: 0.3rem;
        }

        .category-section td div.font-semibold {
            font-size: 0.9rem;
            line-height: 1.3;
        }

        .category-section td:last-child,
        .category-section th:last-child {
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .prices-hero h1 {
            font-size: 1.58rem;
            line-height: 1.16;
        }

        .prices-hero p {
            font-size: 0.88rem;
            line-height: 1.45;
        }

        .prices-nav h3 {
            font-size: 0.73rem;
            margin-bottom: 0.65rem;
        }

        .prices-nav a {
            font-size: 0.71rem;
            padding: 0.36rem 0.72rem;
        }

        .category-section tbody tr:last-child td {
            border-bottom: none;
        }

        .prices-cta h3 {
            font-size: 1.28rem;
            line-height: 1.2;
            margin-bottom: 0.55rem;
        }

        .prices-cta p {
            font-size: 0.86rem;
            line-height: 1.42;
        }

        .prices-cta a {
            font-size: 0.86rem;
        }

        .price-service-link {
            color: #0f3463;
            text-decoration: underline;
            text-decoration-color: rgba(47, 189, 239, 0.45);
            text-underline-offset: 2px;
            transition: color .2s ease, text-decoration-color .2s ease;
        }

        .price-service-link:hover {
            color: #2fbdef;
            text-decoration-color: rgba(47, 189, 239, 0.95);
        }

        @media (min-width: 768px) {
            .prices-hero h1 {
                font-size: 1.95rem;
            }
        }

        @media (max-width: 767px) {
            .category-section {
                padding: 0.78rem;
            }

            .category-section h2 {
                font-size: 1.14rem;
            }

            .prices-hero h1 {
                font-size: 1.36rem;
            }
        }
    </style>
</head>
<body class="bg-[linear-gradient(to_bottom,#f9fcff_0%,#f3f8fd_45%,#eef4fb_100%)] text-[#0f2749] antialiased">
    <?php
    $header = new Header($brand_colors);
    echo $header->render();
    ?>

    <!-- Main Content -->
    <main class="mx-auto max-w-6xl px-6 py-8 md:px-10 md:py-10">
        <!-- Заголовок -->
        <div class="prices-hero mb-6 p-5 md:p-6">
            <div class="absolute -right-14 -top-14 h-36 w-36 rounded-full bg-[#2fbdef1f] blur-2xl"></div>
            <div class="absolute -left-12 bottom-0 h-28 w-28 rounded-full bg-[#2fbdef14] blur-2xl"></div>
            <div class="relative">
                <p class="text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-[#2fbdef]">Стоимость услуг</p>
                <h1 class="mt-2 font-bold text-[#0f2749]">Прайс-лист клиники</h1>
                <p class="mt-2 max-w-3xl text-[0.95rem] leading-relaxed text-[#214a7f] md:text-[1.02rem]">
                    Актуальные цены по направлениям лечения: от первичной консультации до комплексных программ восстановления. Поможем подобрать специалиста и оптимальный формат терапии.
                </p>
                <div class="mt-3.5 flex flex-wrap gap-2">
                    <span class="inline-flex rounded-full border border-[#c7ddf0] bg-white/75 px-3 py-1 text-[0.74rem] font-semibold text-[#2a5a94]">Актуальные цены</span>
                    <span class="inline-flex rounded-full border border-[#c7ddf0] bg-white/75 px-3 py-1 text-[0.74rem] font-semibold text-[#2a5a94]">Ежедневно 9:00-21:00</span>
                </div>
            </div>
        </div>

        <!-- Навигация по разделам -->
        <div class="prices-nav mb-6 p-3.5 md:p-4">
            <h3 class="text-sm font-bold uppercase tracking-[0.1em] text-[#2fbdef] mb-4">Быстрая навигация:</h3>
            <div class="flex flex-wrap gap-2">
                <a href="#chief-doctor" class="inline-flex items-center rounded-full border border-[#cfe0ef] bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94] hover:border-[#8bb7dc] hover:text-[#2fbdef]">Приём главного врача</a>
                <a href="#psychology" class="inline-flex items-center rounded-full border border-[#cfe0ef] bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94] hover:border-[#8bb7dc] hover:text-[#2fbdef]">Психология</a>
                <a href="#osteopathy" class="inline-flex items-center rounded-full border border-[#cfe0ef] bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94] hover:border-[#8bb7dc] hover:text-[#2fbdef]">Остеопатия</a>
                <a href="#infusion" class="inline-flex items-center rounded-full border border-[#cfe0ef] bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94] hover:border-[#8bb7dc] hover:text-[#2fbdef]">Инфузионная терапия</a>
                <a href="#injection" class="inline-flex items-center rounded-full border border-[#cfe0ef] bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94] hover:border-[#8bb7dc] hover:text-[#2fbdef]">Инъекционная терапия</a>
                <a href="#reflexotherapy" class="inline-flex items-center rounded-full border border-[#cfe0ef] bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94] hover:border-[#8bb7dc] hover:text-[#2fbdef]">Рефлексотерапия</a>
                <a href="#physiotherapy" class="inline-flex items-center rounded-full border border-[#cfe0ef] bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94] hover:border-[#8bb7dc] hover:text-[#2fbdef]">Физиотерапия</a>
                <a href="#taping" class="inline-flex items-center rounded-full border border-[#cfe0ef] bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94] hover:border-[#8bb7dc] hover:text-[#2fbdef]">Тейпирование и банки</a>
            </div>
        </div>

        <!-- Категории услуг -->
        <div class="space-y-6">
            <!-- Приём главного врача -->
            <section id="chief-doctor" class="category-section">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b-2 border-[#2fbdef]">
                    <h2 class="text-2xl font-bold text-[#2fbdef]">Приём главного врача</h2>
                    <span class="inline-flex items-center rounded-full bg-[#2fbdef] px-2.5 py-1 text-[0.66rem] font-semibold uppercase tracking-[0.08em] text-white">Костромина И.В.</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-[#f0f7fc]">
                                <th class="text-left px-4 py-3 font-semibold text-[#2fbdef]">Услуга</th>
                                <th class="text-right px-4 py-3 font-semibold text-[#2fbdef] whitespace-nowrap">Цена</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-[#0f2749]">Лечебно-диагностический приём</div>
                                    <p class="text-sm text-[#214a7f] mt-1">Многоуровневая Системная Адаптационная Диагностика и Терапия (МСАДТ) - ВРТ, БРТ, психорегуляция, биорегуляция</p>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">12 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-[#0f2749]">Авторский тематический приём</div>
                                    <p class="text-sm text-[#214a7f] mt-1">ВРТ и БРТ + психогенетическая диагностика, кинезиодиагностика и психорегуляция</p>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">15 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-[#0f2749]">Приём "Мать и дитя"</div>
                                    <p class="text-sm text-[#214a7f] mt-1">Авторская методика для мамы и ребенка (0-3 года). Стоимость за 2-х пациентов</p>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">12 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-[#0f2749]">Мониторинг и препараты</div>
                                    <p class="text-sm text-[#214a7f] mt-1">Изготовление индивидуального гомеопатического или аромапрепарата. Бесплатно для пациентов на комплексной программе</p>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">3 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-[#0f2749]">"Экосистема - Естественное омоложение"</div>
                                    <p class="text-sm text-[#214a7f] mt-1">Детоксикация, биорегуляция, регенерация без учета стоимости препарата</p>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">10 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-[#0f2749]">"Экосистема" с плазмолифтингом</div>
                                    <p class="text-sm text-[#214a7f] mt-1">Полная программа с омолаживающими процедурами</p>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">13 000 ₽</td>
                            </tr>
                            <tr class="bg-[#f9f0e6]">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-[#0f2749]">Социальный приём</div>
                                    <p class="text-sm text-[#214a7f] mt-1">Программа лояльности для пенсионеров 70+, участников СВО, дети 3-11 лет</p>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">5 000 ₽</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Психология -->
            <section id="psychology" class="category-section">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b-2 border-[#2fbdef]">
                    <h2 class="text-2xl font-bold text-[#2fbdef]">Психология</h2>
                    <span class="inline-flex items-center rounded-full bg-[#2fbdef] px-2.5 py-1 text-[0.66rem] font-semibold uppercase tracking-[0.08em] text-white">Ференц Н.Ю.</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-[#f0f7fc]">
                                <th class="text-left px-4 py-3 font-semibold text-[#2fbdef]">Услуга</th>
                                <th class="text-right px-4 py-3 font-semibold text-[#2fbdef] whitespace-nowrap">Цена</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-[#0f2749]">Приём психолога - 60 минут</div>
                                    <p class="text-sm text-[#214a7f] mt-1">Психодиагностика и терапия (базовый пакет)</p>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">5 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-[#0f2749]">Приём психолога - 90 минут</div>
                                    <p class="text-sm text-[#214a7f] mt-1">Расширенная консультация с глубокой диагностикой</p>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">7 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-[#0f2749]">Приём психолога - 120 минут</div>
                                    <p class="text-sm text-[#214a7f] mt-1">Интенсивная работа над сложными психологическими вопросами</p>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">10 000 ₽</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Остеопатия -->
            <section id="osteopathy" class="category-section">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b-2 border-[#2fbdef]">
                    <h2 class="text-2xl font-bold text-[#2fbdef]">Остеопатия</h2>
                    <span class="inline-flex items-center rounded-full bg-[#2fbdef] px-2.5 py-1 text-[0.66rem] font-semibold uppercase tracking-[0.08em] text-white">Специалисты</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-[#f0f7fc]">
                                <th class="text-left px-4 py-3 font-semibold text-[#2fbdef]">Услуга</th>
                                <th class="text-right px-4 py-3 font-semibold text-[#2fbdef] whitespace-nowrap">Цена</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-[#0f2749]">Диагностика и консультация (Нехорошева Л.С.)</div>
                                    <p class="text-sm text-[#214a7f] mt-1">Врач-остеопат, мануальный терапевт, кандидат медицинских наук</p>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">4 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-[#0f2749]">Приём остеопата (Нехорошева Л.С.)</div>
                                    <p class="text-sm text-[#214a7f] mt-1">Полный лечебный сеанс</p>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">10 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-[#0f2749]">Диагностика и консультация (Вертлиб В.П.)</div>
                                    <p class="text-sm text-[#214a7f] mt-1">Врач-остеопат, невролог</p>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">4 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-[#0f2749]">Приём остеопата (Вертлиб В.П.)</div>
                                    <p class="text-sm text-[#214a7f] mt-1">Полный лечебный сеанс</p>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">10 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-[#0f2749]">Приём для детей 0-3 лет</div>
                                    <p class="text-sm text-[#214a7f] mt-1">Специализированный остеопатический приём</p>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">6 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-[#0f2749]">Приём для детей 3-7 лет</div>
                                    <p class="text-sm text-[#214a7f] mt-1">Развивающий и коррекционный прием</p>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">7 000 ₽</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Инфузионная терапия -->
            <section id="infusion" class="category-section">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b-2 border-[#2fbdef]">
                    <h2 class="text-2xl font-bold text-[#2fbdef]">Инфузионная терапия</h2>
                    <span class="inline-flex items-center rounded-full bg-[#2fbdef] px-2.5 py-1 text-[0.66rem] font-semibold uppercase tracking-[0.08em] text-white">Капельницы</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-[#f0f7fc]">
                                <th class="text-left px-4 py-3 font-semibold text-[#2fbdef]">Услуга</th>
                                <th class="text-right px-4 py-3 font-semibold text-[#2fbdef] whitespace-nowrap">Цена</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="px-4 py-3">Гомеопатическая капельница Heel (детоксикация)</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">2 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Гомеопатическая капельница Heel + озон</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">3 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Капельница с озоном (иммуномодуляция)</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">3 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Капельница с глутатионом (антиоксидант)</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">3 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Капельница с элькаром (левокарнитин)</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">2 500 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Капельница с липоевой кислотой (Германия)</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">2 500 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Капельница с цитофлавином</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">2 500 ₽</td>
                            </tr>
                            <tr class="bg-[#f0f7fc] font-semibold">
                                <td class="px-4 py-3">Капельница Лаеннек (Япония) - 1 ампула 2мл</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">4 500 ₽</td>
                            </tr>
                            <tr class="bg-[#f0f7fc] font-semibold">
                                <td class="px-4 py-3">Капельница Лаеннек (Япония) - 2 ампулы 4мл</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">6 000 ₽</td>
                            </tr>
                            <tr class="bg-[#f0f7fc] font-semibold">
                                <td class="px-4 py-3">Капельница Лаеннек (Япония) - 3 ампулы 6мл</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">8 000 ₽</td>
                            </tr>
                            <tr class="bg-[#f0f7fc] font-semibold">
                                <td class="px-4 py-3">Капельница Лаеннек (Япония) - 4 ампулы 8мл</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">10 000 ₽</td>
                            </tr>
                            <tr class="bg-[#f0f7fc] font-semibold">
                                <td class="px-4 py-3">Капельница Лаеннек (Япония) - 5 ампул 10мл</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">12 000 ₽</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Инъекционная терапия -->
            <section id="injection" class="category-section">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b-2 border-[#2fbdef]">
                    <h2 class="text-2xl font-bold text-[#2fbdef]">Инъекционная терапия</h2>
                    <span class="inline-flex items-center rounded-full bg-[#2fbdef] px-2.5 py-1 text-[0.66rem] font-semibold uppercase tracking-[0.08em] text-white">PRP, озон, биопунктура</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-[#f0f7fc]">
                                <th class="text-left px-4 py-3 font-semibold text-[#2fbdef]">Услуга</th>
                                <th class="text-right px-4 py-3 font-semibold text-[#2fbdef] whitespace-nowrap">Цена</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="px-4 py-3">Биопунктура МЭЛСМОН (плацента, иммуномодуляция)</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">7 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Гомеопунктура с препаратами HEEL</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">3 500 ₽</td>
                            </tr>
                            <tr class="bg-[#f0f7fc] font-semibold">
                                <td class="px-4 py-3">PRP-терапия (плазмотерапия) - 1 пробирка</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">7 000 ₽</td>
                            </tr>
                            <tr class="bg-[#f0f7fc] font-semibold">
                                <td class="px-4 py-3">PRP-терапия - 2 пробирки</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">9 000 ₽</td>
                            </tr>
                            <tr class="bg-[#f0f7fc] font-semibold">
                                <td class="px-4 py-3">PRP-терапия - 3 пробирки</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">11 000 ₽</td>
                            </tr>
                            <tr class="bg-[#f0f7fc] font-semibold">
                                <td class="px-4 py-3">PRP-терапия - 4 пробирки</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">13 000 ₽</td>
                            </tr>
                            <tr class="bg-[#f0f7fc] font-semibold">
                                <td class="px-4 py-3">PRP-терапия - 5 пробирок</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">15 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Индивидуальный иммуномодулятор "Принцесса на горошине"</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">7 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Омолаживающая сыворотка из плазмы (14 дней)</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">5 500 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Карбокситерапия / озонотерапия - 1 зона</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">3 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Карбокситерапия / озонотерапия - 2 зоны</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">4 500 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Карбокситерапия / озонотерапия - общая</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">6 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Внутримышечные инъекции</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">600 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Внутримышечные инъекции (выездная)</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">1 200 ₽</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Рефлексотерапия -->
            <section id="reflexotherapy" class="category-section">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b-2 border-[#2fbdef]">
                    <h2 class="text-2xl font-bold text-[#2fbdef]">Рефлексотерапия</h2>
                    <span class="inline-flex items-center rounded-full bg-[#2fbdef] px-2.5 py-1 text-[0.66rem] font-semibold uppercase tracking-[0.08em] text-white">Кондратова Е.А.</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-[#f0f7fc]">
                                <th class="text-left px-4 py-3 font-semibold text-[#2fbdef]">Услуга</th>
                                <th class="text-right px-4 py-3 font-semibold text-[#2fbdef] whitespace-nowrap">Цена</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="px-4 py-3">Диагностика и консультация зав.отделения</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">3 500 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Экспресс-приём зав.отделения</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">4 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Корпоральная иглорефлексотерапия (10-15 игл)</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">5 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Микропунктура (аурикулотерапия, краниотерапия, Су-Джок)</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">4 500 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Лазеропунктура</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">3 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Акупунктурный лифтинг лица и тела</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">6 000 ₽</td>
                            </tr>
                            <tr class="bg-[#f9f0e6]">
                                <td class="px-4 py-3">
                                    <div class="font-semibold">Авторский метод "Восточный экспресс"</div>
                                    <p class="text-sm text-[#214a7f] mt-1">Кетгут-терапия (10 нитей) + подарок - нить в ухо</p>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">12 500 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold">Программа "Женское здоровье"</div>
                                    <p class="text-sm text-[#214a7f] mt-1">Биоакупунктура + вакуумная гармонизация + активизация ухо</p>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">8 000 ₽</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Физиотерапия -->
            <section id="physiotherapy" class="category-section">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b-2 border-[#2fbdef]">
                    <h2 class="text-2xl font-bold text-[#2fbdef]">Физиотерапия</h2>
                    <span class="inline-flex items-center rounded-full bg-[#2fbdef] px-2.5 py-1 text-[0.66rem] font-semibold uppercase tracking-[0.08em] text-white">Аппаратные методы</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-[#f0f7fc]">
                                <th class="text-left px-4 py-3 font-semibold text-[#2fbdef]">Услуга</th>
                                <th class="text-right px-4 py-3 font-semibold text-[#2fbdef] whitespace-nowrap">Цена</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="px-4 py-3">HILT (лазерная терапия) - 1 зона</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">5 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">HILT (лазерная терапия) - 2 зоны</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">8 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Транскраниальная магнитотерапия (ТКМТ)</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">3 500 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Транскраниальная электростимуляция</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">3 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Магнитотерапия / фотостимуляция Амблио</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">1 500 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Ударно-волновая терапия - 1 зона</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">4 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Ударно-волновая терапия - 2 и более зон</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">6 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">НЛОК (надвенное лазерное облучение крови)</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">1 500 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Гелиосплазма - 1 зона</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">1 500 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Гелиосплазма - омоложение кожи</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">5 500 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Электромиостимуляция VIP LINE - 1 зона</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">3 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Электромиостимуляция VIP LINE - 2 зоны</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">5 500 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Электромиостимуляция VIP LINE - 3 зоны</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">8 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Микротоки VIP LINE (лицо, шея, декольте)</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">5 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Изометрическая миостимуляция (лицо и шея)</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">5 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Электрофорез лекарственный</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">2 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Ультрафонофорез лекарственный (1-2 зоны)</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">2 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Ультрафонофорез лекарственный (3-4 зоны)</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">2 500 ₽</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Тейпирование и банки -->
            <section id="taping" class="category-section">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b-2 border-[#2fbdef]">
                    <h2 class="text-2xl font-bold text-[#2fbdef]">Тейпирование и банки</h2>
                    <span class="inline-flex items-center rounded-full bg-[#2fbdef] px-2.5 py-1 text-[0.66rem] font-semibold uppercase tracking-[0.08em] text-white">Процедуры Кондратовой Е.А.</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-[#f0f7fc]">
                                <th class="text-left px-4 py-3 font-semibold text-[#2fbdef]">Услуга</th>
                                <th class="text-right px-4 py-3 font-semibold text-[#2fbdef] whitespace-nowrap">Цена</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="px-4 py-3">Кинезиотейпирование - 1 зона</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">2 500 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Кинезиотейпирование - 2 зоны</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">3 500 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Кинезиотейпирование - 3 зоны</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">4 500 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Акупунктурное кросс-тейпирование (1-2 зоны)</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">2 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Акупунктурное кросс-тейпирование (3-4 зоны)</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">2 500 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Акупунктурное кросс-тейпирование (5-6 зон)</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">3 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Постановка банок - 1 зона</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">3 000 ₽</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Постановка банок - 2 зоны</td>
                                <td class="px-4 py-3 text-right font-bold text-[#2fbdef] whitespace-nowrap">4 000 ₽</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <!-- CTA блок -->
        <div class="prices-cta mt-9 rounded-2xl border border-[#2fbdef]/20 bg-gradient-to-r from-[#2fbdef] to-[#2fbdef] p-5 text-white shadow-[0_12px_28px_rgba(47,189,239,0.2)] md:p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                <div>
                    <h3 class="font-bold">Не уверены, какая услуга вам нужна?</h3>
                    <p class="text-[rgba(255,255,255,0.9)] mb-4">
                        Позвоните нам, и наши специалисты подберут оптимальный план лечения именно для вас.
                    </p>
                    <p class="text-sm text-[rgba(255,255,255,0.8)]">
                        Персональный подход гарантирован!
                    </p>
                </div>
                <div class="flex flex-col gap-3">
                    <a href="tel:<?php echo preg_replace('/\D/', '', CLINIC_PHONE); ?>" class="inline-flex items-center justify-center gap-2 rounded-full bg-white px-5 py-2.5 font-semibold text-[#2fbdef] hover:bg-[#f0f7fc] transition-colors">
                        <i class="fas fa-phone"></i>
                        Позвонить: <?php echo CLINIC_PHONE; ?>
                    </a>
                    <a href="/#contact" class="inline-flex items-center justify-center gap-2 rounded-full border border-white bg-[rgba(255,255,255,0.2)] px-5 py-2.5 font-semibold text-white hover:bg-[rgba(255,255,255,0.3)] transition-colors">
                        <i class="fas fa-calendar"></i>
                        Записаться онлайн
                    </a>
                </div>
            </div>
        </div>
    </main>

    <?php
    $footer = new Footer($brand_colors);
    echo $footer->render();
    ?>

    <script>
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // Make service names in price tables clickable when corresponding service pages exist.
        (function () {
            const serviceIdSet = new Set(<?php echo json_encode(array_keys($serviceIds), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>);
            const rules = [
                { id: 'chief-doctor-consultation', pattern: /лечебнодиагностическ(ий|ого)приемглавноговрача|лечебнодиагностическ(ий|ого)прием/ },
                { id: 'avtorskiy-tematicheskiy-priem', pattern: /авторски(й|и)тематически(й|и)прием/ },
                { id: 'lechebno-diagnosticheskiy-priem-mat-i-ditya-avtorskaya-metodika-mama-i-rebenok-s-0-do-3-kh-let', pattern: /матьидитя|матьдитя/ },
                { id: 'monitoring', pattern: /мониторинг/ },
                { id: 'avtorskaya-metodika-ekosistema-estestvennoe-omolozhenie', pattern: /экосистемаестественноеомоложение|экосистема/ },
                { id: 'sotsialnyy-priem', pattern: /социальны(й|и)прием/ },
                { id: 'psychotherapy', pattern: /приемпсихолога|психотерап/ },
                { id: 'diagnostika-i-konsultatsiya-vracha-osteopata-nekhoroshevoy-l-s', pattern: /диагностик(аи)?иконсультация.*нехорошев/ },
                { id: 'diagnostika-i-konsultatsiya-vracha-osteopata-nevrologa-vertlib-v-p', pattern: /диагностик(аи)?иконсультация.*вертлиб/ },
                { id: 'osteopathy', pattern: /приемостеопата|остеопат/ },
                { id: 'priem-detskogo-osteopata', pattern: /приемдлядетей|детск(ий|ого)остеопат/ },
                { id: 'gomeopaticheskaya-kapelnitsa-preparatami-kheel-ozon', pattern: /капельница.*heel.*озон/ },
                { id: 'gomeopaticheskaya-kapelnitsa-preparatami-kheel', pattern: /гомеопатическа(я|й)капельница.*heel|капельница.*heel/ },
                { id: 'kapelnitsa-s-glutationom-antioksidant', pattern: /капельницасглутатион/ },
                { id: 'kapelnitsa-s-elkarom-levokarnitin', pattern: /капельницасэлькар/ },
                { id: 'kapelnitsa-s-lipoevoy-kislotoy-germaniya', pattern: /капельницаслипоев/ },
                { id: 'kapelnitsa-s-tsitoflavinom', pattern: /капельницасцитофлавин/ },
                { id: 'kapelnitsa-laennek-yaponiya', pattern: /капельницалаеннек/ },
                { id: 'biopunktura', pattern: /биопунктур/ },
                { id: 'gomeopunktura', pattern: /гомеопунктур/ },
                { id: 'prp-therapy', pattern: /prpтерапия|плазмотерап/ },
                { id: 'karboksiterapiya-ozonoterapiya', pattern: /карбокситерапия|озонотерапия/ },
                { id: 'inektsionnaya-terapiya', pattern: /внутримышечны(е|х)инъекц|инъекц/ },
                { id: 'ekspress-priem-zav-otdeleniya-vracha-akushera-ginekologa-refleksoterapevta-kondratovoy-e-a', pattern: /экспрессприемзавотделения|экспрессприем/ },
                { id: 'korporalnaya-iglorefleksoterapiya', pattern: /корпоральнаяиглорефлексотерапия/ },
                { id: 'mikropunktura-aurikulyarnaya', pattern: /микропунктура|аурикулотерапия|краниотерапия|суджок/ },
                { id: 'mikropunktura-lazernaya', pattern: /лазеропунктур/ },
                { id: 'avtorskaya-programma-kondratovoy-e-a-zhenskoe-zdorove', pattern: /женскоездоровье/ },
                { id: 'akupunkturnyy-avtorskiy-metod-kondratovoy-e-a-vostochnyy-ekspress', pattern: /восточныйэкспресс|кетгут/ },
                { id: 'hilt-therapy', pattern: /hilt|лазернаятерапия/ },
                { id: 'transkranialnaya-magnitoterapiya-tkmt', pattern: /транскраниальнаямагнитотерапия|ткмт/ },
                { id: 'magnitoterapiya-fotostimulyatsiya-amblio', pattern: /магнитотерапияфотостимуляцияамблио|амблио/ },
                { id: 'shock-wave-therapy', pattern: /ударноволноваятерапия/ },
                { id: 'geliosplazma', pattern: /гелиосплазма/ },
                { id: 'miostimulyatsiya-vip-layn', pattern: /vipline|электромиостимуляция|миостимуляц|микротоки/ },
                { id: 'elektroforez', pattern: /электрофорез/ },
                { id: 'ultrafonoforez', pattern: /ультрафонофорез/ },
                { id: 'taping', pattern: /кинезиотейпирован/ },
                { id: 'akupunkturnoe-kross-teypirovanie', pattern: /кросстейпирован/ },
                { id: 'cupping', pattern: /постановкабанок|баночн/ }
            ];

            const normalize = (value) =>
                (value || '')
                    .toLowerCase()
                    .replace(/[ё]/g, 'е')
                    .replace(/[^a-zа-я0-9]+/g, '');

            const getMainTitle = (td) => {
                const strongTitle = td.querySelector('div.font-semibold');
                if (strongTitle) {
                    return { text: strongTitle.textContent || '', node: strongTitle };
                }

                const clone = td.cloneNode(true);
                clone.querySelectorAll('p').forEach((p) => p.remove());
                return { text: clone.textContent || '', node: td };
            };

            document.querySelectorAll('.category-section tbody tr td:first-child').forEach((td) => {
                const { text, node } = getMainTitle(td);
                const normalized = normalize(text);
                if (!normalized) {
                    return;
                }

                const match = rules.find((rule) => serviceIdSet.has(rule.id) && rule.pattern.test(normalized));
                if (!match) {
                    return;
                }

                const href = '/services/' + encodeURIComponent(match.id);
                const titleText = (text || '').trim();
                if (!titleText) {
                    return;
                }

                if (node === td) {
                    const clone = td.cloneNode(true);
                    clone.querySelectorAll('p').forEach((p) => p.remove());
                    const plain = (clone.textContent || '').trim();
                    if (plain) {
                        td.innerHTML = td.innerHTML.replace(plain, '<a class="price-service-link" href="' + href + '">' + plain + '</a>');
                    }
                    return;
                }

                node.innerHTML = '<a class="price-service-link" href="' + href + '">' + titleText + '</a>';
            });
        })();
    </script>
</body>
</html>
