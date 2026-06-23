<?php
require_once 'config.php';
require_once 'includes/components/Components.php';

$siteUrl = rtrim(CLINIC_SITE_URL, '/');
$iconPath = CLINIC_ICON_PATH;
$socialImageUrl = bioinmed_default_social_image_url();
$canonicalUrl = $siteUrl . '/user-agreement';
$pageTitle = 'Пользовательское соглашение | ' . CLINIC_NAME;
$pageDescription = 'Пользовательское соглашение сайта клиники ' . CLINIC_NAME . ': условия использования сайта, записи через формы и обратной связи.';

$structuredData = bioinmed_medical_organization_schema();
$breadcrumbStructuredData = bioinmed_breadcrumb_schema([
    ['name' => 'Главная', 'url' => $siteUrl . '/'],
    ['name' => 'Пользовательское соглашение', 'url' => $canonicalUrl],
]);

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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script type="application/ld+json"><?php echo json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script type="application/ld+json"><?php echo json_encode($breadcrumbStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <?php echo bioinmed_uis_counter_head(); ?>
</head>
<body class="min-h-screen bg-[#e4f1fa] text-[#0f2749] antialiased">
<?php
$header = new Header($brand_colors);
echo $header->render();
?>

<main class="mx-auto max-w-6xl px-6 py-10 md:px-10 md:py-14">
    <section class="pb-8">
        <div class="max-w-4xl">
            <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-[#0a293c]">Правовая информация</p>
            <h1 class="mt-2 text-[1.8rem] font-bold leading-tight text-[#0f2749] md:text-[2.6rem]">Пользовательское соглашение</h1>
            <p class="mt-4 text-[0.98rem] leading-relaxed text-[#0a293c]">
                Настоящее соглашение регулирует использование сайта клиники <?php echo e(CLINIC_NAME); ?>, размещённых на нём материалов,
                форм обратной связи, сервиса записи на приём и иных пользовательских сценариев взаимодействия с сайтом.
            </p>
            <div class="mt-4 flex flex-col gap-1 text-[0.82rem] text-[#0a293c] sm:flex-row sm:flex-wrap sm:gap-4">
                <p><span class="font-semibold text-[#0a293c]">Дата вступления в силу:</span> 30.04.2026</p>
                <p><span class="font-semibold text-[#0a293c]">Дата последнего изменения:</span> 30.04.2026</p>
            </div>
        </div>

        <div class="mt-8 max-w-4xl space-y-8 text-[0.98rem] leading-relaxed text-[#0a293c]">
            <section class="border-b border-[#e2ecf5] pb-6">
                <h2 class="text-[1.16rem] font-bold text-[#0f2749]">1. Общие положения</h2>
                <p class="mt-3">Используя сайт, пользователь подтверждает, что ознакомился с настоящим соглашением и принимает его условия в полном объёме. Если пользователь не согласен с условиями, он должен прекратить использование сайта.</p>
            </section>

            <section class="border-b border-[#e2ecf5] pb-6">
                <h2 class="text-[1.16rem] font-bold text-[#0f2749]">2. Назначение сайта</h2>
                <p class="mt-3">Сайт предназначен для предоставления информации о клинике, специалистах, услугах, ценах, формате записи и контактах. Информация на сайте носит справочный характер и не заменяет очную консультацию врача.</p>
            </section>

            <section class="border-b border-[#e2ecf5] pb-6">
                <h2 class="text-[1.16rem] font-bold text-[#0f2749]">3. Порядок использования форм и сервисов записи</h2>
                <p class="mt-3">При заполнении форм пользователь обязуется указывать достоверные сведения, достаточные для обратной связи. Отправка формы означает согласие пользователя на получение звонка или сообщения по указанным контактным данным в целях обработки запроса и организации записи.</p>
            </section>

            <section class="border-b border-[#e2ecf5] pb-6">
                <h2 class="text-[1.16rem] font-bold text-[#0f2749]">4. Информационные материалы и ограничения</h2>
                <p class="mt-3">Материалы сайта не являются публичной офертой, медицинским заключением, диагнозом или назначением лечения. Решения о диагностике и терапии принимаются только лечащим врачом по итогам личного приема и обследования.</p>
            </section>

            <section class="border-b border-[#e2ecf5] pb-6">
                <h2 class="text-[1.16rem] font-bold text-[#0f2749]">5. Интеллектуальная собственность</h2>
                <p class="mt-3">Все материалы сайта, включая тексты, изображения, графику, логотипы, структуру страниц и элементы интерфейса, охраняются законом. Их копирование, распространение и иное использование допускается только с письменного разрешения правообладателя или в случаях, предусмотренных законодательством.</p>
            </section>

            <section class="border-b border-[#e2ecf5] pb-6">
                <h2 class="text-[1.16rem] font-bold text-[#0f2749]">6. Ограничение ответственности</h2>
                <p class="mt-3">Администрация сайта предпринимает разумные меры для поддержания актуальности информации, однако не гарантирует абсолютную полноту, точность и бесперебойность работы сайта. Клиника не несёт ответственности за возможные убытки, возникшие вследствие использования материалов сайта без очной консультации специалиста.</p>
            </section>

            <section class="border-b border-[#e2ecf5] pb-6">
                <h2 class="text-[1.16rem] font-bold text-[#0f2749]">7. Ссылки на сторонние ресурсы</h2>
                <p class="mt-3">На сайте могут размещаться ссылки на сторонние ресурсы и сервисы. Клиника не контролирует содержание таких ресурсов и не несёт ответственности за их работу, политику обработки данных и актуальность размещённой на них информации.</p>
            </section>

            <section class="border-b border-[#e2ecf5] pb-6">
                <h2 class="text-[1.16rem] font-bold text-[#0f2749]">8. Связь с администрацией сайта</h2>
                <p class="mt-3">По вопросам использования сайта, направления замечаний, обработки обращений и организации записи пользователь может обратиться по телефону <?php echo e(CLINIC_PHONE); ?> или по электронной почте <?php echo e(CLINIC_EMAIL); ?>.</p>
            </section>

            <section>
                <h2 class="text-[1.16rem] font-bold text-[#0f2749]">9. Изменение условий соглашения</h2>
                <p class="mt-3">Клиника вправе вносить изменения в настоящее соглашение. Новая редакция действует с момента публикации на этой странице, если иное прямо не указано в тексте обновления.</p>
            </section>
        </div>
    </section>
</main>

<?php
$footer = new Footer($brand_colors);
echo $footer->render();
?>
</body>
</html>
