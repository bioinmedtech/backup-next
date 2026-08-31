<?php

function bioinmed_render_blog_weather_assets() {
    $config = [
        'version' => '20260901-weather-v6',
        'weatherEndpointVersion' => (string)(int)@filemtime(__DIR__ . '/../../api/weather-forecast/index.php'),
    ];

    $scriptSrc = bioinmed_versioned_asset_path('/public/assets/js/blog-weather.js');

    return '<script>window.bioinmedBlogWeatherConfig='
        . json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        . ';</script>' . "\n"
        . '<script src="' . htmlspecialchars($scriptSrc, ENT_QUOTES, 'UTF-8') . '" defer></script>';
}
