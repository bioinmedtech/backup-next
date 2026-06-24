<?php
require_once __DIR__ . '/includes/pin_protection.php';
bioinmed_pin_require_access();


require_once 'config.php';
require_once 'includes/components/Components.php';

$doctorsPage = bioinmed_read_json_file('pages/doctors.json');
$doctorsMeta = is_array($doctorsPage['meta'] ?? null) ? $doctorsPage['meta'] : [];
$doctorsHero = is_array($doctorsPage['hero'] ?? null) ? $doctorsPage['hero'] : [];
$doctorsChiefQuote = is_array($doctorsPage['chief_quote'] ?? null) ? $doctorsPage['chief_quote'] : [];
$doctorsTeam = is_array($doctorsPage['team'] ?? null) ? $doctorsPage['team'] : [];
$doctorsCta = is_array($doctorsPage['cta'] ?? null) ? $doctorsPage['cta'] : [];

$siteUrl  = rtrim(CLINIC_SITE_URL, '/');
$iconPath = CLINIC_ICON_PATH;
$iconUrl  = $siteUrl . $iconPath;
$socialImageUrl = bioinmed_default_social_image_url();
$canonicalUrl = $siteUrl . '/doctors';
$pageTitle = trim((string)($doctorsMeta['title'] ?? '')) . (string)($doctorsMeta['title_suffix'] ?? '') . CLINIC_NAME;
$pageDescription = trim((string)($doctorsMeta['description'] ?? ''));
$phone1      = CLINIC_PHONE;
$phone1link  = preg_replace('/\D/', '', $phone1);
$phone2      = defined('CLINIC_PHONE_2') ? CLINIC_PHONE_2 : '';
$phone2link  = $phone2 ? preg_replace('/\D/', '', $phone2) : '';

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$doctorOrder = [
    'kondratova-elena-aleksandrovna',
    'nehorosheva-lyudmila-sergeevna',
    'vertlib-valeriya-pavlovna',
    'mayorova-darya-sergeevna',
    'rozhkov-sergei-leonidovich',
];
$doctorOrderMap = array_flip($doctorOrder);
$chiefDoctor = $doctors[0] ?? null;
$teamDoctors = [];
foreach (array_slice($doctors, 1) as $originalIndex => $doctorItem) {
    $doctorItem['__original_index'] = $originalIndex;
    $teamDoctors[] = $doctorItem;
}
usort($teamDoctors, static function (array $left, array $right) use ($doctorOrderMap): int {
    $leftOrder = $doctorOrderMap[$left['slug'] ?? ''] ?? 999;
    $rightOrder = $doctorOrderMap[$right['slug'] ?? ''] ?? 999;
    if ($leftOrder === $rightOrder) {
        return ($left['__original_index'] ?? 0) <=> ($right['__original_index'] ?? 0);
    }
    return $leftOrder <=> $rightOrder;
});

$orderedDoctors = $chiefDoctor ? array_merge([$chiefDoctor], $teamDoctors) : $teamDoctors;

$doctorListElements = [];
$doctorPosition = 1;
foreach ($orderedDoctors as $doctorItem) {
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
    ['name' => trim((string)($doctorsHero['breadcrumb_home'] ?? '')), 'url' => '/'],
    ['name' => trim((string)($doctorsHero['breadcrumb_current'] ?? '')), 'url' => '/doctors'],
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
        ], $orderedDoctors),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script type="application/ld+json"><?php echo json_encode($organizationStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script type="application/ld+json"><?php echo json_encode($breadcrumbStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script src="/public/vendor/tailwind/tailwindcss-cdn.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/vendor/fontawesome/css/all.min.css">
    <style>
        html {
            font-size: clamp(17px, 0.5vw + 15px, 19px);
        }

        body {
            line-height: 1.72;
        }

        .fade-up { opacity: 0; transform: translateY(22px); transition: opacity .55s ease, transform .55s ease; }
        .fade-up.visible { opacity: 1; transform: translateY(0); }
    </style>
    <?php echo bioinmed_uis_counter_head(); ?>
</head>
<body class="flex min-h-screen flex-col bg-[#e4f1fa] text-[#0f2749] antialiased">
<?php
$header = new Header($brand_colors);
echo $header->render();
?>

<main class="grow">

    <!-- HERO -->
    <section class="border-b border-[#e4edf6] bg-[#e4f1fa]">
        <div class="mx-auto max-w-6xl px-6 py-10 md:px-10 md:py-14">
            <nav class="mb-6 flex items-center gap-2 text-xs text-[#7a9cc4]">
                <a href="/" class="hover:text-[#1977b2]"><?php echo e($doctorsHero['breadcrumb_home'] ?? ''); ?></a>
                <i class="fa-solid fa-chevron-right text-[0.6rem]"></i>
                <span class="text-[#0f2749]"><?php echo e($doctorsHero['breadcrumb_current'] ?? ''); ?></span>
            </nav>
            <div>
                <h1 class="mt-2 text-[2rem] font-bold leading-tight text-[#0a293c] md:text-[2.35rem] lg:text-[2.8rem]"><?php echo e($doctorsHero['heading'] ?? ''); ?></h1>
                <p class="mt-3 text-[0.98rem] leading-relaxed text-[#0a293c] md:text-[1.04rem]">
                    <?php echo e($doctorsHero['text'] ?? ''); ?>
                </p>
            </div>
        </div>
    </section>

    <section class="border-b border-[#e4edf6] bg-[#e4f1fa] py-10 md:py-14">
        <div class="mx-auto max-w-6xl px-6 md:px-10">
            <div class="fade-up overflow-hidden rounded-3xl">
                <img src="<?php echo e(bioinmed_versioned_asset_path('/public/images/team/team-photo.jpg')); ?>"
                     alt="<?php echo e($doctorsHero['team_image_alt'] ?? ''); ?>"
                     class="h-auto max-h-[520px] w-full rounded-3xl object-contain"
                     loading="eager"
                     onerror="this.src='/public/images/placeholder.jpg'">
            </div>
        </div>
    </section>

    <!-- CHIEF DOCTOR -->
    <?php
    $chief = $chiefDoctor;
    if ($chief):
        $chiefExp = trim((string)($chief['experience'] ?? ''));
        $chiefImage = bioinmed_versioned_asset_path('/public/images/team/' . ($chief['image'] ?? ''));
        $chiefYears = null;
        if (preg_match('/(\d+)\s*(?:лет|год)/ui', $chiefExp, $m)) $chiefYears = $m[1];
    ?>
    <section class="bg-[#e4f1fa] py-10 md:py-14">
        <div class="mx-auto max-w-6xl px-6 md:px-10">
                <div class="fade-up grid items-start gap-8 md:grid-cols-[380px_1fr] lg:grid-cols-[460px_1fr]">
                    <div class="w-full max-w-[480px]">
                        <div class="aspect-square overflow-hidden rounded-3xl">
                            <img src="<?php echo e($chiefImage); ?>"
                                 alt="<?php echo e($chief['name']); ?>"
                                 class="h-full w-full rounded-3xl object-cover object-top"
                                 loading="eager"
                                 onerror="this.src='/public/images/placeholder.jpg'">
                        </div>
                        <?php if (!empty($chief['hero_tagline'])): ?>
                        <p class="mt-4 max-w-none text-[#0a293c]" style="font-family:'Caveat',cursive;font-size:clamp(1.35rem,4vw,1.8rem);line-height:1.22;font-weight:700;">
                            <?php echo e($doctorsChiefQuote['text'] ?? ''); ?>
                        </p>
                        <p class="mt-2 text-[1.08rem] font-semibold tracking-[0.04em] text-[#4a6f9c]" style="font-family:'Caveat',cursive;"><?php echo e($doctorsChiefQuote['sign'] ?? ''); ?></p>
                    <?php endif; ?>
                    </div>
                <?php echo bioinmed_render_chief_doctor_summary($chief, [
                    'show_cta' => true,
                    'cta_url' => '/doctors/' . ($chief['slug'] ?? ''),
                    'cta_label' => bioinmed_text('common.more_details'),
                ]); ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ALL DOCTORS GRID -->
    <section class="bg-[#e4f1fa] py-12 md:py-16">
        <div class="mx-auto max-w-6xl px-6 md:px-10">
            <p class="text-[0.92rem] font-semibold uppercase tracking-[0.2em] text-[#0a293c]"><?php echo e($doctorsTeam['eyebrow'] ?? ''); ?></p>
            <h2 class="mt-2 text-[2rem] font-bold text-[#0a293c] md:text-[2.35rem]"><?php echo e($doctorsTeam['title'] ?? ''); ?></h2>
            <p class="mt-2 text-[1.02rem] text-[#0a293c]"><?php echo e($doctorsTeam['description'] ?? ''); ?></p>

            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-2">
                <?php foreach ($teamDoctors as $index => $doc):
                    $docExp = trim((string)($doc['experience'] ?? ''));
                    $docImage = bioinmed_versioned_asset_path('/public/images/team/' . ($doc['image'] ?? ''));
                          $docHasProfile = !array_key_exists('has_profile', $doc) || $doc['has_profile'] !== false;
                    $docActionText = trim((string)($doc['card_action_text'] ?? ($doctorsTeam['card_action_fallback'] ?? '')));
                    $docLink = '/doctors/' . ($doc['slug'] ?? '');
                    $docYears = null;
                    if (preg_match('/(\d+)\s*(?:лет|год)/ui', $docExp, $m)) $docYears = $m[1];
                ?>
                    <article
                        class="fade-up group flex flex-col overflow-hidden rounded-3xl border border-[#dce8f5] bg-white shadow-[0_8px_24px_rgba(8,36,70,0.07)] transition md:flex-row md:items-stretch <?php echo $docHasProfile ? 'hover:border-[#1977b2] hover:shadow-[0_12px_30px_rgba(25,119,178,0.13)]' : ''; ?>"
                        style="transition-delay:<?php echo $index * 60; ?>ms">
                    <div class="overflow-hidden md:w-[260px] lg:w-[320px] md:self-stretch md:shrink-0">
                        <?php if ($docHasProfile): ?>
                        <a href="<?php echo e($docLink); ?>" class="block h-full overflow-hidden">
                        <?php endif; ?>
                                          <img src="<?php echo e($docImage); ?>"
                                      alt="<?php echo e($doc['name']); ?>"
                                                        class="block aspect-[4/5] w-full object-cover object-top transition duration-300 md:h-full md:min-h-full md:aspect-auto <?php echo $docHasProfile ? 'group-hover:scale-[1.03]' : ''; ?>"
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
                        <p class="mt-2 text-[0.88rem] font-semibold uppercase tracking-[0.08em] text-[#0a293c] md:mt-2.5 md:text-[0.92rem]"><?php echo e($doc['title']); ?></p>
                        <?php if ($docExp !== ''): ?>
                        <p class="mt-2.5 text-[0.95rem] font-medium leading-relaxed text-[#0a293c] md:mt-3 md:text-[0.98rem]"><?php echo e($docExp); ?></p>
                        <?php endif; ?>
                        <?php if ($docHasProfile || $docActionText !== ''): ?>
                        <div class="mt-auto flex items-center justify-end pt-3">
                            <?php if ($docHasProfile): ?>
                            <a href="<?php echo e($docLink); ?>" class="inline-flex items-center gap-2 rounded-full bg-[#1977b2] px-4 py-2.5 text-[0.86rem] font-semibold text-white shadow-[0_10px_24px_rgba(25,119,178,0.18)] transition hover:bg-[#16658f]">
                                <?php echo e(bioinmed_text('common.more_details')); ?>
                                <i class="fa-solid fa-arrow-right text-[0.72rem]"></i>
                            </a>
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
    <section class="border-y border-[#e4edf6] bg-[#e4f1fa] py-12">
        <div class="mx-auto max-w-6xl px-6 text-center md:px-10">
            <p class="text-[0.78rem] font-semibold uppercase tracking-[0.2em] text-[#0a293c]"><?php echo e(($doctorsCta['eyebrow'] ?? '') . ' ' . CLINIC_NAME . ' · ' . CLINIC_METRO); ?></p>
            <h2 class="mt-3 text-[1.32rem] font-bold text-[#0a293c] md:text-[1.58rem]"><?php echo e($doctorsCta['title'] ?? ''); ?></h2>
            <p class="mx-auto mt-3 max-w-xl text-[0.98rem] text-[#0a293c]">
                <?php echo e($doctorsCta['text'] ?? ''); ?>
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
