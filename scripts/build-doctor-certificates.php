<?php

$root = dirname(__DIR__);
$sourceRoot = $root . '/public/images/certificates';
$outputRoot = $root . '/public/images/certificates-watermarked';
$outputPublicRoot = '/public/images/certificates-watermarked';
$metadataFile = $root . '/data/content/ru/certificates.json';
$watermarkLogoSource = $root . '/public/images/brand/main-logotype.png';
$watermarkLogo = sys_get_temp_dir() . '/bioinmed-certificate-watermark-logo-' . getmypid() . '.png';

$doctorMap = [
    'kostromina' => 'kostromina-inna-viktorovna',
    'rozhkov' => 'rozhkov-sergei-leonidovich',
    'kondratova' => 'kondratova-elena-aleksandrovna',
    'navrozov' => 'navrozov-evgeniy-sergeevich',
    'mayorova' => 'mayorova-darya-sergeevna',
    'vertlib' => 'vertlib-valeriya-pavlovna',
    'nehorosheva' => 'nehorosheva-lyudmila-sergeevna',
];

$known = [
    'kostromina/Удостоверение Рефлексотерапия 2020.jpg' => ['title' => 'Удостоверение о повышении квалификации: рефлексотерапия', 'date' => '2020'],
    'kostromina/Сертификат Рефлексотерапия 2020.jp2' => ['title' => 'Сертификат специалиста: рефлексотерапия', 'date' => '2020'],
    'kostromina/Сертификат Огранизация здравоохранения 2020.jpg' => ['title' => 'Сертификат специалиста: организация здравоохранения', 'date' => '2020'],
    'kostromina/Удостоверение Огранизация здравоохранения 2020.jpg' => ['title' => 'Удостоверение о повышении квалификации: организация здравоохранения', 'date' => '2020'],
    'kostromina/Удостоверение о повышении квалификации Биорезонансная терапия.jpeg' => ['title' => 'Удостоверение о повышении квалификации: биорезонансная терапия', 'date' => ''],
    'kostromina/Удостоверение о повышении квалификации Ударно-волновая терапия.jpeg' => ['title' => 'Удостоверение о повышении квалификации: ударно-волновая терапия', 'date' => ''],
    'kostromina/Карбокситерапия.jpeg' => ['title' => 'Сертификат: карбокситерапия', 'date' => ''],
    'kostromina/Дерматокосметология.jpeg' => ['title' => 'Сертификат: дерматокосметология', 'date' => ''],
    'kostromina/Гомеопатия.jpeg' => ['title' => 'Документ о подготовке: гомеопатия', 'date' => ''],
    'kostromina/Гомеопатия 2.jpeg' => ['title' => 'Документ о подготовке: гомеопатия', 'date' => ''],
    'kostromina/Артемида-Про.jpeg' => ['title' => 'Сертификат: «Артемида-Про»', 'date' => ''],
    'kostromina/Сертификат Хабилект.jpeg' => ['title' => 'Сертификат: «Хабилект»', 'date' => ''],
    'kostromina/Повышение квалификации 2025 Рефлексотерапия.jpg' => ['title' => 'Повышение квалификации: рефлексотерапия', 'date' => '2025'],
    'kostromina/Повышение квалификации 2025 Организация здравоохранения.jpg' => ['title' => 'Повышение квалификации: организация здравоохранения', 'date' => '2025'],

    'rozhkov/Сертификат Рожков С.Л.jpeg' => ['title' => 'Сертификат: точность и погрешность биомеханической оценки', 'date' => '2026'],
    'rozhkov/Ударно-волновая терапия Рожков С.Л.jpeg' => ['title' => 'Сертификат: ударно-волновая терапия', 'date' => ''],
    'rozhkov/Тренер-консультант Рожков С.Л.jpeg' => ['title' => 'Сертификат: тренер-консультант', 'date' => ''],

    'kondratova/Удостоверение Рефлексотерапия.jpg' => ['title' => 'Удостоверение о повышении квалификации: рефлексотерапия', 'date' => ''],
    'kondratova/Рефлексотерапия в косметологии Кондратова Е.А.jpeg' => ['title' => 'Сертификат: рефлексотерапия в косметологии', 'date' => ''],
    'kondratova/Фармакопунктура 2 Кондратова Е.А..jpg' => ['title' => 'Сертификат: фармакопунктура', 'date' => ''],
    'kondratova/Удостоверение.jpg' => ['title' => 'Удостоверение о повышении квалификации', 'date' => ''],

    'nehorosheva/1 - Остеопат-помощник Нехорошева Л.С.jpeg' => ['title' => 'Сертификат: остеопат-помощник', 'date' => ''],
    'nehorosheva/2 - Терапия 1-2 уровень Нехорошева Л.С.jpeg' => ['title' => 'Сертификат: терапия, 1-2 уровень', 'date' => ''],

    'mayorova/Сертификат.jpeg' => ['title' => 'Сертификат специалиста', 'date' => ''],
    'mayorova/Сертификат 2.jpeg' => ['title' => 'Сертификат специалиста', 'date' => ''],

    'vertlib/Сертификат.jpg' => ['title' => 'Сертификат специалиста', 'date' => ''],
    'vertlib/Сертификат 2.jpg' => ['title' => 'Сертификат специалиста', 'date' => ''],
    'vertlib/Сертификат 3.jpg' => ['title' => 'Сертификат специалиста', 'date' => ''],
    'vertlib/Сертификат 4.jpg' => ['title' => 'Сертификат специалиста', 'date' => ''],
];

function certificate_title_from_filename(string $filename): string
{
    $name = preg_replace('~\.[^.]+$~u', '', $filename);
    $name = preg_replace('~^\d+\s*-\s*~u', '', (string)$name);
    $name = preg_replace('~\s+[А-ЯЁ]\.[А-ЯЁ]\.?$~u', '', (string)$name);
    $name = preg_replace('~\s+Кондратова\s+Е\.*А\.*$~u', '', (string)$name);
    $name = preg_replace('~\s+Кондратова$~u', '', (string)$name);
    $name = preg_replace('~\s+Рожков\s+С\.*Л\.*$~u', '', (string)$name);
    $name = preg_replace('~\s+Нехорошева\s+Л\.*С\.*$~u', '', (string)$name);
    $name = preg_replace('~\s+Нехорошева$~u', '', (string)$name);
    $name = trim(preg_replace('~\s+~u', ' ', (string)$name));
    if ($name === '' || preg_match('~^Сертификат\s*\d*$~u', $name)) {
        return 'Сертификат специалиста';
    }
    if (preg_match('~^Удостоверение$~u', $name)) {
        return 'Удостоверение о повышении квалификации';
    }
    return $name;
}

function certificate_date_from_filename(string $filename): string
{
    if (preg_match('~(19|20)\d{2}~u', $filename, $match)) {
        return $match[0];
    }
    return '';
}

function certificate_sort_value(array $item): int
{
    if (($item['date'] ?? '') !== '' && preg_match('~^\d{4}$~', (string)$item['date'])) {
        return (int)$item['date'];
    }
    if (preg_match('~/(?:\d+\s*-\s*)~u', (string)$item['source']) && preg_match('~/(\d+)\s*-~u', (string)$item['source'], $match)) {
        return 1000 + (int)$match[1];
    }
    return 9999;
}

function run_command(array $command, string $errorMessage): void
{
    $escaped = array_map('escapeshellarg', $command);
    exec(implode(' ', $escaped), $output, $exitCode);
    if ($exitCode !== 0) {
        fwrite(STDERR, $errorMessage . "\n");
        exit(1);
    }
}

if (!is_dir($sourceRoot)) {
    fwrite(STDERR, "Missing source directory: {$sourceRoot}\n");
    exit(1);
}

if (!is_dir($outputRoot) && !mkdir($outputRoot, 0775, true) && !is_dir($outputRoot)) {
    fwrite(STDERR, "Cannot create output directory: {$outputRoot}\n");
    exit(1);
}

if (!is_file($watermarkLogoSource)) {
    fwrite(STDERR, "Missing watermark logo: {$watermarkLogoSource}\n");
    exit(1);
}

run_command([
    'convert',
    $watermarkLogoSource,
    '-resize',
    '320x96',
    '-alpha',
    'set',
    '-channel',
    'A',
    '-evaluate',
    'multiply',
    '0.20',
    '+channel',
    '-background',
    'none',
    '-rotate',
    '-45',
    '-gravity',
    'center',
    '-extent',
    '420x220',
    $watermarkLogo,
], 'Failed to prepare watermark logo');

$metadata = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($sourceRoot, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $fileInfo) {
    if (!$fileInfo->isFile()) {
        continue;
    }

    $extension = strtolower($fileInfo->getExtension());
    if (!in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
        continue;
    }

    $relative = str_replace('\\', '/', substr($fileInfo->getPathname(), strlen($sourceRoot) + 1));
    $parts = explode('/', $relative);
    $folder = $parts[0] ?? '';
    if (!isset($doctorMap[$folder])) {
        continue;
    }

    $doctorSlug = $doctorMap[$folder];
    $slugBase = 'cert-' . substr(sha1($relative), 0, 14);

    $doctorOutputDir = $outputRoot . '/' . $doctorSlug;
    if (!is_dir($doctorOutputDir) && !mkdir($doctorOutputDir, 0775, true) && !is_dir($doctorOutputDir)) {
        fwrite(STDERR, "Cannot create output directory: {$doctorOutputDir}\n");
        exit(1);
    }

    $outputPath = $doctorOutputDir . '/' . $slugBase . '.webp';
    $publicPath = $outputPublicRoot . '/' . $doctorSlug . '/' . $slugBase . '.webp';
    $sourcePath = $fileInfo->getPathname();
    $tempBase = sys_get_temp_dir() . '/bioinmed-certificate-base-' . sha1($relative) . '.png';

    run_command([
        'convert',
        $sourcePath,
        '-auto-orient',
        '-resize',
        '1800x1800>',
        $tempBase,
    ], "Failed to normalize certificate: {$relative}");

    run_command([
        'composite',
        '-tile',
        $watermarkLogo,
        $tempBase,
        '-quality',
        '82',
        $outputPath,
    ], "Failed to write watermarked WebP: {$relative}");

    @unlink($tempBase);

    $knownItem = $known[$relative] ?? [];
    $date = (string)($knownItem['date'] ?? certificate_date_from_filename($relative));
    $title = (string)($knownItem['title'] ?? certificate_title_from_filename(basename($relative)));

    $metadata[$doctorSlug][] = [
        'title' => $title,
        'date' => $date,
        'image' => $publicPath,
        'source' => '/public/images/certificates/' . $relative,
    ];
}

foreach ($metadata as &$items) {
    usort($items, static function (array $a, array $b): int {
        $sort = certificate_sort_value($a) <=> certificate_sort_value($b);
        if ($sort !== 0) {
            return $sort;
        }
        return strnatcasecmp((string)$a['source'], (string)$b['source']);
    });
}
unset($items);

ksort($metadata);

$json = json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
if ($json === false) {
    fwrite(STDERR, "Failed to encode metadata\n");
    exit(1);
}

file_put_contents($metadataFile, $json . "\n");
@unlink($watermarkLogo);
echo "Built certificates metadata: {$metadataFile}\n";
