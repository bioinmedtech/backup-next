<?php
require_once __DIR__ . '/includes/pin_protection.php';
bioinmed_pin_require_access();


require_once 'config.php';
require_once 'includes/components/Components.php';

$siteUrl = rtrim(CLINIC_SITE_URL, '/');
$iconPath = CLINIC_ICON_PATH;

$slug = isset($_GET['slug']) ? trim((string)$_GET['slug']) : '';
$problem = null;
$isChildrenProblemsPage = ($slug === 'children');
foreach ($problems as $item) {
    if (($item['slug'] ?? '') === $slug) {
        $problem = $item;
        break;
    }
}

if (!$isChildrenProblemsPage && $problem === null) {
    http_response_code(404);
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function problem_list_text(string $value): string {
    $value = trim($value);
    return preg_replace('/[.。]+$/u', '', $value) ?? $value;
}

$pageTitle = $isChildrenProblemsPage
    ? 'Детские проблемы — БИОИНМЕД'
    : ($problem ? ($problem['page_title'] ?? ($problem['title'] . ' — БИОИНМЕД')) : 'Проблема не найдена | БИОИНМЕД');
$pageDescription = $isChildrenProblemsPage
    ? 'Отдельный раздел детских проблем: симптомы, ситуации и переход на подробные страницы с маршрутом восстановления.'
    : ($problem ? ($problem['page_description'] ?? $problem['description']) : 'Страница не найдена');
$canonicalUrl = $isChildrenProblemsPage
    ? $siteUrl . '/problems/children'
    : ($problem ? $siteUrl . '/problems/' . rawurlencode((string)$problem['slug']) : $siteUrl . '/problems');
$robotsContent = ($isChildrenProblemsPage || $problem)
    ? 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'
    : 'noindex,follow';
$socialImageUrl = bioinmed_default_social_image_url();
$organizationStructuredData = bioinmed_medical_organization_schema();
$problemDetailsStorageKey = $problem ? ('bioinmed-problem-details:' . (string)($problem['slug'] ?? 'problem')) : '';
$breadcrumbStructuredData = bioinmed_breadcrumb_schema([
    ['name' => 'Главная', 'url' => '/'],
    ['name' => 'Найдите вашу ситуацию', 'url' => '/problems'],
    ['name' => $isChildrenProblemsPage ? 'Детские проблемы' : ($problem['title'] ?? 'Проблема'), 'url' => $canonicalUrl],
]);

$services_map = [];
foreach ($services as $service) {
    $sid = (string)($service['id'] ?? '');
    if ($sid !== '') {
        $services_map[$sid] = $service;
    }
}
$resolved_services = [];
if ($problem) {
    foreach (($problem['solution_links'] ?? []) as $solution_link) {
        if (!is_array($solution_link)) {
            continue;
        }
        $resolved_id = trim((string)($solution_link['id'] ?? ''));
        $label = trim((string)($solution_link['label'] ?? ''));
        if ($resolved_id === '') {
            continue;
        }
        if (isset($service_aliases[$resolved_id])) {
            $resolved_id = (string)$service_aliases[$resolved_id];
        }
        if (isset($services_map[$resolved_id])) {
            $resolved_service = $services_map[$resolved_id];
            $resolved_service['display_label'] = $label !== '' ? $label : ($resolved_service['name'] ?? '');
            $resolved_services[$resolved_id] = $resolved_service;
        }
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?></title>
    <meta name="description" content="<?php echo e($pageDescription); ?>">
    <meta name="robots" content="<?php echo e($robotsContent); ?>">
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
<?php if ($isChildrenProblemsPage): ?>
    <section class="border-b border-[#e6eef7] bg-[#e4f1fa] py-10 md:py-14">
        <div class="mx-auto max-w-6xl px-6 md:px-10">
            <div>
                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-[#1977b2]">Детское направление</p>
                <h1 class="mt-2 text-[2rem] font-bold leading-[1.05] text-[#0f2749] md:text-[2.8rem]">Детские проблемы</h1>
                <p class="mt-4 max-w-3xl text-[1rem] leading-relaxed text-[#0a293c] md:text-[1.06rem]">Выберите ситуацию, которая ближе всего к состоянию ребёнка. Каждая карточка ведёт на отдельную страницу с этапами маршрута восстановления и релевантными услугами.</p>
            </div>
        </div>
    </section>

    <?php
    $children_problems_section = new ProblemsGrid($children_problems, $brand_colors, [
        'show_title' => false,
        'show_cta' => true,
        'cta_url' => '/#contact',
        'cta_label' => 'Не нашли детскую ситуацию? Записаться на консультацию',
    ]);
    echo $children_problems_section->render();
    ?>

<?php elseif (!$problem): ?>
    <section class="mx-auto max-w-4xl px-6 py-20 md:px-10">
        <div class="rounded-3xl bg-white p-10 text-center shadow-[0_16px_40px_rgba(8,36,70,0.08)]">
            <i class="fa-solid fa-circle-question mb-4 text-5xl text-[#b0c8e0]" aria-hidden="true"></i>
            <h1 class="text-3xl font-bold text-[#0a293c]">Страница не найдена</h1>
            <p class="mt-3 text-[#0a293c]">Проверьте ссылку или перейдите на страницу с проблемами.</p>
            <a href="/problems" class="mt-7 inline-flex items-center gap-2 rounded-full bg-[#1977b2] px-6 py-3 text-sm font-semibold uppercase tracking-[0.08em] text-white hover:bg-[#16658f]">
                <i class="fa-solid fa-list" aria-hidden="true"></i> Ко всем ситуациям
            </a>
        </div>
    </section>
<?php else: ?>
    <section class="border-b border-[#e6eef7] bg-[#e4f1fa] py-10 md:py-14">
        <div class="mx-auto max-w-6xl px-6 md:px-10">
            <div>
                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-[#1977b2]">Ситуации и симптомы</p>
                <h1 class="mt-2 text-[2rem] font-bold leading-[1.05] text-[#0f2749] md:text-[2.8rem]"><?php echo e($problem['title']); ?></h1>
                <p class="mt-4 max-w-3xl text-[1rem] leading-relaxed text-[#0a293c] md:text-[1.06rem]"><?php echo e($problem['description']); ?></p>
                <p class="mt-4 max-w-3xl text-[0.96rem] leading-relaxed text-[#0a293c]">
                    Ниже мы собрали для Вас понятный маршрут, поэтапное описание и релевантные услуги, чтобы Вы сразу увидели логику восстановления.
                </p>
            </div>
        </div>
    </section>

    <section class="border-b border-[#e6eef7] bg-[#e4f1fa] py-10 md:py-14">
        <div class="mx-auto max-w-6xl px-6 md:px-10">
            <div class="grid gap-2.5 md:gap-3">
                <?php foreach (($problem['details_sections'] ?? []) as $index => $section): ?>
                    <?php
                    if (!is_array($section)) continue;
                    $sectionTitle = trim((string)($section['title'] ?? ''));
                    $sectionIntro = trim((string)($section['intro'] ?? ''));
                    $sectionItems = $section['items'] ?? [];
                    if ($sectionTitle === '' && (empty($sectionItems) || !is_array($sectionItems))) continue;
                    $sectionIcon = ['fa-user-doctor', 'fa-magnifying-glass', 'fa-clipboard-check', 'fa-kit-medical', 'fa-star'][$index] ?? 'fa-circle-info';
                    ?>
                    <details class="group rounded-[1.4rem] bg-white p-5 shadow-[0_12px_28px_rgba(10,43,80,0.06)]" data-problem-step="<?php echo e((string)$index); ?>"<?php echo $index < 5 ? ' open' : ''; ?>>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-left">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-[#e8f3fc] text-[#1977b2]">
                                    <i class="fa-solid <?php echo e($sectionIcon); ?> text-[0.95rem]" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <span class="block text-[0.98rem] font-bold text-[#0f2749] md:text-[1.05rem]"><?php echo e($sectionTitle); ?></span>
                                    <?php if ($sectionIntro !== ''): ?>
                                        <span class="mt-1 block max-w-3xl text-[0.9rem] leading-relaxed text-[#0a293c]"><?php echo e($sectionIntro); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#f0f7fd] text-[#1977b2] transition group-open:rotate-180">
                                <i class="fa-solid fa-chevron-down text-[0.76rem]" aria-hidden="true"></i>
                            </span>
                        </summary>
                        <?php if (!empty($sectionItems) && is_array($sectionItems)): ?>
                            <ul class="mt-4 space-y-2.5">
                                <?php foreach ($sectionItems as $sectionItem): ?>
                                    <li class="flex items-start gap-3 border-l-2 border-[#dbe8f3] pl-3 text-[0.95rem] leading-relaxed text-[#0a293c]">
                                        <i class="fa-solid fa-check mt-1 text-[0.75rem] text-[#1977b2]" aria-hidden="true"></i>
                                        <span><?php echo e(problem_list_text((string)$sectionItem)); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="border-b border-[#e6eef7] bg-[#e4f1fa] py-10 md:py-14">
        <div class="mx-auto max-w-6xl px-6 md:px-10">
            <div class="rounded-[2rem] bg-transparent p-0">
                <p class="text-[0.74rem] font-semibold uppercase tracking-[0.22em] text-[#1977b2]">Запись</p>
                <h2 class="mt-2 text-[1.45rem] font-bold leading-tight text-[#0f2749] md:text-[1.8rem]">Записаться на приём</h2>
                <p class="mt-3 max-w-2xl text-[0.98rem] leading-relaxed text-[#0a293c]">Оставьте заявку, и мы свяжемся с Вами, чтобы помочь выбрать правильный шаг и удобное время консультации.</p>
                <div class="mt-5 max-w-xl">
                    <?php echo bioinmed_render_callback_form([
                        'source_label' => 'Страница проблемы',
                        'submit_label' => 'Записаться на приём',
                    ]); ?>
                </div>
            </div>
        </div>
    </section>

    <section class="border-b border-[#e6eef7] bg-[#e4f1fa] py-10 md:py-14">
        <div class="mx-auto max-w-6xl px-6 md:px-10">
            <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-[0.74rem] font-semibold uppercase tracking-[0.22em] text-[#1977b2]">Релевантные услуги</p>
                    <h2 class="mt-2 text-[1.45rem] font-bold leading-tight text-[#0f2749] md:text-[1.8rem]">Что может помочь при этой ситуации</h2>
                </div>
                <p class="max-w-2xl text-[0.94rem] leading-relaxed text-[#0a293c]">Собрали услуги, которые чаще всего сочетаются в таком маршруте и помогают двигаться к результату поэтапно.</p>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <?php foreach ($resolved_services as $service): ?>
                    <?php
                    $serviceId = (string)($service['id'] ?? '');
                    $serviceLink = '/services/' . rawurlencode($serviceId);
                    $serviceName = trim((string)($service['name'] ?? ''));
                    $serviceSubtitle = trim((string)($service['subtitle'] ?? ''));
                    $serviceDescription = trim((string)($service['card_description'] ?? $service['description'] ?? ''));
                    $servicePrice = trim((string)($service['price'] ?? ''));
                    ?>
                    <a href="<?php echo e($serviceLink); ?>" class="group block rounded-[1.6rem] bg-white p-5 shadow-[0_10px_26px_rgba(10,43,80,0.06)] ring-1 ring-[#d9e7f2] transition hover:-translate-y-0.5 hover:shadow-[0_16px_34px_rgba(10,43,80,0.1)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1977b2] focus-visible:ring-offset-2 focus-visible:ring-offset-[#e4f1fa]">
                        <p class="text-[0.7rem] font-semibold uppercase tracking-[0.2em] text-[#1977b2]"><?php echo e($service['display_label'] ?? 'Услуга'); ?></p>
                        <h3 class="mt-2 text-[1.08rem] font-bold leading-tight text-[#0f2749]"><?php echo e($serviceName); ?></h3>
                        <?php if ($serviceSubtitle !== ''): ?>
                            <p class="mt-1 text-[0.84rem] font-semibold uppercase tracking-[0.08em] text-[#0a293c]"><?php echo e($serviceSubtitle); ?></p>
                        <?php endif; ?>
                        <p class="mt-3 text-[0.94rem] leading-relaxed text-[#0a293c]"><?php echo e($serviceDescription); ?></p>
                        <div class="mt-4 flex items-center justify-between gap-3">
                            <?php if ($servicePrice !== ''): ?>
                                <span class="text-[0.92rem] font-semibold text-[#1977b2]"><?php echo e($servicePrice); ?></span>
                            <?php endif; ?>
                            <span class="inline-flex items-center gap-2 rounded-full bg-[#1977b2] px-4 py-2.5 text-[0.92rem] font-semibold text-white shadow-[0_10px_24px_rgba(25,119,178,0.18)] transition group-hover:bg-[#16658f] group-hover:gap-2.5">
                                Подробнее
                                <i class="fa-solid fa-arrow-right text-[0.72rem]" aria-hidden="true"></i>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>
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

    (function() {
        const storageKey = <?php echo json_encode($problemDetailsStorageKey, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        const steps = Array.from(document.querySelectorAll('[data-problem-step]'));

        if (!steps.length) {
            return;
        }

        const safeParse = function(value) {
            if (!value) {
                return null;
            }

            try {
                const parsed = JSON.parse(value);
                return parsed && typeof parsed === 'object' ? parsed : null;
            } catch (error) {
                return null;
            }
        };

        let savedState = null;
        try {
            savedState = safeParse(window.localStorage.getItem(storageKey));
        } catch (error) {
            savedState = null;
        }

        steps.forEach(function(step, index) {
            const stepId = String(step.getAttribute('data-problem-step') || index);

            if (savedState && Object.prototype.hasOwnProperty.call(savedState, stepId)) {
                step.open = Boolean(savedState[stepId]);
            } else if (!step.hasAttribute('open') && index < 5) {
                step.open = true;
            }
        });

        const persistState = function() {
            const nextState = {};

            steps.forEach(function(step, index) {
                nextState[String(step.getAttribute('data-problem-step') || index)] = step.open;
            });

            try {
                window.localStorage.setItem(storageKey, JSON.stringify(nextState));
            } catch (error) {
                // Ignore storage write failures.
            }
        };

        steps.forEach(function(step) {
            step.addEventListener('toggle', persistState);
        });

        persistState();
    })();
</script>
</body>
</html>
