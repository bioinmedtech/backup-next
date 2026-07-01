<?php
require_once 'config.php';
require_once 'includes/components/Components.php';

$privacyPage = bioinmed_read_json_file('pages/privacy.json');
$privacyMeta = is_array($privacyPage['meta'] ?? null) ? $privacyPage['meta'] : [];
$privacyCards = is_array($privacyPage['cards'] ?? null) ? $privacyPage['cards'] : [];
$privacySections = is_array($privacyPage['sections'] ?? null) ? $privacyPage['sections'] : [];

$siteUrl = rtrim(CLINIC_SITE_URL, '/');
$iconPath = CLINIC_ICON_PATH;
$socialImageUrl = bioinmed_default_social_image_url();
$canonicalUrl = $siteUrl . '/privacy';
$pageTitle = trim((string)($privacyMeta['title'] ?? 'Политика конфиденциальности')) . ' | ' . CLINIC_NAME;
$pageDescription = trim((string)($privacyMeta['description'] ?? 'Политика конфиденциальности клиники')) . ' ' . CLINIC_NAME . ': порядок обработки персональных данных, контактная информация и права пользователя.';

$structuredData = bioinmed_medical_organization_schema();
$breadcrumbStructuredData = bioinmed_breadcrumb_schema([
    ['name' => 'Главная', 'url' => $siteUrl . '/'],
    ['name' => trim((string)($privacyMeta['title'] ?? 'Политика конфиденциальности')), 'url' => $canonicalUrl],
]);

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
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
    <meta name="theme-color" content="#248CFF">
    <?php echo bioinmed_render_social_meta($pageTitle, $pageDescription, $canonicalUrl, [
        'image' => $socialImageUrl,
    ]); ?>
    <?php echo bioinmed_render_favicon_links($iconPath); ?>
    <?php echo bioinmed_render_public_head_assets(); ?>
    <script type="application/ld+json"><?php echo json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script type="application/ld+json"><?php echo json_encode($breadcrumbStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <?php echo bioinmed_uis_counter_head(); ?>
</head>
<body class="min-h-screen bg-[#e4f1fa] text-[#0f2749] antialiased">
    <?php echo bioinmed_yandex_metrika_noscript(); ?>
<?php
$header = new Header($brand_colors);
echo $header->render();
?>

<main class="mx-auto max-w-6xl px-6 py-10 md:px-10 md:py-14">
    <section class="pb-8">
        <div class="max-w-4xl" data-admin-block-root>
            <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-[#2a5a94]"<?php echo bioinmed_page_text_attr($privacyPage, 'privacy', 'meta.eyebrow'); ?>><?php echo e($privacyMeta['eyebrow'] ?? 'Правовая информация'); ?></p>
            <h1 class="mt-2 text-[1.8rem] font-bold leading-tight text-[#0f2749] md:text-[2.6rem]"<?php echo bioinmed_page_text_attr($privacyPage, 'privacy', 'meta.title'); ?>><?php echo e($privacyMeta['title'] ?? 'Политика конфиденциальности'); ?></h1>
            <p class="mt-4 text-[0.98rem] leading-relaxed text-[#355b89]"<?php echo bioinmed_page_text_attr($privacyPage, 'privacy', 'meta.intro'); ?>>
                <?php echo e(str_replace('клиника', 'клиника ' . CLINIC_NAME, (string)($privacyMeta['intro'] ?? ''))); ?>
            </p>
            <div class="mt-4 flex flex-col gap-1 text-[0.82rem] text-[#0a293c] sm:flex-row sm:flex-wrap sm:gap-4">
                <p<?php echo bioinmed_page_text_attr($privacyPage, 'privacy', 'meta.effective_date'); ?>><span class="font-semibold text-[#2a5a94]"><?php echo e($privacyMeta['effective_date_label'] ?? 'Дата вступления в силу:'); ?></span> <?php echo e($privacyMeta['effective_date'] ?? '30.04.2026'); ?></p>
                <p<?php echo bioinmed_page_text_attr($privacyPage, 'privacy', 'meta.updated_date'); ?>><span class="font-semibold text-[#2a5a94]"><?php echo e($privacyMeta['updated_date_label'] ?? 'Дата последнего изменения:'); ?></span> <?php echo e($privacyMeta['updated_date'] ?? '30.04.2026'); ?></p>
            </div>
        </div>

        <div class="mt-8 grid gap-5 border-y border-[#d8e6f3] py-6 md:grid-cols-3">
            <div data-admin-block-root>
                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.14em] text-[#2a5a94]"<?php echo bioinmed_page_text_attr($privacyPage, 'privacy', 'cards.operator.title'); ?>><?php echo e($privacyCards['operator']['title'] ?? 'Оператор данных'); ?></p>
                <p class="mt-2 text-[0.95rem] leading-relaxed text-[#355b89]"><?php echo e(CLINIC_NAME); ?><br><?php echo e(CLINIC_ADDRESS); ?></p>
            </div>
            <div data-admin-block-root>
                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.14em] text-[#2a5a94]"<?php echo bioinmed_page_text_attr($privacyPage, 'privacy', 'cards.contacts.title'); ?>><?php echo e($privacyCards['contacts']['title'] ?? 'Контакты'); ?></p>
                <p class="mt-2 text-[0.95rem] leading-relaxed text-[#355b89]"><?php echo e(CLINIC_PHONE); ?><br><?php echo e(CLINIC_EMAIL); ?></p>
            </div>
            <div data-admin-block-root>
                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.14em] text-[#2a5a94]"<?php echo bioinmed_page_text_attr($privacyPage, 'privacy', 'cards.purpose.title'); ?>><?php echo e($privacyCards['purpose']['title'] ?? 'Назначение обработки'); ?></p>
                <p class="mt-2 text-[0.95rem] leading-relaxed text-[#355b89]"<?php echo bioinmed_page_text_attr($privacyPage, 'privacy', 'cards.purpose.text'); ?>><?php echo e($privacyCards['purpose']['text'] ?? ''); ?></p>
            </div>
        </div>

        <div class="mt-8 max-w-4xl space-y-8 text-[0.98rem] leading-relaxed text-[#355b89]">
            <?php foreach ($privacySections as $index => $section): ?>
                <?php
                $isLast = ($index === count($privacySections) - 1);
                $sectionKey = trim((string)($section['id'] ?? ('section_' . $index)));
                $title = trim((string)($section['title'] ?? ''));
                $paragraphEntries = is_array($section['paragraphs'] ?? null) ? $section['paragraphs'] : [];
                ?>
                <section class="<?php echo $isLast ? '' : 'border-b border-[#e2ecf5] pb-6'; ?>" data-admin-block-root>
                    <h2 class="text-[1.16rem] font-bold text-[#0f2749]"<?php echo bioinmed_page_text_attr($privacyPage, 'privacy', 'sections.' . $sectionKey . '.title'); ?>><?php echo e($title); ?></h2>
                    <?php foreach ($paragraphEntries as $paragraphIndex => $paragraphEntry): ?>
                        <?php
                        if (is_array($paragraphEntry)) {
                            $paragraphKey = trim((string)($paragraphEntry['id'] ?? ('p' . $paragraphIndex)));
                            $paragraphText = (string)($paragraphEntry['text'] ?? '');
                        } else {
                            $paragraphKey = 'p' . $paragraphIndex;
                            $paragraphText = (string)$paragraphEntry;
                        }
                        $line = str_replace('{{clinic_email}}', CLINIC_EMAIL, $paragraphText);
                        ?>
                        <p class="mt-3"<?php echo bioinmed_page_text_attr($privacyPage, 'privacy', 'sections.' . $sectionKey . '.paragraphs.' . $paragraphKey); ?>><?php echo e($line); ?></p>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<?php
$footer = new Footer($brand_colors);
echo $footer->render();
?>
</body>
</html>
