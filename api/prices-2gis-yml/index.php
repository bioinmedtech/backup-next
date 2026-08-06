<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/includes/prices/YmlFeed.php';

$pricesPath = dirname(__DIR__, 2) . '/data/content/ru/pages/prices.json';
$feed = bioinmed_prices_yml_read_payload($pricesPath);
if (!is_array($feed['payload'])) {
    http_response_code(503);
    header('Content-Type: application/xml; charset=UTF-8');
    echo '<?xml version="1.0" encoding="UTF-8"?><error>Price list is temporarily unavailable</error>';
    exit;
}

bioinmed_prices_yml_output($feed['payload'], $feed['raw'], $pricesPath, '2gis');
