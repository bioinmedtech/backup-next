<?php
// Конфигурация и общие данные клиники

function bioinmed_bootstrap_site_data() {
    $path = __DIR__ . '/data/content/ru/site.json';
    if (!is_file($path)) {
        return [];
    }

    $raw = @file_get_contents($path);
    if (!is_string($raw) || $raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function bioinmed_bootstrap_get(array $source, $key_path, $default = null) {
    $current = $source;
    foreach (array_filter(explode('.', (string)$key_path), static function ($part) { return $part !== ''; }) as $part) {
        if (!is_array($current) || !array_key_exists($part, $current)) {
            return $default;
        }
        $current = $current[$part];
    }
    return $current;
}

$bioinmed_site_data = bioinmed_bootstrap_site_data();

define('CLINIC_NAME', (string)bioinmed_bootstrap_get($bioinmed_site_data, 'clinic.name', 'БИОИНМЕД'));
define('CLINIC_SITE_URL', (string)bioinmed_bootstrap_get($bioinmed_site_data, 'clinic.site_url', 'https://bioinmed.ru'));
define('CLINIC_ICON_PATH', (string)bioinmed_bootstrap_get($bioinmed_site_data, 'clinic.icon_path', '/public/images/brand/bioinmed-icon.png'));
define('CLINIC_PHONE', (string)bioinmed_bootstrap_get($bioinmed_site_data, 'clinic.phone', '+7 (495) 796-03-36'));
define('CLINIC_ADDRESS', (string)bioinmed_bootstrap_get($bioinmed_site_data, 'clinic.address', 'Москва, Оболенский пер., 9А'));
define('CLINIC_METRO', (string)bioinmed_bootstrap_get($bioinmed_site_data, 'clinic.metro', 'м. Фрунзенская'));
define('CLINIC_EMAIL', (string)bioinmed_bootstrap_get($bioinmed_site_data, 'clinic.email', 'info@bioinmed.ru'));
define('CLINIC_HOURS', (string)bioinmed_bootstrap_get($bioinmed_site_data, 'clinic.hours', 'Ежедневно с 9:00 до 21:00 (без выходных)'));
define('CLINIC_TAGLINE', (string)bioinmed_bootstrap_get($bioinmed_site_data, 'clinic.tagline', 'Интегративная и восстановительная медицина. Индивидуальный подход к каждому пациенту.'));
define('ONLINE_BOOKING_URL', (string)bioinmed_bootstrap_get($bioinmed_site_data, 'clinic.online_booking_url', '#contact'));
define('CLINIC_VK', (string)bioinmed_bootstrap_get($bioinmed_site_data, 'clinic.vk', 'https://vk.com/bioinmed'));
define('CLINIC_TELEGRAM', (string)bioinmed_bootstrap_get($bioinmed_site_data, 'clinic.telegram', 'https://t.me/bioinmed'));
define('HERO_TITLE', (string)bioinmed_bootstrap_get($bioinmed_site_data, 'clinic.hero_title', 'Восстановление здоровья через интегративную медицину'));
define('HERO_IMAGE', (string)bioinmed_bootstrap_get($bioinmed_site_data, 'clinic.hero_image', '/public/images/team/kostromina.jpg'));
define('RECAPTCHA_SITE_KEY', getenv('BIOINMED_RECAPTCHA_SITE_KEY') ?: '6LfmOs0sAAAAAKHWO2jG24uuWIL7UBy3x7gG8awh');
define('RECAPTCHA_SECRET_KEY', getenv('BIOINMED_RECAPTCHA_SECRET_KEY') ?: '6LfmOs0sAAAAAJQP0aJ3ho1kB7VHy4VeyW_s4GQe');
define('KLIENTIKS_API_ACCOUNT_ID', getenv('BIOINMED_KLIENTIKS_ACCOUNT_ID') ?: '2c9bfa39d606');
define('KLIENTIKS_API_USER_ID', getenv('BIOINMED_KLIENTIKS_USER_ID') ?: '560a4e656f4d');
define('KLIENTIKS_API_TOKEN', getenv('BIOINMED_KLIENTIKS_API_TOKEN') ?: '924c34b977b92cbf644536023d58429c');
define('KLIENTIKS_API_BASE_URL', getenv('BIOINMED_KLIENTIKS_API_BASE_URL') ?: 'https://klientiks.ru/clientix/Restapi');

// Ключевые показатели (статистика)
define('CLINIC_EXPERIENCE_YEARS', (string)bioinmed_bootstrap_get($bioinmed_site_data, 'stats.experience_years', '5+'));
define('CLINIC_EXPERIENCE_DESC', (string)bioinmed_bootstrap_get($bioinmed_site_data, 'stats.experience_desc', 'ЛЕТ КЛИНИЧЕСКОГО ОПЫТА'));
define('CLINIC_RATING', (string)bioinmed_bootstrap_get($bioinmed_site_data, 'stats.rating', '5'));
define('CLINIC_RATING_DESC', (string)bioinmed_bootstrap_get($bioinmed_site_data, 'stats.rating_desc', 'ОЦЕНКА ПАЦИЕНТОВ'));
define('CLINIC_PATIENTS_YEARLY', (string)bioinmed_bootstrap_get($bioinmed_site_data, 'stats.patients_yearly', '20+'));
define('CLINIC_PATIENTS_DESC', (string)bioinmed_bootstrap_get($bioinmed_site_data, 'stats.patients_desc', 'МЕТОДИК РЕАБИЛИТАЦИИ'));
define('CLINIC_LICENSE_TEXT', (string)bioinmed_bootstrap_get($bioinmed_site_data, 'stats.license_text', 'Лицензия'));
define('CLINIC_LICENSE_DESC', (string)bioinmed_bootstrap_get($bioinmed_site_data, 'stats.license_desc', 'Медицинская лицензия, соблюдение норм СанПиН'));

// Цветовая палитра
$brand_colors = [
    'primary' => '#1977b2',
    'secondary' => '#0077bd',
    'accent' => '#1977b2',
    'mint' => '#5fb5c0',
    'success' => '#00d084',
    'light_bg' => '#f2f8fb',
    'white' => '#ffffff',
    'text_dark' => '#0f2749',
    'text_light' => '#2a4b7c',
];
$site_brand_colors = bioinmed_bootstrap_get($bioinmed_site_data, 'brand_colors', []);
if (is_array($site_brand_colors)) {
    $brand_colors = array_merge($brand_colors, $site_brand_colors);
}

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

function bioinmed_content_json_path($file_name) {
    $name = trim((string)$file_name);
    if ($name === '') {
        return '';
    }

    return __DIR__ . '/data/content/ru/' . $name;
}

function bioinmed_read_json_file($file_name) {
    static $cache = [];

    $path = bioinmed_content_json_path($file_name);
    if ($path === '') {
        return [];
    }

    if (array_key_exists($path, $cache)) {
        return $cache[$path];
    }

    if (!is_file($path)) {
        $cache[$path] = [];
        return $cache[$path];
    }

    $raw = @file_get_contents($path);
    if (!is_string($raw) || $raw === '') {
        $cache[$path] = [];
        return $cache[$path];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        $cache[$path] = [];
        return $cache[$path];
    }

    $cache[$path] = $decoded;
    return $cache[$path];
}

function bioinmed_json_get($source, $key_path, $default = null) {
    $current = $source;
    $parts = array_filter(explode('.', (string)$key_path), static function ($part) {
        return $part !== '';
    });

    foreach ($parts as $part) {
        if (!is_array($current) || !array_key_exists($part, $current)) {
            return $default;
        }
        $current = $current[$part];
    }

    return $current;
}

function bioinmed_text($key_path, $default = '') {
    static $texts = null;
    if (!is_array($texts)) {
        $texts = bioinmed_read_json_file('texts.json');
    }

    $value = bioinmed_json_get($texts, $key_path, $default);
    return is_scalar($value) ? (string)$value : (string)$default;
}

function bioinmed_link($key_path, array $fallback = []) {
    static $links = null;
    if (!is_array($links)) {
        $links = bioinmed_read_json_file('links.json');
    }

    $node = bioinmed_json_get($links, $key_path, null);
    if (!is_array($node)) {
        $node = [];
    }

    $text = trim((string)($node['text'] ?? ($fallback['text'] ?? '')));
    $url = trim((string)($node['url'] ?? ($fallback['url'] ?? '#')));
    if ($url === '') {
        $url = '#';
    }

    return [
        'text' => $text,
        'url' => $url,
    ];
}

function bioinmed_data_text_id($value) {
    return ' data-text-id="' . htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
}

function bioinmed_page_text_node($page_data, $page_name, $path, $default = '') {
    $safe_page = preg_replace('/[^a-z0-9_]+/i', '', (string)$page_name);
    $safe_path = trim((string)$path, '.');
    $key = $safe_page !== ''
        ? 'pages.' . $safe_page . ($safe_path !== '' ? '.' . $safe_path : '')
        : $safe_path;

    $value = bioinmed_json_get($page_data, $safe_path, $default);
    if (!is_scalar($value)) {
        $value = $default;
    }

    return [
        'key' => $key,
        'value' => (string)$value,
        'attr' => bioinmed_data_text_id($key),
    ];
}

function bioinmed_page_text_attr($page_data, $page_name, $path, $default = '') {
    $node = bioinmed_page_text_node($page_data, $page_name, $path, $default);
    return $node['attr'];
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
    $submit_label = trim((string)($options['submit_label'] ?? bioinmed_text('common.request_callback')));
    $form_class = trim((string)($options['form_class'] ?? ''));
    $button_class = trim((string)($options['button_class'] ?? ''));

    $privacy_link = bioinmed_link('legal.privacy_genitive');
    $agreement_link = bioinmed_link('legal.user_agreement_genitive');

    if ($submit_label === '') {
        $submit_label = bioinmed_text('common.request_callback');
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
                    <a href="{$escape($privacy_link['url'])}" class="font-semibold text-[#0a293c] underline decoration-[#bfd9ed] underline-offset-2 hover:text-[#1977b2]">{$escape($privacy_link['text'])}</a>
                    и
                    <a href="{$escape($agreement_link['url'])}" class="font-semibold text-[#0a293c] underline decoration-[#bfd9ed] underline-offset-2 hover:text-[#1977b2]">{$escape($agreement_link['text'])}</a>.
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
    $ctaLabel = trim((string)($options['cta_label'] ?? bioinmed_text('common.more_details')));
    $ctaIcon = trim((string)($options['cta_icon'] ?? 'fa-arrow-right'));
    $surfaceClass = trim((string)($options['surface_class'] ?? 'flex flex-col justify-between'));
    $showBioIfTaglineMissing = array_key_exists('show_bio_if_tagline_missing', $options)
        ? (bool)$options['show_bio_if_tagline_missing']
        : true;

    $specializationIntro = '';
    $intro = '';
    $introClass = 'mt-4 text-[1rem] leading-relaxed text-[#0a293c] md:text-[1.08rem]';
    $leadershipIntro = '';
    $educationalRoleHtml = '';
    if ($slug === 'kostromina-inna-viktorovna') {
        $specializationIntro = 'СПЕЦИАЛИЗИРУЮСЬ НА СЛОЖНЫХ СЛУЧАЯХ';
        $intro = 'Более 30 лет клинической практики в детской и взрослой медицины';
        $introClass = 'mt-4 text-[0.75rem] font-bold uppercase tracking-[0.14em] text-[#0a293c]';
        if ($leadership !== '') {
            $leadershipIntro = $leadership;
        }

        $doctorPageData = bioinmed_read_json_file('pages/doctor.json');
        $educationalRole = is_array($doctorPageData['sections']['educational_role'] ?? null)
            ? $doctorPageData['sections']['educational_role']
            : [];
        $educationalRoleTitle = trim((string)($educationalRole['title'] ?? ''));
        $educationalRoleItems = is_array($educationalRole['items'] ?? null)
            ? $educationalRole['items']
            : [];

        if (!empty($educationalRoleItems)) {
            $educationalRoleItemsHtml = '';
            foreach ($educationalRoleItems as $roleItem) {
                if (is_array($roleItem)) {
                    $roleText = trim((string)($roleItem['text'] ?? ''));
                } else {
                    $roleText = trim((string)$roleItem);
                }

                if ($roleText === '') {
                    continue;
                }

                $educationalRoleItemsHtml .= '<li class="rounded-xl border border-[#dbe9f5] bg-white p-3.5 text-[0.9rem] leading-relaxed text-[#0a293c]"><span class="flex items-start gap-2.5"><span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-[#a8cde9] bg-[#eef7ff] text-[#1977b2]"><i class="fa-solid fa-check text-[0.62rem]" aria-hidden="true"></i></span><span>' . $escape($roleText) . '</span></span></li>';
            }

            if ($educationalRoleItemsHtml !== '') {
                $educationalRoleHtml = '<div class="mt-5 rounded-2xl border border-[#d6e6f4] bg-[#f6fbff] p-4 md:p-5">'
                    . ($educationalRoleTitle !== ''
                        ? '<p class="text-[0.75rem] font-bold uppercase tracking-[0.14em] text-[#0a293c]">' . $escape($educationalRoleTitle) . '</p>'
                        : '')
                    . '<ul class="mt-3 space-y-2.5">' . $educationalRoleItemsHtml . '</ul>'
                    . '</div>';
            }
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
    if ($showBioIfTaglineMissing && empty($doctor['hero_tagline']) && $bio !== '' && $intro !== $bio) {
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
                        ' . ($projectTitle !== '' ? '<p class="mt-4 text-[0.75rem] font-bold uppercase tracking-[0.14em] text-[#0a293c]">' . $escape($projectTitle) . '</p>' : '') . '
                        ' . ($intro !== '' ? '<p class="' . $escape($introClass) . '">' . $escape($intro) . '</p>' : '') . '
                        ' . ($specializationIntro !== '' ? '<p class="mt-4 text-[0.75rem] font-bold uppercase tracking-[0.14em] text-[#0a293c]">' . $escape($specializationIntro) . '</p>' : '') . '
                        ' . ($leadershipIntro !== '' ? '<p class="mt-4 text-[1rem] leading-relaxed text-[#0a293c] md:text-[1.08rem]">' . $escape($leadershipIntro) . '</p>' : '') . '
                        ' . $educationalRoleHtml . '
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

    static $aliases = null;
    if ($aliases === null) {
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

        $config_data = bioinmed_read_json_file('config-data.json');
        $json_aliases = bioinmed_json_get($config_data, 'service_category_aliases', []);
        if (is_array($json_aliases)) {
            $aliases = array_merge($aliases, $json_aliases);
        }
    }

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
    global $bioinmed_site_data;

    $value = (string)$text;
    if ($value === '') {
        return '';
    }

    $replacements = bioinmed_bootstrap_get($bioinmed_site_data, 'service_text_replacements', []);
    if (!is_array($replacements)) {
        $replacements = [];
    }

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
    global $bioinmed_site_data;

    $haystack = (string)($relativePath . ' ' . $title);

    $rules = bioinmed_bootstrap_get($bioinmed_site_data, 'docs_category_rules', []);
    if (is_array($rules) && !empty($rules)) {
        foreach ($rules as $rule) {
            $category = trim((string)($rule['category'] ?? ''));
            $keywords = $rule['keywords'] ?? [];
            if ($category === '' || !is_array($keywords)) {
                continue;
            }
            foreach ($keywords as $keyword) {
                if (bioinmed_contains_ci($haystack, $keyword)) {
                    return $category;
                }
            }
        }
    }

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
    global $bioinmed_site_data;

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

    $exact_map = bioinmed_bootstrap_get($bioinmed_site_data, 'service_image_exact_map', []);
    if (!is_array($exact_map)) {
        $exact_map = [];
    }

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
$bioinmed_config_data = bioinmed_read_json_file('config-data.json');
$bioinmed_config_array = static function ($key_path) use ($bioinmed_config_data) {
    $value = bioinmed_json_get($bioinmed_config_data, $key_path, []);
    return is_array($value) ? $value : [];
};

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

$problems = $bioinmed_config_array('problems_raw');
$problem_common_consultation = $bioinmed_config_array('problem_common_consultation');
$problem_common_diagnostics = $bioinmed_config_array('problem_common_diagnostics');
$problem_common_plan = $bioinmed_config_array('problem_common_plan');
$rehab_tracks = $bioinmed_config_array('rehab_tracks');
$result_tracks = $bioinmed_config_array('result_tracks');
$problem_profiles = $bioinmed_config_array('problem_profiles');

foreach ($problems as &$problem) {
    if (!is_array($problem)) {
        continue;
    }

    $title = trim((string)($problem['title'] ?? ''));
    if ($title === '' || !isset($problem_profiles[$title]) || !is_array($problem_profiles[$title])) {
        continue;
    }

    if (!isset($problem['slug']) || trim((string)$problem['slug']) === '') {
        $problem['slug'] = bioinmed_slugify_problem_name($title);
    }

    $rehab_key = (string)($problem_profiles[$title]['rehab'] ?? 'broad');
    $result_key = (string)($problem_profiles[$title]['result'] ?? 'pain');

    $problem['page_title'] = trim($title . ' — БИОИНМЕД');
    $problem['page_description'] = trim((string)($problem['description'] ?? '') . ' Отдельная страница с раскрывающимся описанием проблемы, этапами маршрута лечения и релевантными услугами клиники.');

    $problem['details_sections'] = [
        [
            'key' => 'consultation_diagnostics',
            'title' => '1. Консультация и диагностика врача/специалиста',
            'intro' => 'Сначала врач собирает жалобы, историю состояния и намечает клиническую гипотезу.',
            'items' => $problem_common_consultation,
        ],
        [
            'key' => 'functional_diagnostics',
            'title' => '2. Функциональная диагностика',
            'intro' => 'На этом этапе уточняем, как работает опорно-двигательный аппарат и где именно есть перегрузка.',
            'items' => $problem_common_diagnostics,
        ],
        [
            'key' => 'treatment_plan',
            'title' => '3. Составление плана лечения',
            'intro' => 'После диагностики формируется персональный маршрут с последовательностью методов и сроками контроля.',
            'items' => $problem_common_plan,
        ],
        [
            'key' => 'rehabilitation',
            'title' => '4. Реабилитация',
            'intro' => 'Подключаем методы, которые помогают безопасно восстановить движение, снизить боль и закрепить результат.',
            'items' => isset($rehab_tracks[$rehab_key]) && is_array($rehab_tracks[$rehab_key]) ? $rehab_tracks[$rehab_key] : ($rehab_tracks['broad'] ?? []),
        ],
        [
            'key' => 'result',
            'title' => '5. Результат',
            'intro' => 'Цель этапа - устойчивое улучшение самочувствия, подвижности и качества жизни.',
            'items' => isset($result_tracks[$result_key]) && is_array($result_tracks[$result_key]) ? $result_tracks[$result_key] : ($result_tracks['pain'] ?? []),
        ],
    ];

    foreach ($problem['details_sections'] as $section_index => &$details_section) {
        if (!is_array($details_section)) {
            continue;
        }

        $section_key = trim((string)($details_section['key'] ?? ''));
        if ($section_key === '') {
            $section_key = 'section_' . $section_index;
        }
        $details_section['key'] = $section_key;

        $items = is_array($details_section['items'] ?? null) ? $details_section['items'] : [];
        $normalized_items = [];
        $used_item_ids = [];
        foreach ($items as $item_index => $item_entry) {
            if (is_array($item_entry)) {
                $item_text = trim((string)($item_entry['text'] ?? ''));
                $item_id = trim((string)($item_entry['id'] ?? ''));
            } else {
                $item_text = trim((string)$item_entry);
                $item_id = '';
            }

            if ($item_text === '') {
                continue;
            }

            if ($item_id === '') {
                $item_id = bioinmed_slugify_problem_name($item_text);
                if ($item_id === '') {
                    $item_id = 'item_' . $item_index;
                }
            }

            if (isset($used_item_ids[$item_id])) {
                $used_item_ids[$item_id]++;
                $item_id .= '_' . $used_item_ids[$item_id];
            } else {
                $used_item_ids[$item_id] = 1;
            }

            $normalized_items[] = [
                'id' => $item_id,
                'text' => $item_text,
            ];
        }
        $details_section['items'] = $normalized_items;
    }
    unset($details_section);
}
unset($problem);

$problem_order = $bioinmed_config_array('problem_order');
$problem_order_map = [];
foreach ($problem_order as $order_index => $problem_title) {
    $problem_order_map[trim((string)$problem_title)] = $order_index;
}

usort($problems, static function (array $left, array $right) use ($problem_order_map): int {
    $left_title = trim((string)($left['title'] ?? ''));
    $right_title = trim((string)($right['title'] ?? ''));
    $left_order = $problem_order_map[$left_title] ?? 999;
    $right_order = $problem_order_map[$right_title] ?? 999;
    if ($left_order === $right_order) {
        return 0;
    }

    return $left_order <=> $right_order;
});

$children_problem_titles = $bioinmed_config_array('children_problem_titles');
$children_problems_map = [];
foreach ($children_problem_titles as $children_problem_title) {
    $children_problems_map[trim((string)$children_problem_title)] = true;
}

$children_problems = [];
foreach ($problems as $problem_item) {
    if (!is_array($problem_item)) {
        continue;
    }

    $problem_title = trim((string)($problem_item['title'] ?? ''));
    if ($problem_title === '' || !isset($children_problems_map[$problem_title])) {
        continue;
    }

    $children_problems[] = $problem_item;
}

$advantages = $bioinmed_config_array('advantages');
$doctors = $bioinmed_config_array('doctors');
?>
