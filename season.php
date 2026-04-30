<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/components/Components.php';

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
$format_art_attribution = static function (array $art): string {
    $parts = [];

    foreach (['artist', 'artwork', 'year', 'museum'] as $key) {
        $value = trim((string)($art[$key] ?? ''));
        if ($value === '') {
            continue;
        }

        $parts[] = $key === 'artwork' ? '«' . $value . '»' : $value;
    }

    return implode(' · ', $parts);
};

$hero_attribution = $format_art_attribution($s);
$hero_mobile_attribution = trim((string)($s['hero_mobile_attribution'] ?? ''));
if ($hero_mobile_attribution === '') {
    $hero_mobile_attribution = $hero_attribution;
}

$season_art_popup_items = [];
foreach ($gallery_items as $item) {
    $popup_meta = array_values(array_filter([
        trim((string)($item['artist'] ?? '')),
        trim((string)($item['year'] ?? '')),
    ]));

    $season_art_popup_items[] = [
        'image' => (string)($item['image'] ?? ''),
        'alt' => (string)($item['alt'] ?? ''),
        'title' => (string)($item['artwork'] ?? ''),
        'meta' => implode(', ', $popup_meta),
        'museum' => (string)($item['museum'] ?? ''),
    ];
}

$season_titles = [
    'spring' => [
        'health' => 'Весеннее обновление здоровья',
        'gallery' => 'Художники о пробуждении весны',
        'tips' => 'Весенние ориентиры для самочувствия',
        'services' => 'Процедуры для весеннего восстановления',
        'cta' => 'Запишитесь на весеннюю консультацию',
        'nav' => 'Другие времена года',
    ],
    'summer' => [
        'health' => 'Летний ресурс и выносливость',
        'gallery' => 'Художники о силе лета',
        'tips' => 'Летние ориентиры для самочувствия',
        'services' => 'Процедуры для летнего тонуса',
        'cta' => 'Запишитесь на летнюю консультацию',
        'nav' => 'Другие времена года',
    ],
    'autumn' => [
        'health' => 'Осенний баланс и устойчивость',
        'gallery' => 'Художники о глубине осени',
        'tips' => 'Осенние ориентиры для самочувствия',
        'services' => 'Процедуры для осенней адаптации',
        'cta' => 'Запишитесь на осеннюю консультацию',
        'nav' => 'Другие времена года',
    ],
    'winter' => [
        'health' => 'Зимнее восстановление и тепло',
        'gallery' => 'Художники о тишине зимы',
        'tips' => 'Зимние ориентиры для самочувствия',
        'services' => 'Процедуры для зимней поддержки',
        'cta' => 'Запишитесь на зимнюю консультацию',
        'nav' => 'Другие времена года',
    ],
];
$titles = $season_titles[$slug] ?? [
    'health' => 'Здоровье ' . $s['name_gen'],
    'gallery' => 'Художники о ' . $s['name_gen'],
    'tips' => 'Советы для вашего здоровья',
    'services' => 'Услуги для ' . $s['name_gen'],
    'cta' => 'Запишитесь на консультацию',
    'nav' => 'Другие сезоны',
];

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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous">
    <style>
        .season-hero {
            position: relative;
            min-height: calc(100svh - var(--header-height, 0px));
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }
        .season-hero__bg {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
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
        }
        .season-nav-dot.active {
            background: white;
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
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(5,16,30,0.9) 0%, rgba(5,16,30,0.35) 45%, rgba(5,16,30,0.05) 100%);
        }
        .season-gallery-slide__caption {
            position: absolute;
            inset-inline: 0;
            bottom: 0;
            z-index: 1;
            padding: 1rem 1.25rem 1.1rem;
            color: #fff;
        }
        .season-gallery-slide__title {
            font-size: 0.9rem;
            font-weight: 700;
            line-height: 1.2;
        }
        .season-gallery-slide__meta {
            margin-top: 0.35rem;
            font-size: 0.68rem;
            color: rgba(255,255,255,0.8);
        }
        .season-gallery-slide__museum {
            margin-top: 0.5rem;
            font-size: 0.58rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.62);
        }
        .season-gallery-lead {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .season-gallery-lead__hint {
            font-size: 0.74rem;
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
            background: linear-gradient(180deg, rgba(255,255,255,0.98) 0%, rgba(248,251,255,0.96) 100%);
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
            background: var(--season-accent, #2fbdef);
        }
        .season-practice-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 36px rgba(8, 36, 70, 0.12);
            border-color: rgba(47, 189, 239, 0.34);
        }
        .season-practice-card__kicker {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 9999px;
            background: rgba(47, 189, 239, 0.12);
            padding: 0.26rem 0.62rem;
            color: #2a5a94;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .season-practice-card__title {
            margin-top: 0.7rem;
            font-size: 0.94rem;
            font-weight: 700;
            color: #173b64;
            line-height: 1.25;
        }
        .season-practice-card__subtitle {
            margin-top: 0.45rem;
            font-size: 0.68rem;
            letter-spacing: 0.13em;
            text-transform: uppercase;
            color: #7a91aa;
            font-weight: 600;
        }
        .season-practice-card__desc {
            margin-top: 0.7rem;
            font-size: 0.78rem;
            line-height: 1.55;
            color: #4f6f91;
        }
        .season-practice-card__cta {
            margin-top: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.42rem;
            color: #214f80;
            font-size: 0.76rem;
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
            font-size: 0.8rem;
            font-weight: 700;
            line-height: 1.25;
            text-shadow: 0 2px 8px rgba(0,0,0,0.44);
        }
        .season-art-popup__meta {
            margin-top: 0.35rem;
            color: rgba(255,255,255,0.82);
            font-size: 0.66rem;
            text-shadow: 0 1px 6px rgba(0,0,0,0.4);
        }
        .season-art-popup__museum {
            margin-top: 0.5rem;
            color: rgba(255,255,255,0.66);
            font-size: 0.56rem;
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
</head>
<body class="bg-white antialiased">
<?= $header->render() ?>

<!-- ═══════════════ HERO ═══════════════ -->
<section class="season-hero" aria-label="<?= $e($s['name']) ?>">
    <div class="season-hero__bg hidden md:block" style="background-image:url('<?= $e($hero_image_desktop) ?>');" role="img" aria-label="<?= $e($hero_image_desktop_alt) ?>"></div>
    <div class="season-hero__bg md:hidden" style="background-image:url('<?= $e($hero_image_mobile) ?>');" role="img" aria-label="<?= $e($hero_image_mobile_alt) ?>"></div>
    <div class="season-hero__overlay"></div>

    <!-- Season navigation dots -->
    <div class="season-hero__content pb-10 pt-[calc(var(--header-height,0px)+1.45rem)] md:pb-12 md:pt-[calc(var(--header-height,0px)+1.9rem)]">
        <div class="mx-auto max-w-6xl px-6 md:px-10">

            <!-- Season switcher top-right -->
            <div class="absolute top-24 right-6 md:right-10 flex flex-col gap-2 z-10">
                <?php foreach ($seasons as $key => $sv): ?>
                <a href="/seasons/<?= $e($key) ?>"
                   class="flex items-center gap-2 group <?= $key === $slug ? 'opacity-100' : 'opacity-50 hover:opacity-80' ?> transition-opacity"
                   title="<?= $e($sv['name']) ?>">
                    <span class="text-white text-xs font-medium"><?= $e($sv['name']) ?></span>
                </a>
                <?php endforeach ?>
            </div>

            <!-- Main hero text -->
            <p class="text-[0.68rem] font-semibold tracking-[0.2em] uppercase mb-2.5" style="color:<?= $e($s['color']) ?>">
                Времена года
            </p>
            <h1 class="text-4xl md:text-6xl font-black text-white leading-none mb-4">
                <?= $e($s['name']) ?>
            </h1>
            <p class="text-[0.9rem] md:text-[1.04rem] font-light mb-4 max-w-xl" style="color:rgba(255,255,255,0.92)">
                <?= $e($s['slogan']) ?>
            </p>
            <blockquote class="text-[0.78rem] md:text-[0.86rem] italic max-w-2xl border-l-4 pl-3.5 leading-relaxed" style="color:rgba(255,255,255,0.86);border-color:<?= $e($s['color']) ?>">
                <?= $e($s['quote']) ?>
            </blockquote>

            <!-- Artwork attribution -->
            <?php if ($hero_attribution !== ''): ?>
            <p class="mt-5 hidden text-white/40 text-[0.64rem] md:block">
                <?= $e($hero_attribution) ?>
            </p>
            <?php endif ?>
            <?php if ($hero_mobile_attribution !== ''): ?>
            <p class="mt-5 text-white/40 text-[0.64rem] md:hidden">
                <?= $e($hero_mobile_attribution) ?>
            </p>
            <?php endif ?>
        </div>
    </div>
</section>

<!-- ═══════════════ INTRO ═══════════════ -->
<section class="py-14 md:py-20" style="background:<?= $e($s['color_light']) ?>">
    <div class="mx-auto max-w-6xl px-6 md:px-10">
        <div class="mx-auto max-w-4xl text-center">
            <span class="text-4xl mb-5 block"><?= $s['icon'] ?></span>
            <h2 class="text-[1.16rem] md:text-[1.34rem] font-bold mb-3" style="color:<?= $e($s['color_dark']) ?>">
                <?= $e($titles['health']) ?>
            </h2>
            <p class="text-[0.84rem] text-gray-700 leading-relaxed max-w-3xl mx-auto">
                <?= $e($s['intro']) ?>
            </p>
        </div>
    </div>
</section>

<!-- ═══════════════ GALLERY ═══════════════ -->
<section class="py-14 md:py-20 bg-white">
    <div class="mx-auto max-w-6xl px-6 md:px-10">
        <div class="max-w-3xl">
            <p class="text-[0.68rem] font-semibold tracking-[0.2em] uppercase mb-2.5" style="color:<?= $e($s['color']) ?>">Искусство сезона</p>
            <h2 class="text-[1.16rem] md:text-[1.34rem] font-bold text-[#173b64] mb-2.5"><?= $e($titles['gallery']) ?></h2>
            <p class="text-[0.82rem] text-gray-600 leading-relaxed">
                <?= $e($s['art_medicine_quote'] ?? $s['intro']) ?>
            </p>
        </div>

        <?php if ($gallery_items !== []): ?>
        <div class="season-gallery-shell" aria-label="Галерея сезона">
            <div class="season-gallery-lead">
                <p class="season-gallery-lead__hint">Откройте работу, чтобы рассмотреть изображение крупнее.</p>
            </div>
            <div class="season-gallery-masonry">
                <?php foreach ($gallery_items as $index => $item): ?>
                <?php $gallery_meta = array_values(array_filter([
                    trim((string)($item['artist'] ?? '')),
                    trim((string)($item['year'] ?? '')),
                ])); ?>
                <figure class="season-gallery-slide" data-season-gallery-index="<?= $e((string)$index) ?>" onclick="openSeasonArtPopup(<?= $e((string)$index) ?>)">
                    <img src="<?= $e($item['image']) ?>" alt="<?= $e($item['alt']) ?>" loading="lazy">
                    <figcaption class="season-gallery-slide__caption">
                        <p class="season-gallery-slide__title">«<?= $e($item['artwork']) ?>»</p>
                        <?php if ($gallery_meta !== []): ?>
                        <p class="season-gallery-slide__meta"><?= $e(implode(', ', $gallery_meta)) ?></p>
                        <?php endif ?>
                        <?php if (!empty($item['museum'])): ?>
                        <p class="season-gallery-slide__museum"><?= $e($item['museum']) ?></p>
                        <?php endif ?>
                    </figcaption>
                </figure>
                <?php endforeach ?>
                </div>
        </div>
        <?php endif ?>
    </div>
</section>

<!-- ═══════════════ SEASONAL TIPS ═══════════════ -->
<section class="py-14 md:py-20 bg-white">
    <div class="mx-auto max-w-6xl px-6 md:px-10">
        <h2 class="text-[1.16rem] md:text-[1.34rem] font-bold text-[#173b64] mb-7 text-center">
            <?= $e($titles['tips']) ?>
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($s['tips'] as $tip): ?>
            <div class="flex gap-4 p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex-shrink-0 w-11 h-11 rounded-full flex items-center justify-center text-white text-base"
                     style="background:<?= $e($s['color']) ?>">
                    <i class="fa-solid <?= $e($tip['icon']) ?>" aria-hidden="true"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-[0.84rem] text-[#173b64] mb-1.5"><?= $e($tip['title']) ?></h3>
                    <p class="text-gray-600 text-[0.76rem] leading-relaxed"><?= $e($tip['text']) ?></p>
                </div>
            </div>
            <?php endforeach ?>
        </div>
    </div>
</section>

<!-- ═══════════════ SEASON SERVICES ═══════════════ -->
<section class="py-14 md:py-20" style="background:<?= $e($s['color_light']) ?>">
    <div class="mx-auto max-w-6xl px-6 md:px-10">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between mb-10">
            <div class="max-w-2xl">
                <p class="text-[0.68rem] font-semibold tracking-[0.2em] uppercase" style="color:<?= $e($s['color']) ?>">Практика сезона</p>
                <h2 class="text-[1.16rem] md:text-[1.34rem] font-bold mt-2.5" style="color:<?= $e($s['color_dark']) ?>"><?= $e($titles['services']) ?></h2>
            </div>
            <a href="/services" class="inline-flex items-center gap-2 text-[0.82rem] font-semibold text-[#173b64] hover:text-[#2fbdef] transition-colors">
                Все услуги клиники
                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
        </div>

        <div class="season-practice-grid">
            <?php foreach ($season_services as $service): ?>
            <a href="/services/<?= $e($service['id']) ?>" class="season-practice-card" style="--season-accent:<?= $e($s['color']) ?>">
                <span class="season-practice-card__kicker"><i class="fa-solid fa-stethoscope" aria-hidden="true"></i> Рекомендация сезона</span>
                <h3 class="season-practice-card__title"><?= $e($service['label']) ?></h3>
                <?php if ($service['subtitle'] !== ''): ?>
                <p class="season-practice-card__subtitle"><?= $e($service['subtitle']) ?></p>
                <?php endif ?>
                <p class="season-practice-card__desc"><?= $e($service['desc']) ?></p>
                <span class="season-practice-card__cta">Подробнее об услуге <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
            </a>
            <?php endforeach ?>
        </div>
    </div>
</section>

<!-- ═══════════════ CTA ═══════════════ -->
<section class="py-14 md:py-18 text-white" style="background:<?= $e($s['color_dark']) ?>">
    <div class="mx-auto max-w-6xl px-6 md:px-10">
        <div class="mx-auto max-w-4xl text-center">
            <h2 class="text-[1.16rem] md:text-[1.34rem] font-bold mb-3"><?= $e($titles['cta']) ?></h2>
            <p class="text-white/80 text-[0.8rem] mb-7 max-w-xl mx-auto leading-relaxed">
                Наши специалисты разработают индивидуальную программу с учётом сезона и ваших особенностей.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="javascript:void(0)" onclick="openBookingPopup(true);return false;"
                   class="inline-flex items-center justify-center gap-2 rounded-full bg-white px-7 py-3.5 text-[0.9rem] font-semibold shadow-lg transition hover:-translate-y-0.5"
                   style="color:<?= $e($s['color_dark']) ?>">
                    <i class="fa-regular fa-calendar-check" aria-hidden="true"></i>
                    Записаться онлайн
                </a>
                <a href="tel:<?= $e(preg_replace('/[^+\d]/', '', CLINIC_PHONE)) ?>"
                   class="inline-flex items-center justify-center gap-2 rounded-full border-2 border-white/40 px-7 py-3.5 text-[0.9rem] font-medium text-white transition hover:bg-white/10">
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
        <h2 class="text-center text-[0.68rem] font-semibold tracking-[0.2em] uppercase text-gray-400 mb-6"><?= $e($titles['nav']) ?></h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php foreach ($seasons as $key => $sv): ?>
            <?php $is_current = ($key === $slug); ?>
            <?php $is_actual_now = ($key === $actual_season_slug); ?>
            <a href="/seasons/<?= $e($key) ?>"
                class="group relative overflow-hidden rounded-2xl border border-[#dbe8f4] bg-white aspect-[4/3] <?= $is_current ? 'ring-4 ring-offset-2' : '' ?> transition-all hover:scale-[1.02]"
               style="<?= $is_current ? 'ring-color:' . $e($sv['color']) : '' ?>"
               <?= $is_current ? 'aria-current="page"' : '' ?>>
                <img src="<?= $e($sv['image']) ?>" alt="<?= $e($sv['name']) ?>" class="absolute inset-0 h-full w-full object-cover">
                <div class="absolute inset-0 transition-opacity" style="background:linear-gradient(to top, rgba(8,22,38,0.72) 0%, rgba(8,22,38,0.08) 58%)"></div>
                <div class="absolute bottom-0 left-0 right-0 p-4">
                    <div class="font-semibold text-sm text-white"><?= $e($sv['name']) ?></div>
                </div>
                <?php if ($is_actual_now): ?>
                 <div class="absolute top-3 right-3 inline-flex text-xs font-bold text-white rounded-full px-2 py-0.5"
                     style="background:<?= $e($sv['color']) ?>">сейчас</div>
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
        <button type="button" class="season-art-popup__close" onclick="closeSeasonArtPopup()" aria-label="Закрыть изображение">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
        <button type="button" class="season-art-popup__nav season-art-popup__nav--prev" onclick="showPrevSeasonArtPopup()" aria-label="Предыдущая картина">
            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
        </button>
        <button type="button" class="season-art-popup__nav season-art-popup__nav--next" onclick="showNextSeasonArtPopup()" aria-label="Следующая картина">
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
        var title = document.getElementById('season-art-popup-title');
        var meta = document.getElementById('season-art-popup-meta');
        var museum = document.getElementById('season-art-popup-museum');
        if(!image||!title||!meta||!museum){return;}
        if(index < 0){index = seasonPopupItems.length - 1;}
        if(index >= seasonPopupItems.length){index = 0;}
        currentSeasonPopupIndex = index;
        var item = seasonPopupItems[index] || {};
        image.src = item.image || '';
        image.alt = item.alt || item.title || '';
        title.textContent = item.title ? '«' + item.title + '»' : '';
        meta.textContent = item.meta || '';
        museum.textContent = item.museum || '';
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
</script>
</body>
</html>
