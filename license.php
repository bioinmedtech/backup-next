<?php
require_once __DIR__ . '/includes/pin_protection.php';
bioinmed_pin_require_access();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/components/Components.php';
require_once __DIR__ . '/includes/content/AboutSectionNav.php';

$e = static function ($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$licensePage = bioinmed_read_json_file('pages/license.json');
$licenseMeta = is_array($licensePage['meta'] ?? null) ? $licensePage['meta'] : [];
$licenseHero = is_array($licensePage['hero'] ?? null) ? $licensePage['hero'] : [];
$siteUrl = rtrim(CLINIC_SITE_URL, '/');
$canonicalUrl = $siteUrl . '/license';
$pageTitle = trim((string)($licenseMeta['title'] ?? 'Лицензия клиники')) . ' | ' . CLINIC_NAME;
$pageDescription = trim((string)($licenseMeta['description'] ?? 'Медицинская лицензия и санитарно-эпидемиологические документы клиники БИОИНМЕД в Москве.'));
$socialImageUrl = bioinmed_default_social_image_url();
$documents = [];
for ($i = 1; $i <= 5; $i++) {
    $documents[] = bioinmed_versioned_asset_path('/public/images/license/license-page-' . $i . '.jpeg');
}
$renderWatermark = static function () {
    $items = '';
    for ($i = 0; $i < 20; $i++) {
        $items .= '<span aria-hidden="true"></span>';
    }
    return '<span class="license-watermark" aria-hidden="true">' . $items . '</span>';
};
$structuredData = bioinmed_medical_organization_schema();
$breadcrumbStructuredData = bioinmed_breadcrumb_schema([
    ['name' => 'Главная', 'url' => $siteUrl . '/'],
    ['name' => 'О клинике', 'url' => $siteUrl . '/about'],
    ['name' => 'Лицензия', 'url' => $canonicalUrl],
]);
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
    <?php echo bioinmed_render_social_meta($pageTitle, $pageDescription, $canonicalUrl, ['image' => $socialImageUrl]); ?>
    <?php echo bioinmed_render_favicon_links(CLINIC_ICON_PATH); ?>
    <?php echo bioinmed_render_public_head_assets(); ?>
    <style>
        .license-document {
            position: relative;
            isolation: isolate;
            display: block;
            width: 100%;
            aspect-ratio: 210 / 297;
            overflow: hidden;
            cursor: zoom-in;
        }
        .license-document > img {
            position: relative;
            z-index: 1;
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .license-watermark {
            position: absolute;
            z-index: 3;
            inset: -28%;
            display: grid;
            grid-template-columns: repeat(4, minmax(110px, 1fr));
            align-content: center;
            gap: 54px 34px;
            opacity: .22;
            transform: rotate(-45deg);
            transform-origin: center;
            pointer-events: none;
        }
        .license-watermark > span {
            display: block;
            height: 42px;
            background: url('/public/images/brand/main-logotype.png') center / contain no-repeat;
        }
        .license-modal-document {
            position: relative;
            isolation: isolate;
            display: inline-block;
            max-width: 100%;
            overflow: hidden;
            border-radius: 1rem;
            background: white;
            box-shadow: 0 18px 48px rgba(0, 0, 0, .35);
        }
        .license-modal-document > img {
            position: relative;
            z-index: 1;
            display: block;
            width: auto;
            max-width: 100%;
            max-height: calc(100vh - 3rem);
            object-fit: contain;
        }
        .license-modal-document .license-watermark { opacity: .2; }
        #license-document-modal {
            position: fixed;
            inset: 0;
            z-index: 10000;
            background: rgba(7, 21, 40, .82);
        }
    </style>
    <script type="application/ld+json"><?php echo json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script type="application/ld+json"><?php echo json_encode($breadcrumbStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <?php echo bioinmed_uis_counter_head(); ?>
</head>
<body class="min-h-screen bg-[#e4f1fa] text-[#0f2749] antialiased">
<?php echo bioinmed_yandex_metrika_noscript(); ?>
<?php echo (new Header($brand_colors))->render(); ?>

<main class="mx-auto max-w-6xl px-6 py-8 md:px-10 md:py-12">
    <?php echo bioinmed_render_about_breadcrumbs('Лицензия'); ?>

    <section class="mt-6 overflow-hidden rounded-3xl border border-[#d8e6f3] bg-white p-5 shadow-[0_14px_36px_rgba(8,36,70,0.08)] md:p-7" data-admin-block-root>
        <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-[#1977b2]"<?php echo bioinmed_page_text_attr($licensePage, 'license', 'hero.eyebrow'); ?>><?php echo $e($licenseHero['eyebrow'] ?? 'Правовая информация'); ?></p>
        <h1 class="mt-2 text-[1.8rem] font-bold leading-tight text-[#0f2749] md:text-[2.6rem]"<?php echo bioinmed_page_text_attr($licensePage, 'license', 'hero.heading'); ?>><?php echo $e($licenseHero['heading'] ?? 'Медицинская лицензия'); ?></h1>
        <p class="mt-4 max-w-4xl text-[0.98rem] leading-relaxed text-[#355b89]"<?php echo bioinmed_page_text_attr($licensePage, 'license', 'hero.intro'); ?>><?php echo $e($licenseHero['intro'] ?? 'Клиника осуществляет медицинскую деятельность на основании действующей лицензии. Ниже опубликована выписка из реестра лицензий и санитарно-эпидемиологические документы.'); ?></p>
    </section>

    <section class="mt-8">
        <div class="grid items-start gap-6 md:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($documents as $index => $document): ?>
                <figure class="rounded-2xl border border-[#d3e2ef] bg-white p-3 shadow-[0_12px_30px_rgba(8,36,70,0.10)]">
                    <button type="button" class="license-document rounded-xl border border-[#e0e8ef] bg-white" data-license-document="<?php echo $e($document); ?>" aria-label="Открыть лицензионный документ, страница <?php echo $index + 1; ?>">
                        <img src="<?php echo $e($document); ?>" alt="Лицензионный документ БИОИНМЕД, страница <?php echo $index + 1; ?>" loading="<?php echo $index < 2 ? 'eager' : 'lazy'; ?>" decoding="async">
                        <?php echo $renderWatermark(); ?>
                    </button>
                </figure>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<div id="license-document-modal" class="fixed inset-0 z-[100] hidden bg-[rgba(7,21,40,0.82)] px-4 py-6" role="dialog" aria-modal="true" aria-label="Просмотр лицензионного документа">
    <button type="button" id="license-document-modal-close" class="absolute right-5 top-5 z-10 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20" aria-label="Закрыть">
        <i class="fa-solid fa-xmark text-lg"></i>
    </button>
    <div class="mx-auto flex h-full max-w-6xl items-center justify-center">
        <div class="license-modal-document">
            <img id="license-document-modal-image" src="" alt="Лицензионный документ БИОИНМЕД">
            <?php echo $renderWatermark(); ?>
        </div>
    </div>
</div>

<?php echo (new Footer($brand_colors))->render(); ?>
<script>
    (function () {
        const modal = document.getElementById('license-document-modal');
        const modalImage = document.getElementById('license-document-modal-image');
        const closeButton = document.getElementById('license-document-modal-close');
        const documentButtons = Array.from(document.querySelectorAll('[data-license-document]'));

        function openDocument(button) {
            if (!modal || !modalImage) return;
            const preview = button.querySelector('img');
            modalImage.src = button.dataset.licenseDocument || '';
            modalImage.alt = preview ? preview.alt : 'Лицензионный документ БИОИНМЕД';
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            closeButton?.focus();
        }

        function closeDocument() {
            if (!modal) return;
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        documentButtons.forEach(function (button) {
            button.addEventListener('click', function () { openDocument(button); });
        });
        closeButton?.addEventListener('click', closeDocument);
        modal?.addEventListener('click', function (event) {
            if (!event.target.closest('.license-modal-document') && !event.target.closest('#license-document-modal-close')) {
                closeDocument();
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal?.classList.contains('hidden')) closeDocument();
        });
    })();
</script>
</body>
</html>
