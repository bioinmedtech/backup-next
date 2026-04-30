<?php
require_once 'config.php';
require_once 'includes/components/Components.php';

$siteUrl = rtrim(CLINIC_SITE_URL, '/');
$iconPath = CLINIC_ICON_PATH;
$socialImageUrl = bioinmed_default_social_image_url();
$canonicalUrl = $siteUrl . '/privacy.php';
$pageTitle = 'Политика конфиденциальности | ' . CLINIC_NAME;
$pageDescription = 'Политика конфиденциальности клиники ' . CLINIC_NAME . ': порядок обработки персональных данных, контактная информация и права пользователя.';

$structuredData = bioinmed_medical_organization_schema();
$breadcrumbStructuredData = bioinmed_breadcrumb_schema([
    ['name' => 'Главная', 'url' => $siteUrl . '/'],
    ['name' => 'Политика конфиденциальности', 'url' => $canonicalUrl],
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
    <meta name="theme-color" content="#2fbdef">
    <?php echo bioinmed_render_social_meta($pageTitle, $pageDescription, $canonicalUrl, [
        'image' => $socialImageUrl,
    ]); ?>
    <?php echo bioinmed_render_favicon_links($iconPath); ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script type="application/ld+json"><?php echo json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script type="application/ld+json"><?php echo json_encode($breadcrumbStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
</head>
<body class="min-h-screen bg-[linear-gradient(to_bottom,#f9fcff_0%,#f2f7fc_50%,#edf4fb_100%)] text-[#0f2749] antialiased">
<?php
$header = new Header($brand_colors);
echo $header->render();
?>

<main class="mx-auto max-w-6xl px-6 py-10 md:px-10 md:py-14">
    <section class="pb-8">
        <div class="max-w-4xl">
            <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-[#2a5a94]">Правовая информация</p>
            <h1 class="mt-2 text-[1.8rem] font-bold leading-tight text-[#0f2749] md:text-[2.6rem]">Политика конфиденциальности</h1>
            <p class="mt-4 text-[0.98rem] leading-relaxed text-[#355b89]">
                Настоящая политика определяет порядок обработки, хранения и защиты персональных данных, которые клиника <?php echo e(CLINIC_NAME); ?>
                получает при обращении через сайт, по телефону, по электронной почте, через формы записи и при дальнейшем взаимодействии с пациентом.
            </p>
            <div class="mt-4 flex flex-col gap-1 text-[0.82rem] text-[#5a7fa3] sm:flex-row sm:flex-wrap sm:gap-4">
                <p><span class="font-semibold text-[#2a5a94]">Дата вступления в силу:</span> 30.04.2026</p>
                <p><span class="font-semibold text-[#2a5a94]">Дата последнего изменения:</span> 30.04.2026</p>
            </div>
        </div>

        <div class="mt-8 grid gap-5 border-y border-[#d8e6f3] py-6 md:grid-cols-3">
            <div>
                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.14em] text-[#2a5a94]">Оператор данных</p>
                <p class="mt-2 text-[0.95rem] leading-relaxed text-[#355b89]"><?php echo e(CLINIC_NAME); ?><br><?php echo e(CLINIC_ADDRESS); ?></p>
            </div>
            <div>
                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.14em] text-[#2a5a94]">Контакты</p>
                <p class="mt-2 text-[0.95rem] leading-relaxed text-[#355b89]"><?php echo e(CLINIC_PHONE); ?><br><?php echo e(CLINIC_EMAIL); ?></p>
            </div>
            <div>
                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.14em] text-[#2a5a94]">Назначение обработки</p>
                <p class="mt-2 text-[0.95rem] leading-relaxed text-[#355b89]">Обратная связь, запись на приём, информирование об услугах, координация визита и сопровождение пациента.</p>
            </div>
        </div>

        <div class="mt-8 max-w-4xl space-y-8 text-[0.98rem] leading-relaxed text-[#355b89]">
            <section class="border-b border-[#e2ecf5] pb-6">
                <h2 class="text-[1.16rem] font-bold text-[#0f2749]">1. Общие положения</h2>
                <p class="mt-3">Настоящая политика применяется ко всей информации, которую сайт клиники и сотрудники клиники могут получить о пользователе при посещении сайта, отправке заявки, записи на консультацию, запросе обратного звонка и при других формах взаимодействия.</p>
                <p class="mt-3">Использование сайта означает ознакомление пользователя с настоящей политикой и согласие с изложенными в ней условиями обработки персональных данных.</p>
            </section>

            <section class="border-b border-[#e2ecf5] pb-6">
                <h2 class="text-[1.16rem] font-bold text-[#0f2749]">2. Какие данные могут обрабатываться</h2>
                <p class="mt-3">Клиника может обрабатывать следующие категории данных: фамилия, имя, отчество, номер телефона, адрес электронной почты, сведения из обращения, данные о выбранной услуге, а также технические данные, автоматически передаваемые браузером при посещении сайта.</p>
                <p class="mt-3">Если пользователь сообщает дополнительные сведения добровольно, они также могут обрабатываться в объёме, необходимом для ответа на запрос или организации записи.</p>
            </section>

            <section class="border-b border-[#e2ecf5] pb-6">
                <h2 class="text-[1.16rem] font-bold text-[#0f2749]">3. Цели обработки персональных данных</h2>
                <p class="mt-3">Данные используются для обратной связи с пользователем, согласования даты и времени приёма, уточнения симптомов и запроса, информирования об услугах клиники, повышения качества сервиса, а также выполнения требований законодательства Российской Федерации.</p>
            </section>

            <section class="border-b border-[#e2ecf5] pb-6">
                <h2 class="text-[1.16rem] font-bold text-[#0f2749]">4. Правовые основания обработки</h2>
                <p class="mt-3">Основанием для обработки является добровольное согласие пользователя, выраженное при отправке формы или ином обращении, а также иные основания, предусмотренные действующим законодательством Российской Федерации.</p>
            </section>

            <section class="border-b border-[#e2ecf5] pb-6">
                <h2 class="text-[1.16rem] font-bold text-[#0f2749]">5. Порядок хранения и защиты данных</h2>
                <p class="mt-3">Клиника принимает разумные организационные и технические меры для защиты персональных данных от утраты, неправомерного доступа, раскрытия, изменения, копирования и иных неправомерных действий.</p>
                <p class="mt-3">Доступ к персональным данным предоставляется только тем сотрудникам и подрядчикам, которым он объективно необходим для выполнения соответствующих функций.</p>
            </section>

            <section class="border-b border-[#e2ecf5] pb-6">
                <h2 class="text-[1.16rem] font-bold text-[#0f2749]">6. Передача данных третьим лицам</h2>
                <p class="mt-3">Передача персональных данных третьим лицам возможна только в случаях, когда это необходимо для исполнения запроса пользователя, предусмотрено законом или осуществляется на основании отдельного согласия пользователя.</p>
            </section>

            <section class="border-b border-[#e2ecf5] pb-6">
                <h2 class="text-[1.16rem] font-bold text-[#0f2749]">7. Права субъекта персональных данных</h2>
                <p class="mt-3">Пользователь вправе запросить сведения об обработке своих данных, потребовать их уточнения, обновления, ограничения обработки или удаления, а также отозвать ранее данное согласие, направив обращение на <?php echo e(CLINIC_EMAIL); ?>.</p>
            </section>

            <section class="border-b border-[#e2ecf5] pb-6">
                <h2 class="text-[1.16rem] font-bold text-[#0f2749]">8. Использование файлов cookie и технических данных</h2>
                <p class="mt-3">Сайт может использовать cookie, журналы сервера и иные технические механизмы, необходимые для корректной работы сайта, аналитики посещаемости и повышения удобства использования.</p>
            </section>

            <section>
                <h2 class="text-[1.16rem] font-bold text-[#0f2749]">9. Актуализация политики</h2>
                <p class="mt-3">Клиника вправе обновлять настоящую политику. Новая редакция вступает в силу с момента публикации на этой странице, если иное не предусмотрено самой редакцией документа.</p>
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