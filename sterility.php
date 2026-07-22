<?php
require_once __DIR__ . '/includes/pin_protection.php';
bioinmed_pin_require_access();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/components/Components.php';
require_once __DIR__ . '/includes/content/EditableLists.php';
require_once __DIR__ . '/includes/content/AboutSectionNav.php';

$e = static function ($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$sterilityPage = bioinmed_read_json_file('pages/sterility.json');
$sterilityMeta = is_array($sterilityPage['meta'] ?? null) ? $sterilityPage['meta'] : [];
$sterilityHero = is_array($sterilityPage['hero'] ?? null) ? $sterilityPage['hero'] : [];
$sterilityMeasures = is_array($sterilityPage['measures'] ?? null) ? $sterilityPage['measures'] : [];
$sterilityCycle = is_array($sterilityPage['cycle'] ?? null) ? $sterilityPage['cycle'] : [];
$sterilitySteps = is_array($sterilityCycle['steps'] ?? null) ? $sterilityCycle['steps'] : [];
$sterilityCta = is_array($sterilityPage['cta'] ?? null) ? $sterilityPage['cta'] : [];
$sterilitySubmitNode = bioinmed_page_text_node($sterilityPage, 'sterility', 'cta.submit_label', 'Задать вопрос');
$sterilityMeasureFallback = [];
foreach ($sterilityMeasures as $measureKey => $measure) {
    if (!is_array($measure)) continue;
    $sterilityMeasureFallback[] = [
        'id' => (string)$measureKey,
        'text' => (string)($measure['title'] ?? ''),
        'secondary' => (string)($measure['text'] ?? ''),
        'icon' => (string)($measure['icon'] ?? 'fa-solid fa-shield-heart'),
    ];
}
$sterilityMeasureItems = bioinmed_editable_list_items($sterilityPage, 'sterility.measures', $sterilityMeasureFallback, 'fa-solid fa-shield-heart');
$sterilityStepFallback = [];
foreach ($sterilitySteps as $stepKey => $step) {
    if (!is_array($step)) continue;
    $sterilityStepFallback[] = [
        'id' => (string)$stepKey,
        'text' => (string)($step['title'] ?? ''),
        'secondary' => (string)($step['text'] ?? ''),
    ];
}
$sterilityStepItems = bioinmed_editable_list_items($sterilityPage, 'sterility.cycle.steps', $sterilityStepFallback, '');
$siteUrl = rtrim(CLINIC_SITE_URL, '/');
$canonicalUrl = $siteUrl . '/sterility';
$pageTitle = trim((string)($sterilityMeta['title'] ?? 'Стерильность и безопасность')) . ' | ' . CLINIC_NAME;
$pageDescription = trim((string)($sterilityMeta['description'] ?? 'Как в клинике БИОИНМЕД организованы дезинфекция, обработка инструментов, одноразовые материалы и контроль безопасности.'));
$structuredData = bioinmed_medical_organization_schema();
$breadcrumbStructuredData = bioinmed_breadcrumb_schema([
    ['name' => 'Главная', 'url' => $siteUrl . '/'],
    ['name' => 'О клинике', 'url' => $siteUrl . '/about'],
    ['name' => 'Стерильность', 'url' => $canonicalUrl],
]);
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $e($pageTitle); ?></title>
    <meta name="description" content="<?php echo $e($pageDescription); ?>"><meta name="robots" content="index,follow">
    <link rel="canonical" href="<?php echo $e($canonicalUrl); ?>"><meta name="theme-color" content="#1977b2">
    <?php echo bioinmed_render_social_meta($pageTitle, $pageDescription, $canonicalUrl, ['image' => bioinmed_default_social_image_url()]); ?>
    <?php echo bioinmed_render_favicon_links(CLINIC_ICON_PATH); ?><?php echo bioinmed_render_public_head_assets(); ?>
    <script type="application/ld+json"><?php echo json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script type="application/ld+json"><?php echo json_encode($breadcrumbStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <?php echo bioinmed_uis_counter_head(); ?>
</head>
<body class="min-h-screen bg-[#e4f1fa] text-[#0f2749] antialiased">
<?php echo bioinmed_yandex_metrika_noscript(); ?><?php echo (new Header($brand_colors))->render(); ?>
<main class="mx-auto max-w-6xl px-6 py-8 md:px-10 md:py-12">
    <?php echo bioinmed_render_about_breadcrumbs('Стерильность'); ?>
    <section class="relative mt-6 overflow-hidden rounded-3xl border border-[#d8e6f3] bg-white p-5 shadow-[0_14px_36px_rgba(8,36,70,0.08)] md:p-8" style="background:linear-gradient(135deg,#ffffff 0%,#edf6fd 100%)" data-admin-block-root>
        <div class="max-w-4xl">
            <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-[#1977b2]"<?php echo bioinmed_page_text_attr($sterilityPage, 'sterility', 'hero.eyebrow'); ?>><?php echo $e($sterilityHero['eyebrow'] ?? 'Безопасность пациента'); ?></p>
            <h1 class="mt-2 text-[1.8rem] font-bold leading-tight text-[#0f2749] md:text-[2.6rem]"<?php echo bioinmed_page_text_attr($sterilityPage, 'sterility', 'hero.heading'); ?>><?php echo $e($sterilityHero['heading'] ?? 'Стерильность — обязательная часть лечения'); ?></h1>
            <p class="mt-4 text-[1rem] leading-relaxed text-[#355b89]"<?php echo bioinmed_page_text_attr($sterilityPage, 'sterility', 'hero.intro'); ?>><?php echo $e($sterilityHero['intro'] ?? ''); ?></p>
        </div>
    </section>
    <section class="mt-8 grid gap-4 md:grid-cols-3"<?php echo bioinmed_editable_list_attrs('sterility', 'sterility.measures', 'Основные меры безопасности', true, 'Описание'); ?>>
        <?php echo bioinmed_editable_list_toolbar('div'); ?>
        <?php foreach ($sterilityMeasureItems as $measure): ?>
            <article class="rounded-2xl border border-[#d9e7f3] bg-white p-5 shadow-[0_8px_18px_rgba(8,36,70,0.05)]<?php echo bioinmed_editable_list_item_class($measure); ?>" data-admin-block-root<?php echo bioinmed_editable_list_item_attrs($measure); ?>>
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-[#e8f3fc] text-[#1977b2]"><i class="<?php echo $e($measure['icon'] ?: 'fa-solid fa-shield-heart'); ?>" data-admin-list-icon-view></i></span>
                <h2 class="mt-4 text-[1.05rem] font-bold text-[#0f2749]" data-admin-list-text-view><?php echo $e($measure['text']); ?></h2>
                <p class="mt-2 text-[0.9rem] leading-relaxed text-[#355b89]" data-admin-list-secondary-view><?php echo $e($measure['secondary']); ?></p>
                <?php echo bioinmed_editable_list_actions($measure); ?>
            </article>
        <?php endforeach; ?>
    </section>
    <section class="mt-8 rounded-3xl border border-[#d9e7f3] bg-white p-5 shadow-[0_12px_30px_rgba(8,36,70,0.08)] md:p-7">
        <div class="max-w-3xl" data-admin-block-root><p class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-[#1977b2]"<?php echo bioinmed_page_text_attr($sterilityPage, 'sterility', 'cycle.eyebrow'); ?>><?php echo $e($sterilityCycle['eyebrow'] ?? 'Полный цикл'); ?></p><h2 class="mt-2 text-[1.4rem] font-bold text-[#0f2749] md:text-[1.75rem]"<?php echo bioinmed_page_text_attr($sterilityPage, 'sterility', 'cycle.heading'); ?>><?php echo $e($sterilityCycle['heading'] ?? 'Этапы обработки инструментов'); ?></h2></div>
        <ol class="mt-6 grid gap-4 md:grid-cols-2"<?php echo bioinmed_editable_list_attrs('sterility', 'sterility.cycle.steps', 'Этапы обработки инструментов', false, 'Описание'); ?>>
            <?php echo bioinmed_editable_list_toolbar(); ?>
            <?php foreach ($sterilityStepItems as $stepIndex => $step): ?>
                <li class="flex gap-4 rounded-2xl border border-[#e1ebf4] bg-[#f8fbff] p-4<?php echo bioinmed_editable_list_item_class($step); ?>" data-admin-block-root<?php echo bioinmed_editable_list_item_attrs($step); ?>>
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#1977b2] font-bold text-white" data-admin-list-position><?php echo $stepIndex + 1; ?></span>
                    <div><h3 class="font-bold text-[#0f2749]" data-admin-list-text-view><?php echo $e($step['text']); ?></h3><p class="mt-1 text-[0.88rem] leading-relaxed text-[#355b89]" data-admin-list-secondary-view><?php echo $e($step['secondary']); ?></p></div>
                    <?php echo bioinmed_editable_list_actions($step); ?>
                </li>
            <?php endforeach; ?>
        </ol>
        <div class="mt-6 rounded-2xl border border-[#bcd9ed] bg-[#eaf5ff] p-4 text-[0.9rem] leading-relaxed text-[#17446f]" data-admin-block-root><strong<?php echo bioinmed_page_text_attr($sterilityPage, 'sterility', 'cycle.note_label'); ?>><?php echo $e($sterilityCycle['note_label'] ?? 'Важно:'); ?></strong> <span<?php echo bioinmed_page_text_attr($sterilityPage, 'sterility', 'cycle.note'); ?>><?php echo $e($sterilityCycle['note'] ?? ''); ?></span></div>
    </section>
    <section class="mt-8 grid gap-6 rounded-3xl border border-[#d9e7f3] bg-white p-5 md:grid-cols-2 md:p-7">
        <div data-admin-block-root><h2 class="text-[1.35rem] font-bold text-[#0f2749]"<?php echo bioinmed_page_text_attr($sterilityPage, 'sterility', 'cta.heading'); ?>><?php echo $e($sterilityCta['heading'] ?? 'Остались вопросы о безопасности?'); ?></h2><p class="mt-3 text-[0.92rem] leading-relaxed text-[#355b89]"<?php echo bioinmed_page_text_attr($sterilityPage, 'sterility', 'cta.text'); ?>><?php echo $e($sterilityCta['text'] ?? ''); ?></p></div>
        <div><?php echo bioinmed_render_callback_form(['source_label' => 'Стерильность — вопрос', 'submit_label' => $sterilitySubmitNode['value'], 'submit_label_attr' => $sterilitySubmitNode['attr']]); ?></div>
    </section>
</main>
<?php echo (new Footer($brand_colors))->render(); ?>
</body></html>
