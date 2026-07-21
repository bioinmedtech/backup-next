<?php
require_once __DIR__ . '/includes/pin_protection.php';
bioinmed_pin_require_access();


require_once 'config.php';
require_once 'includes/components/Components.php';
require_once 'includes/content/EditableLists.php';

$doctorPage = bioinmed_read_json_file('pages/doctor.json');
$doctorMeta = is_array($doctorPage['meta'] ?? null) ? $doctorPage['meta'] : [];
$doctorBreadcrumbs = is_array($doctorPage['breadcrumbs'] ?? null) ? $doctorPage['breadcrumbs'] : [];
$doctorNotFound = is_array($doctorPage['not_found'] ?? null) ? $doctorPage['not_found'] : [];
$doctorHero = is_array($doctorPage['hero'] ?? null) ? $doctorPage['hero'] : [];
$doctorCta = is_array($doctorPage['cta'] ?? null) ? $doctorPage['cta'] : [];
$doctorSectionsText = is_array($doctorPage['sections'] ?? null) ? $doctorPage['sections'] : [];
$doctorServicesText = is_array($doctorPage['services'] ?? null) ? $doctorPage['services'] : [];
$doctorFinalCta = is_array($doctorPage['final_cta'] ?? null) ? $doctorPage['final_cta'] : [];
$doctorEducationalRole = is_array($doctorSectionsText['educational_role'] ?? null) ? $doctorSectionsText['educational_role'] : [];
$doctorTrustSection = is_array($doctorSectionsText['trust'] ?? null) ? $doctorSectionsText['trust'] : [];
$doctorHeroSignNode = bioinmed_page_text_node($doctorPage, 'doctor', 'hero.sign', (string)($doctorHero['sign'] ?? ''));

$siteUrl = rtrim(CLINIC_SITE_URL, '/');
$iconPath = CLINIC_ICON_PATH;
$iconUrl = $siteUrl . $iconPath;

$slug = isset($_GET['slug']) ? trim((string)$_GET['slug']) : '';
$doctor = null;

foreach ($doctors as $item) {
    if (isset($item['slug']) && $item['slug'] === $slug && (!array_key_exists('has_profile', $item) || $item['has_profile'] !== false)) {
        $doctor = $item;
        break;
    }
}

if ($doctor === null) {
    http_response_code(404);
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$pageTitle    = $doctor ? e($doctor['name']) . (string)($doctorMeta['title_suffix'] ?? '') . CLINIC_NAME : ((string)($doctorMeta['not_found_title'] ?? '') . ' | ' . CLINIC_NAME);
$pageDesc     = $doctor
    ? e($doctor['name']) . ' — ' . e($doctor['specialty'] ?? '') . (string)($doctorMeta['description_suffix'] ?? '')
    : ($doctorMeta['not_found_description'] ?? '');
$canonicalUrl = $doctor
    ? $siteUrl . '/doctors/' . rawurlencode((string)$slug)
    : $siteUrl . '/doctors';
$robotsContent = $doctor
    ? 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'
    : 'noindex,follow';
$phone1       = CLINIC_PHONE;
$phone1link   = preg_replace('/\D/', '', $phone1);
$phone2       = defined('CLINIC_PHONE_2') ? CLINIC_PHONE_2 : '';
$phone2link   = $phone2 ? preg_replace('/\D/', '', $phone2) : '';
$doctorImagePath = $doctor && !empty($doctor['image'])
    ? bioinmed_preferred_image_asset_path('/public/images/team/' . $doctor['image'])
    : '';
$doctorProjectTitle = trim((string)($doctor['project_title'] ?? ''));
$doctorEditablePrefix = 'doctor.' . (string)($doctor['slug'] ?? ($slug ?: 'default'));
$socialImageUrl = $doctor && !empty($doctor['image'])
    ? bioinmed_absolute_url($doctorImagePath)
    : bioinmed_default_social_image_url();
$organizationStructuredData = bioinmed_medical_organization_schema();
$breadcrumbStructuredData = bioinmed_breadcrumb_schema([
    ['name' => (string)($doctorBreadcrumbs['home'] ?? ''), 'url' => '/'],
    ['name' => (string)($doctorBreadcrumbs['team'] ?? ''), 'url' => '/doctors'],
]);
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="<?php echo $pageDesc; ?>">
    <meta name="robots" content="<?php echo $robotsContent; ?>">
    <link rel="canonical" href="<?php echo e($canonicalUrl); ?>">
    <meta name="theme-color" content="#1977b2">
    <?php echo bioinmed_render_social_meta($pageTitle, $pageDesc, $canonicalUrl, [
        'type' => 'profile',
        'image' => $socialImageUrl,
        'image_alt' => $doctor['name'] ?? (CLINIC_NAME . (string)($doctorMeta['social_image_alt_suffix'] ?? '')),
    ]); ?>
    <?php echo bioinmed_render_favicon_links($iconPath); ?>
    <?php if ($doctor): ?>
    <script type="application/ld+json"><?php echo json_encode([
        '@context'  => 'https://schema.org',
        '@type'     => 'Physician',
        'name'      => $doctor['name'],
        'jobTitle'  => $doctor['title'] ?? '',
        'description' => $doctor['bio'] ?? '',
        'worksFor'  => ['@type' => 'MedicalOrganization', 'name' => CLINIC_NAME, 'url' => CLINIC_SITE_URL],
        'image'     => bioinmed_absolute_url($doctorImagePath),
        'url'       => $canonicalUrl,
        'mainEntityOfPage' => $canonicalUrl,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <?php endif; ?>
    <script type="application/ld+json"><?php echo json_encode($organizationStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script type="application/ld+json"><?php echo json_encode($breadcrumbStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <?php echo bioinmed_render_public_head_assets(); ?>
    <style>
        html {
            font-size: clamp(17px, 0.5vw + 15px, 19px);
        }

        body {
            line-height: 1.72;
        }

        .bioinmed-editable-list-item-hidden,
        .bioinmed-editable-list-toolbar,
        .bioinmed-editable-list-actions {
            display: none !important;
        }

        .fade-up { opacity: 0; transform: translateY(22px); transition: opacity .55s ease, transform .55s ease; }
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

<?php if (!$doctor): ?>
<main class="mx-auto max-w-4xl grow px-6 py-20 md:px-10">
    <div class="rounded-3xl border border-[#dbe8f3] bg-white p-10 text-center shadow-[0_16px_40px_rgba(8,36,70,0.08)]" data-admin-block-root>
        <i class="fa-solid fa-user-slash mb-4 text-5xl text-[#b0c8e0]"></i>
        <h1 class="text-3xl font-bold text-[#0a293c]"<?php echo bioinmed_page_text_attr($doctorPage, 'doctor', 'not_found.title'); ?>><?php echo e($doctorNotFound['title'] ?? ''); ?></h1>
        <p class="mt-3 text-[#0a293c]"<?php echo bioinmed_page_text_attr($doctorPage, 'doctor', 'not_found.text'); ?>><?php echo e($doctorNotFound['text'] ?? ''); ?></p>
        <a href="/" class="mt-7 inline-flex items-center gap-2 rounded-full bg-[#1977b2] px-6 py-3 text-sm font-semibold uppercase tracking-[0.08em] text-white hover:bg-[#16658f]"<?php echo bioinmed_page_text_attr($doctorPage, 'doctor', 'not_found.home_button'); ?>>
            <i class="fa-solid fa-house"></i> <?php echo e($doctorNotFound['home_button'] ?? ''); ?>
        </a>
    </div>
</main>

<?php else: ?>
<main class="grow">

    <!-- ===== HERO ===== -->
    <section class="bg-[#e4f1fa] py-10 md:py-14">
        <div class="mx-auto max-w-6xl px-6 md:px-10">

            <!-- breadcrumb -->
            <nav class="mb-6 flex items-center gap-2 text-xs text-[#7a9cc4]">
                <a href="/" class="hover:text-[#1977b2]"<?php echo bioinmed_page_text_attr($doctorPage, 'doctor', 'breadcrumbs.home'); ?>><?php echo e($doctorBreadcrumbs['home'] ?? ''); ?></a>
                <i class="fa-solid fa-chevron-right text-[0.6rem]"></i>
                <a href="/doctors" class="hover:text-[#1977b2]"<?php echo bioinmed_page_text_attr($doctorPage, 'doctor', 'breadcrumbs.team'); ?>><?php echo e($doctorBreadcrumbs['team'] ?? ''); ?></a>
            </nav>

            <div class="grid items-start gap-8 md:grid-cols-[380px_1fr] lg:grid-cols-[460px_1fr]">

                <!-- photo -->
                <div class="fade-up w-full max-w-[480px]" data-admin-block-root>
                    <div class="aspect-square overflow-hidden rounded-3xl">
                            <img src="<?php echo e($doctorImagePath); ?>"
                             alt="<?php echo e($doctor['name']); ?>"
                             class="h-full w-full rounded-3xl object-cover object-top"
                             loading="eager"
                             fetchpriority="high"
                             decoding="async"
                             onerror="this.src='/public/images/placeholder.jpg'">
                    </div>
                    <?php if (!empty($doctor['hero_tagline'])): ?>
                    <p class="caveat-reveal mt-4 max-w-none text-[#0a293c]" style="font-family:'Caveat',cursive;font-size:clamp(1.35rem,4vw,1.8rem);line-height:1.22;font-weight:700;">
                        <?php echo e($doctor['hero_tagline']); ?>
                    </p>
                    <p class="caveat-reveal mt-2 text-[1.08rem] font-semibold tracking-[0.04em] text-[#4a6f9c]" style="font-family:'Caveat',cursive;font-weight:700;"<?php echo $doctorHeroSignNode['attr']; ?>><?php echo e($doctorHeroSignNode['value']); ?></p>
                    <?php endif; ?>
                </div>

                <!-- info -->
                <div class="fade-up" style="transition-delay:.08s">
                    <div class="mt-6">
                        <?php echo bioinmed_render_chief_doctor_summary($doctor, [
                            'show_cta' => false,
                            'surface_class' => 'space-y-4',
                            'text_prefix' => 'pages.doctor.doctor_items.' . ($doctor['slug'] ?? 'doctor') . '.summary',
                            'text_values' => bioinmed_json_get(
                                $doctorPage,
                                'doctor_items.' . ($doctor['slug'] ?? 'doctor') . '.summary',
                                []
                            ),
                            'editable_list_key' => $doctorEditablePrefix . '.summary.educational_role',
                        ]); ?>
                    </div>

                    <div class="mt-6 lg:hidden">
                        <div id="book-mobile" class="fade-up rounded-3xl border border-[#d9e7f3] bg-white p-6 shadow-[0_12px_30px_rgba(8,36,70,0.10)]" data-admin-block-root>
                            <h3 class="mt-2 text-[1.3rem] font-bold leading-tight text-[#0a293c]"<?php echo bioinmed_data_text_id('common.book_appointment'); ?>><?php echo e(bioinmed_text('common.book_appointment')); ?></h3>
                            <p class="mt-2 text-[0.96rem] leading-relaxed text-[#0a293c]"<?php echo bioinmed_data_text_id('common.callback_15_min'); ?>>
                                <?php echo e(bioinmed_text('common.callback_15_min')); ?>
                            </p>

                            <div class="mt-5 space-y-3">
                                <?php echo bioinmed_render_callback_form([
                                    'source_label' => ($doctor['name'] ?? '') . (string)($doctorCta['mobile_source_suffix'] ?? ''),
                                    'submit_label' => bioinmed_text('common.request_callback'),
                                    'button_class' => 'inline-flex w-full items-center justify-center rounded-full bg-[#1977b2] px-5 py-2.5 text-[0.98rem] font-semibold text-white transition hover:bg-[#16658f] disabled:cursor-not-allowed disabled:bg-[#a7d7e9] disabled:text-white/90',
                                ]); ?>
                            </div>

                            <div class="my-5 flex items-center gap-3">
                                <div class="h-px grow bg-[#e2ecf5]"></div>
                                <span class="text-xs text-[#0a293c]"<?php echo bioinmed_page_text_attr($doctorPage, 'doctor', 'cta.call_direct'); ?>><?php echo e($doctorCta['call_direct'] ?? ''); ?></span>
                                <div class="h-px grow bg-[#e2ecf5]"></div>
                            </div>

                            <a href="tel:<?php echo $phone1link; ?>"
                                      class="flex items-center justify-center gap-2 rounded-full border-2 border-[#1977b2] px-6 py-3 text-sm font-bold text-[#1977b2] hover:bg-[#f0f8ff]">
                                <i class="fa-solid fa-phone-volume"></i> <?php echo e($phone1); ?>
                            </a>
                            <?php if ($phone2): ?>
                            <a href="tel:<?php echo $phone2link; ?>"
                               class="mt-2 flex items-center justify-center gap-2 rounded-full border border-[#d6e4f2] px-6 py-2.5 text-sm font-semibold text-[#0a293c] hover:border-[#1977b2] hover:text-[#1977b2]">
                                <i class="fa-solid fa-phone text-xs"></i> <?php echo e($phone2); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== DETAILS ===== -->
    <section class="mx-auto max-w-6xl px-6 py-12 md:px-10 md:py-16">
        <div class="grid gap-8 lg:grid-cols-[1fr_360px]">

            <!-- left column: rich content -->
            <?php $customSections = $doctor['custom_sections'] ?? []; ?>
            <?php $hideStandardSections = !empty($doctor['hide_standard_sections']); ?>
            <?php
            if (!empty($customSections) && is_array($customSections)) {
                $practiceSectionIndex = null;
                $authorMethodSectionIndex = null;
                foreach ($customSections as $sectionIndex => $sectionData) {
                    $sectionKey = trim((string)($sectionData['key'] ?? ''));
                    if ($sectionKey === 'author-method-omolozhenie') {
                        $authorMethodSectionIndex = $sectionIndex;
                    }
                    if ($sectionKey === 'treatment-practice-directions') {
                        $practiceSectionIndex = $sectionIndex;
                    }
                }
                $authorMethodSection = null;
                if ($authorMethodSectionIndex !== null) {
                    $authorMethodSection = $customSections[$authorMethodSectionIndex];
                    unset($customSections[$authorMethodSectionIndex]);
                }
                if ($practiceSectionIndex !== null) {
                    $practiceSection = $customSections[$practiceSectionIndex];
                    unset($customSections[$practiceSectionIndex]);
                    if ($authorMethodSection !== null) {
                        array_unshift($customSections, $practiceSection);
                        array_unshift($customSections, $authorMethodSection);
                    } else {
                        array_unshift($customSections, $practiceSection);
                    }
                } elseif ($authorMethodSection !== null) {
                    array_unshift($customSections, $authorMethodSection);
                }
            }
            ?>
            <?php $customSectionKeys = !empty($customSections) && is_array($customSections) ? array_values(array_filter(array_map(static function ($section) {
                return trim((string)($section['key'] ?? ''));
            }, $customSections))) : []; ?>
            <div class="space-y-6">

                <?php if (!$hideStandardSections && !empty($doctor['specializations']) && is_array($doctor['specializations'])): ?>
                <details class="doctor-section-toggle fade-up group rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]" data-admin-block-root data-doctor-toggle="specializations">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-7 text-left marker:hidden">
                        <span class="flex items-center gap-2.5 text-[1.1rem] font-bold text-[#0a293c]">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#1977b2]"><i class="fa-solid fa-list-check text-sm"></i></span>
                            <span<?php echo bioinmed_page_text_attr($doctorPage, 'doctor', 'sections.specializations_title'); ?>><?php echo e($doctorSectionsText['specializations_title'] ?? ''); ?></span>
                        </span>
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[#c9dff1] bg-white text-[#0a293c]">
                            <i class="fa-solid fa-chevron-down text-[0.82rem] transition group-open:rotate-180" aria-hidden="true"></i>
                        </span>
                    </summary>
                    <div class="px-7 pb-7">
                        <?php bioinmed_render_editable_icon_list($doctorPage, 'doctor', $doctorEditablePrefix . '.specializations', bioinmed_editable_list_resolve_texts($doctorPage, 'doctor_items.' . ($doctor['slug'] ?? 'doctor') . '.specializations', $doctor['specializations']), 'Специализации', 'space-y-3', 'flex items-start gap-3 text-sm leading-snug text-[#0a293c]', 'fa-solid fa-circle'); ?>
                    </div>
                </details>
                <?php endif; ?>

                <?php if (!$hideStandardSections && !empty($doctor['focus']) && is_array($doctor['focus'])): ?>
                <details class="doctor-section-toggle fade-up group rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]" data-admin-block-root data-doctor-toggle="focus">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-7 text-left marker:hidden">
                        <span class="flex items-center gap-2.5 text-xl font-bold text-[#0a293c]">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#1977b2]"><i class="fa-solid fa-bullseye text-sm"></i></span>
                            <span<?php echo bioinmed_page_text_attr($doctorPage, 'doctor', 'sections.focus_title'); ?>><?php echo e($doctorSectionsText['focus_title'] ?? ''); ?></span>
                        </span>
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[#c9dff1] bg-white text-[#0a293c]">
                            <i class="fa-solid fa-chevron-down text-[0.82rem] transition group-open:rotate-180" aria-hidden="true"></i>
                        </span>
                    </summary>
                    <div class="px-7 pb-7">
                        <?php bioinmed_render_editable_icon_list($doctorPage, 'doctor', $doctorEditablePrefix . '.focus', bioinmed_editable_list_resolve_texts($doctorPage, 'doctor_items.' . ($doctor['slug'] ?? 'doctor') . '.focus', $doctor['focus']), 'Основные направления', 'grid gap-3 sm:grid-cols-2', 'flex items-start gap-3 rounded-xl border border-[#e4edf6] bg-white p-3 text-[0.96rem] leading-snug text-[#0a293c]'); ?>
                    </div>
                </details>
                <?php endif; ?>

                <?php if (!empty($customSections) && is_array($customSections)): ?>
                <?php foreach ($customSections as $section):
                    $sectionKey = trim((string)($section['key'] ?? ''));
                    $sectionTitle = trim((string)($section['title'] ?? ''));
                    $sectionIcon = trim((string)($section['icon'] ?? 'fa-solid fa-circle-info'));
                    $sectionCardClasses = trim((string)($section['card_classes'] ?? 'rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]'));
                    $sectionIconBgClasses = trim((string)($section['icon_bg_classes'] ?? 'bg-[#e8f3fc] text-[#1977b2]'));
                    $sectionIntro = trim((string)($section['intro'] ?? ''));
                    $sectionText = trim((string)($section['text'] ?? ''));
                    $sectionLinkLabel = trim((string)($section['link_label'] ?? ''));
                    $sectionLinkHref = trim((string)($section['link_href'] ?? ''));
                    $sectionItems = $section['items'] ?? [];
                    $sectionSubsections = $section['subsections'] ?? [];
                    if ($sectionKey === '' || $sectionTitle === '') {
                        continue;
                    }
                    $sectionBase = 'custom_sections.' . ($doctor['slug'] ?? 'doctor') . '.' . $sectionKey;
                    $sectionTitleNode = bioinmed_page_text_node($doctorPage, 'doctor', $sectionBase . '.title', $sectionTitle);
                    $sectionIntroNode = bioinmed_page_text_node($doctorPage, 'doctor', $sectionBase . '.intro', $sectionIntro);
                    $sectionTextNode = bioinmed_page_text_node($doctorPage, 'doctor', $sectionBase . '.text', $sectionText);
                    $sectionLinkLabelNode = bioinmed_page_text_node($doctorPage, 'doctor', $sectionBase . '.link_label', $sectionLinkLabel);
                ?>
                <details id="doctor-section-<?php echo e($sectionKey); ?>" class="doctor-section-toggle fade-up group <?php echo e($sectionCardClasses); ?>" data-admin-block-root data-doctor-toggle="<?php echo e($sectionKey); ?>">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-7 text-left marker:hidden">
                        <span class="flex items-center gap-2.5 text-xl font-bold text-[#0a293c]">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full <?php echo e($sectionIconBgClasses); ?>"><i class="<?php echo e($sectionIcon); ?> text-sm"></i></span>
                            <span<?php echo $sectionTitleNode['attr']; ?>><?php echo e($sectionTitleNode['value']); ?></span>
                        </span>
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[#c9dff1] bg-white text-[#0a293c]">
                            <i class="fa-solid fa-chevron-down text-[0.82rem] transition group-open:rotate-180" aria-hidden="true"></i>
                        </span>
                    </summary>
                    <div class="space-y-5 px-7 pb-7">
                        <?php if ($sectionIntro !== ''): ?>
                        <p class="text-[0.96rem] leading-relaxed text-[#0a293c]"<?php echo $sectionIntroNode['attr']; ?>><?php echo e($sectionIntroNode['value']); ?></p>
                        <?php endif; ?>

                        <?php if ($sectionText !== ''): ?>
                        <p class="text-[0.96rem] leading-relaxed text-[#0a293c]"<?php echo $sectionTextNode['attr']; ?>><?php echo e($sectionTextNode['value']); ?></p>
                        <?php endif; ?>

                        <?php if ($sectionLinkLabel !== '' && $sectionLinkHref !== ''): ?>
                        <div>
                            <a href="<?php echo e($sectionLinkHref); ?>" class="inline-flex items-center gap-2 rounded-full bg-[#1977b2] px-4 py-2.5 text-[0.88rem] font-semibold text-white transition hover:bg-[#16658f]">
                                <span<?php echo $sectionLinkLabelNode['attr']; ?>><?php echo e($sectionLinkLabelNode['value']); ?></span>
                                <i class="fa-solid fa-arrow-right text-[0.74rem]" aria-hidden="true"></i>
                            </a>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($sectionItems) && is_array($sectionItems)): ?>
                        <?php bioinmed_render_editable_icon_list($doctorPage, 'doctor', $doctorEditablePrefix . '.custom.' . $sectionKey . '.items', bioinmed_editable_list_resolve_texts($doctorPage, $sectionBase . '.items', $sectionItems), $sectionTitle, 'space-y-3 text-[0.96rem] leading-snug text-[#0a293c]', 'flex items-start gap-3', 'fa-solid fa-circle'); ?>
                        <?php endif; ?>

                        <?php if (!empty($sectionSubsections) && is_array($sectionSubsections)): ?>
                        <div class="space-y-5">
                            <?php foreach ($sectionSubsections as $subIndex => $subsection):
                                $subTitle = trim((string)($subsection['title'] ?? ''));
                                $subItems = $subsection['items'] ?? [];
                                if ($subTitle === '' && empty($subItems)) {
                                    continue;
                                }
                                $subBase = $sectionBase . '.subsections.' . $subIndex;
                                $subTitleNode = bioinmed_page_text_node($doctorPage, 'doctor', $subBase . '.title', $subTitle);
                            ?>
                            <div class="rounded-2xl border border-[#e4edf6] bg-white p-4 md:p-5">
                                <?php if ($subTitle !== ''): ?>
                                <h3 class="text-[0.98rem] font-semibold text-[#0a293c] md:text-[1.03rem]"<?php echo $subTitleNode['attr']; ?>><?php echo e($subTitleNode['value']); ?></h3>
                                <?php endif; ?>
                                <?php if (!empty($subItems) && is_array($subItems)): ?>
                                <?php bioinmed_render_editable_icon_list($doctorPage, 'doctor', $doctorEditablePrefix . '.custom.' . $sectionKey . '.subsections.' . $subIndex . '.items', bioinmed_editable_list_resolve_texts($doctorPage, $subBase . '.items', is_array($subItems) ? $subItems : []), $subTitle !== '' ? $subTitle : $sectionTitle, 'mt-3 space-y-3 text-[0.96rem] leading-snug text-[#0a293c]', 'flex items-start gap-3', 'fa-solid fa-circle'); ?>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </details>
                <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!in_array('education', $customSectionKeys, true) && !empty($doctor['education'])): ?>
                <details class="doctor-section-toggle fade-up group rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]" data-admin-block-root data-doctor-toggle="education">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-7 text-left marker:hidden">
                        <span class="flex items-center gap-2.5 text-xl font-bold text-[#0a293c]">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#1977b2]" aria-hidden="true">
                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                    <path d="M3 21h18" />
                                    <path d="M6 21V8l6-3 6 3v13" />
                                    <path d="M9 21v-8h6v8" />
                                    <path d="M12 5V3" />
                                </svg>
                            </span>
                            <span<?php echo bioinmed_page_text_attr($doctorPage, 'doctor', 'sections.education_title'); ?>><?php echo e($doctorSectionsText['education_title'] ?? ''); ?></span>
                        </span>
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[#c9dff1] bg-white text-[#0a293c]">
                            <i class="fa-solid fa-chevron-down text-[0.82rem] transition group-open:rotate-180" aria-hidden="true"></i>
                        </span>
                    </summary>
                    <div class="px-7 pb-7">
                        <?php $educationNode = bioinmed_page_text_node($doctorPage, 'doctor', 'doctor_items.' . ($doctor['slug'] ?? 'doctor') . '.education', (string)($doctor['education'] ?? '')); ?>
                        <p class="text-[0.96rem] leading-relaxed text-[#0a293c]"<?php echo $educationNode['attr']; ?>><?php echo e($educationNode['value']); ?></p>
                    </div>
                </details>
                <?php endif; ?>

                <?php if (!in_array('trust', $customSectionKeys, true)): ?>
                <details class="doctor-section-toggle fade-up group rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]" data-admin-block-root data-doctor-toggle="trust">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-7 text-left marker:hidden">
                        <span class="flex items-center gap-2.5 text-xl font-bold text-[#0a293c]">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#1977b2]"><i class="fa-solid fa-shield-halved text-sm"></i></span>
                            <span<?php echo bioinmed_page_text_attr($doctorPage, 'doctor', 'sections.trust.title'); ?>><?php echo e($doctorTrustSection['title'] ?? ''); ?></span>
                        </span>
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[#c9dff1] bg-white text-[#0a293c]">
                            <i class="fa-solid fa-chevron-down text-[0.82rem] transition group-open:rotate-180" aria-hidden="true"></i>
                        </span>
                    </summary>
                    <div class="px-7 pb-7">
                        <?php bioinmed_render_editable_icon_list($doctorPage, 'doctor', $doctorEditablePrefix . '.trust', is_array($doctorTrustSection['items'] ?? null) ? $doctorTrustSection['items'] : [], 'Почему можно доверять', 'space-y-3 text-[0.96rem] text-[#0a293c]', 'flex items-start gap-3'); ?>
                    </div>
                </details>
                <?php endif; ?>
            </div>

            <!-- right column: sticky CTA -->
            <div class="hidden self-stretch lg:block">
                <div id="book" class="sticky top-24 rounded-3xl border border-[#d9e7f3] bg-white p-6 shadow-[0_12px_30px_rgba(8,36,70,0.10)]" data-admin-block-root>
                    <h3 class="mt-2 text-[1.3rem] font-bold leading-tight text-[#0a293c]"><?php echo e(bioinmed_text('common.book_appointment')); ?></h3>
                    <p class="mt-2 text-[0.96rem] leading-relaxed text-[#0a293c]">
                        <?php echo e(bioinmed_text('common.callback_15_min')); ?>
                    </p>

                    <div class="mt-5 space-y-3">
                        <?php echo bioinmed_render_callback_form([
                            'source_label' => ($doctor['name'] ?? '') . (string)($doctorCta['sticky_source_suffix'] ?? ''),
                            'submit_label' => bioinmed_text('common.request_callback'),
                            'button_class' => 'inline-flex w-full items-center justify-center rounded-full bg-[#1977b2] px-5 py-2.5 text-[0.98rem] font-semibold text-white transition hover:bg-[#16658f] disabled:cursor-not-allowed disabled:bg-[#a7d7e9] disabled:text-white/90',
                        ]); ?>
                    </div>

                    <!-- divider -->
                    <div class="my-5 flex items-center gap-3">
                        <div class="h-px grow bg-[#e2ecf5]"></div>
                        <span class="text-xs text-[#0a293c]"<?php echo bioinmed_page_text_attr($doctorPage, 'doctor', 'cta.call_direct'); ?>><?php echo e($doctorCta['call_direct'] ?? ''); ?></span>
                        <div class="h-px grow bg-[#e2ecf5]"></div>
                    </div>

                    <a href="tel:<?php echo $phone1link; ?>"
                              class="flex items-center justify-center gap-2 rounded-full border-2 border-[#1977b2] px-6 py-3 text-sm font-bold text-[#1977b2] hover:bg-[#f0f8ff]">
                        <i class="fa-solid fa-phone-volume"></i> <?php echo e($phone1); ?>
                    </a>
                    <?php if ($phone2): ?>
                    <a href="tel:<?php echo $phone2link; ?>"
                       class="mt-2 flex items-center justify-center gap-2 rounded-full border border-[#d6e4f2] px-6 py-2.5 text-sm font-semibold text-[#0a293c] hover:border-[#1977b2] hover:text-[#1977b2]">
                        <i class="fa-solid fa-phone text-xs"></i> <?php echo e($phone2); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== SERVICES ===== -->
    <?php
    $doctorServiceIds = $doctor['services'] ?? [];
    $doctorServices = [];
    $servicesMap = [];
    foreach ($services as $srv) {
        $sid = (string)($srv['id'] ?? '');
        if ($sid !== '') {
            $servicesMap[$sid] = $srv;
        }
    }

    if (!empty($doctorServiceIds)) {
        foreach ($doctorServiceIds as $sid) {
            $resolvedSid = isset($service_aliases[$sid]) ? (string)$service_aliases[$sid] : (string)$sid;
            if (isset($servicesMap[$resolvedSid])) {
                $doctorServices[$resolvedSid] = $servicesMap[$resolvedSid];
            }
        }
    }

    // Fallback: if explicit mapping is missing or too short, auto-pick by doctor profile.
    if (count($doctorServices) < 4) {
        $doctorProfileText = mb_strtolower(
            trim((
                ($doctor['title'] ?? '') . ' ' .
                ($doctor['specialty'] ?? '') . ' ' .
                ($doctor['bio'] ?? '') . ' ' .
                implode(' ', $doctor['specializations'] ?? []) . ' ' .
                implode(' ', $doctor['focus'] ?? [])
            )),
            'UTF-8'
        );

        $preferredCategories = [];
        if (preg_match('/психолог|психотерап|тревог|паник|эмоцион/u', $doctorProfileText)) {
            $preferredCategories[] = 'psychology';
        }
        if (preg_match('/остеопат|невролог|мануал|массаж|опорно-двиг/u', $doctorProfileText)) {
            $preferredCategories[] = 'osteopathy';
            $preferredCategories[] = 'manual_therapy';
            $preferredCategories[] = 'physiotherapy';
        }
        if (preg_match('/рефлекс|игло|акупункт|гинек|акушер/u', $doctorProfileText)) {
            $preferredCategories[] = 'reflexotherapy';
            $preferredCategories[] = 'injection_therapy';
        }
        if (preg_match('/главн|костромин/u', $doctorProfileText)) {
            $preferredCategories[] = 'chief_doctor';
            $preferredCategories[] = 'diagnostics';
        }
        if (preg_match('/афк|реабил|двигатель/u', $doctorProfileText)) {
            $preferredCategories[] = 'musculoskeletal';
            $preferredCategories[] = 'physiotherapy';
            $preferredCategories[] = 'taping';
        }

        $preferredCategories = array_values(array_unique($preferredCategories));

        if (!empty($preferredCategories)) {
            foreach ($services as $srv) {
                $sid = (string)($srv['id'] ?? '');
                $cat = (string)($srv['category'] ?? '');
                if ($sid === '' || isset($doctorServices[$sid])) {
                    continue;
                }
                if (in_array($cat, $preferredCategories, true)) {
                    $doctorServices[$sid] = $srv;
                }
                if (count($doctorServices) >= 6) {
                    break;
                }
            }
        }
    }

    if (count($doctorServices) < 4) {
        foreach ($services as $srv) {
            $sid = (string)($srv['id'] ?? '');
            if ($sid === '' || isset($doctorServices[$sid])) {
                continue;
            }
            $doctorServices[$sid] = $srv;
            if (count($doctorServices) >= 6) {
                break;
            }
        }
    }

    $doctorServices = array_values($doctorServices);

    // Prioritize diagnostics-related services first on doctor profile pages.
    $diagnosticServices = [];
    $otherServices = [];
    foreach ($doctorServices as $serviceItem) {
        $serviceCategory = trim((string)($serviceItem['category'] ?? ''));
        $serviceName = mb_strtolower(trim((string)($serviceItem['name'] ?? '')), 'UTF-8');
        $serviceId = trim((string)($serviceItem['id'] ?? ''));

        $isDiagnostics = $serviceCategory === 'diagnostics'
            || $serviceId === 'hobilect-diagnostics'
            || mb_strpos($serviceName, 'диагност') !== false;

        if ($isDiagnostics) {
            $diagnosticServices[] = $serviceItem;
        } else {
            $otherServices[] = $serviceItem;
        }
    }
    $doctorServices = array_merge($diagnosticServices, $otherServices);
    ?>
    <?php if (!empty($doctorServices)): ?>
    <section class="border-t border-[#e4edf6] bg-[#e4f1fa] py-12">
        <div class="mx-auto max-w-6xl px-6 md:px-10" data-admin-block-root>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0a293c]"<?php echo bioinmed_page_text_attr($doctorPage, 'doctor', 'services.eyebrow'); ?>><?php echo e($doctorServicesText['eyebrow'] ?? ''); ?></p>
            <h2 class="mt-2 text-xl font-bold text-[#0a293c] md:text-2xl"<?php echo bioinmed_page_text_attr($doctorPage, 'doctor', 'services.title'); ?>><?php echo e($doctorServicesText['title'] ?? ''); ?></h2>
            <p class="mt-2 text-sm text-[#0a293c]"<?php echo bioinmed_page_text_attr($doctorPage, 'doctor', 'services.description'); ?>><?php echo e($doctorServicesText['description'] ?? ''); ?></p>

            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($doctorServices as $srv): ?>
                <?php
                    $doctorServiceId = (string)($srv['id'] ?? '');
                    $doctorServiceNameNode = bioinmed_page_text_node($doctorPage, 'doctor', 'services.items.' . $doctorServiceId . '.name', (string)($srv['name'] ?? ''));
                    $doctorServiceDescriptionNode = bioinmed_page_text_node($doctorPage, 'doctor', 'services.items.' . $doctorServiceId . '.description', (string)($srv['description'] ?? ''));
                    $doctorServicePriceNode = bioinmed_page_text_node($doctorPage, 'doctor', 'services.items.' . $doctorServiceId . '.price', (string)($srv['price'] ?? ($doctorServicesText['price_fallback'] ?? '')));
                    $doctorServiceCtaNode = bioinmed_page_text_node($doctorPage, 'doctor', 'services.items.' . $doctorServiceId . '.cta', bioinmed_text('common.more_details'));
                ?>
                <a href="/services/<?php echo e($srv['id']); ?>"
                   class="fade-up group flex flex-col rounded-2xl border border-[#dce8f5] bg-white p-5 transition hover:border-[#1977b2] hover:shadow-[0_6px_20px_rgba(25,119,178,0.12)]"
                   data-admin-block-root>
                    <p class="text-sm font-semibold leading-snug text-[#0a293c] group-hover:text-[#1977b2]"<?php echo $doctorServiceNameNode['attr']; ?>>
                        <?php echo e($doctorServiceNameNode['value']); ?>
                    </p>
                    <?php if (!empty($srv['description'])): ?>
                    <p class="mt-2 line-clamp-2 text-xs leading-relaxed text-[#0a293c]"<?php echo $doctorServiceDescriptionNode['attr']; ?>>
                        <?php echo e(mb_substr($doctorServiceDescriptionNode['value'], 0, 110, 'UTF-8')); ?>…
                    </p>
                    <?php endif; ?>
                    <div class="mt-auto flex items-center justify-between pt-4">
                        <span class="text-xs font-semibold text-[#1977b2]"<?php echo $doctorServicePriceNode['attr']; ?>><?php echo e($doctorServicePriceNode['value']); ?></span>
                        <span class="inline-flex items-center gap-1 rounded-full bg-[#1977b2] px-3 py-1.5 text-[0.72rem] font-semibold text-white shadow-[0_8px_18px_rgba(25,119,178,0.18)] transition group-hover:bg-[#16658f]">
                            <span<?php echo $doctorServiceCtaNode['attr']; ?>><?php echo e($doctorServiceCtaNode['value']); ?></span>
                            <i class="fa-solid fa-arrow-right text-[0.62rem]"></i>
                        </span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ===== CTA STRIP ===== -->
    <section class="border-y border-[#e4edf6] bg-[#e4f1fa] py-12">
        <div class="mx-auto max-w-6xl px-6 text-center md:px-10" data-admin-block-root>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0a293c]"<?php echo bioinmed_page_text_attr($doctorPage, 'doctor', 'final_cta.eyebrow'); ?>><?php echo e($doctorFinalCta['eyebrow'] ?? ''); ?></p>
            <h2 class="mt-3 text-xl font-bold text-[#0a293c] md:text-2xl"<?php echo bioinmed_page_text_attr($doctorPage, 'doctor', 'final_cta.title'); ?>><?php echo e($doctorFinalCta['title'] ?? ''); ?></h2>
            <p class="mx-auto mt-3 max-w-2xl text-sm text-[#0a293c]">
                <?php
                $finalDescription = (string)($doctorFinalCta['description_template'] ?? '');
                $finalDescription = strtr($finalDescription, [
                    '{{doctor_name}}' => (string)($doctor['name'] ?? ''),
                    '{{clinic_name}}' => CLINIC_NAME,
                    '{{clinic_address}}' => CLINIC_ADDRESS,
                    '{{clinic_metro}}' => CLINIC_METRO,
                ]);
                echo e($finalDescription);
                ?>
            </p>
            <div class="mx-auto mt-6 max-w-md">
                <?php echo bioinmed_render_callback_form([
                    'source_label' => ($doctor['name'] ?? '') . (string)($doctorCta['final_source_suffix'] ?? ''),
                    'submit_label' => bioinmed_text('common.book_appointment'),
                    'button_class' => 'inline-flex w-full items-center justify-center rounded-full bg-[#1977b2] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#16658f] disabled:cursor-not-allowed disabled:bg-[#a7d7e9] disabled:text-white/90',
                ]); ?>
            </div>
            <div class="mt-4 flex flex-wrap justify-center gap-3">
                <a href="tel:<?php echo $phone1link; ?>" class="rounded-full border border-[#1977b2] px-5 py-2.5 text-sm font-semibold text-[#1977b2] hover:bg-white">
                    <i class="fa-solid fa-phone mr-1.5"></i><?php echo e($phone1); ?>
                </a>
            </div>
        </div>
    </section>

</main>
<?php endif; ?>

<?php
$footer = new Footer($brand_colors);
echo $footer->render();
?>

<script>
    // Intersection observer for fade-up animations
    document.querySelectorAll('.fade-up').forEach(function(el) {
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) { el.classList.add('visible'); observer.unobserve(el); }
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -30px 0px' });
        observer.observe(el);
    });

    (function() {
        var doctorSlug = <?php echo json_encode((string)$slug, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        var toggles = document.querySelectorAll('.doctor-section-toggle[data-doctor-toggle]');
        if (!doctorSlug || !toggles.length || typeof window.localStorage === 'undefined') {
            return;
        }

        var storageKey = 'bioinmed-doctor-toggles:' + doctorSlug;
        var savedState = {};

        try {
            var rawState = window.localStorage.getItem(storageKey);
            if (rawState) {
                savedState = JSON.parse(rawState) || {};
            }
        } catch (error) {
            savedState = {};
        }

        toggles.forEach(function(toggle) {
            var sectionKey = toggle.getAttribute('data-doctor-toggle');
            if (!sectionKey) {
                return;
            }

            if (Object.prototype.hasOwnProperty.call(savedState, sectionKey)) {
                toggle.open = !!savedState[sectionKey];
            }

            toggle.addEventListener('toggle', function() {
                savedState[sectionKey] = toggle.open;
                try {
                    window.localStorage.setItem(storageKey, JSON.stringify(savedState));
                } catch (error) {
                    // Ignore storage write failures.
                }
            });
        });

        document.querySelectorAll('[data-doctor-open-target]').forEach(function(link) {
            link.addEventListener('click', function(event) {
                var targetId = link.getAttribute('data-doctor-open-target');
                if (!targetId) {
                    return;
                }

                var target = document.getElementById(targetId);
                if (!target) {
                    return;
                }

                event.preventDefault();
                if ('open' in target && !target.open) {
                    target.open = true;
                }
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    })();

</script>
</body>
</html>
