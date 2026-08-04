<?php
require_once __DIR__ . '/includes/pin_protection.php';
bioinmed_pin_require_access();


require_once 'config.php';
require_once 'includes/components/Components.php';
require_once 'includes/content/EditableLists.php';

$problemPage = bioinmed_read_json_file('pages/problem.json');
$problemMeta = is_array($problemPage['meta'] ?? null) ? $problemPage['meta'] : [];
$problemBreadcrumbs = is_array($problemPage['breadcrumbs'] ?? null) ? $problemPage['breadcrumbs'] : [];
$problemChildrenText = is_array($problemPage['children'] ?? null) ? $problemPage['children'] : [];
$problemNotFoundText = is_array($problemPage['not_found'] ?? null) ? $problemPage['not_found'] : [];
$problemDetailText = is_array($problemPage['problem'] ?? null) ? $problemPage['problem'] : [];
$problemAppointmentText = is_array($problemPage['appointment'] ?? null) ? $problemPage['appointment'] : [];
$problemServicesText = is_array($problemPage['services'] ?? null) ? $problemPage['services'] : [];

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
    ? (($problemMeta['children_title'] ?? '') . ' — ' . CLINIC_NAME)
    : ($problem ? ($problem['page_title'] ?? ($problem['title'] . ' — ' . CLINIC_NAME)) : (($problemMeta['not_found_title'] ?? '') . ' | ' . CLINIC_NAME));
$pageDescription = $isChildrenProblemsPage
    ? (string)($problemMeta['children_description'] ?? '')
    : ($problem ? ($problem['page_description'] ?? $problem['description']) : (string)($problemMeta['not_found_description'] ?? ''));
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
    ['name' => (string)($problemBreadcrumbs['home'] ?? ''), 'url' => '/'],
    ['name' => (string)($problemBreadcrumbs['problems'] ?? ''), 'url' => '/problems'],
    ['name' => $isChildrenProblemsPage ? (string)($problemBreadcrumbs['children'] ?? '') : ($problem['title'] ?? (string)($problemBreadcrumbs['default_problem'] ?? '')), 'url' => $canonicalUrl],
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
    <?php echo bioinmed_render_public_head_assets(); ?>
    <style>
        body { background: #e4f1fa; color: #0f2749; line-height: 1.72; }
        html { scroll-behavior: smooth; }
        .fade-up { opacity: 0; transform: translateY(22px); transition: opacity .55s ease, transform .55s ease; }
        .fade-up.visible { opacity: 1; transform: translateY(0); }
    </style>
    <?php echo bioinmed_uis_counter_head(); ?>
</head>
<body class="flex min-h-screen flex-col antialiased">
    <?php echo bioinmed_yandex_metrika_noscript(); ?>
<?php
$header = new Header($brand_colors);
echo $header->render();
?>

<main class="grow">
<?php if ($isChildrenProblemsPage): ?>
    <section class="border-b border-[#e6eef7] bg-[#e4f1fa] py-10 md:py-14">
        <div class="mx-auto max-w-6xl px-6 md:px-10">
            <div class="bioinmed-back-row"><?php echo bioinmed_render_back_button(['fallback' => '/problems']); ?></div>
            <div data-admin-block-root>
                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-[#1977b2]"<?php echo bioinmed_page_text_attr($problemPage, 'problem', 'children.eyebrow'); ?>><?php echo e($problemChildrenText['eyebrow'] ?? ''); ?></p>
                <h1 class="mt-2 text-[2rem] font-bold leading-[1.05] text-[#0f2749] md:text-[2.8rem]"<?php echo bioinmed_page_text_attr($problemPage, 'problem', 'children.heading'); ?>><?php echo e($problemChildrenText['heading'] ?? ''); ?></h1>
                <p class="mt-4 max-w-3xl text-[1rem] leading-relaxed text-[#0a293c] md:text-[1.06rem]"<?php echo bioinmed_page_text_attr($problemPage, 'problem', 'children.description'); ?>><?php echo e($problemChildrenText['description'] ?? ''); ?></p>
            </div>
        </div>
    </section>

    <?php
    $children_problems_section = new ProblemsGrid($children_problems, $brand_colors, [
        'show_title' => false,
        'show_cta' => true,
        'cta_url' => bioinmed_link('nav.contacts')['url'],
        'cta_label' => (string)($problemChildrenText['cta_label'] ?? ''),
    ]);
    echo $children_problems_section->render();
    ?>

<?php elseif (!$problem): ?>
    <section class="mx-auto max-w-4xl px-6 py-20 md:px-10">
        <div class="bioinmed-back-row"><?php echo bioinmed_render_back_button(['fallback' => '/problems']); ?></div>
        <div class="rounded-3xl bg-white p-10 text-center shadow-[0_16px_40px_rgba(8,36,70,0.08)]" data-admin-block-root>
            <i class="fa-solid fa-circle-question mb-4 text-5xl text-[#b0c8e0]" aria-hidden="true"></i>
            <h1 class="text-3xl font-bold text-[#0a293c]"<?php echo bioinmed_page_text_attr($problemPage, 'problem', 'not_found.title'); ?>><?php echo e($problemNotFoundText['title'] ?? ''); ?></h1>
            <p class="mt-3 text-[#0a293c]"<?php echo bioinmed_page_text_attr($problemPage, 'problem', 'not_found.text'); ?>><?php echo e($problemNotFoundText['text'] ?? ''); ?></p>
            <a href="/problems" class="mt-7 inline-flex items-center gap-2 rounded-full bg-[#1977b2] px-6 py-3 text-sm font-semibold uppercase tracking-[0.08em] text-white hover:bg-[#16658f]">
                <i class="fa-solid fa-list" aria-hidden="true"></i> <span<?php echo bioinmed_page_text_attr($problemPage, 'problem', 'not_found.button'); ?>><?php echo e($problemNotFoundText['button'] ?? ''); ?></span>
            </a>
        </div>
    </section>
<?php else: ?>
    <?php
    $problemSlug = trim((string)($problem['slug'] ?? ''));
    $problemTitleKey = 'problem.items.' . $problemSlug . '.title';
    $problemDescriptionKey = 'problem.items.' . $problemSlug . '.description';
    $problemTitleText = bioinmed_text($problemTitleKey, (string)($problem['title'] ?? ''));
    $problemDescriptionText = bioinmed_text($problemDescriptionKey, (string)($problem['description'] ?? ''));
    ?>
    <section class="border-b border-[#e6eef7] bg-[#e4f1fa] py-10 md:py-14">
        <div class="mx-auto max-w-6xl px-6 md:px-10">
            <div class="bioinmed-back-row"><?php echo bioinmed_render_back_button(['fallback' => '/problems']); ?></div>
            <div data-admin-block-root>
                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-[#1977b2]"<?php echo bioinmed_page_text_attr($problemPage, 'problem', 'problem.eyebrow'); ?>><?php echo e($problemDetailText['eyebrow'] ?? ''); ?></p>
                <h1 class="mt-2 text-[2rem] font-bold leading-[1.05] text-[#0f2749] md:text-[2.8rem]"<?php echo bioinmed_data_text_id($problemTitleKey); ?>><?php echo e($problemTitleText); ?></h1>
                <p class="mt-4 max-w-3xl text-[1rem] leading-relaxed text-[#0a293c] md:text-[1.06rem]"<?php echo bioinmed_data_text_id($problemDescriptionKey); ?>><?php echo e($problemDescriptionText); ?></p>
                <p class="mt-4 max-w-3xl text-[0.96rem] leading-relaxed text-[#0a293c]"<?php echo bioinmed_page_text_attr($problemPage, 'problem', 'problem.intro'); ?>>
                    <?php echo e($problemDetailText['intro'] ?? ''); ?>
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
                    $sectionKey = trim((string)($section['key'] ?? ('section_' . $index)));
                    $legacySectionBase = 'problem.details_sections.' . $sectionKey;
                    $sectionBasePath = 'details.' . $problemSlug . '.sections.' . $sectionKey;
                    $sectionTitle = (string)bioinmed_text($legacySectionBase . '.title', $sectionTitle);
                    $sectionIntro = (string)bioinmed_text($legacySectionBase . '.intro', $sectionIntro);
                    $sectionTitleNode = bioinmed_page_text_node($problemPage, 'problem', $sectionBasePath . '.title', $sectionTitle);
                    $sectionIntroNode = bioinmed_page_text_node($problemPage, 'problem', $sectionBasePath . '.intro', $sectionIntro);
                    $sectionItemFallback = [];
                    foreach ((is_array($sectionItems) ? $sectionItems : []) as $itemIndex => $sectionItemEntry) {
                        if (is_array($sectionItemEntry)) {
                            $sectionItemText = (string)($sectionItemEntry['text'] ?? '');
                            $sectionItemKey = trim((string)($sectionItemEntry['id'] ?? ('item_' . $itemIndex)));
                        } else {
                            $sectionItemText = (string)$sectionItemEntry;
                            $sectionItemKey = 'item_' . $itemIndex;
                        }
                        $sectionItemText = (string)bioinmed_text($legacySectionBase . '.items.' . $sectionItemKey, $sectionItemText);
                        $sectionItemFallback[] = [
                            'id' => $sectionItemKey,
                            'text' => $sectionItemText,
                            'icon' => 'fa-solid fa-check',
                        ];
                    }
                    $sectionListKey = 'problem.' . $problemSlug . '.details_sections.' . $sectionKey . '.items';
                    $sectionListItems = bioinmed_editable_list_items($problemPage, $sectionListKey, $sectionItemFallback, 'fa-solid fa-check');
                    ?>
                    <details class="group rounded-[1.4rem] bg-white p-5 shadow-[0_12px_28px_rgba(10,43,80,0.06)]" data-admin-block-root data-problem-step="<?php echo e((string)$index); ?>"<?php echo $index < 5 ? ' open' : ''; ?>>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-left">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-[#e8f3fc] text-[#1977b2]">
                                    <i class="fa-solid <?php echo e($sectionIcon); ?> text-[0.95rem]" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <span class="block text-[0.98rem] font-bold text-[#0f2749] md:text-[1.05rem]"<?php echo $sectionTitleNode['attr']; ?>><?php echo e($sectionTitleNode['value']); ?></span>
                                    <?php if ($sectionIntroNode['value'] !== ''): ?>
                                        <span class="mt-1 block max-w-3xl text-[0.9rem] leading-relaxed text-[#0a293c]"<?php echo $sectionIntroNode['attr']; ?>><?php echo e($sectionIntroNode['value']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#f0f7fd] text-[#1977b2] transition group-open:rotate-180">
                                <i class="fa-solid fa-chevron-down text-[0.76rem]" aria-hidden="true"></i>
                            </span>
                        </summary>
                            <ul class="mt-4 space-y-2.5"<?php echo bioinmed_editable_list_attrs('problem', $sectionListKey, $sectionTitleNode['value'], true); ?>>
                                <?php echo bioinmed_editable_list_toolbar(); ?>
                                <?php foreach ($sectionListItems as $sectionItem): ?>
                                    <li class="flex items-start gap-3 border-l-2 border-[#dbe8f3] pl-3 text-[0.95rem] leading-relaxed text-[#0a293c]<?php echo bioinmed_editable_list_item_class($sectionItem); ?>"<?php echo bioinmed_editable_list_item_attrs($sectionItem); ?>>
                                        <i class="<?php echo e($sectionItem['icon'] ?: 'fa-solid fa-check'); ?> mt-1 text-[0.75rem] text-[#1977b2]" data-admin-list-icon-view aria-hidden="true"></i>
                                        <span data-admin-list-text-view><?php echo e(problem_list_text((string)$sectionItem['text'])); ?></span>
                                        <?php echo bioinmed_editable_list_actions($sectionItem); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="border-b border-[#e6eef7] bg-[#e4f1fa] py-10 md:py-14">
        <div class="mx-auto max-w-6xl px-6 md:px-10">
            <div class="rounded-3xl border border-[#d7e6f3] bg-white p-7 shadow-[0_18px_42px_rgba(6,29,60,0.08)] md:p-9" data-admin-block-root>
                <?php
                $appointmentHeadingNode = bioinmed_page_text_node($problemPage, 'problem', 'appointment.heading', 'Запишитесь онлайн — прямо сейчас');
                $appointmentTextNode = bioinmed_page_text_node($problemPage, 'problem', 'appointment.text', (string)($problemAppointmentText['text'] ?? ''));
                $appointmentSubmitNode = bioinmed_page_text_node($problemPage, 'problem', 'appointment.submit_label', (string)($problemAppointmentText['submit_label'] ?? bioinmed_text('common.book_appointment')));
                $appointmentPhonePlaceholderNode = bioinmed_page_text_node($problemPage, 'problem', 'appointment.phone_placeholder', bioinmed_text('forms.phone.placeholder_default', 'Ваш телефон'));
                ?>
                <div class="grid gap-7 lg:grid-cols-[1.05fr_0.95fr] lg:items-start">
                    <div>
                        <h2 class="text-[1.35rem] font-bold leading-tight text-[#0f2749] md:text-[1.6rem]"<?php echo $appointmentHeadingNode['attr']; ?>><?php echo e($appointmentHeadingNode['value']); ?></h2>
                        <p class="mt-2.5 max-w-xl text-[0.94rem] leading-relaxed text-[#0a293c]"<?php echo $appointmentTextNode['attr']; ?>><?php echo e($appointmentTextNode['value']); ?></p>
                    </div>
                    <div class="w-full max-w-lg lg:ml-auto">
                        <?php echo bioinmed_render_callback_form([
                            'source_label' => (string)($problemAppointmentText['source_label'] ?? ''),
                            'submit_label' => $appointmentSubmitNode['value'],
                            'submit_label_attr' => $appointmentSubmitNode['attr'],
                            'phone_placeholder' => $appointmentPhonePlaceholderNode['value'],
                            'phone_placeholder_attr' => $appointmentPhonePlaceholderNode['attr'],
                        ]); ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="border-b border-[#e6eef7] bg-[#e4f1fa] py-10 md:py-14">
        <div class="mx-auto max-w-6xl px-6 md:px-10">
            <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between" data-admin-block-root>
                <div>
                    <p class="text-[0.74rem] font-semibold uppercase tracking-[0.22em] text-[#1977b2]"<?php echo bioinmed_page_text_attr($problemPage, 'problem', 'services.eyebrow'); ?>><?php echo e($problemServicesText['eyebrow'] ?? ''); ?></p>
                    <h2 class="mt-2 text-[1.45rem] font-bold leading-tight text-[#0f2749] md:text-[1.8rem]"<?php echo bioinmed_page_text_attr($problemPage, 'problem', 'services.title'); ?>><?php echo e($problemServicesText['title'] ?? ''); ?></h2>
                </div>
                <p class="max-w-2xl text-[0.94rem] leading-relaxed text-[#0a293c]"<?php echo bioinmed_page_text_attr($problemPage, 'problem', 'services.description'); ?>><?php echo e($problemServicesText['description'] ?? ''); ?></p>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <?php foreach ($resolved_services as $service): ?>
                    <?php
                    $serviceId = (string)($service['id'] ?? '');
                    $serviceNodeBase = 'services.items.' . $serviceId;
                    $serviceLink = '/services/' . rawurlencode($serviceId);
                    $serviceName = trim((string)($service['name'] ?? ''));
                    $serviceDescription = trim((string)($service['card_description'] ?? $service['description'] ?? ''));
                    $serviceActualPrice = bioinmed_service_actual_price_parts($service);
                    $servicePrice = trim((string)($serviceActualPrice['price'] ?? ''));
                    $serviceNameNode = bioinmed_page_text_node($problemPage, 'problem', $serviceNodeBase . '.name', $serviceName);
                    $serviceDescriptionNode = bioinmed_page_text_node($problemPage, 'problem', $serviceNodeBase . '.description', $serviceDescription);
                    $servicePriceNode = bioinmed_page_text_node($problemPage, 'problem', $serviceNodeBase . '.price', $servicePrice);
                    $servicePriceNode['value'] = $servicePrice;
                    ?>
                    <a href="<?php echo e($serviceLink); ?>" class="group flex h-full flex-col rounded-[1.6rem] bg-white p-5 shadow-[0_10px_26px_rgba(10,43,80,0.06)] ring-1 ring-[#d9e7f2] transition hover:-translate-y-0.5 hover:shadow-[0_16px_34px_rgba(10,43,80,0.1)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1977b2] focus-visible:ring-offset-2 focus-visible:ring-offset-[#e4f1fa]" data-admin-block-root>
                        <h3 class="text-[1.08rem] font-bold leading-tight text-[#0f2749]"<?php echo $serviceNameNode['attr']; ?>><?php echo e($serviceNameNode['value']); ?></h3>
                        <p class="mt-3 text-[0.94rem] leading-relaxed text-[#0a293c]"<?php echo $serviceDescriptionNode['attr']; ?>><?php echo e($serviceDescriptionNode['value']); ?></p>
                        <div class="mt-auto flex items-center justify-between gap-3 pt-4">
                            <?php if ($servicePriceNode['value'] !== ''): ?>
                                <span class="text-[1.02rem] font-semibold leading-none text-[#1977b2]"<?php echo $servicePriceNode['attr']; ?>><?php echo e($servicePriceNode['value']); ?></span>
                            <?php endif; ?>
                            <span class="inline-flex items-center gap-2 rounded-full bg-[#1977b2] px-4 py-2.5 text-[0.92rem] font-semibold text-white shadow-[0_10px_24px_rgba(25,119,178,0.18)] transition group-hover:bg-[#16658f] group-hover:gap-2.5">
                                <?php echo e(bioinmed_text($serviceNodeBase . '.cta', bioinmed_text('common.more_details'))); ?>
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
