<?php
require_once __DIR__ . '/includes/pin_protection.php';
bioinmed_pin_require_access();


require_once 'config.php';
require_once 'includes/components/Components.php';
require_once 'includes/content/EditableLists.php';

$siteUrl = rtrim(CLINIC_SITE_URL, '/');
$iconPath = CLINIC_ICON_PATH;
$iconUrl = $siteUrl . $iconPath;
$socialImageUrl = bioinmed_default_social_image_url();
$canonicalUrl = $siteUrl . '/';
$pageTitle = 'Клиника восстановительной медицины в Москве | БИОИНМЕД';
$pageDescription = 'Диагностика «Хабилект», остеопатия, рефлексотерапия, физиотерапия, опытные врачи и персональный план лечения.';

$structuredData = bioinmed_medical_organization_schema();

$websiteStructuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    '@id' => $siteUrl . '/#website',
    'name' => CLINIC_NAME,
    'url' => $siteUrl,
    'inLanguage' => 'ru-RU',
];

$faqStructuredData = bioinmed_faq_schema(array_map(static function ($item) {
    return [
        'q' => $item['question'] ?? '',
        'a' => $item['answer'] ?? '',
    ];
}, is_array($faq_items) ? $faq_items : []));

$indexPage = bioinmed_read_json_file('pages/index.json');
$indexHealthRoute = is_array($indexPage['health_route'] ?? null) ? $indexPage['health_route'] : [];
$indexHealthRouteSteps = is_array($indexHealthRoute['steps'] ?? null) ? $indexHealthRoute['steps'] : [];
$indexHealthRouteIcons = [
    'consultation' => 'fa-solid fa-user-doctor',
    'diagnostics' => 'fa-solid fa-magnifying-glass-chart',
    'treatment' => 'fa-solid fa-heart-pulse',
    'recovery' => 'fa-solid fa-kit-medical',
    'activity' => 'fa-solid fa-person-walking',
    'result' => 'fa-solid fa-star',
];
$indexHealthRouteFallback = [];
foreach ($indexHealthRouteSteps as $stepKey => $step) {
    $indexHealthRouteFallback[] = [
        'id' => (string)$stepKey,
        'text' => (string)($step['title'] ?? ''),
        'secondary' => (string)($step['text'] ?? ''),
        'icon' => $indexHealthRouteIcons[$stepKey] ?? 'fa-solid fa-check',
    ];
}
$indexHealthRouteEditableItems = bioinmed_editable_list_items($indexPage, 'index.health_route.steps', $indexHealthRouteFallback, 'fa-solid fa-check');
$homeSloganTitle = bioinmed_text('home.slogan.title', 'С нами выздоравливать легко!');
$homeSloganSignature = bioinmed_text('home.slogan.signature', 'Ваш Биоинмед');
$homeSloganLogo = bioinmed_versioned_asset_path('/public/images/brand/bioinmed-icon.png');
$routeToolsTitle = bioinmed_text('home.route_tools.title', 'Ключевые системы восстановления: Хабилект и Биорезонанс');
$routeToolsIntro = bioinmed_text('home.route_tools.intro', 'Мы используем эти направления как практические этапы: точно определяем ключевые перегрузки, выбираем приоритеты восстановления и ведем Вас по индивидуальной программе с контролем динамики.');
$routeToolsHabilectLabel = bioinmed_text('home.route_tools.habilect.label', 'Хабилект');
$routeToolsHabilectDescription = bioinmed_text('home.route_tools.habilect.description', 'Хабилект - это диагностический контур, который помогает врачу увидеть функциональные дисбалансы, расставить приоритеты лечения и сократить путь от жалоб к конкретным решениям.');
$routeToolsHabilectCta = bioinmed_text('home.route_tools.habilect.cta', 'Записаться на консультацию');
$routeToolsBioresonanceLabel = bioinmed_text('home.route_tools.bioresonance.label', 'Биорезонанс');
$routeToolsBioresonanceDescription = bioinmed_text('home.route_tools.bioresonance.description', 'Биорезонанс в нашей практике - это мягкий инструмент сопровождения, который интегрируется в комплексную терапию и помогает поддерживать адаптационные ресурсы организма.');
$routeToolsBioresonanceCta = bioinmed_text('home.route_tools.bioresonance.cta', 'Уточнить, подходит ли Вам');

$routeToolsHabilectItems = bioinmed_editable_list_items($indexPage, 'index.route_tools.habilect.items', [
    ['id' => 'habilect-item-1', 'text' => bioinmed_text('home.route_tools.habilect.items.1', 'Выявление факторов, которые тормозят восстановление именно у Вас.'), 'icon' => 'fa-solid fa-check'],
    ['id' => 'habilect-item-2', 'text' => bioinmed_text('home.route_tools.habilect.items.2', 'Определение приоритетов: что важно делать в первую очередь.'), 'icon' => 'fa-solid fa-check'],
    ['id' => 'habilect-item-3', 'text' => bioinmed_text('home.route_tools.habilect.items.3', 'Корректировка тактики по мере прохождения программы лечения.'), 'icon' => 'fa-solid fa-check'],
], 'fa-solid fa-check');

$routeToolsBioresonanceItems = bioinmed_editable_list_items($indexPage, 'index.route_tools.bioresonance.items', [
    ['id' => 'bioresonance-item-1', 'text' => bioinmed_text('home.route_tools.bioresonance.items.1', 'Деликатное включение в курс наряду с базовыми методиками восстановления.'), 'icon' => 'fa-solid fa-check'],
    ['id' => 'bioresonance-item-2', 'text' => bioinmed_text('home.route_tools.bioresonance.items.2', 'Снижение функциональной перегрузки и поддержка саморегуляции.'), 'icon' => 'fa-solid fa-check'],
    ['id' => 'bioresonance-item-3', 'text' => bioinmed_text('home.route_tools.bioresonance.items.3', 'Индивидуальная настройка интенсивности и длительности курса.'), 'icon' => 'fa-solid fa-check'],
], 'fa-solid fa-check');

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
    <link rel="canonical" href="<?php echo $canonicalUrl; ?>">
    <meta name="theme-color" content="#1977b2">
    <?php echo bioinmed_render_social_meta($pageTitle, $pageDescription, $canonicalUrl, [
        'image' => $socialImageUrl,
    ]); ?>
    <?php echo bioinmed_render_favicon_links($iconPath); ?>
    
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    
    <?php echo bioinmed_render_public_head_assets(); ?>
        <script type="application/ld+json">
                <?php echo json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
        </script>
        <script type="application/ld+json">
            <?php echo json_encode($websiteStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
        </script>
        <script type="application/ld+json">
            <?php echo json_encode($faqStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
        </script>
    
    <!-- Фирменные шрифты и стили -->
    <style>
        :root {
            --background: #e4f1fa;
            --foreground: #0f2749;
        }
        
        * {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', 'SF Pro Display', 'SF Pro Text', sans-serif;
        }
        
        html {
            scroll-behavior: smooth;
            background: var(--background);
        }
        
        body {
            background: var(--background);
            color: var(--foreground);
        }
        
        /* Avoid large reveal animations that cause section flashing during scroll. */
        .fade-in {
            opacity: 1;
            transform: none;
        }
        
        a, button {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        ::selection {
            background: rgba(36, 140, 255, 0.3);
            color: #1977b2;
        }

        input:focus, textarea:focus {
            box-shadow: 0 0 0 3px rgba(36, 140, 255, 0.1);
        }

        .bioinmed-editable-list-item-hidden,
        .bioinmed-editable-list-toolbar,
        .bioinmed-editable-list-actions { display: none !important; }


        @media (max-width: 767px) {
            body {
                min-width: 320px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                scroll-behavior: auto !important;
            }
        }
    </style>
<?php echo bioinmed_uis_counter_head(); ?>
</head>
<body class="antialiased">
    <?php echo bioinmed_yandex_metrika_noscript(); ?>
    <!-- Header -->
    <?php
    $header = new Header($brand_colors);
    echo $header->render();
    ?>
    
    <!-- Hero Section -->
    <?php
    $hero = new HeroSection($brand_colors);
    echo $hero->render();
    ?>

    <!-- Stats Block (Key Performance Indicators) -->
    <?php
    $stats = new StatsBlock($brand_colors);
    echo $stats->render();
    ?>

    <!-- Advantages -->
    <?php
    $advantages_section = new AdvantagesBlock($advantages, $brand_colors);
    echo $advantages_section->render();
    ?>

    <section class="relative isolate flex items-center overflow-hidden border-b py-6 md:py-8" style="border-color:#3b6282;min-height:64svh;min-height:64dvh;background-color:rgb(25 119 178);">
        <div class="pointer-events-none absolute inset-0" style="background:rgba(255,255,255,0.02);"></div>
        <img src="<?php echo e($homeSloganLogo); ?>" alt="" aria-hidden="true" class="pointer-events-none absolute left-1/2 top-1/2 h-[66vmin] w-[66vmin] max-h-[620px] max-w-[620px] -translate-x-1/2 -translate-y-1/2 object-contain" style="opacity:0.04;filter:brightness(0) invert(1) saturate(0);">

        <div class="relative mx-auto flex w-full max-w-4xl items-center justify-center px-6 text-center md:px-10">
            <div class="w-full">
                <p class="caveat-reveal mx-auto max-w-5xl leading-[1.02]" style="font-family:'Caveat',cursive;font-size:clamp(2rem,4.7vw,3.35rem);font-weight:700;color:#f2f8ff;"<?php echo bioinmed_data_text_id('home.slogan.title'); ?>><?php echo e($homeSloganTitle); ?></p>
                <p class="caveat-reveal mt-3" style="font-family:'Caveat',cursive;font-size:clamp(1.28rem,2.35vw,1.8rem);font-weight:700;color:#d6e8f8;"<?php echo bioinmed_data_text_id('home.slogan.signature'); ?>><?php echo e($homeSloganSignature); ?></p>
            </div>
        </div>
    </section>

    <section class="relative isolate overflow-hidden border-b border-[#d2e6f6] py-14 md:py-20" style="background:linear-gradient(180deg,#f8fcff 0%,#ecf5fc 100%);" data-admin-block-root>
        <div class="pointer-events-none absolute inset-x-0 top-0 h-10" style="background:linear-gradient(180deg,rgba(16,77,120,0.2) 0%,rgba(16,77,120,0) 100%);"></div>
        <div class="pointer-events-none absolute -left-24 top-10 h-56 w-56 rounded-full" style="background:radial-gradient(circle,rgba(79,161,219,0.18) 0%,rgba(79,161,219,0) 72%);"></div>
        <div class="pointer-events-none absolute -right-20 bottom-0 h-60 w-60 rounded-full" style="background:radial-gradient(circle,rgba(126,193,238,0.16) 0%,rgba(126,193,238,0) 72%);"></div>
        <div class="mx-auto max-w-6xl px-6 md:px-10">
            <div class="relative mx-auto max-w-5xl text-center" data-admin-block-root>
                <h2 class="text-[2rem] font-bold leading-[1.14] text-[#0f2749] md:text-[2.56rem]"<?php echo bioinmed_data_text_id('home.route_tools.title'); ?>><?php echo e($routeToolsTitle); ?></h2>
                <p class="mt-4 text-[1.06rem] leading-relaxed text-[#17446f] md:text-[1.14rem]"<?php echo bioinmed_data_text_id('home.route_tools.intro'); ?>><?php echo e($routeToolsIntro); ?></p>
            </div>

            <div class="mt-8 grid gap-5 md:mt-10 md:grid-cols-2">
                <article class="relative overflow-hidden rounded-[1.75rem] border border-[#b7d7ee] bg-white p-6 shadow-[0_14px_34px_rgba(15,39,73,0.1)] md:p-7" data-admin-block-root>
                    <div class="absolute -right-10 -top-10 h-28 w-28 rounded-full bg-[#dff0fc]"></div>
                    <div class="relative">
                        <span class="inline-flex items-center gap-2 rounded-full bg-[#e8f3fc] px-3 py-1 text-[0.8rem] font-bold uppercase tracking-[0.08em] text-[#1977b2]"><i class="fa-solid fa-microchip" aria-hidden="true"></i> <span<?php echo bioinmed_data_text_id('home.route_tools.habilect.label'); ?>><?php echo e($routeToolsHabilectLabel); ?></span></span>
                        <p class="mt-4 text-[1rem] leading-relaxed text-[#0f2749]"<?php echo bioinmed_data_text_id('home.route_tools.habilect.description'); ?>><?php echo e($routeToolsHabilectDescription); ?></p>
                        <ul class="mt-4 space-y-2.5 text-[0.96rem] leading-relaxed text-[#17446f]"<?php echo bioinmed_editable_list_attrs('index', 'index.route_tools.habilect.items', 'Главная: Хабилект тезисы', true); ?>>
                            <?php echo bioinmed_editable_list_toolbar(); ?>
                            <?php foreach ($routeToolsHabilectItems as $routeToolHabilectItem): ?>
                            <li class="flex gap-2<?php echo bioinmed_editable_list_item_class($routeToolHabilectItem); ?>"<?php echo bioinmed_editable_list_item_attrs($routeToolHabilectItem); ?>><i class="<?php echo e($routeToolHabilectItem['icon']); ?> mt-1 text-[0.7rem] text-[#1977b2]" data-admin-list-icon-view aria-hidden="true"></i><span data-admin-list-text-view><?php echo e($routeToolHabilectItem['text']); ?></span><?php echo bioinmed_editable_list_actions($routeToolHabilectItem); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="<?php echo e(defined('ONLINE_BOOKING_URL') ? ONLINE_BOOKING_URL : '/'); ?>" onclick="onlineBooking.open();return false;" class="mt-5 inline-flex items-center gap-2 rounded-full bg-[#1977b2] px-4 py-2.5 text-[0.9rem] font-semibold text-white transition hover:bg-[#16658f]"><span<?php echo bioinmed_data_text_id('home.route_tools.habilect.cta'); ?>><?php echo e($routeToolsHabilectCta); ?></span> <i class="fa-solid fa-arrow-right text-[0.72rem]" aria-hidden="true"></i></a>
                    </div>
                </article>

                <article class="relative overflow-hidden rounded-[1.75rem] border border-[#b7d7ee] bg-white p-6 shadow-[0_14px_34px_rgba(15,39,73,0.1)] md:p-7" data-admin-block-root>
                    <div class="absolute -left-10 -bottom-10 h-28 w-28 rounded-full bg-[#e4f1fa]"></div>
                    <div class="relative">
                        <span class="inline-flex items-center gap-2 rounded-full bg-[#edf6ff] px-3 py-1 text-[0.8rem] font-bold uppercase tracking-[0.08em] text-[#23699d]"><i class="fa-solid fa-wave-square" aria-hidden="true"></i> <span<?php echo bioinmed_data_text_id('home.route_tools.bioresonance.label'); ?>><?php echo e($routeToolsBioresonanceLabel); ?></span></span>
                        <p class="mt-4 text-[1rem] leading-relaxed text-[#0f2749]"<?php echo bioinmed_data_text_id('home.route_tools.bioresonance.description'); ?>><?php echo e($routeToolsBioresonanceDescription); ?></p>
                        <ul class="mt-4 space-y-2.5 text-[0.96rem] leading-relaxed text-[#17446f]"<?php echo bioinmed_editable_list_attrs('index', 'index.route_tools.bioresonance.items', 'Главная: Биорезонанс тезисы', true); ?>>
                            <?php echo bioinmed_editable_list_toolbar(); ?>
                            <?php foreach ($routeToolsBioresonanceItems as $routeToolsBioresonanceItem): ?>
                            <li class="flex gap-2<?php echo bioinmed_editable_list_item_class($routeToolsBioresonanceItem); ?>"<?php echo bioinmed_editable_list_item_attrs($routeToolsBioresonanceItem); ?>><i class="<?php echo e($routeToolsBioresonanceItem['icon']); ?> mt-1 text-[0.7rem] text-[#23699d]" data-admin-list-icon-view aria-hidden="true"></i><span data-admin-list-text-view><?php echo e($routeToolsBioresonanceItem['text']); ?></span><?php echo bioinmed_editable_list_actions($routeToolsBioresonanceItem); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="<?php echo e(defined('ONLINE_BOOKING_URL') ? ONLINE_BOOKING_URL : '/'); ?>" onclick="onlineBooking.open();return false;" class="mt-5 inline-flex items-center gap-2 rounded-full border border-[#b8d4ea] bg-white px-4 py-2.5 text-[0.9rem] font-semibold text-[#17446f] transition hover:border-[#7db5dc] hover:text-[#1977b2]"><span<?php echo bioinmed_data_text_id('home.route_tools.bioresonance.cta'); ?>><?php echo e($routeToolsBioresonanceCta); ?></span> <i class="fa-solid fa-arrow-right text-[0.72rem]" aria-hidden="true"></i></a>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <!-- Special Offer -->
    <?php
    $offer_section = new SpecialOffer($brand_colors);
    echo $offer_section->render();
    ?>

    <!-- Problems Grid -->
    <?php
    $problems_section = new ProblemsGrid($problems, $brand_colors, [
        'eyebrow' => '',
        'title' => 'Найдите Вашу ситуацию',
        'subtitle' => '',
    ]);
    echo $problems_section->render();
    ?>

    <section class="border-b border-[#e6eef7] bg-[linear-gradient(180deg,#eef7ff_0%,#e4f1fa_100%)] py-12 md:py-16">
        <div class="mx-auto max-w-6xl px-6 md:px-10">
            <div class="rounded-[2rem] border border-[#dbe8f4] bg-white p-6 shadow-[0_16px_40px_rgba(8,36,70,0.07)] md:p-8">
                <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between" data-admin-block-root>
                    <div>
                        <h2 class="text-[1.4rem] font-bold text-[#0f2749] md:text-[1.75rem]"<?php echo bioinmed_page_text_attr($indexPage, 'index', 'health_route.title'); ?>><?php echo e($indexHealthRoute['title'] ?? 'Ваш маршрут здоровья в Биоинмед'); ?></h2>
                    </div>
                    <p class="max-w-2xl text-[0.92rem] leading-relaxed text-[#1977b2]"<?php echo bioinmed_page_text_attr($indexPage, 'index', 'health_route.subtitle'); ?>><?php echo e($indexHealthRoute['subtitle'] ?? 'Маршрут построен так, чтобы пациент видел всю логику лечения целиком: от первого обращения до устойчивого результата.'); ?></p>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3"<?php echo bioinmed_editable_list_attrs('index', 'index.health_route.steps', 'Маршрут здоровья', true, 'Описание'); ?>>
                    <?php echo bioinmed_editable_list_toolbar('div'); ?>
                    <?php foreach ($indexHealthRouteEditableItems as $routeItem): ?>
                    <?php
                        $routeItemId = (string)($routeItem['id'] ?? '');
                        $isGreenRouteItem = in_array($routeItemId, ['recovery', 'activity'], true);
                        $isOrangeRouteItem = $routeItemId === 'result';
                        $routeCardStyle = $isGreenRouteItem
                            ? 'border-color:#d8ebdf;background:linear-gradient(180deg,#f4fcf8 0%,#ffffff 100%);'
                            : ($isOrangeRouteItem ? 'border-color:#efd1a9;background:linear-gradient(180deg,#fff5e8 0%,#ffffff 100%);' : '');
                        $routeIconStyle = $isGreenRouteItem
                            ? 'background:#e7f7ef;color:#2f9b6a;'
                            : ($isOrangeRouteItem ? 'background:#ffead1;color:#c56818;' : '');
                        $routeTitleStyle = $isOrangeRouteItem ? 'color:#9a5117;' : '';
                    ?>
                    <article class="rounded-2xl border border-[#dce8f4] bg-[linear-gradient(180deg,#f9fcff_0%,#ffffff_100%)] p-5 shadow-[0_8px_18px_rgba(8,36,70,0.05)]<?php echo bioinmed_editable_list_item_class($routeItem); ?>" style="<?php echo e($routeCardStyle); ?>" data-admin-block-root<?php echo bioinmed_editable_list_item_attrs($routeItem); ?>>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#e8f3fc] text-[#1977b2]" style="<?php echo e($routeIconStyle); ?>"><i class="<?php echo e($routeItem['icon']); ?> text-[1rem]" data-admin-list-icon-view></i></span>
                            <p class="text-[1rem] font-semibold text-[#17446f]" style="<?php echo e($routeTitleStyle); ?>" data-admin-list-text-view><?php echo e($routeItem['text']); ?></p>
                        </div>
                        <p class="mt-3 text-[0.9rem] leading-relaxed text-[#0f2749]" data-admin-list-secondary-view><?php echo e($routeItem['secondary']); ?></p>
                        <?php echo bioinmed_editable_list_actions($routeItem); ?>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Chief Doctor -->
    <?php
    $chief_doctor_section = new ChiefDoctorBlock($doctors[0], $brand_colors);
    echo $chief_doctor_section->render();
    ?>
    
    <!-- Doctors -->
    <?php
    $doctorOrder = [
        'kondratova-elena-aleksandrovna',
        'nehorosheva-lyudmila-sergeevna',
        'vertlib-valeriya-pavlovna',
        'mayorova-darya-sergeevna',
        'rozhkov-sergei-leonidovich',
    ];
    $doctorOrderMap = array_flip($doctorOrder);
    $doctors_without_chief = array_slice($doctors, 1);
    foreach ($doctors_without_chief as $originalIndex => &$doctorItem) {
        $doctorItem['__original_index'] = $originalIndex;
    }
    unset($doctorItem);
    usort($doctors_without_chief, static function (array $left, array $right) use ($doctorOrderMap): int {
        $leftOrder = $doctorOrderMap[$left['slug'] ?? ''] ?? 999;
        $rightOrder = $doctorOrderMap[$right['slug'] ?? ''] ?? 999;
        if ($leftOrder === $rightOrder) {
            return ($left['__original_index'] ?? 0) <=> ($right['__original_index'] ?? 0);
        }

        return $leftOrder <=> $rightOrder;
    });
    foreach ($doctors_without_chief as &$doctorItem) {
        unset($doctorItem['__original_index']);
    }
    unset($doctorItem);

    $doctors_section = new DoctorsGrid($doctors_without_chief, $brand_colors);
    echo $doctors_section->render();
    ?>

    <!-- FAQ -->
    <?php
    $faq_section = new FAQBlock($faq_items, $brand_colors);
    echo $faq_section->render();
    ?>

    <!-- Reviews -->
    <?php
    $cases_section = new CasesSlider($brand_colors);
    echo $cases_section->render();
    ?>

    <!-- Popular Services -->
    <?php
    $services_section = new ServicesGrid($services, $brand_colors, ['show_images' => false]);
    echo $services_section->render();
    ?>

    <!-- Appointment CTA -->
    <?php
    $appointment_cta = new AppointmentCTA($brand_colors);
    echo $appointment_cta->render();
    ?>
    
    <!-- Contacts -->
    <?php
    $solidarity_section = new SolidarityMedicineBlock($brand_colors);
    echo $solidarity_section->render();
    ?>

    <!-- Contacts -->
    <?php
    $contact_section = new ContactSection($brand_colors);
    echo $contact_section->render();
    ?>

    <!-- Partners -->
    <?php
    $partners_section = new PartnersBlock($brand_colors);
    echo $partners_section->render();
    ?>
    
    <!-- Footer -->
    <?php
    $footer = new Footer($brand_colors);
    echo $footer->render();
    ?>
    
    <script>
        // Doctors slider controls
        (function initDoctorsSlider() {
            var track = document.querySelector('.doctor-slider-track');
            var prev = document.querySelector('.doctor-slider-prev');
            var next = document.querySelector('.doctor-slider-next');
            if (!track || !prev || !next) return;

            function getStep() {
                return Math.max(280, Math.round(track.clientWidth * 0.85));
            }

            prev.addEventListener('click', function() {
                track.scrollBy({ left: -getStep(), behavior: 'smooth' });
            });

            next.addEventListener('click', function() {
                track.scrollBy({ left: getStep(), behavior: 'smooth' });
            });
        })();
    </script>

</body>
</html>
