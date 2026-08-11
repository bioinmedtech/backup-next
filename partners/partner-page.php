<?php
if (!isset($bioinmedPartnerSlug) || !in_array($bioinmedPartnerSlug, ['habilect', 'heel'], true)) {
    http_response_code(404);
    exit('Partner not found');
}
require_once dirname(__DIR__) . '/includes/pin_protection.php';
bioinmed_pin_require_access();
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/components/Components.php';
require_once dirname(__DIR__) . '/includes/content/EditableLists.php';

$e = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$isHabilect = $bioinmedPartnerSlug === 'habilect';
$pageId = $isHabilect ? 'partnerhabilect' : 'partnerheel';
$page = bioinmed_read_json_file('pages/' . $pageId . '.json');
$meta = is_array($page['meta'] ?? null) ? $page['meta'] : [];
$hero = is_array($page['hero'] ?? null) ? $page['hero'] : [];
$facts = is_array($page['facts'] ?? null) ? $page['facts'] : [];
$story = is_array($page['story'] ?? null) ? $page['story'] : [];
$timeline = is_array($page['timeline'] ?? null) ? $page['timeline'] : [];
$systems = is_array($page['systems'] ?? null) ? $page['systems'] : [];
$certification = is_array($page['certification'] ?? null) ? $page['certification'] : [];
$clinic = is_array($page['clinic'] ?? null) ? $page['clinic'] : [];
$notice = is_array($page['notice'] ?? null) ? $page['notice'] : [];
$partnerName = $isHabilect ? 'Хабилект' : 'Heel';
$officialUrl = $isHabilect ? 'https://habilect.com/' : 'https://www.heel-russia.ru/human-medicines';
$primaryUrl = $isHabilect ? '/services/habilect-diagnostics' : '/#contact';
$logo = $isHabilect
    ? bioinmed_preferred_image_asset_path('/public/images/partners/habilect-logo.png')
    : bioinmed_versioned_asset_path('/public/images/partners/heel-logo.svg');
$heroPhoto = $isHabilect ? bioinmed_preferred_image_asset_path('/public/images/services/habilect-2.jpg') : '';
$accent = '#1977b2';
$siteUrl = rtrim(CLINIC_SITE_URL, '/');
$canonicalUrl = $siteUrl . '/partners/' . $bioinmedPartnerSlug;
$pageTitle = trim((string)($meta['title'] ?? $partnerName)) . ' | ' . CLINIC_NAME;
$pageDescription = bioinmed_meta_description(
    $meta['description'] ?? '',
    'О партнёре ' . $partnerName . ' клиники БИОИНМЕД: технологии, подход и применение в восстановительной медицине.'
);
$socialImageUrl = bioinmed_og_image_url('partner-' . $bioinmedPartnerSlug, $isHabilect ? $heroPhoto : '');
$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => $partnerName,
    'url' => $officialUrl,
    'logo' => $siteUrl . preg_replace('/\\?.*$/', '', $logo),
];
$breadcrumbStructuredData = bioinmed_breadcrumb_schema([
    ['name' => 'Главная', 'url' => $siteUrl . '/'],
    ['name' => 'О клинике', 'url' => $siteUrl . '/about'],
    ['name' => 'Партнёры', 'url' => $siteUrl . '/partners'],
    ['name' => $partnerName, 'url' => $canonicalUrl],
]);
$pairFallback = static function (array $source, string $textKey, string $secondaryKey, string $idPrefix, string $defaultIcon = ''): array {
    $items = [];
    foreach ($source as $index => $entry) {
        if (!is_array($entry)) continue;
        $items[] = [
            'id' => $idPrefix . '-' . ($index + 1),
            'text' => (string)($entry[$textKey] ?? ''),
            'secondary' => (string)($entry[$secondaryKey] ?? ''),
            'icon' => (string)($entry['icon'] ?? $defaultIcon),
        ];
    }
    return $items;
};
$factsItems = bioinmed_editable_list_items($page, $pageId . '.facts', $pairFallback($facts, 'value', 'label', 'fact'), '');
$storyItems = bioinmed_editable_list_items($page, $pageId . '.story.paragraphs', (array)($story['paragraphs'] ?? []), '');
$timelineItems = bioinmed_editable_list_items($page, $pageId . '.timeline.items', $pairFallback((array)($timeline['items'] ?? []), 'year', 'text', 'timeline'), '');
$systemItems = bioinmed_editable_list_items($page, $pageId . '.systems.items', $pairFallback((array)($systems['items'] ?? []), 'title', 'text', 'system', 'fa-solid fa-check'), 'fa-solid fa-check');
$certificationItems = bioinmed_editable_list_items($page, $pageId . '.certification.items', $pairFallback((array)($certification['items'] ?? []), 'title', 'text', 'document', 'fa-solid fa-shield-halved'), 'fa-solid fa-shield-halved');
$clinicPointItems = bioinmed_editable_list_items($page, $pageId . '.clinic.points', (array)($clinic['points'] ?? []), 'fa-solid fa-check');
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
    <meta name="theme-color" content="<?php echo $e($accent); ?>">
    <?php echo bioinmed_render_social_meta($pageTitle, $pageDescription, $canonicalUrl, ['image' => $socialImageUrl]); ?>
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
    <div class="bioinmed-back-row"><?php echo bioinmed_render_back_button(['fallback' => '/partners/']); ?></div>
    <nav aria-label="Хлебные крошки" class="mb-6 flex flex-wrap items-center gap-2 text-xs text-[#6c91ae]">
        <a href="/" class="hover:text-[#1977b2]">Главная</a><i class="fa-solid fa-chevron-right text-[0.55rem]"></i>
        <a href="/about" class="hover:text-[#1977b2]">О клинике</a><i class="fa-solid fa-chevron-right text-[0.55rem]"></i>
        <a href="/partners/" class="hover:text-[#1977b2]">Партнёры</a><i class="fa-solid fa-chevron-right text-[0.55rem]"></i>
        <span class="font-semibold text-[#0f2749]" aria-current="page"><?php echo $e($partnerName); ?></span>
    </nav>

    <section class="overflow-hidden rounded-[2rem] border border-[#d8e6f3] bg-white shadow-[0_14px_36px_rgba(8,36,70,0.08)]">
        <div class="grid lg:grid-cols-[1.15fr_0.85fr]">
            <div class="flex flex-col justify-center p-5 md:p-7" data-admin-block-root>
                <img src="<?php echo $e($logo); ?>" alt="<?php echo $e($partnerName); ?>" class="mb-6 h-12 w-40 object-contain object-left">
                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.2em] text-[#1977b2]"<?php echo bioinmed_page_text_attr($page, $pageId, 'hero.eyebrow'); ?>><?php echo $e($hero['eyebrow'] ?? 'Партнёр клиники'); ?></p>
                <h1 class="mt-2 text-[1.65rem] font-bold leading-[1.08] text-[#0f2749] md:text-[2.2rem]"<?php echo bioinmed_page_text_attr($page, $pageId, 'hero.heading'); ?>><?php echo $e($hero['heading'] ?? $partnerName); ?></h1>
                <p class="mt-4 text-[0.98rem] leading-[1.62] text-[#0a293c]"<?php echo bioinmed_page_text_attr($page, $pageId, 'hero.intro'); ?>><?php echo $e($hero['intro'] ?? ''); ?></p>
                <span class="mt-5 inline-flex w-fit items-center gap-2 rounded-full bg-[#e8f3fc] px-4 py-2 text-[0.76rem] font-semibold text-[#1977b2]"><i class="fa-solid fa-check"></i><span<?php echo bioinmed_page_text_attr($page, $pageId, 'hero.badge'); ?>><?php echo $e($hero['badge'] ?? ''); ?></span></span>
            </div>
            <?php if ($isHabilect): ?>
                <div class="relative min-h-[280px] overflow-hidden bg-[#dceaf7] lg:min-h-[400px]">
                    <img src="<?php echo $e($heroPhoto); ?>" alt="Комплекс Хабилект в клинике БИОИНМЕД" class="absolute inset-0 h-full w-full object-cover object-center" decoding="async">
                    <span class="absolute inset-0 bg-[linear-gradient(180deg,transparent_60%,rgba(8,41,62,0.28)_100%)]"></span>
                </div>
            <?php else: ?>
                <div class="relative flex min-h-[280px] items-center justify-center overflow-hidden bg-[#e8f3fc] p-6 lg:min-h-[400px]">
                    <span class="absolute -right-14 -top-14 h-44 w-44 rounded-full bg-[#1977b21e] blur-3xl"></span>
                    <div class="relative rounded-3xl border border-[#d6e4f1] bg-white p-8 shadow-[0_16px_38px_rgba(8,36,70,0.12)]"><img src="<?php echo $e($logo); ?>" alt="Heel" class="h-20 w-60 object-contain"></div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="grid grid-cols-2 gap-3 md:grid-cols-4"<?php echo bioinmed_editable_list_attrs($pageId, $pageId . '.facts', 'Ключевые факты', false, 'Подпись'); ?>>
        <?php echo bioinmed_editable_list_toolbar('div'); ?>
        <?php foreach ($factsItems as $item): ?>
            <article class="rounded-2xl border border-[#d9e7f3] bg-white p-4 text-center shadow-[0_8px_22px_rgba(8,36,70,0.05)]<?php echo bioinmed_editable_list_item_class($item); ?>" data-admin-block-root<?php echo bioinmed_editable_list_item_attrs($item); ?>>
                <strong class="block text-[1.1rem] font-bold text-[#1977b2] md:text-[1.25rem]" data-admin-list-text-view><?php echo $e($item['text']); ?></strong>
                <span class="mt-1 block text-[0.75rem] leading-snug text-[#0a293c]" data-admin-list-secondary-view><?php echo $e($item['secondary']); ?></span>
                <?php echo bioinmed_editable_list_actions($item); ?>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
        <article class="rounded-3xl border border-[#d9e7f3] bg-white p-5 shadow-[0_10px_24px_rgba(8,36,70,0.06)] md:p-7" data-admin-block-root>
            <p class="text-[0.7rem] font-semibold uppercase tracking-[0.2em] text-[#1977b2]"<?php echo bioinmed_page_text_attr($page, $pageId, 'story.eyebrow'); ?>><?php echo $e($story['eyebrow'] ?? 'О компании'); ?></p>
            <h2 class="mt-2 text-[1.4rem] font-bold leading-tight text-[#0f2749] md:text-[1.75rem]"<?php echo bioinmed_page_text_attr($page, $pageId, 'story.heading'); ?>><?php echo $e($story['heading'] ?? ''); ?></h2>
            <div class="mt-4 space-y-3"<?php echo bioinmed_editable_list_attrs($pageId, $pageId . '.story.paragraphs', 'О компании: абзацы', false); ?>>
                <?php echo bioinmed_editable_list_toolbar('div'); ?>
                <?php foreach ($storyItems as $item): ?>
                    <div class="text-[0.94rem] leading-relaxed text-[#0a293c]<?php echo bioinmed_editable_list_item_class($item); ?>"<?php echo bioinmed_editable_list_item_attrs($item); ?>><p data-admin-list-text-view><?php echo $e($item['text']); ?></p><?php echo bioinmed_editable_list_actions($item); ?></div>
                <?php endforeach; ?>
            </div>
        </article>
        <aside class="rounded-3xl border border-[#d9e7f3] bg-[#f8fbff] p-5 md:p-7" data-admin-block-root>
            <h2 class="text-[1.2rem] font-bold text-[#0f2749]"<?php echo bioinmed_page_text_attr($page, $pageId, 'timeline.heading'); ?>><?php echo $e($timeline['heading'] ?? ''); ?></h2>
            <div class="relative mt-5"<?php echo bioinmed_editable_list_attrs($pageId, $pageId . '.timeline.items', 'Этапы и подход', false, 'Описание'); ?>>
                <span class="pointer-events-none absolute bottom-2 left-[5px] top-2 w-px bg-[#b8d2e7]" aria-hidden="true"></span>
                <?php echo bioinmed_editable_list_toolbar('div'); ?>
                <?php foreach ($timelineItems as $item): ?>
                    <div class="relative pb-5 pl-8 last:pb-0<?php echo bioinmed_editable_list_item_class($item); ?>"<?php echo bioinmed_editable_list_item_attrs($item); ?>>
                        <span class="absolute left-0 top-1 h-3 w-3 rounded-full border-2 border-[#1977b2] bg-white shadow-[0_0_0_3px_#f8fbff]" aria-hidden="true"></span>
                        <strong class="block text-[0.86rem] font-bold text-[#1977b2]" data-admin-list-text-view><?php echo $e($item['text']); ?></strong>
                        <p class="mt-1 text-[0.84rem] leading-relaxed text-[#0a293c]" data-admin-list-secondary-view><?php echo $e($item['secondary']); ?></p>
                        <?php echo bioinmed_editable_list_actions($item); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </aside>
    </section>

    <section>
        <div class="max-w-3xl" data-admin-block-root>
            <p class="text-[0.7rem] font-semibold uppercase tracking-[0.2em] text-[#1977b2]"<?php echo bioinmed_page_text_attr($page, $pageId, 'systems.eyebrow'); ?>><?php echo $e($systems['eyebrow'] ?? ''); ?></p>
            <h2 class="mt-2 text-[1.4rem] font-bold text-[#0f2749] md:text-[1.75rem]"<?php echo bioinmed_page_text_attr($page, $pageId, 'systems.heading'); ?>><?php echo $e($systems['heading'] ?? ''); ?></h2>
            <p class="mt-3 text-[0.92rem] leading-relaxed text-[#0a293c]"<?php echo bioinmed_page_text_attr($page, $pageId, 'systems.intro'); ?>><?php echo $e($systems['intro'] ?? ''); ?></p>
        </div>
        <div class="mt-5 grid gap-4 md:grid-cols-3"<?php echo bioinmed_editable_list_attrs($pageId, $pageId . '.systems.items', 'Возможности и научный подход', true, 'Описание'); ?>>
            <?php echo bioinmed_editable_list_toolbar('div'); ?>
            <?php foreach ($systemItems as $item): ?>
                <article class="rounded-3xl border border-[#d9e7f3] bg-white p-5 shadow-[0_10px_24px_rgba(8,36,70,0.06)]<?php echo bioinmed_editable_list_item_class($item); ?>" data-admin-block-root<?php echo bioinmed_editable_list_item_attrs($item); ?>>
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#e8f3fc] text-[#1977b2]"><i class="<?php echo $e($item['icon'] ?: 'fa-solid fa-check'); ?>" data-admin-list-icon-view></i></span>
                    <h3 class="mt-4 text-[1rem] font-bold text-[#0f2749]" data-admin-list-text-view><?php echo $e($item['text']); ?></h3>
                    <p class="mt-2 text-[0.86rem] leading-relaxed text-[#0a293c]" data-admin-list-secondary-view><?php echo $e($item['secondary']); ?></p>
                    <?php echo bioinmed_editable_list_actions($item); ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="rounded-3xl border border-[#d9e7f3] bg-white p-5 shadow-[0_12px_30px_rgba(8,36,70,0.08)] md:p-7">
        <div class="max-w-3xl" data-admin-block-root>
            <p class="text-[0.7rem] font-semibold uppercase tracking-[0.2em] text-[#1977b2]"<?php echo bioinmed_page_text_attr($page, $pageId, 'certification.eyebrow'); ?>><?php echo $e($certification['eyebrow'] ?? ''); ?></p>
            <h2 class="mt-2 text-[1.4rem] font-bold text-[#0f2749] md:text-[1.75rem]"<?php echo bioinmed_page_text_attr($page, $pageId, 'certification.heading'); ?>><?php echo $e($certification['heading'] ?? ''); ?></h2>
            <p class="mt-3 text-[0.91rem] leading-relaxed text-[#0a293c]"<?php echo bioinmed_page_text_attr($page, $pageId, 'certification.intro'); ?>><?php echo $e($certification['intro'] ?? ''); ?></p>
        </div>
        <div class="mt-5 grid gap-4 md:grid-cols-3"<?php echo bioinmed_editable_list_attrs($pageId, $pageId . '.certification.items', 'Документы и качество', true, 'Описание'); ?>>
            <?php echo bioinmed_editable_list_toolbar('div'); ?>
            <?php foreach ($certificationItems as $item): ?>
                <article class="rounded-2xl border border-[#e4edf6] bg-[#f8fbff] p-4<?php echo bioinmed_editable_list_item_class($item); ?>" data-admin-block-root<?php echo bioinmed_editable_list_item_attrs($item); ?>>
                    <i class="<?php echo $e($item['icon'] ?: 'fa-solid fa-shield-halved'); ?> text-[#1977b2]" data-admin-list-icon-view></i>
                    <h3 class="mt-3 text-[0.98rem] font-bold text-[#0f2749]" data-admin-list-text-view><?php echo $e($item['text']); ?></h3>
                    <p class="mt-2 text-[0.84rem] leading-relaxed text-[#0a293c]" data-admin-list-secondary-view><?php echo $e($item['secondary']); ?></p>
                    <?php echo bioinmed_editable_list_actions($item); ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="rounded-3xl border border-[#d9e7f3] bg-white p-5 shadow-[0_12px_30px_rgba(8,36,70,0.08)] md:p-7">
        <div class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr] lg:items-start">
            <div data-admin-block-root>
                <p class="text-[0.7rem] font-semibold uppercase tracking-[0.2em] text-[#1977b2]"<?php echo bioinmed_page_text_attr($page, $pageId, 'clinic.eyebrow'); ?>><?php echo $e($clinic['eyebrow'] ?? 'В БИОИНМЕД'); ?></p>
                <h2 class="mt-2 text-[1.4rem] font-bold leading-tight text-[#0f2749] md:text-[1.75rem]"<?php echo bioinmed_page_text_attr($page, $pageId, 'clinic.heading'); ?>><?php echo $e($clinic['heading'] ?? ''); ?></h2>
                <p class="mt-3 text-[0.92rem] leading-relaxed text-[#0a293c]"<?php echo bioinmed_page_text_attr($page, $pageId, 'clinic.text'); ?>><?php echo $e($clinic['text'] ?? ''); ?></p>
            </div>
            <div>
                <ul class="space-y-3"<?php echo bioinmed_editable_list_attrs($pageId, $pageId . '.clinic.points', 'Применение в БИОИНМЕД'); ?>>
                    <?php echo bioinmed_editable_list_toolbar(); ?>
                    <?php foreach ($clinicPointItems as $item): ?>
                        <li class="flex gap-3 rounded-2xl border border-[#e4edf6] bg-[#f8fbff] p-3.5 text-[0.86rem] text-[#0a293c]<?php echo bioinmed_editable_list_item_class($item); ?>"<?php echo bioinmed_editable_list_item_attrs($item); ?>><i class="<?php echo $e($item['icon'] ?: 'fa-solid fa-check'); ?> mt-0.5 text-[#1977b2]" data-admin-list-icon-view></i><span data-admin-list-text-view><?php echo $e($item['text']); ?></span><?php echo bioinmed_editable_list_actions($item); ?></li>
                    <?php endforeach; ?>
                </ul>
                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="<?php echo $e($primaryUrl); ?>" class="inline-flex items-center gap-2 rounded-full bg-[#1977b2] px-4 py-2.5 text-[0.84rem] font-semibold text-white transition hover:bg-[#16658f]"><span<?php echo bioinmed_page_text_attr($page, $pageId, 'clinic.service_button'); ?>><?php echo $e($clinic['service_button'] ?? 'Записаться'); ?></span><i class="fa-solid fa-arrow-right"></i></a>
                    <a href="<?php echo $e($officialUrl); ?>" target="_blank" rel="noreferrer noopener" class="inline-flex items-center gap-2 rounded-full border border-[#b8d2e7] bg-white px-4 py-2.5 text-[0.84rem] font-semibold text-[#17446f] transition hover:border-[#82bee4] hover:text-[#1977b2]"><span<?php echo bioinmed_page_text_attr($page, $pageId, 'clinic.official_button'); ?>><?php echo $e($clinic['official_button'] ?? 'Официальный сайт'); ?></span><i class="fa-solid fa-arrow-right text-[0.72rem]"></i></a>
                </div>
            </div>
        </div>
    </section>

    <?php if (!$isHabilect && $notice): ?>
        <aside class="flex gap-4 rounded-3xl border border-[#d9e7f3] bg-[#f8fbff] p-5 text-[#0a293c]" data-admin-block-root>
            <i class="fa-solid fa-circle-info mt-1 text-[#1977b2]"></i>
            <div><h2 class="font-bold text-[#0f2749]"<?php echo bioinmed_page_text_attr($page, $pageId, 'notice.heading'); ?>><?php echo $e($notice['heading'] ?? 'Важная информация'); ?></h2><p class="mt-1 text-[0.84rem] leading-relaxed"<?php echo bioinmed_page_text_attr($page, $pageId, 'notice.text'); ?>><?php echo $e($notice['text'] ?? ''); ?></p></div>
        </aside>
    <?php endif; ?>
    <div class="text-center text-[0.76rem] text-[#4a6f8a]">Информация о компании подготовлена по материалам <a href="<?php echo $e($officialUrl); ?>" target="_blank" rel="noreferrer noopener" class="font-semibold underline decoration-dotted underline-offset-4 hover:text-[#1977b2]">официального сайта <?php echo $e($partnerName); ?></a>.</div>
</main>
<?php echo (new Footer($brand_colors))->render(); ?>
</body>
</html>
