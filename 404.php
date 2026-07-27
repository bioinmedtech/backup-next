<?php
require_once 'config.php';
require_once 'includes/components/Components.php';

http_response_code(404);

$siteUrl = rtrim(CLINIC_SITE_URL, '/');
$iconPath = CLINIC_ICON_PATH;
$iconUrl = $siteUrl . $iconPath;
$canonicalUrl = $siteUrl . '/404';
$pageTitle = 'Страница не найдена (404) | ' . CLINIC_NAME;
$pageDescription = 'Запрошенная страница не найдена. Перейдите на главную или оставьте номер, и команда клиники поможет найти нужную услугу.';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$phone1 = CLINIC_PHONE;
$phone1link = preg_replace('/\D/', '', $phone1);
$phone2 = defined('CLINIC_PHONE_2') ? CLINIC_PHONE_2 : '';
$phone2link = $phone2 ? preg_replace('/\D/', '', $phone2) : '';
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?></title>
    <meta name="description" content="<?php echo e($pageDescription); ?>">
    <meta name="robots" content="noindex,follow,noarchive">
    <link rel="canonical" href="<?php echo e($canonicalUrl); ?>">
    <meta name="theme-color" content="#1977b2">
    <?php echo bioinmed_render_social_meta($pageTitle, $pageDescription, $canonicalUrl, [
        'image' => bioinmed_default_social_image_url(),
    ]); ?>
    <?php echo bioinmed_render_favicon_links($iconPath); ?>
    <?php echo bioinmed_render_public_head_assets(); ?>
    <style>
        .fade-up { opacity: 0; transform: translateY(20px); transition: opacity .45s ease, transform .45s ease; }
        .fade-up.visible { opacity: 1; transform: translateY(0); }
    </style>
    <?php echo bioinmed_uis_counter_head(); ?>
</head>
<body class="flex min-h-screen flex-col bg-[#e4f1fa] text-[#0f2749] antialiased">
    <?php echo bioinmed_yandex_metrika_noscript(); ?>
<?php
$header = new Header($brand_colors);
echo $header->render();
?>

<main class="mx-auto flex w-full max-w-6xl grow items-center px-6 py-12 md:px-10 md:py-16">
    <div class="grid w-full items-start gap-6 lg:grid-cols-[1fr_390px]">
        <section class="fade-up rounded-3xl border border-[#d8e6f3] bg-[#e4f1fa] p-7 shadow-[0_16px_40px_rgba(8,36,70,0.08)] md:p-9">
            <div class="inline-flex items-center gap-2 rounded-full border border-[#d5e5f3] bg-[#f2f9ff] px-3 py-1.5 text-xs font-semibold text-[#0a293c]">
                <i class="fa-solid fa-triangle-exclamation text-[#1977b2]"></i>
                Ошибка 404
            </div>
            <h1 class="mt-4 text-2xl font-bold leading-tight text-[#0a293c] md:text-4xl">Мы не нашли эту страницу</h1>
            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-[#0a293c] md:text-base">
                Возможно, ссылка устарела или адрес введён с ошибкой. Вы можете вернуться на главную,
                открыть каталог услуг или оставить номер, и мы быстро подскажем нужного специалиста.
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="/" class="inline-flex items-center gap-2 rounded-full bg-[#1977b2] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#16658f]">
                    <i class="fa-solid fa-house"></i> На главную
                </a>
                <a href="/services" class="inline-flex items-center gap-2 rounded-full border border-[#1977b2] px-5 py-2.5 text-sm font-semibold text-[#1977b2] hover:bg-[#f2f9ff]">
                    <i class="fa-solid fa-stethoscope"></i> Услуги
                </a>
                <a href="/prices" class="inline-flex items-center gap-2 rounded-full border border-[#c8ddee] bg-white px-5 py-2.5 text-sm font-semibold text-[#0a293c] hover:bg-[#f2f9ff]">
                    <i class="fa-solid fa-list"></i> Прайс-лист
                </a>
            </div>

            <div class="mt-8 grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl border border-[#dce8f5] bg-white p-4 text-sm text-[#0a293c]">
                    <p class="font-semibold text-[#0a293c]">Ищете врача?</p>
                    <p class="mt-1">Посмотрите команду специалистов и выберите удобный формат приёма.</p>
                    <a href="/doctors" class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-[#1977b2] hover:text-[#16658f]">Открыть профессиональную команду <i class="fa-solid fa-arrow-right text-[0.65rem]"></i></a>
                </div>
                <div class="rounded-2xl border border-[#dce8f5] bg-white p-4 text-sm text-[#0a293c]">
                    <p class="font-semibold text-[#0a293c]">Нужна помощь с навигацией?</p>
                    <p class="mt-1">Мы подскажем нужную услугу и поможем выбрать подходящего врача.</p>
                    <a href="tel:<?php echo e($phone1link); ?>" class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-[#1977b2] hover:text-[#16658f]">Позвонить сейчас <i class="fa-solid fa-phone text-[0.65rem]"></i></a>
                </div>
            </div>
        </section>

        <aside class="fade-up rounded-3xl border border-[#d9e7f3] bg-white p-6 shadow-[0_12px_30px_rgba(8,36,70,0.1)]" style="transition-delay:.08s">
            <p class="text-xs font-semibold uppercase tracking-[0.15em] text-[#0a293c]">Помочь с записью</p>
            <h2 class="mt-2 text-xl font-bold text-[#0a293c]">Запишитесь онлайн — прямо сейчас</h2>
            <p class="mt-2 text-sm leading-relaxed text-[#0a293c]">Перезвоним в течение 15 минут.</p>

            <div class="mt-4">
                <?php echo bioinmed_render_callback_form([
                    'source_label' => '404 — форма обратного звонка',
                    'submit_label' => 'Перезвоните мне',
                    'button_class' => 'inline-flex w-full items-center justify-center rounded-full bg-[#1977b2] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#16658f] disabled:cursor-not-allowed disabled:bg-[#a7d7e9] disabled:text-white/90',
                ]); ?>
            </div>

            <div class="my-5 flex items-center gap-3">
                <div class="h-px grow bg-[#e2ecf5]"></div>
                <span class="text-xs text-[#0a293c]">или позвоните</span>
                <div class="h-px grow bg-[#e2ecf5]"></div>
            </div>

            <a href="tel:<?php echo e($phone1link); ?>" class="flex items-center justify-center gap-2 rounded-full border border-[#1977b2] px-5 py-2.5 text-sm font-semibold text-[#1977b2] hover:bg-[#f0f8ff]">
                <i class="fa-solid fa-phone"></i> <?php echo e($phone1); ?>
            </a>
            <?php if ($phone2): ?>
            <a href="tel:<?php echo e($phone2link); ?>" class="mt-2 flex items-center justify-center gap-2 rounded-full border border-[#d6e4f2] px-5 py-2.5 text-sm font-semibold text-[#0a293c] hover:border-[#1977b2] hover:text-[#1977b2]">
                <i class="fa-solid fa-phone text-xs"></i> <?php echo e($phone2); ?>
            </a>
            <?php endif; ?>
        </aside>
    </div>
</main>

<?php
$footer = new Footer($brand_colors);
echo $footer->render();
?>

<script>
    document.querySelectorAll('.fade-up').forEach(function(el) {
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) { el.classList.add('visible'); observer.unobserve(el); }
            });
        }, { threshold: 0.08 });
        observer.observe(el);
    });

    document.getElementById('lost-page-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type=submit]');
        btn.textContent = '✓ Приняли! Скоро перезвоним';
        btn.classList.remove('bg-[#1977b2]', 'hover:bg-[#16658f]');
        btn.classList.add('bg-green-600');
        btn.disabled = true;
    });
</script>
</body>
</html>
