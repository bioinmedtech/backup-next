<?php
require_once 'config.php';
require_once 'includes/components/Components.php';

$agreementPage = bioinmed_read_json_file('pages/user-agreement.json');
$agreementMeta = is_array($agreementPage['meta'] ?? null) ? $agreementPage['meta'] : [];
$agreementSections = is_array($agreementPage['sections'] ?? null) ? $agreementPage['sections'] : [];

$siteUrl = rtrim(CLINIC_SITE_URL, '/');
$iconPath = CLINIC_ICON_PATH;
$socialImageUrl = bioinmed_default_social_image_url();
$canonicalUrl = $siteUrl . '/user-agreement';
$pageTitle = trim((string)($agreementMeta['title'] ?? 'Пользовательское соглашение')) . ' | ' . CLINIC_NAME;
$pageDescription = trim((string)($agreementMeta['description'] ?? 'Пользовательское соглашение сайта клиники')) . ' ' . CLINIC_NAME . ': условия использования сайта, записи через формы и обратной связи.';

$structuredData = bioinmed_medical_organization_schema();
$breadcrumbStructuredData = bioinmed_breadcrumb_schema([
    ['name' => 'Главная', 'url' => $siteUrl . '/'],
    ['name' => trim((string)($agreementMeta['title'] ?? 'Пользовательское соглашение')), 'url' => $canonicalUrl],
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
    <meta name="theme-color" content="#1977b2">
    <?php echo bioinmed_render_social_meta($pageTitle, $pageDescription, $canonicalUrl, [
        'image' => $socialImageUrl,
    ]); ?>
    <?php echo bioinmed_render_favicon_links($iconPath); ?>
    <script src="/public/vendor/tailwind/tailwindcss-cdn.js"></script>
    <link rel="stylesheet" href="/public/vendor/fontawesome/css/all.min.css">
    <script type="application/ld+json"><?php echo json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script type="application/ld+json"><?php echo json_encode($breadcrumbStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <?php echo bioinmed_uis_counter_head(); ?>
</head>
<body class="min-h-screen bg-[#e4f1fa] text-[#0f2749] antialiased">
<?php
$header = new Header($brand_colors);
echo $header->render();
?>

<main class="mx-auto max-w-6xl px-6 py-10 md:px-10 md:py-14">
    <section class="pb-8">
        <div class="max-w-4xl">
            <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-[#0a293c]"><?php echo e($agreementMeta['eyebrow'] ?? 'Правовая информация'); ?></p>
            <h1 class="mt-2 text-[1.8rem] font-bold leading-tight text-[#0f2749] md:text-[2.6rem]"><?php echo e($agreementMeta['title'] ?? 'Пользовательское соглашение'); ?></h1>
            <p class="mt-4 text-[0.98rem] leading-relaxed text-[#0a293c]">
                <?php
                $intro = str_replace('клиники', 'клиники ' . CLINIC_NAME, (string)($agreementMeta['intro'] ?? ''));
                echo e($intro);
                ?>
            </p>
            <div class="mt-4 flex flex-col gap-1 text-[0.82rem] text-[#0a293c] sm:flex-row sm:flex-wrap sm:gap-4">
                <p><span class="font-semibold text-[#0a293c]"><?php echo e($agreementMeta['effective_date_label'] ?? 'Дата вступления в силу:'); ?></span> <?php echo e($agreementMeta['effective_date'] ?? '30.04.2026'); ?></p>
                <p><span class="font-semibold text-[#0a293c]"><?php echo e($agreementMeta['updated_date_label'] ?? 'Дата последнего изменения:'); ?></span> <?php echo e($agreementMeta['updated_date'] ?? '30.04.2026'); ?></p>
            </div>
        </div>

        <div class="mt-8 max-w-4xl space-y-8 text-[0.98rem] leading-relaxed text-[#0a293c]">
            <?php foreach ($agreementSections as $index => $section): ?>
                <?php
                $isLast = ($index === count($agreementSections) - 1);
                $title = trim((string)($section['title'] ?? ''));
                $paragraphs = is_array($section['paragraphs'] ?? null) ? $section['paragraphs'] : [];
                ?>
                <section class="<?php echo $isLast ? '' : 'border-b border-[#e2ecf5] pb-6'; ?>">
                    <h2 class="text-[1.16rem] font-bold text-[#0f2749]"><?php echo e($title); ?></h2>
                    <?php foreach ($paragraphs as $paragraph): ?>
                        <?php
                        $line = str_replace('{{clinic_phone}}', CLINIC_PHONE, (string)$paragraph);
                        $line = str_replace('{{clinic_email}}', CLINIC_EMAIL, $line);
                        ?>
                        <p class="mt-3"><?php echo e($line); ?></p>
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
