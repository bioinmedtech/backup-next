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
$iconUrl = $siteUrl . $iconPath;
$socialImageUrl = bioinmed_default_social_image_url();
$canonicalUrl = $siteUrl . '/';
$pageTitle = 'Клиника восстановительной медицины в Москве | БИОИНМЕД';
$pageDescription = 'Диагностика HABILECT, остеопатия, рефлексотерапия, физиотерапия, опытные врачи и персональный план лечения.';

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
    <meta name="theme-color" content="#2fbdef">
    <?php echo bioinmed_render_social_meta($pageTitle, $pageDescription, $canonicalUrl, [
        'image' => $socialImageUrl,
    ]); ?>
    <?php echo bioinmed_render_favicon_links($iconPath); ?>
    
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
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
            --background: #f3f8fd;
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
            background: linear-gradient(to bottom, #f9fcff 0%, #f3f8fd 45%, #eef4fb 100%);
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
            background: rgba(47, 189, 239, 0.3);
            color: #2fbdef;
        }

        input:focus, textarea:focus {
            box-shadow: 0 0 0 3px rgba(47, 189, 239, 0.1);
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

    <!-- Problems Grid -->
    <?php
    $problems_section = new ProblemsGrid($problems, $brand_colors);
    echo $problems_section->render();
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

    <!-- Chief Doctor -->
    <?php
    $chief_doctor_section = new ChiefDoctorBlock($doctors[0], $brand_colors);
    echo $chief_doctor_section->render();
    ?>
    
    <!-- Doctors -->
    <?php
    $doctors_without_chief = array_slice($doctors, 1);
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
    $contact_section = new ContactSection($brand_colors);
    echo $contact_section->render();
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
