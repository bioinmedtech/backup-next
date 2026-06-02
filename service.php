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

$serviceSlug = isset($_GET['slug']) ? trim((string)$_GET['slug']) : '';
$serviceId   = isset($_GET['id'])   ? trim((string)$_GET['id'])   : '';
$service     = null;
$matchedByLegacy = false;
$matchedByAlias = false;

if ($serviceSlug !== '' && isset($service_aliases[$serviceSlug])) {
    $serviceSlug = (string)$service_aliases[$serviceSlug];
    $matchedByAlias = true;
}
if ($serviceId !== '' && isset($service_aliases[$serviceId])) {
    $serviceId = (string)$service_aliases[$serviceId];
    $matchedByAlias = true;
}

// Also collect related services (same category, excluding current)
$related = [];
foreach ($services as $item) {
    $itemId = isset($item['id']) ? (string)$item['id'] : '';
    $legacyId = isset($item['legacy_id']) ? (string)$item['legacy_id'] : '';
    if (
        ($serviceSlug !== '' && ($itemId === $serviceSlug || $legacyId === $serviceSlug)) ||
        ($serviceId !== '' && ($itemId === $serviceId || $legacyId === $serviceId))
    ) {
        $service = $item;
        $matchedByLegacy = ($serviceSlug !== '' && $legacyId !== '' && $legacyId === $serviceSlug);
    }
}

// Canonical redirect from legacy hash URL to readable slug URL.
if ($service && $matchedByLegacy && !headers_sent()) {
    header('Location: /services/' . rawurlencode((string)($service['id'] ?? '')), true, 301);
    exit;
}
if ($service && $matchedByAlias && !headers_sent()) {
    header('Location: /services/' . rawurlencode((string)($service['id'] ?? '')), true, 301);
    exit;
}
if ($service) {
    foreach ($services as $item) {
        if (($item['id'] ?? '') !== ($service['id'] ?? '')
            && ($item['category'] ?? '') === ($service['category'] ?? '')) {
            $related[] = $item;
        }
    }
    if (empty($related)) { // fallback: first 3 from any category
        foreach ($services as $item) {
            if (($item['id'] ?? '') !== ($service['id'] ?? '')) {
                $related[] = $item;
                if (count($related) >= 3) break;
            }
        }
    }
}

if ($service === null) {
    http_response_code(404);
}



function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$pageTitle = $service
    ? e($service['name']) . ' — цена, описание, запись | БИОИНМЕД'
    : 'Услуга не найдена | БИОИНМЕД';
$pageDesc  = $service
    ? e($service['description'] ?? $service['name']) . ' Запись на приём в БИОИНМЕД: ' . e(CLINIC_PHONE)
    : 'Описание услуги не найдено';
$canonicalUrl = $service
    ? $siteUrl . '/services/' . rawurlencode((string)($service['id'] ?? $serviceSlug))
    : $siteUrl . '/services';
$robotsContent = $service
    ? 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'
    : 'noindex,follow';

$phone1     = CLINIC_PHONE;
$phone1link = preg_replace('/\D/', '', $phone1);
$phone2     = defined('CLINIC_PHONE_2') ? CLINIC_PHONE_2 : '';
$phone2link = $phone2 ? preg_replace('/\D/', '', $phone2) : '';

// Map category to icon + label
$catInfo = [
    'diagnostics'     => ['icon' => 'fa-magnifying-glass-plus', 'label' => 'Диагностика'],
    'musculoskeletal' => ['icon' => 'fa-bone',                  'label' => 'Опорно-двигательный аппарат'],
    'manual_therapy'  => ['icon' => 'fa-hands',                 'label' => 'Мануальная терапия'],
    'osteopathy'      => ['icon' => 'fa-hand-sparkles',         'label' => 'Остеопатия'],
    'therapy'         => ['icon' => 'fa-heart-pulse',           'label' => 'Терапия'],
    'physiotherapy'   => ['icon' => 'fa-wave-square',           'label' => 'Физиотерапия'],
    'reflexotherapy'  => ['icon' => 'fa-bullseye',              'label' => 'Рефлексотерапия'],
    'infusion_therapy'=> ['icon' => 'fa-droplet',               'label' => 'Инфузионная терапия'],
    'ozone_therapy'   => ['icon' => 'fa-wind',                  'label' => 'Озонотерапия'],
    'injection_therapy'=>['icon' => 'fa-syringe',               'label' => 'Инъекционная терапия'],
    'chief_doctor'    => ['icon' => 'fa-user-doctor',           'label' => 'Приём главного врача'],
    'psychology'      => ['icon' => 'fa-brain',                 'label' => 'Психология'],
    'taping'          => ['icon' => 'fa-bandage',               'label' => 'Тейпирование и банки'],
    'integrative'     => ['icon' => 'fa-leaf',                  'label' => 'Интегративная медицина'],
];
$cat      = $service['category'] ?? 'therapy';
$catIcon  = $catInfo[$cat]['icon']  ?? 'fa-stethoscope';
$catLabel = $catInfo[$cat]['label'] ?? 'Услуга';
$serviceGallery = $service ? bioinmed_service_gallery_urls($service, 4) : [];
$servicePrimaryImage = $serviceGallery[0] ?? null;
$socialImageUrl = $servicePrimaryImage ? ($siteUrl . $servicePrimaryImage) : bioinmed_default_social_image_url();
$serviceGalleryJson = json_encode(array_values($serviceGallery), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$faqs_on_page = [
    ['q' => 'Сколько времени занимает первичный приём?',
     'a' => 'Обычно 60–90 минут. Врач собирает анамнез, проводит оценку состояния и формирует маршрут лечения.'],
    ['q' => 'Нужна ли предварительная подготовка?',
     'a' => 'Специальной подготовки не требуется. Возьмите с собой имеющиеся результаты анализов и исследований (если есть).'],
    ['q' => 'Как быстро будет результат?',
     'a' => 'Зависит от состояния пациента. Часть пациентов отмечают улучшение уже после первой–второй процедуры, другие — после курса. Врач контролирует динамику и корректирует план.'],
    ['q' => 'Можно ли совмещать с другими методами лечения?',
     'a' => 'Да. Интегративный подход клиники именно об этом: услуги сочетаются и усиливают друг друга. Врач подберёт оптимальную комбинацию.'],
];
$organizationStructuredData = bioinmed_medical_organization_schema();
$breadcrumbStructuredData = bioinmed_breadcrumb_schema([
    ['name' => 'Главная', 'url' => '/'],
    ['name' => 'Услуги', 'url' => '/services'],
    ['name' => $service['name'] ?? 'Услуга', 'url' => $canonicalUrl],
]);
$faqStructuredData = bioinmed_faq_schema($faqs_on_page);
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="<?php echo $pageDesc; ?>">
    <meta name="robots" content="<?php echo $robotsContent; ?>">
    <link rel="canonical" href="<?php echo e($canonicalUrl); ?>">
    <meta name="theme-color" content="#2fbdef">
    <?php echo bioinmed_render_social_meta($pageTitle, $pageDesc, $canonicalUrl, [
        'type' => 'article',
        'image' => $socialImageUrl,
        'image_alt' => $service['name'] ?? (CLINIC_NAME . ' — услуга клиники'),
    ]); ?>
    <?php echo bioinmed_render_favicon_links($iconPath); ?>
    <?php if ($service): ?>
    <script type="application/ld+json"><?php echo json_encode([
        '@context'       => 'https://schema.org',
        '@type'          => 'MedicalProcedure',
        'name'           => $service['name'],
        'description'    => $service['description'] ?? '',
        'procedureType'  => $catLabel,
        'followup'       => $service['details'] ?? '',
        'provider'       => [
            '@type' => 'MedicalOrganization',
            'name'  => CLINIC_NAME,
            'url'   => CLINIC_SITE_URL,
            'telephone' => CLINIC_PHONE,
            'address'   => ['@type' => 'PostalAddress', 'streetAddress' => CLINIC_ADDRESS],
        ],
        'url' => $canonicalUrl,
        'image' => $socialImageUrl,
        'mainEntityOfPage' => $canonicalUrl,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <?php endif; ?>
    <script type="application/ld+json"><?php echo json_encode($organizationStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script type="application/ld+json"><?php echo json_encode($breadcrumbStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <?php if ($service): ?>
    <script type="application/ld+json"><?php echo json_encode($faqStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <?php endif; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .fade-up { opacity: 0; transform: translateY(20px); transition: opacity .5s ease, transform .5s ease; }
        .fade-up.visible { opacity: 1; transform: translateY(0); }
        .prose-service p { margin-bottom: .85rem; line-height: 1.75; }
        .service-gallery-thumb.is-active { border-color: #2fbdef; box-shadow: 0 0 0 3px rgba(47, 189, 239, 0.16); }
        .service-main-image-frame { overflow: hidden; }
        .service-main-image-live {
            transform: scale(1.08);
            will-change: transform;
        }
        .service-main-image-live.is-animating {
            animation: serviceLivePhotoZoom 6s ease-out forwards;
        }
        @keyframes serviceLivePhotoZoom {
            from { transform: scale(1.1); }
            to { transform: scale(1); }
        }
        @media (prefers-reduced-motion: reduce) {
            .service-main-image-live,
            .service-main-image-live.is-animating {
                animation: none;
                transform: none;
            }
        }
    </style>
    <?php echo bioinmed_uis_counter_head(); ?>
</head>
<body class="flex min-h-screen flex-col bg-[linear-gradient(to_bottom,#f9fcff_0%,#f3f8fd_45%,#eef4fb_100%)] text-[#0f2749] antialiased">
<?php
$header = new Header($brand_colors);
echo $header->render();
?>

<?php if (!$service): ?>
<main class="mx-auto max-w-4xl grow px-6 py-20 md:px-10">
    <div class="rounded-3xl border border-[#dbe8f3] bg-white p-10 text-center shadow-[0_16px_40px_rgba(8,36,70,0.08)]">
        <i class="fa-solid fa-triangle-exclamation mb-4 text-5xl text-[#b0c8e0]"></i>
        <h1 class="text-3xl font-bold text-[#0f3463]">Услуга не найдена</h1>
        <p class="mt-3 text-[#355b89]">Проверьте ссылку или перейдите к прайс-листу.</p>
        <div class="mt-7 flex flex-wrap justify-center gap-3">
            <a href="/prices" class="inline-flex items-center gap-2 rounded-full bg-[#2fbdef] px-6 py-3 text-sm font-semibold text-white hover:bg-[#1fb3d8]">
                <i class="fa-solid fa-list"></i> Прайс-лист
            </a>
            <a href="/" class="inline-flex items-center gap-2 rounded-full border border-[#c4daed] bg-white px-6 py-3 text-sm font-semibold text-[#2a5a94] hover:bg-[#ecf5ff]">
                <i class="fa-solid fa-house"></i> На главную
            </a>
        </div>
    </div>
</main>

<?php else: ?>
<main class="grow">

    <!-- ===== SERVICE CONTENT ===== -->
    <section>
        <div class="mx-auto max-w-6xl px-6 py-10 md:px-10 md:py-14">

            <!-- breadcrumb -->
            <nav class="mb-6 flex items-center gap-2 text-xs text-[#7a9cc4]">
                <a href="/" class="hover:text-[#2fbdef]">Главная</a>
                <i class="fa-solid fa-chevron-right text-[0.6rem]"></i>
                <a href="/services" class="hover:text-[#2fbdef]">Услуги</a>
                <i class="fa-solid fa-chevron-right text-[0.6rem]"></i>
                <span class="text-[#0f2749]"><?php echo e($service['name']); ?></span>
            </nav>

            <div class="fade-up">
                    <h1 class="mt-4 text-2xl font-bold leading-tight text-[#0f3463] md:text-3xl lg:text-4xl"><?php echo e($service['name']); ?></h1>
                    <?php if (!empty($service['subtitle'])): ?>
                    <p class="mt-2 text-lg font-semibold text-[#2a5a94]"><?php echo e($service['subtitle']); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($service['description'])): ?>
                    <p class="mt-4 max-w-2xl text-base leading-relaxed text-[#355b89] md:text-[1.02rem]">
                        <?php echo e($service['description']); ?>
                    </p>
                    <?php endif; ?>

                    <!-- trust badges -->
                    <div class="mt-7 flex flex-wrap gap-3">
                        <div class="flex items-center gap-2 rounded-full border border-[#d6e4f2] bg-white px-3 py-2 text-xs font-semibold text-[#2a5a94]">
                            <i class="fa-solid fa-shield-heart text-[#2fbdef]"></i> Лицензированная клиника
                        </div>
                        <div class="flex items-center gap-2 rounded-full border border-[#d6e4f2] bg-white px-3 py-2 text-xs font-semibold text-[#2a5a94]">
                            <i class="fa-solid fa-user-doctor text-[#2fbdef]"></i> Врачи с опытом 20–30 лет
                        </div>
                        <div class="flex items-center gap-2 rounded-full border border-[#d6e4f2] bg-white px-3 py-2 text-xs font-semibold text-[#2a5a94]">
                            <i class="fa-solid fa-clock text-[#2fbdef]"></i> Индивидуальный план лечения
                        </div>
                    </div>
                </div>

            <div class="mt-8 grid items-start gap-8 lg:mt-10 lg:grid-cols-[minmax(0,1fr)_380px]">

                <!-- left: main content -->
                <div class="space-y-6 lg:order-1">

                <!-- Как проходит приём и лечение -->
                <div class="fade-up rounded-3xl border border-[#d9e7f3] bg-white p-7 shadow-[0_8px_28px_rgba(8,36,70,0.06)]">
                    <h2 class="flex items-center gap-2.5 text-xl font-bold text-[#0f3463]">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#2fbdef]">
                            <i class="fa-solid fa-circle-play text-sm"></i>
                        </span>
                        Как проходит приём и лечение
                    </h2>
                    <?php if (!empty($service['details'])): ?>
                    <p class="mt-4 text-sm leading-relaxed text-[#355b89]"><?php echo e($service['details']); ?></p>
                    <?php endif; ?>
                    <ol class="mt-5 space-y-3">
                        <li class="flex items-start gap-3">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#2fbdef] text-xs font-bold text-white">1</span>
                            <span class="text-sm text-[#214a7f] mt-0.5">Первичная консультация — врач собирает анамнез и оценивает состояние</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#2fbdef] text-xs font-bold text-white">2</span>
                            <span class="text-sm text-[#214a7f] mt-0.5">Диагностика — функциональная оценка, при необходимости инструментальная</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#2fbdef] text-xs font-bold text-white">3</span>
                            <span class="text-sm text-[#214a7f] mt-0.5">Составление персонального плана лечения с конкретными целями</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#2fbdef] text-xs font-bold text-white">4</span>
                            <span class="text-sm text-[#214a7f] mt-0.5">Проведение курса — с контролем динамики и корректировкой плана</span>
                        </li>
                    </ol>
                </div>

                <!-- Who it's for -->
                <?php if (!empty($service['target'])): ?>
                <div class="fade-up rounded-3xl border border-[#d9e7f3] bg-white p-7 shadow-[0_8px_28px_rgba(8,36,70,0.06)]">
                    <h2 class="flex items-center gap-2.5 text-xl font-bold text-[#0f3463]">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#2fbdef]">
                            <i class="fa-solid fa-users text-sm"></i>
                        </span>
                        Кому показана услуга
                    </h2>
                    <p class="mt-4 text-sm leading-relaxed text-[#355b89]"><?php echo e($service['target']); ?></p>
                    <div class="mt-4 rounded-xl bg-[#f4f9ff] border border-[#dce8f5] p-4 text-sm text-[#214a7f]">
                        <i class="fa-solid fa-circle-info text-[#2fbdef] mr-2"></i>
                        Точные показания определяет врач на первичной консультации. Запишитесь — первый приём займёт 60–90 минут.
                    </div>
                </div>
                <?php endif; ?>

                <!-- Why BIOINMED -->
                <div class="fade-up rounded-3xl border border-[#d9e7f3] bg-[#f4f9ff] p-7">
                    <h2 class="flex items-center gap-2.5 text-xl font-bold text-[#0f3463]">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#dceefb] text-[#2fbdef]">
                            <i class="fa-solid fa-award text-sm"></i>
                        </span>
                        Почему БИОИНМЕД
                    </h2>
                    <ul class="mt-5 grid gap-3 sm:grid-cols-2">
                        <li class="flex items-start gap-3 rounded-xl border border-[#e4edf6] bg-white p-3 text-sm text-[#214a7f]">
                            <i class="fa-solid fa-check mt-0.5 text-[#2fbdef]"></i>Лечим причину боли, а не маскируем симптомы
                        </li>
                        <li class="flex items-start gap-3 rounded-xl border border-[#e4edf6] bg-white p-3 text-sm text-[#214a7f]">
                            <i class="fa-solid fa-check mt-0.5 text-[#2fbdef]"></i>Объясняем диагноз и план простым понятным языком
                        </li>
                        <li class="flex items-start gap-3 rounded-xl border border-[#e4edf6] bg-white p-3 text-sm text-[#214a7f]">
                            <i class="fa-solid fa-check mt-0.5 text-[#2fbdef]"></i>Собираем персональный маршрут из методов, которые реально сочетаются
                        </li>
                        <li class="flex items-start gap-3 rounded-xl border border-[#e4edf6] bg-white p-3 text-sm text-[#214a7f]">
                            <i class="fa-solid fa-check mt-0.5 text-[#2fbdef]"></i>Контролируем динамику на каждом этапе и корректируем курс
                        </li>
                        <li class="flex items-start gap-3 rounded-xl border border-[#e4edf6] bg-white p-3 text-sm text-[#214a7f]">
                            <i class="fa-solid fa-check mt-0.5 text-[#2fbdef]"></i>Врачи с 20-30-летним опытом и клиника с лицензией МЗ РФ
                        </li>
                        <li class="flex items-start gap-3 rounded-xl border border-[#e4edf6] bg-white p-3 text-sm text-[#214a7f]">
                            <i class="fa-solid fa-check mt-0.5 text-[#2fbdef]"></i>Прозрачные цены и быстрый контакт с клиникой без ожидания
                        </li>
                    </ul>
                </div>

                <!-- FAQ mini -->
                <div class="fade-up rounded-3xl border border-[#d9e7f3] bg-white p-7 shadow-[0_8px_28px_rgba(8,36,70,0.06)]">
                    <h2 class="flex items-center gap-2.5 text-xl font-bold text-[#0f3463]">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#2fbdef]">
                            <i class="fa-solid fa-circle-question text-sm"></i>
                        </span>
                        Часто задаваемые вопросы
                    </h2>
                    <div class="mt-5 space-y-4" id="faq-list">
                        <?php
                        foreach ($faqs_on_page as $i => $faq):
                        ?>
                        <details class="group rounded-xl border border-[#e4edf6] bg-[#f8fbff]">
                            <summary class="flex cursor-pointer items-center justify-between gap-3 px-5 py-4 text-sm font-semibold text-[#0f3463] marker:hidden list-none">
                                <?php echo e($faq['q']); ?>
                                <i class="fa-solid fa-chevron-down text-[#2fbdef] text-xs transition-transform group-open:rotate-180 shrink-0"></i>
                            </summary>
                            <p class="px-5 pb-4 text-sm leading-relaxed text-[#355b89]"><?php echo e($faq['a']); ?></p>
                        </details>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Related services -->
                <?php if (!empty($related)): ?>
                <div class="fade-up">
                    <h2 class="text-xl font-bold text-[#0f3463]">Другие услуги клиники</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <?php foreach (array_slice($related, 0, 4) as $rel): ?>
                        <a href="/services/<?php echo e($rel['id']); ?>"
                           class="flex flex-col justify-between rounded-2xl border border-[#dce8f5] bg-white p-5 hover:border-[#2fbdef] hover:shadow-md transition-all">
                            <div>
                                <p class="text-sm font-semibold leading-snug text-[#0f3463]"><?php echo e($rel['name']); ?></p>
                                <p class="mt-1 text-xs text-[#5a7fa3]"><?php echo e($rel['description'] ?? ''); ?></p>
                            </div>
                            <div class="mt-4 flex items-center justify-between">
                                <span class="text-sm font-bold text-[#2fbdef]"><?php echo e($rel['price'] ?? ''); ?></span>
                                <span class="text-xs font-semibold text-[#2fbdef]">Подробнее <i class="fa-solid fa-arrow-right text-[0.65rem]"></i></span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

                <!-- right: media + booking -->
                <div class="order-first fade-up space-y-4 lg:order-2" style="transition-delay:.07s">
                    <?php if ($servicePrimaryImage): ?>
                    <div class="overflow-hidden rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_12px_30px_rgba(8,36,70,0.10)]">
                        <div class="service-main-image-frame relative aspect-[5/4] overflow-hidden bg-[#edf7ff]">
                            <img src="<?php echo e($servicePrimaryImage); ?>"
                                 alt="<?php echo e($service['name']); ?>"
                                 id="service-main-image"
                                 class="service-main-image-live is-animating h-full w-full cursor-zoom-in object-cover"
                                 loading="eager">
                            <button type="button"
                                    id="service-image-zoom"
                                    class="absolute right-4 top-4 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/88 text-[#1b5c99] shadow-[0_10px_24px_rgba(8,36,70,0.12)] transition hover:bg-white"
                                    aria-label="Увеличить изображение">
                                <i class="fa-solid fa-magnifying-glass-plus text-[#2fbdef]"></i>
                            </button>
                        </div>
                        <?php if (!empty($serviceGallery)): ?>
                        <div class="grid grid-cols-4 gap-2 border-t border-[#e7eef6] bg-[#f8fbff] p-3">
                            <?php foreach ($serviceGallery as $galleryIndex => $galleryImage): ?>
                            <button type="button"
                                    class="service-gallery-thumb <?php echo $galleryIndex === 0 ? 'is-active' : ''; ?> overflow-hidden rounded-2xl border border-[#dfe7f1] bg-white aspect-[4/3] transition hover:border-[#2fbdef]"
                                    data-image-src="<?php echo e($galleryImage); ?>"
                                    data-image-alt="<?php echo e($service['name']); ?>">
                                <img src="<?php echo e($galleryImage); ?>"
                                     alt="<?php echo e($service['name']); ?>"
                                     class="h-full w-full object-cover"
                                     loading="lazy">
                            </button>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div id="book" class="rounded-3xl border border-[#d9e7f3] bg-white p-6 shadow-[0_12px_30px_rgba(8,36,70,0.10)]">
                        <div class="flex items-end gap-2 border-b border-[#eaf1f8] pb-5">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#4b6f9a]">Стоимость</p>
                                <p class="mt-1 text-3xl font-bold text-[#0f3463]"><?php echo e($service['price'] ?? 'По запросу'); ?></p>
                                <?php if (!empty($service['price_note'])): ?>
                                <p class="mt-0.5 text-sm text-[#5a7fa3]"><?php echo e($service['price_note']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <p class="mt-5 text-xs font-semibold uppercase tracking-[0.18em] text-[#2a5a94]">Записаться на приём</p>
                        <p class="mt-1 text-sm text-[#355b89]">Перезвоним в течение 15 минут.</p>

                        <div class="mt-4">
                            <?php echo bioinmed_render_callback_form([
                                'source_label' => ($service['name'] ?? 'Услуга') . ' — сайдбар',
                                'submit_label' => 'Перезвоните мне',
                                'button_class' => 'inline-flex w-full items-center justify-center rounded-full bg-[#2fbdef] px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-[#1fb3d8] disabled:cursor-not-allowed disabled:bg-[#a7d7e9] disabled:text-white/90',
                            ]); ?>
                        </div>

                        <div class="my-4 flex items-center gap-3">
                            <div class="h-px grow bg-[#e2ecf5]"></div>
                            <span class="text-xs text-[#9ab8d4]">или позвоните</span>
                            <div class="h-px grow bg-[#e2ecf5]"></div>
                        </div>
                        <a href="tel:<?php echo $phone1link; ?>"
                           class="flex items-center justify-center gap-2 rounded-full border-2 border-[#2fbdef] px-5 py-2.5 text-sm font-bold text-[#2fbdef] hover:bg-[#f0f8ff]">
                            <i class="fa-solid fa-phone-volume"></i> <?php echo e($phone1); ?>
                        </a>
                    </div>

                    <div class="rounded-3xl border border-[#d9e7f3] bg-white p-6 shadow-[0_12px_30px_rgba(8,36,70,0.10)]">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#4b6f9a]">Как подготовиться к визиту</p>
                        <ul class="mt-3 space-y-2.5 text-sm text-[#214a7f]">
                            <li class="flex items-start gap-2.5">
                                <i class="fa-solid fa-file-medical mt-0.5 shrink-0 text-[#2fbdef]"></i>
                                <span>Возьмите результаты анализов и исследований, если они есть.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <i class="fa-solid fa-clock mt-0.5 shrink-0 text-[#2fbdef]"></i>
                                <span>Первичный приём обычно занимает 60-90 минут.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <i class="fa-solid fa-list-check mt-0.5 shrink-0 text-[#2fbdef]"></i>
                                <span>По итогам вы получите понятный персональный план лечения.</span>
                            </li>
                        </ul>
                                <?php echo bioinmed_render_callback_form([
                                    'source_label' => ($service['name'] ?? 'Услуга') . ' — блок преимуществ',
                                    'submit_label' => 'Оставить заявку',
                                    'form_class' => 'mt-4',
                                    'button_class' => 'mt-4 flex w-full items-center justify-center gap-2 rounded-full bg-[#2fbdef] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#1fb3d8] disabled:cursor-not-allowed disabled:bg-[#a7d7e9] disabled:text-white/90',
                                ]); ?>
                    </div>

                    <div class="rounded-3xl border border-[#d9e7f3] bg-[#f4f9ff] p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#4b6f9a]">Клиника БИОИНМЕД</p>
                        <ul class="mt-3 space-y-2.5 text-sm text-[#214a7f]">
                            <li class="flex items-start gap-2.5">
                                <i class="fa-solid fa-location-dot mt-0.5 shrink-0 text-[#2fbdef]"></i>
                                <span><?php echo e(CLINIC_ADDRESS); ?>, <?php echo e(CLINIC_METRO); ?></span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <i class="fa-solid fa-clock mt-0.5 shrink-0 text-[#2fbdef]"></i>
                                <span><?php echo e(CLINIC_HOURS); ?></span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <i class="fa-solid fa-shield-heart mt-0.5 shrink-0 text-[#2fbdef]"></i>
                                <span>Лицензия МЗ РФ, СанПиН</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <i class="fa-solid fa-user-doctor mt-0.5 shrink-0 text-[#2fbdef]"></i>
                                <span>Врачи с опытом 20–30 лет</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== FINAL CTA STRIP ===== -->
    <section class="border-y border-[#e4edf6] bg-[linear-gradient(90deg,#ecf6ff_0%,#f7fbff_100%)] py-12">
        <div class="mx-auto max-w-6xl px-6 text-center md:px-10">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#2a5a94]">Клиника БИОИНМЕД · <?php echo e(CLINIC_ADDRESS); ?></p>
            <h2 class="mt-3 text-xl font-bold text-[#0f3463] md:text-2xl">Жизнь без боли начинается с первого шага</h2>
            <p class="mx-auto mt-3 max-w-xl text-sm text-[#355b89]">
                Запишитесь на консультацию, чтобы понять причину симптомов и получить персональный план восстановления без лишних назначений.
            </p>
            <div class="mx-auto mt-6 max-w-md">
                <?php echo bioinmed_render_callback_form([
                    'source_label' => ($service['name'] ?? 'Услуга') . ' — финальная CTA',
                    'submit_label' => 'Записаться на приём',
                    'button_class' => 'inline-flex w-full items-center justify-center rounded-full bg-[#2fbdef] px-7 py-3 text-sm font-semibold text-white transition hover:bg-[#1fb3d8] disabled:cursor-not-allowed disabled:bg-[#a7d7e9] disabled:text-white/90',
                ]); ?>
            </div>
            <div class="mt-4 flex flex-wrap justify-center gap-3">
                <a href="tel:<?php echo $phone1link; ?>" class="rounded-full border border-[#c9dcee] bg-white px-7 py-3 text-sm font-semibold text-[#2a5a94] hover:border-[#2fbdef] hover:text-[#2fbdef]">
                    <i class="fa-solid fa-phone mr-1.5"></i><?php echo e($phone1); ?>
                </a>
                <a href="/prices" class="rounded-full border border-[#c9dcee] bg-white px-7 py-3 text-sm font-semibold text-[#2a5a94] hover:border-[#2fbdef] hover:text-[#2fbdef]">
                    <i class="fa-solid fa-list mr-1.5"></i>Все услуги и цены
                </a>
            </div>
        </div>
    </section>

</main>
<?php endif; ?>

<?php if ($servicePrimaryImage): ?>
<div id="service-image-modal" class="fixed inset-0 z-[100] hidden bg-[rgba(7,21,40,0.82)] px-4 py-6">
    <button type="button" id="service-image-modal-close" class="absolute right-5 top-5 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20" aria-label="Закрыть">
        <i class="fa-solid fa-xmark text-lg"></i>
    </button>
    <div class="mx-auto flex h-full max-w-6xl items-center justify-center">
        <img id="service-image-modal-image" src="<?php echo e($servicePrimaryImage); ?>" alt="<?php echo e($service['name']); ?>" class="max-h-full max-w-full rounded-3xl border border-white/15 bg-white/5 object-contain shadow-[0_18px_48px_rgba(0,0,0,0.35)]">
    </div>
</div>
<?php endif; ?>

<?php
$footer = new Footer($brand_colors);
echo $footer->render();
?>

<script>
    // Fade-up on scroll
    document.querySelectorAll('.fade-up').forEach(function(el) {
        const obs = new IntersectionObserver(function(entries) {
            entries.forEach(function(e) { if (e.isIntersecting) { el.classList.add('visible'); obs.unobserve(el); } });
        }, { threshold: 0.08, rootMargin: '0px 0px -30px 0px' });
        obs.observe(el);
    });

    const serviceGallery = <?php echo $serviceGalleryJson ?: '[]'; ?>;
    const mainImage = document.getElementById('service-main-image');
    const zoomButton = document.getElementById('service-image-zoom');
    const galleryThumbs = Array.from(document.querySelectorAll('.service-gallery-thumb'));
    const imageModal = document.getElementById('service-image-modal');
    const imageModalImage = document.getElementById('service-image-modal-image');
    const imageModalClose = document.getElementById('service-image-modal-close');

    function restartServiceImageAnimation() {
        if (!mainImage || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        mainImage.classList.remove('is-animating');
        void mainImage.offsetWidth;
        mainImage.classList.add('is-animating');
    }

    function setActiveGalleryImage(src, alt) {
        if (!mainImage || !src) return;
        mainImage.src = src;
        mainImage.alt = alt || mainImage.alt;
        restartServiceImageAnimation();
        if (imageModalImage) {
            imageModalImage.src = src;
            imageModalImage.alt = alt || imageModalImage.alt;
        }
        galleryThumbs.forEach(function(button) {
            const isActive = button.dataset.imageSrc === src;
            button.classList.toggle('is-active', isActive);
        });
    }

    galleryThumbs.forEach(function(button) {
        button.addEventListener('click', function() {
            setActiveGalleryImage(button.dataset.imageSrc, button.dataset.imageAlt || '');
        });
    });

    function openImageModal() {
        if (!imageModal || !mainImage) return;
        imageModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        if (imageModalImage) {
            imageModalImage.src = mainImage.src;
            imageModalImage.alt = mainImage.alt;
        }
    }

    function closeImageModal() {
        if (!imageModal) return;
        imageModal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    mainImage?.addEventListener('click', openImageModal);
    zoomButton?.addEventListener('click', openImageModal);
    imageModalClose?.addEventListener('click', closeImageModal);
    imageModal?.addEventListener('click', function(event) {
        if (event.target === imageModal) {
            closeImageModal();
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeImageModal();
        }
    });

    restartServiceImageAnimation();
</script>
</body>
</html>
