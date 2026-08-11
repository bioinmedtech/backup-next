<?php
require_once __DIR__ . '/includes/pin_protection.php';
bioinmed_pin_require_access();


require_once 'config.php';
require_once 'includes/components/Components.php';
require_once 'includes/content/EditableLists.php';

$servicePage = bioinmed_read_json_file('pages/service.json');
$serviceMeta = is_array($servicePage['meta'] ?? null) ? $servicePage['meta'] : [];
$serviceBreadcrumbs = is_array($servicePage['breadcrumbs'] ?? null) ? $servicePage['breadcrumbs'] : [];
$serviceNotFound = is_array($servicePage['not_found'] ?? null) ? $servicePage['not_found'] : [];
$serviceDefault = is_array($servicePage['default'] ?? null) ? $servicePage['default'] : [];
$serviceFaqText = is_array($servicePage['faq'] ?? null) ? $servicePage['faq'] : [];
$serviceFlowText = is_array($servicePage['flow'] ?? null) ? $servicePage['flow'] : [];
$serviceAboutText = is_array($servicePage['about'] ?? null) ? $servicePage['about'] : [];
$serviceRelatedText = is_array($servicePage['related'] ?? null) ? $servicePage['related'] : [];
$serviceSidebarText = is_array($servicePage['sidebar'] ?? null) ? $servicePage['sidebar'] : [];
$serviceFinalCta = is_array($servicePage['final_cta'] ?? null) ? $servicePage['final_cta'] : [];
$serviceImageModal = is_array($servicePage['image_modal'] ?? null) ? $servicePage['image_modal'] : [];
$serviceHabilect = is_array($servicePage['habilect'] ?? null) ? $servicePage['habilect'] : [];
$serviceHabilectSections = is_array($serviceHabilect['sections'] ?? null) ? $serviceHabilect['sections'] : [];

$serviceNotFoundTitleNode = bioinmed_page_text_node($servicePage, 'service', 'not_found.title', 'Услуга не найдена');
$serviceNotFoundTextNode = bioinmed_page_text_node($servicePage, 'service', 'not_found.text', 'Проверьте ссылку или перейдите к прайс-листу.');
$serviceNotFoundPricesButtonNode = bioinmed_page_text_node($servicePage, 'service', 'not_found.prices_button', 'Прайс-лист');
$serviceNotFoundHomeButtonNode = bioinmed_page_text_node($servicePage, 'service', 'not_found.home_button', 'На главную');

$siteUrl = rtrim(CLINIC_SITE_URL, '/');
$iconPath = CLINIC_ICON_PATH;

$serviceSlug = isset($_GET['slug']) ? trim((string)$_GET['slug']) : '';
$serviceId   = isset($_GET['id'])   ? trim((string)$_GET['id'])   : '';
$service     = null;
$matchedByLegacy = false;
$matchedByAlias = false;

if ($serviceSlug !== '' && isset($service_aliases[$serviceSlug])) {
    $serviceSlug = (string)$service_aliases[$serviceSlug];
    $matchedByAlias = true;
}
if ($serviceId !== '' && isset($service_aliases[$serviceId])) {
    $serviceId = (string)$service_aliases[$serviceId];
    $matchedByAlias = true;
}

// Also collect related services (same category, excluding current)
$related = [];
foreach ($services as $item) {
    $itemId = isset($item['id']) ? (string)$item['id'] : '';
    $legacyId = isset($item['legacy_id']) ? (string)$item['legacy_id'] : '';
    if (
        ($serviceSlug !== '' && ($itemId === $serviceSlug || $legacyId === $serviceSlug)) ||
        ($serviceId !== '' && ($itemId === $serviceId || $legacyId === $serviceId))
    ) {
        $service = $item;
        $matchedByLegacy = ($serviceSlug !== '' && $legacyId !== '' && $legacyId === $serviceSlug);
    }
}

// Canonical redirect from legacy hash URL to readable slug URL.
if ($service && $matchedByLegacy && !headers_sent()) {
    header('Location: /services/' . rawurlencode((string)($service['id'] ?? '')), true, 301);
    exit;
}
if ($service && $matchedByAlias && !headers_sent()) {
    header('Location: /services/' . rawurlencode((string)($service['id'] ?? '')), true, 301);
    exit;
}
if ($service) {
    foreach ($services as $item) {
        if (($item['id'] ?? '') !== ($service['id'] ?? '')
            && ($item['category'] ?? '') === ($service['category'] ?? '')) {
            $related[] = $item;
        }
    }
    if (empty($related)) { // fallback: first 3 from any category
        foreach ($services as $item) {
            if (($item['id'] ?? '') !== ($service['id'] ?? '')) {
                $related[] = $item;
                if (count($related) >= 3) break;
            }
        }
    }
}

if ($service === null) {
    http_response_code(404);
}



function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function service_card_excerpt(string $value, int $limit = 88): string {
    $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    if ($value === '') {
        return '';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($value, 'UTF-8') > $limit) {
            return rtrim(mb_substr($value, 0, $limit - 1, 'UTF-8')) . '…';
        }
        return $value;
    }

    if (strlen($value) > $limit) {
        return rtrim(substr($value, 0, $limit - 1)) . '…';
    }

    return $value;
}

function bioinmed_render_habilect_two_column_section(
    array $servicePage,
    array $serviceHabilectSections,
    string $sectionKey,
    string $detailsId,
    string $iconClass
): void {
    $section = is_array($serviceHabilectSections[$sectionKey] ?? null) ? $serviceHabilectSections[$sectionKey] : [];
    $leftListKey = 'service.habilect-diagnostics.habilect.sections.' . $sectionKey . '.left_items';
    $rightListKey = 'service.habilect-diagnostics.habilect.sections.' . $sectionKey . '.right_items';
    $leftFallback = bioinmed_editable_list_resolve_texts($servicePage, 'habilect.sections.' . $sectionKey . '.left_items', is_array($section['left_items'] ?? null) ? $section['left_items'] : []);
    $rightFallback = bioinmed_editable_list_resolve_texts($servicePage, 'habilect.sections.' . $sectionKey . '.right_items', is_array($section['right_items'] ?? null) ? $section['right_items'] : []);
    $leftItems = bioinmed_editable_list_items($servicePage, $leftListKey, $leftFallback, 'fa-solid fa-check');
    $rightItems = bioinmed_editable_list_items($servicePage, $rightListKey, $rightFallback, 'fa-solid fa-check');
    ?>
    <details id="<?php echo e($detailsId); ?>" class="group rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]" data-admin-block-root>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-7 text-left marker:hidden">
            <span class="flex items-center gap-2.5 text-[1.05rem] font-bold text-[#0a293c] md:text-[1.12rem]">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#1977b2]"><i class="<?php echo e($iconClass); ?> text-xs"></i></span>
                <span<?php echo bioinmed_page_text_attr($servicePage, 'service', 'habilect.sections.' . $sectionKey . '.title'); ?>><?php echo e($section['title'] ?? ''); ?></span>
            </span>
            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#cfe0ef] bg-white text-[#0a293c]">
                <i class="fa-solid fa-chevron-down text-[0.72rem] transition group-open:rotate-180"></i>
            </span>
        </summary>
        <div class="space-y-5 px-7 pb-7">
            <p class="text-[0.96rem] leading-relaxed text-[#0a293c]"<?php echo bioinmed_page_text_attr($servicePage, 'service', 'habilect.sections.' . $sectionKey . '.intro'); ?>><?php echo e($section['intro'] ?? ''); ?></p>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <div class="rounded-xl border border-[#e4edf6] bg-white p-4">
                    <ul class="space-y-3 text-[0.96rem] leading-snug text-[#0a293c]"<?php echo bioinmed_editable_list_attrs('service', $leftListKey, (string)($section['title'] ?? '') . ': левая колонка'); ?>>
                        <?php echo bioinmed_editable_list_toolbar(); ?>
                        <?php foreach ($leftItems as $leftItem): ?>
                        <li class="flex items-start gap-3<?php echo bioinmed_editable_list_item_class($leftItem); ?>"<?php echo bioinmed_editable_list_item_attrs($leftItem); ?>><i class="<?php echo e($leftItem['icon']); ?> mt-0.5 text-[#1977b2]" data-admin-list-icon-view></i><span data-admin-list-text-view><?php echo e($leftItem['text']); ?></span><?php echo bioinmed_editable_list_actions($leftItem); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="rounded-xl border border-[#e4edf6] bg-white p-4">
                    <ul class="space-y-3 text-[0.96rem] leading-snug text-[#0a293c]"<?php echo bioinmed_editable_list_attrs('service', $rightListKey, (string)($section['title'] ?? '') . ': правая колонка'); ?>>
                        <?php echo bioinmed_editable_list_toolbar(); ?>
                        <?php foreach ($rightItems as $rightItem): ?>
                        <li class="flex items-start gap-3<?php echo bioinmed_editable_list_item_class($rightItem); ?>"<?php echo bioinmed_editable_list_item_attrs($rightItem); ?>><i class="<?php echo e($rightItem['icon']); ?> mt-0.5 text-[#1977b2]" data-admin-list-icon-view></i><span data-admin-list-text-view><?php echo e($rightItem['text']); ?></span><?php echo bioinmed_editable_list_actions($rightItem); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </details>
    <?php
}

function bioinmed_render_habilect_grid_items_section(
    array $servicePage,
    array $serviceHabilectSections,
    string $sectionKey,
    string $detailsId,
    string $iconClass,
    string $columnsClass
): void {
    $section = is_array($serviceHabilectSections[$sectionKey] ?? null) ? $serviceHabilectSections[$sectionKey] : [];
    $listKey = 'service.habilect-diagnostics.habilect.sections.' . $sectionKey . '.items';
    $itemFallback = bioinmed_editable_list_resolve_texts($servicePage, 'habilect.sections.' . $sectionKey . '.items', is_array($section['items'] ?? null) ? $section['items'] : []);
    $items = bioinmed_editable_list_items($servicePage, $listKey, $itemFallback, 'fa-solid fa-check');
    ?>
    <details id="<?php echo e($detailsId); ?>" class="group rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]" data-admin-block-root>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-7 text-left marker:hidden">
            <span class="flex items-center gap-2.5 text-[1.05rem] font-bold text-[#0a293c] md:text-[1.12rem]">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#1977b2]"><i class="<?php echo e($iconClass); ?> text-xs"></i></span>
                <span<?php echo bioinmed_page_text_attr($servicePage, 'service', 'habilect.sections.' . $sectionKey . '.title'); ?>><?php echo e($section['title'] ?? ''); ?></span>
            </span>
            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#cfe0ef] bg-white text-[#0a293c]">
                <i class="fa-solid fa-chevron-down text-[0.72rem] transition group-open:rotate-180"></i>
            </span>
        </summary>
        <div class="space-y-5 px-7 pb-7">
            <p class="text-[0.96rem] leading-relaxed text-[#0a293c]"<?php echo bioinmed_page_text_attr($servicePage, 'service', 'habilect.sections.' . $sectionKey . '.intro'); ?>><?php echo e($section['intro'] ?? ''); ?></p>
            <ul class="mt-4 grid gap-3 <?php echo e($columnsClass); ?> text-[0.96rem] leading-snug text-[#0a293c]"<?php echo bioinmed_editable_list_attrs('service', $listKey, (string)($section['title'] ?? '')); ?>>
                <?php echo bioinmed_editable_list_toolbar(); ?>
                <?php foreach ($items as $item): ?>
                <li class="flex items-start gap-3 rounded-xl border border-[#e4edf6] bg-white p-3.5<?php echo bioinmed_editable_list_item_class($item); ?>"<?php echo bioinmed_editable_list_item_attrs($item); ?>><i class="<?php echo e($item['icon']); ?> mt-0.5 text-[#1977b2]" data-admin-list-icon-view></i><span data-admin-list-text-view><?php echo e($item['text']); ?></span><?php echo bioinmed_editable_list_actions($item); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </details>
    <?php
}

$pageTitle = $service
    ? trim((string)($service['meta_title'] ?? $service['name'])) . ' | ' . CLINIC_NAME
    : ((string)($serviceMeta['not_found_title'] ?? '') . ' | ' . CLINIC_NAME);
$pageDesc  = $service
    ? bioinmed_meta_description(
        trim((string)($service['description'] ?? '')),
        trim((string)($service['name'] ?? '')) . ' в клинике БИОИНМЕД в Москве. Описание услуги, цена и запись на приём: ' . CLINIC_PHONE,
        170
    )
    : bioinmed_meta_description(
        $serviceMeta['not_found_description'] ?? '',
        'Описание услуги не найдено. Перейдите к каталогу услуг клиники БИОИНМЕД.'
    );
$canonicalUrl = $service
    ? $siteUrl . '/services/' . rawurlencode((string)($service['id'] ?? $serviceSlug))
    : $siteUrl . '/services';
$robotsContent = $service
    ? 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'
    : 'noindex,follow';

$phone1     = CLINIC_PHONE;
$phone1link = preg_replace('/\D/', '', $phone1);
$phone2     = defined('CLINIC_PHONE_2') ? CLINIC_PHONE_2 : '';
$phone2link = $phone2 ? preg_replace('/\D/', '', $phone2) : '';

// Map category to icon + label
$catInfo = [
    'diagnostics'     => ['icon' => 'fa-magnifying-glass-plus', 'label' => bioinmed_text('service.categories.diagnostics', 'Диагностика')],
    'musculoskeletal' => ['icon' => 'fa-bone',                  'label' => bioinmed_text('service.categories.musculoskeletal', 'Опорно-двигательный аппарат')],
    'manual_therapy'  => ['icon' => 'fa-hands',                 'label' => bioinmed_text('service.categories.manual_therapy', 'Мануальная терапия')],
    'osteopathy'      => ['icon' => 'fa-hand-sparkles',         'label' => bioinmed_text('service.categories.osteopathy', 'Остеопатия')],
    'therapy'         => ['icon' => 'fa-heart-pulse',           'label' => bioinmed_text('service.categories.therapy', 'Терапия')],
    'physiotherapy'   => ['icon' => 'fa-wave-square',           'label' => bioinmed_text('service.categories.physiotherapy', 'Физиотерапия')],
    'reflexotherapy'  => ['icon' => 'fa-bullseye',              'label' => bioinmed_text('service.categories.reflexotherapy', 'Рефлексотерапия')],
    'infusion_therapy'=> ['icon' => 'fa-droplet',               'label' => bioinmed_text('service.categories.infusion_therapy', 'Инфузионная терапия')],
    'ozone_therapy'   => ['icon' => 'fa-wind',                  'label' => bioinmed_text('service.categories.ozone_therapy', 'Озонотерапия')],
    'injection_therapy'=>['icon' => 'fa-syringe',               'label' => bioinmed_text('service.categories.injection_therapy', 'Инъекционная терапия')],
    'chief_doctor'    => ['icon' => 'fa-user-doctor',           'label' => bioinmed_text('service.categories.chief_doctor', 'Приём главного врача')],
    'psychology'      => ['icon' => 'fa-brain',                 'label' => bioinmed_text('service.categories.psychology', 'Психология')],
    'taping'          => ['icon' => 'fa-bandage',               'label' => bioinmed_text('service.categories.taping', 'Тейпирование и банки')],
    'integrative'     => ['icon' => 'fa-leaf',                  'label' => bioinmed_text('service.categories.integrative', 'Интегративная медицина')],
];
$cat      = $service['category'] ?? 'therapy';
$catIcon  = $catInfo[$cat]['icon']  ?? 'fa-stethoscope';
$catLabel = $catInfo[$cat]['label'] ?? bioinmed_text('service.default_label', '');
$serviceActualPrice = $service ? bioinmed_service_actual_price_parts($service) : ['price' => '', 'note' => ''];
$serviceGallery = $service ? bioinmed_service_gallery_urls($service, 4) : [];
$servicePrimaryImage = $serviceGallery[0] ?? null;
$socialImageUrl = $servicePrimaryImage ? ($siteUrl . $servicePrimaryImage) : bioinmed_default_social_image_url();
$serviceEditablePrefix = 'service.' . (string)($service['id'] ?? ($serviceSlug ?: 'default'));
$serviceDoctorTitle = trim((string)($service['doctor_title'] ?? ''));
$serviceHeroNameNode = bioinmed_page_text_node($servicePage, 'service', $serviceEditablePrefix . '.hero.service_name', (string)($service['name'] ?? ''));
$serviceDoctorTitleNode = bioinmed_page_text_node($servicePage, 'service', $serviceEditablePrefix . '.hero.doctor_title', $serviceDoctorTitle);
$serviceHeroDescriptionNode = bioinmed_page_text_node($servicePage, 'service', $serviceEditablePrefix . '.hero.description', (string)($service['description'] ?? ''));
$serviceFlowDetailsNode = bioinmed_page_text_node($servicePage, 'service', $serviceEditablePrefix . '.flow.details', (string)($service['details'] ?? ''));
$serviceFlowTargetNode = bioinmed_page_text_node($servicePage, 'service', $serviceEditablePrefix . '.flow.target_text', (string)($service['target'] ?? ''));
$serviceSidebarPriceNode = bioinmed_page_text_node(
    $servicePage,
    'service',
    'sidebar.price',
    (string)($serviceActualPrice['price'] !== '' ? $serviceActualPrice['price'] : ($serviceDefault['price_on_request'] ?? ''))
);
$serviceSidebarPriceNoteNode = bioinmed_page_text_node($servicePage, 'service', 'sidebar.price_note', (string)($serviceActualPrice['note'] ?? ''));
$serviceSidebarPriceNode['value'] = (string)($serviceActualPrice['price'] !== '' ? $serviceActualPrice['price'] : ($serviceDefault['price_on_request'] ?? ''));
$serviceSidebarPriceNoteNode['value'] = (string)($serviceActualPrice['note'] ?? '');
$isHabilect = (($service['id'] ?? '') === 'habilect-diagnostics');
$serviceGalleryJson = json_encode(array_values($serviceGallery), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$faqFallback = [];
foreach ((is_array($serviceFaqText['items'] ?? null) ? $serviceFaqText['items'] : []) as $faqIndex => $faqEntry) {
    $faqFallback[] = [
        'id' => 'faq-' . $faqIndex,
        'text' => (string)($faqEntry['q'] ?? ''),
        'secondary' => (string)($faqEntry['a'] ?? ''),
    ];
}
$serviceFaqItems = bioinmed_editable_list_items($servicePage, $serviceEditablePrefix . '.faq.items', $faqFallback, '');
$faqs_on_page = array_map(static fn (array $faq): array => ['q' => $faq['text'], 'a' => $faq['secondary']], array_values(array_filter($serviceFaqItems, static fn (array $faq): bool => empty($faq['hidden']))));
$flowFallback = bioinmed_editable_list_resolve_texts($servicePage, 'flow.steps', is_array($serviceFlowText['steps'] ?? null) ? $serviceFlowText['steps'] : []);
$aboutFallback = bioinmed_editable_list_resolve_texts($servicePage, 'about.items', is_array($serviceAboutText['items'] ?? null) ? $serviceAboutText['items'] : []);
$flowSteps = bioinmed_editable_list_items($servicePage, $serviceEditablePrefix . '.flow.steps', $flowFallback, '');
$aboutItems = bioinmed_editable_list_items($servicePage, $serviceEditablePrefix . '.about.items', $aboutFallback, 'fa-solid fa-check');
$visitPrepFallback = [];
$visitPrepIcons = ['fa-solid fa-file-medical', 'fa-solid fa-clock', 'fa-solid fa-list-check'];
foreach (bioinmed_editable_list_resolve_texts($servicePage, 'sidebar.visit_prep_items', is_array($serviceSidebarText['visit_prep_items'] ?? null) ? $serviceSidebarText['visit_prep_items'] : []) as $visitPrepIndex => $visitPrepText) {
    $visitPrepFallback[] = ['text' => (string)$visitPrepText, 'icon' => $visitPrepIcons[$visitPrepIndex] ?? 'fa-solid fa-check'];
}
$visitPrepItems = bioinmed_editable_list_items($servicePage, $serviceEditablePrefix . '.sidebar.visit_prep_items', $visitPrepFallback, 'fa-solid fa-check');
$organizationStructuredData = bioinmed_medical_organization_schema();
$breadcrumbStructuredData = bioinmed_breadcrumb_schema([
    ['name' => (string)($serviceBreadcrumbs['home'] ?? ''), 'url' => '/'],
    ['name' => (string)($serviceBreadcrumbs['services'] ?? ''), 'url' => '/services'],
]);
$faqStructuredData = bioinmed_faq_schema($faqs_on_page);
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?></title>
    <meta name="description" content="<?php echo e($pageDesc); ?>">
    <meta name="robots" content="<?php echo $robotsContent; ?>">
    <link rel="canonical" href="<?php echo e($canonicalUrl); ?>">
    <meta name="theme-color" content="#1977b2">
    <?php echo bioinmed_render_social_meta($pageTitle, $pageDesc, $canonicalUrl, [
        'type' => 'article',
        'image' => $socialImageUrl,
        'image_alt' => $service['name'] ?? (CLINIC_NAME . (string)($serviceMeta['social_image_alt_suffix'] ?? '')),
    ]); ?>
    <?php echo bioinmed_render_favicon_links($iconPath); ?>
    <?php if ($service): ?>
    <script type="application/ld+json"><?php echo json_encode([
        '@context'       => 'https://schema.org',
        '@type'          => 'MedicalProcedure',
        'name'           => $service['name'],
        'description'    => $service['description'] ?? '',
        'procedureType'  => $catLabel,
        'followup'       => $service['details'] ?? '',
        'provider'       => [
            '@type' => 'MedicalOrganization',
            'name'  => CLINIC_NAME,
            'url'   => CLINIC_SITE_URL,
            'telephone' => CLINIC_PHONE,
            'address'   => ['@type' => 'PostalAddress', 'streetAddress' => CLINIC_ADDRESS],
        ],
        'url' => $canonicalUrl,
        'image' => $socialImageUrl,
        'mainEntityOfPage' => $canonicalUrl,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <?php endif; ?>
    <script type="application/ld+json"><?php echo json_encode($organizationStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script type="application/ld+json"><?php echo json_encode($breadcrumbStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <?php if ($service): ?>
    <script type="application/ld+json"><?php echo json_encode($faqStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <?php endif; ?>
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

        main p,
        main li {
            line-height: 1.72;
        }

        main .text-xs {
            font-size: 0.8rem;
        }

        main .text-sm {
            font-size: 0.96rem;
        }

        main .text-base {
            font-size: 1.04rem;
        }

        main h1 {
            font-size: clamp(2rem, 2.6vw, 3rem);
            line-height: 1.12;
        }

        main h2 {
            font-size: clamp(1.3rem, 1.7vw, 1.7rem);
            line-height: 1.18;
        }

        .fade-up { opacity: 0; transform: translateY(20px); transition: opacity .5s ease, transform .5s ease; }
        .fade-up.visible { opacity: 1; transform: translateY(0); }
        .prose-service p { margin-bottom: .95rem; line-height: 1.78; }
        .service-gallery-thumb.is-active { border-color: #1977b2; box-shadow: 0 0 0 3px rgba(36, 140, 255, 0.16); }
        .service-main-image-frame { overflow: hidden; }
        .service-main-image-live {
            transform: scale(1.08);
            will-change: transform;
        }
        .service-main-image-live.is-animating {
            animation: serviceLivePhotoZoom 6s ease-out forwards;
        }
        @keyframes serviceLivePhotoZoom {
            from { transform: scale(1.1); }
            to { transform: scale(1); }
        }
        @media (prefers-reduced-motion: reduce) {
            .service-main-image-live,
            .service-main-image-live.is-animating {
                animation: none;
                transform: none;
            }
        }
    </style>
    <?php echo bioinmed_uis_counter_head(); ?>
</head>
<body class="flex min-h-screen flex-col bg-[#e4f1fa] text-[#0f2749] antialiased">
    <?php echo bioinmed_yandex_metrika_noscript(); ?>
<?php
$header = new Header($brand_colors);
echo $header->render();
?>

<?php if (!$service): ?>
<main class="mx-auto max-w-4xl grow px-6 py-20 md:px-10">
    <div class="rounded-3xl border border-[#dbe8f3] bg-white p-10 text-center shadow-[0_16px_40px_rgba(8,36,70,0.08)]" data-admin-block-root>
        <i class="fa-solid fa-triangle-exclamation mb-4 text-5xl text-[#b0c8e0]"></i>
        <h1 class="text-3xl font-bold text-[#0a293c]"<?php echo $serviceNotFoundTitleNode['attr']; ?>><?php echo e($serviceNotFoundTitleNode['value']); ?></h1>
        <p class="mt-3 text-[#0a293c]"<?php echo $serviceNotFoundTextNode['attr']; ?>><?php echo e($serviceNotFoundTextNode['value']); ?></p>
        <div class="mt-7 flex flex-wrap justify-center gap-3">
            <a href="<?php echo e(bioinmed_link('nav.prices')['url']); ?>" class="inline-flex items-center gap-2 rounded-full bg-[#1977b2] px-6 py-3 text-sm font-semibold text-white hover:bg-[#16658f]"<?php echo $serviceNotFoundPricesButtonNode['attr']; ?>>
                <i class="fa-solid fa-list"></i> <?php echo e($serviceNotFoundPricesButtonNode['value']); ?>
            </a>
            <a href="/" class="inline-flex items-center gap-2 rounded-full border border-[#c4daed] bg-white px-6 py-3 text-sm font-semibold text-[#0a293c] hover:bg-[#ecf5ff]"<?php echo $serviceNotFoundHomeButtonNode['attr']; ?>>
                <i class="fa-solid fa-house"></i> <?php echo e($serviceNotFoundHomeButtonNode['value']); ?>
            </a>
        </div>
    </div>
</main>

<?php else: ?>
<main class="grow">

    <!-- ===== SERVICE CONTENT ===== -->
    <section>
        <div class="mx-auto max-w-6xl px-6 py-10 md:px-10 md:py-14">

            <!-- breadcrumb -->
            <div class="bioinmed-back-row"><?php echo bioinmed_render_back_button(['fallback' => '/services']); ?></div>
            <nav class="mb-6 flex items-center gap-2 text-xs text-[#7a9cc4]">
                <a href="/" class="hover:text-[#1977b2]"<?php echo bioinmed_page_text_attr($servicePage, 'service', 'breadcrumbs.home'); ?>><?php echo e($serviceBreadcrumbs['home'] ?? ''); ?></a>
                <i class="fa-solid fa-chevron-right text-[0.6rem]"></i>
                <a href="/services" class="hover:text-[#1977b2]"<?php echo bioinmed_page_text_attr($servicePage, 'service', 'breadcrumbs.services'); ?>><?php echo e($serviceBreadcrumbs['services'] ?? ''); ?></a>
            </nav>

            <div class="fade-up" data-admin-block-root>
                    <h1 class="mt-4 text-xl font-bold leading-tight text-[#0a293c] sm:text-2xl md:text-3xl lg:text-4xl"<?php echo $serviceHeroNameNode['attr']; ?>><?php echo e($serviceHeroNameNode['value']); ?></h1>
                    <?php if ($serviceDoctorTitleNode['value'] !== ''): ?>
                    <p class="mt-2 text-[0.95rem] font-semibold text-[#355b89]"<?php echo $serviceDoctorTitleNode['attr']; ?>><?php echo e($serviceDoctorTitleNode['value']); ?></p>
                    <?php endif; ?>

                    <?php if (!$isHabilect && $serviceHeroDescriptionNode['value'] !== ''): ?>
                    <p class="mt-4 max-w-2xl text-base leading-relaxed text-[#0a293c] md:text-[1.02rem]"<?php echo $serviceHeroDescriptionNode['attr']; ?>>
                        <?php echo e($serviceHeroDescriptionNode['value']); ?>
                    </p>
                    <?php endif; ?>

                </div>

            <div class="mt-8 grid items-start gap-8 lg:mt-10 lg:grid-cols-[minmax(0,1fr)_380px]">

                <!-- left: main content -->
                <div class="space-y-6 lg:order-1">

                <!-- Реабилитация «Хабилект» -->
                <?php if ($isHabilect): ?>
                <div class="fade-up">
                    <div data-admin-block-root>
                    <?php foreach ((is_array($serviceHabilect['intro_paragraphs'] ?? null) ? $serviceHabilect['intro_paragraphs'] : []) as $habilectIntroIndex => $habilectIntroEntry): ?>
                    <?php
                    if (is_array($habilectIntroEntry)) {
                        $habilectIntro = (string)($habilectIntroEntry['text'] ?? '');
                        $habilectIntroKey = trim((string)($habilectIntroEntry['id'] ?? ('paragraph_' . $habilectIntroIndex)));
                    } else {
                        $habilectIntro = (string)$habilectIntroEntry;
                        $habilectIntroKey = 'paragraph_' . $habilectIntroIndex;
                    }
                    ?>
                    <p class="<?php echo $habilectIntroIndex === 0 ? 'max-w-3xl' : 'mt-3 max-w-3xl'; ?> text-[0.98rem] leading-relaxed text-[#0a293c] md:text-[1.02rem]"<?php echo bioinmed_page_text_attr($servicePage, 'service', 'habilect.intro_paragraphs.' . $habilectIntroKey); ?>>
                        <?php echo e($habilectIntro); ?>
                    </p>
                    <?php endforeach; ?>
                    </div>
                    <div class="mt-6">
                        <?php bioinmed_render_editable_icon_list($servicePage, 'service', 'service.habilect-diagnostics.habilect.top_points', is_array($serviceHabilect['top_points'] ?? null) ? $serviceHabilect['top_points'] : [], 'Ключевые возможности Хабилект', 'mt-4 grid gap-3 md:grid-cols-2', 'flex items-start gap-3 rounded-2xl border border-[#e4edf6] bg-white p-3.5 text-[0.92rem] leading-relaxed text-[#0a293c] md:p-4'); ?>
                    </div>
                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <div class="rounded-2xl border border-[#e4edf6] bg-transparent p-4 md:p-5" data-admin-block-root>
                            <p class="text-[0.75rem] font-semibold uppercase tracking-[0.16em] text-[#1977b2]"<?php echo bioinmed_page_text_attr($servicePage, 'service', 'habilect.module_short.hclinic_title'); ?>><?php echo e($serviceHabilect['module_short']['hclinic_title'] ?? 'H.Clinic'); ?></p>
                            <?php bioinmed_render_editable_icon_list($servicePage, 'service', 'service.habilect-diagnostics.habilect.module_short.hclinic_items', is_array($serviceHabilect['module_short']['hclinic_items'] ?? null) ? $serviceHabilect['module_short']['hclinic_items'] : [], 'H.Clinic: кратко', 'mt-3 space-y-2.5 text-[0.92rem] leading-relaxed text-[#0a293c]', 'flex items-start gap-3'); ?>
                        </div>
                        <div class="rounded-2xl border border-[#e4edf6] bg-transparent p-4 md:p-5" data-admin-block-root>
                            <p class="text-[0.75rem] font-semibold uppercase tracking-[0.16em] text-[#1977b2]"<?php echo bioinmed_page_text_attr($servicePage, 'service', 'habilect.module_short.motionlab_title'); ?>><?php echo e($serviceHabilect['module_short']['motionlab_title'] ?? 'H.MotionLAB'); ?></p>
                            <?php bioinmed_render_editable_icon_list($servicePage, 'service', 'service.habilect-diagnostics.habilect.module_short.motionlab_items', [(string)($serviceHabilect['module_short']['motionlab_text'] ?? '')], 'H.MotionLAB: кратко', 'mt-3 space-y-2.5 text-[0.92rem] leading-relaxed text-[#0a293c]', 'flex items-start gap-3'); ?>
                        </div>
                    </div>

                    <div class="mt-6 space-y-4">
                        <?php bioinmed_render_habilect_two_column_section($servicePage, $serviceHabilectSections, 'for_who', 'habilect-for-who', 'fa-solid fa-user-group'); ?>

                        <?php bioinmed_render_habilect_two_column_section($servicePage, $serviceHabilectSections, 'assessment', 'habilect-assessment', 'fa-solid fa-chart-column'); ?>

                        <details id="habilect-process" class="group rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]" data-admin-block-root>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-7 text-left marker:hidden">
                                <span class="flex items-center gap-2.5 text-[1.05rem] font-bold text-[#0a293c] md:text-[1.12rem]">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#1977b2]"><i class="fa-solid fa-person-walking text-xs"></i></span>
                                    <span<?php echo bioinmed_page_text_attr($servicePage, 'service', 'habilect.sections.modules.title'); ?>><?php echo e($serviceHabilectSections['modules']['title'] ?? ''); ?></span>
                                </span>
                                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#cfe0ef] bg-white text-[#0a293c]">
                                    <i class="fa-solid fa-chevron-down text-[0.72rem] transition group-open:rotate-180"></i>
                                </span>
                            </summary>
                            <div class="space-y-5 px-7 pb-7">
                                <p class="text-[0.96rem] leading-relaxed text-[#0a293c]"<?php echo bioinmed_page_text_attr($servicePage, 'service', 'habilect.sections.modules.intro'); ?>><?php echo e($serviceHabilectSections['modules']['intro'] ?? ''); ?></p>
                                <div class="mt-4 grid gap-4 md:grid-cols-2">
                                    <div class="rounded-xl border border-[#e4edf6] bg-white p-4">
                                        <p class="text-[0.84rem] font-semibold uppercase tracking-[0.12em] text-[#1977b2]"<?php echo bioinmed_page_text_attr($servicePage, 'service', 'habilect.sections.modules.hclinic_title'); ?>><?php echo e($serviceHabilectSections['modules']['hclinic_title'] ?? 'H.Clinic'); ?></p>
                                        <?php bioinmed_render_editable_icon_list($servicePage, 'service', 'service.habilect-diagnostics.habilect.sections.modules.hclinic_items', is_array($serviceHabilectSections['modules']['hclinic_items'] ?? null) ? $serviceHabilectSections['modules']['hclinic_items'] : [], 'Модули H.Clinic', 'mt-3 space-y-3 text-[0.96rem] leading-snug text-[#0a293c]', 'flex items-start gap-3'); ?>
                                    </div>
                                    <div class="rounded-xl border border-[#e4edf6] bg-white p-4">
                                        <p class="text-[0.84rem] font-semibold uppercase tracking-[0.12em] text-[#1977b2]"<?php echo bioinmed_page_text_attr($servicePage, 'service', 'habilect.sections.modules.motionlab_title'); ?>><?php echo e($serviceHabilectSections['modules']['motionlab_title'] ?? 'H.MotionLAB'); ?></p>
                                        <?php bioinmed_render_editable_icon_list($servicePage, 'service', 'service.habilect-diagnostics.habilect.sections.modules.motionlab_items', is_array($serviceHabilectSections['modules']['motionlab_items'] ?? null) ? $serviceHabilectSections['modules']['motionlab_items'] : [], 'Модули H.MotionLAB', 'mt-3 space-y-3 text-[0.96rem] leading-snug text-[#0a293c]', 'flex items-start gap-3'); ?>
                                    </div>
                                </div>
                            </div>
                        </details>

                        <?php bioinmed_render_habilect_grid_items_section($servicePage, $serviceHabilectSections, 'biofeedback', 'habilect-biofeedback', 'fa-solid fa-circle-nodes', 'md:grid-cols-2'); ?>

                        <?php bioinmed_render_habilect_grid_items_section($servicePage, $serviceHabilectSections, 'games', 'habilect-games', 'fa-solid fa-gamepad', 'md:grid-cols-3'); ?>

                        <?php bioinmed_render_habilect_two_column_section($servicePage, $serviceHabilectSections, 'reports', 'habilect-reports', 'fa-solid fa-file-waveform'); ?>

                        <details id="habilect-benefits" class="group rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]" data-admin-block-root>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-7 text-left marker:hidden">
                                <span class="flex items-center gap-2.5 text-[1.05rem] font-bold text-[#0a293c] md:text-[1.12rem]">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#1977b2]"><i class="fa-solid fa-award text-xs"></i></span>
                                    <span<?php echo bioinmed_page_text_attr($servicePage, 'service', 'habilect.sections.regulations.title'); ?>><?php echo e($serviceHabilectSections['regulations']['title'] ?? ''); ?></span>
                                </span>
                                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#cfe0ef] bg-white text-[#0a293c]">
                                    <i class="fa-solid fa-chevron-down text-[0.72rem] transition group-open:rotate-180"></i>
                                </span>
                            </summary>
                            <div class="space-y-5 px-7 pb-7">
                                <p class="text-[0.96rem] leading-relaxed text-[#0a293c]"<?php echo bioinmed_page_text_attr($servicePage, 'service', 'habilect.sections.regulations.intro'); ?>><?php echo e($serviceHabilectSections['regulations']['intro'] ?? ''); ?></p>
                                <div class="mt-4 grid gap-3 md:grid-cols-2">
                                    <div class="rounded-xl border border-[#e4edf6] bg-white p-4">
                                        <p class="text-[0.84rem] font-semibold uppercase tracking-[0.14em] text-[#1977b2]"<?php echo bioinmed_page_text_attr($servicePage, 'service', 'habilect.sections.regulations.orders_title'); ?>><?php echo e($serviceHabilectSections['regulations']['orders_title'] ?? ''); ?></p>
                                        <?php bioinmed_render_editable_icon_list($servicePage, 'service', 'service.habilect-diagnostics.habilect.sections.regulations.orders_items', is_array($serviceHabilectSections['regulations']['orders_items'] ?? null) ? $serviceHabilectSections['regulations']['orders_items'] : [], 'Приказы МЗ РФ', 'mt-3 space-y-3 text-[0.96rem] leading-snug text-[#0a293c]', 'flex items-start gap-3'); ?>
                                    </div>
                                    <div class="rounded-xl border border-[#e4edf6] bg-white p-4">
                                        <p class="text-[0.84rem] font-semibold uppercase tracking-[0.14em] text-[#1977b2]"<?php echo bioinmed_page_text_attr($servicePage, 'service', 'habilect.sections.regulations.clinical_title'); ?>><?php echo e($serviceHabilectSections['regulations']['clinical_title'] ?? ''); ?></p>
                                        <?php bioinmed_render_editable_icon_list($servicePage, 'service', 'service.habilect-diagnostics.habilect.sections.regulations.clinical_items', is_array($serviceHabilectSections['regulations']['clinical_items'] ?? null) ? $serviceHabilectSections['regulations']['clinical_items'] : [], 'Клинический контур', 'mt-3 space-y-3 text-[0.96rem] leading-snug text-[#0a293c]', 'flex items-start gap-3'); ?>
                                    </div>
                                </div>
                            </div>
                        </details>

                        <details id="habilect-patient-result" class="group rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]" data-admin-block-root>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-7 text-left marker:hidden">
                                <span class="flex items-center gap-2.5 text-[1.05rem] font-bold text-[#0a293c] md:text-[1.12rem]">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#1977b2]"><i class="fa-solid fa-clipboard-check text-xs"></i></span>
                                    <span<?php echo bioinmed_page_text_attr($servicePage, 'service', 'habilect.sections.metrics.title'); ?>><?php echo e($serviceHabilectSections['metrics']['title'] ?? ''); ?></span>
                                </span>
                                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#cfe0ef] bg-white text-[#0a293c]">
                                    <i class="fa-solid fa-chevron-down text-[0.72rem] transition group-open:rotate-180"></i>
                                </span>
                            </summary>
                            <div class="space-y-5 px-7 pb-7">
                                <p class="text-[0.96rem] leading-relaxed text-[#0a293c]"<?php echo bioinmed_page_text_attr($servicePage, 'service', 'habilect.sections.metrics.intro'); ?>><?php echo e($serviceHabilectSections['metrics']['intro'] ?? ''); ?></p>
                                <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                    <?php foreach ((is_array($serviceHabilectSections['metrics']['cards'] ?? null) ? $serviceHabilectSections['metrics']['cards'] : []) as $metricCardIndex => $metricCard): ?>
                                    <?php $metricCardKey = trim((string)($metricCard['id'] ?? ('card_' . $metricCardIndex))); ?>
                                    <div class="rounded-2xl border border-[#e4edf6] bg-white p-4 text-center">
                                        <p class="text-3xl font-bold text-[#1977b2]"<?php echo bioinmed_page_text_attr($servicePage, 'service', 'habilect.sections.metrics.cards.' . $metricCardKey . '.value'); ?>><?php echo e($metricCard['value'] ?? ''); ?></p>
                                        <p class="mt-2 text-sm leading-relaxed text-[#0a293c]"<?php echo bioinmed_page_text_attr($servicePage, 'service', 'habilect.sections.metrics.cards.' . $metricCardKey . '.text'); ?>><?php echo e($metricCard['text'] ?? ''); ?></p>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </details>

                    </div>
                </div>
                <?php else: ?>
                <!-- Как проходит приём и кому показана услуга -->
                <details class="fade-up group rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]" data-admin-block-root>
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-7 text-left marker:hidden">
                        <span class="flex items-center gap-2.5 text-xl font-bold text-[#0a293c]">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#1977b2]">
                                <i class="fa-solid fa-circle-play text-sm"></i>
                            </span>
                            <span<?php echo bioinmed_page_text_attr($servicePage, 'service', 'flow.title'); ?>><?php echo e($serviceFlowText['title'] ?? ''); ?></span>
                        </span>
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[#c9dff1] bg-white text-[#0a293c]">
                            <i class="fa-solid fa-chevron-down text-[0.82rem] transition group-open:rotate-180" aria-hidden="true"></i>
                        </span>
                    </summary>
                    <div class="space-y-5 px-7 pb-7">
                        <?php if ($serviceFlowDetailsNode['value'] !== ''): ?>
                        <p class="text-[0.96rem] leading-relaxed text-[#0a293c]"<?php echo $serviceFlowDetailsNode['attr']; ?>><?php echo e($serviceFlowDetailsNode['value']); ?></p>
                        <?php endif; ?>
                        <ol class="space-y-3"<?php echo bioinmed_editable_list_attrs('service', $serviceEditablePrefix . '.flow.steps', 'Этапы приёма', false); ?>>
                            <?php echo bioinmed_editable_list_toolbar(); ?>
                            <?php foreach ($flowSteps as $flowIndex => $flowStep): ?>
                            <li class="flex items-start gap-3<?php echo bioinmed_editable_list_item_class($flowStep); ?>"<?php echo bioinmed_editable_list_item_attrs($flowStep); ?>>
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#1977b2] text-xs font-bold text-white" data-admin-list-position><?php echo $flowIndex + 1; ?></span>
                                <span class="text-sm text-[#0a293c] mt-0.5" data-admin-list-text-view><?php echo e($flowStep['text']); ?></span>
                                <?php echo bioinmed_editable_list_actions($flowStep); ?>
                            </li>
                            <?php endforeach; ?>
                        </ol>
                        <?php if ($serviceFlowTargetNode['value'] !== ''): ?>
                        <div class="rounded-2xl border border-[#dce8f5] bg-[#f8fbff] p-4">
                            <h3 class="text-[0.92rem] font-semibold uppercase tracking-[0.14em] text-[#0a293c]"<?php echo bioinmed_page_text_attr($servicePage, 'service', 'flow.target_title'); ?>><?php echo e($serviceFlowText['target_title'] ?? ''); ?></h3>
                            <p class="mt-2 text-sm leading-relaxed text-[#0a293c]"<?php echo $serviceFlowTargetNode['attr']; ?>><?php echo e($serviceFlowTargetNode['value']); ?></p>
                            <div class="mt-4 rounded-xl border border-[#dce8f5] bg-white p-4 text-sm text-[#0a293c]">
                                <i class="fa-solid fa-circle-info text-[#1977b2] mr-2"></i>
                                <?php echo e($serviceFlowText['target_note'] ?? ''); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </details>
                <?php endif; ?>

                <!-- Why BIOINMED -->
                <details class="fade-up group rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]" data-admin-block-root>
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 text-left marker:hidden md:p-6">
                        <span class="flex items-center gap-2.5 text-[1.1rem] font-bold text-[#0a293c] md:text-[1.22rem]">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#dceefb] text-[#1977b2]">
                                <i class="fa-solid fa-award text-sm"></i>
                            </span>
                            <span<?php echo bioinmed_page_text_attr($servicePage, 'service', 'about.title'); ?>><?php echo e($serviceAboutText['title'] ?? ''); ?></span>
                        </span>
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[#c9dff1] bg-white text-[#0a293c]">
                            <i class="fa-solid fa-chevron-down text-[0.82rem] transition group-open:rotate-180" aria-hidden="true"></i>
                        </span>
                    </summary>
                    <div class="px-5 pb-5 md:px-6 md:pb-6">
                        <ul class="grid gap-3 sm:grid-cols-2"<?php echo bioinmed_editable_list_attrs('service', $serviceEditablePrefix . '.about.items', 'Преимущества клиники'); ?>>
                            <?php echo bioinmed_editable_list_toolbar(); ?>
                            <?php foreach ($aboutItems as $aboutItem): ?>
                            <li class="flex items-start gap-3 rounded-xl border border-[#e4edf6] bg-white p-3 text-[0.9rem] text-[#0a293c] md:text-[0.92rem]<?php echo bioinmed_editable_list_item_class($aboutItem); ?>"<?php echo bioinmed_editable_list_item_attrs($aboutItem); ?>>
                                <i class="<?php echo e($aboutItem['icon']); ?> mt-0.5 text-[#1977b2]" data-admin-list-icon-view></i><span data-admin-list-text-view><?php echo e($aboutItem['text']); ?></span><?php echo bioinmed_editable_list_actions($aboutItem); ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </details>

                <!-- FAQ mini -->
                <details class="fade-up group rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]" data-admin-block-root>
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 text-left marker:hidden md:p-6">
                        <span class="flex items-center gap-2.5 text-[1.1rem] font-bold text-[#0a293c] md:text-[1.22rem]">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#1977b2]">
                                <i class="fa-solid fa-circle-question text-sm"></i>
                            </span>
                            <span<?php echo bioinmed_page_text_attr($servicePage, 'service', 'faq.title'); ?>><?php echo e($serviceFaqText['title'] ?? ''); ?></span>
                        </span>
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[#c9dff1] bg-white text-[#0a293c]">
                            <i class="fa-solid fa-chevron-down text-[0.82rem] transition group-open:rotate-180" aria-hidden="true"></i>
                        </span>
                    </summary>
                    <div class="space-y-4 px-5 pb-5 md:px-6 md:pb-6" id="faq-list"<?php echo bioinmed_editable_list_attrs('service', $serviceEditablePrefix . '.faq.items', 'Часто задаваемые вопросы', false, 'Ответ'); ?>>
                        <?php echo bioinmed_editable_list_toolbar('div'); ?>
                        <?php foreach ($serviceFaqItems as $faq): ?>
                        <details class="group rounded-2xl border border-[#e4edf6] bg-white<?php echo bioinmed_editable_list_item_class($faq); ?>" data-admin-block-root<?php echo bioinmed_editable_list_item_attrs($faq); ?>>
                            <summary class="flex cursor-pointer items-center justify-between gap-4 p-4 text-[0.98rem] font-semibold text-[#0a293c] marker:hidden list-none md:p-5 md:text-[1.04rem]">
                                <span data-admin-list-text-view><?php echo e($faq['text']); ?></span>
                                <i class="fa-solid fa-chevron-down shrink-0 text-xs text-[#1977b2] transition-transform group-open:rotate-180"></i>
                            </summary>
                            <p class="px-4 pb-4 text-[0.92rem] leading-relaxed text-[#0a293c] md:px-5 md:pb-5 md:text-[0.96rem]" data-admin-list-secondary-view><?php echo e($faq['secondary']); ?></p>
                            <?php echo bioinmed_editable_list_actions($faq); ?>
                        </details>
                        <?php endforeach; ?>
                    </div>
                </details>

                <!-- Related services -->
                <?php if (!empty($related)): ?>
                <div class="fade-up" data-admin-block-root>
                    <h2 class="text-xl font-bold text-[#0a293c]"<?php echo bioinmed_page_text_attr($servicePage, 'service', 'related.title'); ?>><?php echo e($serviceRelatedText['title'] ?? ''); ?></h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <?php foreach (array_slice($related, 0, 4) as $rel): ?>
                        <?php
                            $relId = (string)($rel['id'] ?? '');
                            $relNameNode = bioinmed_page_text_node($servicePage, 'service', 'related.items.' . $relId . '.name', (string)($rel['name'] ?? ''));
                            $relDescNode = bioinmed_page_text_node($servicePage, 'service', 'related.items.' . $relId . '.description', service_card_excerpt((string)($rel['card_description'] ?? $rel['description'] ?? ''), 72));
                            $relActualPrice = bioinmed_service_actual_price_parts($rel);
                            $relPriceNode = bioinmed_page_text_node($servicePage, 'service', 'related.items.' . $relId . '.price', (string)($relActualPrice['price'] ?? ($rel['price'] ?? '')));
                            $relPriceNode['value'] = (string)($relActualPrice['price'] ?? ($rel['price'] ?? ''));
                            $relCtaNode = bioinmed_page_text_node($servicePage, 'service', 'related.items.' . $relId . '.cta', bioinmed_text('common.more_details'));
                        ?>
                        <a href="/services/<?php echo e($rel['id']); ?>"
                           class="flex flex-col justify-between rounded-2xl border border-[#dce8f5] bg-white p-5 hover:border-[#1977b2] hover:shadow-md transition-all"
                           data-admin-block-root>
                            <div>
                                <p class="text-sm font-semibold leading-snug text-[#0a293c]"<?php echo $relNameNode['attr']; ?>><?php echo e($relNameNode['value']); ?></p>
                                <p class="mt-1 text-xs text-[#0a293c]"<?php echo $relDescNode['attr']; ?>><?php echo e($relDescNode['value']); ?></p>
                            </div>
                            <div class="mt-4 flex items-center justify-between">
                                <span class="text-sm font-bold text-[#1977b2]"<?php echo $relPriceNode['attr']; ?>><?php echo e($relPriceNode['value']); ?></span>
                                <span class="text-xs font-semibold text-[#1977b2]"><span<?php echo $relCtaNode['attr']; ?>><?php echo e($relCtaNode['value']); ?></span> <i class="fa-solid fa-arrow-right text-[0.65rem]"></i></span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

                <!-- right: media + booking -->
                <div class="order-first fade-up space-y-4 lg:order-2" style="transition-delay:.07s">
                    <?php if ($servicePrimaryImage): ?>
                    <div class="overflow-hidden rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_12px_30px_rgba(8,36,70,0.10)]" data-admin-block-root>
                        <div class="service-main-image-frame relative aspect-[5/4] overflow-hidden bg-[#edf7ff]">
                            <img src="<?php echo e($servicePrimaryImage); ?>"
                                 alt="<?php echo e($service['name']); ?>"
                                 id="service-main-image"
                                 class="service-main-image-live is-animating h-full w-full cursor-zoom-in object-cover"
                                 loading="eager"
                                 fetchpriority="high"
                                 decoding="async">
                            <button type="button"
                                    id="service-image-zoom"
                                    class="absolute right-4 top-4 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/88 text-[#1b5c99] shadow-[0_10px_24px_rgba(8,36,70,0.12)] transition hover:bg-white"
                                    aria-label="<?php echo e($serviceImageModal['zoom_label'] ?? ''); ?>">
                                <i class="fa-solid fa-magnifying-glass-plus text-[#1977b2]"></i>
                            </button>
                        </div>
                        <?php if (!empty($serviceGallery)): ?>
                        <div class="grid grid-cols-4 gap-2 border-t border-[#e7eef6] bg-[#f8fbff] p-3">
                            <?php foreach ($serviceGallery as $galleryIndex => $galleryImage): ?>
                            <button type="button"
                                    class="service-gallery-thumb <?php echo $galleryIndex === 0 ? 'is-active' : ''; ?> overflow-hidden rounded-2xl border border-[#dfe7f1] bg-white aspect-[4/3] transition hover:border-[#1977b2]"
                                    data-image-src="<?php echo e($galleryImage); ?>"
                                    data-image-alt="<?php echo e($service['name']); ?>">
                                <img src="<?php echo e($galleryImage); ?>"
                                     alt="<?php echo e($service['name']); ?>"
                                     class="h-full w-full object-cover"
                                     loading="lazy"
                                     decoding="async">
                            </button>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div id="book" class="rounded-3xl border border-[#d9e7f3] bg-white p-6 shadow-[0_12px_30px_rgba(8,36,70,0.10)]" data-admin-block-root>
                        <div class="flex items-end gap-2 border-b border-[#eaf1f8] pb-5">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#4b6f9a]"<?php echo bioinmed_page_text_attr($servicePage, 'service', 'sidebar.price_title'); ?>><?php echo e($serviceSidebarText['price_title'] ?? ''); ?></p>
                                <p class="mt-1 text-3xl font-bold text-[#0a293c]"<?php echo $serviceSidebarPriceNode['attr']; ?>><?php echo e($serviceSidebarPriceNode['value']); ?></p>
                                <?php if ($serviceSidebarPriceNoteNode['value'] !== ''): ?>
                                <p class="mt-0.5 text-sm text-[#0a293c]"<?php echo $serviceSidebarPriceNoteNode['attr']; ?>><?php echo e($serviceSidebarPriceNoteNode['value']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mt-5">
                            <?php echo bioinmed_render_callback_form([
                                'source_label' => ($service['name'] ?? ($serviceDefault['source_service_label'] ?? '')) . (string)($serviceSidebarText['source_suffix'] ?? ''),
                                'button_class' => 'inline-flex w-full items-center justify-center rounded-full bg-[#1977b2] px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-[#16658f] disabled:cursor-not-allowed disabled:bg-[#a7d7e9] disabled:text-white/90',
                            ]); ?>
                        </div>

                        <div class="my-4 flex items-center gap-3">
                            <div class="h-px grow bg-[#e2ecf5]"></div>
                            <span class="text-xs text-[#0a293c]"><?php echo e($serviceSidebarText['call_label'] ?? ''); ?></span>
                            <div class="h-px grow bg-[#e2ecf5]"></div>
                        </div>
                        <a href="tel:<?php echo $phone1link; ?>"
                           class="flex items-center justify-center gap-2 rounded-full border-2 border-[#1977b2] px-5 py-2.5 text-sm font-bold text-[#1977b2] hover:bg-[#f0f8ff]">
                            <i class="fa-solid fa-phone-volume"></i> <?php echo e($phone1); ?>
                        </a>
                    </div>

                    <div class="rounded-3xl border border-[#d9e7f3] bg-white p-6 shadow-[0_12px_30px_rgba(8,36,70,0.10)]" data-admin-block-root>
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#4b6f9a]"<?php echo bioinmed_page_text_attr($servicePage, 'service', 'sidebar.visit_prep_title'); ?>><?php echo e($serviceSidebarText['visit_prep_title'] ?? ''); ?></p>
                        <ul class="mt-3 space-y-2.5 text-sm text-[#0a293c]"<?php echo bioinmed_editable_list_attrs('service', $serviceEditablePrefix . '.sidebar.visit_prep_items', 'Подготовка к визиту'); ?>>
                            <?php echo bioinmed_editable_list_toolbar(); ?>
                            <?php foreach ($visitPrepItems as $visitPrepItem): ?>
                            <li class="flex items-start gap-3<?php echo bioinmed_editable_list_item_class($visitPrepItem); ?>"<?php echo bioinmed_editable_list_item_attrs($visitPrepItem); ?>>
                                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#e8f3fc] text-[#1977b2]"><i class="<?php echo e($visitPrepItem['icon']); ?> text-[0.68rem]" data-admin-list-icon-view></i></span>
                                <span data-admin-list-text-view><?php echo e($visitPrepItem['text']); ?></span><?php echo bioinmed_editable_list_actions($visitPrepItem); ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="rounded-3xl border border-[#d9e7f3] bg-white p-5" data-admin-block-root>
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#0a293c]"<?php echo bioinmed_page_text_attr($servicePage, 'service', 'sidebar.clinic_title'); ?>><?php echo e($serviceSidebarText['clinic_title'] ?? ''); ?></p>
                        <ul class="mt-3 space-y-2.5 text-sm text-[#0a293c]">
                            <li class="flex items-start gap-2.5">
                                <i class="fa-solid fa-location-dot mt-0.5 shrink-0 text-[#1977b2]"></i>
                                <span><?php echo e(CLINIC_ADDRESS); ?>, <?php echo e(CLINIC_METRO); ?></span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <i class="fa-solid fa-clock mt-0.5 shrink-0 text-[#1977b2]"></i>
                                <span><?php echo e(CLINIC_HOURS); ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== FINAL CTA STRIP ===== -->
    <section class="border-y border-[#e4edf6] bg-[#e4f1fa] py-12">
        <div class="mx-auto max-w-6xl px-6 text-center md:px-10" data-admin-block-root>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0a293c]"<?php echo bioinmed_page_text_attr($servicePage, 'service', 'final_cta.eyebrow'); ?>><?php echo e($serviceFinalCta['eyebrow'] ?? ''); ?></p>
            <h2 class="mt-3 text-xl font-bold text-[#0a293c] md:text-2xl"<?php echo bioinmed_page_text_attr($servicePage, 'service', 'final_cta.title'); ?>><?php echo e($serviceFinalCta['title'] ?? ''); ?></h2>
            <p class="mx-auto mt-3 max-w-xl text-sm text-[#0a293c]"<?php echo bioinmed_page_text_attr($servicePage, 'service', 'final_cta.text'); ?>>
                <?php echo e($serviceFinalCta['text'] ?? ''); ?>
            </p>
            <div class="mx-auto mt-6 max-w-md">
                <?php echo bioinmed_render_callback_form([
                    'source_label' => ($service['name'] ?? ($serviceDefault['source_service_label'] ?? '')) . (string)($serviceFinalCta['source_suffix'] ?? ''),
                    'submit_label' => bioinmed_text('common.book_appointment'),
                    'button_class' => 'inline-flex w-full items-center justify-center rounded-full bg-[#1977b2] px-7 py-3 text-sm font-semibold text-white transition hover:bg-[#16658f] disabled:cursor-not-allowed disabled:bg-[#a7d7e9] disabled:text-white/90',
                ]); ?>
            </div>
            <div class="mt-4 flex flex-wrap justify-center gap-3">
                <a href="tel:<?php echo $phone1link; ?>" class="rounded-full border border-[#c9dcee] bg-white px-7 py-3 text-sm font-semibold text-[#0a293c] hover:border-[#1977b2] hover:text-[#1977b2]">
                    <i class="fa-solid fa-phone mr-1.5"></i><?php echo e($phone1); ?>
                </a>
                <a href="<?php echo e(bioinmed_link('nav.prices')['url']); ?>" class="rounded-full border border-[#c9dcee] bg-white px-7 py-3 text-sm font-semibold text-[#0a293c] hover:border-[#1977b2] hover:text-[#1977b2]">
                    <i class="fa-solid fa-list mr-1.5"></i><?php echo e($serviceFinalCta['prices_button'] ?? ''); ?>
                </a>
            </div>
        </div>
    </section>

</main>
<?php endif; ?>

<?php if ($servicePrimaryImage): ?>
<div id="service-image-modal" class="fixed inset-0 z-[100] hidden bg-[rgba(7,21,40,0.82)] px-4 py-6">
    <button type="button" id="service-image-modal-close" class="absolute right-5 top-5 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20" aria-label="<?php echo e($serviceImageModal['close_label'] ?? ''); ?>">
        <i class="fa-solid fa-xmark text-lg"></i>
    </button>
    <div class="mx-auto flex h-full max-w-6xl items-center justify-center">
        <img id="service-image-modal-image" src="<?php echo e($servicePrimaryImage); ?>" alt="<?php echo e($service['name']); ?>" class="max-h-full max-w-full rounded-3xl border border-white/15 bg-white/5 object-contain shadow-[0_18px_48px_rgba(0,0,0,0.35)]">
    </div>
</div>
<?php endif; ?>

<?php
$footer = new Footer($brand_colors);
echo $footer->render();
?>

<script>
    // Fade-up on scroll
    document.querySelectorAll('.fade-up').forEach(function(el) {
        const obs = new IntersectionObserver(function(entries) {
            entries.forEach(function(e) { if (e.isIntersecting) { el.classList.add('visible'); obs.unobserve(el); } });
        }, { threshold: 0.08, rootMargin: '0px 0px -30px 0px' });
        obs.observe(el);
    });

    const serviceGallery = <?php echo $serviceGalleryJson ?: '[]'; ?>;
    const mainImage = document.getElementById('service-main-image');
    const zoomButton = document.getElementById('service-image-zoom');
    const galleryThumbs = Array.from(document.querySelectorAll('.service-gallery-thumb'));
    const imageModal = document.getElementById('service-image-modal');
    const imageModalImage = document.getElementById('service-image-modal-image');
    const imageModalClose = document.getElementById('service-image-modal-close');

    function restartServiceImageAnimation() {
        if (!mainImage || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        mainImage.classList.remove('is-animating');
        void mainImage.offsetWidth;
        mainImage.classList.add('is-animating');
    }

    function setActiveGalleryImage(src, alt) {
        if (!mainImage || !src) return;
        mainImage.src = src;
        mainImage.alt = alt || mainImage.alt;
        restartServiceImageAnimation();
        if (imageModalImage) {
            imageModalImage.src = src;
            imageModalImage.alt = alt || imageModalImage.alt;
        }
        galleryThumbs.forEach(function(button) {
            const isActive = button.dataset.imageSrc === src;
            button.classList.toggle('is-active', isActive);
        });
    }

    galleryThumbs.forEach(function(button) {
        button.addEventListener('click', function() {
            setActiveGalleryImage(button.dataset.imageSrc, button.dataset.imageAlt || '');
        });
    });

    function openImageModal() {
        if (!imageModal || !mainImage) return;
        imageModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        if (imageModalImage) {
            imageModalImage.src = mainImage.src;
            imageModalImage.alt = mainImage.alt;
        }
    }

    function closeImageModal() {
        if (!imageModal) return;
        imageModal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function openDetailsTarget(hash) {
        if (!hash) return false;
        var target = document.querySelector(hash);
        if (!target) return false;

        var details = target.tagName && target.tagName.toLowerCase() === 'details'
            ? target
            : target.closest('details');

        if (details) {
            details.open = true;
            window.requestAnimationFrame(function() {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
            return true;
        }

        return false;
    }

    function syncDetailsAnchor() {
        if (!window.location.hash) return;
        openDetailsTarget(window.location.hash);
    }

    mainImage?.addEventListener('click', openImageModal);
    zoomButton?.addEventListener('click', openImageModal);
    imageModalClose?.addEventListener('click', closeImageModal);
    imageModal?.addEventListener('click', function(event) {
        if (event.target === imageModal) {
            closeImageModal();
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeImageModal();
        }
    });

    document.querySelectorAll('a[href^="#habilect-"]').forEach(function(link) {
        link.addEventListener('click', function(event) {
            var hash = link.getAttribute('href');
            if (!hash || hash === '#') return;
            if (openDetailsTarget(hash)) {
                event.preventDefault();
                history.replaceState(null, '', hash);
            }
        });
    });

    window.addEventListener('hashchange', syncDetailsAnchor);
    syncDetailsAnchor();

    restartServiceImageAnimation();
</script>
</body>
</html>
