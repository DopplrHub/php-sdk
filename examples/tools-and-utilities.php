<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use DopplerHub\DopplerHub;

$api = new DopplerHub(
    getenv('DOPPLERHUB_API_KEY') ?: 'YOUR_API_KEY',
    getenv('DOPPLERHUB_BASE_URL') ?: 'https://api.dopplrhub.com/api/v1'
);

$rates = $api->utilities()->currencyRates('USD');
print_r(array_slice($rates['rates'], 0, 5, true));

$api->tools()->ocr(__DIR__ . '/input.pdf', 'ocr-docx', ['language' => 'eng'])
    ->wait()
    ->download(__DIR__ . '/input.docx');

$api->tools()->pdfCompress(__DIR__ . '/packet.pdf', 'screen')
    ->wait()
    ->download(__DIR__ . '/packet-compressed.pdf');

$api->tools()->imageResize(__DIR__ . '/hero.png', [
    'width' => 1920,
    'height' => 1080,
    'fit' => 'cover',
    'outputFormat' => 'webp',
])->wait()->download(__DIR__ . '/hero.webp');

$api->tools()->videoTrim(__DIR__ . '/clip.mp4', 3, 12, [
    'outputFormat' => 'mp4',
])->wait()->download(__DIR__ . '/clip-trimmed.mp4');

$api->tools()->ada(__DIR__ . '/brochure.pdf')
    ->download(__DIR__ . '/brochure-ada-report.pdf');

$api->tools()->ats(
    __DIR__ . '/resume.pdf',
    'Senior PHP engineer with API design experience',
    ['industry' => 'technology']
)->download(__DIR__ . '/resume-optimized.docx');
