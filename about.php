<?php
// PIN-защита сайта
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Проверяем, предоставлен ли доступ через splash.php
if (!isset($_SESSION['site_access_granted'])) {
    // Проверяем переменную окружения для отключения PIN-защиты
    // По умолчанию защита ВКЛЮЧЕНА, отключается только при PIN_PROTECTION_ENABLED=0
    $pin_protection_disabled = in_array(getenv('PIN_PROTECTION_ENABLED'), ['0', 'false'], true);
    if (!$pin_protection_disabled) {
        // PIN-защита включена, перенаправляем на splash.php
        header('Location: /splash.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

require_once 'config.php';
require_once 'includes/components/Components.php';

$siteUrl = rtrim(CLINIC_SITE_URL, '/');
$iconPath = CLINIC_ICON_PATH;
$socialImageUrl = bioinmed_default_social_image_url();
$canonicalUrl = $siteUrl . '/about';
$pageTitle = 'О клинике | ' . CLINIC_NAME;
$pageDescription = 'Клиника гомеопатии и биорегуляции БИОИНМЕД: интегративная медицина, диагностика первопричин и персональные программы восстановления.';

$structuredData = bioinmed_medical_organization_schema();
$breadcrumbStructuredData = bioinmed_breadcrumb_schema([
	['name' => 'Главная', 'url' => $siteUrl . '/'],
	['name' => 'О клинике', 'url' => $canonicalUrl],
]);

$bookingUrl = defined('ONLINE_BOOKING_URL') ? ONLINE_BOOKING_URL : '#contact';
$chief = $doctors[0] ?? [];
$chiefName = trim((string)($chief['name'] ?? 'Инна Викторовна Костромина'));
$chiefTitle = trim((string)($chief['title'] ?? 'Главный врач'));
$chiefBio = trim((string)($chief['bio'] ?? 'Эксперт в области интегративной медицины, биорегуляции и персональных программ восстановления.'));
$chiefLeadership = trim((string)($chief['leadership'] ?? ''));
$chiefExperience = trim((string)($chief['experience'] ?? 'Более 30 лет врачебной практики'));
$chiefImage = '/public/images/team/kostromina-default.jpg';
$mapUrl = 'https://yandex.com/maps/-/CPGGyEzo';
if (!empty($chief['image'])) {
	$chiefImage = '/public/images/team/' . ltrim((string)$chief['image'], '/');
}


$aboutServiceShowcase = [
	[
		'image' => '/public/images/services/habilect.jpg',
		'alt' => 'Диагностика HABILECT в БИОИНМЕД',
		'title' => 'Диагностика HABILECT',
		'desc' => 'Флагманская диагностика клиники: помогает увидеть перегрузки и ограничения опорно-двигательного аппарата за один визит.',
		'href' => '/services/hobilect-diagnostics',
	],
	[
		'image' => '/public/images/services/physiotherapy-treatment.jpg',
		'alt' => 'Физиотерапия в БИОИНМЕД',
		'title' => 'Физиотерапия',
		'desc' => 'Аппаратные методики для снятия боли, улучшения микроциркуляции и ускорения восстановления после перегрузок.',
		'href' => '/services/fizioterapiya',
	],
	[
		'image' => '/public/images/services/acupuncture-therapy-6.jpg',
		'alt' => 'Рефлексотерапия и иглоукалывание в БИОИНМЕД',
		'title' => 'Рефлексотерапия и иглоукалывание',
		'desc' => 'Точечная работа с болью, вегетативной нервной системой и стрессовой нагрузкой для более устойчивого самочувствия.',
		'href' => '/services/acupuncture',
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
	<meta name="theme-color" content="#2fbdef">
	<?php echo bioinmed_render_social_meta($pageTitle, $pageDescription, $canonicalUrl, [
		'image' => $socialImageUrl,
	]); ?>
	<?php echo bioinmed_render_favicon_links($iconPath); ?>
	<script src="https://cdn.tailwindcss.com"></script>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
	<script type="application/ld+json"><?php echo json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
	<script type="application/ld+json"><?php echo json_encode($breadcrumbStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
	<?php echo bioinmed_uis_counter_head(); ?>
</head>
<body class="min-h-screen bg-[linear-gradient(to_bottom,#f9fcff_0%,#f2f7fc_50%,#edf4fb_100%)] text-[#0f2749] antialiased">
<?php
$header = new Header($brand_colors);
echo $header->render();
?>

<main class="mx-auto max-w-6xl space-y-8 px-6 py-8 md:space-y-10 md:px-10 md:py-12">
	<section class="relative overflow-hidden rounded-[2rem] border border-[#d8e6f3] bg-[radial-gradient(circle_at_top_left,#ffffff_0%,#edf6fd_38%,#deedf8_100%)] p-5 shadow-[0_14px_36px_rgba(8,36,70,0.08)] md:p-7">
		<div class="pointer-events-none absolute -right-14 -top-14 h-44 w-44 rounded-full bg-[#2fbdef1e] blur-3xl"></div>
		<div class="pointer-events-none absolute -left-10 bottom-0 h-36 w-36 rounded-full bg-[#0f274914] blur-3xl"></div>
		<div class="relative grid gap-6 lg:grid-cols-[1.15fr_0.85fr] lg:items-center">
			<div>
				<p class="text-[0.72rem] font-semibold uppercase tracking-[0.2em] text-[#2a5a94]">Клиника гомеопатии и биорегуляции</p>
				<h1 class="mt-2 text-[1.65rem] font-bold leading-[1.08] text-[#0f2749] md:text-[2.2rem]">Клиника БИОИНМЕД</h1>
				<p class="mt-3.5 max-w-2xl text-[0.9rem] leading-relaxed text-[#355b89]">
					Мы объединили классическую и восточную медицину, направления информационной медицины, психологию,
					психогенетику, гомеопатию, рефлексо-физиотерапию и нутрициологию в единую систему восстановления.
				</p>
				<div class="mt-5 rounded-2xl border border-[#d6e5f2] bg-white/90 p-4 shadow-[0_8px_20px_rgba(8,36,70,0.05)]">
					<p class="text-[0.7rem] font-semibold uppercase tracking-[0.14em] text-[#2a5a94]">Подход БИОИНМЕД</p>
					<p class="mt-2 text-[1.05rem] font-semibold leading-snug text-[#0f2749]">Выздоровление как искусство: точно, глубоко, персонально.</p>
				</div>
				<div class="mt-4 grid gap-3 sm:grid-cols-3">
					<div class="rounded-xl border border-[#d9e7f4] bg-white p-3.5 shadow-[0_6px_16px_rgba(8,36,70,0.04)]">
						<p class="text-[1.18rem] font-bold text-[#0f2749]">30+</p>
						<p class="text-[0.75rem] leading-snug text-[#4f759c]">лет клинического опыта</p>
					</div>
					<div class="rounded-xl border border-[#d9e7f4] bg-white p-3.5 shadow-[0_6px_16px_rgba(8,36,70,0.04)]">
						<p class="text-[1.18rem] font-bold text-[#0f2749]">20+</p>
						<p class="text-[0.75rem] leading-snug text-[#4f759c]">направлений интегративной медицины</p>
					</div>
					<div class="rounded-xl border border-[#d9e7f4] bg-white p-3.5 shadow-[0_6px_16px_rgba(8,36,70,0.04)]">
						<p class="text-[1.18rem] font-bold text-[#0f2749]">1</p>
						<p class="text-[0.75rem] leading-snug text-[#4f759c]">персональный маршрут для каждого пациента</p>
					</div>
				</div>
			</div>
			<aside class="rounded-3xl border border-[#d6e4f1] bg-white p-5 shadow-[0_16px_38px_rgba(8,36,70,0.13)]">
				<p class="text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-[#2a5a94]">Адрес клиники</p>
				<h2 class="mt-2 text-[1.25rem] font-bold leading-tight text-[#0f2749]"><?php echo e(CLINIC_ADDRESS); ?></h2>
				<p class="mt-1 text-[0.9rem] font-medium text-[#2a5a94]"><?php echo e(CLINIC_METRO); ?></p>

				<div class="mt-4 space-y-3 rounded-2xl border border-[#dce8f4] bg-[#f7fbff] p-4">
					<div class="flex items-start gap-2 text-[0.86rem] text-[#355b89]">
						<i class="fa-solid fa-location-dot mt-0.5 text-[#2fbdef]"></i>
						<span>Клиника находится в удобной доступности от метро и городского транспорта.</span>
					</div>
					<div class="flex items-start gap-2 text-[0.86rem] text-[#355b89]">
						<i class="fa-solid fa-clock mt-0.5 text-[#2fbdef]"></i>
						<span><?php echo e(CLINIC_HOURS); ?></span>
					</div>
					<div class="flex items-start gap-2 text-[0.86rem] text-[#355b89]">
						<i class="fa-solid fa-phone mt-0.5 text-[#2fbdef]"></i>
						<span><?php echo e(CLINIC_PHONE); ?></span>
					</div>
				</div>

				<div class="mt-4 grid gap-2 sm:grid-cols-2">
					<a href="<?php echo e($mapUrl); ?>" target="_blank" rel="noreferrer noopener" class="inline-flex items-center justify-center gap-2 rounded-full border border-[#c7dbed] bg-white px-3.5 py-2 text-[0.78rem] font-semibold text-[#2a5a94] transition hover:border-[#8fbde0] hover:text-[#2fbdef]">
						<i class="fa-solid fa-map-location-dot"></i>
						Открыть карту
					</a>
					<a href="tel:<?php echo e(preg_replace('/\D/', '', CLINIC_PHONE)); ?>" class="inline-flex items-center justify-center gap-2 rounded-full bg-[#2fbdef] px-3.5 py-2 text-[0.78rem] font-semibold text-white transition hover:bg-[#1fb3d8]">
						<i class="fa-solid fa-phone-volume"></i>
						Позвонить
					</a>
				</div>
			</p>
		</article>
	</section>

	<section>
		<div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
			<h2 class="text-[1.4rem] font-bold text-[#0f2749] md:text-[1.75rem]">Уникальные особенности БИОИНМЕД</h2>
			<p class="text-[0.84rem] text-[#4f759c]">Диагностика, биорегуляция и интегративные протоколы в одной клинической системе</p>
		</div>
		<div class="mt-5 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
			<article class="rounded-2xl border border-[#dce8f4] bg-white p-5 shadow-[0_8px_18px_rgba(8,36,70,0.06)] transition hover:border-[#bfd7eb]"><p class="font-semibold text-[#17446f]">Вегетативно-резонансный тест</p><p class="mt-2 text-sm leading-relaxed text-[#4f759c]">Точная оценка функциональных нарушений и подбор персонального маршрута восстановления.</p></article>
			<article class="rounded-2xl border border-[#dce8f4] bg-white p-5 shadow-[0_8px_18px_rgba(8,36,70,0.06)] transition hover:border-[#bfd7eb]"><p class="font-semibold text-[#17446f]">Диагностика HABILECT</p><p class="mt-2 text-sm leading-relaxed text-[#4f759c]">Инновационная система для диагностики и реабилитации опорно-двигательного аппарата.</p></article>
			<article class="rounded-2xl border border-[#dce8f4] bg-white p-5 shadow-[0_8px_18px_rgba(8,36,70,0.06)] transition hover:border-[#bfd7eb]"><p class="font-semibold text-[#17446f]">Кинезиодиагностика</p><p class="mt-2 text-sm leading-relaxed text-[#4f759c]">Выявление функциональных цепочек, перегрузок и глубинных причин хронической боли.</p></article>
			<article class="rounded-2xl border border-[#dce8f4] bg-white p-5 shadow-[0_8px_18px_rgba(8,36,70,0.06)] transition hover:border-[#bfd7eb]"><p class="font-semibold text-[#17446f]">Психодиагностика</p><p class="mt-2 text-sm leading-relaxed text-[#4f759c]">Определение психоэмоциональных факторов, влияющих на состояние и скорость восстановления.</p></article>
			<article class="rounded-2xl border border-[#dce8f4] bg-white p-5 shadow-[0_8px_18px_rgba(8,36,70,0.06)] transition hover:border-[#bfd7eb]"><p class="font-semibold text-[#17446f]">Ольфактодиагностика</p><p class="mt-2 text-sm leading-relaxed text-[#4f759c]">Дополнительный инструмент оценки адаптационных возможностей и тонкой настройки терапии.</p></article>
			<article class="rounded-2xl border border-[#dce8f4] bg-white p-5 shadow-[0_8px_18px_rgba(8,36,70,0.06)] transition hover:border-[#bfd7eb]"><p class="font-semibold text-[#17446f]">Интегративная синергия</p><p class="mt-2 text-sm leading-relaxed text-[#4f759c]">Сочетание аппаратных и информационных технологий, психотерапии, рефлексотерапии, остеопатии и нутрициологии.</p></article>
		</div>
	</section>

	<section class="overflow-hidden rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_12px_30px_rgba(8,36,70,0.10)]">
		<div class="p-6 md:p-8">
			<div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
				<h2 class="text-[1.35rem] font-bold text-[#0f2749] md:text-[1.75rem]">Как проходит путь пациента</h2>
				<p class="text-[0.84rem] text-[#4f759c]">Четкий маршрут от первичной записи до персонального плана восстановления</p>
			</div>
			<div class="mt-5 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
				<article class="rounded-2xl border border-[#dce8f4] bg-[#f7fbff] p-4">
					<div class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white text-[#2fbdef] shadow-[0_6px_14px_rgba(8,36,70,0.08)]"><i class="fa-solid fa-calendar-check"></i></div>
					<p class="mt-3 text-[0.78rem] font-semibold text-[#17446f]">Предварительная запись</p>
					<p class="mt-1 text-[0.8rem] leading-relaxed text-[#4f759c]">Быстро подбираем врача и удобное окно приема под ваш запрос.</p>
				</article>
				<article class="rounded-2xl border border-[#dce8f4] bg-[#f7fbff] p-4">
					<div class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white text-[#2fbdef] shadow-[0_6px_14px_rgba(8,36,70,0.08)]"><i class="fa-solid fa-stethoscope"></i></div>
					<p class="mt-3 text-[0.78rem] font-semibold text-[#17446f]">Первичная консультация</p>
					<p class="mt-1 text-[0.8rem] leading-relaxed text-[#4f759c]">Врач формирует гипотезу и определяет объем необходимой диагностики.</p>
				</article>
				<article class="rounded-2xl border border-[#dce8f4] bg-[#f7fbff] p-4">
					<div class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white text-[#2fbdef] shadow-[0_6px_14px_rgba(8,36,70,0.08)]"><i class="fa-solid fa-heart-pulse"></i></div>
					<p class="mt-3 text-[0.78rem] font-semibold text-[#17446f]">Персональный протокол</p>
					<p class="mt-1 text-[0.8rem] leading-relaxed text-[#4f759c]">Подбираем тактику лечения и восстановления с учетом особенностей пациента.</p>
				</article>
				<article class="rounded-2xl border border-[#dce8f4] bg-[#f7fbff] p-4">
					<div class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white text-[#2fbdef] shadow-[0_6px_14px_rgba(8,36,70,0.08)]"><i class="fa-solid fa-clipboard-check"></i></div>
					<p class="mt-3 text-[0.78rem] font-semibold text-[#17446f]">Контроль динамики</p>
					<p class="mt-1 text-[0.8rem] leading-relaxed text-[#4f759c]">Отслеживаем результат и корректируем программу по ходу лечения.</p>
				</article>
			</div>
		</div>
	</section>

	<section class="grid gap-5 lg:grid-cols-[0.95fr_1.05fr]">
		<div class="overflow-hidden rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_12px_30px_rgba(8,36,70,0.10)]">
			<img src="<?php echo e($chiefImage); ?>" alt="<?php echo e($chiefName); ?>" class="h-full min-h-[320px] w-full object-cover object-top" loading="lazy">
		</div>
		<article class="rounded-3xl border border-[#d9e7f3] bg-white p-5 md:p-7 shadow-[0_12px_30px_rgba(8,36,70,0.10)]">
			<p class="text-[0.72rem] font-semibold uppercase tracking-[0.14em] text-[#2a5a94]">Об основателе</p>
			<h2 class="mt-2 text-[1.32rem] font-bold leading-tight text-[#0f2749] md:text-[1.7rem]"><?php echo e($chiefName); ?></h2>
			<p class="mt-2.5 text-[0.9rem] leading-relaxed text-[#355b89]"><?php echo e($chiefBio); ?></p>
			<?php if ($chiefLeadership !== ''): ?>
				<p class="mt-2.5 text-[0.9rem] leading-relaxed text-[#355b89]"><?php echo e($chiefLeadership); ?></p>
			<?php endif; ?>
			<div class="mt-4 space-y-2 rounded-2xl border border-[#dce8f4] bg-[#f7fbff] p-4 text-[0.84rem] leading-relaxed text-[#355b89]">
				<p>Потомственный доктор и эксперт в сфере интегративной медицины, психологии, гомеопатии, рефлексотерапии и биорегуляции.</p>
				<p>Разработала более десятка авторских методик, семинаров и комплексных оздоровительных программ.</p>
				<p>Профессиональный фокус - объединение медицины и психологии для поиска первопричины заболевания задолго до выраженных клинических проявлений.</p>
			</div>
		</article>
	</section>

	<section class="overflow-hidden rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_12px_30px_rgba(8,36,70,0.10)]">
		<div class="grid gap-0 lg:grid-cols-[1.05fr_0.95fr]">
			<div class="bg-[linear-gradient(145deg,#eff8ff_0%,#f9fcff_65%)] p-5 md:p-7">
				<p class="text-[0.72rem] font-semibold uppercase tracking-[0.14em] text-[#2a5a94]">Записаться на прием</p>
				<h2 class="mt-2 text-[1.26rem] font-bold leading-tight text-[#0f2749] md:text-[1.65rem]">Записаться на приём</h2>
				<p class="mt-2.5 max-w-md text-[0.86rem] leading-relaxed text-[#355b89]">Перезвоним в течение 15 минут.</p>
				<div class="mt-4 space-y-2 text-[0.8rem] text-[#355b89]">
					<p class="flex items-center gap-2"><i class="fa-solid fa-phone-volume text-[#2fbdef]"></i> Свяжемся в ближайшее время</p>
					<p class="flex items-center gap-2"><i class="fa-solid fa-user-doctor text-[#2fbdef]"></i> Подскажем профильного специалиста</p>
					<p class="flex items-center gap-2"><i class="fa-solid fa-calendar-check text-[#2fbdef]"></i> Подберем комфортное окно записи</p>
				</div>
			</div>
			<div class="p-5 md:p-7">
				<div class="max-w-xl">
					<a href="javascript:void(0)" class="jsClientix_openWidget mt-3 inline-flex w-full items-center justify-center rounded-full bg-[#2fbdef] px-6 py-2.5 text-[0.84rem] font-semibold text-white transition hover:bg-[#1fb3d8] active:bg-[#1597b9]">Перезвоните мне</a>
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
