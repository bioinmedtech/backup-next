<?php
// Конфигурация и общие данные клиники

define('CLINIC_NAME', 'БИОИНМЕД');
define('CLINIC_SITE_URL', 'https://bioinmed.ru');
define('CLINIC_ICON_PATH', '/public/images/brand/bioinmed-icon.png');
define('CLINIC_PHONE', '+7 (495) 796-03-36');
define('CLINIC_ADDRESS', 'Москва, Оболенский пер., 9А');
define('CLINIC_METRO', 'м. Фрунзенская');
define('CLINIC_EMAIL', 'info@bioinmed.ru');
define('CLINIC_HOURS', 'Ежедневно с 9:00 до 21:00 (без выходных)');
define('CLINIC_TAGLINE', 'Интегративная и восстановительная медицина. Индивидуальный подход к каждому пациенту.');
define('ONLINE_BOOKING_URL', '#contact');
define('CLINIC_VK', 'https://vk.com/bioinmed');
define('CLINIC_TELEGRAM', 'https://t.me/bioinmed');
define('HERO_TITLE', 'Восстановление здоровья через интегративную медицину');
define('HERO_IMAGE', '/public/images/team/kostromina.jpg');
define('RECAPTCHA_SITE_KEY', getenv('BIOINMED_RECAPTCHA_SITE_KEY') ?: '6LfmOs0sAAAAAKHWO2jG24uuWIL7UBy3x7gG8awh');
define('RECAPTCHA_SECRET_KEY', getenv('BIOINMED_RECAPTCHA_SECRET_KEY') ?: '6LfmOs0sAAAAAJQP0aJ3ho1kB7VHy4VeyW_s4GQe');
define('KLIENTIKS_API_ACCOUNT_ID', getenv('BIOINMED_KLIENTIKS_ACCOUNT_ID') ?: '2c9bfa39d606');
define('KLIENTIKS_API_USER_ID', getenv('BIOINMED_KLIENTIKS_USER_ID') ?: '560a4e656f4d');
define('KLIENTIKS_API_TOKEN', getenv('BIOINMED_KLIENTIKS_API_TOKEN') ?: '924c34b977b92cbf644536023d58429c');
define('KLIENTIKS_API_BASE_URL', getenv('BIOINMED_KLIENTIKS_API_BASE_URL') ?: 'https://klientiks.ru/clientix/Restapi');

// Ключевые показатели (статистика)
define('CLINIC_EXPERIENCE_YEARS', '5+');
define('CLINIC_EXPERIENCE_DESC', 'ЛЕТ КЛИНИЧЕСКОГО ОПЫТА');
define('CLINIC_RATING', '5');
define('CLINIC_RATING_DESC', 'ОЦЕНКА ПАЦИЕНТОВ');
define('CLINIC_PATIENTS_YEARLY', '20+');
define('CLINIC_PATIENTS_DESC', 'МЕТОДИК РЕАБИЛИТАЦИИ');
define('CLINIC_LICENSE_TEXT', 'Лицензия');
define('CLINIC_LICENSE_DESC', 'Медицинская лицензия, соблюдение норм СанПиН');

// Цветовая палитра
$brand_colors = [
    'primary' => '#1977b2',      // Основной цвет
    'secondary' => '#0077bd',    // Дополнительный цвет
    'accent' => '#1977b2',       // Акцент
    'mint' => '#5fb5c0',         // Мята
    'success' => '#00d084',      // Зелёный
    'light_bg' => '#f2f8fb',     // Светлый фон
    'white' => '#ffffff',
    'text_dark' => '#0f2749',
    'text_light' => '#2a4b7c',
];

function bioinmed_absolute_url($path = '') {
    $site_url = rtrim((string)CLINIC_SITE_URL, '/');
    $value = trim((string)$path);
    if ($value === '') {
        return $site_url;
    }
    if (preg_match('~^https?://~i', $value)) {
        return $value;
    }
    return $site_url . '/' . ltrim($value, '/');
}

function bioinmed_versioned_asset_path($path = '') {
    $value = trim((string)$path);
    if ($value === '') {
        return '';
    }

    if (preg_match('~^https?://~i', $value)) {
        return $value;
    }

    $normalized = '/' . ltrim($value, '/');
    $full_path = __DIR__ . $normalized;
    if (!is_file($full_path)) {
        return $normalized;
    }

    $version = @filemtime($full_path);
    if (!is_int($version) || $version <= 0) {
        return $normalized;
    }

    return $normalized . '?v=' . $version;
}

function bioinmed_default_social_image_path() {
    return '/public/images/brand/og-preview-bioinmed.png';
}

function bioinmed_default_social_image_url() {
    return bioinmed_absolute_url(bioinmed_default_social_image_path());
}

function bioinmed_uis_counter_head() {
    return <<<HTML
    <!-- UIS -->
    <script type="text/javascript" async src="https://app.uiscom.ru/static/cs.min.js?k=if02ewgEvY95V_mhIKLExPfM0ipC6i1u"></script>
    <!-- UIS -->
    HTML;
}

function bioinmed_render_callback_form(array $options = []) {
    static $instance = 0;
    $instance++;

    $escape = static function ($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    };

    $source_label = trim((string)($options['source_label'] ?? 'Заявка с сайта'));
    $submit_label = trim((string)($options['submit_label'] ?? 'Перезвоните мне'));
    $form_class = trim((string)($options['form_class'] ?? ''));
    $button_class = trim((string)($options['button_class'] ?? ''));

    if ($submit_label === '') {
        $submit_label = 'Перезвоните мне';
    }

    if ($button_class === '') {
        $button_class = 'inline-flex w-full items-center justify-center rounded-full bg-[#1977b2] px-6 py-3 text-[0.98rem] font-semibold text-white transition hover:bg-[#16658f] disabled:cursor-not-allowed disabled:bg-[#a7d7e9] disabled:text-white/90';
    }

    $phone_id = 'callback-phone-' . $instance;
    $consent_id = 'callback-consent-' . $instance;

    return <<<HTML
    <form action="/callback-request.php" method="post" class="js-callback-form {$escape($form_class)}" novalidate>
        <input type="hidden" name="source_label" value="{$escape($source_label)}">
        <input type="hidden" name="page_title" value="">
        <input type="hidden" name="page_url" value="">
        <div class="space-y-3">
            <div>
                <input
                    id="{$escape($phone_id)}"
                    type="tel"
                    name="phone"
                    inputmode="tel"
                    autocomplete="tel"
                    aria-label="Ваш телефон"
                    data-placeholder-default="Ваш телефон"
                    data-placeholder-active="+7 (___) ___-__-__"
                    placeholder="Ваш телефон"
                    class="js-callback-phone w-full rounded-2xl border border-[#d4e3f0] bg-[#f9fcff] px-4 py-3 text-[1rem] text-[#0f2749] outline-none transition placeholder:text-[#0a293c] focus:border-[#1977b2] focus:bg-white"
                    required
                >
            </div>
            <label for="{$escape($consent_id)}" class="flex items-start gap-2 text-[0.84rem] leading-relaxed text-[#0a293c]">
                <input
                    id="{$escape($consent_id)}"
                    type="checkbox"
                    name="consent"
                    value="1"
                    class="js-callback-consent mt-0.5 h-4 w-4 shrink-0 rounded border-[#b8d2e7] text-[#1977b2] focus:ring-[#1977b2]"
                    required
                >
                <span>
                    Я соглашаюсь с условиями
                    <a href="/privacy" class="font-semibold text-[#0a293c] underline decoration-[#bfd9ed] underline-offset-2 hover:text-[#1977b2]">Политики конфиденциальности</a>
                    и
                    <a href="/user-agreement" class="font-semibold text-[#0a293c] underline decoration-[#bfd9ed] underline-offset-2 hover:text-[#1977b2]">Пользовательского соглашения</a>.
                </span>
            </label>
            <button type="submit" class="js-callback-submit {$escape($button_class)}">{$escape($submit_label)}</button>
            <p class="js-callback-status hidden rounded-2xl px-3 py-2 text-[0.82rem] leading-relaxed"></p>
        </div>
    </form>
    HTML;
}

function bioinmed_current_season_slug($timestamp = null) {
    if ($timestamp instanceof DateTimeInterface) {
        $dt = (new DateTimeImmutable($timestamp->format('c')))->setTimezone(new DateTimeZone('Europe/Moscow'));
    } elseif (is_int($timestamp) || (is_string($timestamp) && ctype_digit($timestamp))) {
        $dt = (new DateTimeImmutable('@' . (int)$timestamp))->setTimezone(new DateTimeZone('Europe/Moscow'));
    } else {
        $dt = new DateTimeImmutable('now', new DateTimeZone('Europe/Moscow'));
    }

    $month = (int)$dt->format('n');
    if ($month >= 3 && $month <= 5) {
        return 'spring';
    }
    if ($month >= 6 && $month <= 8) {
        return 'summer';
    }
    if ($month >= 9 && $month <= 11) {
        return 'autumn';
    }

    return 'winter';
}

function bioinmed_social_image_meta($image_url = '', $alt = '') {
    $url = trim((string)$image_url);
    if ($url === '') {
        $url = bioinmed_default_social_image_url();
    }

    if (!preg_match('~^https?://~i', $url)) {
        $url = bioinmed_absolute_url($url);
    }

    $meta = [
        'url' => $url,
        'alt' => trim((string)$alt) !== '' ? trim((string)$alt) : (CLINIC_NAME . ' — клиника восстановительной медицины'),
        'width' => null,
        'height' => null,
        'type' => null,
    ];

    $site_host = (string)parse_url(CLINIC_SITE_URL, PHP_URL_HOST);
    $image_host = (string)parse_url($url, PHP_URL_HOST);
    $image_path = (string)parse_url($url, PHP_URL_PATH);

    if ($image_host === '' || strcasecmp($image_host, $site_host) === 0) {
        $local_path = __DIR__ . '/' . ltrim($image_path, '/');
        if (is_file($local_path)) {
            $image_size = @getimagesize($local_path);
            if (is_array($image_size)) {
                $meta['width'] = isset($image_size[0]) ? (int)$image_size[0] : null;
                $meta['height'] = isset($image_size[1]) ? (int)$image_size[1] : null;
                $meta['type'] = isset($image_size['mime']) ? (string)$image_size['mime'] : null;
            }
        }
    }

    return $meta;
}

function bioinmed_render_social_meta($title, $description, $canonical_url, array $options = []) {
    $escape = static function ($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    };

    $og_type = trim((string)($options['type'] ?? 'website'));
    if ($og_type === '') {
        $og_type = 'website';
    }

    $image_meta = bioinmed_social_image_meta(
        $options['image'] ?? '',
        $options['image_alt'] ?? ($options['twitter_image_alt'] ?? '')
    );

    $lines = [
        '<meta property="og:locale" content="ru_RU">',
        '<meta property="og:site_name" content="' . $escape(CLINIC_NAME) . '">',
        '<meta property="og:type" content="' . $escape($og_type) . '">',
        '<meta property="og:title" content="' . $escape($title) . '">',
        '<meta property="og:description" content="' . $escape($description) . '">',
        '<meta property="og:url" content="' . $escape($canonical_url) . '">',
        '<meta property="og:image" content="' . $escape($image_meta['url']) . '">',
        '<meta property="og:image:url" content="' . $escape($image_meta['url']) . '">',
    ];

    if (stripos($image_meta['url'], 'https://') === 0) {
        $lines[] = '<meta property="og:image:secure_url" content="' . $escape($image_meta['url']) . '">';
    }
    if (!empty($image_meta['type'])) {
        $lines[] = '<meta property="og:image:type" content="' . $escape($image_meta['type']) . '">';
    }
    if (!empty($image_meta['width'])) {
        $lines[] = '<meta property="og:image:width" content="' . $escape((string)$image_meta['width']) . '">';
    }
    if (!empty($image_meta['height'])) {
        $lines[] = '<meta property="og:image:height" content="' . $escape((string)$image_meta['height']) . '">';
    }
    if (!empty($image_meta['alt'])) {
        $lines[] = '<meta property="og:image:alt" content="' . $escape($image_meta['alt']) . '">';
    }

    $lines[] = '<meta name="twitter:card" content="summary_large_image">';
    $lines[] = '<meta name="twitter:title" content="' . $escape($title) . '">';
    $lines[] = '<meta name="twitter:description" content="' . $escape($description) . '">';
    $lines[] = '<meta name="twitter:image" content="' . $escape($image_meta['url']) . '">';
    $domain = (string)parse_url(CLINIC_SITE_URL, PHP_URL_HOST);
    if ($domain !== '') {
        $lines[] = '<meta name="twitter:domain" content="' . $escape($domain) . '">';
    }
    if (!empty($image_meta['alt'])) {
        $lines[] = '<meta name="twitter:image:alt" content="' . $escape($image_meta['alt']) . '">';
    }

    return implode("\n    ", $lines);
}

function bioinmed_render_favicon_links($icon_path = null) {
    $path = trim((string)($icon_path ?? CLINIC_ICON_PATH));
    if ($path === '') {
        return '';
    }

    $escape = static function ($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    };

    $icon = $escape($path);
    return '<link rel="icon" type="image/png" href="' . $icon . '">'
        . "\n    "
        . '<link rel="apple-touch-icon" href="' . $icon . '">';
}

function bioinmed_medical_organization_schema() {
    return [
        '@context' => 'https://schema.org',
        '@type' => 'MedicalClinic',
        '@id' => bioinmed_absolute_url('/#organization'),
        'name' => CLINIC_NAME,
        'url' => CLINIC_SITE_URL,
        'image' => bioinmed_default_social_image_url(),
        'logo' => bioinmed_absolute_url(CLINIC_ICON_PATH),
        'telephone' => [CLINIC_PHONE],
        'email' => CLINIC_EMAIL,
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => CLINIC_ADDRESS,
            'addressLocality' => 'Москва',
            'addressCountry' => 'RU',
        ],
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => '55.7299',
            'longitude' => '37.5797',
        ],
        'openingHoursSpecification' => [[
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => [
                'https://schema.org/Monday',
                'https://schema.org/Tuesday',
                'https://schema.org/Wednesday',
                'https://schema.org/Thursday',
                'https://schema.org/Friday',
                'https://schema.org/Saturday',
                'https://schema.org/Sunday',
            ],
            'opens' => '09:00',
            'closes' => '21:00',
        ]],
        'sameAs' => array_values(array_filter([
            defined('CLINIC_VK') ? CLINIC_VK : '',
            defined('CLINIC_TELEGRAM') ? CLINIC_TELEGRAM : '',
        ])),
    ];
}

function bioinmed_breadcrumb_schema(array $items) {
    $list = [];
    $position = 1;
    foreach ($items as $item) {
        $name = trim((string)($item['name'] ?? ''));
        if ($name === '') {
            continue;
        }
        $entry = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => $name,
        ];
        $url = trim((string)($item['url'] ?? ''));
        if ($url !== '') {
            $entry['item'] = bioinmed_absolute_url($url);
        }
        $list[] = $entry;
    }

    return [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $list,
    ];
}

function bioinmed_render_chief_doctor_summary(array $doctor, array $options = []) {
    $escape = static function ($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    };

    $title = trim((string)($doctor['title'] ?? 'ОСНОВАТЕЛЬ И ГЛАВНЫЙ ВРАЧ'));
    $name = trim((string)($doctor['name'] ?? ''));
    $projectTitle = trim((string)($doctor['project_title'] ?? ''));
    $leadership = trim((string)($doctor['hero_leadership'] ?? ($doctor['leadership'] ?? '')));
    $bio = trim((string)($doctor['bio'] ?? ''));
    $slug = trim((string)($doctor['slug'] ?? ''));
    $showCta = (bool)($options['show_cta'] ?? false);
    $ctaUrl = trim((string)($options['cta_url'] ?? ''));
    $ctaLabel = trim((string)($options['cta_label'] ?? 'Подробнее о враче'));
    $ctaIcon = trim((string)($options['cta_icon'] ?? 'fa-arrow-right'));
    $surfaceClass = trim((string)($options['surface_class'] ?? 'flex flex-col justify-between'));
    $showBioIfTaglineMissing = array_key_exists('show_bio_if_tagline_missing', $options)
        ? (bool)$options['show_bio_if_tagline_missing']
        : true;

    $intro = '';
    if ($slug === 'kostromina-inna-viktorovna') {
        $intro = 'Специализируюсь на сложных случаях. Более 30 лет клинической практики в области детской и взрослой медицины.';
        if ($leadership !== '') {
            $intro .= ' ' . $leadership;
        }
    } elseif ($leadership !== '') {
        $intro = $leadership;
    } elseif ($bio !== '') {
        $intro = $bio;
    }

    $highlightHtml = '';
    $highlights = $doctor['hero_highlights'] ?? [];
    if (!empty($highlights) && is_array($highlights)) {
        $highlightHtml .= '<ul class="mt-4 space-y-2 text-[0.96rem] leading-relaxed text-[#0a293c]">';
        foreach ($highlights as $highlight) {
            $highlightHtml .= '<li class="flex items-start gap-3"><span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-[#1977b2]"></span><span>' . $escape($highlight) . '</span></li>';
        }
        $highlightHtml .= '</ul>';
    }

    $bioHtml = '';
    if ($showBioIfTaglineMissing && empty($doctor['hero_tagline']) && $bio !== '') {
        $bioHtml = '<p class="mt-6 text-[0.98rem] leading-relaxed text-[#0a293c] md:text-[1.03rem]">' . $escape($bio) . '</p>';
    }

    $ctaHtml = '';
    if ($showCta && $ctaUrl !== '') {
        $ctaHtml = '
                    <div class="mt-6">
                        <a href="' . $escape($ctaUrl) . '" class="inline-flex items-center gap-2 rounded-full bg-[#1977b2] px-5 py-2.5 text-[0.92rem] font-semibold text-white shadow-[0_10px_24px_rgba(25,119,178,0.18)] transition hover:bg-[#16658f]">
                            ' . $escape($ctaLabel) . '
                            <i class="fa-solid ' . $escape($ctaIcon) . ' text-[0.72rem]"></i>
                        </a>
                    </div>';
    }

    return '
                <div class="' . $escape($surfaceClass) . '">
                    <div>
                        <p class="mb-2 text-[0.78rem] font-semibold uppercase tracking-[0.2em] text-[#1977b2]">' . $escape($title) . '</p>
                        <h2 class="mt-0 text-[2rem] font-bold leading-tight text-[#0a293c] md:text-[2.35rem]">' . $escape($name) . '</h2>
                        ' . ($projectTitle !== '' ? '<p class="mt-4 text-[0.75rem] font-semibold uppercase tracking-[0.14em] text-[#0a293c]">' . $escape($projectTitle) . '</p>' : '') . '
                        ' . ($intro !== '' ? '<p class="mt-8 text-[1rem] leading-relaxed text-[#0a293c] md:mt-10 md:text-[1.08rem]">' . $escape($intro) . '</p>' : '') . '
                        ' . $bioHtml . '
                        ' . $highlightHtml . '
                    </div>
                    ' . $ctaHtml . '
                </div>';
}

function bioinmed_faq_schema(array $items) {
    $entities = [];
    foreach ($items as $item) {
        $question = trim((string)($item['q'] ?? ''));
        $answer = trim((string)($item['a'] ?? ''));
        if ($question === '' || $answer === '') {
            continue;
        }
        $entities[] = [
            '@type' => 'Question',
            'name' => $question,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $answer,
            ],
        ];
    }

    return [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $entities,
    ];
}

function bioinmed_normalize_service_category($category) {
    $value = strtolower(trim((string)$category));
    if ($value === '') {
        return 'other';
    }

    $aliases = [
        'manual-therapy' => 'manual_therapy',
        'injection-therapy' => 'injection_therapy',
        'infusion-therapy' => 'infusion_therapy',
        'ozone-therapy' => 'ozone_therapy',
        'ozonoterapiya' => 'ozone_therapy',
        'chief-doctor' => 'chief_doctor',
        'musculoskeletal-program' => 'musculoskeletal',
        'physio' => 'physiotherapy',
    ];

    return $aliases[$value] ?? str_replace('-', '_', $value);
}

function bioinmed_slugify_service_name($text) {
    $value = trim((string)$text);
    if ($value === '') {
        return '';
    }

    $value = strtr($value, [
        'А'=>'A','Б'=>'B','В'=>'V','Г'=>'G','Д'=>'D','Е'=>'E','Ё'=>'E','Ж'=>'Zh','З'=>'Z','И'=>'I','Й'=>'Y',
        'К'=>'K','Л'=>'L','М'=>'M','Н'=>'N','О'=>'O','П'=>'P','Р'=>'R','С'=>'S','Т'=>'T','У'=>'U','Ф'=>'F',
        'Х'=>'Kh','Ц'=>'Ts','Ч'=>'Ch','Ш'=>'Sh','Щ'=>'Sch','Ъ'=>'','Ы'=>'Y','Ь'=>'','Э'=>'E','Ю'=>'Yu','Я'=>'Ya',
        'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z','и'=>'i','й'=>'y',
        'к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f',
        'х'=>'kh','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
    ]);
    $latin = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if (!is_string($latin) || $latin === '') {
        $latin = $value;
    }

    $latin = strtolower($latin);
    $latin = preg_replace('/[^a-z0-9]+/', '-', $latin);
    $latin = trim((string)$latin, '-');
    return $latin;
}

function bioinmed_slugify_problem_name($text) {
    return bioinmed_slugify_service_name($text);
}

function bioinmed_localize_service_text($text) {
    $value = (string)$text;
    if ($value === '') {
        return '';
    }

    $replacements = [
        'HABILECT' => 'Хабилект',
        'HILT' => 'ХИЛТ',
        'PRP' => 'ПРП',
        'VIP LINE' => 'ВИП ЛАЙН',
        'Heel' => 'Хеель',
        'HEEЛ' => 'ХЕЕЛЬ',
        'HEEL' => 'ХЕЕЛЬ',
        'Detox' => 'Детокс',
        'detox' => 'детокс',
        'LINE' => 'ЛАЙН',
    ];

    $value = strtr($value, $replacements);
    // Исправление частой смешанной латиницы в русских словах.
    $value = str_replace('Детокc', 'Детокс', $value);
    $value = preg_replace('/\s+/u', ' ', $value);
    return trim((string)$value);
}

function bioinmed_clean_docs_service_title($rawName) {
    $title = trim((string)$rawName);
    $title = preg_replace('/\.html?$/iu', '', $title);
    $title = preg_replace('/\s+-\s+Клиника.+$/iu', '', $title);
    $title = preg_replace('/\s+-\s+медицин.+$/iu', '', $title);
    $title = str_replace(['_', '«', '»'], [' ', '"', '"'], $title);
    $title = preg_replace('/\s+/u', ' ', $title);
    return trim((string)$title);
}

function bioinmed_extract_docs_service_meta($filePath) {
    $raw = @file_get_contents($filePath);
    if (!is_string($raw) || $raw === '') {
        return ['title' => '', 'description' => ''];
    }

    $title = '';
    $description = '';
    $paragraphs = [];

    if (preg_match('/<title>(.*?)<\/title>/isu', $raw, $matches)) {
        $title = html_entity_decode(trim((string)$matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    if (preg_match('/<meta\s+name="description"\s+content="([^"]*)"/isu', $raw, $matches)) {
        $description = html_entity_decode(trim((string)$matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    if (preg_match_all('/<p[^>]*>(.*?)<\/p>/isu', $raw, $matches) && !empty($matches[1])) {
        foreach ($matches[1] as $paragraphHtml) {
            $paragraph = html_entity_decode(strip_tags((string)$paragraphHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $paragraph = preg_replace('/\s+/u', ' ', $paragraph);
            $paragraph = trim((string)$paragraph);
            if ($paragraph === '' || strlen($paragraph) < 40) {
                continue;
            }
            if (
                bioinmed_contains_ci($paragraph, 'cookie') ||
                bioinmed_contains_ci($paragraph, 'wordpress') ||
                bioinmed_contains_ci($paragraph, 'wpcf7') ||
                bioinmed_contains_ci($paragraph, 'персональных данных') ||
                bioinmed_contains_ci($paragraph, 'я подтверждаю свое согласие') ||
                bioinmed_contains_ci($paragraph, '152-фз') ||
                bioinmed_contains_ci($paragraph, 'согласие на передачу и обработку') ||
                bioinmed_contains_ci($paragraph, 'каналов передачи информации')
            ) {
                continue;
            }
            $paragraphs[] = $paragraph;
            if (count($paragraphs) >= 6) {
                break;
            }
        }
    }

    return ['title' => $title, 'description' => $description, 'paragraphs' => $paragraphs];
}

function bioinmed_contains_ci($haystack, $needle) {
    return preg_match('/' . preg_quote((string)$needle, '/') . '/iu', (string)$haystack) === 1;
}

function bioinmed_docs_category_by_path($relativePath, $title) {
    $haystack = (string)($relativePath . ' ' . $title);

    if (bioinmed_contains_ci($haystack, 'инфуз') || bioinmed_contains_ci($haystack, 'капель')) {
        return 'infusion_therapy';
    }
    if (bioinmed_contains_ci($haystack, 'инъек') || bioinmed_contains_ci($haystack, 'prp') || bioinmed_contains_ci($haystack, 'озон')) {
        return 'injection_therapy';
    }
    if (bioinmed_contains_ci($haystack, 'рефлекс') || bioinmed_contains_ci($haystack, 'акупункт')) {
        return 'reflexotherapy';
    }
    if (bioinmed_contains_ci($haystack, 'физио') || bioinmed_contains_ci($haystack, 'hilt') || bioinmed_contains_ci($haystack, 'увт')) {
        return 'physiotherapy';
    }
    if (bioinmed_contains_ci($haystack, 'тейп') || bioinmed_contains_ci($haystack, 'банк')) {
        return 'taping';
    }
    if (bioinmed_contains_ci($haystack, 'психолог')) {
        return 'psychology';
    }
    if (bioinmed_contains_ci($haystack, 'главн') || bioinmed_contains_ci($haystack, 'костромин')) {
        return 'chief_doctor';
    }
    if (bioinmed_contains_ci($haystack, 'остеоп') || bioinmed_contains_ci($haystack, 'массаж')) {
        return 'osteopathy';
    }

    return 'other';
}

function bioinmed_service_image_url($filename) {
    $value = trim((string)$filename);
    if ($value === '') {
        return null;
    }

    return '/public/images/services/' . rawurlencode($value);
}

function bioinmed_service_image_files($service, $limit = 4) {
    static $available = null;

    if ($available === null) {
        $available = [];
        $images_dir = __DIR__ . '/public/images/services';
        if (is_dir($images_dir)) {
            foreach (scandir($images_dir) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $path = $images_dir . '/' . $entry;
                if (is_file($path)) {
                    $available[$entry] = true;
                }
            }
        }
    }

    $service_id = strtolower(trim((string)($service['id'] ?? '')));
    $service_name = strtolower(trim((string)($service['name'] ?? '')));
    $service_category = strtolower(trim((string)($service['category'] ?? 'other')));
    $images = [];

    $push = static function ($filename) use (&$images, $available, $limit) {
        $value = trim((string)$filename);
        if ($value === '' || !isset($available[$value]) || in_array($value, $images, true)) {
            return;
        }
        if (count($images) >= $limit) {
            return;
        }
        $images[] = $value;
    };

    $push_group = static function (array $filenames) use (&$push) {
        foreach ($filenames as $filename) {
            $push($filename);
        }
    };

    $exact_map = [
        'hobilect-diagnostics' => ['habilect.jpg', 'habilect-2.jpg'],
        'chief-doctor-consultation' => ['chief-doctor-consultation.jpg'],
        'hilt-therapy' => ['Advanced Regenerative Therapy Laser.jpg'],
        'acupuncture' => ['acupuncture-therapy.jpg', 'acupuncture-therapy-2.jpg', 'acupuncture-therapy-3.jpg'],
        'shock-wave-therapy' => ['shockwave-therapy.jpg', 'shockwave-therapy-2.jpg', 'shockwave-therapy-3.jpg', 'shockwave-therapy-4.jpg'],
        'infusion-therapy' => ['infusion-therapy.jpg'],
        'cupping' => ['cupping-therapy.jpg', 'cupping-therapy-2.jpg'],
        'fizioterapiya' => ['physiotherapy-treatment.jpg', 'physiotherapy-treatment-2.jpg'],
        'inektsionnaya-terapiya' => ['injection-therapy.jpg', 'injection-therapy-4.jpg', 'injection-therapy-5.jpg'],
        'akupunkturnoe-kross-teypirovanie' => ['cross-taping.jpg', 'cross-taping-2.jpg', 'cross-taping-3.jpg'],
        'elektroforez' => ['electrophoresis-therapy.jpg', 'electrophoresis-therapy-2.jpg', 'electrophoresis-therapy-3.jpg'],
        'transkranialnaya-magnitoterapiya-tkmt' => ['transcranial-therapy.jpg', 'transcranial-therapy-2.jpg'],
        'miostimulyatsiya-vip-layn' => ['vip-line-therapy.jpg', 'vip-line-therapy-2.jpg', 'vip-line-therapy-3.jpg', 'vip-line-therapy-4.jpg'],
        'magnitoterapiya-fotostimulyatsiya-amblio' => ['amblyo-therapy.jpg', 'amblyo-therapy-2.jpg', 'amblyo-therapy-3.jpg', 'amblyo-therapy-4.jpg'],
    ];

    if (isset($exact_map[$service_id])) {
        $push_group($exact_map[$service_id]);
        return array_slice($images, 0, $limit);
    }

    return [];
}

function bioinmed_service_gallery_urls($service, $limit = 4) {
    $files = bioinmed_service_image_files($service, $limit);
    $urls = [];
    foreach ($files as $file) {
        $url = bioinmed_service_image_url($file);
        if ($url !== null) {
            $urls[] = $url;
        }
    }
    return $urls;
}

function bioinmed_service_primary_image_url($service) {
    $gallery = bioinmed_service_gallery_urls($service, 1);
    return $gallery[0] ?? null;
}

// Услуги и связанный статичный контент
$services = require __DIR__ . '/config/services.php';
$faq_items = require __DIR__ . '/config/faqs.php';
$service_aliases = require __DIR__ . '/config/service_aliases.php';

// Нормализация категорий для каталога услуг.
$normalized_services = [];
$max_order = 0;
foreach ($services as $index => $service_item) {
    if (!is_array($service_item)) {
        continue;
    }

    $service_id = trim((string)($service_item['id'] ?? ''));
    $service_name = trim((string)($service_item['name'] ?? ''));
    if ($service_id === '' || $service_name === '') {
        continue;
    }

    $service_item['category'] = bioinmed_normalize_service_category($service_item['category'] ?? 'other');
    $service_item['name'] = bioinmed_localize_service_text($service_item['name'] ?? '');
    if (isset($service_item['subtitle'])) {
        $service_item['subtitle'] = bioinmed_localize_service_text($service_item['subtitle']);
    }
    if (isset($service_item['description'])) {
        $service_item['description'] = bioinmed_localize_service_text($service_item['description']);
    }
    $service_item['order'] = intval($service_item['order'] ?? ($index + 1));
    $max_order = max($max_order, $service_item['order']);
    $normalized_services[$service_id] = $service_item;
}

$services = array_values($normalized_services);
usort($services, function ($a, $b) {
    return intval($a['order'] ?? 9999) <=> intval($b['order'] ?? 9999);
});

// Проблемы пациентов
$problems = [
    [
        'title' => 'Боли в спине и суставах',
        'description' => 'Боль и скованность в спине, шее, плечах или суставах, которые усиливаются при движении, нагрузке или после долгого сидения.',
        'solutions' => 'Диагностика Хабилект, Остеопатия, Мануальная терапия, Стельки Формтотикс, ЛФК, Массаж, Физиотерапия',
        'solution_links' => [
            ['label' => 'Диагностика Хабилект', 'id' => 'hobilect-diagnostics'],
            ['label' => 'Консультация главного врача', 'id' => 'chief-doctor-consultation'],
            ['label' => 'Мануальная терапия', 'id' => 'manual-therapy-session'],
            ['label' => 'Остеопатия', 'id' => 'osteopathy'],
            ['label' => 'ЛФК и реабилитация', 'id' => 'physiotherapy-comprehensive'],
            ['label' => 'Стельки Формтотикс', 'id' => 'orthotic-insoles-formthotics'],
            ['label' => 'Медицинский массаж', 'id' => 'massage'],
            ['label' => 'Физиотерапия', 'id' => 'fizioterapiya'],
        ],
        'icon' => '🔴',
    ],
    [
        'title' => 'Головные боли и мигрени',
        'description' => 'Повторяющиеся головные боли, мигрени, тяжесть в голове и дискомфорт на фоне стресса, усталости или напряжения.',
        'solutions' => 'Консультация главного врача, Мануальная терапия, Остеопатия, Рефлексотерапия, Психотерапия, Инфузионная терапия',
        'solution_links' => [
            ['label' => 'Консультация главного врача', 'id' => 'chief-doctor-consultation'],
            ['label' => 'Консультация невролога/мануального терапевта', 'id' => 'manual-therapy-consultation'],
            ['label' => 'Остеопатия', 'id' => 'osteopathy'],
            ['label' => 'Рефлексотерапия', 'id' => 'acupuncture'],
            ['label' => 'Психотерапия', 'id' => 'psychotherapy'],
            ['label' => 'Инфузионная терапия', 'id' => 'infusion-therapy'],
        ],
        'icon' => '🔴',
    ],
    [
        'title' => 'Сколиоз и нарушение осанки',
        'description' => 'Сутулость, асимметрия плеч и лопаток, нарушение осанки и мышечный дисбаланс.',
        'solutions' => 'Хабилект-диагностика, Реабилитолог, ЛФК, Кинезиотерапия, Стельки Формтотикс, Остеопатия, Физиотерапия',
        'solution_links' => [
            ['label' => 'Диагностика Хабилект', 'id' => 'hobilect-diagnostics'],
            ['label' => 'Приём реабилитолога', 'id' => 'rehabilitation-specialist-consultation'],
            ['label' => 'ЛФК и реабилитация', 'id' => 'physiotherapy-comprehensive'],
            ['label' => 'Кинезиотерапия', 'id' => 'kineziodiagnostics-therapy'],
            ['label' => 'Стельки Формтотикс', 'id' => 'orthotic-insoles-formthotics'],
            ['label' => 'Остеопатия', 'id' => 'osteopathy'],
            ['label' => 'Физиотерапия', 'id' => 'fizioterapiya'],
        ],
        'icon' => '🔴',
    ],
    [
        'title' => 'Плоскостопие, вальгус, варус и деформации стоп',
        'description' => 'Плоскостопие, завал стопы, вальгус или варус, боли в стопах, коленях и пояснице при ходьбе и нагрузке.',
        'solutions' => 'Стельки Формтотикс, Стельки Футмастер, Реабилитолог, Диагностика Хабилект, Остеопатия, ЛФК',
        'solution_links' => [
            ['label' => 'Стельки Формтотикс', 'id' => 'orthotic-insoles-formthotics'],
            ['label' => 'Стельки Футмастер', 'id' => 'orthotic-insoles-footmaster'],
            ['label' => 'Приём реабилитолога', 'id' => 'rehabilitation-specialist-consultation'],
            ['label' => 'Диагностика Хабилект', 'id' => 'hobilect-diagnostics'],
            ['label' => 'ЛФК и реабилитация', 'id' => 'physiotherapy-comprehensive'],
            ['label' => 'Остеопатия', 'id' => 'osteopathy'],
        ],
        'icon' => '🔴',
    ],
    [
        'title' => 'Последствия травм и операций',
        'description' => 'Боль, отёк, скованность или ограничение движения после травмы, операции или длительной иммобилизации.',
        'solutions' => 'Диагностика Хабилект, Реабилитолог, Кинезиотерапия, ЛФК, Массаж, УВТ, ХИЛТ, Физиотерапия, Инъекционная терапия',
        'solution_links' => [
            ['label' => 'Диагностика Хабилект', 'id' => 'hobilect-diagnostics'],
            ['label' => 'Приём реабилитолога', 'id' => 'rehabilitation-specialist-consultation'],
            ['label' => 'Кинезиотерапия', 'id' => 'kineziodiagnostics-therapy'],
            ['label' => 'ЛФК и реабилитация', 'id' => 'physiotherapy-comprehensive'],
            ['label' => 'Медицинский массаж', 'id' => 'massage'],
            ['label' => 'УВТ', 'id' => 'shock-wave-therapy'],
            ['label' => 'ХИЛТ-терапия', 'id' => 'hilt-therapy'],
            ['label' => 'Физиотерапия', 'id' => 'fizioterapiya'],
            ['label' => 'Инъекционная терапия', 'id' => 'inektsionnaya-terapiya'],
        ],
        'icon' => '🔴',
    ],
    [
        'title' => 'Неврологические нарушения',
        'description' => 'Онемение, покалывание, головокружение, слабость, боли по ходу нервов и другие неврологические жалобы.',
        'solutions' => 'Консультация невролога, Диагностика Хабилект, Рефлексотерапия, Остеопатия, Физиотерапия, Инфузионная терапия',
        'solution_links' => [
            ['label' => 'Консультация невролога/мануального терапевта', 'id' => 'manual-therapy-consultation'],
            ['label' => 'Диагностика Хабилект', 'id' => 'hobilect-diagnostics'],
            ['label' => 'Рефлексотерапия', 'id' => 'acupuncture'],
            ['label' => 'Остеопатия', 'id' => 'osteopathy'],
            ['label' => 'Физиотерапия', 'id' => 'fizioterapiya'],
            ['label' => 'Инфузионная терапия', 'id' => 'infusion-therapy'],
        ],
        'icon' => '🔴',
    ],
    [
        'title' => 'Синдром хронической усталости и постковид',
        'description' => 'Слабость, быстрая утомляемость, снижение переносимости нагрузок и проблемы с восстановлением после ковида или затяжной болезни.',
        'solutions' => 'Консультация главного врача, Диагностика Хабилект, Инфузионная терапия, Озонотерапия, Карбокситерапия, Рефлексотерапия, ЛФК',
        'solution_links' => [
            ['label' => 'Консультация главного врача', 'id' => 'chief-doctor-consultation'],
            ['label' => 'Диагностика Хабилект', 'id' => 'hobilect-diagnostics'],
            ['label' => 'Инфузионная терапия', 'id' => 'infusion-therapy'],
            ['label' => 'Озонотерапия', 'id' => 'ozonoterapiya'],
            ['label' => 'Карбокситерапия', 'id' => 'karboksiterapiya-ozonoterapiya'],
            ['label' => 'Рефлексотерапия', 'id' => 'acupuncture'],
            ['label' => 'ЛФК и реабилитация', 'id' => 'physiotherapy-comprehensive'],
        ],
        'icon' => '🔴',
    ],
    [
        'title' => 'Тревога, стресс, проблемы со сном',
        'description' => 'Тревога, внутреннее напряжение, нарушения сна, раздражительность и ощущение постоянного стресса.',
        'solutions' => 'Психотерапия, Рефлексотерапия, Микропунктура, Инфузионная терапия, Консультация главного врача',
        'solution_links' => [
            ['label' => 'Психотерапия', 'id' => 'psychotherapy'],
            ['label' => 'Рефлексотерапия', 'id' => 'acupuncture'],
            ['label' => 'Микропунктура', 'id' => 'mikropunktura-aurikulyarnaya'],
            ['label' => 'Инфузионная терапия', 'id' => 'infusion-therapy'],
            ['label' => 'Консультация главного врача', 'id' => 'chief-doctor-consultation'],
        ],
        'icon' => '🔴',
    ],
    [
        'title' => 'Отсутствие уверенности в себе',
        'description' => 'Снижение уверенности, трудности в общении, страх оценки и ощущение эмоциональной зажатости.',
        'solutions' => 'Психотерапия, Рефлексотерапия, Консультация главного врача, Инфузионная терапия',
        'solution_links' => [
            ['label' => 'Психотерапия', 'id' => 'psychotherapy'],
            ['label' => 'Рефлексотерапия', 'id' => 'acupuncture'],
            ['label' => 'Консультация главного врача', 'id' => 'chief-doctor-consultation'],
            ['label' => 'Инфузионная терапия', 'id' => 'infusion-therapy'],
        ],
        'icon' => '🔴',
    ],
    [
        'title' => 'Малоподвижный образ жизни: лишний вес, метаболический синдром, гипоксия',
        'description' => 'Снижение активности, лишний вес, одышка, быстрая утомляемость и ощущение тяжести в теле.',
        'solutions' => 'Диагностика Хабилект, Реабилитолог, ЛФК, Физиотерапия, Инфузионная терапия, Психотерапия',
        'solution_links' => [
            ['label' => 'Диагностика Хабилект', 'id' => 'hobilect-diagnostics'],
            ['label' => 'Приём реабилитолога', 'id' => 'rehabilitation-specialist-consultation'],
            ['label' => 'ЛФК и реабилитация', 'id' => 'physiotherapy-comprehensive'],
            ['label' => 'Физиотерапия', 'id' => 'fizioterapiya'],
            ['label' => 'Инфузионная терапия', 'id' => 'infusion-therapy'],
            ['label' => 'Психотерапия', 'id' => 'psychotherapy'],
        ],
        'icon' => '🔴',
    ],
    [
        'title' => 'Пожилые пациенты 70+: нарушения памяти, сна, когнитивные функции, шаткость',
        'description' => 'Снижение памяти, сна, устойчивости и общей активности в пожилом возрасте.',
        'solutions' => 'Консультация главного врача, Диагностика Хабилект, Рефлексотерапия, Остеопатия, Реабилитолог, Физиотерапия',
        'solution_links' => [
            ['label' => 'Консультация главного врача', 'id' => 'chief-doctor-consultation'],
            ['label' => 'Диагностика Хабилект', 'id' => 'hobilect-diagnostics'],
            ['label' => 'Рефлексотерапия', 'id' => 'acupuncture'],
            ['label' => 'Остеопатия', 'id' => 'osteopathy'],
            ['label' => 'Приём реабилитолога', 'id' => 'rehabilitation-specialist-consultation'],
            ['label' => 'Физиотерапия', 'id' => 'fizioterapiya'],
        ],
        'icon' => '🔴',
    ],
    [
        'title' => 'Трудности с переносимостью спортивных нагрузок',
        'description' => 'Быстрое утомление, боли после тренировок, снижение выносливости и трудности с восстановлением.',
        'solutions' => 'Диагностика Хабилект, Кинезиотерапия, ЛФК, Стельки Формтотикс, Остеопатия, Физиотерапия, УВТ',
        'solution_links' => [
            ['label' => 'Диагностика Хабилект', 'id' => 'hobilect-diagnostics'],
            ['label' => 'Кинезиотерапия', 'id' => 'kineziodiagnostics-therapy'],
            ['label' => 'ЛФК и реабилитация', 'id' => 'physiotherapy-comprehensive'],
            ['label' => 'Стельки Формтотикс', 'id' => 'orthotic-insoles-formthotics'],
            ['label' => 'Остеопатия', 'id' => 'osteopathy'],
            ['label' => 'Физиотерапия', 'id' => 'fizioterapiya'],
            ['label' => 'УВТ', 'id' => 'shock-wave-therapy'],
        ],
        'icon' => '🔴',
    ],
    [
        'title' => 'Слабый иммунитет и частые ОРВИ',
        'description' => 'Частые ОРВИ, затяжное восстановление, слабость и ощущение, что организм долго не приходит в норму.',
        'solutions' => 'Консультация главного врача, Инфузионная терапия, Озонотерапия, Карбокситерапия, Рефлексотерапия',
        'solution_links' => [
            ['label' => 'Консультация главного врача', 'id' => 'chief-doctor-consultation'],
            ['label' => 'Инфузионная терапия', 'id' => 'infusion-therapy'],
            ['label' => 'Озонотерапия', 'id' => 'ozonoterapiya'],
            ['label' => 'Карбокситерапия', 'id' => 'karboksiterapiya-ozonoterapiya'],
            ['label' => 'Рефлексотерапия', 'id' => 'acupuncture'],
        ],
        'icon' => '🔴',
    ],
    [
        'title' => 'Точная диагностика скрытых проблем опорно-двигательного аппарата и биомеханики',
        'description' => 'Скрытые перегрузки, нарушения биомеханики, осанки и опоры, которые могут проявляться болью или усталостью.',
        'solutions' => 'Диагностика Хабилект, Консультация главного врача, Приём реабилитолога, Кинезиотерапия, Стельки Формтотикс',
        'solution_links' => [
            ['label' => 'Диагностика Хабилект', 'id' => 'hobilect-diagnostics'],
            ['label' => 'Консультация главного врача', 'id' => 'chief-doctor-consultation'],
            ['label' => 'Приём реабилитолога', 'id' => 'rehabilitation-specialist-consultation'],
            ['label' => 'Кинезиотерапия', 'id' => 'kineziodiagnostics-therapy'],
            ['label' => 'Стельки Формтотикс', 'id' => 'orthotic-insoles-formthotics'],
        ],
        'icon' => '🔴',
    ],
];

$problem_common_consultation = [
    'Главный врач, врач ВРТ, гомеопат, интегративный психолог',
    'Заведующая отделением: врач акушер-гинеколог, рефлексотерапевт, физиотерапевт',
    'Врач-невролог, мануальный терапевт',
    'Врач-остеопат',
    'Специалист по реабилитации',
    'Кинезиолог',
];

$problem_common_diagnostics = [
    'Вегетативно-резонансное тестирование',
    '3D-диагностика опорно-двигательного аппарата на мультифункциональном комплексе Хабилект',
    'Диагностика стоп на подоскопе (Формтотикс)',
];

$problem_common_plan = [
    'Формируется персональный маршрут лечения по этапам и приоритетам',
    'Определяются профильные специалисты и последовательность приёмов',
    'Подбирается комбинация методов по клинической задаче и переносимости',
    'Согласовывается программа реабилитации и контрольные визиты',
];

$rehab_tracks = [
    'broad' => [
        'ЛФК.',
        'Стельки Формтотикс/Футмастер по показаниям.',
        'Массаж.',
        'Кинезиотерапия.',
        'Инъекционная и инфузионная терапия (в том числе гомеопунктура Хеель и плазмотерапия).',
        'Озонотерапия/карбокситерапия.',
        'Физиотерапия: УВТ, ХИЛТ, НЛОК, гелиосплазма, электростимуляция, электрофорез, ультрафонофорез, магнитотерапия.',
    ],
    'headache' => [
        'Массаж.',
        'Инъекционная и инфузионная терапия по показаниям.',
        'Озонотерапия/карбокситерапия.',
        'Физиотерапия: УВТ, ХИЛТ, НЛОК, гелиосплазма, электростимуляция, электрофорез, ультрафонофорез, магнитотерапия.',
    ],
    'posture' => [
        'ЛФК.',
        'Стельки Формтотикс/Футмастер.',
        'Массаж.',
        'Физиотерапия: УВТ, электростимуляция, электрофорез, ультрафонофорез, магнитотерапия.',
    ],
];

$result_tracks = [
    'pain' => [
        'Снижение или снятие болевого синдрома.',
        'Улучшение подвижности и переносимости нагрузки.',
        'Уменьшение контрактур и мышечного напряжения.',
        'Общее улучшение самочувствия и качества жизни.',
    ],
    'headache' => [
        'Снижение частоты и интенсивности головной боли.',
        'Стабилизация сна и уровня стресса.',
        'Общее улучшение самочувствия и качества жизни.',
    ],
    'posture' => [
        'Улучшение осанки и устойчивости.',
        'Снижение боли и перегрузки при движении.',
        'Повышение качества жизни.',
    ],
    'neuro' => [
        'Снижение выраженности неврологической симптоматики.',
        'Улучшение сна, когнитивных функций и самочувствия.',
        'Улучшение подвижности, устойчивости и качества жизни.',
    ],
];

$problem_profiles = [
    'Боли в спине и суставах' => ['rehab' => 'broad', 'result' => 'pain'],
    'Головные боли и мигрени' => ['rehab' => 'headache', 'result' => 'headache'],
    'Сколиоз и нарушение осанки' => ['rehab' => 'posture', 'result' => 'posture'],
    'Плоскостопие, вальгус, варус и деформации стоп' => ['rehab' => 'posture', 'result' => 'pain'],
    'Последствия травм и операций' => ['rehab' => 'broad', 'result' => 'pain'],
    'Неврологические нарушения' => ['rehab' => 'broad', 'result' => 'neuro'],
    'Синдром хронической усталости и постковид' => ['rehab' => 'broad', 'result' => 'neuro'],
    'Тревога, стресс, проблемы со сном' => ['rehab' => 'broad', 'result' => 'headache'],
    'Отсутствие уверенности в себе' => ['rehab' => 'broad', 'result' => 'headache'],
    'Малоподвижный образ жизни: лишний вес, метаболический синдром, гипоксия' => ['rehab' => 'broad', 'result' => 'pain'],
    'Пожилые пациенты 70+: нарушения памяти, сна, когнитивные функции, шаткость' => ['rehab' => 'broad', 'result' => 'neuro'],
    'Трудности с переносимостью спортивных нагрузок' => ['rehab' => 'broad', 'result' => 'pain'],
    'Слабый иммунитет и частые ОРВИ' => ['rehab' => 'broad', 'result' => 'headache'],
    'Точная диагностика скрытых проблем опорно-двигательного аппарата и биомеханики' => ['rehab' => 'posture', 'result' => 'posture'],
];

foreach ($problems as &$problem) {
    $title = (string)($problem['title'] ?? '');
    if (!isset($problem_profiles[$title])) {
        continue;
    }

    if (!isset($problem['slug']) || trim((string)$problem['slug']) === '') {
        $problem['slug'] = bioinmed_slugify_problem_name($title);
    }

    $rehab_key = (string)$problem_profiles[$title]['rehab'];
    $result_key = (string)$problem_profiles[$title]['result'];

    $problem['page_title'] = trim($title . ' — БИОИНМЕД');
    $problem['page_description'] = trim((string)($problem['description'] ?? '') . ' Отдельная страница с раскрывающимся описанием проблемы, этапами маршрута лечения и релевантными услугами клиники.');

    $problem['details_sections'] = [
        [
            'title' => '1. Консультация и диагностика врача/специалиста',
            'intro' => 'Сначала врач собирает жалобы, историю состояния и намечает клиническую гипотезу.',
            'items' => $problem_common_consultation,
        ],
        [
            'title' => '2. Функциональная диагностика',
            'intro' => 'На этом этапе уточняем, как работает опорно-двигательный аппарат и где именно есть перегрузка.',
            'items' => $problem_common_diagnostics,
        ],
        [
            'title' => '3. Составление плана лечения',
            'intro' => 'После диагностики формируется персональный маршрут с последовательностью методов и сроками контроля.',
            'items' => $problem_common_plan,
        ],
        [
            'title' => '4. Реабилитация',
            'intro' => 'Подключаем методы, которые помогают безопасно восстановить движение, снизить боль и закрепить результат.',
            'items' => $rehab_tracks[$rehab_key] ?? $rehab_tracks['broad'],
        ],
        [
            'title' => '5. Результат',
            'intro' => 'Цель этапа - устойчивое улучшение самочувствия, подвижности и качества жизни.',
            'items' => $result_tracks[$result_key] ?? $result_tracks['pain'],
        ],
    ];
}
unset($problem);

// Преимущества клиники
$advantages = [
    [
        'title' => 'Современное оборудование',
        'description' => 'Немецкие аппараты Хеель, УВТ, лазерные системы',
        'icon' => '🏥',
    ],
    [
        'title' => 'Опытные врачи',
        'description' => 'Стаж от 10 лет, кандидаты наук, постоянное обучение',
        'icon' => '👨‍⚕️',
    ],
    [
        'title' => 'Безопасные методы',
        'description' => 'Без вреда для печени и органов, натуральные подходы',
        'icon' => '✓',
    ],
    [
        'title' => 'Удобная локация',
        'description' => 'Метро Фрунзенская, 3 минуты пешком',
        'icon' => '📍',
    ],
];

// Врачи клиники
$doctors = [
    [
        'id' => 1,
        'slug' => 'kostromina-inna-viktorovna',
        'name' => 'Костромина Инна Викторовна',
        'title' => 'ОСНОВАТЕЛЬ И ГЛАВНЫЙ ВРАЧ',
        'project_title' => 'Автор лечебно-восстановительного проекта «Хабилект»',
        'specialty' => 'Врач ВРТ и БРТ, гомеопат, интегративный психолог, рефлексотерапевт, гомотоксиколог, аромотерапевт',
        'experience' => 'Более 30 лет врачебной практики',
        'bio' => 'Ведёт пациентов со сложными хроническими, психоэмоциональными, репродуктивными и нейросоматическими состояниями, сочетая классическую и интегративную медицину.',
        'hero_tagline' => 'Определение причины заболевания - Ваш первый шаг к психологическому и физическому здоровью',
        'hero_leadership' => 'Формирую клинические стандарты и авторские методики БИОИНМЕД, разрабатываю персональные маршруты восстановления и курирую работу команды специалистов.',
        'leadership' => 'Формирует клинические стандарты и авторские методики БИОИНМЕД, разрабатывает персональные маршруты восстановления и курирует работу команды специалистов.',
        'hide_standard_sections' => true,
        'custom_sections' => [
            [
                'key' => 'education',
                'title' => 'Образование',
                'icon' => 'fa-solid fa-graduation-cap',
                'card_classes' => 'rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]',
                'icon_bg_classes' => 'bg-[#e8f3fc] text-[#1977b2]',
                'intro' => 'Высококвалифицированный специалист с более чем 30-летним стажем. Имеет фундаментальное педиатрическое и терапевтическое образование, многолетний опыт работы в научной, клинической и управленческой сферах.',
                'subsections' => [
                    [
                        'title' => 'Базовое образование',
                        'items' => [
                            '1995 - Первый Московский медицинский институт имени И. М. Сеченова, специальность «Лечебное дело».',
                            '1996 - Интернатура по специальности «Врач-педиатр».',
                            '1998 - Клиническая ординатура по специальности «Врач-педиатр».',
                            '2000 - Российский университет дружбы народов, курс «Общая и клиническая фито- и ароматерапия».',
                            '2004 - Российский государственный медицинский университет, дерматовенерология.',
                            '2004 - РГМУ, дермато-косметология (кафедра кожных и венерических болезней).',
                        ],
                    ],
                    [
                        'title' => 'Дополнительное постдипломное профессиональное образование',
                        'items' => [
                            '2005 - Миланский университет: курс классической гомеопатии и гомотоксикологии, гомеосинергетическая медицина и кинезиология.',
                            '2006 - Российская медицинская академия последипломного образования: мезотерапия, фармакопунктура.',
                            '2009 - РГМУ, МОНИКИ: дерматовенерология, дерматокосметология.',
                            '2011 - Институт повышения квалификации ФМБА: электропунктурная диагностика по Фоллю, вегетативный резонансный тест, биорезонансная косметология.',
                            '2012 - Московский институт гомеопатии.',
                            '2015-2017 - МУИЦ, Институт психотерапии: практическая психология, эриксоновский гипноз, гештальт-терапия.',
                            '2020 - Медицинский центр «Артемида»: классическая гипнотерапия, регрессивный и мгновенный гипноз.',
                            '2021 - Кубанская медицинская академия: биорезонансная терапия, вегетативный резонансный тест.',
                            '2022 - МЦИД «Артемида»: квантовые техники, работа с временными точками, хроносиматика.',
                            '2023 - Школа карбокситерапии, курс ударно-волновой терапии.',
                            '2024 - Академия ПРП-терапии: плазмолифтинг в косметологии и трихологии.',
                        ],
                    ],
                ],
            ],
            [
                'key' => 'experience',
                'title' => 'Опыт',
                'icon' => 'fa-solid fa-briefcase-medical',
                'card_classes' => 'rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]',
                'icon_bg_classes' => 'bg-[#e8f3fc] text-[#1977b2]',
                'intro' => 'Клиническая практика и профессиональный путь по данным портфолио.',
                'items' => [
                    '1985-1987 - Клиника детских болезней Первого ММИ им. И. М. Сеченова: отделение функциональной диагностики и кафедра патофизиологии.',
                    '1995-1996 - Клиника детских болезней Первого ММИ, врач-интерн.',
                    '1996-1998 - Клиника детских болезней Первого ММИ, врач-ординатор.',
                    '1999-2001 - Центр профориентации при Правительстве г. Москвы, главный специалист отдела психофизиологической реабилитации.',
                    '2000-2001 - Клиническая ароматерапия на кафедре ФУВ РУДН, авторский семинар по влиянию запахов на психоэмоциональное состояние.',
                    '2001-2003 - Медицинский центр «Грация», главный врач.',
                    '2004-2010 - ЗАО «Мартинес Имидж»: научно-методическая и практическая работа, дерматолог, дерматокосметолог.',
                    '2010-2022 - Частная практика.',
                    '2022-2023 - ООО «Медицинский центр инновационных технологий «АРТЕМИДА», заместитель директора по медицинской части.',
                    '2023 - настоящее время - ООО «Клиника гомеопатии и биорегуляции «БИОИНМЕД», главный врач.',
                ],
            ],
            [
                'key' => 'profiling',
                'title' => 'Профилирование',
                'icon' => 'fa-solid fa-bullseye',
                'card_classes' => 'rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]',
                'icon_bg_classes' => 'bg-[#e8f3fc] text-[#1977b2]',
                'items' => [
                    'Сочетает классическую медицину с современными методами интегративной и восстановительной медицины: вегето-резонансная диагностика, кинезиотестирование, биорезонансная терапия, гомеопатия, гомотоксикология, гомеопунктура, биопунктура, мезотерапия, карбокситерапия, озонотерапия, рефлексотерапия, психотерапия, авторские психогенетические методики, ольфактодиагностика, ольфактотерапия и ароматерапия.',
                    'Виртуозно владеет вегето-резонансным тестированием, что позволяет выявлять причины заболеваний на клеточном уровне.',
                    'Автор уникальных методик психогенетической диагностики и естественного омоложения.',
                    'Работает со сложными случаями, включая онкологические, инфекционные, аутоиммунные, эндокринопатии, аллергические кожные, репродуктивные, психосоматические и психогенетические нарушения.',
                    'Уделяет особое внимание психоэмоциональному состоянию пациентов и коррекции глубинных причин болезней.',
                    'Ведёт пациентов от точки зачатия до серебряного возраста и проводит комплексные приёмы для матери и ребёнка.',
                ],
            ],
            [
                'key' => 'author-method-omolozhenie',
                'title' => 'Авторская методика «Естественное омоложение организма»',
                'icon' => 'fa-solid fa-wand-magic-sparkles',
                'card_classes' => 'rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]',
                'icon_bg_classes' => 'bg-[#e8f3fc] text-[#1977b2]',
                'intro' => 'Отдельный авторский подход к восстановлению, поддержанию ресурса и мягкому омоложению организма.',
                'text' => 'Интенсивная авторская программа омоложения, в которой несколько методов работают в связке ради заметного эффекта за один визит.',
                'link_label' => 'Подробнее об услуге',
                'link_href' => '/services/avtorskaya-metodika-ekosistema-estestvennoe-omolozhenie',
            ],
            [
                'key' => 'treatment-practice-directions',
                'title' => 'Направления лечебной практики',
                'icon' => 'fa-solid fa-list-check',
                'card_classes' => 'rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]',
                'icon_bg_classes' => 'bg-[#e8f3fc] text-[#1977b2]',
                'subsections' => [
                    [
                        'title' => '1. Заболевания нервной системы и опорно-двигательного аппарата',
                        'items' => [
                            'Нервная система: головные боли напряжения, мигрени, головокружения, вегетососудистая дистония (ВСД), панические атаки, невропатии лицевого и тройничного нерва.',
                            'Неврозы, тики, последствия стрессов и психоэмоциональных перегрузок.',
                            'Нарушения сна: бессонница, лунатизм, энурез.',
                            'Посттравматические и послеоперационные неврологические состояния.',
                            'Неврологические нарушения у детей: задержка нервно-психического развития, гипервозбудимость, последствия перинатальной патологии.',
                            'Опорно-двигательный аппарат: боли в спине, остеохондроз, дорсопатии, межпозвонковые грыжи, протрузии.',
                            'Заболевания суставов: артриты, артрозы, периартриты.',
                            'Мышечно-тонические синдромы, миофасциальные боли.',
                            'Последствия травм, в том числе спортивных, и операций.',
                            'Нарушения осанки, сколиоз у детей и взрослых.',
                        ],
                    ],
                    [
                        'title' => '2. Репродуктивная и перинатальная медицина',
                        'items' => [
                            'Дисфункции и заболевания репродуктивной системы мужчин и женщин.',
                            'Заболевания урогенитального тракта у женщин и мужчин, гинекологические заболевания.',
                            'Проблемы зачатия, ведение беременности и родов.',
                            'Послеродовой период у мам и малышей, включая поддержку грудного вскармливания.',
                            'Неврологические нарушения в грудном и раннем детском возрасте.',
                            'Адаптация к детскому саду и школе.',
                        ],
                    ],
                    [
                        'title' => '3. Психотерапия и психосоматика',
                        'items' => [
                            'Панические атаки, страхи, тревожные состояния.',
                            'Нарушения сна, лунатизм, энурез.',
                            'Психосоматические, психоэмоциональные проблемы и психотравмы.',
                            'Психогенетические нарушения, негативные жизненные сценарии и внутренние конфликты.',
                            'Замкнутый круг повторяющихся событий и глубинные деструктивные сценарии.',
                        ],
                    ],
                    [
                        'title' => '4. Эндокринология и аутоиммунные нарушения',
                        'items' => [
                            'Метаболический синдром.',
                            'Сахарный диабет и заболевания щитовидной железы.',
                            'Аутоиммунные процессы и метаболические нарушения.',
                        ],
                    ],
                    [
                        'title' => '5. Онкология и реабилитация',
                        'items' => [
                            'Диагностика и сопровождение онкологических больных',
                            'Сопровождение в послеоперационном периоде',
                            'Устранение рецидивов',
                        ],
                    ],
                    [
                        'title' => '6. Аллергология и дерматология',
                        'items' => [
                            'Аллергические реакции и кожные заболевания.',
                            'Дерматокосметология.',
                        ],
                    ],
                    [
                        'title' => '7. Эстетическая медицина и омоложение',
                        'items' => [
                            'Мезотерапия, биопунктура, ПРП-терапия, Лаеннек- и Мэлсмон-терапия.',
                            'Карбокситерапия.',
                        ],
                    ],
                    [
                        'title' => '8. Общая терапия',
                        'items' => [
                            'Заболевания внутренних органов',
                        ],
                    ],
                ],
            ],
        ],
        'specializations' => [
            'Вегетативно-резонансная диагностика и биорезонансная терапия',
            'Классическая гомеопатия, гомотоксикология и фармакопунктура',
            'Психотерапевтическое и психогенетическое сопровождение',
            'Ольфактотерапия, рефлексотерапия и восстановительные методики',
        ],
        'education' => 'Первый Московский медицинский институт им. И. М. Сеченова, интернатура и ординатура по педиатрии, последипломная подготовка по гомеопатии, психотерапии, ароматерапии, рефлексотерапии и биорезонансной терапии.',
        'focus' => [
            'Комплексные программы при хронических, аутоиммунных и психосоматических состояниях',
            'Выявление первопричин симптомов на клеточном и функциональном уровне',
            'Персональные схемы восстановления для взрослых и детей',
        ],
        'services' => [
            'priem-glavnogo-vracha-kliniki-kostrominoy-i-v',
            'chief-doctor-consultation',
            'hobilect-diagnostics',
            'acupuncture',
            'refleksoterapiya',
            'lechebno-diagnosticheskiy-priem',
        ],
        'image' => 'kostromina-default.jpg',
    ],
    [
        'id' => 7,
        'slug' => 'rozhkov-sergei-leonidovich',
        'name' => 'Рожков Сергей Леонидович',
        'title' => 'Инструктор-методист АФК, соавтор лечебного проекта «Хабилект»',
        'project_title' => 'Соавтор лечебно-восстановительного проекта «Хабилект»',
        'specialty' => 'Адаптивная физическая культура, восстановительные программы, спортивная методика',
        'experience' => 'Более 15 лет в спортивной и методической работе',
        'bio' => 'Заслуженный мастер спорта и чемпион мира по биатлону. Работает с восстановлением после травм и операций, сочетая спортивную методику, АФК и научный подход.',
        'specializations' => [
            'Адаптивная физическая культура и индивидуальные восстановительные программы',
            'Реабилитация после травм и операций на опорно-двигательном аппарате',
            'Научно-методическое сопровождение и тренировочные протоколы',
            'Работа со спортсменами и пациентами после функциональных перегрузок',
        ],
        'education' => 'Мурманский государственный педагогический институт, Мурманский государственный технический университет, дополнительная подготовка по теории и методике адаптивной физической культуры в Чайковской государственной академии физической культуры.',
        'focus' => [
            'Восстановление после операций, травм и снижения двигательной активности',
            'Коррекция нарушений опорно-двигательного аппарата и безопасное возвращение к нагрузке',
            'Работа со спортсменами и пациентами, которым нужна структурированная реабилитация',
        ],
        'services' => [
            'physiotherapy-comprehensive',
            'taping',
            'kinezioteypirovanie',
            'massage',
            'massazh',
        ],
        'image' => 'rozhkov.jpg',
    ],
    [
        'id' => 4,
        'slug' => 'kondratova-elena-aleksandrovna',
        'name' => 'Кондратова Елена Александровна',
        'title' => 'АКУШЕР-ГИНЕКОЛОГ, РЕФЛЕКСОТЕРАПЕВТ',
        'specialty' => 'Заведующая отделением физиотерапии, акушер-гинеколог, рефлексотерапевт',
        'experience' => 'Более 30 лет врачебной практики',
        'bio' => 'Сочетает классическую акушерско-гинекологическую школу с рефлексотерапией и восстановительными методиками. Ведёт программы по репродуктивному здоровью, боли, реабилитации и эстетической гинекологии.',
        'specializations' => [
            'Акушерство, гинекология и репродуктивное здоровье',
            'Корпоральная акупунктура, су-джок, кранио- и аурикулотерапия',
            'Фармакопунктура, гомеопунктура, ПРП и кетгут-терапия',
            'Вакуумная терапия, тейпирование и авторские программы рефлексотерапии',
        ],
        'education' => 'Владивостокский государственный медицинский университет, государственные сертификаты по акушерству и гинекологии, рефлексотерапии и физиотерапии, дополнительная подготовка по фармакопунктуре, кетгут-терапии и эстетической гинекологии.',
        'focus' => [
            'Подготовка к беременности и сопровождение при бесплодии и гинекологических нарушениях',
            'Головные боли, боли в спине, неврологические и вегетативные нарушения',
            'Омоложение, реабилитация и восстановление после нагрузок и травм',
        ],
        'services' => [
            'ekspress-priem-zav-otdeleniya-vracha-akushera-ginekologa-refleksoterapevta-kondratovoy-e-a',
            'avtorskaya-programma-kondratovoy-e-a-zhenskoe-zdorove',
            'akupunkturnyy-avtorskiy-metod-kondratovoy-e-a-vostochnyy-ekspress',
            'refleksoterapiya',
            'korporalnaya-iglorefleksoterapiya',
            'fizioterapiya',
            'mikropunktura-lazernaya',
            'mikropunktura-aurikulyarnaya',
            'semyanoterapiya',
        ],
        'image' => 'kondratova.jpg',
    ],
    [
        'id' => 9,
        'slug' => 'navrozov-evgeniy-sergeevich',
        'name' => 'Наврозов Евгений Сергеевич',
        'title' => 'Остеопат, кинезиолог',
        'specialty' => 'Остеопат, прикладной кинезиолог, специалист по медицинскому массажу',
        'experience' => 'Более 6 лет клинической практики',
        'bio' => 'Ведёт пациентов с функциональными нарушениями опорно-двигательного аппарата, мышечным дисбалансом и последствиями перегрузок. Сочетает остеопатическую диагностику, кинезиологическое тестирование и мягкие телесные техники для восстановления движения и снижения боли.',
        'specializations' => [
            'Остеопатическая диагностика и мягкая мануальная коррекция',
            'Прикладная кинезиология и функциональное мышечное тестирование',
            'Медицинский массаж и восстановительные протоколы при мышечно-скелетных перегрузках',
            'Комплексный подход к биомеханике, осанке и двигательному стереотипу',
        ],
        'education' => 'Европейская школа остеопатии (ESO), Академия прикладной кинезиологии и мануальной терапии проф. Л. Ф. Васильевой, Университет образовательной медицины по направлению нутрициологии и превентивного управления здоровьем, Медицинский колледж №1 по специальности «Сестринское дело».',
        'focus' => [
            'Боли в спине, шее и суставах, мышечные зажимы и ограничение подвижности',
            'Нарушения биомеханики, осанки и двигательного паттерна',
            'Восстановление после физических перегрузок и хронического мышечного напряжения',
        ],
        'services' => [
            'osteopathy',
            'kineziodiagnostics-therapy',
            'massage',
            'massage-course',
        ],
        'image' => 'navrozov.jpg',
    ],
    [
        'id' => 10,
        'slug' => 'fomichev-dmitriy-viktorovich',
        'name' => 'Фомичев Дмитрий Викторович',
        'title' => 'Невролог, мануальный терапевт',
        'specialty' => 'Неврология, остеопатия, медицинская реабилитация, интегративный подход к боли',
        'experience' => 'Более 16 лет клинической практики',
        'bio' => 'Специализируется на диагностике и лечении острой и хронической боли. В практике сочетает доказательную неврологию с остеопатическими и реабилитационными методиками, работая не только с симптомами, но и с причинами боли. Член Ассоциации специалистов медицины боли России.',
        'specializations' => [
            'Диагностика и лечение головной боли напряжения, мигрени, головокружений и шума в ушах',
            'Ведение пациентов с радикулопатиями, межпозвонковыми грыжами и заболеваниями периферической нервной системы',
            'Остеопатическое лечение, медицинская реабилитация и восстановление функций опорно-двигательного аппарата',
            'Работа с дисфункциями ВНЧС, хронической болью в спине и суставах',
        ],
        'focus' => [
            'Головные боли напряжения, мигрени, нарушения памяти и внимания',
            'Головокружения, шум в ушах, заболевания периферической нервной системы',
            'Острая и хроническая боль в спине, суставах, дисфункции опорно-двигательного аппарата и ВНЧС',
        ],
        'services' => [
            'osteopathy',
            'shock-wave-therapy',
            'prp-therapy',
            'infusion-therapy',
            'inektsionnaya-terapiya',
            'taping',
        ],
        'image' => 'fomichev.jpg',
    ],
    [
        'id' => 8,
        'slug' => 'mayorova-darya-sergeevna',
        'name' => 'Майорова Дарья Сергеевна',
        'title' => 'Специалист по реабилитации и инструктор ЛФК',
        'specialty' => 'ЛФК, реабилитация, массаж, подиатрия, ортопедические стельки',
        'experience' => '15 лет практики',
        'bio' => 'Специализируется на диагностике опорно-двигательного аппарата, восстановлении после травм и операций, коррекции сколиоза и подборе индивидуальных ортопедических решений.',
        'custom_sections' => [
            [
                'key' => 'appointment-structure',
                'title' => 'Структура приёма',
                'icon' => 'fa-solid fa-clipboard-list',
                'card_classes' => 'rounded-3xl border border-[#d9e7f3] bg-white shadow-[0_8px_28px_rgba(8,36,70,0.06)]',
                'icon_bg_classes' => 'bg-[#e8f3fc] text-[#1977b2]',
                'intro' => 'Длительность приёма: 60-120 минут (в зависимости от клинической задачи и наполнения визита).',
                'subsections' => [
                    [
                        'title' => '1. Сбор анамнеза и функциональных жалоб',
                        'items' => [
                            'Жалобы: боль, скованность, нестабильность, усталость, нарушение походки.',
                            'Хронология симптомов: когда началось, что усиливает или уменьшает проявления.',
                            'Перенесённые травмы, операции, хронические заболевания и текущая активность.',
                        ],
                    ],
                    [
                        'title' => '2. Диагностика опорно-двигательного аппарата',
                        'items' => [
                            'Диагностика на мультифункциональном комплексе Хабилект: оценка мышечного баланса и биомеханики.',
                            'Визуальный осмотр и пальпация в покое и в движении: осанка, позвоночник, таз, длина ног.',
                            'Осмотр стоп на плантографе и функциональные пробы: тест Адамса, присед, наклон, подъём ноги.',
                            'Оценка походки: обычный шаг, ходьба на носках и пятках, приставной шаг.',
                        ],
                    ],
                    [
                        'title' => '3. Формирование карты реабилитации',
                        'items' => [
                            'Определяются зоны гипотонуса и гипертонуса.',
                            'Фиксируется вид воздействия для каждой зоны: ЛФК, массаж, активация, коррекция опоры.',
                            'Пациент получает персональную карту с целями и этапами восстановления.',
                        ],
                    ],
                    [
                        'title' => '4. Формовка ортопедических стелек (по показаниям)',
                        'items' => [
                            'Термоформование или подбор готовой модели с подгонкой.',
                            'Обязательная проверка в стоянии и при ходьбе 2-3 минуты.',
                            'Критерий безопасности: после подбора не должно появляться новой боли.',
                        ],
                    ],
                    [
                        'title' => '5. Установочное занятие по ЛФК (15-20 минут)',
                        'items' => [
                            'Базовые упражнения на коррекцию выявленных двигательных паттернов.',
                            'При сколиозе используется подход S.E.A.S. (де-ротация и стабилизация).',
                            'Обучение дыхательному стереотипу и домашнее задание: 2-3 упражнения.',
                        ],
                    ],
                    [
                        'title' => '6. Массаж (по карте реабилитации)',
                        'items' => [
                            'Выполняется после ЛФК при наличии показаний.',
                            'Тип выбирается индивидуально: медицинский, спортивный или релакс-формат.',
                        ],
                    ],
                    [
                        'title' => '7. Заключение и план лечения',
                        'items' => [
                            'Формулируется функциональный диагноз и индивидуальный маршрут восстановления.',
                            'Определяется график повторных визитов и контрольных оценок.',
                            'Выдаются рекомендации по обуви, домашнему режиму, рабочему месту и нагрузкам.',
                        ],
                    ],
                ],
            ],
        ],
        'specializations' => [
            'Диагностика опорно-двигательного аппарата и индивидуальные программы ЛФК',
            'Реабилитация после травм и операций на суставах и позвоночнике',
            'Медицинский массаж и коррекция сколиоза по методике S.E.A.S.',
            'Подиатрическая диагностика и изготовление индивидуальных ортопедических стелек',
        ],
        'education' => 'Российский государственный университет физической культуры, спорта, молодёжи и туризма, профессиональная переподготовка по лечебной физкультуре и реабилитации, дополнительное обучение по массажу, подиатрии, Футмастер и Формтотикс.',
        'focus' => [
            'Восстановление после травм, операций и заболеваний опорно-двигательного аппарата',
            'Сколиоз, нарушения осанки, плоскостопие и коррекция стопы',
            'Реабилитация спортсменов и пациентов с разным уровнем физической подготовки',
        ],
        'services' => [
            'physiotherapy-comprehensive',
            'hobilect-diagnostics',
            'taping',
            'kinezioteypirovanie',
            'massage',
            'massazh',
        ],
        'image' => 'mayorova.jpg',
    ],
    [
        'id' => 6,
        'slug' => 'vertlib-valeriya-pavlovna',
        'name' => 'Вертлиб Валерия Павловна',
        'title' => 'Врач-остеопат, невролог',
        'specialty' => 'Невролог, остеопат, рефлексотерапевт',
        'experience' => 'Более 20 лет врачебной практики',
        'bio' => 'Комплексно ведёт пациентов с нейросоматическими дисфункциями, хронической болью, психосоматическими состояниями и нарушениями опорно-двигательного аппарата. Работает со взрослыми, детьми и беременными.',
        'specializations' => [
            'Структуральная, краниальная, висцеральная, миофасциальная и биодинамическая остеопатия',
            'Нейропролотерапия и перепрограммирование двигательного стереотипа',
            'Рефлексотерапия и мягкие релакс-техники',
            'Остеопатическое сопровождение беременности, детей и пациентов с ВНЧС',
        ],
        'education' => 'Кубанский государственный медицинский университет, интернатура по неврологии, повышение квалификации по детской неврологии, переподготовка по рефлексотерапии, Институт остеопатии Санкт-Петербурга.',
        'focus' => [
            'Острые и хронические болевые синдромы, нейропатии и головные боли',
            'Психосоматические состояния, синдром хронической усталости и последствия стресса',
            'Педиатрическая и перинатальная остеопатия, посттравматическое восстановление',
        ],
        'services' => [
            'diagnostika-i-konsultatsiya-vracha-osteopata-nevrologa-vertlib-v-p',
            'osteopathy',
            'acupuncture',
            'refleksoterapiya',
            'prp-therapy',
            'prp-terapiya',
        ],
        'image' => 'vertlib.jpg',
    ],
    [
        'id' => 3,
        'slug' => 'nehorosheva-lyudmila-sergeevna',
        'name' => 'Нехорошева Людмила Сергеевна',
        'title' => 'Стоматолог, КАНДИДАТ МЕДИЦИНСКИХ НАУК, ВРАЧ-ОСТЕОПАТ, МАНУАЛЬНЫЙ ТЕРАПЕВТ',
        'specialty' => 'Стоматолог, кандидат медицинских наук, врач-остеопат, мануальный терапевт',
        'experience' => 'Более 20 лет врачебной практики',
        'bio' => 'Работает на стыке остеопатии и стоматологии: сопровождает ортодонтическое лечение, коррекцию ВНЧС, детские и взрослые функциональные нарушения, применяя мягкие безопасные техники.',
        'specializations' => [
            'Структуральная, краниальная, висцеральная и миофасциальная остеопатия',
            'Остеопатическое сопровождение ортодонтического и ортопедического лечения',
            'Сомато-эмоциональное освобождение и лимфодренаж',
            'Эстетическое моделирование лица и работа с ВНЧС',
        ],
        'education' => 'Тверская государственная медицинская академия, ординатура и аспирантура в МГМУ им. Мечникова, обучение в российских и международных школах остеопатии, мануальной терапии и краниосакральных техник.',
        'focus' => [
            'Дисфункции ВНЧС, прикуса и зубочелюстной системы',
            'Детская остеопатия, осанка, неврологические и посттравматические состояния',
            'Эстетическая и психосоматическая остеопатия у взрослых',
        ],
        'services' => [
            'diagnostika-i-konsultatsiya-vracha-osteopata-nekhoroshevoy-l-s',
            'osteopathy',
            'priem-detskogo-osteopata',
            'massage',
            'massazh',
        ],
        'image' => 'nehorosheva.jpg',
    ],
];
?>
