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

$slug = isset($_GET['slug']) ? trim((string)$_GET['slug']) : '';
$doctor = null;

foreach ($doctors as $item) {
    if (isset($item['slug']) && $item['slug'] === $slug && (!array_key_exists('has_profile', $item) || $item['has_profile'] !== false)) {
        $doctor = $item;
        break;
    }
}

if ($doctor === null) {
    http_response_code(404);
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$pageTitle    = $doctor ? e($doctor['name']) . ' — специалист клиники БИОИНМЕД' : 'Врач не найден | БИОИНМЕД';
$pageDesc     = $doctor
    ? e($doctor['name']) . ' — ' . e($doctor['specialty'] ?? '') . '. Запись на приём в клинику БИОИНМЕД в Москве.'
    : 'Профиль специалиста не найден';
$canonicalUrl = $doctor
    ? $siteUrl . '/doctors/' . rawurlencode((string)$slug)
    : $siteUrl . '/doctors';
$robotsContent = $doctor
    ? 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'
    : 'noindex,follow';
$phone1       = CLINIC_PHONE;
$phone1link   = preg_replace('/\D/', '', $phone1);
$phone2       = defined('CLINIC_PHONE_2') ? CLINIC_PHONE_2 : '';
$phone2link   = $phone2 ? preg_replace('/\D/', '', $phone2) : '';
$doctorImagePath = $doctor && !empty($doctor['image'])
    ? bioinmed_versioned_asset_path('/public/images/team/' . $doctor['image'])
    : '';
$socialImageUrl = $doctor && !empty($doctor['image'])
    ? bioinmed_absolute_url($doctorImagePath)
    : bioinmed_default_social_image_url();
$organizationStructuredData = bioinmed_medical_organization_schema();
$breadcrumbStructuredData = bioinmed_breadcrumb_schema([
    ['name' => 'Главная', 'url' => '/'],
    ['name' => 'Профессиональная команда', 'url' => '/doctors'],
    ['name' => $doctor['name'] ?? 'Профиль врача', 'url' => $canonicalUrl],
]);
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
        'type' => 'profile',
        'image' => $socialImageUrl,
        'image_alt' => $doctor['name'] ?? (CLINIC_NAME . ' — специалист клиники'),
    ]); ?>
    <?php echo bioinmed_render_favicon_links($iconPath); ?>
    <?php if ($doctor): ?>
    <script type="application/ld+json"><?php echo json_encode([
        '@context'  => 'https://schema.org',
        '@type'     => 'Physician',
        'name'      => $doctor['name'],
        'jobTitle'  => $doctor['title'] ?? '',
        'description' => $doctor['bio'] ?? '',
        'worksFor'  => ['@type' => 'MedicalOrganization', 'name' => CLINIC_NAME, 'url' => CLINIC_SITE_URL],
        'image'     => bioinmed_absolute_url($doctorImagePath),
        'url'       => $canonicalUrl,
        'mainEntityOfPage' => $canonicalUrl,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <?php endif; ?>
    <script type="application/ld+json"><?php echo json_encode($organizationStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script type="application/ld+json"><?php echo json_encode($breadcrumbStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        html {
            font-size: clamp(16px, 0.4vw + 14px, 18px);
        }

        body {
            line-height: 1.72;
        }

        .fade-up { opacity: 0; transform: translateY(22px); transition: opacity .55s ease, transform .55s ease; }
        .fade-up.visible { opacity: 1; transform: translateY(0); }
    </style>
    <?php echo bioinmed_uis_counter_head(); ?>
</head>
<body class="flex min-h-screen flex-col bg-[linear-gradient(to_bottom,#f9fcff_0%,#f3f8fd_45%,#eef4fb_100%)] text-[#0f2749] antialiased">
<?php
$header = new Header($brand_colors);
echo $header->render();
?>

<?php if (!$doctor): ?>
<main class="mx-auto max-w-4xl grow px-6 py-20 md:px-10">
    <div class="rounded-3xl border border-[#dbe8f3] bg-white p-10 text-center shadow-[0_16px_40px_rgba(8,36,70,0.08)]">
        <i class="fa-solid fa-user-slash mb-4 text-5xl text-[#b0c8e0]"></i>
        <h1 class="text-3xl font-bold text-[#0f3463]">Профиль врача не найден</h1>
        <p class="mt-3 text-[#355b89]">Проверьте ссылку или вернитесь на главную страницу.</p>
        <a href="/" class="mt-7 inline-flex items-center gap-2 rounded-full bg-[#2fbdef] px-6 py-3 text-sm font-semibold uppercase tracking-[0.08em] text-white hover:bg-[#1fb3d8]">
            <i class="fa-solid fa-house"></i> На главную
        </a>
    </div>
</main>

<?php else: ?>
<main class="grow">

    <!-- ===== HERO ===== -->
    <section class="bg-white border-b border-[#e4edf6]">
        <div class="mx-auto max-w-6xl px-6 py-10 md:px-10 md:py-14">

            <!-- breadcrumb -->
            <nav class="mb-6 flex items-center gap-2 text-xs text-[#7a9cc4]">
                <a href="/" class="hover:text-[#2fbdef]">Главная</a>
                <i class="fa-solid fa-chevron-right text-[0.6rem]"></i>
                <a href="/doctors" class="hover:text-[#2fbdef]">Профессиональная команда</a>
                <i class="fa-solid fa-chevron-right text-[0.6rem]"></i>
                <span class="text-[#0f2749]"><?php echo e($doctor['name']); ?></span>
            </nav>

            <div class="grid items-start gap-7 md:grid-cols-[340px_1fr] lg:grid-cols-[400px_1fr]">

                <!-- photo -->
                <div class="fade-up">
                    <div class="overflow-hidden rounded-3xl border border-[#d9e7f3] shadow-[0_12px_36px_rgba(8,36,70,0.10)]">
                            <img src="<?php echo e($doctorImagePath); ?>"
                             alt="<?php echo e($doctor['name']); ?>"
                             class="h-full w-full object-cover"
                             loading="eager"
                             onerror="this.src='/public/images/placeholder.jpg'">
                    </div>
                </div>

                <!-- info -->
                <div class="fade-up" style="transition-delay:.08s">
                    <h1 class="text-[2rem] font-bold leading-tight text-[#0f3463] md:text-[2.35rem] lg:text-[2.75rem]"><?php echo e($doctor['name']); ?></h1>
                    <p class="mt-2 text-[1.02rem] font-semibold text-[#2a5a94] md:text-[1.12rem]"><?php echo e($doctor['title']); ?></p>

                    <?php if (!empty($doctor['hero_tagline'])): ?>
                    <p class="mt-4 max-w-3xl text-[#4f6f92]" style="font-family:'Caveat',cursive;font-size:clamp(1.52rem,5.2vw,1.9rem);line-height:1.14;font-weight:700;">
                        <?php echo e($doctor['hero_tagline']); ?>
                    </p>
                    <?php else: ?>
                    <p class="mt-4 text-[0.98rem] leading-relaxed text-[#355b89] md:text-[1.03rem]"><?php echo e($doctor['bio']); ?></p>
                    <?php endif; ?>

                    <?php $heroLeadership = trim((string)($doctor['hero_leadership'] ?? ($doctor['leadership'] ?? ''))); ?>
                    <?php if ($heroLeadership !== ''): ?>
                    <p class="mt-3 text-[1.04rem] leading-relaxed text-[#4a6f9c] md:text-[1.1rem]"><?php echo e($heroLeadership); ?></p>
                    <?php endif; ?>

                    <?php $heroHighlights = $doctor['hero_highlights'] ?? []; ?>
                    <?php if (!empty($heroHighlights) && is_array($heroHighlights)): ?>
                    <ul class="mt-4 space-y-2 text-[0.96rem] leading-relaxed text-[#355b89]">
                        <?php foreach ($heroHighlights as $highlight): ?>
                        <li class="flex items-start gap-3">
                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-[#2fbdef]"></span>
                            <span><?php echo e($highlight); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>

                    <div class="mt-6 lg:hidden">
                        <div id="book-mobile" class="fade-up rounded-3xl border border-[#d9e7f3] bg-white p-6 shadow-[0_12px_30px_rgba(8,36,70,0.10)]">
                            <h3 class="mt-2 text-[1.3rem] font-bold leading-tight text-[#0f3463]">Записаться на прием</h3>
                            <p class="mt-2 text-[0.96rem] leading-relaxed text-[#355b89]">
                                Перезвоним в течение 15 минут.
                            </p>

                            <div class="mt-5 space-y-3">
                                <?php echo bioinmed_render_callback_form([
                                    'source_label' => ($doctor['name'] ?? 'Врач') . ' — mobile CTA',
                                    'submit_label' => 'Перезвоните мне',
                                    'button_class' => 'inline-flex w-full items-center justify-center rounded-full bg-[#2fbdef] px-5 py-2.5 text-[0.98rem] font-semibold text-white transition hover:bg-[#1fb3d8] disabled:cursor-not-allowed disabled:bg-[#a7d7e9] disabled:text-white/90',
                                ]); ?>
                            </div>

                            <div class="my-5 flex items-center gap-3">
                                <div class="h-px grow bg-[#e2ecf5]"></div>
                                <span class="text-xs text-[#9ab8d4]">или позвоните напрямую</span>
                                <div class="h-px grow bg-[#e2ecf5]"></div>
                            </div>

                            <a href="tel:<?php echo $phone1link; ?>"
                                      class="flex items-center justify-center gap-2 rounded-full border-2 border-[#2fbdef] px-6 py-3 text-sm font-bold text-[#2fbdef] hover:bg-[#f0f8ff]">
                                <i class="fa-solid fa-phone-volume"></i> <?php echo e($phone1); ?>
                            </a>
                            <?php if ($phone2): ?>
                            <a href="tel:<?php echo $phone2link; ?>"
                               class="mt-2 flex items-center justify-center gap-2 rounded-full border border-[#d6e4f2] px-6 py-2.5 text-sm font-semibold text-[#2a5a94] hover:border-[#2fbdef] hover:text-[#2fbdef]">
                                <i class="fa-solid fa-phone text-xs"></i> <?php echo e($phone2); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== DETAILS ===== -->
    <section class="mx-auto max-w-6xl px-6 py-12 md:px-10 md:py-16">
        <div class="grid gap-8 lg:grid-cols-[1fr_360px]">

            <!-- left column: rich content -->
            <?php $customSections = $doctor['custom_sections'] ?? []; ?>
            <?php $hideStandardSections = !empty($doctor['hide_standard_sections']); ?>
            <?php
            if (!empty($customSections) && is_array($customSections)) {
                $practiceSectionIndex = null;
                foreach ($customSections as $sectionIndex => $sectionData) {
                    if (trim((string)($sectionData['key'] ?? '')) === 'treatment-practice-directions') {
                        $practiceSectionIndex = $sectionIndex;
                        break;
                    }
                }
                if ($practiceSectionIndex !== null && $practiceSectionIndex !== 0) {
                    $practiceSection = $customSections[$practiceSectionIndex];
                    unset($customSections[$practiceSectionIndex]);
                    array_unshift($customSections, $practiceSection);
                }
            }
            ?>
            <?php $customSectionKeys = !empty($customSections) && is_array($customSections) ? array_values(array_filter(array_map(static function ($section) {
                return trim((string)($section['key'] ?? ''));
            }, $customSections))) : []; ?>
            <div class="space-y-6">

                <?php if (!$hideStandardSections && !empty($doctor['specializations']) && is_array($doctor['specializations'])): ?>
                <details class="doctor-section-toggle fade-up group rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]" data-doctor-toggle="specializations">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-7 text-left marker:hidden">
                        <span class="flex items-center gap-2.5 text-[1.1rem] font-bold text-[#0f3463]">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#e8f3fc] text-[#2fbdef]"><i class="fa-solid fa-list-check text-sm"></i></span>
                            <span>Направления деятельности</span>
                        </span>
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[#c9dff1] bg-[#f3f9ff] text-[#2a5a94]">
                            <i class="fa-solid fa-chevron-down text-[0.82rem] transition group-open:rotate-180" aria-hidden="true"></i>
                        </span>
                    </summary>
                    <div class="px-7 pb-7">
                        <ul class="space-y-3">
                            <?php foreach ($doctor['specializations'] as $spec): ?>
                            <li class="flex items-start gap-3 text-sm leading-snug text-[#214a7f]">
                                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-[#2fbdef]"></span>
                                <span><?php echo e($spec); ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </details>
                <?php endif; ?>

                <?php if (!$hideStandardSections && !empty($doctor['focus']) && is_array($doctor['focus'])): ?>
                <details class="doctor-section-toggle fade-up group rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]" data-doctor-toggle="focus">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-7 text-left marker:hidden">
                        <span class="flex items-center gap-2.5 text-xl font-bold text-[#0f3463]">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#e8f3fc] text-[#2fbdef]"><i class="fa-solid fa-bullseye text-sm"></i></span>
                            <span>Профиль деятельности</span>
                        </span>
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[#c9dff1] bg-[#f3f9ff] text-[#2a5a94]">
                            <i class="fa-solid fa-chevron-down text-[0.82rem] transition group-open:rotate-180" aria-hidden="true"></i>
                        </span>
                    </summary>
                    <div class="px-7 pb-7">
                        <ul class="grid gap-3 sm:grid-cols-2">
                            <?php foreach ($doctor['focus'] as $item): ?>
                            <li class="flex items-start gap-3 rounded-xl border border-[#e4edf6] bg-[#f8fcff] p-3 text-[0.96rem] leading-snug text-[#214a7f]">
                                <i class="fa-solid fa-check mt-0.5 shrink-0 text-[#2fbdef]"></i>
                                <span><?php echo e($item); ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </details>
                <?php endif; ?>

                <?php if (!empty($customSections) && is_array($customSections)): ?>
                <?php foreach ($customSections as $section):
                    $sectionKey = trim((string)($section['key'] ?? ''));
                    $sectionTitle = trim((string)($section['title'] ?? ''));
                    $sectionIcon = trim((string)($section['icon'] ?? 'fa-solid fa-circle-info'));
                    $sectionCardClasses = trim((string)($section['card_classes'] ?? 'rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]'));
                    $sectionIconBgClasses = trim((string)($section['icon_bg_classes'] ?? 'bg-[#e8f3fc] text-[#2fbdef]'));
                    $sectionIntro = trim((string)($section['intro'] ?? ''));
                    $sectionText = trim((string)($section['text'] ?? ''));
                    $sectionItems = $section['items'] ?? [];
                    $sectionSubsections = $section['subsections'] ?? [];
                    if ($sectionKey === '' || $sectionTitle === '') {
                        continue;
                    }
                ?>
                <details id="doctor-section-<?php echo e($sectionKey); ?>" class="doctor-section-toggle fade-up group <?php echo e($sectionCardClasses); ?>" data-doctor-toggle="<?php echo e($sectionKey); ?>">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-7 text-left marker:hidden">
                        <span class="flex items-center gap-2.5 text-xl font-bold text-[#0f3463]">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full <?php echo e($sectionIconBgClasses); ?>"><i class="<?php echo e($sectionIcon); ?> text-sm"></i></span>
                            <span><?php echo e($sectionTitle); ?></span>
                        </span>
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[#c9dff1] bg-[#f3f9ff] text-[#2a5a94]">
                            <i class="fa-solid fa-chevron-down text-[0.82rem] transition group-open:rotate-180" aria-hidden="true"></i>
                        </span>
                    </summary>
                    <div class="space-y-5 px-7 pb-7">
                        <?php if ($sectionIntro !== ''): ?>
                        <p class="text-[0.96rem] leading-relaxed text-[#355b89]"><?php echo e($sectionIntro); ?></p>
                        <?php endif; ?>

                        <?php if ($sectionText !== ''): ?>
                        <p class="text-[0.96rem] leading-relaxed text-[#355b89]"><?php echo e($sectionText); ?></p>
                        <?php endif; ?>

                        <?php if (!empty($sectionItems) && is_array($sectionItems)): ?>
                        <ul class="space-y-3 text-[0.96rem] leading-snug text-[#214a7f]">
                            <?php foreach ($sectionItems as $item): ?>
                            <li class="flex items-start gap-3">
                                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-[#2fbdef]"></span>
                                <span><?php echo e($item); ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>

                        <?php if (!empty($sectionSubsections) && is_array($sectionSubsections)): ?>
                        <div class="space-y-5">
                            <?php foreach ($sectionSubsections as $subsection):
                                $subTitle = trim((string)($subsection['title'] ?? ''));
                                $subItems = $subsection['items'] ?? [];
                                if ($subTitle === '' && empty($subItems)) {
                                    continue;
                                }
                            ?>
                            <div class="rounded-2xl border border-[#e4edf6] bg-[#f8fcff] p-4 md:p-5">
                                <?php if ($subTitle !== ''): ?>
                                <h3 class="text-[0.98rem] font-semibold text-[#0f3463] md:text-[1.03rem]"><?php echo e($subTitle); ?></h3>
                                <?php endif; ?>
                                <?php if (!empty($subItems) && is_array($subItems)): ?>
                                <ul class="mt-3 space-y-3 text-[0.96rem] leading-snug text-[#214a7f]">
                                    <?php foreach ($subItems as $subItem): ?>
                                    <li class="flex items-start gap-3">
                                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-[#2fbdef]"></span>
                                        <span><?php echo e($subItem); ?></span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </details>
                <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!in_array('education', $customSectionKeys, true) && !empty($doctor['education'])): ?>
                <details class="doctor-section-toggle fade-up group rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]" data-doctor-toggle="education">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-7 text-left marker:hidden">
                        <span class="flex items-center gap-2.5 text-xl font-bold text-[#0f3463]">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#e8f3fc] text-[#2fbdef]"><i class="fa-solid fa-university text-sm"></i></span>
                            <span>Образование и квалификация</span>
                        </span>
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[#c9dff1] bg-[#f3f9ff] text-[#2a5a94]">
                            <i class="fa-solid fa-chevron-down text-[0.82rem] transition group-open:rotate-180" aria-hidden="true"></i>
                        </span>
                    </summary>
                    <div class="px-7 pb-7">
                        <p class="text-[0.96rem] leading-relaxed text-[#355b89]"><?php echo e($doctor['education']); ?></p>
                    </div>
                </details>
                <?php endif; ?>

                <?php if (!in_array('trust', $customSectionKeys, true)): ?>
                <details class="doctor-section-toggle fade-up group rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]" data-doctor-toggle="trust">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-7 text-left marker:hidden">
                        <span class="flex items-center gap-2.5 text-xl font-bold text-[#0f3463]">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#e8f3fc] text-[#2fbdef]"><i class="fa-solid fa-shield-halved text-sm"></i></span>
                            <span>Почему пациенты выбирают этого специалиста</span>
                        </span>
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[#c9dff1] bg-[#f3f9ff] text-[#2a5a94]">
                            <i class="fa-solid fa-chevron-down text-[0.82rem] transition group-open:rotate-180" aria-hidden="true"></i>
                        </span>
                    </summary>
                    <div class="px-7 pb-7">
                        <ul class="space-y-3 text-[0.96rem] text-[#214a7f]">
                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#2fbdef]"></i><span>Индивидуальный план лечения — никаких шаблонных схем</span></li>
                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#2fbdef]"></i><span>Сочетание классической и интегративной медицины</span></li>
                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#2fbdef]"></i><span>Работает до устойчивого результата, контролирует динамику</span></li>
                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#2fbdef]"></i><span>Принимает в лицензированной клинике с полным оснащением</span></li>
                        </ul>
                    </div>
                </details>
                <?php endif; ?>
            </div>

            <!-- right column: sticky CTA -->
            <div class="hidden lg:block">
                <div id="book" class="fade-up sticky top-24 rounded-3xl border border-[#d9e7f3] bg-white p-6 shadow-[0_12px_30px_rgba(8,36,70,0.10)]">
                    <h3 class="mt-2 text-[1.3rem] font-bold leading-tight text-[#0f3463]">Записаться на прием</h3>
                    <p class="mt-2 text-[0.96rem] leading-relaxed text-[#355b89]">
                        Перезвоним в течение 15 минут.
                    </p>

                    <div class="mt-5 space-y-3">
                        <?php echo bioinmed_render_callback_form([
                            'source_label' => ($doctor['name'] ?? 'Врач') . ' — sticky CTA',
                            'submit_label' => 'Перезвоните мне',
                            'button_class' => 'inline-flex w-full items-center justify-center rounded-full bg-[#2fbdef] px-5 py-2.5 text-[0.98rem] font-semibold text-white transition hover:bg-[#1fb3d8] disabled:cursor-not-allowed disabled:bg-[#a7d7e9] disabled:text-white/90',
                        ]); ?>
                    </div>

                    <!-- divider -->
                    <div class="my-5 flex items-center gap-3">
                        <div class="h-px grow bg-[#e2ecf5]"></div>
                        <span class="text-xs text-[#9ab8d4]">или позвоните напрямую</span>
                        <div class="h-px grow bg-[#e2ecf5]"></div>
                    </div>

                    <a href="tel:<?php echo $phone1link; ?>"
                              class="flex items-center justify-center gap-2 rounded-full border-2 border-[#2fbdef] px-6 py-3 text-sm font-bold text-[#2fbdef] hover:bg-[#f0f8ff]">
                        <i class="fa-solid fa-phone-volume"></i> <?php echo e($phone1); ?>
                    </a>
                    <?php if ($phone2): ?>
                    <a href="tel:<?php echo $phone2link; ?>"
                       class="mt-2 flex items-center justify-center gap-2 rounded-full border border-[#d6e4f2] px-6 py-2.5 text-sm font-semibold text-[#2a5a94] hover:border-[#2fbdef] hover:text-[#2fbdef]">
                        <i class="fa-solid fa-phone text-xs"></i> <?php echo e($phone2); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== SERVICES ===== -->
    <?php
    $doctorServiceIds = $doctor['services'] ?? [];
    $doctorServices = [];
    $servicesMap = [];
    foreach ($services as $srv) {
        $sid = (string)($srv['id'] ?? '');
        if ($sid !== '') {
            $servicesMap[$sid] = $srv;
        }
    }

    if (!empty($doctorServiceIds)) {
        foreach ($doctorServiceIds as $sid) {
            $resolvedSid = isset($service_aliases[$sid]) ? (string)$service_aliases[$sid] : (string)$sid;
            if (isset($servicesMap[$resolvedSid])) {
                $doctorServices[$resolvedSid] = $servicesMap[$resolvedSid];
            }
        }
    }

    // Fallback: if explicit mapping is missing or too short, auto-pick by doctor profile.
    if (count($doctorServices) < 4) {
        $doctorProfileText = mb_strtolower(
            trim((
                ($doctor['title'] ?? '') . ' ' .
                ($doctor['specialty'] ?? '') . ' ' .
                ($doctor['bio'] ?? '') . ' ' .
                implode(' ', $doctor['specializations'] ?? []) . ' ' .
                implode(' ', $doctor['focus'] ?? [])
            )),
            'UTF-8'
        );

        $preferredCategories = [];
        if (preg_match('/психолог|психотерап|тревог|паник|эмоцион/u', $doctorProfileText)) {
            $preferredCategories[] = 'psychology';
        }
        if (preg_match('/остеопат|невролог|мануал|массаж|опорно-двиг/u', $doctorProfileText)) {
            $preferredCategories[] = 'osteopathy';
            $preferredCategories[] = 'manual_therapy';
            $preferredCategories[] = 'physiotherapy';
        }
        if (preg_match('/рефлекс|игло|акупункт|гинек|акушер/u', $doctorProfileText)) {
            $preferredCategories[] = 'reflexotherapy';
            $preferredCategories[] = 'injection_therapy';
        }
        if (preg_match('/главн|костромин/u', $doctorProfileText)) {
            $preferredCategories[] = 'chief_doctor';
            $preferredCategories[] = 'diagnostics';
        }
        if (preg_match('/афк|реабил|двигатель/u', $doctorProfileText)) {
            $preferredCategories[] = 'musculoskeletal';
            $preferredCategories[] = 'physiotherapy';
            $preferredCategories[] = 'taping';
        }

        $preferredCategories = array_values(array_unique($preferredCategories));

        if (!empty($preferredCategories)) {
            foreach ($services as $srv) {
                $sid = (string)($srv['id'] ?? '');
                $cat = (string)($srv['category'] ?? '');
                if ($sid === '' || isset($doctorServices[$sid])) {
                    continue;
                }
                if (in_array($cat, $preferredCategories, true)) {
                    $doctorServices[$sid] = $srv;
                }
                if (count($doctorServices) >= 6) {
                    break;
                }
            }
        }
    }

    if (count($doctorServices) < 4) {
        foreach ($services as $srv) {
            $sid = (string)($srv['id'] ?? '');
            if ($sid === '' || isset($doctorServices[$sid])) {
                continue;
            }
            $doctorServices[$sid] = $srv;
            if (count($doctorServices) >= 6) {
                break;
            }
        }
    }

    $doctorServices = array_values($doctorServices);
    ?>
    <?php if (!empty($doctorServices)): ?>
    <section class="border-t border-[#e4edf6] bg-white py-12">
        <div class="mx-auto max-w-6xl px-6 md:px-10">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#2a5a94]">Специалист клиники БИОИНМЕД</p>
            <h2 class="mt-2 text-xl font-bold text-[#0f3463] md:text-2xl">Релевантные услуги</h2>
            <p class="mt-2 text-sm text-[#4a6f9c]">Услуги, которые чаще всего выбирают на этом приёме</p>

            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($doctorServices as $srv): ?>
                <a href="/services/<?php echo e($srv['id']); ?>"
                   class="fade-up group flex flex-col rounded-2xl border border-[#dce8f5] bg-[#f8fcff] p-5 transition hover:border-[#2fbdef] hover:shadow-[0_6px_20px_rgba(47,189,239,0.12)]">
                    <p class="text-sm font-semibold leading-snug text-[#0f3463] group-hover:text-[#2fbdef]">
                        <?php echo e($srv['name']); ?>
                    </p>
                    <?php if (!empty($srv['description'])): ?>
                    <p class="mt-2 line-clamp-2 text-xs leading-relaxed text-[#5a7fa3]">
                        <?php echo e(mb_substr($srv['description'], 0, 110, 'UTF-8')); ?>…
                    </p>
                    <?php endif; ?>
                    <div class="mt-auto flex items-center justify-between pt-4">
                        <span class="text-xs font-semibold text-[#2fbdef]"><?php echo e($srv['price'] ?? 'Уточнить'); ?></span>
                        <span class="text-xs text-[#9ab8d4] group-hover:text-[#2fbdef]">Подробнее →</span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ===== CTA STRIP ===== -->
    <section class="border-y border-[#e4edf6] bg-[linear-gradient(90deg,#ecf6ff_0%,#f7fbff_100%)] py-12">
        <div class="mx-auto max-w-6xl px-6 text-center md:px-10">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#2a5a94]">Клиника БИОИНМЕД · Москва</p>
            <h2 class="mt-3 text-xl font-bold text-[#0f3463] md:text-2xl">Не откладывайте заботу о здоровье</h2>
            <p class="mx-auto mt-3 max-w-2xl text-sm text-[#355b89]">
                <?php echo e($doctor['name']); ?> принимает в клинике БИОИНМЕД по адресу: <?php echo e(CLINIC_ADDRESS); ?>, <?php echo e(CLINIC_METRO); ?>.
                Запись ежедневно с 9:00 до 21:00.
            </p>
            <div class="mx-auto mt-6 max-w-md">
                <?php echo bioinmed_render_callback_form([
                    'source_label' => ($doctor['name'] ?? 'Врач') . ' — финальная CTA',
                    'submit_label' => 'Записаться на приём',
                    'button_class' => 'inline-flex w-full items-center justify-center rounded-full bg-[#2fbdef] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#1fb3d8] disabled:cursor-not-allowed disabled:bg-[#a7d7e9] disabled:text-white/90',
                ]); ?>
            </div>
            <div class="mt-4 flex flex-wrap justify-center gap-3">
                <a href="tel:<?php echo $phone1link; ?>" class="rounded-full border border-[#2fbdef] px-5 py-2.5 text-sm font-semibold text-[#2fbdef] hover:bg-white">
                    <i class="fa-solid fa-phone mr-1.5"></i><?php echo e($phone1); ?>
                </a>
            </div>
        </div>
    </section>

</main>
<?php endif; ?>

<?php
$footer = new Footer($brand_colors);
echo $footer->render();
?>

<script>
    // Intersection observer for fade-up animations
    document.querySelectorAll('.fade-up').forEach(function(el) {
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) { el.classList.add('visible'); observer.unobserve(el); }
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -30px 0px' });
        observer.observe(el);
    });

    (function() {
        var doctorSlug = <?php echo json_encode((string)$slug, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        var toggles = document.querySelectorAll('.doctor-section-toggle[data-doctor-toggle]');
        if (!doctorSlug || !toggles.length || typeof window.localStorage === 'undefined') {
            return;
        }

        var storageKey = 'bioinmed-doctor-toggles:' + doctorSlug;
        var savedState = {};

        try {
            var rawState = window.localStorage.getItem(storageKey);
            if (rawState) {
                savedState = JSON.parse(rawState) || {};
            }
        } catch (error) {
            savedState = {};
        }

        toggles.forEach(function(toggle) {
            var sectionKey = toggle.getAttribute('data-doctor-toggle');
            if (!sectionKey) {
                return;
            }

            if (Object.prototype.hasOwnProperty.call(savedState, sectionKey)) {
                toggle.open = !!savedState[sectionKey];
            }

            toggle.addEventListener('toggle', function() {
                savedState[sectionKey] = toggle.open;
                try {
                    window.localStorage.setItem(storageKey, JSON.stringify(savedState));
                } catch (error) {
                    // Ignore storage write failures.
                }
            });
        });

        document.querySelectorAll('[data-doctor-open-target]').forEach(function(link) {
            link.addEventListener('click', function(event) {
                var targetId = link.getAttribute('data-doctor-open-target');
                if (!targetId) {
                    return;
                }

                var target = document.getElementById(targetId);
                if (!target) {
                    return;
                }

                event.preventDefault();
                if ('open' in target && !target.open) {
                    target.open = true;
                }
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    })();

</script>
</body>
</html>
