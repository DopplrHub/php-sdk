<?php

declare(strict_types=1);

namespace DopplrHub;

use CURLFile;

final class DopplrHub
{
    private string $baseUrl;
    private ?ToolsClient $toolsClient = null;
    private ?UtilitiesClient $utilitiesClient = null;

    public function __construct(
        private readonly string $apiKey,
        ?string $baseUrl = null,
        private readonly int $timeoutSeconds = 120
    ) {
        if (!extension_loaded('curl')) {
            throw new ApiException('The cURL extension is required.');
        }

        $this->baseUrl = rtrim($baseUrl ?: 'https://api.dopplrhub.com/api/v1', '/');
    }

    public function tools(): ToolsClient
    {
        if ($this->toolsClient === null) {
            $this->toolsClient = new ToolsClient($this);
        }

        return $this->toolsClient;
    }

    public function utilities(): UtilitiesClient
    {
        if ($this->utilitiesClient === null) {
            $this->utilitiesClient = new UtilitiesClient($this);
        }

        return $this->utilitiesClient;
    }

    public function upload(string $filePath): UploadedFile
    {
        $resolved = realpath($filePath);
        if ($resolved === false || !is_file($resolved)) {
            throw new ApiException('Input file not found: ' . $filePath);
        }

        return $this->uploadFile($resolved);
    }

    public function importFromUrl(string $url, array $options = []): UploadedFile
    {
        $fileName = $options['fileName'] ?? $this->detectRemoteFileName($url, []);
        $payload = [
            'url' => $url,
            'fileName' => $fileName,
        ];

        if (isset($options['contentType'])) {
            $payload['contentType'] = $options['contentType'];
        }

        if (isset($options['authHeader'])) {
            $payload['authHeader'] = $options['authHeader'];
        }

        return UploadedFile::fromResponse($this->requestJson('POST', '/upload/from-url', [
            'json' => $payload,
        ]));
    }

    public function start(string $filePath, string $targetFormat, array $options = []): ConversionJob
    {
        return $this->convert($this->upload($filePath), $targetFormat, $options);
    }

    public function startFromContents(string $contents, string $fileName, string $targetFormat): ConversionJob
    {
        $tempPath = $this->createTemporaryFile($fileName, $contents);

        try {
            return $this->convert($this->uploadFile($tempPath, $fileName), $targetFormat, [
                'originalName' => $fileName,
            ]);
        } finally {
            @unlink($tempPath);
        }
    }

    public function startFromURL(string $url, string $targetFormat, array $options = []): ConversionJob
    {
        return $this->convert($this->importFromUrl($url, $options), $targetFormat, [
            'originalName' => $options['originalName'] ?? ($options['fileName'] ?? null),
            'mediaType' => $options['mediaType'] ?? null,
            'conversionSettings' => $options['conversionSettings'] ?? null,
        ]);
    }

    public function convert(mixed $source, string $targetFormat, array $options = []): ConversionJob
    {
        $upload = $this->normalizeUpload($source, $options);

        return $this->submitJob('/convert', [
            'fileId' => $upload->fileId(),
            'inputKey' => $upload->inputKey(),
            'targetFormat' => $targetFormat,
            'originalName' => $options['originalName'] ?? $upload->fileName(),
            'mediaType' => $options['mediaType'] ?? null,
            'conversionSettings' => $options['conversionSettings'] ?? null,
        ]);
    }

    public function getJob(string $jobId): array
    {
        return $this->requestJson('GET', '/jobs/' . rawurlencode($jobId));
    }

    public function deleteJob(string $jobId): void
    {
        $this->requestJson('DELETE', '/jobs/' . rawurlencode($jobId));
    }

    public function downloadFile(string $url, string $targetPath): void
    {
        $directory = dirname($targetPath);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new ApiException('Could not create output directory: ' . $directory);
        }

        $stream = fopen($targetPath, 'wb');
        if ($stream === false) {
            throw new ApiException('Could not open output file: ' . $targetPath);
        }

        $ch = curl_init($url);
        if ($ch === false) {
            fclose($stream);
            throw new ApiException('Could not initialize download request.');
        }

        curl_setopt_array($ch, [
            CURLOPT_FILE => $stream,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => max($this->timeoutSeconds, 60),
            CURLOPT_FAILONERROR => false,
            CURLOPT_USERAGENT => 'dopplrhub-php-sdk/0.2.0',
        ]);

        $result = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        fclose($stream);

        if ($result === false || $errno !== 0) {
            @unlink($targetPath);
            throw new ApiException('Download failed: ' . $error);
        }

        if ($status >= 400) {
            @unlink($targetPath);
            throw new ApiException('Download failed with HTTP ' . $status . '.');
        }
    }

    public function guessExtension(string $targetFormat): string
    {
        $normalized = strtolower(trim($targetFormat));
        $parts = explode('-', $normalized);
        return end($parts) ?: $normalized;
    }

    public function extensionFromPayload(array $payload): string
    {
        $outputKey = (string) ($payload['outputKey'] ?? $payload['reportKey'] ?? $payload['optimizedResumeKey'] ?? '');
        if ($outputKey !== '') {
            $extension = pathinfo($outputKey, PATHINFO_EXTENSION);
            if ($extension !== '') {
                return strtolower($extension);
            }
        }

        return $this->guessExtension((string) ($payload['targetFormat'] ?? 'bin'));
    }

    public function normalizeUpload(mixed $source, array $options = []): UploadedFile
    {
        if ($source instanceof UploadedFile) {
            return $source;
        }

        if (is_array($source) && isset($source['fileId'], $source['inputKey'])) {
            return UploadedFile::fromResponse($source);
        }

        if (is_string($source) && preg_match('/^https?:\/\//i', $source) === 1) {
            return $this->importFromUrl($source, $options);
        }

        if (is_string($source)) {
            return $this->upload($source);
        }

        throw new ApiException('Source must be a local file path, remote URL, UploadedFile, or upload response array.');
    }

    public function normalizeUploads(array $sources, array $options = []): array
    {
        if ($sources === []) {
            throw new ApiException('At least one source is required.');
        }

        $uploads = [];
        foreach ($sources as $index => $source) {
            $uploads[] = $this->normalizeUpload($source, $options[$index] ?? $options);
        }

        return $uploads;
    }

    public function submitJob(string $endpoint, array $payload): ConversionJob
    {
        $filteredPayload = array_filter($payload, static fn ($value) => $value !== null);
        $job = $this->requestJson('POST', $endpoint, [
            'json' => $filteredPayload,
        ]);

        if (!isset($job['originalName']) && isset($filteredPayload['originalName'])) {
            $job['originalName'] = $filteredPayload['originalName'];
        }

        return new ConversionJob($this, $job);
    }

    public function requestJson(string $method, string $path, array $options = []): array
    {
        $options['headers'] = array_merge(['Accept: application/json'], $options['headers'] ?? []);
        $response = $this->request($method, $path, $options);
        $decoded = json_decode($response['body'], true);

        if (!is_array($decoded)) {
            throw new ApiException(sprintf(
                'Expected JSON response for %s %s, got: %s',
                $method,
                $path,
                trim($response['body']) === '' ? '[empty body]' : trim($response['body'])
            ));
        }

        if ($response['status'] >= 400) {
            $message = is_string($decoded['error'] ?? null) ? $decoded['error'] : ('HTTP ' . $response['status']);
            throw new ApiException($message);
        }

        return $decoded;
    }

    public function request(string $method, string $path, array $options = []): array
    {
        $url = ($options['absoluteUrl'] ?? false) ? $path : $this->baseUrl . '/' . ltrim($path, '/');
        $headers = $options['headers'] ?? [];
        if (($options['includeApiKey'] ?? true) === true) {
            $headers[] = 'x-api-key: ' . $this->apiKey;
        }

        $ch = curl_init($url);
        if ($ch === false) {
            throw new ApiException('Could not initialize request.');
        }

        $responseHeaders = [];
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADERFUNCTION => static function ($curl, string $headerLine) use (&$responseHeaders): int {
                $length = strlen($headerLine);
                $parts = explode(':', $headerLine, 2);
                if (count($parts) === 2) {
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return $length;
            },
            CURLOPT_USERAGENT => 'dopplrhub-php-sdk/0.2.0',
        ]);

        if (isset($options['json'])) {
            $payload = json_encode($options['json'], JSON_THROW_ON_ERROR);
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        if (isset($options['multipart'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $options['multipart']);
        }

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($body === false || $errno !== 0) {
            throw new ApiException('Request failed: ' . $error);
        }

        return [
            'status' => $status,
            'headers' => $responseHeaders,
            'body' => $body,
        ];
    }

    private function uploadFile(string $filePath, ?string $fileName = null): UploadedFile
    {
        $uploadName = $fileName ?: basename($filePath);
        $mimeType = function_exists('mime_content_type')
            ? (mime_content_type($filePath) ?: 'application/octet-stream')
            : 'application/octet-stream';

        return UploadedFile::fromResponse($this->requestJson('POST', '/upload', [
            'multipart' => [
                'file' => new CURLFile($filePath, $mimeType, $uploadName),
            ],
        ]));
    }

    private function createTemporaryFile(string $fileName, string $contents): string
    {
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($fileName)) ?: 'upload.bin';
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('dopplrhub_', true) . '_' . $safeName;
        if (file_put_contents($path, $contents) === false) {
            throw new ApiException('Could not create temporary upload file.');
        }

        return $path;
    }

    private function detectRemoteFileName(string $url, array $headers): string
    {
        $disposition = $headers['content-disposition'] ?? '';
        if (preg_match('/filename\*=UTF-8\'\'([^;]+)/i', $disposition, $matches) === 1) {
            return rawurldecode(trim($matches[1], '"'));
        }

        if (preg_match('/filename="?([^";]+)"?/i', $disposition, $matches) === 1) {
            return trim($matches[1]);
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        $name = basename($path);
        if ($name !== '' && $name !== '/' && $name !== '.') {
            return $name;
        }

        return 'remote-input.bin';
    }
}
