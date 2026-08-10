<?php

$root = dirname(__DIR__);
$sourceRoot = $root . '/public/images/license';
$outputRoot = $sourceRoot . '/watermarked';
$watermarkLogoSource = $root . '/public/images/brand/main-logotype.png';
$watermarkTile = sys_get_temp_dir() . '/bioinmed-license-watermark-tile-' . getmypid() . '.png';

function run_command(array $command, string $errorMessage): void
{
    $escaped = array_map('escapeshellarg', $command);
    exec(implode(' ', $escaped), $output, $exitCode);
    if ($exitCode !== 0) {
        fwrite(STDERR, $errorMessage . "\n");
        exit(1);
    }
}

if (!is_file($watermarkLogoSource)) {
    fwrite(STDERR, "Missing watermark logo: {$watermarkLogoSource}\n");
    exit(1);
}

if (!is_dir($outputRoot) && !mkdir($outputRoot, 0775, true) && !is_dir($outputRoot)) {
    fwrite(STDERR, "Cannot create output directory: {$outputRoot}\n");
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
    $watermarkTile,
], 'Failed to prepare license watermark tile');

for ($i = 1; $i <= 5; $i++) {
    $source = $sourceRoot . '/license-page-' . $i . '.jpeg';
    $base = sys_get_temp_dir() . '/bioinmed-license-page-' . $i . '-' . getmypid() . '.png';
    $output = $outputRoot . '/license-page-' . $i . '.webp';

    if (!is_file($source)) {
        fwrite(STDERR, "Missing license page: {$source}\n");
        exit(1);
    }

    run_command([
        'convert',
        $source,
        '-auto-orient',
        '-resize',
        '1800x2400>',
        $base,
    ], "Failed to normalize license page {$i}");

    run_command([
        'composite',
        '-tile',
        $watermarkTile,
        $base,
        '-quality',
        '82',
        $output,
    ], "Failed to write watermarked license page {$i}");

    @unlink($base);
}

@unlink($watermarkTile);
echo "Built watermarked license documents: {$outputRoot}\n";
