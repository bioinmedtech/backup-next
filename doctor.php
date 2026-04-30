<?php
require_once 'config.php';
require_once 'includes/components/Components.php';

$siteUrl = rtrim(CLINIC_SITE_URL, '/');
$iconPath = CLINIC_ICON_PATH;
$iconUrl = $siteUrl . $iconPath;

$slug = isset($_GET['slug']) ? trim((string)$_GET['slug']) : '';
$doctor = null;

foreach ($doctors as $item) {
    if (isset($item['slug']) && $item['slug'] === $slug) {
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
$experienceText = trim((string)($doctor['experience'] ?? ''));
$experienceYears = null; // numeric years only
if (preg_match('/(\d+)\s*(?:лет|год)/ui', $experienceText, $experienceMatch)) {
    $experienceYears = $experienceMatch[1];
}
$socialImageUrl = $doctor && !empty($doctor['image'])
    ? bioinmed_absolute_url('/public/images/team/' . $doctor['image'])
    : bioinmed_default_social_image_url();
$organizationStructuredData = bioinmed_medical_organization_schema();
$breadcrumbStructuredData = bioinmed_breadcrumb_schema([
    ['name' => 'Главная', 'url' => '/'],
    ['name' => 'Специалисты', 'url' => '/doctors'],
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
    <meta property="og:locale" content="ru_RU">
    <meta property="og:site_name" content="<?php echo e(CLINIC_NAME); ?>">
    <meta property="og:type" content="profile">
    <meta property="og:title" content="<?php echo $pageTitle; ?>">
    <meta property="og:description" content="<?php echo $pageDesc; ?>">
    <meta property="og:url" content="<?php echo e($canonicalUrl); ?>">
    <meta property="og:image" content="<?php echo e($socialImageUrl); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $pageTitle; ?>">
    <meta name="twitter:description" content="<?php echo $pageDesc; ?>">
    <meta name="twitter:image" content="<?php echo e($socialImageUrl); ?>">
    <link rel="icon" type="image/png" href="<?php echo $iconPath; ?>">
    <link rel="apple-touch-icon" href="<?php echo $iconPath; ?>">
    <?php if ($doctor): ?>
    <script type="application/ld+json"><?php echo json_encode([
        '@context'  => 'https://schema.org',
        '@type'     => 'Physician',
        'name'      => $doctor['name'],
        'jobTitle'  => $doctor['title'] ?? '',
        'description' => $doctor['bio'] ?? '',
        'worksFor'  => ['@type' => 'MedicalOrganization', 'name' => CLINIC_NAME, 'url' => CLINIC_SITE_URL],
        'image'     => CLINIC_SITE_URL . '/public/images/team/' . ($doctor['image'] ?? ''),
        'url'       => $canonicalUrl,
        'mainEntityOfPage' => $canonicalUrl,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <?php endif; ?>
    <script type="application/ld+json"><?php echo json_encode($organizationStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script type="application/ld+json"><?php echo json_encode($breadcrumbStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .fade-up { opacity: 0; transform: translateY(22px); transition: opacity .55s ease, transform .55s ease; }
        .fade-up.visible { opacity: 1; transform: translateY(0); }
    </style>
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
                <a href="/doctors" class="hover:text-[#2fbdef]">Специалисты</a>
                <i class="fa-solid fa-chevron-right text-[0.6rem]"></i>
                <span class="text-[#0f2749]"><?php echo e($doctor['name']); ?></span>
            </nav>

            <div class="grid items-start gap-7 md:grid-cols-[280px_1fr] lg:grid-cols-[320px_1fr]">

                <!-- photo -->
                <div class="fade-up">
                    <div class="overflow-hidden rounded-3xl border border-[#d9e7f3] shadow-[0_12px_36px_rgba(8,36,70,0.10)]">
                        <img src="/public/images/team/<?php echo e($doctor['image']); ?>"
                             alt="<?php echo e($doctor['name']); ?>"
                             class="h-full w-full object-cover"
                             loading="eager"
                             onerror="this.src='/public/images/brand/bioinmed-icon.png'">
                    </div>
                    <!-- quick contact card below photo -->
                    <div class="mt-4 rounded-2xl border border-[#dce8f5] bg-[#f4f9ff] p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#2a5a94]">Записаться на прием к специалисту</p>
                        <a href="tel:<?php echo $phone1link; ?>"
                           class="mt-2 flex items-center gap-2 text-base font-bold text-[#2fbdef] hover:text-[#0f2749]">
                            <i class="fa-solid fa-phone text-sm"></i><?php echo e($phone1); ?>
                        </a>
                        <?php if ($phone2): ?>
                        <a href="tel:<?php echo $phone2link; ?>"
                           class="mt-1 flex items-center gap-2 text-sm font-semibold text-[#2a5a94] hover:text-[#2fbdef]">
                            <i class="fa-solid fa-phone text-xs"></i><?php echo e($phone2); ?>
                        </a>
                        <?php endif; ?>
                        <p class="mt-2 text-[0.75rem] text-[#5a7fa3]">Ежедневно с 9:00 до 21:00</p>
                        <a href="#book" class="mt-3 flex w-full items-center justify-center gap-2 rounded-full bg-[#2fbdef] px-4 py-2 text-xs font-semibold text-white hover:bg-[#1fb3d8]">
                            <i class="fa-regular fa-calendar-check"></i> Записаться онлайн
                        </a>
                    </div>
                </div>

                <!-- info -->
                <div class="fade-up" style="transition-delay:.08s">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#2a5a94]">Специалист клиники БИОИНМЕД</p>
                    <h1 class="mt-2 text-2xl font-bold leading-tight text-[#0f3463] md:text-3xl lg:text-4xl"><?php echo e($doctor['name']); ?></h1>
                    <p class="mt-2 text-base font-semibold text-[#2a5a94] md:text-lg"><?php echo e($doctor['title']); ?></p>

                    <!-- key badges -->
                    <div class="mt-5 flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#e8f3fc] px-3 py-1.5 text-xs font-semibold text-[#1b5c99]">
                            <i class="fa-solid fa-stethoscope text-[#2fbdef]"></i>
                            <?php echo e($doctor['specialty']); ?>
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#e8f3fc] px-3 py-1.5 text-xs font-semibold text-[#1b5c99]">
                            <i class="fa-solid fa-clock text-[#2fbdef]"></i>
                            <?php echo e($experienceText); ?>
                        </span>
                    </div>

                    <p class="mt-4 text-sm leading-relaxed text-[#355b89] md:text-[0.95rem]"><?php echo e($doctor['bio']); ?></p>

                    <?php if (!empty($doctor['leadership'])): ?>
                    <p class="mt-3 text-sm italic leading-relaxed text-[#4a6f9c]"><?php echo e($doctor['leadership']); ?></p>
                    <?php endif; ?>

                    <!-- stats strip -->
                    <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-[#dce8f5] bg-[#f8fcff] p-4 text-center">
                            <p class="text-2xl font-bold text-[#2fbdef]"><i class="fa-solid fa-graduation-cap text-xl"></i></p>
                            <p class="mt-1 text-xs text-[#355b89]">Высшее медицинское образование</p>
                        </div>
                        <?php if ($experienceYears !== null): ?>
                        <div class="rounded-2xl border border-[#dce8f5] bg-[#f8fcff] p-4 text-center">
                            <p class="text-2xl font-bold text-[#2fbdef]"><?php echo e($experienceYears); ?></p>
                            <p class="mt-1 text-xs text-[#355b89]">лет клинической практики</p>
                        </div>
                        <?php else: ?>
                        <div class="rounded-2xl border border-[#dce8f5] bg-[#f8fcff] p-4 text-center">
                            <p class="text-2xl font-bold text-[#2fbdef]"><i class="fa-solid fa-star text-xl"></i></p>
                            <p class="mt-1 text-xs text-[#355b89]">Профессиональный опыт</p>
                        </div>
                        <?php endif; ?>
                        <div class="rounded-2xl border border-[#dce8f5] bg-[#f8fcff] p-4 text-center col-span-2 sm:col-span-1">
                            <p class="text-2xl font-bold text-[#2fbdef]"><i class="fa-solid fa-certificate text-xl"></i></p>
                            <p class="mt-1 text-xs text-[#355b89]">Сертификаты и лицензии МЗ РФ</p>
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
            <div class="space-y-6">

                <?php if (!empty($doctor['specializations']) && is_array($doctor['specializations'])): ?>
                <div class="fade-up rounded-3xl border border-[#d9e7f3] bg-white p-7 shadow-[0_8px_28px_rgba(8,36,70,0.06)]">
                    <h2 class="flex items-center gap-2.5 text-xl font-bold text-[#0f3463]">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#e8f3fc] text-[#2fbdef]"><i class="fa-solid fa-list-check text-sm"></i></span>
                        Направления работы
                    </h2>
                    <ul class="mt-5 space-y-3">
                        <?php foreach ($doctor['specializations'] as $spec): ?>
                        <li class="flex items-start gap-3 text-sm leading-snug text-[#214a7f]">
                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-[#2fbdef]"></span>
                            <span><?php echo e($spec); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if (!empty($doctor['focus']) && is_array($doctor['focus'])): ?>
                <div class="fade-up rounded-3xl border border-[#d9e7f3] bg-white p-7 shadow-[0_8px_28px_rgba(8,36,70,0.06)]">
                    <h2 class="flex items-center gap-2.5 text-xl font-bold text-[#0f3463]">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#e8f3fc] text-[#2fbdef]"><i class="fa-solid fa-bullseye text-sm"></i></span>
                        С чем помогает специалист
                    </h2>
                    <ul class="mt-5 grid gap-3 sm:grid-cols-2">
                        <?php foreach ($doctor['focus'] as $item): ?>
                        <li class="flex items-start gap-3 rounded-xl border border-[#e4edf6] bg-[#f8fcff] p-3 text-sm leading-snug text-[#214a7f]">
                            <i class="fa-solid fa-check mt-0.5 shrink-0 text-[#2fbdef]"></i>
                            <span><?php echo e($item); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if (!empty($doctor['education'])): ?>
                <div class="fade-up rounded-3xl border border-[#d9e7f3] bg-white p-7 shadow-[0_8px_28px_rgba(8,36,70,0.06)]">
                    <h2 class="flex items-center gap-2.5 text-xl font-bold text-[#0f3463]">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#e8f3fc] text-[#2fbdef]"><i class="fa-solid fa-university text-sm"></i></span>
                        Образование и квалификация
                    </h2>
                    <p class="mt-4 text-sm leading-relaxed text-[#355b89]"><?php echo e($doctor['education']); ?></p>
                </div>
                <?php endif; ?>

                <!-- Why trust this doctor -->
                <div class="fade-up rounded-3xl border border-[#d9e7f3] bg-[#f4f9ff] p-7">
                    <h2 class="flex items-center gap-2.5 text-xl font-bold text-[#0f3463]">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#dceefb] text-[#2fbdef]"><i class="fa-solid fa-shield-halved text-sm"></i></span>
                        Почему пациенты выбирают этого специалиста
                    </h2>
                    <ul class="mt-5 space-y-3 text-sm text-[#214a7f]">
                        <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#2fbdef]"></i><span>Индивидуальный план лечения — никаких шаблонных схем</span></li>
                        <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#2fbdef]"></i><span>Сочетание классической и интегративной медицины</span></li>
                        <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#2fbdef]"></i><span>Работает до устойчивого результата, контролирует динамику</span></li>
                        <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-0.5 text-[#2fbdef]"></i><span>Принимает в лицензированной клинике с полным оснащением</span></li>
                    </ul>
                </div>
            </div>

            <!-- right column: sticky CTA -->
            <div>
                <div id="book" class="fade-up sticky top-24 rounded-3xl border border-[#d9e7f3] bg-white p-6 shadow-[0_12px_30px_rgba(8,36,70,0.10)]">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#2a5a94]">Запись на приём</p>
                    <h3 class="mt-2 text-xl font-bold leading-tight text-[#0f3463]">Записаться на прием к специалисту</h3>
                    <p class="mt-2 text-sm leading-relaxed text-[#355b89]">
                        Оставьте номер телефона — мы перезвоним в течение 15 минут, уточним запрос и подберём удобное время.
                    </p>

                    <form id="doctor-book-form" class="mt-5 space-y-3">
                        <input type="hidden" name="doctor" value="<?php echo e($doctor['name']); ?>">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-[#2a5a94]">Телефон <span class="text-red-500">*</span></label>
                            <input type="tel" name="phone" id="doc-phone-input" required
                                   placeholder="+7 999 000 11 22"
                                   class="w-full rounded-full border border-[#d6e4f2] bg-[#f8fbff] px-4 py-3 text-sm text-[#173f73] outline-none focus:border-[#2fbdef] focus:ring-2 focus:ring-[#2fbdef]/15">
                        </div>
                        <button type="submit"
                                class="w-full rounded-full bg-[#2fbdef] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#1fb3d8] active:scale-[.98]">
                            <i class="fa-regular fa-calendar-check mr-1.5"></i> Перезвоните мне
                        </button>
                    </form>
                    <p class="mt-3 text-[0.72rem] leading-snug text-[#7a9ab8]">
                        Нажимая кнопку, вы соглашаетесь с обработкой персональных данных в соответствии с&nbsp;ФЗ&nbsp;№&nbsp;152.
                    </p>

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
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="#book" class="rounded-full bg-[#2fbdef] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#1fb3d8]">
                    <i class="fa-regular fa-calendar-check mr-1.5"></i>Записаться на приём
                </a>
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

    // Form submit: show success toast (replace with real API call)
    document.getElementById('doctor-book-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type=submit]');
        btn.textContent = '✓ Заявка принята! Перезвоним скоро';
        btn.classList.replace('bg-[#2fbdef]', 'bg-green-600');
        btn.disabled = true;
    });
</script>
</body>
</html>
