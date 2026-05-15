# DopplrHub PHP SDK

A chainable PHP SDK for the current DopplrHub public API, including generic conversions, tools, and utility endpoints.

## Install

```bash
composer require dopplrhub/php-sdk
```

Or for this local scaffold:

```bash
cd D:\AudioConverter\sdk\php-sdk
composer dump-autoload
```

If you want to distribute the SDK directly from the backend, this workspace now exposes a zip bundle at `/api/sdk/php-sdk.zip`.

## Local file conversion

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use DopplrHub\DopplrHub;

$api = new DopplrHub('YOUR_API_KEY', 'https://api.dopplrhub.com/api/v1');

$api->start('./input.pdf', 'jpg')
    ->wait()
    ->download('./input.jpg')
    ->delete();
```

## Remote file conversion

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use DopplrHub\DopplrHub;

$api = new DopplrHub('YOUR_API_KEY', 'https://api.dopplrhub.com/api/v1');

$api->startFromURL('https://example.com/brochure.pdf', 'png')
    ->wait()
    ->download('./brochure.png')
    ->delete();
```

## Tools

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use DopplrHub\DopplrHub;

$api = new DopplrHub('YOUR_API_KEY', 'https://api.dopplrhub.com/api/v1');

$api->tools()->ocr('./scan.pdf', 'ocr-docx', ['language' => 'eng'])
    ->wait()
    ->download('./scan.docx');

$api->tools()->imageResize('./hero.png', [
    'width' => 1920,
    'height' => 1080,
    'fit' => 'cover',
    'outputFormat' => 'webp',
])->wait()->download('./hero.webp');

$api->tools()->pdfCompress('./packet.pdf', 'screen')
    ->wait()
    ->download('./packet-compressed.pdf');

$api->tools()->videoTrim('./clip.mp4', 3, 12, [
    'outputFormat' => 'mp4',
])->wait()->download('./clip-trimmed.mp4');

$api->tools()->ada('./brochure.pdf')
    ->download('./brochure-ada-report.pdf');

$api->tools()->ats('./resume.pdf', 'Senior PHP engineer with API design experience', [
    'industry' => 'technology',
])->download('./resume-optimized.docx');

$api->tools()->archive(['./a.txt', './b.txt'], 'zip', [
    'archiveName' => 'documents',
])->wait()->download('./documents.zip');

$api->tools()->socialResize('./hero.png', 'instagram', ['post-square', 'story'], [
    'outputFormat' => 'jpg',
])->wait()->download('./hero-instagram.zip');

$api->tools()->atsReexport($report, 'modern', [
    'downloadAs' => 'resume-modern.docx',
])->download('./resume-modern.docx');
```

Tool coverage in the PHP SDK includes `ocr()`, `pdf()`, `image()`, `video()`, `ada()`, `ats()`, `atsReexport()`, `archive()`, and `socialResize()` on `$api->tools()`.

## Examples

- `examples/convert-local-file.php`
- `examples/convert-remote-file.php`
- `examples/ocr-tool.php`
- `examples/pdf-tool.php`
- `examples/image-tool.php`
- `examples/video-tool.php`
- `examples/ada-tool.php`
- `examples/ats-tool.php`
- `examples/tools-and-utilities.php`

## Utilities

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use DopplrHub\DopplrHub;

$api = new DopplrHub('YOUR_API_KEY', 'https://api.dopplrhub.com/api/v1');

$formats = $api->utilities()->supportedFormats();
$rates = $api->utilities()->currencyRates('USD');
$api->utilities()->batchDownload(['JOB_ID_1', 'JOB_ID_2'], './converted_files.zip');
```

## Important behavior note

`startFromURL()` currently downloads the remote resource first, then uploads it into DopplrHub.
It does not perform headless browser webpage rendering.

That means this shape is ready today for remote files like PDF, DOCX, TXT, and other downloadable assets. A call like `startFromURL('http://google.com/', 'png')` would require a backend webpage-render route that does not exist in the current API yet.

## API summary

- `upload(string $filePath): UploadedFile`
- `importFromUrl(string $url, array $options = []): UploadedFile`
- `start(string $filePath, string $targetFormat, array $options = []): ConversionJob`
- `startFromContents(string $contents, string $fileName, string $targetFormat): ConversionJob`
- `startFromURL(string $url, string $targetFormat, array $options = []): ConversionJob`
- `convert(mixed $source, string $targetFormat, array $options = []): ConversionJob`
- `tools(): ToolsClient`
- `utilities(): UtilitiesClient`
- `ConversionJob::wait(?int $timeoutSeconds = null, int $pollSeconds = 2): self`
- `ConversionJob::download(?string $targetPath = null): self`
- `ConversionJob::delete(): self`
