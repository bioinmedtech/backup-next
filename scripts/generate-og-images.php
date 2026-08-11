<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

$root = dirname(__DIR__);
$seasons = require $root . '/config/seasons.php';
$outputDir = $root . '/public/images/og';
$manifestFile = $root . '/data/content/ru/og-images.json';
$quiet = in_array('--quiet', $argv, true);

if (!extension_loaded('gd') || !function_exists('imagewebp')) {
    fwrite(STDERR, "GD with WebP support is required.\n");
    exit(1);
}

if (!is_dir($outputDir) && !mkdir($outputDir, 0775, true) && !is_dir($outputDir)) {
    fwrite(STDERR, "Failed to create output directory: {$outputDir}\n");
    exit(1);
}

$regularFontCandidates = [
    $root . '/public/assets/fonts/timeable/timeable-font-regular.ttf',
    $root . '/public/assets/fonts/timeable/timeable-font-medium.ttf',
    $root . '/public/fonts/SF-Pro-Text-Regular.otf',
    $root . '/public/fonts/SF-Pro-Display-Regular.otf',
    $root . '/public/fonts/SFProText-Regular.ttf',
    $root . '/public/fonts/SFProDisplay-Regular.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
    '/usr/share/fonts/truetype/droid/DroidSansFallbackFull.ttf',
];
$boldFontCandidates = [
    $root . '/public/assets/fonts/timeable/timeable-font-semibold.ttf',
    $root . '/public/assets/fonts/timeable/timeable-font-bold.ttf',
    $root . '/public/fonts/SF-Pro-Text-Semibold.otf',
    $root . '/public/fonts/SF-Pro-Display-Semibold.otf',
    $root . '/public/fonts/SFProText-Semibold.ttf',
    $root . '/public/fonts/SFProDisplay-Semibold.ttf',
    $root . '/public/fonts/SF-Pro-Text-Bold.otf',
    $root . '/public/fonts/SF-Pro-Display-Bold.otf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
];
$regularFont = '';
$boldFont = '';
foreach ($regularFontCandidates as $candidate) {
    if (is_file($candidate)) {
        $regularFont = $candidate;
        break;
    }
}
foreach ($boldFontCandidates as $candidate) {
    if (is_file($candidate)) {
        $boldFont = $candidate;
        break;
    }
}
if (!is_file($regularFont) || !is_file($boldFont)) {
    fwrite(STDERR, "A Cyrillic-capable TrueType font is required.\n");
    exit(1);
}

$e = static fn($value): string => trim(preg_replace('/\s+/u', ' ', strip_tags((string)$value)) ?? '');
$safeKey = static function (string $value): string {
    $key = strtolower(trim($value));
    $key = preg_replace('/[^a-z0-9_-]+/', '-', $key) ?? '';
    $key = trim($key, '-_');
    return $key !== '' ? $key : 'page';
};

$pageMeta = static function (string $file): array {
    $page = bioinmed_read_json_file('pages/' . $file . '.json');
    return is_array($page['meta'] ?? null) ? $page['meta'] : [];
};

$descriptors = [];
$add = static function ($key, string $eyebrow, string $title, string $description = '', $image = '', string $accent = '#1977b2', string $visualType = 'photo') use (&$descriptors, $e, $safeKey): void {
    $key = $safeKey((string)$key);
    $title = $e($title);
    if ($title === '') {
        return;
    }
    $descriptors[$key] = [
        'key' => $key,
        'eyebrow' => $e($eyebrow),
        'title' => $title,
        'description' => bioinmed_meta_description($description, CLINIC_TAGLINE, 150),
        'image' => trim((string)$image),
        'accent' => preg_match('/^#[0-9a-f]{6}$/i', $accent) ? $accent : '#1977b2',
        'visual_type' => $visualType === 'logo' ? 'logo' : 'photo',
    ];
};

$staticPages = [
    'home' => ['file' => null, 'path' => '/', 'title' => 'БИОИНМЕД | Клиника восстановительной медицины в Москве', 'description' => 'Диагностика «Хабилект», остеопатия, рефлексотерапия, физиотерапия, опытные врачи и персональный план лечения.', 'eyebrow' => 'Главная', 'image' => '/public/images/habilect-family.webp'],
    'about' => ['file' => 'about', 'title' => 'О клинике', 'description' => 'Клиника интегративной медицины БИОИНМЕД в Москве: диагностика первопричин, остеопатия, рефлексотерапия, физиотерапия и персональные программы восстановления.', 'eyebrow' => 'О клинике', 'image' => '/public/images/team/kostromina-default.webp'],
    'partners' => ['file' => 'partners', 'title' => 'Партнёры клиники', 'description' => 'Технологические и научные партнёры клиники БИОИНМЕД: медицинская экосистема «Хабилект» и международная фармацевтическая компания Heel.', 'eyebrow' => 'Партнёры'],
    'license' => ['file' => 'license', 'title' => 'Лицензия клиники', 'description' => 'Медицинская лицензия и санитарно-эпидемиологические документы клиники БИОИНМЕД в Москве.', 'eyebrow' => 'Документы'],
    'sterility' => ['file' => 'sterility', 'title' => 'Стерильность и безопасность', 'description' => 'Как в клинике БИОИНМЕД организованы дезинфекция, обработка инструментов, одноразовые материалы и контроль безопасности.', 'eyebrow' => 'Безопасность'],
    'vacancies' => ['file' => 'vacancies', 'title' => 'Вакансии клиники', 'description' => 'Работа в клинике БИОИНМЕД: направления сотрудничества, ценности команды и форма отклика.', 'eyebrow' => 'Команда'],
    'services' => ['file' => 'services', 'title' => 'Все услуги клиники', 'description' => 'Каталог услуг клиники БИОИНМЕД в Москве: диагностика, остеопатия, рефлексотерапия, физиотерапия, капельницы и комплексные программы лечения.', 'eyebrow' => 'Услуги'],
    'prices' => ['file' => 'prices', 'title' => 'Прайс-лист', 'description' => 'Прайс-лист клиники БИОИНМЕД в Москве: цены на консультации, диагностику, остеопатию, рефлексотерапию, физиотерапию, капельницы и комплексные программы.', 'eyebrow' => 'Цены'],
    'doctors' => ['file' => 'doctors', 'title' => 'Моя профессиональная команда', 'description' => 'Врачи клиники БИОИНМЕД в Москве: остеопатия, рефлексотерапия, психотерапия и восстановительная медицина. Выберите специалиста и запишитесь на приём.', 'eyebrow' => 'Врачи'],
    'problems' => ['file' => 'problems', 'title' => 'Симптомы и ситуации для лечения в Москве', 'description' => 'Подберите симптомы и ситуацию для обращения в клинику БИОИНМЕД в Москве: описание, этапы восстановления и подходящие услуги по каждому запросу.', 'eyebrow' => 'Ситуации и симптомы'],
    'privacy' => ['file' => 'privacy', 'title' => 'Политика конфиденциальности', 'description' => 'Политика конфиденциальности сайта клиники БИОИНМЕД: как мы обрабатываем, храним и защищаем персональные данные пользователей и пациентов.', 'eyebrow' => 'Правовая информация'],
    'user-agreement' => ['file' => 'user-agreement', 'title' => 'Пользовательское соглашение', 'description' => 'Пользовательское соглашение сайта клиники БИОИНМЕД: правила использования материалов, форм записи и сервисов обратной связи.', 'eyebrow' => 'Правовая информация'],
    '404' => ['file' => null, 'title' => 'Страница не найдена', 'description' => 'Запрошенная страница не найдена. Перейдите на главную или оставьте номер, и команда клиники поможет найти нужную услугу.', 'eyebrow' => ''],
];

foreach ($staticPages as $key => $item) {
    $meta = isset($item['file']) && $item['file'] ? $pageMeta((string)$item['file']) : [];
    $title = (string)($meta['title'] ?? $item['title']);
    if ($key === 'partners') {
        $title = 'Партнёры клиники';
    } elseif ($key === 'prices') {
        $title = 'Прайс-лист';
    }
    $add($key, (string)$item['eyebrow'], $title, (string)($meta['description'] ?? $item['description']), (string)($item['image'] ?? ''));
}

foreach (['habilect' => 'partnerhabilect', 'heel' => 'partnerheel'] as $slug => $file) {
    $meta = $pageMeta($file);
    $title = (string)($meta['title'] ?? ($slug === 'habilect' ? 'Хабилект — партнёр клиники БИОИНМЕД' : 'Heel — партнёр клиники БИОИНМЕД'));
    $title = trim((string)preg_replace('/\s+БИОИНМЕД$/u', '', $title));
    $description = (string)($meta['description'] ?? '');
    $image = $slug === 'habilect' ? '/public/images/partners/habilect-logo.png' : '/public/images/partners/heel-logo.svg';
    $add('partner-' . $slug, 'Партнёр клиники', $title, $description, $image, '#1977b2', 'logo');
}

foreach ($services as $service) {
    if (!is_array($service)) {
        continue;
    }
    $id = trim((string)($service['id'] ?? ''));
    $name = trim((string)($service['name'] ?? ''));
    if ($id === '' || $name === '') {
        continue;
    }
    $price = bioinmed_service_actual_price_label_by_id($id, trim((string)($service['price'] ?? '')));
    $desc = trim((string)($service['description'] ?? ''));
    if ($price !== '') {
        $desc = trim($desc . ' ' . $price . '.');
    }
    $add('service-' . $id, 'Услуга клиники', (string)($service['meta_title'] ?? $name), $desc, bioinmed_service_primary_image_url($service));
}

foreach ($doctors as $doctor) {
    if (!is_array($doctor)) {
        continue;
    }
    $slug = trim((string)($doctor['slug'] ?? ''));
    $name = trim((string)($doctor['name'] ?? ''));
    if ($slug === '' || $name === '') {
        continue;
    }
    $image = trim((string)($doctor['image'] ?? ''));
    $add('doctor-' . $slug, '', $name, trim((string)($doctor['specialty'] ?? '')) . '. ' . trim((string)($doctor['bio'] ?? '')), $image !== '' ? '/public/images/team/' . $image : '');
}

foreach ($problems as $problem) {
    if (!is_array($problem)) {
        continue;
    }
    $slug = trim((string)($problem['slug'] ?? ''));
    $title = trim((string)($problem['title'] ?? ''));
    if ($slug === '' || $title === '') {
        continue;
    }
    $add('problem-' . $slug, 'Ситуация пациента', $title, (string)($problem['page_description'] ?? ($problem['description'] ?? '')));
}
$problemMeta = $pageMeta('problem');
$add('problem-children', 'Детское направление', (string)($problemMeta['children_title'] ?? 'Проблемы детского и подросткового возраста'), (string)($problemMeta['children_description'] ?? ''));

$seasonConfig = is_array($seasons ?? null) ? $seasons : [];
foreach ($seasonConfig as $slug => $season) {
    if (!is_array($season)) {
        continue;
    }
    $seasonName = trim((string)($season['name'] ?? ''));
    $seasonTitle = $seasonName !== '' ? $seasonName : 'Сезонная программа БИОИНМЕД';
    $seasonDescription = trim((string)($season['slogan'] ?? '') . ' ' . (string)($season['intro'] ?? ''));
    $add('season-' . (string)$slug, 'Сезонная программа', $seasonTitle, $seasonDescription, (string)($season['image_desktop'] ?? ($season['image'] ?? '')), (string)($season['color'] ?? '#1977b2'));
}

$hexToRgb = static function (string $hex): array {
    $hex = ltrim($hex, '#');
    return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
};

$color = static function ($image, string $hex) use ($hexToRgb): int {
    [$r, $g, $b] = $hexToRgb($hex);
    return imagecolorallocate($image, $r, $g, $b);
};

$wrapText = static function (string $text, string $font, int $size, int $maxWidth) use ($e): array {
    $text = $e($text);
    $words = preg_split('/\s+/u', $text) ?: [];
    $lines = [];
    $line = '';
    foreach ($words as $word) {
        $candidate = $line === '' ? $word : $line . ' ' . $word;
        $box = imagettfbbox($size, 0, $font, $candidate);
        $width = is_array($box) ? abs($box[2] - $box[0]) : 0;
        if ($line !== '' && $width > $maxWidth) {
            $lines[] = $line;
            $line = $word;
        } else {
            $line = $candidate;
        }
    }
    if ($line !== '') {
        $lines[] = $line;
    }
    return $lines;
};

$fitLines = static function (string $text, string $font, int $startSize, int $minSize, int $maxWidth, int $maxLines) use ($wrapText): array {
    for ($size = $startSize; $size >= $minSize; $size -= 2) {
        $lines = $wrapText($text, $font, $size, $maxWidth);
        if (count($lines) <= $maxLines) {
            return [$size, $lines];
        }
    }
    $lines = array_slice($wrapText($text, $font, $minSize, $maxWidth), 0, $maxLines);
    if (count($lines) === $maxLines) {
        $last = $lines[$maxLines - 1];
        while (mb_strlen($last, 'UTF-8') > 8) {
            $candidate = rtrim(mb_substr($last, 0, -1, 'UTF-8'));
            $box = imagettfbbox($minSize, 0, $font, $candidate . '...');
            if (is_array($box) && abs($box[2] - $box[0]) <= $maxWidth) {
                $last = $candidate;
                break;
            }
            $last = $candidate;
        }
        $lines[$maxLines - 1] = rtrim($last, " \t\n\r\0\x0B.,;:!-") . '...';
    }
    return [$minSize, $lines];
};

$textWidth = static function (string $text, string $font, int $size): int {
    $box = imagettfbbox($size, 0, $font, $text);
    return is_array($box) ? abs($box[2] - $box[0]) : 0;
};

$drawTextRight = static function ($image, string $text, string $font, int $size, int $right, int $y, int $color) use ($textWidth): void {
    imagettftext($image, $size, 0, $right - $textWidth($text, $font, $size), $y, $color, $font, $text);
};

$loadPhoto = static function (string $path) use ($root) {
    $path = trim($path);
    if ($path === '') {
        return null;
    }
    if (preg_match('/^https?:\/\//i', $path)) {
        return null;
    }
    $local = $root . '/' . ltrim(preg_replace('/\?.*$/', '', $path) ?? $path, '/');
    if (!is_file($local)) {
        return null;
    }
    if (preg_match('/\.svg$/i', $local)) {
        $tmp = tempnam(sys_get_temp_dir(), 'bioinmed-og-svg-');
        if (!is_string($tmp)) {
            return null;
        }
        $png = $tmp . '.png';
        @unlink($tmp);
        $command = 'convert -background none -density 384 ' . escapeshellarg($local) . ' ' . escapeshellarg($png);
        @exec($command, $output, $exitCode);
        if ($exitCode !== 0 || !is_file($png)) {
            @unlink($png);
            return null;
        }
        $resource = @imagecreatefrompng($png);
        @unlink($png);
        return $resource ?: null;
    }
    $info = @getimagesize($local);
    if (!is_array($info)) {
        return null;
    }
    switch ((string)($info['mime'] ?? '')) {
        case 'image/jpeg':
            return @imagecreatefromjpeg($local);
        case 'image/png':
            return @imagecreatefrompng($local);
        case 'image/webp':
            return @imagecreatefromwebp($local);
        default:
            return null;
    }
};

$drawCover = static function ($dst, $src, int $x, int $y, int $w, int $h): void {
    $sw = imagesx($src);
    $sh = imagesy($src);
    if ($sw <= 0 || $sh <= 0) {
        return;
    }
    $scale = max($w / $sw, $h / $sh);
    $cw = (int)round($w / $scale);
    $ch = (int)round($h / $scale);
    $sx = max(0, (int)floor(($sw - $cw) / 2));
    $sy = max(0, (int)floor(($sh - $ch) / 2));
    imagecopyresampled($dst, $src, $x, $y, $sx, $sy, $w, $h, $cw, $ch);
};

$drawContain = static function ($dst, $src, int $x, int $y, int $w, int $h): void {
    $sw = imagesx($src);
    $sh = imagesy($src);
    if ($sw <= 0 || $sh <= 0) {
        return;
    }
    $scale = min($w / $sw, $h / $sh);
    $dw = (int)round($sw * $scale);
    $dh = (int)round($sh * $scale);
    $dx = $x + (int)round(($w - $dw) / 2);
    $dy = $y + (int)round(($h - $dh) / 2);
    imagecopyresampled($dst, $src, $dx, $dy, 0, 0, $dw, $dh, $sw, $sh);
};

$drawRoundedRect = static function ($image, int $x, int $y, int $w, int $h, int $radius, int $fill): void {
    imagefilledrectangle($image, $x + $radius, $y, $x + $w - $radius, $y + $h, $fill);
    imagefilledrectangle($image, $x, $y + $radius, $x + $w, $y + $h - $radius, $fill);
    imagefilledellipse($image, $x + $radius, $y + $radius, $radius * 2, $radius * 2, $fill);
    imagefilledellipse($image, $x + $w - $radius, $y + $radius, $radius * 2, $radius * 2, $fill);
    imagefilledellipse($image, $x + $radius, $y + $h - $radius, $radius * 2, $radius * 2, $fill);
    imagefilledellipse($image, $x + $w - $radius, $y + $h - $radius, $radius * 2, $radius * 2, $fill);
};

$drawCoverRounded = static function ($dst, $src, int $x, int $y, int $size, int $radius) use ($drawCover): void {
    $tmp = imagecreatetruecolor($size, $size);
    imagealphablending($tmp, false);
    imagesavealpha($tmp, true);
    imagefilledrectangle($tmp, 0, 0, $size, $size, imagecolorallocatealpha($tmp, 0, 0, 0, 127));
    imagealphablending($tmp, true);
    $drawCover($tmp, $src, 0, 0, $size, $size);

    imagealphablending($tmp, false);
    $transparent = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
    for ($px = 0; $px < $size; $px++) {
        for ($py = 0; $py < $size; $py++) {
            $cx = $px < $radius ? $radius : ($px >= $size - $radius ? $size - $radius - 1 : $px);
            $cy = $py < $radius ? $radius : ($py >= $size - $radius ? $size - $radius - 1 : $py);
            if (($px - $cx) * ($px - $cx) + ($py - $cy) * ($py - $cy) > $radius * $radius) {
                imagesetpixel($tmp, $px, $py, $transparent);
            }
        }
    }

    imagealphablending($dst, true);
    imagecopy($dst, $tmp, $x, $y, 0, 0, $size, $size);
    imagedestroy($tmp);
};

$manifest = [
    'generated_at' => gmdate('c'),
    'version' => 28,
    'images' => [],
];
$generated = [];

foreach ($descriptors as $descriptor) {
    $descriptor['design_version'] = 28;
    $descriptor['contact_address'] = CLINIC_ADDRESS;
    $descriptor['contact_email'] = CLINIC_EMAIL;
    $descriptor['font_signature'] = basename($regularFont) . ':' . basename($boldFont);
    $hash = substr(hash('sha256', json_encode($descriptor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)), 0, 12);
    $file = $descriptor['key'] . '-' . $hash . '.webp';
    $relativePath = '/public/images/og/' . $file;
    $target = $outputDir . '/' . $file;
    $generated[$file] = true;

    if (!is_file($target)) {
        $image = imagecreatetruecolor(2400, 1260);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        $bg = $color($image, '#f8fbff');
        $white = $color($image, '#ffffff');
        $ink = $color($image, '#0f2749');
        $muted = $color($image, '#355b89');
        $accent = $color($image, (string)$descriptor['accent']);
        imagefilledrectangle($image, 0, 0, 2400, 1260, $bg);

        $photoPath = trim((string)$descriptor['image']);
        if ($photoPath === '') {
            $photoPath = '/public/images/habilect-family.webp';
        }
        $photo = $loadPhoto($photoPath);
        if ((string)($descriptor['visual_type'] ?? 'photo') === 'logo') {
            if ($photo) {
                $drawContain($image, $photo, 1488, 430, 736, 400);
                imagedestroy($photo);
            }
        } elseif ($photo) {
            $drawCoverRounded($image, $photo, 1456, 252, 800, 48);
            imagedestroy($photo);
        } else {
            $drawRoundedRect($image, 1456, 252, 800, 800, 48, imagecolorallocatealpha($image, 25, 119, 178, 72));
        }

        $logo = $loadPhoto('/public/images/brand/main-logotype.png');
        if ($logo) {
            $lw = imagesx($logo);
            $lh = imagesy($logo);
            $targetW = 620;
            $targetH = (int)round($lh * ($targetW / max(1, $lw)));
            imagecopyresampled($image, $logo, 144, 70, 0, 0, $targetW, $targetH, $lw, $lh);
            imagedestroy($logo);
        } else {
            imagettftext($image, 60, 0, 144, 146, $ink, $boldFont, CLINIC_NAME);
        }

        $drawTextRight($image, CLINIC_PHONE, $regularFont, 32, 2256, 140, $muted);

        [$titleSize, $titleLines] = $fitLines((string)$descriptor['title'], $boldFont, 72, 54, 1096, 3);
        [$descSize, $descLines] = $fitLines((string)$descriptor['description'], $regularFont, 34, 28, 1096, 4);

        $titleLineHeight = (int)round($titleSize * 1.32);
        $descLineHeight = (int)round($descSize * 1.58);
        $copyGap = 88;
        $copyHeight = count($titleLines) * $titleLineHeight + $copyGap + count($descLines) * $descLineHeight;
        $y = max(360, (int)round(252 + (800 - $copyHeight) / 2 + $titleSize));

        foreach ($titleLines as $line) {
            imagettftext($image, $titleSize, 0, 144, $y, $ink, $boldFont, $line);
            $y += $titleLineHeight;
        }

        $y += $copyGap;
        foreach ($descLines as $line) {
            imagettftext($image, $descSize, 0, 144, $y, $muted, $regularFont, $line);
            $y += $descLineHeight;
        }

        imagettftext($image, 32, 0, 144, 1160, $muted, $regularFont, CLINIC_ADDRESS);
        $drawTextRight($image, CLINIC_EMAIL, $regularFont, 32, 2256, 1160, $muted);

        if (!imagewebp($image, $target, 100)) {
            imagedestroy($image);
            fwrite(STDERR, "Failed to write {$target}\n");
            exit(1);
        }
        imagedestroy($image);
    }

    $manifest['images'][$descriptor['key']] = [
        'path' => $relativePath,
        'width' => 2400,
        'height' => 1260,
        'type' => 'image/webp',
        'hash' => $hash,
        'title' => $descriptor['title'],
    ];
}

foreach (glob($outputDir . '/*.webp') ?: [] as $oldFile) {
    $base = basename($oldFile);
    if (!isset($generated[$base])) {
        @unlink($oldFile);
    }
}

$json = json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
if (!is_string($json) || file_put_contents($manifestFile, $json . "\n", LOCK_EX) === false) {
    fwrite(STDERR, "Failed to write manifest: {$manifestFile}\n");
    exit(1);
}

if (!$quiet) {
    echo 'Generated OG images: ' . count($manifest['images']) . "\n";
    echo $manifestFile . "\n";
}
