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
$routeToolsEyebrow = bioinmed_text('home.route_tools.eyebrow', 'Диагностика и персональный маршрут');
$routeToolsTitle = bioinmed_text('home.route_tools.title', 'Информационные лечебно-диагностические системы');
$routeToolsIntro = bioinmed_text('home.route_tools.intro', 'Две технологии дополняют клинический осмотр и помогают врачу видеть больше: «Хабилект» объективно оценивает движение, а «Артемида PRO-M» применяется в рамках системной адаптационной диагностики и персонального лечебного маршрута.');
$routeToolsHabilectLabel = bioinmed_text('home.route_tools.habilect.label', '3D-система «Хабилект»');
$routeToolsHabilectTag = bioinmed_text('home.route_tools.habilect.tag', '3D-анализ движения');
$routeToolsHabilectDescription = bioinmed_text('home.route_tools.habilect.description', 'Бесконтактные сенсоры фиксируют движения, равновесие, координацию и качество выполнения упражнений в реальном времени. Врач получает объективные данные для диагностики и контроля восстановления.');
$routeToolsHabilectCta = bioinmed_text('home.route_tools.habilect.cta', 'Подробнее о диагностике');
$routeToolsBioresonanceLabel = bioinmed_text('home.route_tools.bioresonance.label', '«Артемида PRO-M» и биорезонанс');
$routeToolsBioresonanceTag = bioinmed_text('home.route_tools.bioresonance.tag', 'Системная адаптационная диагностика');
$routeToolsBioresonanceDescription = bioinmed_text('home.route_tools.bioresonance.description', 'Лечебно-диагностический комплекс используется на приёме главного врача для вегетативно-резонансного тестирования и индивидуального подбора тактики сопровождения.');
$routeToolsBioresonanceCta = bioinmed_text('home.route_tools.bioresonance.cta', 'Подробнее о приёме');
$routeToolsHabilectLogo = bioinmed_preferred_image_asset_path('/public/images/partners/habilect-logo.png');
$routeToolsClinicLogo = bioinmed_preferred_image_asset_path('/public/images/brand/main-logotype.webp');
$routeToolsHabilectLink = bioinmed_link('hero.habilect', ['url' => '/services/habilect-diagnostics']);
$routeToolsBioresonanceLink = bioinmed_link('hero.bioresonance', ['url' => '/services/chief-doctor-consultation']);

$routeToolsHabilectItems = bioinmed_editable_list_items($indexPage, 'index.route_tools.habilect.items', [
    ['id' => 'habilect-item-1', 'text' => bioinmed_text('home.route_tools.habilect.items.1', 'Оценка движений, баланса и координации без маркеров и датчиков на теле.'), 'icon' => 'fa-solid fa-check'],
    ['id' => 'habilect-item-2', 'text' => bioinmed_text('home.route_tools.habilect.items.2', 'Биологическая обратная связь и наглядный контроль выполнения упражнений.'), 'icon' => 'fa-solid fa-check'],
    ['id' => 'habilect-item-3', 'text' => bioinmed_text('home.route_tools.habilect.items.3', 'Применение в ортопедии, неврологии, спортивной и детской реабилитации.'), 'icon' => 'fa-solid fa-check'],
], 'fa-solid fa-check');

$routeToolsBioresonanceItems = bioinmed_editable_list_items($indexPage, 'index.route_tools.bioresonance.items', [
    ['id' => 'bioresonance-item-1', 'text' => bioinmed_text('home.route_tools.bioresonance.items.1', 'Комплексная оценка функционального состояния и адаптационных реакций.'), 'icon' => 'fa-solid fa-check'],
    ['id' => 'bioresonance-item-2', 'text' => bioinmed_text('home.route_tools.bioresonance.items.2', 'Биорезонансная терапия как часть персональной программы лечения.'), 'icon' => 'fa-solid fa-check'],
    ['id' => 'bioresonance-item-3', 'text' => bioinmed_text('home.route_tools.bioresonance.items.3', 'Контроль врача и сочетание с другими методами восстановительной медицины.'), 'icon' => 'fa-solid fa-check'],
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

        .health-route-app-icon {
            position: relative;
            overflow: hidden;
            background: linear-gradient(145deg, var(--icon-from), var(--icon-to));
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.55);
            box-shadow:
                0 10px 22px var(--icon-shadow),
                inset 0 1px 0 rgba(255, 255, 255, 0.5),
                inset 0 -1px 0 rgba(0, 0, 0, 0.1);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .health-route-app-icon::before {
            content: '';
            position: absolute;
            top: -42%;
            left: -18%;
            width: 84%;
            height: 80%;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.28);
            transform: rotate(-18deg);
            pointer-events: none;
        }

        .health-route-app-icon i {
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .health-route-card:hover .health-route-app-icon {
            transform: translateY(-2px) scale(1.045);
            box-shadow:
                0 14px 28px var(--icon-shadow),
                inset 0 1px 0 rgba(255, 255, 255, 0.55),
                inset 0 -1px 0 rgba(0, 0, 0, 0.1);
        }

        .hero-system-button {
            position: relative;
            overflow: hidden;
            background: linear-gradient(145deg, var(--icon-from), var(--icon-to));
            border: 1px solid rgba(255, 255, 255, 0.55);
            box-shadow:
                0 14px 30px var(--icon-shadow),
                inset 0 1px 0 rgba(255, 255, 255, 0.55),
                inset 0 -1px 0 rgba(0, 0, 0, 0.12);
        }

        .hero-system-button::before {
            content: '';
            position: absolute;
            top: -70%;
            left: -16%;
            width: 64%;
            height: 140%;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.24);
            transform: rotate(-18deg);
            pointer-events: none;
        }

        .hero-system-button > span,
        .hero-system-button > i {
            position: relative;
            z-index: 1;
        }

        .hero-system-button:hover {
            box-shadow:
                0 18px 36px var(--icon-shadow),
                inset 0 1px 0 rgba(255, 255, 255, 0.6),
                inset 0 -1px 0 rgba(0, 0, 0, 0.12);
        }


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

    <section class="relative isolate flex items-center overflow-hidden border-b py-10 md:py-12" style="border-color:#3b6282;min-height:42svh;min-height:42dvh;background-color:rgb(25 119 178);">
        <div class="pointer-events-none absolute inset-0" style="background:rgba(255,255,255,0.02);"></div>
        <img src="<?php echo e($homeSloganLogo); ?>" alt="" aria-hidden="true" class="pointer-events-none absolute left-1/2 top-1/2 h-[66vmin] w-[66vmin] max-h-[620px] max-w-[620px] -translate-x-1/2 -translate-y-1/2 object-contain" style="opacity:0.04;filter:brightness(0) invert(1) saturate(0);">

        <div class="relative mx-auto flex w-full max-w-4xl items-center justify-center px-6 text-center md:px-10">
            <div class="w-full">
                <p class="caveat-reveal mx-auto max-w-5xl leading-[1.02]" style="font-family:'Caveat',cursive;font-size:clamp(2rem,4.7vw,3.35rem);font-weight:700;color:#f2f8ff;"<?php echo bioinmed_data_text_id('home.slogan.title'); ?>><?php echo e($homeSloganTitle); ?></p>
                <p class="caveat-reveal mt-3" style="font-family:'Caveat',cursive;font-size:clamp(1.28rem,2.35vw,1.8rem);font-weight:700;color:#d6e8f8;"<?php echo bioinmed_data_text_id('home.slogan.signature'); ?>><?php echo e($homeSloganSignature); ?></p>
            </div>
        </div>
    </section>

    <section class="relative isolate overflow-hidden border-b border-[#d2e6f6] py-14 md:py-20" style="background:linear-gradient(180deg,#f8fcff 0%,#eef7fd 100%);" data-admin-block-root>
        <div class="pointer-events-none absolute -left-24 top-10 h-56 w-56 rounded-full" style="background:radial-gradient(circle,rgba(79,161,219,0.16) 0%,rgba(79,161,219,0) 72%);"></div>
        <div class="pointer-events-none absolute -right-20 bottom-0 h-60 w-60 rounded-full" style="background:radial-gradient(circle,rgba(126,193,238,0.14) 0%,rgba(126,193,238,0) 72%);"></div>
        <div class="mx-auto max-w-6xl px-6 md:px-10">
            <div class="relative mx-auto max-w-5xl text-center" data-admin-block-root>
                <p class="mb-3 text-[0.76rem] font-bold uppercase tracking-[0.14em] text-[#1977b2]"<?php echo bioinmed_data_text_id('home.route_tools.eyebrow'); ?>><?php echo e($routeToolsEyebrow); ?></p>
                <h2 class="text-[2rem] font-bold leading-[1.14] text-[#0f2749] md:text-[2.56rem]"<?php echo bioinmed_data_text_id('home.route_tools.title'); ?>><?php echo e($routeToolsTitle); ?></h2>
                <p class="mx-auto mt-4 max-w-4xl text-[1.03rem] leading-relaxed text-[#315774] md:text-[1.12rem]"<?php echo bioinmed_data_text_id('home.route_tools.intro'); ?>><?php echo e($routeToolsIntro); ?></p>
            </div>

            <div class="mt-9 grid gap-5 md:mt-11 md:grid-cols-2">
                <article class="group relative flex min-h-full flex-col overflow-hidden rounded-[1.75rem] border border-[#b7d7ee] bg-white shadow-[0_12px_30px_rgba(15,39,73,0.08)] transition duration-300 hover:-translate-y-1 hover:border-[#84bfe4] hover:shadow-[0_18px_38px_rgba(15,39,73,0.11)]" data-admin-block-root>
                    <div class="flex min-h-[112px] items-center justify-between gap-5 border-b border-[#dcebf6] bg-[#f7fbff] px-6 py-5 md:px-7">
                        <img src="<?php echo e($routeToolsHabilectLogo); ?>" alt="Хабилект" class="h-auto w-[190px] max-w-[62%] object-contain object-left" loading="lazy" decoding="async">
                        <span class="health-route-app-icon flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl" style="--icon-from:#0284c7;--icon-to:#22d3ee;--icon-shadow:rgba(2,132,199,0.30);"><i class="fa-solid fa-person-walking text-[1.1rem]" aria-hidden="true"></i></span>
                    </div>
                    <div class="flex flex-1 flex-col p-6 md:p-7">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-full bg-[#e8f3fc] px-3 py-1 text-[0.75rem] font-bold uppercase tracking-[0.08em] text-[#1977b2]"<?php echo bioinmed_data_text_id('home.route_tools.habilect.tag'); ?>><?php echo e($routeToolsHabilectTag); ?></span>
                        </div>
                        <h3 class="mt-4 text-[1.35rem] font-bold leading-tight text-[#0f2749]"<?php echo bioinmed_data_text_id('home.route_tools.habilect.label'); ?>><?php echo e($routeToolsHabilectLabel); ?></h3>
                        <p class="mt-3 text-[0.98rem] leading-relaxed text-[#315774]"<?php echo bioinmed_data_text_id('home.route_tools.habilect.description'); ?>><?php echo e($routeToolsHabilectDescription); ?></p>
                        <ul class="mt-5 space-y-3 text-[0.94rem] leading-relaxed text-[#17446f]"<?php echo bioinmed_editable_list_attrs('index', 'index.route_tools.habilect.items', 'Главная: Хабилект тезисы', true); ?>>
                            <?php echo bioinmed_editable_list_toolbar(); ?>
                            <?php foreach ($routeToolsHabilectItems as $routeToolHabilectItem): ?>
                            <li class="flex gap-3<?php echo bioinmed_editable_list_item_class($routeToolHabilectItem); ?>"<?php echo bioinmed_editable_list_item_attrs($routeToolHabilectItem); ?>><span class="health-route-app-icon mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full" style="--icon-from:#0284c7;--icon-to:#22d3ee;--icon-shadow:rgba(2,132,199,0.24);"><i class="<?php echo e($routeToolHabilectItem['icon']); ?> text-[0.58rem]" data-admin-list-icon-view aria-hidden="true"></i></span><span data-admin-list-text-view><?php echo e($routeToolHabilectItem['text']); ?></span><?php echo bioinmed_editable_list_actions($routeToolHabilectItem); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="<?php echo e($routeToolsHabilectLink['url']); ?>" class="mt-auto inline-flex w-fit items-center gap-2 pt-6 text-[0.92rem] font-bold text-[#1977b2] transition group-hover:text-[#125f91]" data-admin-link-behavior="block-edit"><span<?php echo bioinmed_data_text_id('home.route_tools.habilect.cta'); ?>><?php echo e($routeToolsHabilectCta); ?></span> <i class="fa-solid fa-arrow-right text-[0.72rem] transition-transform group-hover:translate-x-1" aria-hidden="true"></i></a>
                    </div>
                </article>

                <article class="group relative flex min-h-full flex-col overflow-hidden rounded-[1.75rem] border border-[#b7d7ee] bg-white shadow-[0_12px_30px_rgba(15,39,73,0.08)] transition duration-300 hover:-translate-y-1 hover:border-[#84bfe4] hover:shadow-[0_18px_38px_rgba(15,39,73,0.11)]" data-admin-block-root>
                    <div class="flex min-h-[112px] items-center justify-between gap-5 border-b border-[#dcebf6] bg-[#f7fbff] px-6 py-5 md:px-7">
                        <img src="<?php echo e($routeToolsClinicLogo); ?>" alt="БИОИНМЕД" class="h-auto w-[220px] max-w-[68%] object-contain object-left" loading="lazy" decoding="async">
                        <span class="health-route-app-icon flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl" style="--icon-from:#7c3aed;--icon-to:#c026d3;--icon-shadow:rgba(124,58,237,0.30);"><i class="fa-solid fa-wave-square text-[1.05rem]" aria-hidden="true"></i></span>
                    </div>
                    <div class="flex flex-1 flex-col p-6 md:p-7">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-full bg-[#edf3f8] px-3 py-1 text-[0.75rem] font-bold uppercase tracking-[0.08em] text-[#17446f]"<?php echo bioinmed_data_text_id('home.route_tools.bioresonance.tag'); ?>><?php echo e($routeToolsBioresonanceTag); ?></span>
                        </div>
                        <h3 class="mt-4 text-[1.35rem] font-bold leading-tight text-[#0f2749]"<?php echo bioinmed_data_text_id('home.route_tools.bioresonance.label'); ?>><?php echo e($routeToolsBioresonanceLabel); ?></h3>
                        <p class="mt-3 text-[0.98rem] leading-relaxed text-[#315774]"<?php echo bioinmed_data_text_id('home.route_tools.bioresonance.description'); ?>><?php echo e($routeToolsBioresonanceDescription); ?></p>
                        <ul class="mt-5 space-y-3 text-[0.94rem] leading-relaxed text-[#17446f]"<?php echo bioinmed_editable_list_attrs('index', 'index.route_tools.bioresonance.items', 'Главная: Биорезонанс тезисы', true); ?>>
                            <?php echo bioinmed_editable_list_toolbar(); ?>
                            <?php foreach ($routeToolsBioresonanceItems as $routeToolsBioresonanceItem): ?>
                            <li class="flex gap-3<?php echo bioinmed_editable_list_item_class($routeToolsBioresonanceItem); ?>"<?php echo bioinmed_editable_list_item_attrs($routeToolsBioresonanceItem); ?>><span class="health-route-app-icon mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full" style="--icon-from:#7c3aed;--icon-to:#c026d3;--icon-shadow:rgba(124,58,237,0.24);"><i class="<?php echo e($routeToolsBioresonanceItem['icon']); ?> text-[0.58rem]" data-admin-list-icon-view aria-hidden="true"></i></span><span data-admin-list-text-view><?php echo e($routeToolsBioresonanceItem['text']); ?></span><?php echo bioinmed_editable_list_actions($routeToolsBioresonanceItem); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="<?php echo e($routeToolsBioresonanceLink['url']); ?>" class="mt-auto inline-flex w-fit items-center gap-2 pt-6 text-[0.92rem] font-bold text-[#17446f] transition group-hover:text-[#1977b2]" data-admin-link-behavior="block-edit"><span<?php echo bioinmed_data_text_id('home.route_tools.bioresonance.cta'); ?>><?php echo e($routeToolsBioresonanceCta); ?></span> <i class="fa-solid fa-arrow-right text-[0.72rem] transition-transform group-hover:translate-x-1" aria-hidden="true"></i></a>
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
                        $routeIconPalettes = [
                            'consultation' => ['#7c3aed', '#c026d3', 'rgba(124,58,237,0.30)'],
                            'diagnostics' => ['#0284c7', '#22d3ee', 'rgba(2,132,199,0.30)'],
                            'treatment' => ['#e11d48', '#fb7185', 'rgba(225,29,72,0.28)'],
                            'recovery' => ['#059669', '#34d399', 'rgba(5,150,105,0.28)'],
                            'activity' => ['#ea580c', '#fbbf24', 'rgba(234,88,12,0.28)'],
                            'result' => ['#4f46e5', '#ec4899', 'rgba(79,70,229,0.30)'],
                        ];
                        $routeIconPalette = $routeIconPalettes[$routeItemId] ?? ['#2563eb', '#38bdf8', 'rgba(37,99,235,0.28)'];
                        $routeIconStyle = '--icon-from:' . $routeIconPalette[0] . ';--icon-to:' . $routeIconPalette[1] . ';--icon-shadow:' . $routeIconPalette[2] . ';';
                        $routeTitleStyle = $isOrangeRouteItem ? 'color:#9a5117;' : '';
                    ?>
                    <article class="health-route-card rounded-2xl border border-[#dce8f4] bg-[linear-gradient(180deg,#f9fcff_0%,#ffffff_100%)] p-5 shadow-[0_8px_18px_rgba(8,36,70,0.05)]<?php echo bioinmed_editable_list_item_class($routeItem); ?>" style="<?php echo e($routeCardStyle); ?>" data-admin-block-root<?php echo bioinmed_editable_list_item_attrs($routeItem); ?>>
                        <div class="flex items-center gap-3">
                            <span class="health-route-app-icon inline-flex h-[3.25rem] w-[3.25rem] shrink-0 items-center justify-center rounded-[1rem]" style="<?php echo e($routeIconStyle); ?>"><i class="<?php echo e($routeItem['icon']); ?> text-[1.12rem]" data-admin-list-icon-view></i></span>
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
            var cards = Array.from(track.querySelectorAll('article'));
            var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var activeTimer = null;

            function getStep() {
                return Math.max(280, Math.round(track.clientWidth * 0.85));
            }

            function isMobileSlider() {
                return window.matchMedia ? window.matchMedia('(max-width: 767px)').matches : window.innerWidth < 768;
            }

            function setVideo(card, play) {
                var video = card ? card.querySelector('.bioinmed-doctor-hover-media__video') : null;
                if (!video) return;

                if (play && !reduceMotion) {
                    var result = video.play();
                    if (result && typeof result.catch === 'function') {
                        result.catch(function() {});
                    }
                    return;
                }

                video.pause();
                video.currentTime = 0;
            }

            function updateActiveDoctor() {
                if (!cards.length) return;

                if (!isMobileSlider()) {
                    cards.forEach(function(card) {
                        card.classList.remove('bioinmed-doctor-mobile-active');
                        setVideo(card, false);
                    });
                    return;
                }

                var trackRect = track.getBoundingClientRect();
                var trackCenter = trackRect.left + trackRect.width / 2;
                var activeCard = cards.reduce(function(best, card) {
                    var rect = card.getBoundingClientRect();
                    var center = rect.left + rect.width / 2;
                    var distance = Math.abs(center - trackCenter);
                    return !best || distance < best.distance ? { card: card, distance: distance } : best;
                }, null);

                cards.forEach(function(card) {
                    var isActive = activeCard && card === activeCard.card;
                    card.classList.toggle('bioinmed-doctor-mobile-active', !!isActive);
                    setVideo(card, !!isActive);
                });
            }

            function scheduleActiveDoctorUpdate(delay) {
                window.clearTimeout(activeTimer);
                activeTimer = window.setTimeout(updateActiveDoctor, delay || 80);
            }

            prev.addEventListener('click', function() {
                track.scrollBy({ left: -getStep(), behavior: 'smooth' });
                scheduleActiveDoctorUpdate(360);
            });

            next.addEventListener('click', function() {
                track.scrollBy({ left: getStep(), behavior: 'smooth' });
                scheduleActiveDoctorUpdate(360);
            });

            track.addEventListener('scroll', function() {
                scheduleActiveDoctorUpdate(90);
            }, { passive: true });

            window.addEventListener('resize', function() {
                scheduleActiveDoctorUpdate(120);
            });

            window.addEventListener('load', function() {
                scheduleActiveDoctorUpdate(120);
            }, { once: true });

            scheduleActiveDoctorUpdate(0);
        })();
    </script>

</body>
</html>
