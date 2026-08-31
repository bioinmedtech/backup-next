<?php
require_once __DIR__ . '/includes/pin_protection.php';
bioinmed_pin_require_access();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/components/Components.php';
require_once __DIR__ . '/includes/blog/VkBlog.php';
require_once __DIR__ . '/includes/blog/WeatherWidget.php';

$blogPage = bioinmed_read_json_file('pages/blog.json');
$blogMeta = is_array($blogPage['meta'] ?? null) ? $blogPage['meta'] : [];
$blogHeroHeadingNode = bioinmed_page_text_node($blogPage, 'blog', 'hero.heading', 'Блог БИОИНМЕД');
$blogHeroIntroNode = bioinmed_page_text_node($blogPage, 'blog', 'hero.intro', 'Новости клиники, врачебные заметки и понятные материалы о восстановительной медицине: диагностика, движение, сон, стресс и привычки, которые помогают телу возвращаться в ресурс.');
$blogSearchLabelNode = bioinmed_page_text_node($blogPage, 'blog', 'search.label', 'Поиск по публикациям');
$blogSearchPlaceholderNode = bioinmed_page_text_node($blogPage, 'blog', 'search.placeholder', 'Найти статью по теме');
$blogSearchEmptyNode = bioinmed_page_text_node($blogPage, 'blog', 'search.empty', 'По этому запросу публикаций не найдено.');

$cache = bioinmed_blog_read_cache();
$posts = is_array($cache['posts'] ?? null) ? $cache['posts'] : [];
$siteUrl = rtrim(CLINIC_SITE_URL, '/');
$canonicalUrl = $siteUrl . '/blog/';
$pageTitle = trim((string)($blogMeta['title'] ?? 'Блог')) . ' | ' . CLINIC_NAME;
$pageDescription = bioinmed_meta_description(
    trim((string)($blogMeta['description'] ?? 'Блог клиники БИОИНМЕД: новости, заметки врачей и полезные материалы о восстановительной медицине.')),
    'Блог клиники БИОИНМЕД в Москве.'
);
$socialImageUrl = ($posts && bioinmed_blog_primary_image($posts[0]) !== '')
    ? bioinmed_blog_primary_image($posts[0])
    : bioinmed_og_image_url('home');
$updatedAt = (string)($cache['updated_at'] ?? '');
$vkUrl = (string)($cache['source']['url'] ?? 'https://vk.ru/bioinmed');

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$popularServices = bioinmed_popular_services($services, 5, 90);
$sidebarPosts = array_slice($posts, 0, 5);

$blogStructuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'Blog',
    'name' => 'Блог ' . CLINIC_NAME,
    'description' => $pageDescription,
    'url' => $canonicalUrl,
    'inLanguage' => 'ru-RU',
    'publisher' => bioinmed_medical_organization_schema(),
    'mainEntity' => [
        '@type' => 'ItemList',
        'itemListElement' => array_map(static function (array $post, int $index) {
            return [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'url' => bioinmed_blog_post_url($post, true),
                'name' => bioinmed_blog_title($post),
            ];
        }, $posts, array_keys($posts)),
    ],
];
$breadcrumbStructuredData = bioinmed_breadcrumb_schema([
    ['name' => 'Главная', 'url' => '/'],
    ['name' => 'Блог', 'url' => '/blog'],
]);

if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
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
    <link rel="alternate" type="application/rss+xml" title="Блог БИОИНМЕД" href="<?php echo e($siteUrl); ?>/blog-rss.php">
    <meta name="theme-color" content="#1977b2">
    <?php echo bioinmed_render_social_meta($pageTitle, $pageDescription, $canonicalUrl, ['image' => $socialImageUrl]); ?>
    <?php echo bioinmed_render_favicon_links(CLINIC_ICON_PATH); ?>
    <?php echo bioinmed_render_public_head_assets(); ?>
    <script type="application/ld+json"><?php echo json_encode($blogStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script type="application/ld+json"><?php echo json_encode($breadcrumbStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <style>
        html { font-size: clamp(17px, 0.5vw + 15px, 19px); }
        body { line-height: 1.72; }
        .blog-surface { background: #eef6fb; }
        .blog-layout { display: grid; gap: 1.5rem; align-items: start; }
        .blog-main { min-width: 0; display: grid; gap: 1rem; }
        .blog-weather-column { display: none; min-width: 0; }
        .blog-sticky-column { scrollbar-gutter: stable; }
        .blog-weather-mobile { display: none; margin-bottom: .85rem; }
        .blog-weather-mobile__toggle { display: flex; width: 100%; align-items: center; justify-content: space-between; gap: .75rem; border: 1px solid #cddce8; border-radius: 12px; background: #fff; padding: .72rem .85rem; color: #0f2749; font-size: .88rem; font-weight: 800; }
        .blog-weather-mobile__toggle svg { width: 16px; height: 16px; transition: transform .18s ease; }
        .blog-weather-mobile.is-open .blog-weather-mobile__toggle svg { transform: rotate(180deg); }
        .blog-weather-mobile__panel { display: none; margin-top: .6rem; }
        .blog-weather-mobile.is-open .blog-weather-mobile__panel { display: block; }
        .blog-sidebar { display: grid; gap: 1rem; }
        .blog-card { border: 1px solid #d8e6f2; border-radius: 12px; background: #fff; }
        .blog-hero { overflow: hidden; background: linear-gradient(135deg,#ffffff 0%,#f8fcff 58%,#eef8f4 100%); }
        .blog-hero-inner { display: grid; gap: 1rem; padding: 1.35rem; }
        .blog-hero h1 { margin-top: .25rem; color: #0f2749; font-size: 2rem; line-height: 1.08; font-weight: 800; }
        .blog-hero p { max-width: 45rem; color: #244866; font-size: 1rem; line-height: 1.58; }
        .blog-actions { display: flex; flex-wrap: wrap; gap: .75rem; border-top: 1px solid #e1edf6; padding: 1rem 1.35rem; background: rgba(255,255,255,.72); }
        .blog-search { position: relative; flex: 1 1 260px; }
        .blog-search svg { position: absolute; left: .9rem; top: 50%; width: .92rem; height: .92rem; transform: translateY(-50%); color: #7890a5; pointer-events: none; }
        .blog-search input { width: 100%; border: 1px solid #cddce8; border-radius: 10px; background: #fff; padding: .72rem .95rem .72rem 2.45rem; color: #0a293c; font-size: .9rem; font-weight: 600; outline: none; }
        .blog-search input:focus { border-color: #1977b2; box-shadow: 0 0 0 3px rgba(25,119,178,.12); }
        .blog-search-status { color: #607d96; font-size: .82rem; font-weight: 650; align-self: center; }
        .blog-post.is-hidden { display: none; }
        .blog-post.is-lazy-hidden { display: none; }
        .blog-posts-loader { display: none; padding: .35rem 0 .75rem; text-align: center; }
        .blog-posts-loader.is-visible { display: block; }
        .blog-posts-loader__button { display: inline-flex; align-items: center; justify-content: center; gap: .45rem; border: 1px solid #cddce8; border-radius: 10px; background: #fff; padding: .62rem 1rem; color: #1977b2; font-size: .84rem; font-weight: 800; }
        .blog-posts-loader__button:hover, .blog-posts-loader__button:focus-visible { border-color: #1977b2; background: #f7fbff; }
        .blog-empty-search { display: none; }
        .blog-empty-search.is-visible { display: block; }
        .blog-post { overflow: hidden; transition: border-color .18s ease, transform .18s ease; }
        .blog-post:hover { border-color: #abcfe8; transform: translateY(-1px); }
        .blog-post-full-text { display: none; }
        .blog-post.is-expanded .blog-post-excerpt-text { display: none; }
        .blog-post.is-expanded .blog-post-full-text { display: inline; }
        .blog-post-more { display: inline; border: 0; background: transparent; padding: 0; color: #8b95a1; font: inherit; font-weight: 700; cursor: pointer; }
        .blog-post-more:hover, .blog-post-more:focus-visible { color: #1977b2; }
        .blog-post-image { display: block; overflow: hidden; aspect-ratio: 1 / 1; border-top: 1px solid #edf3f8; background: #f7fbfe; }
        .blog-post-image img { display: block; width: 100%; height: 100%; object-fit: cover; }
        .blog-post-footer { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; border-top: 1px solid #edf2f7; padding: .72rem 1.25rem; color: #6b8298; font-size: .84rem; }
        .blog-sidebar-card { border: 1px solid #d8e6f2; border-radius: 12px; background: #fff; padding: 1.1rem; }
        .blog-sidebar-cta { border-color: #aed3e8; background: linear-gradient(145deg,#ffffff 0%,#eef8ff 58%,#f4fbf8 100%); }
        .blog-weather-card { overflow: hidden; padding: 0; border-color: rgba(189,213,230,.9); border-radius: 18px; background: rgba(255,255,255,.82); }
        .blog-weather-card__hero { position: relative; min-height: 210px; padding: 1.08rem; color: #fff; background-color: #17446f; background-image: linear-gradient(150deg,#0e4569 0%,#1977b2 46%,#5db6c9 100%), linear-gradient(180deg,rgba(5,19,34,.04),rgba(5,19,34,.46)); background-position: center; background-size: cover; }
        .blog-weather-card__hero > * { position: relative; z-index: 1; }
        .blog-weather-card__top { display: flex; align-items: flex-start; justify-content: space-between; gap: .8rem; }
        .blog-weather-card__brand { color: #fff; font-size: 1.22rem; font-weight: 800; line-height: 1.05; }
        .blog-weather-card__place { margin-top: .25rem; color: rgba(255,255,255,.76); font-size: .76rem; font-weight: 700; line-height: 1.25; }
        .blog-weather-card__icon { display: inline-flex; width: 48px; height: 48px; flex: 0 0 48px; align-items: center; justify-content: center; border-radius: 999px; background: rgba(255,255,255,.22); color: #fff; backdrop-filter: blur(16px) saturate(1.35); box-shadow: 0 12px 24px rgba(7,25,48,.15); }
        .blog-weather-card__icon svg { display: block; width: 36px; height: 36px; filter: drop-shadow(0 8px 14px rgba(0,0,0,.16)); }
        .blog-weather-card__temp { margin-top: 1.65rem; color: #fff; font-size: 3.15rem; font-weight: 850; letter-spacing: 0; line-height: .9; text-shadow: 0 9px 26px rgba(0,0,0,.26); }
        .blog-weather-card__meta { margin-top: .45rem; color: rgba(255,255,255,.92); font-size: .92rem; font-weight: 800; line-height: 1.2; }
        .blog-weather-card__stats { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: .48rem; margin-top: 1rem; }
        .blog-weather-card__metric { min-height: 66px; border: 1px solid rgba(255,255,255,.22); border-radius: 14px; background: rgba(255,255,255,.18); padding: .55rem; backdrop-filter: blur(18px) saturate(1.4); }
        .blog-weather-card__metric span { display: block; margin-top: .28rem; color: rgba(255,255,255,.7); font-size: .64rem; font-weight: 750; line-height: 1.08; }
        .blog-weather-card__metric strong { display: block; color: #fff; font-size: .92rem; font-weight: 850; line-height: 1.1; white-space: nowrap; }
        .blog-weather-card__metric i { display: inline-flex; width: 18px; height: 18px; align-items: center; justify-content: center; color: rgba(255,255,255,.86); line-height: 0; }
        .blog-weather-card__metric i svg { display: block; width: 18px; height: 18px; stroke: currentColor; }
        .blog-weather-card__forecast { max-height: none; overflow: visible; padding: .45rem 1rem .9rem; background: rgba(255,255,255,.82); backdrop-filter: blur(24px) saturate(1.25); }
        .blog-weather-card__day { display: grid; grid-template-columns: minmax(0,1fr) 32px minmax(62px,auto); gap: .55rem; align-items: center; min-height: 52px; border-bottom: 1px solid rgba(15,39,73,.08); }
        .blog-weather-card__day:last-child { border-bottom: 0; }
        .blog-weather-card__date { color: #8b95a1; font-size: .68rem; font-weight: 760; line-height: 1.1; }
        .blog-weather-card__label { margin-top: .12rem; color: #1d2633; font-size: .9rem; font-weight: 850; line-height: 1.08; }
        .blog-weather-card__day-icon { color: #9ec6ff; line-height: 0; }
        .blog-weather-card__day-icon svg { width: 28px; height: 28px; filter: drop-shadow(0 8px 16px rgba(15,39,73,.12)); }
        .blog-weather-card__range { display: flex; justify-content: flex-end; gap: .36rem; align-items: baseline; white-space: nowrap; }
        .blog-weather-card__range strong { color: #1d2633; font-size: .96rem; font-weight: 850; line-height: 1; }
        .blog-weather-card__range span { color: #8b928f; font-size: .82rem; font-weight: 800; line-height: 1; }
        .blog-weather-card__updated { padding: 0 1rem .95rem; color: #8b928f; font-size: .68rem; font-weight: 760; line-height: 1.2; background: rgba(255,255,255,.82); }
        .blog-service-link, .blog-fresh-link { display: block; border: 1px solid #e6eef6; border-radius: 10px; background: #fbfdff; padding: .78rem .82rem; color: #0f2749; font-size: .88rem; font-weight: 700; line-height: 1.28; }
        .blog-service-link:hover, .blog-fresh-link:hover { border-color: #9fc9e8; color: #1977b2; background: #f7fbff; }
        @media (min-width: 1024px) {
            .blog-layout { grid-template-columns: minmax(0, 1fr) 360px; grid-template-areas: "main sidebar" "main weather"; }
            .blog-main { grid-area: main; }
            .blog-weather-column { grid-area: weather; display: grid; gap: 1rem; }
            .blog-sidebar { grid-area: sidebar; }
            .blog-sticky-column { position: sticky; top: calc(var(--sticky-header-offset, 54px) + .75rem); max-height: calc(100vh - var(--sticky-header-offset, 54px) - 1.5rem); overflow-y: auto; padding-right: .25rem; }
        }
        @media (min-width: 1280px) {
            .blog-layout { grid-template-columns: minmax(285px, 320px) minmax(0, 1fr) minmax(330px, 360px); grid-template-areas: "weather main sidebar"; }
        }
        @media (min-width: 768px) and (max-width: 1023px) {
            .blog-weather-mobile { display: block; }
        }
        @media (min-width: 768px) {
            .blog-hero-inner { padding: 1.75rem 2rem; }
            .blog-hero h1 { font-size: 2.45rem; }
            .blog-actions { padding-inline: 2rem; }
        }
        @media (max-width: 767px) {
            html { font-size: 16px; }
            body { line-height: 1.58; }
            main { padding-left: .65rem !important; padding-right: .65rem !important; }
            .blog-weather-mobile { display: block; }
            .blog-weather-mobile .blog-weather-card__hero { min-height: 198px; }
            .blog-weather-mobile .blog-weather-card__forecast { max-height: none; overflow: visible; }
            .blog-layout, .blog-main { gap: .75rem; }
            .blog-card { border-radius: 10px; }
            .blog-hero-inner { padding: 1rem; }
            .blog-hero h1 { font-size: 1.62rem; line-height: 1.12; }
            .blog-hero p { margin-top: .45rem; font-size: .9rem; line-height: 1.48; }
            .blog-actions { gap: .55rem; padding: .75rem; }
            .blog-search { flex-basis: 100%; }
            .blog-search input { padding-block: .62rem; font-size: .86rem; }
            .blog-post > .p-5 { padding: .85rem !important; }
            .blog-post header { font-size: .72rem !important; }
            .blog-post h2 { font-size: 1.02rem !important; line-height: 1.26 !important; }
            .blog-post p { margin-top: .5rem !important; font-size: .88rem !important; line-height: 1.48 !important; }
            .blog-post-footer { gap: .58rem; padding: .62rem .85rem; font-size: .76rem; line-height: 1.25; }
            .blog-post-footer a { margin-left: 0; width: 100%; justify-content: center; padding-block: .48rem; }
        }
    </style>
    <?php echo bioinmed_uis_counter_head(); ?>
</head>
<body class="blog-surface text-[#0f2749] antialiased">
    <?php echo bioinmed_yandex_metrika_noscript(); ?>
    <?php
    $header = new Header($brand_colors);
    echo $header->render();
    ?>

    <main class="mx-auto max-w-6xl px-6 py-6 md:px-10 md:py-8">
        <div class="blog-weather-mobile" data-blog-weather-mobile>
            <button type="button" class="blog-weather-mobile__toggle" aria-expanded="false" data-weather-mobile-toggle>
                <span>Погода рядом</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <div class="blog-weather-mobile__panel">
                <section class="blog-sidebar-card blog-weather-card" data-blog-weather>
                    <div class="blog-weather-card__hero">
                        <div class="blog-weather-card__top">
                            <div>
                                <div class="blog-weather-card__brand" data-weather-city>Ваш город</div>
                                <div class="blog-weather-card__place" data-weather-place>Прогноз для вашего города</div>
                            </div>
                            <div class="blog-weather-card__icon" aria-hidden="true" data-weather-icon><svg viewBox="0 0 72 72"><circle cx="36" cy="36" r="13" fill="#f7b743"/><g stroke="#f7b743" stroke-width="5" stroke-linecap="round"><path d="M36 9v8M36 55v8M9 36h8M55 36h8M17 17l6 6M49 49l6 6M55 17l-6 6M23 49l-6 6"/></g></svg></div>
                        </div>
                        <div class="blog-weather-card__temp" data-weather-temp>--</div>
                        <div class="blog-weather-card__meta" data-weather-summary>Определяем город...</div>
                        <div class="blog-weather-card__stats">
                            <div class="blog-weather-card__metric"><i aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4 8h10a3 3 0 1 0-3-3" stroke-width="2" stroke-linecap="round"/><path d="M3 13h15a3 3 0 1 1-3 3" stroke-width="2" stroke-linecap="round"/><path d="M5 18h6" stroke-width="2" stroke-linecap="round"/></svg></i><strong data-weather-wind>--</strong><span>ветер</span></div>
                            <div class="blog-weather-card__metric"><i aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M14 4a6 6 0 0 1 4.2 10.3L12 21l-6.2-6.7A6 6 0 0 1 14 4Z" stroke-width="2" stroke-linejoin="round"/><path d="M12 8v4l3 2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></i><strong data-weather-feels>--</strong><span>ощущается</span></div>
                            <div class="blog-weather-card__metric"><i aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M12 3.5S6 10.1 6 14a6 6 0 0 0 12 0c0-3.9-6-10.5-6-10.5Z" stroke-width="2" stroke-linejoin="round"/><path d="M9.5 14.5a2.8 2.8 0 0 0 2.8 2.8" stroke-width="2" stroke-linecap="round"/></svg></i><strong data-weather-humidity>--</strong><span>влажность</span></div>
                            <div class="blog-weather-card__metric"><i aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M5 12a7 7 0 0 1 14 0H5Z" stroke-width="2" stroke-linejoin="round"/><path d="M12 12v7a2 2 0 0 0 4 0" stroke-width="2" stroke-linecap="round"/><path d="M12 5V3" stroke-width="2" stroke-linecap="round"/></svg></i><strong data-weather-precip>--</strong><span>осадки</span></div>
                        </div>
                    </div>
                    <div class="blog-weather-card__forecast" data-weather-forecast></div>
                    <div class="blog-weather-card__updated" data-weather-updated>Обновляем прогноз...</div>
                </section>
            </div>
        </div>
        <div class="blog-layout">
            <aside class="blog-weather-column blog-sticky-column" aria-label="Погода">
                <section class="blog-sidebar-card blog-weather-card" data-blog-weather>
                    <div class="blog-weather-card__hero">
                        <div class="blog-weather-card__top">
                            <div>
                                <div class="blog-weather-card__brand" data-weather-city>Ваш город</div>
                                <div class="blog-weather-card__place" data-weather-place>Прогноз для вашего города</div>
                            </div>
                            <div class="blog-weather-card__icon" aria-hidden="true" data-weather-icon><svg viewBox="0 0 72 72"><circle cx="36" cy="36" r="13" fill="#f7b743"/><g stroke="#f7b743" stroke-width="5" stroke-linecap="round"><path d="M36 9v8M36 55v8M9 36h8M55 36h8M17 17l6 6M49 49l6 6M55 17l-6 6M23 49l-6 6"/></g></svg></div>
                        </div>
                        <div class="blog-weather-card__temp" data-weather-temp>--</div>
                        <div class="blog-weather-card__meta" data-weather-summary>Определяем город...</div>
                        <div class="blog-weather-card__stats">
                            <div class="blog-weather-card__metric">
                                <i aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4 8h10a3 3 0 1 0-3-3" stroke-width="2" stroke-linecap="round"/><path d="M3 13h15a3 3 0 1 1-3 3" stroke-width="2" stroke-linecap="round"/><path d="M5 18h6" stroke-width="2" stroke-linecap="round"/></svg></i>
                                <strong data-weather-wind>--</strong>
                                <span>ветер</span>
                            </div>
                            <div class="blog-weather-card__metric">
                                <i aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M14 4a6 6 0 0 1 4.2 10.3L12 21l-6.2-6.7A6 6 0 0 1 14 4Z" stroke-width="2" stroke-linejoin="round"/><path d="M12 8v4l3 2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></i>
                                <strong data-weather-feels>--</strong>
                                <span>ощущается</span>
                            </div>
                            <div class="blog-weather-card__metric">
                                <i aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M12 3.5S6 10.1 6 14a6 6 0 0 0 12 0c0-3.9-6-10.5-6-10.5Z" stroke-width="2" stroke-linejoin="round"/><path d="M9.5 14.5a2.8 2.8 0 0 0 2.8 2.8" stroke-width="2" stroke-linecap="round"/></svg></i>
                                <strong data-weather-humidity>--</strong>
                                <span>влажность</span>
                            </div>
                            <div class="blog-weather-card__metric">
                                <i aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M5 12a7 7 0 0 1 14 0H5Z" stroke-width="2" stroke-linejoin="round"/><path d="M12 12v7a2 2 0 0 0 4 0" stroke-width="2" stroke-linecap="round"/><path d="M12 5V3" stroke-width="2" stroke-linecap="round"/></svg></i>
                                <strong data-weather-precip>--</strong>
                                <span>осадки</span>
                            </div>
                        </div>
                    </div>
                    <div class="blog-weather-card__forecast" data-weather-forecast></div>
                    <div class="blog-weather-card__updated" data-weather-updated>Обновляем прогноз...</div>
                </section>
            </aside>
            <div class="blog-main">
                <section class="blog-card blog-hero">
                    <div class="blog-hero-inner">
                        <div data-admin-block-root>
                            <h1<?php echo $blogHeroHeadingNode['attr']; ?>><?php echo e($blogHeroHeadingNode['value']); ?></h1>
                            <p class="mt-3"<?php echo $blogHeroIntroNode['attr']; ?>><?php echo e($blogHeroIntroNode['value']); ?></p>
                        </div>
                    </div>
                    <div class="blog-actions">
                        <label class="blog-search" data-admin-block-root>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m21 21-4.35-4.35m1.35-5.15a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
                            <span class="sr-only"<?php echo $blogSearchLabelNode['attr']; ?>><?php echo e($blogSearchLabelNode['value']); ?></span>
                            <input type="search" id="blog-search" placeholder="<?php echo e($blogSearchPlaceholderNode['value']); ?>" autocomplete="off"<?php echo $blogSearchPlaceholderNode['attr']; ?>>
                        </label>
                        <span class="blog-search-status" id="blog-search-status" aria-live="polite"></span>
                        <a href="/blog-rss.php" class="inline-flex items-center gap-2 rounded-lg border border-[#cddce8] bg-white px-3.5 py-2 text-[0.84rem] font-semibold text-[#0a293c] hover:border-[#1977b2] hover:text-[#1977b2]">
                            <i class="fa-solid fa-rss" aria-hidden="true"></i>
                            RSS
                        </a>
                    </div>
                </section>

                <?php if (!$posts): ?>
                    <section class="blog-card p-6">
                        <h2 class="text-[1.2rem] font-bold text-[#0f2749]">Публикации скоро появятся</h2>
                        <p class="mt-2 text-[0.95rem] leading-relaxed text-[#45637f]">Кэш блога пока пуст. После запуска VK-скрипта здесь появятся последние записи сообщества.</p>
                    </section>
                <?php else: ?>
                    <section class="space-y-4" aria-label="Публикации" id="blog-posts">
                        <?php foreach ($posts as $post): ?>
                            <?php
                            $postId = (int)($post['id'] ?? 0);
                            $title = bioinmed_blog_title($post);
                            $bodyText = bioinmed_blog_body_text($post);
                            $excerpt = bioinmed_blog_excerpt($bodyText, 235);
                            $hasMore = mb_strlen(trim(preg_replace('/\s+/u', ' ', $bodyText) ?? ''), 'UTF-8') > mb_strlen($excerpt, 'UTF-8') + 8;
                            $attachments = is_array($post['attachments'] ?? null) ? $post['attachments'] : [];
                            $primaryImage = bioinmed_blog_primary_image($post);
                            $postUrl = bioinmed_blog_post_url($post);
                            ?>
                            <article id="post-<?php echo $postId; ?>" class="blog-card blog-post" data-blog-post data-search-text="<?php echo e(mb_strtolower($title . ' ' . $bodyText, 'UTF-8')); ?>">
                                <div class="p-5 md:p-6">
                                    <header class="flex flex-wrap items-center gap-2 text-[0.78rem] font-semibold text-[#6b8298]">
                                        <time datetime="<?php echo e(date(DATE_ATOM, (int)($post['date'] ?? time()))); ?>"><?php echo e(bioinmed_blog_format_date($post['date'] ?? 0)); ?></time>
                                        <span class="h-1 w-1 rounded-full bg-[#9db4c8]" aria-hidden="true"></span>
                                        <span>БИОИНМЕД</span>
                                    </header>

                                    <a href="<?php echo e($postUrl); ?>" class="mt-4 block">
                                        <h2 class="text-[1.18rem] font-bold leading-snug text-[#0f2749] hover:text-[#1977b2] md:text-[1.36rem]"><?php echo e($title); ?></h2>
                                    </a>

                                    <?php if ($excerpt !== ''): ?>
                                        <p class="mt-3 text-[0.96rem] leading-relaxed text-[#0a293c]">
                                            <?php if ($hasMore): ?>
                                                <span class="blog-post-excerpt-text"><?php echo nl2br(e($excerpt)); ?></span>
                                                <span class="blog-post-full-text"><?php echo nl2br(e($bodyText)); ?></span>
                                                <button type="button" class="blog-post-more" data-blog-more aria-expanded="false">Показать ещё</button>
                                            <?php else: ?>
                                                <?php echo nl2br(e($excerpt)); ?>
                                            <?php endif; ?>
                                        </p>
                                    <?php endif; ?>

                                </div>
                                <?php if ($primaryImage !== ''): ?>
                                    <a href="<?php echo e($postUrl); ?>" class="blog-post-image">
                                        <img src="<?php echo e($primaryImage); ?>" alt="<?php echo e($title); ?>" loading="lazy" decoding="async">
                                    </a>
                                <?php endif; ?>

                                <footer class="blog-post-footer">
                                    <span><i class="fa-solid fa-heart-pulse text-[#1977b2]" aria-hidden="true"></i> <?php echo (int)($post['likes'] ?? 0); ?></span>
                                    <span><i class="fa-solid fa-comments text-[#1977b2]" aria-hidden="true"></i> <?php echo (int)($post['comments'] ?? 0); ?></span>
                                    <span><?php echo count($attachments); ?> влож.</span>
                                    <a href="<?php echo e($postUrl); ?>" class="ml-auto inline-flex items-center gap-2 rounded-lg border border-[#d2e0ec] px-3 py-1.5 font-semibold text-[#1977b2] hover:border-[#1977b2]">
                                        Читать полностью
                                        <i class="fa-solid fa-arrow-right text-[0.72rem]" aria-hidden="true"></i>
                                    </a>
                                </footer>
                            </article>
                        <?php endforeach; ?>
                    </section>
                    <?php if (count($posts) > 30): ?>
                        <div class="blog-posts-loader" id="blog-posts-loader" aria-live="polite">
                            <button type="button" class="blog-posts-loader__button" id="blog-posts-load-more">
                                <span data-blog-posts-loader-text>Показать ещё</span>
                                <i class="fa-solid fa-arrow-down text-[0.72rem]" aria-hidden="true"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                    <section class="blog-card blog-empty-search p-6" id="blog-empty-search" data-admin-block-root>
                        <h2 class="text-[1.1rem] font-bold text-[#0f2749]">Ничего не найдено</h2>
                        <p class="mt-2 text-[0.92rem] leading-relaxed text-[#45637f]"<?php echo $blogSearchEmptyNode['attr']; ?>><?php echo e($blogSearchEmptyNode['value']); ?></p>
                    </section>
                <?php endif; ?>
            </div>

            <aside class="blog-sidebar blog-sticky-column">
                <section class="blog-sidebar-card blog-sidebar-cta">
                    <p class="text-[0.76rem] font-bold uppercase tracking-[0.14em] text-[#1977b2]">Запись в клинику</p>
                    <h2 class="mt-2 text-[1.28rem] font-bold leading-tight text-[#0f2749]">Хотите обсудить свой случай с врачом?</h2>
                    <p class="mt-2 text-[0.92rem] leading-relaxed text-[#45637f]">Администратор уточнит запрос, подскажет направление и предложит ближайшее удобное окно приема.</p>
                    <a href="<?php echo e(defined('ONLINE_BOOKING_URL') ? ONLINE_BOOKING_URL : '/'); ?>" onclick="onlineBooking.open();return false;" class="mt-4 inline-flex w-full items-center justify-center rounded-lg bg-[#1977b2] px-4 py-2.5 text-[0.9rem] font-semibold text-white hover:bg-[#16658f]" data-booking-link="1" data-booking-source="Блог — сайдбар">Записаться</a>
                    <a href="tel:<?php echo e(preg_replace('/[^\d+]/', '', CLINIC_PHONE)); ?>" class="mt-2 inline-flex w-full items-center justify-center rounded-lg border border-[#cddce8] bg-white px-4 py-2.5 text-[0.88rem] font-semibold text-[#0a293c] hover:border-[#1977b2] hover:text-[#1977b2]"><?php echo e(CLINIC_PHONE); ?></a>
                </section>

                <section class="blog-sidebar-card">
                    <h2 class="text-[1.08rem] font-bold text-[#0f2749]">Популярные услуги</h2>
                    <p class="mt-1 text-[0.84rem] leading-relaxed text-[#607d96]">Частые направления, с которых пациенты начинают восстановительный маршрут.</p>
                    <div class="mt-3 grid gap-2">
                        <?php foreach ($popularServices as $service): ?>
                            <?php if (!empty($service['text']) && !empty($service['url'])): ?>
                                <a href="<?php echo e($service['url']); ?>" class="blog-service-link">
                                    <span><?php echo e($service['text']); ?></span>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </section>

                <?php if ($sidebarPosts): ?>
                    <section class="blog-sidebar-card">
                        <h2 class="text-[1.08rem] font-bold text-[#0f2749]">Свежие материалы</h2>
                        <div class="mt-3 grid gap-2">
                            <?php foreach ($sidebarPosts as $sidebarPost): ?>
                                <a href="<?php echo e(bioinmed_blog_post_url($sidebarPost)); ?>" class="blog-fresh-link">
                                    <span><?php echo e(bioinmed_blog_title($sidebarPost)); ?></span>
                                    <time datetime="<?php echo e(date(DATE_ATOM, (int)($sidebarPost['date'] ?? time()))); ?>" class="mt-1 block text-[0.76rem] font-medium text-[#7890a5]"><?php echo e(bioinmed_blog_format_date($sidebarPost['date'] ?? 0, 'd.m.Y')); ?></time>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <section class="blog-sidebar-card">
                    <h2 class="text-[1.08rem] font-bold text-[#0f2749]">Подписка</h2>
                    <p class="mt-2 text-[0.88rem] leading-relaxed text-[#45637f]">Можно читать обновления через RSS или открыть первоисточник публикаций.</p>
                    <div class="mt-3 grid gap-2">
                        <a href="/blog-rss.php" class="inline-flex items-center justify-center gap-2 rounded-lg border border-[#cddce8] px-3 py-2 text-[0.84rem] font-semibold text-[#0a293c] hover:border-[#1977b2] hover:text-[#1977b2]">
                            <i class="fa-solid fa-rss" aria-hidden="true"></i>
                            RSS-лента
                        </a>
                        <a href="<?php echo e($vkUrl); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#1977b2] px-3 py-2 text-[0.84rem] font-semibold text-white hover:bg-[#16658f]">
                            <i class="fa-brands fa-vk" aria-hidden="true"></i>
                            Источник материалов
                        </a>
                    </div>
                </section>
            </aside>
        </div>
    </main>

    <?php
    $footer = new Footer($brand_colors);
    echo $footer->render();
    ?>
    <script>
        (function initBlogSearch() {
            var input = document.getElementById('blog-search');
            var status = document.getElementById('blog-search-status');
            var empty = document.getElementById('blog-empty-search');
            var loader = document.getElementById('blog-posts-loader');
            var loadMore = document.getElementById('blog-posts-load-more');
            var loaderText = loader ? loader.querySelector('[data-blog-posts-loader-text]') : null;
            var posts = Array.prototype.slice.call(document.querySelectorAll('[data-blog-post]'));
            if (!input || !posts.length) {
                return;
            }

            var initialLimit = 30;
            var batchSize = 30;
            var loadedCount = Math.min(initialLimit, posts.length);
            var currentQuery = '';
            var observer = null;

            function normalize(value) {
                return String(value || '').toLocaleLowerCase('ru-RU').replace(/\s+/g, ' ').trim();
            }

            function setLoader(visible, remaining) {
                if (!loader) return;
                loader.classList.toggle('is-visible', visible);
                if (loaderText) {
                    loaderText.textContent = remaining > 0 ? 'Показать ещё ' + Math.min(batchSize, remaining) : 'Все публикации показаны';
                }
            }

            function updateLazyVisibility() {
                var remaining = Math.max(0, posts.length - loadedCount);
                posts.forEach(function(post, index) {
                    post.classList.toggle('is-lazy-hidden', currentQuery === '' && index >= loadedCount);
                });
                setLoader(currentQuery === '' && remaining > 0, remaining);
                if (status && currentQuery === '') {
                    status.textContent = loadedCount < posts.length ? 'Показано ' + loadedCount + ' из ' + posts.length : '';
                }
            }

            function showNextBatch() {
                loadedCount = Math.min(posts.length, loadedCount + batchSize);
                updateLazyVisibility();
            }

            function update() {
                var query = normalize(input.value);
                currentQuery = query;
                var visible = 0;

                posts.forEach(function(post) {
                    var text = normalize(post.getAttribute('data-search-text'));
                    var matched = query === '' || text.indexOf(query) !== -1;
                    post.classList.toggle('is-hidden', !matched);
                    if (matched) {
                        visible += 1;
                    }
                });

                if (empty) {
                    empty.classList.toggle('is-visible', query !== '' && visible === 0);
                }
                if (status) {
                    status.textContent = query === '' ? '' : visible + ' из ' + posts.length;
                }
                updateLazyVisibility();
            }

            input.addEventListener('input', update);
            if (loadMore) {
                loadMore.addEventListener('click', showNextBatch);
            }
            if (loader && 'IntersectionObserver' in window) {
                observer = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting && currentQuery === '' && loadedCount < posts.length) {
                            showNextBatch();
                        }
                    });
                }, { rootMargin: '600px 0px 900px' });
                observer.observe(loader);
            }
            updateLazyVisibility();
        })();
        (function initBlogPostMore() {
            document.addEventListener('click', function(event) {
                var button = event.target.closest && event.target.closest('[data-blog-more]');
                if (!button) {
                    return;
                }

                var post = button.closest('[data-blog-post]');
                if (!post) {
                    return;
                }

                var expanded = !post.classList.contains('is-expanded');
                post.classList.toggle('is-expanded', expanded);
                button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                button.textContent = expanded ? 'Скрыть' : 'Показать ещё';
            });
        })();
    </script>
    <?php echo bioinmed_render_blog_weather_assets(); ?>
</body>
</html>
