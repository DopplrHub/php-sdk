<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use DopplerHub\DopplerHub;

$api = new DopplerHub(
    getenv('DOPPLERHUB_API_KEY') ?: 'YOUR_API_KEY',
    getenv('DOPPLERHUB_BASE_URL') ?: 'https://api.dopplrhub.com/api/v1'
);

$api->tools()->ocr(__DIR__ . '/scan.pdf', 'ocr-docx', ['language' => 'eng'])
    ->wait()
    ->download(__DIR__ . '/scan.docx');
