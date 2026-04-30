<?php
// Конфигурация и общие данные клиники

define('CLINIC_NAME', 'БИОИНМЕД');
define('CLINIC_SITE_URL', 'https://next.bioinmed.ru');
define('CLINIC_ICON_PATH', '/public/images/brand/bioinmed-icon.png');
define('CLINIC_PHONE', '+7 (495) 796-03-36');
define('CLINIC_PHONE_2', '8 (800) 770-03-36');
define('CLINIC_ADDRESS', 'Москва, Оболенский пер., 9А');
define('CLINIC_METRO', 'м. Фрунзенская');
define('CLINIC_EMAIL', 'info@bioinmed.ru');
define('CLINIC_HOURS', 'Ежедневно с 9:00 до 21:00 (без выходных)');
define('CLINIC_TAGLINE', 'Интегративная и восстановительная медицина. Индивидуальный подход к каждому пациенту.');
define('ONLINE_BOOKING_URL', '#contact');
define('CLINIC_VK', 'https://vk.com/bioinmed');
define('CLINIC_TELEGRAM', 'https://t.me/bioinmed');
define('HERO_TITLE', 'Восстановление здоровья через интегративную медицину');
define('HERO_IMAGE', '/public/images/team/kostromina_i_v.png');
define('RECAPTCHA_SITE_KEY', getenv('BIOINMED_RECAPTCHA_SITE_KEY') ?: '6LfmOs0sAAAAAKHWO2jG24uuWIL7UBy3x7gG8awh');
define('RECAPTCHA_SECRET_KEY', getenv('BIOINMED_RECAPTCHA_SECRET_KEY') ?: '6LfmOs0sAAAAAJQP0aJ3ho1kB7VHy4VeyW_s4GQe');

// Ключевые показатели (статистика)
define('CLINIC_EXPERIENCE_YEARS', '30+');
define('CLINIC_EXPERIENCE_DESC', 'ЛЕТ КЛИНИЧЕСКОГО ОПЫТА');
define('CLINIC_RATING', '5');
define('CLINIC_RATING_DESC', 'ОЦЕНКА ПАЦИЕНТОВ');
define('CLINIC_PATIENTS_YEARLY', '20+');
define('CLINIC_PATIENTS_DESC', 'НАПРАВЛЕНИЙ МЕДИЦИНЫ');
define('CLINIC_LICENSE_TEXT', 'Лицензия');
define('CLINIC_LICENSE_DESC', 'МЗ РФ И СОБЛЮДЕНИЕ САНПИН');

// Цветовая палитра
$brand_colors = [
    'primary' => '#2fbdef',      // Основной цвет
    'secondary' => '#0077bd',    // Дополнительный цвет
    'accent' => '#2fbdef',       // Акцент
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

function bioinmed_default_social_image_path() {
    return '/public/images/brand/logo-white-scaled.webp';
}

function bioinmed_default_social_image_url() {
    return bioinmed_absolute_url(bioinmed_default_social_image_path());
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
        'telephone' => [CLINIC_PHONE, CLINIC_PHONE_2],
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

function bioinmed_localize_service_text($text) {
    $value = (string)$text;
    if ($value === '') {
        return '';
    }

    $replacements = [
        'HABILECT' => 'ХАБИЛЕКТ',
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
        'title' => 'Болит спина и поясница',
        'description' => 'Боль при движении, сидячая работа, грыжа диска',
        'solutions' => 'HABILECT-диагностика, Остеопатия, HILT-терапия',
        'icon' => '🔴',
    ],
    [
        'title' => 'Боль в суставах',
        'description' => 'Колено, тазобедренный сустав, артроз, ограничение движения',
        'solutions' => 'Остеопатия, Физиотерапия, Рефлексотерапия',
        'icon' => '🔴',
    ],
    [
        'title' => 'Головные боли и мигрень',
        'description' => 'Частые приступы, давление в голове, шейная зажатость',
        'solutions' => 'Рефлексотерапия, Мануальная коррекция',
        'icon' => '🔴',
    ],
    [
        'title' => 'Тревога и бессонница',
        'description' => 'Нарушение сна, тревожность, панические атаки, стресс',
        'solutions' => 'Психотерапия, Рефлексотерапия',
        'icon' => '🔴',
    ],
    [
        'title' => 'Хроническая усталость и выгорание',
        'description' => 'Постоянная усталость, снижение работоспособности, эмоциональное истощение',
        'solutions' => 'Биорезонансная терапия, Рефлексотерапия, Психотерапия',
        'icon' => '🔴',
    ],
    [
        'title' => 'Нарушение осанки и сколиоз',
        'description' => 'Искривление позвоночника, мышечные дисбалансы, профессиональная деформация',
        'solutions' => 'Остеопатия, АФК, HABILECT-диагностика',
        'icon' => '🔴',
    ],
    [
        'title' => 'Слабый иммунитет и частые ОРВИ',
        'description' => 'Частые простуды, медленное восстановление, снижение защитных сил',
        'solutions' => 'Гомеопатия, Биорезонансная терапия',
        'icon' => '🔴',
    ],
    [
        'title' => 'Неврологические нарушения',
        'description' => 'Онемение, покалывание, нарушение чувствительности, головокружение',
        'solutions' => 'Рефлексотерапия, Остеопатия, HABILECT-диагностика',
        'icon' => '🔴',
    ],
];

// Преимущества клиники
$advantages = [
    [
        'title' => 'Современное оборудование',
        'description' => 'Немецкие аппараты HEEL, УВТ, лазерные системы',
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
        'title' => 'Главный врач',
        'specialty' => 'Врач ВРТ и БРТ, гомеопат, психолог, рефлексотерапевт, гомотоксиколог, аромотерапевт',
        'experience' => 'Более 30 лет врачебной практики',
        'bio' => 'Главный врач и автор комплексных методик клиники. Ведёт пациентов со сложными хроническими, психоэмоциональными, репродуктивными и нейросоматическими состояниями, сочетая классическую и интегративную медицину.',
        'leadership' => 'Формирует клинические стандарты БИОИНМЕД, разрабатывает персональные маршруты восстановления и курирует работу команды специалистов.',
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
        'image' => 'kostromina.jpg',
    ],
    [
        'id' => 2,
        'slug' => 'ferencz-nadezhda-yurevna',
        'name' => 'Ференц Надежда Юрьевна',
        'title' => 'Практический психолог',
        'specialty' => 'Психолог-консультант, полимодальный подход, психосоматическое сопровождение',
        'experience' => 'Частная консультативная практика',
        'bio' => 'Помогает справиться с тревогой, паникой, стрессом, выгоранием, зависимостями и повторяющимися сценариями. Работает с глубинными причинами проблем и помогает выстроить план действий с первых встреч.',
        'specializations' => [
            'Кататимно-имагинативная терапия и авторские визуализации',
            'Репарационная психология и работа с зависимостями',
            'Психосоматическая кинезиология и телесно-ориентированная терапия',
            'Когнитивно-поведенческие и коучинговые инструменты',
        ],
        'education' => 'Институт психологии Smart, Открытая Европейская академия экономики и политики (Прага), Международная академия репарационной психологии и терапии, дополнительная подготовка по КПТ, консультированию и работе с тревогой и стрессом.',
        'focus' => [
            'Поддержка при тревоге, панике, стрессе и эмоциональном выгорании',
            'Личные границы, самоценность и повторяющиеся сценарии в отношениях',
            'Психосоматическое сопровождение при хронической боли и телесных симптомах',
        ],
        'services' => [
            'priem-psikhologa',
            'psychotherapy',
            'psikhologiya',
        ],
        'image' => 'ferencz.jpg',
    ],
    [
        'id' => 3,
        'slug' => 'nehorosheva-lyudmila-sergeevna',
        'name' => 'Нехорошева Людмила Сергеевна',
        'title' => 'Врач-остеопат, мануальный терапевт',
        'specialty' => 'Остеопат, мануальный терапевт, стоматолог, кандидат медицинских наук',
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
    [
        'id' => 4,
        'slug' => 'kondratova-elena-aleksandrovna',
        'name' => 'Кондратова Елена Александровна',
        'title' => 'Акушер-гинеколог, рефлексотерапевт',
        'specialty' => 'Заведующая отделением физиотерапии, акушер-гинеколог, рефлексотерапевт',
        'experience' => 'Более 30 лет врачебной практики',
        'bio' => 'Сочетает классическую акушерско-гинекологическую школу с рефлексотерапией и восстановительными методиками. Ведёт программы по репродуктивному здоровью, боли, реабилитации и эстетической гинекологии.',
        'specializations' => [
            'Акушерство, гинекология и репродуктивное здоровье',
            'Корпоральная акупунктура, су-джок, кранио- и аурикулотерапия',
            'Фармакопунктура, гомеопунктура, PRP и кетгут-терапия',
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
        'id' => 7,
        'slug' => 'rozhkov-sergei-leonidovich',
        'name' => 'Рожков Сергей Леонидович',
        'title' => 'Инструктор-методист АФК',
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
        'id' => 5,
        'slug' => 'mhitaryan-nana-vladimirovna',
        'name' => 'Мхитарян Нана Владимировна',
        'title' => 'Медицинская сестра (акушер)',
        'specialty' => 'Сестринское дело, физиотерапевтическая и процедурная поддержка',
        'experience' => 'Более 30 лет медицинской практики',
        'bio' => 'Обеспечивает профессиональное сопровождение пациентов во время процедур и помогает врачам в реализации комплексных лечебных программ клиники.',
        'specializations' => [
            'Акушерская и сестринская помощь',
            'Физиотерапевтические и процедурные протоколы',
            'Работа с аппаратными методиками и внутривенными назначениями',
            'Поддержка пациентов во время курса лечения',
        ],
        'education' => 'Московское медицинское училище №15, регулярное повышение квалификации по акушерскому делу, сестринскому и процедурному сопровождению.',
        'focus' => [
            'Поддержка врача на приёме и во время лечебных процедур',
            'Контроль комфорта и безопасности пациента во время курса',
            'Сопровождение физиотерапии, инфузионных и инъекционных назначений',
        ],
        'services' => [
            'fizioterapiya',
            'infusion-therapy',
            'inektsionnaya-terapiya',
            'ozone-therapy',
            'cupping',
        ],
        'image' => 'mhitaryan.jpg',
    ],
    [
        'id' => 8,
        'slug' => 'mayorova-darya-sergeevna',
        'name' => 'Майорова Дарья Сергеевна',
        'title' => 'Специалист по реабилитации и инструктор ЛФК',
        'specialty' => 'ЛФК, реабилитация, массаж, подиатрия, ортопедические стельки',
        'experience' => '15 лет практики',
        'bio' => 'Специализируется на диагностике опорно-двигательного аппарата, восстановлении после травм и операций, коррекции сколиоза и подборе индивидуальных ортопедических решений.',
        'specializations' => [
            'Диагностика опорно-двигательного аппарата и индивидуальные программы ЛФК',
            'Реабилитация после травм и операций на суставах и позвоночнике',
            'Медицинский массаж и коррекция сколиоза по методике S.E.A.S.',
            'Подиатрическая диагностика и изготовление индивидуальных ортопедических стелек',
        ],
        'education' => 'Российский государственный университет физической культуры, спорта, молодёжи и туризма, профессиональная переподготовка по лечебной физкультуре и реабилитации, дополнительное обучение по массажу, подиатрии, FOOTMASTER и Formthotics.',
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
];
?>
