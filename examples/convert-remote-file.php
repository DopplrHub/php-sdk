<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use DopplrHub\DopplrHub;

$api = new DopplrHub(
    getenv('DOPPLERHUB_API_KEY') ?: 'YOUR_API_KEY',
    getenv('DOPPLERHUB_BASE_URL') ?: 'https://api.dopplrhub.com/api/v1'
);

$api->startFromURL('https://example.com/sample.pdf', 'png')
    ->wait()
    ->download(__DIR__ . '/sample.png')
    ->delete();
