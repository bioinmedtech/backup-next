<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/pin_protection.php';
bioinmed_pin_require_access();


require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/components/Components.php';

$seasonPage = bioinmed_read_json_file('pages/season.json');
$seasonHeroText = is_array($seasonPage['hero'] ?? null) ? $seasonPage['hero'] : [];
$seasonIntroText = is_array($seasonPage['intro'] ?? null) ? $seasonPage['intro'] : [];
$seasonTipsText = is_array($seasonPage['tips'] ?? null) ? $seasonPage['tips'] : [];
$seasonServicesText = is_array($seasonPage['services'] ?? null) ? $seasonPage['services'] : [];
$seasonCtaText = is_array($seasonPage['cta'] ?? null) ? $seasonPage['cta'] : [];
$seasonNavigationText = is_array($seasonPage['navigation'] ?? null) ? $seasonPage['navigation'] : [];
$seasonPopupText = is_array($seasonPage['popup'] ?? null) ? $seasonPage['popup'] : [];
$seasonTitlesPage = is_array($seasonPage['titles'] ?? null) ? $seasonPage['titles'] : [];

$seasons = require __DIR__ . '/config/seasons.php';
$services = require __DIR__ . '/config/services.php';

$slug = $_GET['slug'] ?? '';
if (!array_key_exists($slug, $seasons)) {
    http_response_code(404);
    header('Location: /seasons/spring');
    exit;
}

$s = $seasons[$slug];
$e = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$services_by_id = [];
foreach ($services as $service) {
    if (isset($service['id'])) {
        $services_by_id[$service['id']] = $service;
    }
}

$asset_exists = static function (string $path): bool {
    $local_path = __DIR__ . '/' . ltrim($path, '/');

    return is_file($local_path) && filesize($local_path) > 4096;
};

$gallery_items = [];
foreach ($s['gallery'] ?? [] as $item) {
    if (!isset($item['image']) || !$asset_exists($item['image'])) {
        continue;
    }
    if ($item['image'] === ($s['image_desktop'] ?? $s['image']) || $item['image'] === ($s['image_mobile'] ?? $s['image'])) {
        continue;
    }
    $gallery_items[] = $item;
}

$season_services = [];
foreach ($s['services'] ?? [] as $service_item) {
    $service_id = $service_item['id'] ?? null;
    if (!$service_id || !isset($services_by_id[$service_id])) {
        continue;
    }

    $season_services[] = [
        'id' => $service_id,
        'label' => $service_item['label'] ?? $services_by_id[$service_id]['name'],
        'desc' => $service_item['desc'] ?? $services_by_id[$service_id]['description'],
        'subtitle' => $services_by_id[$service_id]['subtitle'] ?? '',
    ];
}

$next = $seasons[$s['next']];
$prev = $seasons[$s['prev']];
$actual_season_slug = bioinmed_current_season_slug();

$hero_image_desktop = $s['image_desktop'] ?? $s['image'];
$hero_image_mobile = $s['image_mobile'] ?? $s['image'];
$hero_image_desktop_alt = $s['image_alt'] ?? '';
$hero_image_mobile_alt = $s['image_mobile_alt'] ?? $hero_image_desktop_alt;
$season_art_popup_items = [];
foreach ($gallery_items as $item) {
    $season_art_popup_items[] = [
        'image' => (string)($item['image'] ?? ''),
        'alt'   => (string)($item['alt'] ?? ''),
    ];
}

$season_titles = [
    'spring' => [
        'health' => 'Весеннее обновление здоровья',
        'gallery' => 'Весенние пейзажи',
        'tips' => 'Весенние ориентиры для самочувствия',
        'services' => 'Процедуры для весеннего восстановления',
        'cta' => 'Запишитесь на весеннюю консультацию',
        'nav' => 'Другие времена года',
    ],
    'summer' => [
        'health' => 'Летний ресурс и выносливость',
        'gallery' => 'Летние пейзажи',
        'tips' => 'Летние ориентиры для самочувствия',
        'services' => 'Процедуры для летнего тонуса',
        'cta' => 'Запишитесь на летнюю консультацию',
        'nav' => 'Другие времена года',
    ],
    'autumn' => [
        'health' => 'Осенний баланс и устойчивость',
        'gallery' => 'Осенние пейзажи',
        'tips' => 'Осенние ориентиры для самочувствия',
        'services' => 'Процедуры для осенней адаптации',
        'cta' => 'Запишитесь на осеннюю консультацию',
        'nav' => 'Другие времена года',
    ],
    'winter' => [
        'health' => 'Зимнее восстановление и тепло',
        'gallery' => 'Зимние пейзажи',
        'tips' => 'Зимние ориентиры для самочувствия',
        'services' => 'Процедуры для зимней поддержки',
        'cta' => 'Запишитесь на зимнюю консультацию',
        'nav' => 'Другие времена года',
    ],
];
$season_titles = array_replace_recursive($season_titles, $seasonTitlesPage);
$titles = $season_titles[$slug] ?? [
    'health' => ($seasonIntroText['health_default'] ?? 'Здоровье сезона') . ' ' . $s['name_gen'],
    'gallery' => 'Пейзажи ' . $s['name_gen'],
    'tips' => $seasonTipsText['title_default'] ?? 'Советы для Вашего здоровья',
    'services' => $seasonServicesText['title_default'] ?? ('Услуги для ' . $s['name_gen']),
    'cta' => $seasonCtaText['title_default'] ?? 'Запишитесь на консультацию',
    'nav' => $seasonNavigationText['title_default'] ?? 'Другие времена года',
];

$seasonHealthTitleNode = bioinmed_page_text_node($seasonPage, 'season', 'titles.' . $slug . '.health', $titles['health']);
$seasonTipsTitleNode = bioinmed_page_text_node($seasonPage, 'season', 'titles.' . $slug . '.tips', $titles['tips']);
$seasonServicesTitleNode = bioinmed_page_text_node($seasonPage, 'season', 'titles.' . $slug . '.services', $titles['services']);
$seasonCtaTitleNode = bioinmed_page_text_node($seasonPage, 'season', 'titles.' . $slug . '.cta', $titles['cta']);
$seasonNavTitleNode = bioinmed_page_text_node($seasonPage, 'season', 'titles.' . $slug . '.nav', $titles['nav']);

$page_title  = $s['name'] . ' — Времена года | БИОИНМЕД';
$page_desc   = $s['intro'];
$canonical   = rtrim((string)CLINIC_SITE_URL, '/') . '/seasons/' . $slug;
$social_image = bioinmed_absolute_url($hero_image_desktop);
$social_image_alt = $hero_image_desktop_alt . ' — ' . $s['name'] . ' в проекте «Времена года»';

$header = new Header();
$footer = new Footer();
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $e($page_title) ?></title>
    <meta name="description" content="<?= $e($page_desc) ?>">
    <meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
    <link rel="canonical" href="<?= $e($canonical) ?>">
    <meta name="theme-color" content="<?= $e($s['color']) ?>">
    <?= bioinmed_render_favicon_links() ?>
    <?= bioinmed_render_social_meta($page_title, $page_desc, $canonical, [
        'image' => $social_image,
        'image_alt' => $social_image_alt,
    ]) ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="<?php echo bioinmed_versioned_asset_path('/public/assets/css/site.css'); ?>">
    <link rel="preload" href="<?php echo bioinmed_versioned_asset_path('/public/assets/css/fontawesome-subset.css'); ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="<?php echo bioinmed_versioned_asset_path('/public/assets/css/fontawesome-subset.css'); ?>"></noscript>
    <style>
        * {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', 'SF Pro Display', 'SF Pro Text', sans-serif;
        }
        .season-hero {
            position: relative;
            height: calc(100svh - var(--header-height, 0px));
            min-height: 480px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .season-hero__bg {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
        }
        .season-hero__video {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            opacity: 0;
            transition: opacity 0.6s ease-in;
        }
        .season-hero__video.loaded {
            opacity: 1;
        }
        .season-hero__video.loaded ~ div {
            /* Скрыть фоновое изображение, когда видео загружено */
        }
        .season-hero__overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(
                to top,
                rgba(0,0,0,0.80) 0%,
                rgba(0,0,0,0.45) 45%,
                rgba(0,0,0,0.10) 100%
            );
        }
        .season-hero__content {
            position: relative;
            z-index: 1;
            width: 100%;
            padding: 2rem 0;
        }
        .season-nav-dot.active {
            background: #e4f1fa;
            width: 2rem;
        }
        .season-gallery-shell {
            margin-top: 2rem;
            padding: 0;
        }
        .season-gallery-masonry {
            column-gap: 1rem;
            columns: 1;
        }
        .season-gallery-slide {
            position: relative;
            display: inline-block;
            width: 100%;
            margin: 0 0 1rem;
            overflow: hidden;
            border-radius: 1.3rem;
            background: rgba(255,255,255,0.08);
            box-shadow: 0 18px 36px rgba(1, 10, 20, 0.22);
            break-inside: avoid;
            transition: transform .28s ease, box-shadow .28s ease;
            cursor: pointer;
        }
        .season-gallery-slide:hover {
            transform: translateY(-4px);
            box-shadow: 0 24px 44px rgba(1, 10, 20, 0.28);
        }
        .season-gallery-slide img {
            display: block;
            width: 100%;
            height: auto;
            object-fit: cover;
        }
        .season-gallery-slide::after {
            display: none;
        }
        .season-gallery-slide__caption {
            position: absolute;
            inset-inline: 0;
            bottom: 0;
            z-index: 1;
            padding: 1rem 1.25rem 1.1rem;
            background: linear-gradient(to top, rgba(5,16,30,0.88) 0%, rgba(5,16,30,0.55) 60%, transparent 100%);
            color: #fff;
        }
        .season-gallery-slide__title {
            font-size: 1.02rem;
            font-weight: 700;
            line-height: 1.2;
            text-shadow: 0 1px 4px rgba(0,0,0,0.6);
        }
        .season-gallery-slide__meta {
            margin-top: 0.35rem;
            font-size: 0.84rem;
            color: rgba(255,255,255,0.9);
            text-shadow: 0 1px 3px rgba(0,0,0,0.5);
        }
        .season-gallery-slide__museum {
            margin-top: 0.5rem;
            font-size: 0.72rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.75);
            text-shadow: 0 1px 3px rgba(0,0,0,0.5);
        }
        .season-gallery-lead {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .season-gallery-lead__hint {
            font-size: 0.88rem;
            line-height: 1.45;
            color: #5b7898;
        }
        .season-practice-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        .season-practice-card {
            position: relative;
            overflow: hidden;
            border-radius: 1.5rem;
            border: 1px solid rgba(13, 42, 72, 0.08);
            background: linear-gradient(180deg, rgba(228,241,250,0.95) 0%, rgba(255,255,255,0.98) 100%);
            padding: 1.05rem;
            box-shadow: 0 14px 30px rgba(8, 36, 70, 0.08);
            transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
        }
        .season-practice-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 4px;
            background: var(--season-accent, #1977b2);
        }
        .season-practice-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 36px rgba(8, 36, 70, 0.12);
            border-color: rgba(36, 140, 255, 0.34);
        }
        .season-practice-card__kicker {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 9999px;
            background: rgba(25, 119, 178, 0.12);
            padding: 0.28rem 0.68rem;
            color: #0a293c;
            font-size: 0.84rem;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
        }
        .season-practice-card__title {
            margin-top: 0.7rem;
            font-size: 1.2rem;
            font-weight: 700;
            color: #0a293c;
            line-height: 1.25;
        }
        .season-practice-card__subtitle {
            margin-top: 0.45rem;
            font-size: 0.88rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #7a91aa;
            font-weight: 600;
        }
        .season-practice-card__desc {
            margin-top: 0.7rem;
            font-size: 1.03rem;
            line-height: 1.6;
            color: #4f6f91;
        }
        .season-practice-card__cta {
            margin-top: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.42rem;
            color: #214f80;
            font-size: 0.96rem;
            font-weight: 700;
        }
        .season-practice-card__cta i {
            font-size: 0.66rem;
            transition: transform .2s ease;
        }
        .season-practice-card:hover .season-practice-card__cta i {
            transform: translateX(3px);
        }
        .season-art-popup-backdrop {
            position: fixed;
            inset: 0;
            z-index: 130;
            display: none;
            background: rgba(7, 21, 40, 0.82);
            backdrop-filter: blur(2px);
        }
        .season-art-popup-backdrop.open {
            display: block;
        }
        .season-art-popup {
            position: fixed;
            inset: 0;
            z-index: 131;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .season-art-popup.open {
            display: flex;
        }
        .season-art-popup__card {
            position: relative;
            width: auto;
            max-width: min(90vw, 1120px);
            max-height: min(88svh, 860px);
            overflow: hidden;
            border-radius: 1.7rem;
            background: #071a2d;
            box-shadow: 0 26px 64px rgba(0, 0, 0, 0.42);
        }
        .season-art-popup__image-wrap {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg, rgba(8,22,38,0.12) 0%, rgba(8,22,38,0.26) 100%);
        }
        .season-art-popup__image-wrap::after {
            content: '';
            position: absolute;
            inset-inline: 0;
            bottom: 0;
            height: min(34%, 220px);
            background: linear-gradient(to top, rgba(6,18,32,0.72) 0%, rgba(6,18,32,0.34) 55%, rgba(6,18,32,0) 100%);
            pointer-events: none;
            z-index: 1;
        }
        .season-art-popup__image {
            display: block;
            width: auto;
            height: auto;
            max-width: min(90vw, 1120px);
            max-height: min(88svh, 860px);
            object-fit: contain;
        }
        .season-art-popup__close {
            position: absolute;
            top: 0.9rem;
            right: 0.9rem;
            z-index: 3;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.7rem;
            height: 2.7rem;
            border: 0;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            backdrop-filter: blur(4px);
            cursor: pointer;
        }
        .season-art-popup__nav {
            position: absolute;
            top: 50%;
            z-index: 3;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.8rem;
            height: 2.8rem;
            border: 0;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            transform: translateY(-50%);
            cursor: pointer;
            backdrop-filter: blur(4px);
        }
        .season-art-popup__nav--prev {
            left: 0.9rem;
        }
        .season-art-popup__nav--next {
            right: 0.9rem;
        }
        .season-art-popup__caption {
            position: absolute;
            left: 1.1rem;
            right: auto;
            bottom: 1.1rem;
            z-index: 2;
            width: min(480px, calc(100% - 2.2rem));
        }
        .season-art-popup__title {
            color: #fff;
            font-size: 0.94rem;
            font-weight: 700;
            line-height: 1.25;
            text-shadow: 0 2px 8px rgba(0,0,0,0.44);
        }
        .season-art-popup__meta {
            margin-top: 0.35rem;
            color: rgba(255,255,255,0.82);
            font-size: 0.76rem;
            text-shadow: 0 1px 6px rgba(0,0,0,0.4);
        }
        .season-art-popup__museum {
            margin-top: 0.5rem;
            color: rgba(255,255,255,0.66);
            font-size: 0.64rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            text-shadow: 0 1px 6px rgba(0,0,0,0.36);
        }
        @media (max-width: 767px) {
            .season-gallery-shell {
                margin-top: 1.35rem;
            }
            .season-gallery-masonry {
                columns: 1;
            }
            .season-gallery-lead {
                align-items: flex-start;
                flex-direction: column;
                gap: 0.4rem;
            }
            .season-art-popup {
                padding: 0.5rem;
            }
            .season-art-popup__card {
                width: auto;
                max-width: calc(100vw - 1rem);
                max-height: calc(100svh - 1rem);
                border-radius: 1rem;
            }
            .season-art-popup__image {
                max-width: calc(100vw - 1rem);
                max-height: calc(100svh - 1rem);
            }
            .season-art-popup__caption {
                left: 0.75rem;
                right: auto;
                bottom: 0.85rem;
                width: min(360px, calc(100% - 1.5rem));
            }
            .season-art-popup__nav {
                top: auto;
                bottom: 8.8rem;
                transform: none;
            }
            .season-art-popup__nav--prev {
                left: 0.75rem;
            }
            .season-art-popup__nav--next {
                right: 0.75rem;
            }
        }
        @media (min-width: 768px) {
            .season-gallery-masonry {
                columns: 2;
            }
            .season-practice-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 1.15rem;
            }
            .season-practice-card {
                padding: 1.2rem;
            }
        }
    </style>
    <?php echo bioinmed_uis_counter_head(); ?>
</head>
<body class="bg-[#e4f1fa] antialiased">
<?= $header->render() ?>

<!-- ═══════════════ HERO ═══════════════ -->
<section class="season-hero" aria-label="<?= $e($s['name']) ?>">
    <!-- Desktop video/background -->
    <div class="season-hero__bg hidden md:block" role="img" aria-label="<?= $e($hero_image_desktop_alt) ?>" style="<?php if (!empty($s['video_desktop'])): ?>background-image:url('<?= $e($hero_image_desktop) ?>');background-size:cover;background-position:center;<?php endif ?>">
        <?php if (!empty($s['video_desktop'])): ?>
            <video class="season-hero__video" poster="<?= $e($hero_image_desktop) ?>" autoplay muted loop playsinline preload="metadata">
                <source src="<?= $e($s['video_desktop']) ?>" type="video/mp4">
                <img src="<?= $e($hero_image_desktop) ?>" alt="<?= $e($hero_image_desktop_alt) ?>" style="width:100%;height:100%;object-fit:cover;">
            </video>
        <?php else: ?>
            <div style="background-image:url('<?= $e($hero_image_desktop) ?>');background-size:cover;background-position:center;width:100%;height:100%;"></div>
        <?php endif ?>
    </div>
    
    <!-- Mobile video/background -->
    <div class="season-hero__bg md:hidden" role="img" aria-label="<?= $e($hero_image_mobile_alt) ?>" style="<?php if (!empty($s['video_mobile'])): ?>background-image:url('<?= $e($hero_image_mobile) ?>');background-size:cover;background-position:center;<?php endif ?>">
        <?php if (!empty($s['video_mobile'])): ?>
            <video class="season-hero__video" poster="<?= $e($hero_image_mobile) ?>" autoplay muted loop playsinline preload="metadata">
                <source src="<?= $e($s['video_mobile']) ?>" type="video/mp4">
                <img src="<?= $e($hero_image_mobile) ?>" alt="<?= $e($hero_image_mobile_alt) ?>" style="width:100%;height:100%;object-fit:cover;">
            </video>
        <?php else: ?>
            <div style="background-image:url('<?= $e($hero_image_mobile) ?>');background-size:cover;background-position:center;width:100%;height:100%;"></div>
        <?php endif ?>
    </div>
    
    <div class="season-hero__overlay"></div>

    <!-- Season switcher -->
    <div class="absolute top-4 left-1/2 -translate-x-1/2 md:top-1/2 md:right-6 md:left-auto md:-translate-x-0 md:-translate-y-1/2 lg:right-10 flex flex-row md:flex-col gap-2 md:gap-3 z-10">
        <?php foreach ($seasons as $key => $sv): ?>
        <a href="/seasons/<?= $e($key) ?>"
           class="flex items-center gap-2.5 group <?= $key === $slug ? 'opacity-100' : 'opacity-50 hover:opacity-80' ?> transition-opacity"
           title="<?= $e($sv['name']) ?>">
            <?php if ($key === $slug): ?>
            <span class="block w-2 h-2 rounded-full flex-shrink-0" style="background:<?= $e($sv['color']) ?>"></span>
            <?php else: ?>
            <span class="block w-1.5 h-1.5 rounded-full flex-shrink-0 bg-white/40"></span>
            <?php endif ?>
            <span class="text-white text-base md:text-lg font-semibold leading-none"><?= $e($sv['name']) ?></span>
        </a>
        <?php endforeach ?>
    </div>

    <div class="season-hero__content">
        <div class="mx-auto max-w-6xl px-6 md:px-10" data-admin-block-root>

            <!-- Main hero text -->
            <p class="text-[0.86rem] md:text-[0.92rem] font-semibold tracking-[0.16em] uppercase mb-2.5" style="color:<?= $e($s['color']) ?>"<?= bioinmed_page_text_attr($seasonPage, 'season', 'hero.eyebrow') ?>>
                <?= $e($seasonHeroText['eyebrow'] ?? 'Времена года') ?>
            </p>
            <h1 class="text-4xl md:text-6xl font-black text-white leading-none mb-4"<?= bioinmed_page_text_attr($seasonPage, 'season', 'hero.name') ?>>
                <?= $e($s['name']) ?>
            </h1>
            <p class="text-[1.16rem] md:text-[1.3rem] font-light mb-4 max-w-xl leading-relaxed" style="color:rgba(255,255,255,0.92)"<?= bioinmed_page_text_attr($seasonPage, 'season', 'hero.slogan') ?>>
                <?= $e($s['slogan']) ?>
            </p>
            <blockquote class="text-[1.06rem] md:text-[1.18rem] max-w-2xl pl-3.5 leading-relaxed" style="color:rgba(255,255,255,0.9);border-left:4px solid <?= $e($s['color']) ?>;font-family:'Caveat',cursive;font-size:clamp(1.35rem,2.5vw,1.75rem);font-weight:700;">
                <?= $e($s['quote']) ?>
            </blockquote>


        </div>
    </div>
</section>

<!-- ═══════════════ INTRO ═══════════════ -->
<section class="py-14 md:py-20" style="background:<?= $e($s['color_light']) ?>">
    <div class="mx-auto max-w-6xl px-6 md:px-10">
        <div class="mx-auto max-w-4xl text-center" data-admin-block-root>
            <span class="text-4xl mb-5 block"><?= $s['icon'] ?></span>
            <h2 class="text-[1.46rem] md:text-[1.72rem] font-bold mb-3" style="color:<?= $e($s['color_dark']) ?>"<?= $seasonHealthTitleNode['attr'] ?>>
                <?= $e($seasonHealthTitleNode['value']) ?>
            </h2>
            <p class="text-[1.08rem] md:text-[1.16rem] text-gray-700 leading-relaxed max-w-3xl mx-auto"<?= bioinmed_page_text_attr($seasonPage, 'season', 'intro.text') ?>>
                <?= $e($s['intro']) ?>
            </p>
        </div>
    </div>
</section>

<!-- ═══════════════ SEASONAL TIPS ═══════════════ -->
<section class="py-14 md:py-20 bg-[#e4f1fa]">
    <div class="mx-auto max-w-6xl px-6 md:px-10">
        <h2 class="text-[1.46rem] md:text-[1.72rem] font-bold text-[#0a293c] mb-7 text-center"<?= $seasonTipsTitleNode['attr'] ?>>
            <?= $e($seasonTipsTitleNode['value']) ?>
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($s['tips'] as $tipIndex => $tip): ?>
            <?php
                $tipTitleNode = bioinmed_page_text_node($seasonPage, 'season', 'tips.items.' . $slug . '.' . $tipIndex . '.title', (string)($tip['title'] ?? ''));
                $tipTextNode = bioinmed_page_text_node($seasonPage, 'season', 'tips.items.' . $slug . '.' . $tipIndex . '.text', (string)($tip['text'] ?? ''));
            ?>
            <div class="flex gap-4 p-5 rounded-2xl border border-[#dce8f5] bg-white shadow-sm hover:shadow-md transition-shadow" data-admin-block-root>
                <div class="flex-shrink-0 w-11 h-11 rounded-full flex items-center justify-center text-white text-base"
                     style="background:<?= $e($s['color']) ?>">
                    <i class="fa-solid <?= $e($tip['icon']) ?>" aria-hidden="true"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-[1.1rem] md:text-[1.16rem] text-[#0a293c] mb-1.5"<?= $tipTitleNode['attr'] ?>><?= $e($tipTitleNode['value']) ?></h3>
                    <p class="text-gray-600 text-[1rem] md:text-[1.06rem] leading-relaxed"<?= $tipTextNode['attr'] ?>><?= $e($tipTextNode['value']) ?></p>
                </div>
            </div>
            <?php endforeach ?>
        </div>
    </div>
</section>

<!-- ═══════════════ SEASON SERVICES ═══════════════ -->
<section class="py-14 md:py-20" style="background:<?= $e($s['color_light']) ?>">
    <div class="mx-auto max-w-6xl px-6 md:px-10">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between mb-10" data-admin-block-root>
            <div class="max-w-2xl">
                <p class="text-[0.86rem] md:text-[0.92rem] font-semibold tracking-[0.16em] uppercase" style="color:<?= $e($s['color']) ?>"<?= bioinmed_page_text_attr($seasonPage, 'season', 'services.eyebrow') ?>><?= $e($seasonServicesText['eyebrow'] ?? 'Практика сезона') ?></p>
                <h2 class="text-[1.46rem] md:text-[1.72rem] font-bold mt-2.5" style="color:<?= $e($s['color_dark']) ?>"<?= $seasonServicesTitleNode['attr'] ?>><?= $e($seasonServicesTitleNode['value']) ?></h2>
            </div>
            <a href="/services" class="inline-flex items-center gap-2 text-[1rem] md:text-[1.04rem] font-semibold text-[#0a293c] hover:text-[#1977b2] transition-colors"<?= bioinmed_page_text_attr($seasonPage, 'season', 'services.all_services') ?>>
                <?= $e($seasonServicesText['all_services'] ?? 'Все услуги клиники') ?>
                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
        </div>

        <div class="season-practice-grid">
            <?php foreach ($season_services as $service): ?>
            <?php
                $serviceId = (string)($service['id'] ?? '');
                $serviceLabelNode = bioinmed_page_text_node($seasonPage, 'season', 'services.items.' . $slug . '.' . $serviceId . '.label', (string)($service['label'] ?? ''));
                $serviceSubtitleNode = bioinmed_page_text_node($seasonPage, 'season', 'services.items.' . $slug . '.' . $serviceId . '.subtitle', (string)($service['subtitle'] ?? ''));
                $serviceDescNode = bioinmed_page_text_node($seasonPage, 'season', 'services.items.' . $slug . '.' . $serviceId . '.desc', (string)($service['desc'] ?? ''));
            ?>
            <a href="/services/<?= $e($service['id']) ?>" class="season-practice-card" style="--season-accent:<?= $e($s['color']) ?>" data-admin-block-root>
                <span class="season-practice-card__kicker"<?= bioinmed_page_text_attr($seasonPage, 'season', 'services.recommendation_kicker') ?>><i class="fa-solid fa-stethoscope" aria-hidden="true"></i> <?= $e($seasonServicesText['recommendation_kicker'] ?? 'Рекомендация сезона') ?></span>
                <h3 class="season-practice-card__title"<?= $serviceLabelNode['attr'] ?>><?= $e($serviceLabelNode['value']) ?></h3>
                <?php if ($serviceSubtitleNode['value'] !== ''): ?>
                <p class="season-practice-card__subtitle"<?= $serviceSubtitleNode['attr'] ?>><?= $e($serviceSubtitleNode['value']) ?></p>
                <?php endif ?>
                <p class="season-practice-card__desc"<?= $serviceDescNode['attr'] ?>><?= $e($serviceDescNode['value']) ?></p>
                <span class="season-practice-card__cta"<?= bioinmed_page_text_attr($seasonPage, 'season', 'services.detail_cta') ?>><?= $e($seasonServicesText['detail_cta'] ?? 'Подробнее об услуге') ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
            </a>
            <?php endforeach ?>
        </div>
    </div>
</section>

<!-- ═══════════════ CTA ═══════════════ -->
<section class="py-14 md:py-18 text-white" style="background:<?= $e($s['color_dark']) ?>">
    <div class="mx-auto max-w-6xl px-6 md:px-10">
        <div class="mx-auto max-w-4xl text-center" data-admin-block-root>
            <h2 class="text-[1.46rem] md:text-[1.72rem] font-bold mb-3"<?= $seasonCtaTitleNode['attr'] ?>><?= $e($seasonCtaTitleNode['value']) ?></h2>
            <p class="text-white/80 text-[1.04rem] md:text-[1.12rem] mb-7 max-w-xl mx-auto leading-relaxed"<?= bioinmed_page_text_attr($seasonPage, 'season', 'cta.text') ?>>
                <?= $e($seasonCtaText['text'] ?? 'Наши специалисты разработают индивидуальную программу с учётом сезона и Ваших особенностей.') ?>
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                     <button type="button"
                         class="jsClientix_openWidget inline-flex items-center justify-center gap-2 rounded-full border-0 bg-white px-7 py-3.5 text-[1rem] font-semibold shadow-lg transition hover:-translate-y-0.5"
                   style="color:<?= $e($s['color_dark']) ?>;cursor:pointer;">
                    <i class="fa-regular fa-calendar-check" aria-hidden="true"></i>
                    <span<?= bioinmed_page_text_attr($seasonPage, 'season', 'cta.online_booking') ?>><?= $e($seasonCtaText['online_booking'] ?? 'Записаться онлайн') ?></span>
                </button>
                <a href="tel:<?= $e(preg_replace('/[^+\d]/', '', CLINIC_PHONE)) ?>"
                   class="inline-flex items-center justify-center gap-2 rounded-full border-2 border-white/40 px-7 py-3.5 text-[1rem] font-medium text-white transition hover:bg-white/10">
                    <i class="fa-solid fa-phone" aria-hidden="true"></i>
                    <?= $e(CLINIC_PHONE) ?>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════ SEASON NAVIGATION ═══════════════ -->
<nav class="py-10 bg-gray-50 border-t border-gray-200" aria-label="Другие сезоны">
    <div class="mx-auto max-w-6xl px-6 md:px-10">
        <h2 class="text-center text-[0.88rem] md:text-[0.94rem] font-semibold tracking-[0.16em] uppercase text-gray-400 mb-6"<?= $seasonNavTitleNode['attr'] ?>><?= $e($seasonNavTitleNode['value']) ?></h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php foreach ($seasons as $key => $sv): ?>
            <?php $is_current = ($key === $slug); ?>
            <?php $is_actual_now = ($key === $actual_season_slug); ?>
            <?php $seasonNavNameNode = bioinmed_page_text_node($seasonPage, 'season', 'navigation.items.' . $key . '.name', (string)($sv['name'] ?? '')); ?>
                <a href="/seasons/<?= $e($key) ?>"
                class="group relative overflow-hidden rounded-2xl border border-[#dbe8f4] bg-white aspect-[4/3] <?= $is_current ? 'ring-4 ring-offset-2' : '' ?> transition-all hover:scale-[1.02]"
               style="<?= $is_current ? 'ring-color:' . $e($sv['color']) : '' ?>"
                    <?= $is_current ? 'aria-current="page"' : '' ?> data-admin-block-root>
                <img src="<?= $e($sv['image']) ?>" alt="<?= $e($sv['name']) ?>" class="absolute inset-0 h-full w-full object-cover">
                <div class="absolute inset-0 transition-opacity" style="background:linear-gradient(to top, rgba(8,22,38,0.72) 0%, rgba(8,22,38,0.08) 58%)"></div>
                <div class="absolute bottom-0 left-0 right-0 p-4">
                    <div class="font-semibold text-[1rem] md:text-[1.06rem] text-white"<?= $seasonNavNameNode['attr'] ?>><?= $e($seasonNavNameNode['value']) ?></div>
                </div>
                <?php if ($is_actual_now): ?>
                 <div class="absolute top-3 right-3 inline-flex text-xs font-bold text-white rounded-full px-2 py-0.5"
                     style="background:<?= $e($sv['color']) ?>"<?= bioinmed_page_text_attr($seasonPage, 'season', 'navigation.current_badge') ?>><?= $e($seasonNavigationText['current_badge'] ?? 'сейчас') ?></div>
                <?php endif ?>
            </a>
            <?php endforeach ?>
        </div>
    </div>
</nav>

<?= $footer->render() ?>
<div id="season-art-popup-backdrop" class="season-art-popup-backdrop" onclick="closeSeasonArtPopup()"></div>
<div id="season-art-popup" class="season-art-popup" role="dialog" aria-modal="true" aria-labelledby="season-art-popup-title">
    <div class="season-art-popup__card">
        <button type="button" class="season-art-popup__close" onclick="closeSeasonArtPopup()" aria-label="<?= $e($seasonPopupText['close_label'] ?? 'Закрыть изображение') ?>">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
        <button type="button" class="season-art-popup__nav season-art-popup__nav--prev" onclick="showPrevSeasonArtPopup()" aria-label="<?= $e($seasonPopupText['prev_label'] ?? 'Предыдущая картина') ?>">
            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
        </button>
        <button type="button" class="season-art-popup__nav season-art-popup__nav--next" onclick="showNextSeasonArtPopup()" aria-label="<?= $e($seasonPopupText['next_label'] ?? 'Следующая картина') ?>">
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        </button>
        <div class="season-art-popup__image-wrap">
            <img id="season-art-popup-image" class="season-art-popup__image" src="" alt="">
            <div class="season-art-popup__caption">
                <p id="season-art-popup-title" class="season-art-popup__title"></p>
                <p id="season-art-popup-meta" class="season-art-popup__meta"></p>
                <p id="season-art-popup-museum" class="season-art-popup__museum"></p>
            </div>
        </div>
    </div>
</div>
<script>
    var seasonGalleryItemsCount = <?= $e((string)count($gallery_items)) ?>;
    var seasonPopupItems = <?= json_encode($season_art_popup_items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    var currentSeasonPopupIndex = 0;
    function renderSeasonArtPopup(index){
        if(!seasonPopupItems.length){return;}
        var image = document.getElementById('season-art-popup-image');
        if(!image){return;}
        if(index < 0){index = seasonPopupItems.length - 1;}
        if(index >= seasonPopupItems.length){index = 0;}
        currentSeasonPopupIndex = index;
        var item = seasonPopupItems[index] || {};
        image.src = item.image || '';
        image.alt = item.alt || '';
    }
    function openSeasonArtPopup(index){
        var popup = document.getElementById('season-art-popup');
        var backdrop = document.getElementById('season-art-popup-backdrop');
        if(!popup||!backdrop||!seasonPopupItems.length){return;}
        renderSeasonArtPopup(index);
        popup.classList.add('open');
        backdrop.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeSeasonArtPopup(){
        var popup = document.getElementById('season-art-popup');
        var backdrop = document.getElementById('season-art-popup-backdrop');
        var image = document.getElementById('season-art-popup-image');
        if(popup){popup.classList.remove('open');}
        if(backdrop){backdrop.classList.remove('open');}
        if(image){image.src='';image.alt='';}
        document.body.style.overflow = '';
    }
    function showPrevSeasonArtPopup(){
        renderSeasonArtPopup(currentSeasonPopupIndex - 1);
    }
    function showNextSeasonArtPopup(){
        renderSeasonArtPopup(currentSeasonPopupIndex + 1);
    }
    document.addEventListener('keydown',function(e){
        var popup = document.getElementById('season-art-popup');
        var isPopupOpen = popup && popup.classList.contains('open');
        if(e.key==='Escape' && isPopupOpen){closeSeasonArtPopup();}
        if(isPopupOpen){
            if(e.key==='ArrowLeft'){showPrevSeasonArtPopup();}
            if(e.key==='ArrowRight'){showNextSeasonArtPopup();}
            return;
        }
    });
    
    // Плавный переход видео: добавляем класс .loaded когда видео готово
    document.querySelectorAll('.season-hero__video').forEach(function(video) {
        video.addEventListener('canplay', function() {
            this.classList.add('loaded');
        });
        video.addEventListener('loadstart', function() {
            this.classList.remove('loaded');
        });
    });
</script>
</body>
</html>
