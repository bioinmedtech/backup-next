<?php
require_once __DIR__ . '/includes/pin_protection.php';
bioinmed_pin_require_access();


require_once 'config.php';
require_once 'includes/components/Components.php';
require_once 'includes/content/EditableLists.php';
require_once 'includes/content/AboutSectionNav.php';

$aboutPage = bioinmed_read_json_file('pages/about.json');
$aboutMeta = is_array($aboutPage['meta'] ?? null) ? $aboutPage['meta'] : [];
$aboutHero = is_array($aboutPage['hero'] ?? null) ? $aboutPage['hero'] : [];
$aboutAddress = is_array($aboutPage['address'] ?? null) ? $aboutPage['address'] : [];
$aboutTasks = is_array($aboutPage['tasks'] ?? null) ? $aboutPage['tasks'] : [];
$aboutTaskItems = is_array($aboutTasks['items'] ?? null) ? $aboutTasks['items'] : [];
$aboutHeroParagraphEntries = is_array($aboutHero['paragraphs'] ?? null) ? $aboutHero['paragraphs'] : [];
$aboutHeroParagraphEntries = bioinmed_editable_list_items($aboutPage, 'about.hero.paragraphs', $aboutHeroParagraphEntries, '');
$aboutTaskFallback = [];
foreach ($aboutTaskItems as $taskIndex => $task) {
	$aboutTaskFallback[] = [
		'id' => (string)($task['id'] ?? ('task-' . $taskIndex)),
		'text' => (string)($task['title'] ?? ''),
		'secondary' => (string)($task['text'] ?? ''),
	];
}
$aboutTaskItems = bioinmed_editable_list_items($aboutPage, 'about.tasks.items', $aboutTaskFallback, '');
$aboutChiefQuote = is_array($aboutPage['chief_quote'] ?? null) ? $aboutPage['chief_quote'] : [];
$aboutCta = is_array($aboutPage['cta'] ?? null) ? $aboutPage['cta'] : [];
$aboutCtaHeadingNode = bioinmed_page_text_node($aboutPage, 'about', 'cta.heading', 'Запишитесь онлайн — прямо сейчас');
$aboutContactHoursNode = bioinmed_page_text_node($aboutPage, 'about', 'contact_values.hours', CLINIC_HOURS);
$aboutContactPhoneNode = bioinmed_page_text_node($aboutPage, 'about', 'contact_values.phone', CLINIC_PHONE);
$aboutContactMetroNode = bioinmed_page_text_node($aboutPage, 'about', 'contact_values.metro', CLINIC_METRO);

$siteUrl = rtrim(CLINIC_SITE_URL, '/');
$iconPath = CLINIC_ICON_PATH;
$socialImageUrl = bioinmed_default_social_image_url();
$canonicalUrl = $siteUrl . '/about';
$pageTitle = trim((string)($aboutMeta['title'] ?? 'О клинике')) . ' | ' . CLINIC_NAME;
$pageDescription = trim((string)($aboutMeta['description'] ?? 'Клиника гомеопатии и биорегуляции: интегративная медицина, диагностика первопричин и персональные программы восстановления.'));

$structuredData = bioinmed_medical_organization_schema();
$breadcrumbStructuredData = bioinmed_breadcrumb_schema([
	['name' => 'Главная', 'url' => $siteUrl . '/'],
	['name' => trim((string)($aboutMeta['title'] ?? 'О клинике')), 'url' => $canonicalUrl],
]);

$bookingUrl = defined('ONLINE_BOOKING_URL') ? ONLINE_BOOKING_URL : '/';
$chief = $doctors[0] ?? [];
$chiefName = trim((string)($chief['name'] ?? 'Инна Викторовна Костромина'));
$chiefImage = '/public/images/team/kostromina-default.webp';
$mapUrl = CLINIC_MAP_URL;
$aboutHabilectLink = bioinmed_link('services.habilect_diagnostics');
$aboutPhysioLink = bioinmed_link('services.fizioterapiya');
$aboutAcupunctureLink = bioinmed_link('services.acupuncture');
if (!empty($chief['image'])) {
	$chiefImage = bioinmed_preferred_image_asset_path('/public/images/team/' . ltrim((string)$chief['image'], '/'));
}


$aboutServiceShowcase = [
	[
		'image' => '/public/images/services/habilect.jpg',
		'alt' => 'Диагностика «Хабилект» в БИОИНМЕД',
		'title' => 'Диагностика «Хабилект»',
		'desc' => 'Флагманская диагностика клиники: помогает увидеть перегрузки и ограничения опорно-двигательного аппарата за один визит.',
		'href' => $aboutHabilectLink['url'],
	],
	[
		'image' => '/public/images/services/physiotherapy-treatment.jpg',
		'alt' => 'Физиотерапия в БИОИНМЕД',
		'title' => 'Физиотерапия',
		'desc' => 'Аппаратные методики для снятия боли, улучшения микроциркуляции и ускорения восстановления после перегрузок.',
		'href' => $aboutPhysioLink['url'],
	],
	[
		'image' => '/public/images/services/acupuncture-therapy-6.jpg',
		'alt' => 'Рефлексотерапия и иглоукалывание в БИОИНМЕД',
		'title' => 'Рефлексотерапия и иглоукалывание',
		'desc' => 'Точечная работа с болью, вегетативной нервной системой и стрессовой нагрузкой для более устойчивого самочувствия.',
		'href' => $aboutAcupunctureLink['url'],
	],
];

function e($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
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
	<meta name="theme-color" content="#1977b2">
	<?php echo bioinmed_render_social_meta($pageTitle, $pageDescription, $canonicalUrl, [
		'image' => $socialImageUrl,
	]); ?>
	<?php echo bioinmed_render_favicon_links($iconPath); ?>
	<?php echo bioinmed_render_public_head_assets(); ?>
	<style>
		.bioinmed-editable-list-item-hidden,
		.bioinmed-editable-list-toolbar,
		.bioinmed-editable-list-actions { display: none !important; }
	</style>
	<script type="application/ld+json"><?php echo json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
	<script type="application/ld+json"><?php echo json_encode($breadcrumbStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
	<?php echo bioinmed_uis_counter_head(); ?>
</head>
<body class="min-h-screen bg-[#e4f1fa] text-[#0f2749] antialiased">
<?php echo bioinmed_yandex_metrika_noscript(); ?>
<?php
$header = new Header($brand_colors);
echo $header->render();
?>

<main class="mx-auto max-w-6xl space-y-8 px-6 py-8 md:space-y-10 md:px-10 md:py-12">
	<?php echo bioinmed_render_about_breadcrumbs('О клинике', true); ?>
	<section class="relative overflow-hidden rounded-[2rem] border border-[#d8e6f3] bg-[radial-gradient(circle_at_top_left,#ffffff_0%,#edf6fd_38%,#deedf8_100%)] p-5 shadow-[0_14px_36px_rgba(8,36,70,0.08)] md:p-7">
		<div class="pointer-events-none absolute -right-14 -top-14 h-44 w-44 rounded-full bg-[#1977b21e] blur-3xl"></div>
		<div class="pointer-events-none absolute -left-10 bottom-0 h-36 w-36 rounded-full bg-[#0f274914] blur-3xl"></div>
		<div class="relative grid gap-6 lg:grid-cols-[1.15fr_0.85fr] lg:items-center">
			<div data-admin-block-root>
				<p class="text-[0.72rem] font-semibold uppercase tracking-[0.2em] text-[#0a293c]"<?php echo bioinmed_page_text_attr($aboutPage, 'about', 'hero.eyebrow'); ?>><?php echo e($aboutHero['eyebrow'] ?? ''); ?></p>
					<h1 class="mt-2 text-[1.65rem] font-bold leading-[1.08] text-[#0f2749] md:text-[2.2rem]"<?php echo bioinmed_page_text_attr($aboutPage, 'about', 'hero.heading'); ?>><?php echo e($aboutHero['heading'] ?? 'Клиника'); ?></h1>
				<div class="mt-4 max-w-2xl space-y-2"<?php echo bioinmed_editable_list_attrs('about', 'about.hero.paragraphs', 'Описание клиники', false); ?>>
					<p class="text-[0.74rem] font-semibold uppercase tracking-[0.18em] text-[#1977b2]"<?php echo bioinmed_page_text_attr($aboutPage, 'about', 'hero.ecosystem_label'); ?>><?php echo e($aboutHero['ecosystem_label'] ?? ''); ?></p>
					<p class="text-[1.08rem] font-semibold leading-[1.42] text-[#0a293c] md:text-[1.1rem]"<?php echo bioinmed_page_text_attr($aboutPage, 'about', 'hero.ecosystem_title'); ?>>
						<?php echo e($aboutHero['ecosystem_title'] ?? ''); ?>
					</p>
					<?php echo bioinmed_editable_list_toolbar('div'); ?>
					<?php foreach ($aboutHeroParagraphEntries as $heroParagraphEntry): ?>
						<p class="text-[1rem] leading-[1.62] text-[#0a293c] md:text-[0.98rem]<?php echo bioinmed_editable_list_item_class($heroParagraphEntry); ?>"<?php echo bioinmed_editable_list_item_attrs($heroParagraphEntry); ?>><span data-admin-list-text-view><?php echo e($heroParagraphEntry['text']); ?></span><?php echo bioinmed_editable_list_actions($heroParagraphEntry); ?></p>
					<?php endforeach; ?>
				</div>
			</div>
			<aside class="rounded-3xl border border-[#d6e4f1] bg-white p-5 shadow-[0_16px_38px_rgba(8,36,70,0.13)]" data-admin-block-root>
				<p class="text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-[#0a293c]"<?php echo bioinmed_page_text_attr($aboutPage, 'about', 'address.title'); ?>><?php echo e($aboutAddress['title'] ?? 'Адрес клиники'); ?></p>
				<h2 class="mt-2 text-[1.25rem] font-bold leading-tight text-[#0f2749]"><?php echo e(CLINIC_ADDRESS); ?></h2>
								<p class="mt-1 text-[0.9rem] font-medium text-[#0a293c]"<?php echo $aboutContactMetroNode['attr']; ?>><?php echo e($aboutContactMetroNode['value']); ?></p>

				<div class="mt-4 space-y-3 rounded-2xl border border-[#dce8f4] bg-[#e4f1fa] p-4">
					<div class="flex items-start gap-2 text-[0.86rem] text-[#0a293c]">
						<i class="fa-solid fa-location-dot mt-0.5 text-[#1977b2]"></i>
						<span<?php echo bioinmed_page_text_attr($aboutPage, 'about', 'address.transport_text'); ?>><?php echo e($aboutAddress['transport_text'] ?? ''); ?></span>
					</div>
					<div class="flex items-start gap-2 text-[0.86rem] text-[#0a293c]">
						<i class="fa-solid fa-clock mt-0.5 text-[#1977b2]"></i>
								<span<?php echo $aboutContactHoursNode['attr']; ?>><?php echo e($aboutContactHoursNode['value']); ?></span>
					</div>
					<div class="flex items-start gap-2 text-[0.86rem] text-[#0a293c]">
						<i class="fa-solid fa-phone mt-0.5 text-[#1977b2]"></i>
						<span<?php echo $aboutContactPhoneNode['attr']; ?>><?php echo e($aboutContactPhoneNode['value']); ?></span>
					</div>
				</div>

				<div class="mt-4 grid gap-2 sm:grid-cols-2">
					<a href="<?php echo e($mapUrl); ?>" target="_blank" rel="noreferrer noopener" class="inline-flex items-center justify-center gap-2 rounded-full border border-[#c7dbed] bg-white px-3.5 py-2 text-[0.78rem] font-semibold text-[#0a293c] transition hover:border-[#8fbde0] hover:text-[#1977b2]" data-link-key="site.clinic.map_url" data-link-label="Ссылка на карту">
						<i class="fa-solid fa-map-location-dot"></i>
						<span<?php echo bioinmed_page_text_attr($aboutPage, 'about', 'address.open_map'); ?>><?php echo e($aboutAddress['open_map'] ?? 'Открыть карту'); ?></span>
					</a>
					<a href="tel:<?php echo e(preg_replace('/\D/', '', CLINIC_PHONE)); ?>" class="inline-flex items-center justify-center gap-2 rounded-full bg-[#1977b2] px-3.5 py-2 text-[0.78rem] font-semibold text-white transition hover:bg-[#16658f]">
						<i class="fa-solid fa-phone-volume"></i>
						<span<?php echo bioinmed_page_text_attr($aboutPage, 'about', 'address.call'); ?>><?php echo e($aboutAddress['call'] ?? 'Позвонить'); ?></span>
					</a>
				</div>
			</p>
		</article>
	</section>

	<section>
		<div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between" data-admin-block-root>
			<h2 class="text-[1.4rem] font-bold text-[#0f2749] md:text-[1.75rem]"<?php echo bioinmed_page_text_attr($aboutPage, 'about', 'tasks.title'); ?>><?php echo e($aboutTasks['title'] ?? 'Ключевые задачи клиники'); ?></h2>
			<p class="text-[0.84rem] text-[#0a293c]"<?php echo bioinmed_page_text_attr($aboutPage, 'about', 'tasks.subtitle'); ?>><?php echo e($aboutTasks['subtitle'] ?? ''); ?></p>
		</div>
		<div class="mt-5 rounded-3xl border border-[#d9e7f3] bg-white p-5 shadow-[0_10px_24px_rgba(8,36,70,0.08)] md:p-6">
			<div class="grid gap-4 md:grid-cols-2"<?php echo bioinmed_editable_list_attrs('about', 'about.tasks.items', 'Ключевые задачи клиники', false, 'Описание'); ?>>
				<?php echo bioinmed_editable_list_toolbar('div'); ?>
				<?php foreach ($aboutTaskItems as $task): ?>
					<div class="rounded-2xl border border-[#e4edf6] bg-[#f8fbff] p-4<?php echo bioinmed_editable_list_item_class($task); ?>" data-admin-block-root<?php echo bioinmed_editable_list_item_attrs($task); ?>>
						<p class="text-[0.92rem] font-semibold uppercase tracking-[0.12em] text-[#1977b2]" data-admin-list-text-view><?php echo e($task['text']); ?></p>
						<p class="mt-2 text-[0.98rem] leading-relaxed text-[#0a293c]" data-admin-list-secondary-view><?php echo e($task['secondary']); ?></p>
						<?php echo bioinmed_editable_list_actions($task); ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="bg-[#e4f1fa] py-10 md:py-14">
		<div class="grid gap-8 md:grid-cols-[380px_1fr] lg:grid-cols-[460px_1fr] md:items-start">
		<div class="w-full max-w-[480px]" data-admin-block-root>
			<div class="aspect-square overflow-hidden rounded-3xl">
				<img src="<?php echo e($chiefImage); ?>" alt="<?php echo e($chiefName); ?>" class="h-full w-full rounded-3xl object-cover object-top" loading="lazy">
			</div>
			<p class="caveat-reveal mt-4 max-w-none text-[#0a293c]" style="font-family:'Caveat',cursive;font-size:clamp(1.35rem,4vw,1.8rem);line-height:1.22;font-weight:700;"<?php echo bioinmed_page_text_attr($aboutPage, 'about', 'chief_quote.text'); ?>>
				<?php echo e($aboutChiefQuote['text'] ?? ''); ?>
			</p>
			<p class="caveat-reveal mt-2 text-[1.08rem] font-semibold tracking-[0.04em] text-[#4a6f9c]" style="font-family:'Caveat',cursive;font-weight:700;"<?php echo bioinmed_page_text_attr($aboutPage, 'about', 'chief_quote.sign'); ?>><?php echo e($aboutChiefQuote['sign'] ?? ''); ?></p>
		</div>
		<article class="p-5 md:p-7" data-admin-block-root>
			<?php echo bioinmed_render_chief_doctor_summary($chief, [
				'show_cta' => false,
				'editable_list_key' => 'about.chief.educational_role',
				'editable_list_page' => 'about',
				'editable_list_page_data' => $aboutPage,
			]); ?>
		</article>
	</section>

	<section class="overflow-hidden rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_12px_30px_rgba(8,36,70,0.10)]">
		<div class="grid gap-0 lg:grid-cols-[1.05fr_0.95fr]">
			<div class="bg-white p-5 md:p-7" data-admin-block-root>
				<h2 class="text-[1.26rem] font-bold leading-tight text-[#0f2749] md:text-[1.65rem]"<?php echo $aboutCtaHeadingNode['attr']; ?>><?php echo e($aboutCtaHeadingNode['value']); ?></h2>
				<div class="mt-4 space-y-2 text-[0.8rem] text-[#0a293c]">
					<p class="flex items-center gap-2"<?php echo bioinmed_page_text_attr($aboutPage, 'about', 'cta.help_soon'); ?>><i class="fa-solid fa-phone-volume text-[#1977b2]"></i> <?php echo e($aboutCta['help_soon'] ?? ''); ?></p>
					<p class="flex items-center gap-2"<?php echo bioinmed_page_text_attr($aboutPage, 'about', 'cta.select_doctor'); ?>><i class="fa-solid fa-user-doctor text-[#1977b2]"></i> <?php echo e($aboutCta['select_doctor'] ?? ''); ?></p>
					<p class="flex items-center gap-2"<?php echo bioinmed_page_text_attr($aboutPage, 'about', 'cta.pick_time'); ?>><i class="fa-solid fa-calendar-check text-[#1977b2]"></i> <?php echo e($aboutCta['pick_time'] ?? ''); ?></p>
				</div>
			</div>
			<div class="p-5 md:p-7">
				<div class="max-w-xl">
					<?php echo bioinmed_render_callback_form([
						'source_label' => 'О клинике — CTA',
					]); ?>
				</div>
			</div>
		</div>
	</section>

</main>

<?php
$footer = new Footer($brand_colors);
echo $footer->render();
?>
</body>
</html>
