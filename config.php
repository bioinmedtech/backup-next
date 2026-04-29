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
define('ADMIN_CABINET_PASSWORD', getenv('BIOINMED_ADMIN_PASSWORD') ?: 'рih87u7t85445');
define('ADMIN_CABINET_DEFAULT_USER', getenv('BIOINMED_ADMIN_USER') ?: 'admin');
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

// Услуги (для главной и страниц услуг)
$services = [
    [
        'id' => 'hobilect-diagnostics',
        'name' => 'Комплексная диагностика организма HABILECT',
        'subtitle' => 'Ключевая услуга клиники',
        'category' => 'diagnostics',
        'price' => 'от 9 900 ₽',
        'price_note' => '',
        'description' => 'Расширенная оценка организма с акцентом на опорно-двигательный аппарат, функциональные перегрузки и индивидуальный маршрут восстановления.',
        'details' => 'Включает первичный осмотр, функциональную оценку, интерпретацию показателей и персональные рекомендации.',
        'target' => 'Боли в спине, шее, суставах, нарушения осанки, мышечные дисбалансы.',
        'order' => 1,
    ],
    [
        'id' => 'musculoskeletal-program',
        'name' => 'Программа восстановления опорно-двигательного аппарата',
        'subtitle' => 'Спина, суставы, связки, фасции',
        'category' => 'musculoskeletal',
        'price' => 'от 12 500 ₽',
        'price_note' => '/сеанс',
        'description' => 'Комплексный курс при болях и ограничении движения: диагностика, мануальные техники, контроль динамики и корректировка плана.',
        'details' => 'Подходит при хронической боли, скованности и последствиях перегрузок.',
        'target' => 'Поясница, шейный отдел, колени, тазобедренные суставы.',
        'order' => 2,
    ],
    [
        'id' => 'osteopathy',
        'name' => 'Остеопатия и мягкая мануальная коррекция',
        'subtitle' => 'Без жестких манипуляций',
        'category' => 'manual_therapy',
        'price' => 'от 5 500 ₽',
        'price_note' => '',
        'description' => 'Работа с первопричинами дискомфорта: баланс мышц, связок и фасций, улучшение подвижности и снижение болевого синдрома.',
        'details' => 'Индивидуальный подход для взрослых и детей.',
        'target' => 'Головные боли, перекосы, боли в суставах и мышцах.',
        'order' => 3,
    ],
    [
        'id' => 'reflexotherapy',
        'name' => 'Рефлексотерапия',
        'subtitle' => 'Иглорефлексотерапия и акупунктурные методики',
        'category' => 'therapy',
        'price' => 'от 3 500 ₽',
        'price_note' => '/сеанс',
        'description' => 'Точечное воздействие для снятия боли, восстановления нервно-мышечного баланса и уменьшения стрессовой нагрузки.',
        'details' => 'Используется как самостоятельный метод и в составе комплексного плана.',
        'target' => 'Болевые синдромы, мигрени, хроническая усталость.',
        'order' => 4,
    ],
    [
        'id' => 'physiotherapy',
        'name' => 'Физиотерапия и аппаратное восстановление',
        'subtitle' => 'Современные аппаратные протоколы',
        'category' => 'therapy',
        'price' => 'от 4 500 ₽',
        'price_note' => '',
        'description' => 'Комплекс аппаратных методов для восстановления тканей, уменьшения боли и ускорения реабилитации.',
        'details' => 'Подбор протокола по диагностике и клинической картине.',
        'target' => 'Суставы, мышцы, связочный аппарат, посттравматические состояния.',
        'order' => 5,
    ],
    [
        'id' => 'integrative-follow-up',
        'name' => 'Интегративное сопровождение врача',
        'subtitle' => 'Контроль динамики и корректировка плана',
        'category' => 'integrative',
        'price' => 'от 4 900 ₽',
        'price_note' => '/консультация',
        'description' => 'Регулярная оценка результатов и настройка программы лечения, чтобы пациент двигался к устойчивому восстановлению.',
        'details' => 'Для пациентов, проходящих диагностику и комплексные курсы в клинике.',
        'target' => 'Сложные и комбинированные состояния, требующие маршрутизации.',
        'order' => 6,
    ],
];

// Частые вопросы
$faq_items = [
    [
        'question' => 'С чего начать, если беспокоят спина или суставы?',
        'answer' => 'Рекомендуем начать с комплексной диагностики HABILECT. Она помогает увидеть первопричины перегрузок и собрать персональный план восстановления. Это займет 60-90 минут и создаст основу всего лечения.',
    ],
    [
        'question' => 'Нужно ли приносить документы и анализы на первый прием?',
        'answer' => 'Анализы и документы не обязательны, но если у вас есть свежие УЗИ, МРТ или снимки — принесите их. Врач использует эту информацию для более точной диагностики. Если их нет — ничего страшного, врач проведет необходимую оценку.',
    ],
    [
        'question' => 'Как часто нужно приходить на приемы?',
        'answer' => 'Частота зависит от вашего состояния и плана лечения. Обычно это 1-2 раза в неделю на начальном этапе (2-4 недели), затем кратность уменьшается. Врач определит оптимальный график на первичной консультации.',
    ],
    [
        'question' => 'Можно ли пройти лечение без длительного приема лекарств?',
        'answer' => 'Да. Мы используем интегративный подход: сочетание диагностики, мануальных, рефлекторных и аппаратных методов по показаниям. Это позволяет во многих случаях достичь результатов без избыточной медикаментозной нагрузки.',
    ],
    [
        'question' => 'Есть ли противопоказания к лечению?',
        'answer' => 'Противопоказания зависят от метода. Например, рефлексотерапия не рекомендуется при активных инфекциях или беременности. Остеопатия имеет ограничения при острых нарушениях кровообращения. На консультации врач оценит ваше состояние и подберет безопасные методы.',
    ],
    [
        'question' => 'С какого возраста можно начинать лечение?',
        'answer' => 'Мы работаем с пациентами любого возраста. Для детей применяются мягкие техники остеопатии и рефлексотерапии. Пожилые пациенты получают щадящие методы без риска. Врач подбирает подход индивидуально.',
    ],
    [
        'question' => 'Что происходит на первом приеме?',
        'answer' => 'Врач беседует с вами о жалобах и истории болезни, затем проводит функциональную диагностику (осмотр, тесты движения, пальпацию). Заканчивается первичной консультацией, где обсуждаются результаты и составляется индивидуальный план лечения.',
    ],
    [
        'question' => 'Работаете ли вы с хронической болью в спине?',
        'answer' => 'Да. Это одно из ключевых направлений клиники: диагностика, восстановление движений, снижение болевого синдрома и контроль динамики. Даже при давних проблемах комплексный подход часто дает заметные результаты.',
    ],
    [
        'question' => 'Какие варианты оплаты и скидки?',
        'answer' => 'Мы работаем с наличными, картами и банковскими переводами. Доступна рассрочка на комплексные программы. Есть скидки для курсов (пакеты из 5+ сеансов). Уточняйте актуальные предложения у администратора.',
    ],
    [
        'question' => 'Можно ли записаться только по номеру телефона?',
        'answer' => 'Да. Оставьте телефон, и администратор перезвонит в течение 15 минут, подберет удобное время и нужного специалиста. Или позвоните сами: +7 (495) 796-03-36.',
    ],
];

// Переопределение услуг из редактируемого хранилища
$services_storage_path = __DIR__ . '/data/services.json';
if (is_file($services_storage_path) && is_readable($services_storage_path)) {
    $raw_services = file_get_contents($services_storage_path);
    if (is_string($raw_services) && $raw_services !== '') {
        $decoded_services = json_decode($raw_services, true);
        if (is_array($decoded_services) && !empty($decoded_services)) {
            $services = $decoded_services;
        }
    }
}

// Alias mapping: alias service id => canonical service id
$service_aliases = [];
$service_aliases_path = __DIR__ . '/data/service_aliases.json';
if (is_file($service_aliases_path) && is_readable($service_aliases_path)) {
    $raw_aliases = file_get_contents($service_aliases_path);
    if (is_string($raw_aliases) && $raw_aliases !== '') {
        $decoded_aliases = json_decode($raw_aliases, true);
        if (is_array($decoded_aliases)) {
            foreach ($decoded_aliases as $alias_id => $canonical_id) {
                $alias = trim((string)$alias_id);
                $canonical = trim((string)$canonical_id);
                if ($alias !== '' && $canonical !== '' && $alias !== $canonical) {
                    $service_aliases[$alias] = $canonical;
                }
            }
        }
    }
}

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

// Переопределение FAQ из редактируемого хранилища
$faq_storage_path = __DIR__ . '/data/faqs.json';
if (is_file($faq_storage_path) && is_readable($faq_storage_path)) {
    $raw_faq = file_get_contents($faq_storage_path);
    if (is_string($raw_faq) && $raw_faq !== '') {
        $decoded_faq = json_decode($raw_faq, true);
        if (is_array($decoded_faq) && !empty($decoded_faq)) {
            $faq_items = $decoded_faq;
        }
    }
}

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
        'specialty' => 'Главный врач, врач ВРТ и БРТ, гомеопат, психолог, рефлексотерапевт',
        'experience' => 'Более 30 лет клинической практики',
        'bio' => 'Автор комплексных программ клиники. Сочетает классическую медицину с интегративными и восстановительными методами.',
        'leadership' => 'Руководит клинической командой, формирует стандарты ведения пациентов и участвует в обучении специалистов.',
        'specializations' => [
            'Вегетативно-резонансная диагностика и биорезонансная терапия',
            'Гомеопатия и гомотоксикология',
            'Рефлексотерапия и ароматерапия',
            'Психологическое сопровождение и кинезиодиагностика',
        ],
        'education' => 'Первый МГМУ им. И. М. Сеченова, ординатура по педиатрии, последипломные программы по гомеопатии, рефлексотерапии и биорезонансной терапии.',
        'focus' => [
            'Комплексные программы при хронических состояниях',
            'Выявление первопричин симптомов на доклиническом уровне',
            'Персональные маршруты восстановления без избыточной нагрузки',
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
        'title' => 'Практикующий психолог',
        'specialty' => 'Психолог-консультант, интегративная психотерапия',
        'experience' => 'Более 2000 часов практики',
        'bio' => 'Работает с тревожными и паническими состояниями, стрессом и кризисными периодами.',
        'specializations' => [
            'Кататимно-имагинативная терапия (символдрама)',
            'Репарационная психология',
            'Психосоматическая кинезиология',
            'Телесно-ориентированная терапия',
        ],
        'education' => 'Международный институт психологии Smart, Открытая Европейская академия экономики и политики (Прага), Международная Академия репарационной психологии.',
        'focus' => [
            'Сопровождение в кризисных ситуациях и при выгорании',
            'Работа с личными границами и отношениями',
            'Поддержка при тревоге и панических атаках',
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
        'title' => 'Врач-остеопат',
        'specialty' => 'Остеопат, мануальный терапевт, кандидат медицинских наук',
        'experience' => '20 лет практики',
        'bio' => 'Комбинирует остеопатию, миофасциальные и лимфодренажные техники, работает со сложными функциональными нарушениями.',
        'specializations' => [
            'Краниальные, структуральные и висцеральные техники',
            'Сомато-эмоциональное освобождение',
            'Лечение неврологических и посттравматических состояний',
            'Эстетическое моделирование лица',
        ],
        'education' => 'Тверская государственная медицинская академия, ординатура и аспирантура в МГМУ им. Мечникова, обучение в европейских школах остеопатии.',
        'focus' => [
            'Реабилитация после травм и функциональных перегрузок',
            'Работа с головными болями и нарушениями осанки',
            'Поддержка при заболеваниях опорно-двигательного аппарата',
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
        'specialty' => 'Заведующая отделением физиотерапии',
        'experience' => 'Более 25 лет практики',
        'bio' => 'Сочетает акушерско-гинекологическую школу и восточные оздоровительные методики в персональных программах.',
        'specializations' => [
            'Акушерство и гинекология',
            'Рефлексотерапия и фармакопунктура',
            'Репродуктология и восстановительные программы',
            'Физиотерапевтические методики',
        ],
        'education' => 'Владивостокский государственный медицинский университет, государственные сертификаты по рефлексотерапии и физиотерапии.',
        'focus' => [
            'Сопровождение при гинекологических нарушениях',
            'Поддержка репродуктивного здоровья',
            'Реабилитация и восстановление после нагрузок',
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
        'id' => 5,
        'slug' => 'mhitaryan-nana-vladimirovna',
        'name' => 'Мхитарян Нана Владимировна',
        'title' => 'Медицинская сестра (акушер)',
        'specialty' => 'Сестринское дело, физиотерапевтическая поддержка',
        'experience' => 'Более 30 лет практики',
        'bio' => 'Обеспечивает профессиональный уход и выполнение процедур в рамках комплексных программ клиники.',
        'specializations' => [
            'Акушерская и сестринская помощь',
            'Физиотерапевтические процедуры',
            'Работа с аппаратными методиками',
            'Проведение инъекционных процедур',
        ],
        'education' => 'Московское медицинское училище №15 (диплом с отличием), регулярное повышение квалификации по акушерскому делу.',
        'focus' => [
            'Поддержка врача на приеме и в процедурах',
            'Контроль санитарно-эпидемиологических стандартов',
            'Сопровождение пациентов в процессе лечения',
        ],
        'services' => [
            'fizioterapiya',
            'infusion-therapy',
            'inektsionnaya-terapiya',
            'banki',
            'banochnyy-massazh',
            'cupping',
        ],
        'image' => 'mhitaryan.jpg',
    ],
    [
        'id' => 6,
        'slug' => 'vertlib-valeriya-pavlovna',
        'name' => 'Вертлиб Валерия Павловна',
        'title' => 'Врач-остеопат, невролог',
        'specialty' => 'Остеопатия, неврология, рефлексотерапия',
        'experience' => '20 лет практики',
        'bio' => 'Комплексный подход к лечению болевых синдромов, нейросоматических и психосоматических состояний.',
        'specializations' => [
            'Структуральные, краниальные и висцеральные остеопатические техники',
            'Миофасциальная коррекция и лимфодренаж',
            'Лечение острых и хронических болевых синдромов',
            'Коррекция неврологических и функциональных нарушений',
        ],
        'education' => 'Кубанский государственный медицинский университет, интернатура по неврологии, переподготовка по рефлексотерапии, Институт остеопатии Санкт-Петербурга.',
        'focus' => [
            'Боли в спине и суставах',
            'Последствия травм и длительного стресса',
            'Синдром хронической усталости и психосоматические состояния',
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
        'title' => 'Специалист по АФК',
        'specialty' => 'Адаптивная физическая культура и восстановительные практики',
        'experience' => 'Практика в области АФК',
        'bio' => 'Специализируется на восстановительных занятиях и физической реабилитации в рамках комплексных программ клиники.',
        'specializations' => [
            'Адаптивная физическая культура',
            'Поддержка восстановительных программ',
            'Индивидуальная работа по двигательным навыкам',
        ],
        'education' => 'Мурманский государственный педагогический институт, Академия дополнительного профессионального образования «Перспектива».',
        'focus' => [
            'Восстановление после функциональных нарушений',
            'Повышение двигательной активности',
            'Поддержка пациентов в реабилитационном периоде',
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
];

// Переопределение врачей из редактируемого хранилища
$doctors_storage_path = __DIR__ . '/data/doctors.json';
if (is_file($doctors_storage_path) && is_readable($doctors_storage_path)) {
    $raw_doctors = file_get_contents($doctors_storage_path);
    if (is_string($raw_doctors) && $raw_doctors !== '') {
        $decoded_doctors = json_decode($raw_doctors, true);
        if (is_array($decoded_doctors) && !empty($decoded_doctors)) {
            $doctors = $decoded_doctors;
        }
    }
}

// Кейсы пациентов
$cases = [
    [
        'patient' => 'Сергей, 42 года',
        'problem' => 'Грыжа диска L4-L5, боль при ходьбе 6 месяцев',
        'treatment' => 'HILT-терапия (10 сеансов) + остеопатия',
        'result' => 'Боль полностью ушла, вернулся в спорт',
        'rating' => 5,
    ],
    [
        'patient' => 'Анна, 35 лет',
        'problem' => 'Выпадение волос, облысение по женскому типу',
        'treatment' => 'PRP-терапия волосистой части головы',
        'result' => 'Появился новый рост волос, останавливается выпадение',
        'rating' => 5,
    ],
    [
        'patient' => 'Ольга, 58 лет',
        'problem' => 'Артроз коленного сустава, не могла подняться на лестницу',
        'treatment' => 'УВТ (6 сеансов) + медицинский массаж',
        'result' => 'Свободно ходит, боль ушла, вернулась активность',
        'rating' => 5,
    ],
];

// Переопределение отзывов из редактируемого хранилища
$reviews_storage_path = __DIR__ . '/data/reviews.json';
if (is_file($reviews_storage_path) && is_readable($reviews_storage_path)) {
    $raw_reviews = file_get_contents($reviews_storage_path);
    if (is_string($raw_reviews) && $raw_reviews !== '') {
        $decoded_reviews = json_decode($raw_reviews, true);
        if (is_array($decoded_reviews) && !empty($decoded_reviews)) {
            $cases = $decoded_reviews;
        }
    }
}
?>
