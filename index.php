<?php
require_once __DIR__ . '/includes/pin_protection.php';
bioinmed_pin_require_access();


require_once 'config.php';
require_once 'includes/components/Components.php';

$siteUrl = rtrim(CLINIC_SITE_URL, '/');
$iconPath = CLINIC_ICON_PATH;
$iconUrl = $siteUrl . $iconPath;
$socialImageUrl = bioinmed_default_social_image_url();
$canonicalUrl = $siteUrl . '/';
$pageTitle = 'Клиника восстановительной медицины в Москве | БИОИНМЕД';
$pageDescription = 'Диагностика «Хабилект», остеопатия, рефлексотерапия, физиотерапия, опытные врачи и персональный план лечения.';

$structuredData = bioinmed_medical_organization_schema();

$websiteStructuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    '@id' => $siteUrl . '/#website',
    'name' => CLINIC_NAME,
    'url' => $siteUrl,
    'inLanguage' => 'ru-RU',
];

$faqStructuredData = bioinmed_faq_schema(array_map(static function ($item) {
    return [
        'q' => $item['question'] ?? '',
        'a' => $item['answer'] ?? '',
    ];
}, is_array($faq_items) ? $faq_items : []));
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
    <link rel="canonical" href="<?php echo $canonicalUrl; ?>">
    <meta name="theme-color" content="#1977b2">
    <?php echo bioinmed_render_social_meta($pageTitle, $pageDescription, $canonicalUrl, [
        'image' => $socialImageUrl,
    ]); ?>
    <?php echo bioinmed_render_favicon_links($iconPath); ?>
    
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    
    <!-- Tailwind CSS CDN -->
    <script src="/public/vendor/tailwind/tailwindcss-cdn.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/vendor/fontawesome/css/all.min.css">
        <script type="application/ld+json">
                <?php echo json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
        </script>
        <script type="application/ld+json">
            <?php echo json_encode($websiteStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
        </script>
        <script type="application/ld+json">
            <?php echo json_encode($faqStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
        </script>
    
    <!-- Фирменные шрифты и стили -->
    <style>
        :root {
            --background: #e4f1fa;
            --foreground: #0f2749;
        }
        
        * {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', 'SF Pro Display', 'SF Pro Text', sans-serif;
        }
        
        html {
            scroll-behavior: smooth;
            background: var(--background);
        }
        
        body {
            background: var(--background);
            color: var(--foreground);
        }
        
        /* Анимация появления при скролле */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in {
            animation: fadeInUp 0.6s ease-out forwards;
        }
        
        a, button {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        ::selection {
            background: rgba(36, 140, 255, 0.3);
            color: #1977b2;
        }

        input:focus, textarea:focus {
            box-shadow: 0 0 0 3px rgba(36, 140, 255, 0.1);
        }


        @media (max-width: 767px) {
            body {
                min-width: 320px;
            }
        }

        /* ── Hero keeps natural height so it doesn't fight the header layout ── */

        @media (prefers-reduced-motion: reduce) {
            .fade-in {
                animation: none;
            }

            * {
                scroll-behavior: auto !important;
            }
        }
    </style>
<?php echo bioinmed_uis_counter_head(); ?>
</head>
<body class="antialiased">
    <!-- Header -->
    <?php
    $header = new Header($brand_colors);
    echo $header->render();
    ?>
    
    <!-- Hero Section -->
    <?php
    $hero = new HeroSection($brand_colors);
    echo $hero->render();
    ?>

    <!-- Stats Block (Key Performance Indicators) -->
    <?php
    $stats = new StatsBlock($brand_colors);
    echo $stats->render();
    ?>

    <!-- Advantages -->
    <?php
    $advantages_section = new AdvantagesBlock($advantages, $brand_colors);
    echo $advantages_section->render();
    ?>

    <!-- Special Offer -->
    <?php
    $offer_section = new SpecialOffer($brand_colors);
    echo $offer_section->render();
    ?>

    <!-- Problems Grid -->
    <?php
    $problems_section = new ProblemsGrid($problems, $brand_colors, [
        'eyebrow' => '',
        'title' => 'С какими проблемами работаем',
        'subtitle' => '',
    ]);
    echo $problems_section->render();
    ?>

    <section class="border-b border-[#e6eef7] bg-[linear-gradient(180deg,#eef7ff_0%,#e4f1fa_100%)] py-12 md:py-16">
        <div class="mx-auto max-w-6xl px-6 md:px-10">
            <div class="rounded-[2rem] border border-[#dbe8f4] bg-white p-6 shadow-[0_16px_40px_rgba(8,36,70,0.07)] md:p-8">
                <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <div>
                        <h2 class="text-[1.4rem] font-bold text-[#0f2749] md:text-[1.75rem]">Ваш маршрут здоровья в Биоинмед</h2>
                    </div>
                    <p class="max-w-2xl text-[0.92rem] leading-relaxed text-[#1977b2]">Маршрут построен так, чтобы пациент видел всю логику лечения целиком: от первого обращения до устойчивого результата.</p>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <article class="rounded-2xl border border-[#dce8f4] bg-[linear-gradient(180deg,#f9fcff_0%,#ffffff_100%)] p-5 shadow-[0_8px_18px_rgba(8,36,70,0.05)]">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#e8f3fc] text-[#1977b2]"><i class="fa-solid fa-user-doctor text-[1rem]"></i></span>
                            <div>
                                <p class="text-[1rem] font-semibold text-[#17446f]">Консультация</p>
                            </div>
                        </div>
                        <p class="mt-3 text-[0.9rem] leading-relaxed text-[#0f2749]">Врач собирает жалобы, историю заболевания и формирует первичное понимание ситуации.</p>
                    </article>
                    <article class="rounded-2xl border border-[#dce8f4] bg-[linear-gradient(180deg,#f9fcff_0%,#ffffff_100%)] p-5 shadow-[0_8px_18px_rgba(8,36,70,0.05)]">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#e8f3fc] text-[#1977b2]"><i class="fa-solid fa-magnifying-glass-chart text-[1rem]"></i></span>
                            <div>
                                <p class="text-[1rem] font-semibold text-[#17446f]">Диагностика</p>
                            </div>
                        </div>
                        <p class="mt-3 text-[0.9rem] leading-relaxed text-[#0f2749]">Подбираются нужные методы обследования, чтобы увидеть причину нарушений, а не только симптомы.</p>
                    </article>
                    <article class="rounded-2xl border border-[#dce8f4] bg-[linear-gradient(180deg,#f9fcff_0%,#ffffff_100%)] p-5 shadow-[0_8px_18px_rgba(8,36,70,0.05)]">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#e8f3fc] text-[#1977b2]"><i class="fa-solid fa-heart-pulse text-[1rem]"></i></span>
                            <div>
                                <p class="text-[1rem] font-semibold text-[#17446f]">Лечение</p>
                            </div>
                        </div>
                        <p class="mt-3 text-[0.9rem] leading-relaxed text-[#0f2749]">Составляется персональный лечебный план с процедурами, рекомендациями и понятной последовательностью шагов.</p>
                    </article>
                    <article class="rounded-2xl border border-[#dce8f4] bg-[linear-gradient(180deg,#f9fcff_0%,#ffffff_100%)] p-5 shadow-[0_8px_18px_rgba(8,36,70,0.05)]">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#e8f3fc] text-[#1977b2]"><i class="fa-solid fa-kit-medical text-[1rem]"></i></span>
                            <div>
                                <p class="text-[1rem] font-semibold text-[#17446f]">Восстановление</p>
                            </div>
                        </div>
                        <p class="mt-3 text-[0.9rem] leading-relaxed text-[#0f2749]">Организм адаптируется к изменениям, а улучшения постепенно закрепляются без лишней перегрузки.</p>
                    </article>
                    <article class="rounded-2xl border border-[#dce8f4] bg-[linear-gradient(180deg,#f9fcff_0%,#ffffff_100%)] p-5 shadow-[0_8px_18px_rgba(8,36,70,0.05)]">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#e8f3fc] text-[#1977b2]"><i class="fa-solid fa-person-walking text-[1rem]"></i></span>
                            <div>
                                <p class="text-[1rem] font-semibold text-[#17446f]">Лечебная физическая активность</p>
                            </div>
                        </div>
                        <p class="mt-3 text-[0.9rem] leading-relaxed text-[#0f2749]">Подключаются упражнения и безопасная физическая нагрузка, чтобы сохранить результат в повседневной жизни.</p>
                    </article>
                    <article class="rounded-2xl border border-[#d8ebdf] bg-[linear-gradient(180deg,#f4fcf8_0%,#ffffff_100%)] p-5 shadow-[0_8px_18px_rgba(8,36,70,0.05)]">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#e7f7ef] text-[#2f9b6a]"><i class="fa-solid fa-star text-[1rem]"></i></span>
                            <div>
                                <p class="text-[1rem] font-semibold text-[#17446f]">Результат</p>
                            </div>
                        </div>
                        <p class="mt-3 text-[0.9rem] leading-relaxed text-[#0f2749]">Цель маршрута - не временный эффект, а более устойчивое самочувствие и понятное движение к восстановлению.</p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <!-- Chief Doctor -->
    <?php
    $chief_doctor_section = new ChiefDoctorBlock($doctors[0], $brand_colors);
    echo $chief_doctor_section->render();
    ?>
    
    <!-- Doctors -->
    <?php
    $doctorOrder = [
        'kondratova-elena-aleksandrovna',
        'nehorosheva-lyudmila-sergeevna',
        'vertlib-valeriya-pavlovna',
        'mayorova-darya-sergeevna',
        'rozhkov-sergei-leonidovich',
    ];
    $doctorOrderMap = array_flip($doctorOrder);
    $doctors_without_chief = array_slice($doctors, 1);
    foreach ($doctors_without_chief as $originalIndex => &$doctorItem) {
        $doctorItem['__original_index'] = $originalIndex;
    }
    unset($doctorItem);
    usort($doctors_without_chief, static function (array $left, array $right) use ($doctorOrderMap): int {
        $leftOrder = $doctorOrderMap[$left['slug'] ?? ''] ?? 999;
        $rightOrder = $doctorOrderMap[$right['slug'] ?? ''] ?? 999;
        if ($leftOrder === $rightOrder) {
            return ($left['__original_index'] ?? 0) <=> ($right['__original_index'] ?? 0);
        }

        return $leftOrder <=> $rightOrder;
    });
    foreach ($doctors_without_chief as &$doctorItem) {
        unset($doctorItem['__original_index']);
    }
    unset($doctorItem);

    $doctors_section = new DoctorsGrid($doctors_without_chief, $brand_colors);
    echo $doctors_section->render();
    ?>

    <!-- FAQ -->
    <?php
    $faq_section = new FAQBlock($faq_items, $brand_colors);
    echo $faq_section->render();
    ?>

    <!-- Reviews -->
    <?php
    $cases_section = new CasesSlider($brand_colors);
    echo $cases_section->render();
    ?>

    <!-- Popular Services -->
    <?php
    $services_section = new ServicesGrid($services, $brand_colors, ['show_images' => false]);
    echo $services_section->render();
    ?>

    <!-- Appointment CTA -->
    <?php
    $appointment_cta = new AppointmentCTA($brand_colors);
    echo $appointment_cta->render();
    ?>
    
    <!-- Contacts -->
    <?php
    $solidarity_section = new SolidarityMedicineBlock($brand_colors);
    echo $solidarity_section->render();
    ?>

    <!-- Contacts -->
    <?php
    $contact_section = new ContactSection($brand_colors);
    echo $contact_section->render();
    ?>

    <!-- Partners -->
    <?php
    $partners_section = new PartnersBlock($brand_colors);
    echo $partners_section->render();
    ?>
    
    <!-- Footer -->
    <?php
    $footer = new Footer($brand_colors);
    echo $footer->render();
    ?>
    
    <script>
        // Doctors slider controls
        (function initDoctorsSlider() {
            var track = document.querySelector('.doctor-slider-track');
            var prev = document.querySelector('.doctor-slider-prev');
            var next = document.querySelector('.doctor-slider-next');
            if (!track || !prev || !next) return;

            function getStep() {
                return Math.max(280, Math.round(track.clientWidth * 0.85));
            }

            prev.addEventListener('click', function() {
                track.scrollBy({ left: -getStep(), behavior: 'smooth' });
            });

            next.addEventListener('click', function() {
                track.scrollBy({ left: getStep(), behavior: 'smooth' });
            });
        })();
    </script>

    <script>
        // Простая анимация появления при скролле
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);
        
        // Наблюдаем за секциями
        document.querySelectorAll('section').forEach(section => {
            observer.observe(section);
        });
    </script>
</body>
</html>
