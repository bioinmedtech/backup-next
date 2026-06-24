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

function service_card_excerpt(string $value, int $limit = 88): string {
    $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    if ($value === '') {
        return '';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($value, 'UTF-8') > $limit) {
            return rtrim(mb_substr($value, 0, $limit - 1, 'UTF-8')) . '…';
        }
        return $value;
    }

    if (strlen($value) > $limit) {
        return rtrim(substr($value, 0, $limit - 1)) . '…';
    }

    return $value;
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
$serviceDoctorTitle = trim((string)($service['doctor_title'] ?? ''));
$serviceDoctorName = trim((string)($service['doctor_name'] ?? ''));
$serviceDoctorProjectTitle = trim((string)($service['doctor_project_title'] ?? ''));
$isHobilect = (($service['id'] ?? '') === 'hobilect-diagnostics');
$serviceGalleryJson = json_encode(array_values($serviceGallery), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$faqs_on_page = [
    ['q' => 'Сколько времени занимает первичный приём?',
     'a' => 'Обычно 60–90 минут. Врач собирает анамнез, проводит оценку состояния и формирует маршрут лечения.'],
    ['q' => 'Нужна ли предварительная подготовка?',
     'a' => 'Специальной подготовки не требуется. Возьмите с собой имеющиеся результаты анализов и исследований (если есть). Чистую облегающую одежду, за исключением черного цвета и бархатной ткани.'],
    ['q' => 'Как быстро будет результат?',
     'a' => 'Зависит от Вашего состояния. Часть пациентов отмечают улучшение уже после первой–второй сеанса, при длительной патологии — после курса.'],
    ['q' => 'Можно ли совмещать с другими методами лечения?',
     'a' => 'Да, в клинике Биоинмед комплексный подход: услуги сочетаются и усиливают друг друга. Врач подберёт оптимальную комбинацию.'],
];
$organizationStructuredData = bioinmed_medical_organization_schema();
$breadcrumbStructuredData = bioinmed_breadcrumb_schema([
    ['name' => 'Главная', 'url' => '/'],
    ['name' => 'Услуги', 'url' => '/services'],
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
    <meta name="theme-color" content="#1977b2">
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
    <link rel="stylesheet" href="/public/vendor/fontawesome/css/all.min.css">
    <style>
        html {
            font-size: clamp(17px, 0.5vw + 15px, 19px);
        }

        body {
            line-height: 1.72;
        }

        main p,
        main li {
            line-height: 1.72;
        }

        main .text-xs {
            font-size: 0.8rem;
        }

        main .text-sm {
            font-size: 0.96rem;
        }

        main .text-base {
            font-size: 1.04rem;
        }

        main h1 {
            font-size: clamp(2rem, 2.6vw, 3rem);
            line-height: 1.12;
        }

        main h2 {
            font-size: clamp(1.3rem, 1.7vw, 1.7rem);
            line-height: 1.18;
        }

        .fade-up { opacity: 0; transform: translateY(20px); transition: opacity .5s ease, transform .5s ease; }
        .fade-up.visible { opacity: 1; transform: translateY(0); }
        .prose-service p { margin-bottom: .95rem; line-height: 1.78; }
        .service-gallery-thumb.is-active { border-color: #1977b2; box-shadow: 0 0 0 3px rgba(36, 140, 255, 0.16); }
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
<body class="flex min-h-screen flex-col bg-[#e4f1fa] text-[#0f2749] antialiased">
<?php
$header = new Header($brand_colors);
echo $header->render();
?>

<?php if (!$service): ?>
<main class="mx-auto max-w-4xl grow px-6 py-20 md:px-10">
    <div class="rounded-3xl border border-[#dbe8f3] bg-white p-10 text-center shadow-[0_16px_40px_rgba(8,36,70,0.08)]">
        <i class="fa-solid fa-triangle-exclamation mb-4 text-5xl text-[#b0c8e0]"></i>
        <h1 class="text-3xl font-bold text-[#0a293c]">Услуга не найдена</h1>
        <p class="mt-3 text-[#0a293c]">Проверьте ссылку или перейдите к прайс-листу.</p>
        <div class="mt-7 flex flex-wrap justify-center gap-3">
            <a href="/prices" class="inline-flex items-center gap-2 rounded-full bg-[#1977b2] px-6 py-3 text-sm font-semibold text-white hover:bg-[#16658f]">
                <i class="fa-solid fa-list"></i> Прайс-лист
            </a>
            <a href="/" class="inline-flex items-center gap-2 rounded-full border border-[#c4daed] bg-white px-6 py-3 text-sm font-semibold text-[#0a293c] hover:bg-[#ecf5ff]">
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
                <a href="/" class="hover:text-[#1977b2]">Главная</a>
                <i class="fa-solid fa-chevron-right text-[0.6rem]"></i>
                <a href="/services" class="hover:text-[#1977b2]">Услуги</a>
            </nav>

            <div class="fade-up">
                    <?php if ($serviceDoctorTitle !== '' || $serviceDoctorName !== '' || $serviceDoctorProjectTitle !== ''): ?>
                    <?php if ($serviceDoctorTitle !== ''): ?>
                    <p class="text-[0.78rem] font-semibold uppercase tracking-[0.18em] text-[#0a293c]"><?php echo e($serviceDoctorTitle); ?></p>
                    <?php endif; ?>
                    <?php if ($serviceDoctorName !== ''): ?>
                    <h1 class="mt-2 text-2xl font-bold leading-tight text-[#0a293c] md:text-3xl lg:text-4xl"><?php echo e($serviceDoctorName); ?></h1>
                    <?php else: ?>
                    <h1 class="mt-4 text-2xl font-bold leading-tight text-[#0a293c] md:text-3xl lg:text-4xl"><?php echo e($service['name']); ?></h1>
                    <?php endif; ?>
                    <?php if ($serviceDoctorProjectTitle !== ''): ?>
                    <p class="mt-2.5 text-[0.75rem] font-semibold uppercase tracking-[0.14em] text-[#0a293c]"><?php echo e($serviceDoctorProjectTitle); ?></p>
                    <?php endif; ?>
                    <?php else: ?>
                    <h1 class="mt-4 text-2xl font-bold leading-tight text-[#0a293c] md:text-3xl lg:text-4xl"><?php echo e($service['name']); ?></h1>
                    <?php if (!empty($service['subtitle'])): ?>
                    <p class="mt-2 text-lg font-semibold text-[#0a293c]"><?php echo e($service['subtitle']); ?></p>
                    <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!$isHobilect && !empty($service['description'])): ?>
                    <p class="mt-4 max-w-2xl text-base leading-relaxed text-[#0a293c] md:text-[1.02rem]">
                        <?php echo e($service['description']); ?>
                    </p>
                    <?php endif; ?>

                </div>

            <div class="mt-8 grid items-start gap-8 lg:mt-10 lg:grid-cols-[minmax(0,1fr)_380px]">

                <!-- left: main content -->
                <div class="space-y-6 lg:order-1">

                <!-- Реабилитация «Хабилект» -->
                <?php if ($isHobilect): ?>
                <div class="fade-up">
                    <p class="max-w-3xl text-[0.98rem] leading-relaxed text-[#0a293c] md:text-[1.02rem]">
                        Мультифункциональная медицинская система на базе высокоточных бесконтактных сенсоров. Один комплекс объединяет более 10 решений, от биологической обратной связи и баланс-платформы до гониометра и лаборатории движений, а программные модули и игровые сценарии помогают поддерживать точность и вовлечённость.
                    </p>
                    <p class="mt-3 max-w-3xl text-[0.98rem] leading-relaxed text-[#0a293c] md:text-[1.02rem]">
                        Во время занятия специалист контролирует выполнение, а система фиксирует движения, равновесие, координацию и качество выполнения в реальном времени. Это делает восстановление понятным и наглядным. Методика применяется в неврологии, травматологии, ортопедии, спортивной и детской реабилитации, а также при восстановлении после травм, операций и нарушений походки.
                    </p>
                    <div class="mt-6">
                        <ul class="mt-4 grid gap-3 md:grid-cols-2">
                            <li class="flex items-start gap-3 rounded-2xl border border-[#e4edf6] bg-white p-3.5 text-[0.92rem] leading-relaxed text-[#0a293c] md:p-4">
                                <i class="fa-solid fa-check mt-0.5 text-[#1977b2]" aria-hidden="true"></i>
                                <span>Оптическая сенсорная 3D-диагностика опорно-двигательного аппарата</span>
                            </li>
                            <li class="flex items-start gap-3 rounded-2xl border border-[#e4edf6] bg-white p-3.5 text-[0.92rem] leading-relaxed text-[#0a293c] md:p-4">
                                <i class="fa-solid fa-check mt-0.5 text-[#1977b2]" aria-hidden="true"></i>
                                <span>Создание вашего цифрового двойника для наглядной оценки состояния</span>
                            </li>
                            <li class="flex items-start gap-3 rounded-2xl border border-[#e4edf6] bg-white p-3.5 text-[0.92rem] leading-relaxed text-[#0a293c] md:p-4">
                                <i class="fa-solid fa-check mt-0.5 text-[#1977b2]" aria-hidden="true"></i>
                                <span>Визуализация биомеханики движений в статике и динамике</span>
                            </li>
                            <li class="flex items-start gap-3 rounded-2xl border border-[#e4edf6] bg-white p-3.5 text-[0.92rem] leading-relaxed text-[#0a293c] md:p-4">
                                <i class="fa-solid fa-check mt-0.5 text-[#1977b2]" aria-hidden="true"></i>
                                <span>Подбор персонального маршрута выздоровления по результатам диагностики</span>
                            </li>
                        </ul>
                    </div>
                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <div class="rounded-2xl border border-[#e4edf6] bg-transparent p-4 md:p-5">
                            <p class="text-[0.75rem] font-semibold uppercase tracking-[0.16em] text-[#1977b2]">H.Clinic</p>
                            <ul class="mt-3 space-y-2.5 text-[0.92rem] leading-relaxed text-[#0a293c]">
                                <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Упражнения под контролем специалиста</span></li>
                                <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Фиксация движений, равновесия и координации</span></li>
                                <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Наглядный результат на экране в реальном времени</span></li>
                            </ul>
                        </div>
                        <div class="rounded-2xl border border-[#e4edf6] bg-transparent p-4 md:p-5">
                            <p class="text-[0.75rem] font-semibold uppercase tracking-[0.16em] text-[#1977b2]">H.MotionLAB</p>
                            <p class="mt-3 text-[0.92rem] leading-relaxed text-[#0a293c]">Высокоточная лаборатория движений</p>
                        </div>
                    </div>

                    <div class="mt-6 space-y-4">
                        <details id="hobilect-for-who" class="group rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-7 text-left marker:hidden">
                                <span class="flex items-center gap-2.5 text-[1.05rem] font-bold text-[#0a293c] md:text-[1.12rem]">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#1977b2]"><i class="fa-solid fa-user-group text-xs"></i></span>
                                    Области применения
                                </span>
                                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#cfe0ef] bg-white text-[#0a293c]">
                                    <i class="fa-solid fa-chevron-down text-[0.72rem] transition group-open:rotate-180"></i>
                                </span>
                            </summary>
                            <div class="space-y-5 px-7 pb-7">
                                <p class="text-[0.96rem] leading-relaxed text-[#0a293c]">«Хабилект» используется на всех этапах реабилитации: в стационарных учреждениях, в санаториях, спорте, а также для оценки профессиональных заболеваний, в неврологии, травматологии, ортопедии, при оценке риска падения у пожилых и в детской реабилитации.</p>
                                <div class="mt-4 grid gap-3 md:grid-cols-2">
                                    <div class="rounded-xl border border-[#e4edf6] bg-white p-4">
                                        <ul class="space-y-3 text-[0.96rem] leading-snug text-[#0a293c]">
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Нарушения походки и снижение устойчивости</span></li>
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Нарушения координации движений и равновесия</span></li>
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Восстановление после травм и операций</span></li>
                                        </ul>
                                    </div>
                                    <div class="rounded-xl border border-[#e4edf6] bg-white p-4">
                                        <ul class="space-y-3 text-[0.96rem] leading-snug text-[#0a293c]">
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Неврологические, ортопедические и травматологические состояния</span></li>
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Детская и спортивная реабилитация</span></li>
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Контроль риска падений и двигательных нарушений</span></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </details>

                        <details id="hobilect-assessment" class="group rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-7 text-left marker:hidden">
                                <span class="flex items-center gap-2.5 text-[1.05rem] font-bold text-[#0a293c] md:text-[1.12rem]">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#1977b2]"><i class="fa-solid fa-chart-column text-xs"></i></span>
                                    Что оценивает система
                                </span>
                                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#cfe0ef] bg-white text-[#0a293c]">
                                    <i class="fa-solid fa-chevron-down text-[0.72rem] transition group-open:rotate-180"></i>
                                </span>
                            </summary>
                            <div class="space-y-5 px-7 pb-7">
                                <p class="text-[0.96rem] leading-relaxed text-[#0a293c]">Система анализа движений помогает врачу получить объективные данные о пациенте в статике и динамике. В основе - более 80 параметров биомеханики и их корреляции, на которых строится дальнейшая тактика восстановления.</p>
                                <div class="mt-4 grid gap-3 md:grid-cols-2">
                                    <div class="rounded-xl border border-[#e4edf6] bg-white p-4">
                                        <ul class="space-y-3 text-[0.96rem] leading-snug text-[#0a293c]">
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Походку, симметричность движений и центр тяжести</span></li>
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Равновесие, устойчивость и координацию</span></li>
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Движения в суставах и параметры движения в трёх плоскостях</span></li>
                                        </ul>
                                    </div>
                                    <div class="rounded-xl border border-[#e4edf6] bg-white p-4">
                                        <ul class="space-y-3 text-[0.96rem] leading-snug text-[#0a293c]">
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Качество выполнения упражнений и динамику восстановления</span></li>
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Патологические паттерны походки и слабые места опоры</span></li>
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Изменение центра тяжести в статике и динамике</span></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </details>

                        <details id="hobilect-process" class="group rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-7 text-left marker:hidden">
                                <span class="flex items-center gap-2.5 text-[1.05rem] font-bold text-[#0a293c] md:text-[1.12rem]">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#1977b2]"><i class="fa-solid fa-person-walking text-xs"></i></span>
                                    Программные модули
                                </span>
                                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#cfe0ef] bg-white text-[#0a293c]">
                                    <i class="fa-solid fa-chevron-down text-[0.72rem] transition group-open:rotate-180"></i>
                                </span>
                            </summary>
                            <div class="space-y-5 px-7 pb-7">
                                <p class="text-[0.96rem] leading-relaxed text-[#0a293c]">Комплекс состоит из разных по назначению программных модулей, каждый решает свою задачу, но все они объединены в одном корпусе. Мы постоянно обновляем модули, добавляем новые игровые сценарии и улучшаем точность отображаемых данных.</p>
                                <div class="mt-4 grid gap-4 md:grid-cols-2">
                                    <div class="rounded-xl border border-[#e4edf6] bg-white p-4">
                                        <p class="text-[0.84rem] font-semibold uppercase tracking-[0.12em] text-[#1977b2]">H.Clinic</p>
                                        <ul class="mt-3 space-y-3 text-[0.96rem] leading-snug text-[#0a293c]">
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Реабилитация с упражнениями, играми и биологической обратной связью</span></li>
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Визуальная обратная связь для контроля качества движения</span></li>
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Подходит для занятий стоя, сидя и с дополнительным инвентарём</span></li>
                                        </ul>
                                    </div>
                                    <div class="rounded-xl border border-[#e4edf6] bg-white p-4">
                                        <p class="text-[0.84rem] font-semibold uppercase tracking-[0.12em] text-[#1977b2]">H.MotionLAB</p>
                                        <ul class="mt-3 space-y-3 text-[0.96rem] leading-snug text-[#0a293c]">
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Высокоточная лаборатория движений</span></li>
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Анализ более 80 биомеханических параметров</span></li>
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Автоматические отчёты и нулевое время подготовки к тесту</span></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </details>

                        <details id="hobilect-biofeedback" class="group rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-7 text-left marker:hidden">
                                <span class="flex items-center gap-2.5 text-[1.05rem] font-bold text-[#0a293c] md:text-[1.12rem]">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#1977b2]"><i class="fa-solid fa-circle-nodes text-xs"></i></span>
                                    Игровые сценарии и биологическая обратная связь
                                </span>
                                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#cfe0ef] bg-white text-[#0a293c]">
                                    <i class="fa-solid fa-chevron-down text-[0.72rem] transition group-open:rotate-180"></i>
                                </span>
                            </summary>
                            <div class="space-y-5 px-7 pb-7">
                                <p class="text-[0.96rem] leading-relaxed text-[#0a293c]">В системе встроены игровые сценарии для взрослых и детских центров реабилитации. Пациент может выполнять задания с биологической обратной связью, с дополненной реальностью, тактильно или следя за движениями в зеркале.</p>
                                <ul class="mt-4 grid gap-3 md:grid-cols-2 text-[0.96rem] leading-snug text-[#0a293c]">
                                    <li class="flex items-start gap-3 rounded-xl border border-[#e4edf6] bg-white p-3.5"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Улучшение контроля движений</span></li>
                                    <li class="flex items-start gap-3 rounded-xl border border-[#e4edf6] bg-white p-3.5"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Повышение осознанности выполнения упражнений</span></li>
                                    <li class="flex items-start gap-3 rounded-xl border border-[#e4edf6] bg-white p-3.5"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Закрепление правильного двигательного навыка</span></li>
                                    <li class="flex items-start gap-3 rounded-xl border border-[#e4edf6] bg-white p-3.5"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Рост мотивации и понятный прогресс в динамике</span></li>
                                </ul>
                            </div>
                        </details>

                        <details id="hobilect-games" class="group rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-7 text-left marker:hidden">
                                <span class="flex items-center gap-2.5 text-[1.05rem] font-bold text-[#0a293c] md:text-[1.12rem]">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#1977b2]"><i class="fa-solid fa-gamepad text-xs"></i></span>
                                    Игровые упражнения
                                </span>
                                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#cfe0ef] bg-white text-[#0a293c]">
                                    <i class="fa-solid fa-chevron-down text-[0.72rem] transition group-open:rotate-180"></i>
                                </span>
                            </summary>
                            <div class="space-y-5 px-7 pb-7">
                                <p class="text-[0.96rem] leading-relaxed text-[#0a293c]">Игровой формат помогает сделать реабилитацию более вовлекающей, особенно для детей и пациентов, которым сложно сохранять интерес к однотипным упражнениям.</p>
                                <ul class="mt-4 grid gap-3 md:grid-cols-3 text-[0.96rem] leading-snug text-[#0a293c]">
                                    <li class="flex items-start gap-3 rounded-xl border border-[#e4edf6] bg-white p-3.5"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Разные уровни сложности и типы движений</span></li>
                                    <li class="flex items-start gap-3 rounded-xl border border-[#e4edf6] bg-white p-3.5"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Сценарии под задачи реабилитации</span></li>
                                    <li class="flex items-start gap-3 rounded-xl border border-[#e4edf6] bg-white p-3.5"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Повышение вовлечённости без потери лечебной цели</span></li>
                                </ul>
                            </div>
                        </details>

                        <details id="hobilect-reports" class="group rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-7 text-left marker:hidden">
                                <span class="flex items-center gap-2.5 text-[1.05rem] font-bold text-[#0a293c] md:text-[1.12rem]">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#1977b2]"><i class="fa-solid fa-file-waveform text-xs"></i></span>
                                    Наглядные отчёты
                                </span>
                                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#cfe0ef] bg-white text-[#0a293c]">
                                    <i class="fa-solid fa-chevron-down text-[0.72rem] transition group-open:rotate-180"></i>
                                </span>
                            </summary>
                            <div class="space-y-5 px-7 pb-7">
                                <p class="text-[0.96rem] leading-relaxed text-[#0a293c]">«Хабилект» даёт наглядные отчёты, оценку в статике и динамике, мощные инструменты анализа, а также запись и воспроизведение пробы.</p>
                                <div class="mt-4 grid gap-3 md:grid-cols-2">
                                    <div class="rounded-xl border border-[#e4edf6] bg-white p-4">
                                        <ul class="space-y-3 text-[0.96rem] leading-snug text-[#0a293c]">
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Запись и воспроизведение проб</span></li>
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Анализ в статике и динамике</span></li>
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Графики, таблицы и сравнение результатов в динамике</span></li>
                                        </ul>
                                    </div>
                                    <div class="rounded-xl border border-[#e4edf6] bg-white p-4">
                                        <ul class="space-y-3 text-[0.96rem] leading-snug text-[#0a293c]">
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Подготовка отчётов для контроля лечения</span></li>
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Оценка симметричности движений и баланса</span></li>
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Понятная база для корректировки программы</span></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </details>

                        <details id="hobilect-benefits" class="group rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-7 text-left marker:hidden">
                                <span class="flex items-center gap-2.5 text-[1.05rem] font-bold text-[#0a293c] md:text-[1.12rem]">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#1977b2]"><i class="fa-solid fa-award text-xs"></i></span>
                                    Нормативные документы
                                </span>
                                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#cfe0ef] bg-white text-[#0a293c]">
                                    <i class="fa-solid fa-chevron-down text-[0.72rem] transition group-open:rotate-180"></i>
                                </span>
                            </summary>
                            <div class="space-y-5 px-7 pb-7">
                                <p class="text-[0.96rem] leading-relaxed text-[#0a293c]">«Хабилект» включён в нормативную базу и применяется как медицинское оборудование в клинической практике. Это не просто технологическая платформа, а система, на которую можно опираться в реабилитационных маршрутах и при оснащении медицинских подразделений.</p>
                                <div class="mt-4 grid gap-3 md:grid-cols-2">
                                    <div class="rounded-xl border border-[#e4edf6] bg-white p-4">
                                        <p class="text-[0.84rem] font-semibold uppercase tracking-[0.14em] text-[#1977b2]">Приказы МЗ РФ</p>
                                        <ul class="mt-3 space-y-3 text-[0.96rem] leading-snug text-[#0a293c]">
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Приказ МЗ РФ от 22.02.2019 № 90н - оснащение региональных сосудистых центров и первичных сосудистых отделений</span></li>
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Приказ МЗ РФ от 23.10.2019 № 878н - порядок организации медицинской реабилитации детей</span></li>
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Приказ МЗ РФ от 31.07.2020 № 788н - порядок организации медицинской реабилитации взрослых</span></li>
                                        </ul>
                                    </div>
                                    <div class="rounded-xl border border-[#e4edf6] bg-white p-4">
                                        <p class="text-[0.84rem] font-semibold uppercase tracking-[0.14em] text-[#1977b2]">Клинический контур</p>
                                        <ul class="mt-3 space-y-3 text-[0.96rem] leading-snug text-[#0a293c]">
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Используется в рамках реабилитации взрослых и детей</span></li>
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Поддерживает стандарты оснащения медицинских организаций</span></li>
                                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i><span>Позволяет внедрять единый подход к диагностике и восстановлению</span></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </details>

                        <details id="hobilect-patient-result" class="group rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-7 text-left marker:hidden">
                                <span class="flex items-center gap-2.5 text-[1.05rem] font-bold text-[#0a293c] md:text-[1.12rem]">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#1977b2]"><i class="fa-solid fa-clipboard-check text-xs"></i></span>
                                    Основные показатели
                                </span>
                                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#cfe0ef] bg-white text-[#0a293c]">
                                    <i class="fa-solid fa-chevron-down text-[0.72rem] transition group-open:rotate-180"></i>
                                </span>
                            </summary>
                            <div class="space-y-5 px-7 pb-7">
                                <p class="text-[0.96rem] leading-relaxed text-[#0a293c]">80 параметров оценки биомеханики и их корреляции. 5 секунд необходимо для подготовки к проведению пробы. 15 секунд для подготовки отчётов к печати или импорту в МИС. Длительность пробы - любая, от нескольких секунд до часов.</p>
                                <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                    <div class="rounded-2xl border border-[#e4edf6] bg-white p-4 text-center">
                                        <p class="text-3xl font-bold text-[#1977b2]">80</p>
                                        <p class="mt-2 text-sm leading-relaxed text-[#0a293c]">параметров оценки биомеханики и их корреляции</p>
                                    </div>
                                    <div class="rounded-2xl border border-[#e4edf6] bg-white p-4 text-center">
                                        <p class="text-3xl font-bold text-[#1977b2]">5</p>
                                        <p class="mt-2 text-sm leading-relaxed text-[#0a293c]">секунд необходимо для подготовки к проведению пробы</p>
                                    </div>
                                    <div class="rounded-2xl border border-[#e4edf6] bg-white p-4 text-center">
                                        <p class="text-3xl font-bold text-[#1977b2]">15</p>
                                        <p class="mt-2 text-sm leading-relaxed text-[#0a293c]">секунд для подготовки отчётов к печати или импорту в МИС</p>
                                    </div>
                                    <div class="rounded-2xl border border-[#e4edf6] bg-white p-4 text-center">
                                        <p class="text-3xl font-bold text-[#1977b2]">∞</p>
                                        <p class="mt-2 text-sm leading-relaxed text-[#0a293c]">любая длительность пробы, от нескольких секунд до часов</p>
                                    </div>
                                </div>
                            </div>
                        </details>

                    </div>
                </div>
                <?php else: ?>
                <!-- Как проходит приём и кому показана услуга -->
                <details class="fade-up group rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-7 text-left marker:hidden">
                        <span class="flex items-center gap-2.5 text-xl font-bold text-[#0a293c]">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#1977b2]">
                                <i class="fa-solid fa-circle-play text-sm"></i>
                            </span>
                            <span>Как проходит приём и кому показана услуга</span>
                        </span>
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[#c9dff1] bg-white text-[#0a293c]">
                            <i class="fa-solid fa-chevron-down text-[0.82rem] transition group-open:rotate-180" aria-hidden="true"></i>
                        </span>
                    </summary>
                    <div class="space-y-5 px-7 pb-7">
                        <?php if (!empty($service['details'])): ?>
                        <p class="text-[0.96rem] leading-relaxed text-[#0a293c]"><?php echo e($service['details']); ?></p>
                        <?php endif; ?>
                        <ol class="space-y-3">
                            <li class="flex items-start gap-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#1977b2] text-xs font-bold text-white">1</span>
                                <span class="text-sm text-[#0a293c] mt-0.5">Первичная консультация — врач собирает анамнез и оценивает состояние</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#1977b2] text-xs font-bold text-white">2</span>
                                <span class="text-sm text-[#0a293c] mt-0.5">Диагностика — функциональная оценка, при необходимости инструментальная</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#1977b2] text-xs font-bold text-white">3</span>
                                <span class="text-sm text-[#0a293c] mt-0.5">Составление персонального маршрута лечения с конкретными целями</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#1977b2] text-xs font-bold text-white">4</span>
                                <span class="text-sm text-[#0a293c] mt-0.5">Проведение курса — с контролем динамики и корректировкой маршрута</span>
                            </li>
                        </ol>
                        <?php if (!empty($service['target'])): ?>
                        <div class="rounded-2xl border border-[#dce8f5] bg-[#f8fbff] p-4">
                            <h3 class="text-[0.92rem] font-semibold uppercase tracking-[0.14em] text-[#0a293c]">Кому показана услуга</h3>
                            <p class="mt-2 text-sm leading-relaxed text-[#0a293c]"><?php echo e($service['target']); ?></p>
                            <div class="mt-4 rounded-xl border border-[#dce8f5] bg-white p-4 text-sm text-[#0a293c]">
                                <i class="fa-solid fa-circle-info text-[#1977b2] mr-2"></i>
                                Точные показания определяет врач на первичной консультации. Запишитесь - первый приём займёт 60-90 минут.
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </details>
                <?php endif; ?>

                <!-- Why BIOINMED -->
                <details class="fade-up group rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 text-left marker:hidden md:p-6">
                        <span class="flex items-center gap-2.5 text-[1.1rem] font-bold text-[#0a293c] md:text-[1.22rem]">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#dceefb] text-[#1977b2]">
                                <i class="fa-solid fa-award text-sm"></i>
                            </span>
                            <span>О клинике БИОИНМЕД</span>
                        </span>
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[#c9dff1] bg-white text-[#0a293c]">
                            <i class="fa-solid fa-chevron-down text-[0.82rem] transition group-open:rotate-180" aria-hidden="true"></i>
                        </span>
                    </summary>
                    <div class="px-5 pb-5 md:px-6 md:pb-6">
                        <ul class="grid gap-3 sm:grid-cols-2">
                            <li class="flex items-start gap-3 rounded-xl border border-[#e4edf6] bg-white p-3 text-[0.9rem] text-[#0a293c] md:text-[0.92rem]">
                                <i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i>Мы с Вами от первого касания до результата
                            </li>
                            <li class="flex items-start gap-3 rounded-xl border border-[#e4edf6] bg-white p-3 text-[0.9rem] text-[#0a293c] md:text-[0.92rem]">
                                <i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i>Биоинмед - Ваш комплексный персональный маршрут лечения
                            </li>
                            <li class="flex items-start gap-3 rounded-xl border border-[#e4edf6] bg-white p-3 text-[0.9rem] text-[#0a293c] md:text-[0.92rem]">
                                <i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i>Объясняем диагноз и маршрут лечения простым понятным языком
                            </li>
                            <li class="flex items-start gap-3 rounded-xl border border-[#e4edf6] bg-white p-3 text-[0.9rem] text-[#0a293c] md:text-[0.92rem]">
                                <i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i>Контролируем динамику на каждом этапе и корректируем курс
                            </li>
                            <li class="flex items-start gap-3 rounded-xl border border-[#e4edf6] bg-white p-3 text-[0.9rem] text-[#0a293c] md:text-[0.92rem]">
                                <i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i>Врачи с 20-30-летним опытом и клиника с лицензией МЗ РФ
                            </li>
                            <li class="flex items-start gap-3 rounded-xl border border-[#e4edf6] bg-white p-3 text-[0.9rem] text-[#0a293c] md:text-[0.92rem]">
                                <i class="fa-solid fa-check mt-0.5 text-[#1977b2]"></i>Прозрачные цены и быстрый контакт с клиникой без ожидания
                            </li>
                        </ul>
                    </div>
                </details>

                <!-- FAQ mini -->
                <details class="fade-up group rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 text-left marker:hidden md:p-6">
                        <span class="flex items-center gap-2.5 text-[1.1rem] font-bold text-[#0a293c] md:text-[1.22rem]">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#1977b2]">
                                <i class="fa-solid fa-circle-question text-sm"></i>
                            </span>
                            <span>Часто задаваемые вопросы</span>
                        </span>
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[#c9dff1] bg-white text-[#0a293c]">
                            <i class="fa-solid fa-chevron-down text-[0.82rem] transition group-open:rotate-180" aria-hidden="true"></i>
                        </span>
                    </summary>
                    <div class="space-y-4 px-5 pb-5 md:px-6 md:pb-6" id="faq-list">
                        <?php foreach ($faqs_on_page as $i => $faq): ?>
                        <details class="group rounded-2xl border border-[#e4edf6] bg-white">
                            <summary class="flex cursor-pointer items-center justify-between gap-4 p-4 text-[0.98rem] font-semibold text-[#0a293c] marker:hidden list-none md:p-5 md:text-[1.04rem]">
                                <?php echo e($faq['q']); ?>
                                <i class="fa-solid fa-chevron-down shrink-0 text-xs text-[#1977b2] transition-transform group-open:rotate-180"></i>
                            </summary>
                            <p class="px-4 pb-4 text-[0.92rem] leading-relaxed text-[#0a293c] md:px-5 md:pb-5 md:text-[0.96rem]"><?php echo e($faq['a']); ?></p>
                        </details>
                        <?php endforeach; ?>
                    </div>
                </details>

                <!-- Related services -->
                <?php if (!empty($related)): ?>
                <div class="fade-up">
                    <h2 class="text-xl font-bold text-[#0a293c]">Другие услуги клиники</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <?php foreach (array_slice($related, 0, 4) as $rel): ?>
                        <a href="/services/<?php echo e($rel['id']); ?>"
                           class="flex flex-col justify-between rounded-2xl border border-[#dce8f5] bg-white p-5 hover:border-[#1977b2] hover:shadow-md transition-all">
                            <div>
                                <p class="text-sm font-semibold leading-snug text-[#0a293c]"><?php echo e($rel['name']); ?></p>
                                <p class="mt-1 text-xs text-[#0a293c]"><?php echo e(service_card_excerpt((string)($rel['card_description'] ?? $rel['description'] ?? ''), 72)); ?></p>
                            </div>
                            <div class="mt-4 flex items-center justify-between">
                                <span class="text-sm font-bold text-[#1977b2]"><?php echo e($rel['price'] ?? ''); ?></span>
                                <span class="text-xs font-semibold text-[#1977b2]">Подробнее <i class="fa-solid fa-arrow-right text-[0.65rem]"></i></span>
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
                                <i class="fa-solid fa-magnifying-glass-plus text-[#1977b2]"></i>
                            </button>
                        </div>
                        <?php if (!empty($serviceGallery)): ?>
                        <div class="grid grid-cols-4 gap-2 border-t border-[#e7eef6] bg-[#f8fbff] p-3">
                            <?php foreach ($serviceGallery as $galleryIndex => $galleryImage): ?>
                            <button type="button"
                                    class="service-gallery-thumb <?php echo $galleryIndex === 0 ? 'is-active' : ''; ?> overflow-hidden rounded-2xl border border-[#dfe7f1] bg-white aspect-[4/3] transition hover:border-[#1977b2]"
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
                                <p class="mt-1 text-3xl font-bold text-[#0a293c]"><?php echo e($service['price'] ?? 'По запросу'); ?></p>
                                <?php if (!empty($service['price_note'])): ?>
                                <p class="mt-0.5 text-sm text-[#0a293c]"><?php echo e($service['price_note']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <p class="mt-5 text-xs font-semibold uppercase tracking-[0.18em] text-[#0a293c]">Записаться на приём</p>
                        <p class="mt-1 text-sm text-[#0a293c]">Перезвоним в течение 15 минут.</p>

                        <div class="mt-4">
                            <?php echo bioinmed_render_callback_form([
                                'source_label' => ($service['name'] ?? 'Услуга') . ' — сайдбар',
                                'submit_label' => 'Перезвоните мне',
                                'button_class' => 'inline-flex w-full items-center justify-center rounded-full bg-[#1977b2] px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-[#16658f] disabled:cursor-not-allowed disabled:bg-[#a7d7e9] disabled:text-white/90',
                            ]); ?>
                        </div>

                        <div class="my-4 flex items-center gap-3">
                            <div class="h-px grow bg-[#e2ecf5]"></div>
                            <span class="text-xs text-[#0a293c]">или позвоните</span>
                            <div class="h-px grow bg-[#e2ecf5]"></div>
                        </div>
                        <a href="tel:<?php echo $phone1link; ?>"
                           class="flex items-center justify-center gap-2 rounded-full border-2 border-[#1977b2] px-5 py-2.5 text-sm font-bold text-[#1977b2] hover:bg-[#f0f8ff]">
                            <i class="fa-solid fa-phone-volume"></i> <?php echo e($phone1); ?>
                        </a>
                    </div>

                    <div class="rounded-3xl border border-[#d9e7f3] bg-white p-6 shadow-[0_12px_30px_rgba(8,36,70,0.10)]">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#4b6f9a]">Как подготовиться к визиту</p>
                        <ul class="mt-3 space-y-2.5 text-sm text-[#0a293c]">
                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#1977b2]">
                                    <i class="fa-solid fa-file-medical text-[0.68rem]"></i>
                                </span>
                                <span>Возьмите результаты анализов и исследований, если они есть.</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#1977b2]">
                                    <i class="fa-solid fa-clock text-[0.68rem]"></i>
                                </span>
                                <span>Первичный приём обычно занимает 60-90 минут.</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#1977b2]">
                                    <i class="fa-solid fa-list-check text-[0.68rem]"></i>
                                </span>
                                <span>По итогам Вы получите понятный персональный маршрут лечения.</span>
                            </li>
                        </ul>
                    </div>

                    <div class="rounded-3xl border border-[#d9e7f3] bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#0a293c]">Клиника БИОИНМЕД</p>
                        <ul class="mt-3 space-y-2.5 text-sm text-[#0a293c]">
                            <li class="flex items-start gap-2.5">
                                <i class="fa-solid fa-location-dot mt-0.5 shrink-0 text-[#1977b2]"></i>
                                <span><?php echo e(CLINIC_ADDRESS); ?>, <?php echo e(CLINIC_METRO); ?></span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <i class="fa-solid fa-clock mt-0.5 shrink-0 text-[#1977b2]"></i>
                                <span><?php echo e(CLINIC_HOURS); ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== FINAL CTA STRIP ===== -->
    <section class="border-y border-[#e4edf6] bg-[#e4f1fa] py-12">
        <div class="mx-auto max-w-6xl px-6 text-center md:px-10">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0a293c]">Клиника БИОИНМЕД · <?php echo e(CLINIC_ADDRESS); ?></p>
            <h2 class="mt-3 text-xl font-bold text-[#0a293c] md:text-2xl">Жизнь без боли начинается с первого шага</h2>
            <p class="mx-auto mt-3 max-w-xl text-sm text-[#0a293c]">
                Запишитесь на консультацию, чтобы понять причину симптомов и получить персональный план восстановления без лишних назначений.
            </p>
            <div class="mx-auto mt-6 max-w-md">
                <?php echo bioinmed_render_callback_form([
                    'source_label' => ($service['name'] ?? 'Услуга') . ' — финальная CTA',
                    'submit_label' => 'Записаться на приём',
                    'button_class' => 'inline-flex w-full items-center justify-center rounded-full bg-[#1977b2] px-7 py-3 text-sm font-semibold text-white transition hover:bg-[#16658f] disabled:cursor-not-allowed disabled:bg-[#a7d7e9] disabled:text-white/90',
                ]); ?>
            </div>
            <div class="mt-4 flex flex-wrap justify-center gap-3">
                <a href="tel:<?php echo $phone1link; ?>" class="rounded-full border border-[#c9dcee] bg-white px-7 py-3 text-sm font-semibold text-[#0a293c] hover:border-[#1977b2] hover:text-[#1977b2]">
                    <i class="fa-solid fa-phone mr-1.5"></i><?php echo e($phone1); ?>
                </a>
                <a href="/prices" class="rounded-full border border-[#c9dcee] bg-white px-7 py-3 text-sm font-semibold text-[#0a293c] hover:border-[#1977b2] hover:text-[#1977b2]">
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

    function openDetailsTarget(hash) {
        if (!hash) return false;
        var target = document.querySelector(hash);
        if (!target) return false;

        var details = target.tagName && target.tagName.toLowerCase() === 'details'
            ? target
            : target.closest('details');

        if (details) {
            details.open = true;
            window.requestAnimationFrame(function() {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
            return true;
        }

        return false;
    }

    function syncDetailsAnchor() {
        if (!window.location.hash) return;
        openDetailsTarget(window.location.hash);
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

    document.querySelectorAll('a[href^="#hobilect-"]').forEach(function(link) {
        link.addEventListener('click', function(event) {
            var hash = link.getAttribute('href');
            if (!hash || hash === '#') return;
            if (openDetailsTarget(hash)) {
                event.preventDefault();
                history.replaceState(null, '', hash);
            }
        });
    });

    window.addEventListener('hashchange', syncDetailsAnchor);
    syncDetailsAnchor();

    restartServiceImageAnimation();
</script>
</body>
</html>
