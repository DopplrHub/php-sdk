<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use DopplerHub\DopplerHub;

$api = new DopplerHub(
    getenv('DOPPLERHUB_API_KEY') ?: 'YOUR_API_KEY',
    getenv('DOPPLERHUB_BASE_URL') ?: 'https://api.dopplrhub.com/api/v1'
);

$api->tools()->ats(
    __DIR__ . '/resume.pdf',
    'Senior PHP engineer with API design experience',
    ['industry' => 'technology']
)->download(__DIR__ . '/resume-optimized.docx');
