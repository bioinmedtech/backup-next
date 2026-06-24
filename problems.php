<?php
require_once __DIR__ . '/includes/pin_protection.php';
bioinmed_pin_require_access();


require_once 'config.php';
require_once 'includes/components/Components.php';

$problemsPage = bioinmed_read_json_file('pages/problems.json');
$problemsMeta = is_array($problemsPage['meta'] ?? null) ? $problemsPage['meta'] : [];
$problemsInsideItems = is_array($problemsPage['inside_items'] ?? null) ? $problemsPage['inside_items'] : [];

$siteUrl = rtrim(CLINIC_SITE_URL, '/');
$iconPath = CLINIC_ICON_PATH;
$canonicalUrl = $siteUrl . '/problems';
$pageTitle = trim((string)($problemsMeta['title'] ?? '')) . (string)($problemsMeta['title_suffix'] ?? '');
$pageDescription = trim((string)($problemsMeta['description'] ?? '')) . ' ' . CLINIC_NAME . (string)($problemsMeta['description_suffix'] ?? '');
$socialImageUrl = bioinmed_default_social_image_url();
$organizationStructuredData = bioinmed_medical_organization_schema();
$breadcrumbStructuredData = bioinmed_breadcrumb_schema([
    ['name' => (string)($problemsPage['breadcrumbs']['home'] ?? ''), 'url' => '/'],
    ['name' => trim((string)($problemsMeta['title'] ?? '')), 'url' => '/problems'],
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
    <?php echo bioinmed_render_social_meta($pageTitle, $pageDescription, $canonicalUrl, ['image' => $socialImageUrl]); ?>
    <?php echo bioinmed_render_favicon_links($iconPath); ?>
    <script type="application/ld+json"><?php echo json_encode($organizationStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script type="application/ld+json"><?php echo json_encode($breadcrumbStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script src="/public/vendor/tailwind/tailwindcss-cdn.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/vendor/fontawesome/css/all.min.css">
    <style>
        body { background: #e4f1fa; color: #0f2749; line-height: 1.72; }
        html { scroll-behavior: smooth; }
        .fade-up { opacity: 0; transform: translateY(22px); transition: opacity .55s ease, transform .55s ease; }
        .fade-up.visible { opacity: 1; transform: translateY(0); }
    </style>
    <?php echo bioinmed_uis_counter_head(); ?>
</head>
<body class="flex min-h-screen flex-col antialiased">
<?php
$header = new Header($brand_colors);
echo $header->render();
?>

<main class="grow">
    <section class="border-b border-[#e6eef7] bg-[#e4f1fa] py-12 md:py-16">
        <div class="mx-auto max-w-6xl px-6 md:px-10">
            <div class="grid gap-8 lg:grid-cols-[1.15fr_0.85fr] lg:items-start">
                <div>
                    <p class="text-[0.74rem] font-semibold uppercase tracking-[0.22em] text-[#1977b2]"><?php echo e($problemsMeta['hero_eyebrow'] ?? ''); ?></p>
                    <h1 class="mt-2 text-[1.9rem] font-bold leading-tight text-[#0f2749] md:text-[2.45rem]"><?php echo e($problemsMeta['hero_heading'] ?? ''); ?></h1>
                    <p class="mt-4 max-w-3xl text-[1rem] leading-relaxed text-[#0a293c] md:text-[1.05rem]">
                        <?php echo e($problemsMeta['hero_text'] ?? ''); ?>
                    </p>
                </div>
                <div class="rounded-[2rem] bg-white p-6 shadow-[0_14px_34px_rgba(10,43,80,0.08)]">
                    <p class="text-[0.75rem] font-semibold uppercase tracking-[0.2em] text-[#1977b2]"><?php echo e($problemsMeta['inside_title'] ?? ''); ?></p>
                    <ul class="mt-4 space-y-3 text-[0.96rem] leading-relaxed text-[#0a293c]">
                        <?php foreach ($problemsInsideItems as $item): ?>
                            <li class="flex items-start gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-[#1977b2]"></span><span><?php echo e($item); ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <?php
    $problems_section = new ProblemsGrid($problems, $brand_colors, ['show_title' => false, 'show_cta' => false]);
    echo $problems_section->render();
    ?>
</main>

<?php
$footer = new Footer($brand_colors);
echo $footer->render();
?>

<script>
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
    document.querySelectorAll('.fade-up').forEach(function(el) { observer.observe(el); });
</script>
</body>
</html>
