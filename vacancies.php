<?php
require_once __DIR__ . '/includes/pin_protection.php';
bioinmed_pin_require_access();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/components/Components.php';
require_once __DIR__ . '/includes/content/EditableLists.php';
require_once __DIR__ . '/includes/content/AboutSectionNav.php';

$e = static function ($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$vacanciesPage = bioinmed_read_json_file('pages/vacancies.json');
$vacanciesMeta = is_array($vacanciesPage['meta'] ?? null) ? $vacanciesPage['meta'] : [];
$vacanciesHero = is_array($vacanciesPage['hero'] ?? null) ? $vacanciesPage['hero'] : [];
$vacanciesValues = is_array($vacanciesPage['values'] ?? null) ? $vacanciesPage['values'] : [];
$vacanciesDirections = is_array($vacanciesPage['directions'] ?? null) ? $vacanciesPage['directions'] : [];
$vacanciesRoles = is_array($vacanciesDirections['roles'] ?? null) ? $vacanciesDirections['roles'] : [];
$vacanciesResponse = is_array($vacanciesPage['response'] ?? null) ? $vacanciesPage['response'] : [];
$vacanciesSubmitNode = bioinmed_page_text_node($vacanciesPage, 'vacancies', 'response.submit_label', 'Откликнуться');
$vacanciesValueFallback = [];
foreach ($vacanciesValues as $valueKey => $value) {
    if (!is_array($value)) continue;
    $vacanciesValueFallback[] = [
        'id' => (string)$valueKey,
        'text' => (string)($value['title'] ?? ''),
        'secondary' => (string)($value['text'] ?? ''),
        'icon' => (string)($value['icon'] ?? 'fa-solid fa-heart-pulse'),
    ];
}
$vacanciesValueItems = bioinmed_editable_list_items($vacanciesPage, 'vacancies.values', $vacanciesValueFallback, 'fa-solid fa-heart-pulse');
$vacanciesRoleFallback = [];
foreach ($vacanciesRoles as $roleKey => $role) {
    if (!is_array($role)) continue;
    $vacanciesRoleFallback[] = [
        'id' => (string)$roleKey,
        'text' => (string)($role['title'] ?? ''),
        'icon' => (string)($role['icon'] ?? 'fa-solid fa-user-doctor'),
    ];
}
$vacanciesRoleItems = bioinmed_editable_list_items($vacanciesPage, 'vacancies.directions.roles', $vacanciesRoleFallback, 'fa-solid fa-user-doctor');
$siteUrl = rtrim(CLINIC_SITE_URL, '/');
$canonicalUrl = $siteUrl . '/vacancies';
$pageTitle = trim((string)($vacanciesMeta['title'] ?? 'Вакансии клиники')) . ' | ' . CLINIC_NAME;
$pageDescription = trim((string)($vacanciesMeta['description'] ?? 'Работа в клинике БИОИНМЕД: направления сотрудничества, ценности команды и форма отклика.'));
$structuredData = bioinmed_medical_organization_schema();
$breadcrumbStructuredData = bioinmed_breadcrumb_schema([
    ['name' => 'Главная', 'url' => $siteUrl . '/'], ['name' => 'О клинике', 'url' => $siteUrl . '/about'], ['name' => 'Вакансии', 'url' => $canonicalUrl],
]);
?>
<!doctype html><html lang="ru"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $e($pageTitle); ?></title><meta name="description" content="<?php echo $e($pageDescription); ?>"><meta name="robots" content="index,follow">
<link rel="canonical" href="<?php echo $e($canonicalUrl); ?>"><meta name="theme-color" content="#1977b2">
<?php echo bioinmed_render_social_meta($pageTitle, $pageDescription, $canonicalUrl, ['image' => bioinmed_default_social_image_url()]); ?>
<?php echo bioinmed_render_favicon_links(CLINIC_ICON_PATH); ?><?php echo bioinmed_render_public_head_assets(); ?>
<script type="application/ld+json"><?php echo json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
<script type="application/ld+json"><?php echo json_encode($breadcrumbStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
<?php echo bioinmed_uis_counter_head(); ?></head>
<body class="min-h-screen bg-[#e4f1fa] text-[#0f2749] antialiased">
<?php echo bioinmed_yandex_metrika_noscript(); ?><?php echo (new Header($brand_colors))->render(); ?>
<main class="mx-auto max-w-6xl px-6 py-8 md:px-10 md:py-12">
    <?php echo bioinmed_render_about_breadcrumbs('Вакансии'); ?>
    <section class="mt-6 overflow-hidden rounded-3xl border border-[#d8e6f3] bg-white p-5 shadow-[0_14px_36px_rgba(8,36,70,0.08)] md:p-8" style="background:linear-gradient(135deg,#ffffff 0%,#edf6fd 100%)" data-admin-block-root>
        <div class="max-w-4xl"><p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-[#1977b2]"<?php echo bioinmed_page_text_attr($vacanciesPage, 'vacancies', 'hero.eyebrow'); ?>><?php echo $e($vacanciesHero['eyebrow'] ?? 'Работа в БИОИНМЕД'); ?></p><h1 class="mt-2 text-[1.8rem] font-bold leading-tight text-[#0f2749] md:text-[2.6rem]"<?php echo bioinmed_page_text_attr($vacanciesPage, 'vacancies', 'hero.heading'); ?>><?php echo $e($vacanciesHero['heading'] ?? 'Присоединяйтесь к нашей команде'); ?></h1><p class="mt-4 text-[1rem] leading-relaxed text-[#355b89]"<?php echo bioinmed_page_text_attr($vacanciesPage, 'vacancies', 'hero.intro'); ?>><?php echo $e($vacanciesHero['intro'] ?? ''); ?></p></div>
    </section>
    <section class="mt-8 grid gap-4 md:grid-cols-3"<?php echo bioinmed_editable_list_attrs('vacancies', 'vacancies.values', 'Ценности команды', true, 'Описание'); ?>>
        <?php echo bioinmed_editable_list_toolbar('div'); ?>
        <?php foreach ($vacanciesValueItems as $value): ?><article class="rounded-2xl border border-[#d9e7f3] bg-white p-5 shadow-[0_8px_18px_rgba(8,36,70,0.05)]<?php echo bioinmed_editable_list_item_class($value); ?>" data-admin-block-root<?php echo bioinmed_editable_list_item_attrs($value); ?>><span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-[#e8f3fc] text-[#1977b2]"><i class="<?php echo $e($value['icon'] ?: 'fa-solid fa-heart-pulse'); ?>" data-admin-list-icon-view></i></span><h2 class="mt-4 text-[1.05rem] font-bold text-[#0f2749]" data-admin-list-text-view><?php echo $e($value['text']); ?></h2><p class="mt-2 text-[0.9rem] leading-relaxed text-[#355b89]" data-admin-list-secondary-view><?php echo $e($value['secondary']); ?></p><?php echo bioinmed_editable_list_actions($value); ?></article><?php endforeach; ?>
    </section>
    <section class="mt-8" data-admin-block-root><p class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-[#1977b2]"<?php echo bioinmed_page_text_attr($vacanciesPage, 'vacancies', 'directions.eyebrow'); ?>><?php echo $e($vacanciesDirections['eyebrow'] ?? 'Направления'); ?></p><h2 class="mt-2 text-[1.4rem] font-bold text-[#0f2749] md:text-[1.75rem]"<?php echo bioinmed_page_text_attr($vacanciesPage, 'vacancies', 'directions.heading'); ?>><?php echo $e($vacanciesDirections['heading'] ?? 'С кем мы готовы знакомиться'); ?></h2><p class="mt-2 max-w-3xl text-[0.9rem] leading-relaxed text-[#355b89]"<?php echo bioinmed_page_text_attr($vacanciesPage, 'vacancies', 'directions.intro'); ?>><?php echo $e($vacanciesDirections['intro'] ?? ''); ?></p></section>
    <section class="mt-5 grid gap-5 lg:grid-cols-3"<?php echo bioinmed_editable_list_attrs('vacancies', 'vacancies.directions.roles', 'Направления вакансий', true); ?>>
        <?php echo bioinmed_editable_list_toolbar('div'); ?>
        <?php foreach ($vacanciesRoleItems as $role): ?>
            <?php
                $roleId = (string)$role['id'];
                $roleSource = is_array($vacanciesRoles[$roleId] ?? null) ? $vacanciesRoles[$roleId] : [];
                $requirementFallback = [];
                foreach ((is_array($roleSource['items'] ?? null) ? $roleSource['items'] : []) as $requirementIndex => $requirementText) {
                    $requirementFallback[] = [
                        'id' => 'requirement-' . ($requirementIndex + 1),
                        'text' => (string)$requirementText,
                        'icon' => 'fa-solid fa-check',
                    ];
                }
                $requirementListKey = 'vacancies.directions.role_items.' . $roleId;
                $requirements = bioinmed_editable_list_items($vacanciesPage, $requirementListKey, $requirementFallback, 'fa-solid fa-check');
            ?>
            <article class="rounded-2xl border border-[#d9e7f3] bg-white p-5 shadow-[0_10px_24px_rgba(8,36,70,0.07)]<?php echo bioinmed_editable_list_item_class($role); ?>" data-admin-block-root<?php echo bioinmed_editable_list_item_attrs($role); ?>>
                <div class="flex items-center gap-3"><span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[#1977b2] text-white"><i class="<?php echo $e($role['icon'] ?: 'fa-solid fa-user-doctor'); ?>" data-admin-list-icon-view></i></span><h3 class="font-bold leading-tight text-[#0f2749]" data-admin-list-text-view><?php echo $e($role['text']); ?></h3></div>
                <ul class="mt-4 space-y-3 text-[0.88rem] leading-relaxed text-[#355b89]"<?php echo bioinmed_editable_list_attrs('vacancies', $requirementListKey, 'Требования: ' . $role['text'], true); ?>>
                    <?php echo bioinmed_editable_list_toolbar(); ?>
                    <?php foreach ($requirements as $requirement): ?><li class="flex gap-2<?php echo bioinmed_editable_list_item_class($requirement); ?>"<?php echo bioinmed_editable_list_item_attrs($requirement); ?>><i class="<?php echo $e($requirement['icon'] ?: 'fa-solid fa-check'); ?> mt-1 text-[0.72rem] text-[#1977b2]" data-admin-list-icon-view></i><span data-admin-list-text-view><?php echo $e($requirement['text']); ?></span><?php echo bioinmed_editable_list_actions($requirement); ?></li><?php endforeach; ?>
                </ul>
                <?php echo bioinmed_editable_list_actions($role); ?>
            </article>
        <?php endforeach; ?>
    </section>
    <section class="mt-8 overflow-hidden rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_12px_30px_rgba(8,36,70,0.09)]"><div class="grid gap-0 lg:grid-cols-2"><div class="p-5 md:p-7" data-admin-block-root><p class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-[#1977b2]"<?php echo bioinmed_page_text_attr($vacanciesPage, 'vacancies', 'response.eyebrow'); ?>><?php echo $e($vacanciesResponse['eyebrow'] ?? 'Отклик'); ?></p><h2 class="mt-2 text-[1.35rem] font-bold text-[#0f2749]"<?php echo bioinmed_page_text_attr($vacanciesPage, 'vacancies', 'response.heading'); ?>><?php echo $e($vacanciesResponse['heading'] ?? 'Расскажите о себе'); ?></h2><p class="mt-3 text-[0.92rem] leading-relaxed text-[#355b89]"<?php echo bioinmed_page_text_attr($vacanciesPage, 'vacancies', 'response.text'); ?>><?php echo $e($vacanciesResponse['text'] ?? ''); ?></p><a href="mailto:<?php echo $e(CLINIC_EMAIL); ?>?subject=<?php echo rawurlencode((string)($vacanciesResponse['email_subject'] ?? 'Отклик на вакансию в БИОИНМЕД')); ?>" class="mt-5 inline-flex items-center gap-2 rounded-full border border-[#c7dbed] bg-white px-4 py-2.5 text-[0.86rem] font-semibold text-[#17446f] transition hover:border-[#8fbde0] hover:text-[#1977b2]"><i class="fa-solid fa-envelope"></i><?php echo $e(CLINIC_EMAIL); ?></a></div><div class="border-t border-[#e1ebf4] p-5 md:p-7 lg:border-l lg:border-t-0"><?php echo bioinmed_render_callback_form(['source_label' => 'Вакансии — отклик', 'submit_label' => $vacanciesSubmitNode['value'], 'submit_label_attr' => $vacanciesSubmitNode['attr']]); ?></div></div></section>
</main>
<?php echo (new Footer($brand_colors))->render(); ?>
</body></html>
