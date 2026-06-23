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

$siteUrl  = rtrim(CLINIC_SITE_URL, '/');
$iconPath = CLINIC_ICON_PATH;
$iconUrl  = $siteUrl . $iconPath;
$socialImageUrl = bioinmed_default_social_image_url();
$canonicalUrl = $siteUrl . '/doctors';
$pageTitle = 'Профессиональная команда — клиника БИОИНМЕД';
$pageDescription = 'Познакомьтесь с командой врачей клиники БИОИНМЕД. Опытные специалисты в области остеопатии, рефлексотерапии, гомеопатии, психотерапии и восстановительной медицины в Москве.';
$phone1      = CLINIC_PHONE;
$phone1link  = preg_replace('/\D/', '', $phone1);
$phone2      = defined('CLINIC_PHONE_2') ? CLINIC_PHONE_2 : '';
$phone2link  = $phone2 ? preg_replace('/\D/', '', $phone2) : '';

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$doctorListElements = [];
$doctorPosition = 1;
foreach ($doctors as $doctorItem) {
    $doctorSlug = trim((string)($doctorItem['slug'] ?? ''));
    $doctorName = trim((string)($doctorItem['name'] ?? ''));
    if ($doctorSlug === '' || $doctorName === '') {
        continue;
    }
    $doctorListElements[] = [
        '@type' => 'ListItem',
        'position' => $doctorPosition++,
        'name' => $doctorName,
        'url' => $siteUrl . '/doctors/' . rawurlencode($doctorSlug),
    ];
}

$pageStructuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $pageTitle,
    'description' => $pageDescription,
    'url' => $canonicalUrl,
    'inLanguage' => 'ru-RU',
    'mainEntity' => [
        '@type' => 'ItemList',
        'itemListElement' => $doctorListElements,
    ],
];

$organizationStructuredData = bioinmed_medical_organization_schema();
$breadcrumbStructuredData = bioinmed_breadcrumb_schema([
    ['name' => 'Главная', 'url' => '/'],
    ['name' => 'Профессиональная команда', 'url' => '/doctors'],
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
    <script type="application/ld+json"><?php echo json_encode($pageStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script type="application/ld+json"><?php echo json_encode([
        '@context' => 'https://schema.org',
        '@type'    => 'MedicalOrganization',
        'name'     => CLINIC_NAME,
        'url'      => $canonicalUrl,
        'employee' => array_map(fn($d) => [
            '@type'    => 'Physician',
            'name'     => $d['name'],
            'jobTitle' => $d['title'] ?? '',
            'url'      => CLINIC_SITE_URL . '/doctors/' . ($d['slug'] ?? ''),
        ], $doctors),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
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

<main class="grow">

    <!-- HERO -->
    <section class="border-b border-[#e4edf6] bg-white">
        <div class="mx-auto max-w-6xl px-6 py-10 md:px-10 md:py-14">
            <nav class="mb-6 flex items-center gap-2 text-xs text-[#7a9cc4]">
                <a href="/" class="hover:text-[#1977b2]">Главная</a>
                <i class="fa-solid fa-chevron-right text-[0.6rem]"></i>
                <span class="text-[#0f2749]">Профессиональная команда</span>
            </nav>
            <div class="max-w-2xl">
                <p class="text-[0.78rem] font-semibold uppercase tracking-[0.18em] text-[#0a293c]">Клиника БИОИНМЕД · Москва</p>
                <h1 class="mt-2 text-[2rem] font-bold leading-tight text-[#0a293c] md:text-[2.35rem] lg:text-[2.8rem]">Профессиональная команда</h1>
                <p class="mt-3 text-[0.98rem] leading-relaxed text-[#0a293c] md:text-[1.04rem]">
                    Команда клиники БИОИНМЕД — врачи с многолетним опытом в области интегративной медицины, остеопатии, рефлексотерапии, гомеопатии и психотерапии. Каждый специалист строит индивидуальный план лечения.
                </p>
            </div>
        </div>
    </section>

    <section class="border-b border-[#e4edf6] bg-[#f7fbff] py-10 md:py-14">
        <div class="mx-auto max-w-6xl px-6 md:px-10">
            <div class="fade-up overflow-hidden rounded-3xl border border-[#d6e5f2] bg-[#eaf3fb] shadow-[0_18px_40px_rgba(6,29,60,0.08)]">
                <img src="<?php echo e(bioinmed_versioned_asset_path('/public/images/team/team-photo.jpg')); ?>"
                     alt="Команда клиники БИОИНМЕД"
                     class="h-auto max-h-[520px] w-full object-contain"
                     loading="eager"
                     onerror="this.src='/public/images/placeholder.jpg'">
            </div>
        </div>
    </section>

    <!-- CHIEF DOCTOR -->
    <?php
    $chief = $doctors[0] ?? null;
    if ($chief):
        $chiefExp = trim((string)($chief['experience'] ?? ''));
        $chiefImage = bioinmed_versioned_asset_path('/public/images/team/' . ($chief['image'] ?? ''));
        $chiefYears = null;
        if (preg_match('/(\d+)\s*(?:лет|год)/ui', $chiefExp, $m)) $chiefYears = $m[1];
    ?>
    <section class="border-b border-[#e4edf6] bg-[#f6fbff] py-10 md:py-14">
        <div class="mx-auto max-w-6xl px-6 md:px-10">
            <p class="mb-6 text-[0.78rem] font-semibold uppercase tracking-[0.2em] text-[#1977b2]">Главный врач</p>
            <div class="fade-up grid gap-8 rounded-3xl border border-[#d6e5f2] bg-white p-6 shadow-[0_18px_40px_rgba(6,29,60,0.08)] md:grid-cols-[320px_1fr] md:p-8">
                <div class="overflow-hidden rounded-2xl">
                    <img src="<?php echo e($chiefImage); ?>"
                         alt="<?php echo e($chief['name']); ?>"
                         class="h-full max-h-[400px] w-full object-cover"
                         loading="eager"
                        onerror="this.src='/public/images/placeholder.jpg'">
                </div>
                <div class="flex flex-col justify-between">
                    <div>
                        <h2 class="text-[2rem] font-bold leading-tight text-[#0a293c] md:text-[2.35rem]"><?php echo e($chief['name']); ?></h2>
                        <p class="mt-1 text-[0.92rem] font-semibold uppercase tracking-[0.15em] text-[#0a293c]"><?php echo e($chief['title']); ?></p>
                        <?php if (!empty($chief['hero_tagline'])): ?>
                        <p class="mt-4 max-w-3xl text-[#4f6f92]" style="font-family:'Caveat',cursive;font-size:clamp(1.48rem,5vw,1.82rem);line-height:1.14;font-weight:700;">
                            <?php echo e($chief['hero_tagline']); ?>
                        </p>
                        <?php else: ?>
                        <p class="mt-4 text-[0.98rem] leading-relaxed text-[#0a293c]"><?php echo e($chief['bio']); ?></p>
                        <?php endif; ?>
                        <?php $chiefHeroLeadership = trim((string)($chief['hero_leadership'] ?? ($chief['leadership'] ?? ''))); ?>
                        <?php if ($chiefHeroLeadership !== ''): ?>
                        <p class="mt-3 text-[1rem] leading-relaxed text-[#4a6f9c] md:text-[1.08rem]"><?php echo e($chiefHeroLeadership); ?></p>
                        <?php endif; ?>
                        <?php $chiefHighlights = $chief['hero_highlights'] ?? []; ?>
                        <?php if (!empty($chiefHighlights) && is_array($chiefHighlights)): ?>
                        <ul class="mt-4 space-y-2 text-[0.96rem] leading-relaxed text-[#0a293c]">
                            <?php foreach ($chiefHighlights as $highlight): ?>
                            <li class="flex items-start gap-3">
                                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-[#1977b2]"></span>
                                <span><?php echo e($highlight); ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                    <div class="mt-6">
                        <a href="/doctors/<?php echo e($chief['slug']); ?>"
                            class="inline-flex items-center gap-2 rounded-full bg-[#1977b2] px-5 py-2.5 text-[0.98rem] font-semibold text-white hover:bg-[#16658f]">
                            Подробнее
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ALL DOCTORS GRID -->
    <section class="py-12 md:py-16">
        <div class="mx-auto max-w-6xl px-6 md:px-10">
            <p class="text-[0.92rem] font-semibold uppercase tracking-[0.2em] text-[#0a293c]">Команда клиники</p>
            <h2 class="mt-2 text-[2rem] font-bold text-[#0a293c] md:text-[2.35rem]">Врачи команды</h2>
            <p class="mt-2 text-[1.02rem] text-[#4a6f9c]">Нажмите на карточку врача, чтобы узнать подробнее о его специализации и записаться на приём</p>

            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach (array_slice($doctors, 1) as $index => $doc):
                    $docExp = trim((string)($doc['experience'] ?? ''));
                    $docImage = bioinmed_versioned_asset_path('/public/images/team/' . ($doc['image'] ?? ''));
                          $docHasProfile = !array_key_exists('has_profile', $doc) || $doc['has_profile'] !== false;
                    $docActionText = trim((string)($doc['card_action_text'] ?? 'Команда клиники'));
                    $docLink = '/doctors/' . ($doc['slug'] ?? '');
                    $docYears = null;
                    if (preg_match('/(\d+)\s*(?:лет|год)/ui', $docExp, $m)) $docYears = $m[1];
                ?>
                     <article
                         class="fade-up group flex flex-col overflow-hidden rounded-3xl border border-[#dce8f5] bg-white shadow-[0_8px_24px_rgba(8,36,70,0.07)] transition <?php echo $docHasProfile ? 'hover:border-[#1977b2] hover:shadow-[0_12px_30px_rgba(25,119,178,0.13)]' : ''; ?>"
                         style="transition-delay:<?php echo $index * 60; ?>ms">
                    <div class="overflow-hidden">
                        <?php if ($docHasProfile): ?>
                        <a href="<?php echo e($docLink); ?>" class="block overflow-hidden">
                        <?php endif; ?>
                                          <img src="<?php echo e($docImage); ?>"
                                      alt="<?php echo e($doc['name']); ?>"
                                                        class="aspect-[4/5] w-full object-cover transition duration-300 md:aspect-[5/6] <?php echo $docHasProfile ? 'group-hover:scale-[1.03]' : ''; ?>"
                             loading="lazy"
                                      onerror="this.src='/public/images/placeholder.jpg'">
                        <?php if ($docHasProfile): ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="flex flex-1 flex-col p-5 md:p-6">
                        <h3 class="text-[1.02rem] font-bold leading-tight text-[#0a293c] md:text-[1.08rem]">
                            <?php if ($docHasProfile): ?>
                            <a href="<?php echo e($docLink); ?>" class="transition hover:text-[#1977b2]"><?php echo e($doc['name']); ?></a>
                            <?php else: ?>
                            <?php echo e($doc['name']); ?>
                            <?php endif; ?>
                        </h3>
                        <p class="mt-1 text-[0.88rem] font-semibold uppercase tracking-[0.08em] text-[#0a293c] md:text-[0.92rem]"><?php echo e($doc['title']); ?></p>
                        <?php if ($docExp !== ''): ?>
                        <p class="mt-2 text-[0.98rem] font-semibold leading-snug text-[#0a293c] md:text-[1rem]"><?php echo e($docExp); ?></p>
                        <?php endif; ?>
                        <p class="mt-3 text-[0.96rem] leading-relaxed text-[#4a6f9c] md:text-[1rem] line-clamp-3"><?php echo e($doc['bio']); ?></p>

                        <?php if ($docHasProfile || $docActionText !== ''): ?>
                        <div class="mt-auto flex items-center justify-end pt-4">
                            <?php if ($docHasProfile): ?>
                            <a href="<?php echo e($docLink); ?>" class="text-[0.96rem] font-semibold text-[#1977b2] hover:underline">Подробнее →</a>
                            <?php else: ?>
                            <span class="text-[0.96rem] font-semibold text-[#6d8db2]"><?php echo e($docActionText); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="border-y border-[#e4edf6] bg-[linear-gradient(90deg,#ecf6ff_0%,#f7fbff_100%)] py-12">
        <div class="mx-auto max-w-6xl px-6 text-center md:px-10">
            <p class="text-[0.78rem] font-semibold uppercase tracking-[0.2em] text-[#0a293c]">Клиника БИОИНМЕД · <?php echo e(CLINIC_METRO); ?></p>
            <h2 class="mt-3 text-[1.32rem] font-bold text-[#0a293c] md:text-[1.58rem]">Запишитесь к нужному специалисту</h2>
            <p class="mx-auto mt-3 max-w-xl text-[0.98rem] text-[#0a293c]">
                Если не знаете, к кому обратиться — позвоните нам. Мы поможем выбрать врача под ваш запрос.
            </p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="tel:<?php echo $phone1link; ?>" class="rounded-full bg-[#1977b2] px-5 py-2.5 text-[0.98rem] font-semibold text-white hover:bg-[#16658f]">
                    <i class="fa-solid fa-phone mr-1.5"></i><?php echo e($phone1); ?>
                </a>
                <?php if ($phone2): ?>
                <a href="tel:<?php echo $phone2link; ?>" class="rounded-full border border-[#1977b2] px-5 py-2.5 text-[0.98rem] font-semibold text-[#1977b2] hover:bg-white">
                    <i class="fa-solid fa-phone mr-1.5"></i><?php echo e($phone2); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

</main>

<?php
$footer = new Footer($brand_colors);
echo $footer->render();
?>

<script>
    document.querySelectorAll('.fade-up').forEach(function(el) {
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) { el.classList.add('visible'); observer.unobserve(el); }
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -30px 0px' });
        observer.observe(el);
    });
</script>
</body>
</html>
