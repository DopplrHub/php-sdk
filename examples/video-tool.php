<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use DopplrHub\DopplrHub;

$api = new DopplrHub(
    getenv('DOPPLERHUB_API_KEY') ?: 'YOUR_API_KEY',
    getenv('DOPPLERHUB_BASE_URL') ?: 'https://api.dopplrhub.com/api/v1'
);

$api->tools()->videoTrim(__DIR__ . '/clip.mp4', 3, 12, [
    'outputFormat' => 'mp4',
])->wait()->download(__DIR__ . '/clip-trimmed.mp4');
