<?php

declare(strict_types=1);

namespace DopplrHub;

final class ToolsClient
{
    public function __construct(private readonly DopplrHub $client)
    {
    }

    public function pdfMerge(array $sources, array $params = [], array $options = []): ConversionJob
    {
        return $this->pdf($sources, 'merge', $params, $options);
    }

    public function pdfSplit(mixed $source, string $ranges = '', array $options = []): ConversionJob
    {
        return $this->pdf($source, 'split', ['ranges' => $ranges], $options);
    }

    public function pdfCompress(mixed $source, string $quality = 'medium', array $options = []): ConversionJob
    {
        return $this->pdf($source, 'compress', ['quality' => $quality], $options);
    }

    public function pdfRotate(mixed $source, int $degrees = 90, string $pages = 'all', array $options = []): ConversionJob
    {
        return $this->pdf($source, 'rotate', ['degrees' => $degrees, 'pages' => $pages], $options);
    }

    public function pdfProtect(mixed $source, string $userPassword, array $options = []): ConversionJob
    {
        return $this->pdf($source, 'protect', [
            'userPassword' => $userPassword,
            'ownerPassword' => $options['ownerPassword'] ?? '',
        ], $options);
    }

    public function pdfUnlock(mixed $source, string $password, array $options = []): ConversionJob
    {
        return $this->pdf($source, 'unlock', ['password' => $password], $options);
    }

    public function pdfFlatten(mixed $source, array $options = []): ConversionJob
    {
        return $this->pdf($source, 'flatten', [], $options);
    }

    public function pdfResize(mixed $source, ?int $width = null, ?int $height = null, array $options = []): ConversionJob
    {
        return $this->pdf($source, 'resize', array_filter(['width' => $width, 'height' => $height], static fn ($v) => $v !== null), $options);
    }

    public function pdfCrop(mixed $source, int $left, int $top, int $width, int $height, array $options = []): ConversionJob
    {
        return $this->pdf($source, 'crop', ['left' => $left, 'top' => $top, 'width' => $width, 'height' => $height], $options);
    }

    public function pdfOrganize(mixed $source, string $pages = '', array $options = []): ConversionJob
    {
        return $this->pdf($source, 'organize', ['pages' => $pages], $options);
    }

    public function pdfExtractImages(mixed $source, array $options = []): ConversionJob
    {
        return $this->pdf($source, 'extract-images', [], $options);
    }

    public function pdfRemovePages(mixed $source, string $pages = '', array $options = []): ConversionJob
    {
        return $this->pdf($source, 'remove-pages', ['pages' => $pages], $options);
    }

    public function pdfExtractPages(mixed $source, string $ranges = '', array $options = []): ConversionJob
    {
        return $this->pdf($source, 'extract-pages', ['ranges' => $ranges], $options);
    }

    public function socialResize(mixed $source, string $platform, array $selectedSizeIds, array $options = []): ConversionJob
    {
        $upload = $this->client->normalizeUpload($source, $options);

        return $this->client->submitJob('/tools/social-resize', array_filter([
            'fileId' => $upload->fileId(),
            'inputKey' => $upload->inputKey(),
            'originalName' => $options['originalName'] ?? $upload->fileName(),
            'platform' => $platform,
            'selectedSizeIds' => $selectedSizeIds,
            'outputFormat' => $options['outputFormat'] ?? 'jpg',
            'offsets' => $options['offsets'] ?? [],
            'fileSizeBytes' => $options['fileSizeBytes'] ?? null,
        ], static fn ($v) => $v !== null));
    }

    public function imageResize(mixed $source, array $params = [], array $options = []): ConversionJob
    {
        return $this->image($source, 'resize', [
            'width' => $params['width'] ?? null,
            'height' => $params['height'] ?? null,
            'fit' => $params['fit'] ?? 'inside',
            'outputFormat' => $params['outputFormat'] ?? 'jpg',
        ], $options);
    }

    public function imageCrop(mixed $source, int $left, int $top, int $width, int $height, array $options = []): ConversionJob
    {
        return $this->image($source, 'crop', [
            'left' => $left,
            'top' => $top,
            'width' => $width,
            'height' => $height,
            'outputFormat' => $options['outputFormat'] ?? 'jpg',
        ], $options);
    }

    public function imageRotate(mixed $source, int $angle = 90, array $options = []): ConversionJob
    {
        return $this->image($source, 'rotate', [
            'angle' => $angle,
            'outputFormat' => $options['outputFormat'] ?? 'jpg',
        ], $options);
    }

    public function imageFlip(mixed $source, string $direction = 'horizontal', array $options = []): ConversionJob
    {
        return $this->image($source, 'flip', [
            'direction' => $direction,
            'outputFormat' => $options['outputFormat'] ?? 'jpg',
        ], $options);
    }

    public function imageUpscale(mixed $source, float $scale = 2, array $options = []): ConversionJob
    {
        return $this->image($source, 'upscale', [
            'scale' => $scale,
            'width' => $options['width'] ?? null,
            'height' => $options['height'] ?? null,
            'outputFormat' => $options['outputFormat'] ?? 'jpg',
        ], $options);
    }

    public function videoTrim(mixed $source, float $startTime = 0, ?float $endTime = null, array $options = []): ConversionJob
    {
        return $this->video($source, 'trim', [
            'outputFormat' => $options['outputFormat'] ?? 'mp4',
            'trim' => array_filter([
                'enabled' => true,
                'startTime' => $startTime,
                'endTime' => $endTime,
            ], static fn ($value) => $value !== null),
        ], $options);
    }

    public function videoExtract(mixed $source, float $startTime = 0, ?float $endTime = null, array $options = []): ConversionJob
    {
        return $this->video($source, 'extract', [
            'outputFormat' => $options['outputFormat'] ?? 'mp4',
            'trim' => array_filter([
                'enabled' => true,
                'startTime' => $startTime,
                'endTime' => $endTime,
            ], static fn ($value) => $value !== null),
        ], $options);
    }

    public function videoCrop(mixed $source, int $left, int $top, int $width, int $height, array $options = []): ConversionJob
    {
        return $this->video($source, 'crop', [
            'left' => $left,
            'top' => $top,
            'width' => $width,
            'height' => $height,
            'outputFormat' => $options['outputFormat'] ?? 'mp4',
        ], $options);
    }

    public function ocr(mixed $source, string $targetFormat = 'ocr-pdf', array $options = []): ConversionJob
    {
        $upload = $this->client->normalizeUpload($source, $options);

        return $this->client->submitJob('/tools/ocr', [
            'fileId' => $upload->fileId(),
            'inputKey' => $upload->inputKey(),
            'targetFormat' => $targetFormat,
            'originalName' => $options['originalName'] ?? $upload->fileName(),
            'language' => $options['language'] ?? 'eng',
        ]);
    }

    public function pdf(mixed $source, string $operation, array $params = [], array $options = []): ConversionJob
    {
        $payload = [
            'operation' => $operation,
            'params' => $params,
        ];

        if ($operation === 'merge') {
            $mergeSources = is_array($source) ? $source : ($options['sources'] ?? null);
            if (!is_array($mergeSources) || $mergeSources === []) {
                throw new ApiException('PDF merge requires an array of sources.');
            }

            $uploads = $this->client->normalizeUploads($mergeSources);
            $payload['fileId'] = $uploads[0]->fileId();
            $payload['inputKeys'] = array_map(static fn (UploadedFile $item) => $item->inputKey(), $uploads);
            $payload['inputKey'] = $payload['inputKeys'][0];
            $payload['originalName'] = $options['originalName'] ?? $uploads[0]->fileName();
        } else {
            $upload = $this->client->normalizeUpload($source, $options);
            $payload['fileId'] = $upload->fileId();
            $payload['inputKey'] = $upload->inputKey();
            $payload['originalName'] = $options['originalName'] ?? $upload->fileName();
        }

        return $this->client->submitJob('/tools/pdf', $payload);
    }

    public function image(mixed $source, string $operation, array $params = [], array $options = []): ConversionJob
    {
        $upload = $this->client->normalizeUpload($source, $options);

        return $this->client->submitJob('/tools/image', [
            'operation' => $operation,
            'fileId' => $upload->fileId(),
            'inputKey' => $upload->inputKey(),
            'originalName' => $options['originalName'] ?? $upload->fileName(),
            'params' => $params,
        ]);
    }

    public function video(mixed $source, string $operation, array $params = [], array $options = []): ConversionJob
    {
        $upload = $this->client->normalizeUpload($source, $options);

        return $this->client->submitJob('/tools/video', [
            'operation' => $operation,
            'fileId' => $upload->fileId(),
            'inputKey' => $upload->inputKey(),
            'originalName' => $options['originalName'] ?? $upload->fileName(),
            'params' => $params,
        ]);
    }

    public function archive(array $sources, string $targetFormat = 'zip', array $options = []): ConversionJob
    {
        $uploads = $this->client->normalizeUploads($sources);

        return $this->client->submitJob('/tools/archive', [
            'inputKeys' => array_map(static fn (UploadedFile $item) => $item->inputKey(), $uploads),
            'fileNames' => array_map(static fn (UploadedFile $item) => $item->fileName(), $uploads),
            'targetFormat' => $targetFormat,
            'archiveName' => $options['archiveName'] ?? 'archive',
            'inputPassword' => $options['inputPassword'] ?? '',
            'outputPassword' => $options['outputPassword'] ?? '',
        ]);
    }

    public function ada(mixed $source, array $options = []): ImmediateResult
    {
        $upload = $this->client->normalizeUpload($source, $options);
        $response = $this->client->requestJson('POST', '/tools/ada/analyze', [
            'json' => array_filter([
                'fileId' => $upload->fileId(),
                'inputKey' => $upload->inputKey(),
                'originalName' => $options['originalName'] ?? $upload->fileName(),
                'contentType' => $options['contentType'] ?? null,
            ], static fn ($value) => $value !== null),
        ]);

        return new ImmediateResult($this->client, $response, 'reportDownloadUrl', 'reportKey');
    }

    public function ats(mixed $source, string $jobDescription, array $options = []): ImmediateResult
    {
        $upload = $this->client->normalizeUpload($source, $options);
        $response = $this->client->requestJson('POST', '/tools/ats/analyze', [
            'json' => array_filter([
                'fileId' => $upload->fileId(),
                'inputKey' => $upload->inputKey(),
                'originalName' => $options['originalName'] ?? $upload->fileName(),
                'contentType' => $options['contentType'] ?? null,
                'jobDescription' => $jobDescription,
                'industry' => $options['industry'] ?? null,
                'templateId' => $options['templateId'] ?? null,
            ], static fn ($value) => $value !== null),
        ]);

        return new ImmediateResult($this->client, $response, 'optimizedResumeDownloadUrl', 'optimizedResumeKey');
    }

    public function atsReexport(array $report, string $templateId, array $options = []): ImmediateResult
    {
        $response = $this->client->requestJson('POST', '/tools/ats/reexport', [
            'json' => array_filter([
                'report' => $report,
                'templateId' => $templateId,
                'fileId' => $options['fileId'] ?? null,
                'originalName' => $options['originalName'] ?? null,
            ], static fn ($value) => $value !== null),
        ]);

        return new ImmediateResult(
            $this->client,
            $response,
            'optimizedResumeDownloadUrl',
            null,
            $options['downloadAs'] ?? 'optimized-resume.docx'
        );
    }
}
