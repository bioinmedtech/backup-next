<?php
require_once dirname(__DIR__) . '/includes/pin_protection.php';
bioinmed_pin_require_access();
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/components/Components.php';
require_once dirname(__DIR__) . '/includes/content/AboutSectionNav.php';
require_once dirname(__DIR__) . '/includes/content/EditableLists.php';

$e = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$page = bioinmed_read_json_file('pages/partners.json');
$meta = is_array($page['meta'] ?? null) ? $page['meta'] : [];
$hero = is_array($page['hero'] ?? null) ? $page['hero'] : [];
$principles = is_array($page['principles'] ?? null) ? $page['principles'] : [];
$principleItemsRaw = is_array($principles['items'] ?? null) ? $principles['items'] : [];
$habilect = is_array($page['habilect'] ?? null) ? $page['habilect'] : [];
$heel = is_array($page['heel'] ?? null) ? $page['heel'] : [];
$closing = is_array($page['closing'] ?? null) ? $page['closing'] : [];
$siteUrl = rtrim(CLINIC_SITE_URL, '/');
$canonicalUrl = $siteUrl . '/partners';
$pageTitle = trim((string)($meta['title'] ?? 'Партнёры клиники')) . ' | ' . CLINIC_NAME;
$pageDescription = trim((string)($meta['description'] ?? 'Технологические и фармацевтические партнёры клиники БИОИНМЕД.'));
$structuredData = bioinmed_medical_organization_schema();
$breadcrumbStructuredData = bioinmed_breadcrumb_schema([
    ['name' => 'Главная', 'url' => $siteUrl . '/'],
    ['name' => 'О клинике', 'url' => $siteUrl . '/about'],
    ['name' => 'Партнёры', 'url' => $canonicalUrl],
]);
$habilectLogo = bioinmed_preferred_image_asset_path('/public/images/partners/habilect-logo.png');
$habilectPhoto = bioinmed_preferred_image_asset_path('/public/images/services/habilect-2.jpg');
$heelLogo = bioinmed_versioned_asset_path('/public/images/partners/heel-logo.svg');
$principleIcons = ['fa-solid fa-microscope', 'fa-solid fa-diagram-project', 'fa-solid fa-user-doctor'];
$principleFallback = [];
foreach ($principleItemsRaw as $index => $item) {
    $principleFallback[] = [
        'id' => 'principle-' . ($index + 1),
        'text' => (string)($item['title'] ?? ''),
        'secondary' => (string)($item['text'] ?? ''),
        'icon' => $principleIcons[$index] ?? 'fa-solid fa-check',
    ];
}
$principleItems = bioinmed_editable_list_items($page, 'partners.principles.items', $principleFallback, 'fa-solid fa-check');
$habilectPointItems = bioinmed_editable_list_items($page, 'partners.habilect.points', (array)($habilect['points'] ?? []), 'fa-solid fa-check');
$heelPointItems = bioinmed_editable_list_items($page, 'partners.heel.points', (array)($heel['points'] ?? []), 'fa-solid fa-check');
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $e($pageTitle); ?></title>
    <meta name="description" content="<?php echo $e($pageDescription); ?>">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <link rel="canonical" href="<?php echo $e($canonicalUrl); ?>">
    <meta name="theme-color" content="#1977b2">
    <?php echo bioinmed_render_social_meta($pageTitle, $pageDescription, $canonicalUrl, ['image' => bioinmed_default_social_image_url()]); ?>
    <?php echo bioinmed_render_favicon_links(CLINIC_ICON_PATH); ?>
    <?php echo bioinmed_render_public_head_assets(); ?>
    <style>
        .bioinmed-editable-list-item-hidden,
        .bioinmed-editable-list-toolbar,
        .bioinmed-editable-list-actions { display: none !important; }
    </style>
    <script type="application/ld+json"><?php echo json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script type="application/ld+json"><?php echo json_encode($breadcrumbStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <?php echo bioinmed_uis_counter_head(); ?>
</head>
<body class="min-h-screen bg-[#e4f1fa] text-[#0f2749] antialiased">
<?php echo bioinmed_yandex_metrika_noscript(); ?>
<?php echo (new Header($brand_colors))->render(); ?>

<main class="mx-auto max-w-6xl space-y-8 px-6 py-8 md:space-y-10 md:px-10 md:py-12">
    <?php echo bioinmed_render_about_breadcrumbs('Партнёры'); ?>

    <section class="relative overflow-hidden rounded-[2rem] border border-[#d8e6f3] bg-[radial-gradient(circle_at_top_left,#ffffff_0%,#edf6fd_38%,#deedf8_100%)] p-5 shadow-[0_14px_36px_rgba(8,36,70,0.08)] md:p-7" data-admin-block-root>
        <div class="pointer-events-none absolute -right-14 -top-14 h-44 w-44 rounded-full bg-[#1977b21e] blur-3xl"></div>
        <div class="relative max-w-3xl">
            <p class="text-[0.72rem] font-semibold uppercase tracking-[0.2em] text-[#1977b2]"<?php echo bioinmed_page_text_attr($page, 'partners', 'hero.eyebrow'); ?>><?php echo $e($hero['eyebrow'] ?? 'О клинике'); ?></p>
            <h1 class="mt-2 text-[1.65rem] font-bold leading-[1.08] text-[#0f2749] md:text-[2.2rem]"<?php echo bioinmed_page_text_attr($page, 'partners', 'hero.heading'); ?>><?php echo $e($hero['heading'] ?? 'Партнёры БИОИНМЕД'); ?></h1>
            <p class="mt-4 text-[0.98rem] leading-[1.62] text-[#0a293c]"<?php echo bioinmed_page_text_attr($page, 'partners', 'hero.intro'); ?>><?php echo $e($hero['intro'] ?? ''); ?></p>
        </div>
    </section>

    <section>
        <h2 class="text-[1.4rem] font-bold text-[#0f2749] md:text-[1.75rem]"<?php echo bioinmed_page_text_attr($page, 'partners', 'principles.heading'); ?>><?php echo $e($principles['heading'] ?? 'Как мы выбираем партнёров'); ?></h2>
        <div class="mt-5 grid gap-4 md:grid-cols-3"<?php echo bioinmed_editable_list_attrs('partners', 'partners.principles.items', 'Принципы партнёрства', true, 'Описание'); ?>>
            <?php echo bioinmed_editable_list_toolbar('div'); ?>
            <?php foreach ($principleItems as $item): ?>
                <article class="rounded-3xl border border-[#d9e7f3] bg-white p-5 shadow-[0_10px_24px_rgba(8,36,70,0.06)]<?php echo bioinmed_editable_list_item_class($item); ?>" data-admin-block-root<?php echo bioinmed_editable_list_item_attrs($item); ?>>
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#e8f3fc] text-[#1977b2]"><i class="<?php echo $e($item['icon'] ?: 'fa-solid fa-check'); ?>" data-admin-list-icon-view></i></span>
                    <h3 class="mt-4 text-[1rem] font-bold text-[#0f2749]" data-admin-list-text-view><?php echo $e($item['text']); ?></h3>
                    <p class="mt-2 text-[0.9rem] leading-relaxed text-[#0a293c]" data-admin-list-secondary-view><?php echo $e($item['secondary']); ?></p>
                    <?php echo bioinmed_editable_list_actions($item); ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="space-y-6" aria-label="Наши партнёры">
        <article class="group overflow-hidden rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_12px_30px_rgba(8,36,70,0.08)]">
            <div class="grid lg:grid-cols-[0.9fr_1.1fr]">
                <a href="/partners/habilect/" class="relative min-h-[260px] overflow-hidden bg-[#dceaf7] lg:min-h-[360px]" aria-label="Подробнее о партнёре Хабилект">
                    <img src="<?php echo $e($habilectPhoto); ?>" alt="Диагностика на комплексе Хабилект в клинике БИОИНМЕД" class="absolute inset-0 h-full w-full object-cover object-center transition duration-500 group-hover:scale-[1.025]" loading="lazy" decoding="async">
                    <span class="absolute inset-0 bg-[linear-gradient(180deg,transparent_55%,rgba(7,38,60,0.48)_100%)]"></span>
                    <span class="absolute bottom-4 left-4 rounded-2xl bg-white px-4 py-3 shadow-lg"><img src="<?php echo $e($habilectLogo); ?>" alt="Хабилект" class="h-10 w-auto object-contain"></span>
                </a>
                <div class="flex flex-col justify-center p-5 md:p-7" data-admin-block-root>
                    <p class="text-[0.7rem] font-semibold uppercase tracking-[0.2em] text-[#1977b2]"<?php echo bioinmed_page_text_attr($page, 'partners', 'habilect.eyebrow'); ?>><?php echo $e($habilect['eyebrow'] ?? ''); ?></p>
                    <h2 class="mt-2 text-[1.4rem] font-bold text-[#0f2749] md:text-[1.75rem]"<?php echo bioinmed_page_text_attr($page, 'partners', 'habilect.title'); ?>><?php echo $e($habilect['title'] ?? 'Хабилект'); ?></h2>
                    <p class="mt-3 text-[0.96rem] leading-relaxed text-[#0a293c]"<?php echo bioinmed_page_text_attr($page, 'partners', 'habilect.text'); ?>><?php echo $e($habilect['text'] ?? ''); ?></p>
                    <ul class="mt-4 space-y-2.5"<?php echo bioinmed_editable_list_attrs('partners', 'partners.habilect.points', 'Хабилект: преимущества'); ?>>
                        <?php echo bioinmed_editable_list_toolbar(); ?>
                        <?php foreach ($habilectPointItems as $item): ?>
                            <li class="flex gap-3 text-[0.9rem] text-[#0a293c]<?php echo bioinmed_editable_list_item_class($item); ?>"<?php echo bioinmed_editable_list_item_attrs($item); ?>><i class="<?php echo $e($item['icon'] ?: 'fa-solid fa-check'); ?> mt-0.5 text-[#1977b2]" data-admin-list-icon-view></i><span data-admin-list-text-view><?php echo $e($item['text']); ?></span><?php echo bioinmed_editable_list_actions($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="/partners/habilect/" class="mt-5 inline-flex w-fit items-center gap-2 rounded-full bg-[#1977b2] px-4 py-2.5 text-[0.84rem] font-semibold text-white transition hover:bg-[#16658f]"><span<?php echo bioinmed_page_text_attr($page, 'partners', 'habilect.button'); ?>><?php echo $e($habilect['button'] ?? 'Подробнее'); ?></span><i class="fa-solid fa-arrow-right"></i></a>
                </div>
            </div>
        </article>

        <article class="group overflow-hidden rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_12px_30px_rgba(8,36,70,0.08)]">
            <div class="grid lg:grid-cols-[0.9fr_1.1fr]">
                <a href="/partners/heel/" class="relative flex min-h-[260px] items-center justify-center overflow-hidden bg-[#e8f3fc] p-6 lg:min-h-[360px]" aria-label="Подробнее о партнёре Heel">
                    <span class="absolute -right-14 -top-14 h-44 w-44 rounded-full bg-[#1977b21e] blur-3xl"></span>
                    <span class="relative flex h-36 w-64 items-center justify-center rounded-3xl border border-[#d6e4f1] bg-white p-7 shadow-[0_16px_38px_rgba(8,36,70,0.12)]"><img src="<?php echo $e($heelLogo); ?>" alt="Heel" class="h-20 w-full object-contain"></span>
                </a>
                <div class="flex flex-col justify-center p-5 md:p-7" data-admin-block-root>
                    <p class="text-[0.7rem] font-semibold uppercase tracking-[0.2em] text-[#1977b2]"<?php echo bioinmed_page_text_attr($page, 'partners', 'heel.eyebrow'); ?>><?php echo $e($heel['eyebrow'] ?? ''); ?></p>
                    <h2 class="mt-2 text-[1.4rem] font-bold text-[#0f2749] md:text-[1.75rem]"<?php echo bioinmed_page_text_attr($page, 'partners', 'heel.title'); ?>><?php echo $e($heel['title'] ?? 'Heel'); ?></h2>
                    <p class="mt-3 text-[0.96rem] leading-relaxed text-[#0a293c]"<?php echo bioinmed_page_text_attr($page, 'partners', 'heel.text'); ?>><?php echo $e($heel['text'] ?? ''); ?></p>
                    <ul class="mt-4 space-y-2.5"<?php echo bioinmed_editable_list_attrs('partners', 'partners.heel.points', 'Heel: преимущества'); ?>>
                        <?php echo bioinmed_editable_list_toolbar(); ?>
                        <?php foreach ($heelPointItems as $item): ?>
                            <li class="flex gap-3 text-[0.9rem] text-[#0a293c]<?php echo bioinmed_editable_list_item_class($item); ?>"<?php echo bioinmed_editable_list_item_attrs($item); ?>><i class="<?php echo $e($item['icon'] ?: 'fa-solid fa-check'); ?> mt-0.5 text-[#1977b2]" data-admin-list-icon-view></i><span data-admin-list-text-view><?php echo $e($item['text']); ?></span><?php echo bioinmed_editable_list_actions($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="/partners/heel/" class="mt-5 inline-flex w-fit items-center gap-2 rounded-full bg-[#1977b2] px-4 py-2.5 text-[0.84rem] font-semibold text-white transition hover:bg-[#16658f]"><span<?php echo bioinmed_page_text_attr($page, 'partners', 'heel.button'); ?>><?php echo $e($heel['button'] ?? 'Подробнее'); ?></span><i class="fa-solid fa-arrow-right"></i></a>
                </div>
            </div>
        </article>
    </section>

    <section class="rounded-3xl border border-[#d9e7f3] bg-white p-5 shadow-[0_10px_24px_rgba(8,36,70,0.06)] md:p-7" data-admin-block-root>
        <div class="flex items-start gap-4">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#1977b2]"><i class="fa-solid fa-user-doctor"></i></span>
            <div>
                <h2 class="text-[1.2rem] font-bold text-[#0f2749] md:text-[1.45rem]"<?php echo bioinmed_page_text_attr($page, 'partners', 'closing.heading'); ?>><?php echo $e($closing['heading'] ?? ''); ?></h2>
                <p class="mt-2 max-w-4xl text-[0.94rem] leading-relaxed text-[#0a293c]"<?php echo bioinmed_page_text_attr($page, 'partners', 'closing.text'); ?>><?php echo $e($closing['text'] ?? ''); ?></p>
            </div>
        </div>
    </section>
</main>
<?php echo (new Footer($brand_colors))->render(); ?>
</body>
</html>
