<?php
require_once __DIR__ . '/../includes/pin_protection.php';
bioinmed_pin_require_access();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/components/Components.php';
require_once __DIR__ . '/../includes/blog/VkBlog.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$post = bioinmed_blog_find_post(bioinmed_blog_current_post_identifier());
if (!$post) {
    http_response_code(404);
    require __DIR__ . '/../404.php';
    exit;
}

$siteUrl = rtrim(CLINIC_SITE_URL, '/');
$canonicalUrl = bioinmed_blog_post_url($post, true);
$canonicalPath = bioinmed_blog_post_url($post, false);
$vkPostUrl = bioinmed_blog_vk_post_url($post);
$title = bioinmed_blog_title($post);
$bodyText = bioinmed_blog_body_text($post);
$pageTitle = $title . ' | Блог ' . CLINIC_NAME;
$pageDescription = bioinmed_meta_description(bioinmed_blog_excerpt($bodyText, 165), 'Публикация блога клиники БИОИНМЕД.');
$primaryImage = bioinmed_blog_primary_image($post);
$socialImageUrl = $primaryImage !== '' ? $primaryImage : bioinmed_og_image_url('home');
$attachments = is_array($post['attachments'] ?? null) ? $post['attachments'] : [];
$relatedPosts = bioinmed_blog_related_posts($post, 4);
$commentItems = bioinmed_blog_comments($post);
$popularServices = [
    bioinmed_link('services.habilect_diagnostics'),
    bioinmed_link('services.osteopathy'),
    bioinmed_link('services.acupuncture'),
    ['text' => 'Инфузионная терапия', 'url' => '/services/infusion-therapy'],
];

$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'headline' => $title,
    'description' => $pageDescription,
    'articleBody' => $bodyText,
    'wordCount' => str_word_count(strip_tags($bodyText), 0, 'АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯабвгдеёжзийклмнопрстуфхцчшщъыьэюя'),
    'commentCount' => (int)($post['comments'] ?? 0),
    'datePublished' => date(DATE_ATOM, (int)($post['date'] ?? time())),
    'dateModified' => date(DATE_ATOM, (int)($post['date'] ?? time())),
    'url' => $canonicalUrl,
    'mainEntityOfPage' => $canonicalUrl,
    'isBasedOn' => $vkPostUrl,
    'inLanguage' => 'ru-RU',
    'author' => bioinmed_medical_organization_schema(),
    'publisher' => bioinmed_medical_organization_schema(),
];
if ($primaryImage !== '') {
    $structuredData['image'] = [$primaryImage];
}

$currentPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
$isCli = PHP_SAPI === 'cli';
if (!$isCli && is_string($currentPath) && rtrim($currentPath, '/') !== rtrim($canonicalPath, '/')) {
    header('Location: ' . $canonicalUrl, true, 301);
    exit;
}

$breadcrumbStructuredData = bioinmed_breadcrumb_schema([
    ['name' => 'Главная', 'url' => '/'],
    ['name' => 'Блог', 'url' => '/blog/'],
    ['name' => $title, 'url' => bioinmed_blog_post_url($post, false)],
]);
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
    <script type="application/ld+json"><?php echo json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script type="application/ld+json"><?php echo json_encode($breadcrumbStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <style>
        html { font-size: clamp(17px, 0.5vw + 15px, 19px); }
        body { line-height: 1.72; }
        .blog-surface { background: #eef6fb; }
        .blog-card { border: 1px solid #d8e6f2; border-radius: 12px; background: #fff; }
        .blog-sidebar-card { border: 1px solid #d8e6f2; border-radius: 12px; background: #fff; padding: 1.1rem; }
        .blog-sidebar-cta { border-color: #aed3e8; background: linear-gradient(145deg,#ffffff 0%,#eef8ff 58%,#f4fbf8 100%); }
        .blog-service-link, .blog-fresh-link { display: block; border: 1px solid #e6eef6; border-radius: 10px; background: #fbfdff; padding: .78rem .82rem; color: #0f2749; font-size: .88rem; font-weight: 700; line-height: 1.28; }
        .blog-service-link:hover, .blog-fresh-link:hover { border-color: #9fc9e8; color: #1977b2; background: #f7fbff; }
        .blog-content p + p { margin-top: .9rem; }
        .blog-layout { display: grid; gap: 1.25rem; align-items: start; }
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
        .blog-comment { border: 1px solid #e5edf5; border-radius: 10px; background: #fbfdff; padding: .9rem; }
        .blog-comment + .blog-comment { margin-top: .65rem; }
        .blog-comment-text { color: #0a293c; font-size: .94rem; line-height: 1.6; }
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
        .blog-weather-card__metric i { display: block; color: rgba(255,255,255,.86); font-size: .82rem; line-height: 1; }
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
        @media (min-width: 1024px) {
            .blog-layout { grid-template-columns: minmax(0, 1fr) 360px; }
            .blog-sticky-column { position: sticky; top: calc(var(--sticky-header-offset, 54px) + .75rem); max-height: calc(100vh - var(--sticky-header-offset, 54px) - 1.5rem); overflow-y: auto; padding-right: .25rem; }
        }
        @media (min-width: 1280px) {
            .blog-layout { grid-template-columns: minmax(285px, 320px) minmax(0, 1fr) minmax(330px, 360px); }
            .blog-weather-column { display: grid; gap: 1rem; }
        }
        @media (max-width: 767px) {
            html { font-size: 16px; }
            body { line-height: 1.6; }
            main { padding-left: .65rem !important; padding-right: .65rem !important; }
            .blog-weather-mobile { display: block; }
            .blog-weather-mobile .blog-weather-card__hero { min-height: 198px; }
            .blog-weather-mobile .blog-weather-card__forecast { max-height: none; overflow: visible; }
            .blog-layout, .blog-main { gap: .75rem; }
            .blog-card { border-radius: 10px; }
            article.blog-card > .p-5 { padding: 1rem !important; }
            article.blog-card h1 { font-size: 1.36rem !important; line-height: 1.18 !important; }
            .blog-content { font-size: .94rem !important; line-height: 1.62 !important; }
            .blog-content p + p { margin-top: .7rem; }
        }
    </style>
    <?php echo bioinmed_uis_counter_head(); ?>
</head>
<body class="blog-surface text-[#0f2749] antialiased">
    <?php echo bioinmed_yandex_metrika_noscript(); ?>
    <?php echo (new Header($brand_colors))->render(); ?>

    <main class="mx-auto max-w-6xl px-6 py-6 md:px-10 md:py-8">
        <div class="bioinmed-back-row"><?php echo bioinmed_render_back_button(['fallback' => '/blog', 'label' => 'К блогу']); ?></div>

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
                                <div class="blog-weather-card__brand">БИОИНМЕД</div>
                                <div class="blog-weather-card__place" data-weather-place>Погода рядом</div>
                            </div>
                            <div class="blog-weather-card__icon" aria-hidden="true" data-weather-icon><svg viewBox="0 0 72 72"><circle cx="36" cy="36" r="13" fill="#f7b743"/><g stroke="#f7b743" stroke-width="5" stroke-linecap="round"><path d="M36 9v8M36 55v8M9 36h8M55 36h8M17 17l6 6M49 49l6 6M55 17l-6 6M23 49l-6 6"/></g></svg></div>
                        </div>
                        <div class="blog-weather-card__temp" data-weather-temp>--</div>
                        <div class="blog-weather-card__meta" data-weather-summary>Определяем город...</div>
                        <div class="blog-weather-card__stats">
                            <div class="blog-weather-card__metric"><i aria-hidden="true">~</i><strong data-weather-wind>--</strong><span>ветер</span></div>
                            <div class="blog-weather-card__metric"><i aria-hidden="true">◴</i><strong data-weather-feels>--</strong><span>ощущается</span></div>
                            <div class="blog-weather-card__metric"><i aria-hidden="true">◇</i><strong data-weather-humidity>--</strong><span>влажность</span></div>
                            <div class="blog-weather-card__metric"><i aria-hidden="true">☂</i><strong data-weather-precip>--</strong><span>осадки</span></div>
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
                                <div class="blog-weather-card__brand">БИОИНМЕД</div>
                                <div class="blog-weather-card__place" data-weather-place>Погода рядом</div>
                            </div>
                            <div class="blog-weather-card__icon" aria-hidden="true" data-weather-icon><svg viewBox="0 0 72 72"><circle cx="36" cy="36" r="13" fill="#f7b743"/><g stroke="#f7b743" stroke-width="5" stroke-linecap="round"><path d="M36 9v8M36 55v8M9 36h8M55 36h8M17 17l6 6M49 49l6 6M55 17l-6 6M23 49l-6 6"/></g></svg></div>
                        </div>
                        <div class="blog-weather-card__temp" data-weather-temp>--</div>
                        <div class="blog-weather-card__meta" data-weather-summary>Определяем город...</div>
                        <div class="blog-weather-card__stats">
                            <div class="blog-weather-card__metric">
                                <i aria-hidden="true">~</i>
                                <strong data-weather-wind>--</strong>
                                <span>ветер</span>
                            </div>
                            <div class="blog-weather-card__metric">
                                <i aria-hidden="true">◴</i>
                                <strong data-weather-feels>--</strong>
                                <span>ощущается</span>
                            </div>
                            <div class="blog-weather-card__metric">
                                <i aria-hidden="true">◇</i>
                                <strong data-weather-humidity>--</strong>
                                <span>влажность</span>
                            </div>
                            <div class="blog-weather-card__metric">
                                <i aria-hidden="true">☂</i>
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
                <article class="blog-card overflow-hidden">
                    <?php if ($primaryImage !== ''): ?>
                        <img src="<?php echo e($primaryImage); ?>" alt="<?php echo e($title); ?>" class="max-h-[560px] w-full object-cover" loading="eager" decoding="async">
                    <?php endif; ?>
                    <div class="p-5 md:p-7">
                        <header class="flex flex-wrap items-center gap-2 text-[0.82rem] font-semibold text-[#6b8298]">
                            <a href="/blog/" class="text-[#1977b2] hover:text-[#16658f]">Блог БИОИНМЕД</a>
                            <span class="h-1 w-1 rounded-full bg-[#9db4c8]" aria-hidden="true"></span>
                            <time datetime="<?php echo e(date(DATE_ATOM, (int)($post['date'] ?? time()))); ?>"><?php echo e(bioinmed_blog_format_date($post['date'] ?? 0)); ?></time>
                        </header>

                        <h1 class="mt-5 text-[1.72rem] font-bold leading-tight text-[#0f2749] md:text-[2.2rem]"><?php echo e($title); ?></h1>

                        <?php if ($bodyText !== ''): ?>
                            <div class="blog-content mt-5 text-[1rem] leading-relaxed text-[#0a293c]">
                                <?php foreach (preg_split('/\R{2,}/u', $bodyText) ?: [] as $paragraph): ?>
                                    <?php $paragraph = trim($paragraph); ?>
                                    <?php if ($paragraph !== ''): ?>
                                        <p><?php echo nl2br(e($paragraph)); ?></p>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($attachments): ?>
                            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                                <?php foreach ($attachments as $attachment): ?>
                                    <?php if (($attachment['type'] ?? '') === 'photo' && !empty($attachment['url']) && $attachment['url'] !== $primaryImage): ?>
                                        <a href="<?php echo e($attachment['url']); ?>" target="_blank" rel="noopener noreferrer" class="block overflow-hidden rounded-xl border border-[#dce6f0] bg-[#f7f9fb]">
                                            <img src="<?php echo e($attachment['url']); ?>" alt="<?php echo e($attachment['alt'] ?? $title); ?>" class="max-h-[420px] w-full object-cover" loading="lazy" decoding="async">
                                        </a>
                                    <?php elseif (($attachment['type'] ?? '') === 'video' && !empty($attachment['url'])): ?>
                                        <a href="<?php echo e($attachment['url']); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-xl border border-[#dce6f0] bg-[#f7f9fb] p-4 font-semibold text-[#1977b2] hover:border-[#1977b2]">
                                            <i class="fa-solid fa-play" aria-hidden="true"></i>
                                            <?php echo e($attachment['title'] ?? 'Видео VK'); ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <footer class="mt-6 flex flex-wrap items-center gap-3 border-t border-[#e7eef5] pt-4 text-[0.86rem] text-[#6b8298]">
                            <span><i class="fa-solid fa-heart-pulse text-[#1977b2]" aria-hidden="true"></i> <?php echo (int)($post['likes'] ?? 0); ?></span>
                            <span><i class="fa-solid fa-comments text-[#1977b2]" aria-hidden="true"></i> <?php echo (int)($post['comments'] ?? 0); ?></span>
                            <a href="<?php echo e($vkPostUrl); ?>" target="_blank" rel="noopener noreferrer" class="ml-auto inline-flex items-center gap-2 rounded-full border border-[#d2e0ec] px-3 py-1.5 font-semibold text-[#1977b2] hover:border-[#1977b2]">
                                <i class="fa-brands fa-vk" aria-hidden="true"></i>
                                Источник
                            </a>
                        </footer>
                    </div>
                </article>

                <section class="blog-card p-5 md:p-6" aria-labelledby="blog-comments-title">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 id="blog-comments-title" class="text-[1.16rem] font-bold text-[#0f2749]">Комментарии</h2>
                        <span class="rounded-full border border-[#d8e6f2] bg-[#f7fbff] px-2.5 py-1 text-[0.78rem] font-semibold text-[#607d96]"><?php echo (int)($post['comments'] ?? 0); ?></span>
                    </div>
                    <?php if ($commentItems): ?>
                        <div class="mt-4">
                            <?php foreach ($commentItems as $comment): ?>
                                <article class="blog-comment">
                                    <div class="flex flex-wrap items-center gap-2 text-[0.76rem] font-semibold text-[#7890a5]">
                                        <span>Комментарий к публикации</span>
                                        <?php if (!empty($comment['date'])): ?>
                                            <span class="h-1 w-1 rounded-full bg-[#b5c5d4]" aria-hidden="true"></span>
                                            <time datetime="<?php echo e(date(DATE_ATOM, (int)$comment['date'])); ?>"><?php echo e(bioinmed_blog_format_date($comment['date'], 'd.m.Y H:i')); ?></time>
                                        <?php endif; ?>
                                    </div>
                                    <p class="blog-comment-text mt-2"><?php echo nl2br(e($comment['text'] ?? '')); ?></p>
                                    <?php if (!empty($comment['likes'])): ?>
                                        <p class="mt-2 text-[0.78rem] font-semibold text-[#7890a5]"><i class="fa-solid fa-heart-pulse text-[#1977b2]" aria-hidden="true"></i> <?php echo (int)$comment['likes']; ?></p>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="mt-3 text-[0.92rem] leading-relaxed text-[#45637f]">Комментариев в локальной копии пока нет. Обсуждение можно посмотреть в источнике публикации.</p>
                    <?php endif; ?>
                    <a href="<?php echo e($vkPostUrl); ?>" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex items-center gap-2 rounded-lg border border-[#d2e0ec] px-3 py-2 text-[0.86rem] font-semibold text-[#1977b2] hover:border-[#1977b2]">
                        Открыть обсуждение
                        <i class="fa-solid fa-arrow-up-right-from-square text-[0.72rem]" aria-hidden="true"></i>
                    </a>
                </section>
            </div>

            <aside class="blog-sidebar blog-sticky-column">
                <section class="blog-sidebar-card blog-sidebar-cta">
                    <p class="text-[0.76rem] font-bold uppercase tracking-[0.14em] text-[#1977b2]">Запись в клинику</p>
                    <h2 class="mt-2 text-[1.2rem] font-bold leading-tight text-[#0f2749]">Подберем специалиста под Ваш запрос</h2>
                    <p class="mt-2 text-[0.9rem] leading-relaxed text-[#45637f]">Администратор уточнит ситуацию и предложит удобное время приема.</p>
                    <a href="<?php echo e(defined('ONLINE_BOOKING_URL') ? ONLINE_BOOKING_URL : '/'); ?>" onclick="onlineBooking.open();return false;" class="mt-4 inline-flex w-full items-center justify-center rounded-lg bg-[#1977b2] px-4 py-2.5 text-[0.9rem] font-semibold text-white hover:bg-[#16658f]" data-booking-link="1" data-booking-source="Блог — пост">Записаться</a>
                    <a href="tel:<?php echo e(preg_replace('/[^\d+]/', '', CLINIC_PHONE)); ?>" class="mt-2 inline-flex w-full items-center justify-center rounded-lg border border-[#cddce8] bg-white px-4 py-2.5 text-[0.88rem] font-semibold text-[#0a293c] hover:border-[#1977b2] hover:text-[#1977b2]"><?php echo e(CLINIC_PHONE); ?></a>
                </section>
                <section class="blog-sidebar-card">
                    <h2 class="text-[1.08rem] font-bold text-[#0f2749]">Популярные услуги</h2>
                    <div class="mt-3 grid gap-2">
                        <?php foreach ($popularServices as $service): ?>
                            <?php if (!empty($service['text']) && !empty($service['url'])): ?>
                                <a href="<?php echo e($service['url']); ?>" class="blog-service-link"><?php echo e($service['text']); ?></a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php if ($relatedPosts): ?>
                    <section class="blog-sidebar-card">
                        <h2 class="text-[1.08rem] font-bold text-[#0f2749]">Другие публикации</h2>
                        <div class="mt-3 grid gap-2">
                            <?php foreach ($relatedPosts as $related): ?>
                                <a href="<?php echo e(bioinmed_blog_post_url($related)); ?>" class="blog-fresh-link">
                                    <span><?php echo e(bioinmed_blog_title($related)); ?></span>
                                    <time datetime="<?php echo e(date(DATE_ATOM, (int)($related['date'] ?? time()))); ?>" class="mt-1 block text-[0.76rem] font-medium text-[#7890a5]"><?php echo e(bioinmed_blog_format_date($related['date'] ?? 0, 'd.m.Y')); ?></time>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </aside>
        </div>
    </main>

    <?php echo (new Footer($brand_colors))->render(); ?>
    <script>
        (function initBlogWeather() {
            var roots = Array.prototype.slice.call(document.querySelectorAll('[data-blog-weather]'));
            var mobile = document.querySelector('[data-blog-weather-mobile]');
            var mobileToggle = document.querySelector('[data-weather-mobile-toggle]');
            var storageKey = 'bioinmedBlogWeatherMobileOpen';
            if (!roots.length || !window.fetch) {
                return;
            }

            var fallbackLocation = { city: 'Москва', latitude: 55.7558, longitude: 37.6173 };
            var descriptions = {
                0: 'Ясно', 1: 'Преимущественно ясно', 2: 'Переменная облачность', 3: 'Пасмурно',
                45: 'Туман', 48: 'Изморозь', 51: 'Морось', 53: 'Морось', 55: 'Морось',
                61: 'Небольшой дождь', 63: 'Дождь', 65: 'Сильный дождь', 71: 'Небольшой снег',
                73: 'Снег', 75: 'Сильный снег', 80: 'Ливень', 81: 'Ливень', 82: 'Сильный ливень',
                95: 'Гроза', 96: 'Гроза', 99: 'Гроза'
            };

            function setText(node, value) {
                if (node) node.textContent = value;
            }

            function eachWeatherNode(selector, callback) {
                roots.forEach(function(root) {
                    var node = root.querySelector(selector);
                    if (node) callback(node, root);
                });
            }

            function weatherSvg(code) {
                if ([61,63,65,80,81,82].indexOf(code) !== -1) return '<svg viewBox="0 0 72 72"><path d="M23 45h27a11 11 0 0 0 1-22 18 18 0 0 0-34 7A8 8 0 0 0 23 45Z" fill="#c7dcff"/><path d="M24 53l-4 9M36 53l-4 9M48 53l-4 9" stroke="#5b9dff" stroke-width="5" stroke-linecap="round"/></svg>';
                if ([71,73,75].indexOf(code) !== -1) return '<svg viewBox="0 0 72 72"><path d="M23 43h27a11 11 0 0 0 1-22 18 18 0 0 0-34 7A8 8 0 0 0 23 43Z" fill="#d6e7ff"/><g fill="#9ec6ff"><circle cx="24" cy="55" r="3"/><circle cx="36" cy="59" r="3"/><circle cx="48" cy="55" r="3"/></g></svg>';
                if ([95,96,99].indexOf(code) !== -1) return '<svg viewBox="0 0 72 72"><path d="M23 42h27a11 11 0 0 0 1-22 18 18 0 0 0-34 7A8 8 0 0 0 23 42Z" fill="#4b5568"/><path d="M37 40l-8 16h8l-4 13 13-20h-8l5-9Z" fill="#f4b73f"/></svg>';
                if ([45,48].indexOf(code) !== -1) return '<svg viewBox="0 0 72 72"><path d="M23 38h27a11 11 0 0 0 1-22 18 18 0 0 0-34 7A8 8 0 0 0 23 38Z" fill="#c7dcff"/><path d="M14 50h44M19 59h34" stroke="#a8b4c2" stroke-width="5" stroke-linecap="round"/></svg>';
                if ([2,3,51,53,55].indexOf(code) !== -1) return '<svg viewBox="0 0 72 72"><circle cx="26" cy="25" r="12" fill="#f7b743"/><path d="M23 46h30a12 12 0 0 0 1-24 19 19 0 0 0-36 8A9 9 0 0 0 23 46Z" fill="#c7dcff"/></svg>';
                return '<svg viewBox="0 0 72 72"><circle cx="36" cy="36" r="13" fill="#f7b743"/><g stroke="#f7b743" stroke-width="5" stroke-linecap="round"><path d="M36 9v8M36 55v8M9 36h8M55 36h8M17 17l6 6M49 49l6 6M55 17l-6 6M23 49l-6 6"/></g></svg>';
            }

            function setIcon(node, code) {
                if (node) node.innerHTML = weatherSvg(Number(code || 0));
            }

            function setMobileOpen(open) {
                if (!mobile || !mobileToggle) return;
                mobile.classList.toggle('is-open', open);
                mobileToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                try {
                    window.localStorage.setItem(storageKey, open ? '1' : '0');
                } catch (error) {}
            }

            function formatDate(value, index) {
                var date = new Date(value + 'T12:00:00');
                if (Number.isNaN(date.getTime())) return index === 0 ? 'Сегодня' : '';
                if (index === 0) return 'Сегодня';
                return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long' });
            }

            function dayLabel(value, index) {
                if (index === 0) return 'Сейчас';
                var date = new Date(value + 'T12:00:00');
                if (Number.isNaN(date.getTime())) return '';
                return date.toLocaleDateString('ru-RU', { weekday: 'long' });
            }

            function renderForecast(daily) {
                if (!daily || !Array.isArray(daily.time)) return;
                var html = daily.time.slice(0, 7).map(function(date, index) {
                    var code = Number((daily.weather_code || [])[index] || 0);
                    var max = Math.round(Number((daily.temperature_2m_max || [])[index] || 0));
                    var min = Math.round(Number((daily.temperature_2m_min || [])[index] || 0));
                    return '<div class="blog-weather-card__day"><div><div class="blog-weather-card__date">' + formatDate(date, index) + '</div><div class="blog-weather-card__label">' + dayLabel(date, index) + '</div></div><div class="blog-weather-card__day-icon" aria-hidden="true">' + weatherSvg(code) + '</div><div class="blog-weather-card__range"><strong>' + max + '°</strong><span>' + min + '°</span></div></div>';
                }).join('');
                eachWeatherNode('[data-weather-forecast]', function(node) {
                    node.innerHTML = html;
                });
            }

            function loadWeather(location) {
                var url = 'https://api.open-meteo.com/v1/forecast?latitude=' + encodeURIComponent(location.latitude) + '&longitude=' + encodeURIComponent(location.longitude) + '&current=temperature_2m,apparent_temperature,relative_humidity_2m,precipitation,weather_code,wind_speed_10m&daily=weather_code,temperature_2m_max,temperature_2m_min&forecast_days=7&timezone=auto&wind_speed_unit=ms';
                return fetch(url, { credentials: 'omit' }).then(function(response) {
                    if (!response.ok) throw new Error('weather');
                    return response.json();
                }).then(function(data) {
                    var current = data && data.current ? data.current : {};
                    var code = Number(current.weather_code || 0);
                    eachWeatherNode('[data-weather-temp]', function(node) { setText(node, Math.round(Number(current.temperature_2m || 0)) + '°'); });
                    eachWeatherNode('[data-weather-summary]', function(node) { setText(node, descriptions[code] || 'Погода сейчас'); });
                    eachWeatherNode('[data-weather-feels]', function(node) { setText(node, Math.round(Number(current.apparent_temperature || current.temperature_2m || 0)) + '°'); });
                    eachWeatherNode('[data-weather-wind]', function(node) { setText(node, Math.round(Number(current.wind_speed_10m || 0)) + ' м/с'); });
                    eachWeatherNode('[data-weather-humidity]', function(node) { setText(node, Math.round(Number(current.relative_humidity_2m || 0)) + '%'); });
                    eachWeatherNode('[data-weather-precip]', function(node) { setText(node, Math.round(Number(current.precipitation || 0)) + ' мм'); });
                    eachWeatherNode('[data-weather-place]', function(node) { setText(node, (location.city || 'Ваш город') + ' · по IP'); });
                    eachWeatherNode('[data-weather-updated]', function(node) { setText(node, 'Обновлено: ' + new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })); });
                    eachWeatherNode('[data-weather-icon]', function(node) { setIcon(node, code); });
                    renderForecast(data.daily);
                });
            }

            if (mobile && mobileToggle) {
                var savedOpen = false;
                try {
                    savedOpen = window.localStorage.getItem(storageKey) === '1';
                } catch (error) {}
                setMobileOpen(savedOpen);
                mobileToggle.addEventListener('click', function() {
                    setMobileOpen(!mobile.classList.contains('is-open'));
                });
            }

            fetch('https://ipapi.co/json/', { credentials: 'omit' })
                .then(function(response) {
                    if (!response.ok) throw new Error('geo');
                    return response.json();
                })
                .then(function(geo) {
                    return {
                        city: geo && geo.city ? geo.city : fallbackLocation.city,
                        latitude: geo && geo.latitude ? geo.latitude : fallbackLocation.latitude,
                        longitude: geo && geo.longitude ? geo.longitude : fallbackLocation.longitude
                    };
                })
                .catch(function() { return fallbackLocation; })
                .then(loadWeather)
                .catch(function() {
                    eachWeatherNode('[data-weather-summary]', function(node) { setText(node, 'Погода недоступна'); });
                    eachWeatherNode('[data-weather-place]', function(node) { setText(node, 'Попробуем обновить позже'); });
                });
        })();
    </script>
</body>
</html>
