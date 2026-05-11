<?php

declare(strict_types=1);

namespace DopplrHub;

final class UtilitiesClient
{
    public function __construct(private readonly DopplrHub $client)
    {
    }

    public function supportedFormats(): array
    {
        return $this->client->requestJson('GET', '/upload/formats');
    }

    public function currencyRates(string $base = 'USD'): array
    {
        return $this->client->requestJson('GET', '/tools/units/currency-rates?base=' . rawurlencode(strtoupper($base)));
    }

    public function batchDownload(array $jobIds, string $targetPath): string
    {
        if ($jobIds === []) {
            throw new ApiException('jobIds must be a non-empty array.');
        }

        $response = $this->client->request('POST', '/jobs/batch-download', [
            'json' => ['jobIds' => array_values($jobIds)],
            'headers' => ['Accept: application/zip'],
        ]);

        if (($response['status'] ?? 500) >= 400) {
            $decoded = json_decode($response['body'], true);
            $message = is_array($decoded) && is_string($decoded['error'] ?? null)
                ? $decoded['error']
                : ('HTTP ' . (int) $response['status']);
            throw new ApiException($message);
        }

        $directory = dirname($targetPath);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new ApiException('Could not create output directory: ' . $directory);
        }

        if (file_put_contents($targetPath, $response['body']) === false) {
            throw new ApiException('Could not write archive to ' . $targetPath);
        }

        return $targetPath;
    }
}
