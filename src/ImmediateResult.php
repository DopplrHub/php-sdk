<?php

declare(strict_types=1);

namespace DopplrHub;

final class ImmediateResult
{
    public function __construct(
        private readonly DopplrHub $client,
        private readonly array $payload,
        private readonly string $downloadUrlField,
        private readonly ?string $downloadKeyField = null,
        private readonly ?string $defaultFileName = null
    ) {
    }

    public function toArray(): array
    {
        return $this->payload;
    }

    public function download(?string $targetPath = null): self
    {
        $downloadUrl = (string) ($this->payload[$this->downloadUrlField] ?? '');
        if ($downloadUrl === '') {
            throw new ApiException('Response did not include a download URL.');
        }

        $this->client->downloadFile($downloadUrl, $targetPath ?: $this->defaultDownloadPath());
        return $this;
    }

    private function defaultDownloadPath(): string
    {
        $downloadKey = $this->downloadKeyField ? (string) ($this->payload[$this->downloadKeyField] ?? '') : '';
        if ($downloadKey !== '') {
            return '.' . DIRECTORY_SEPARATOR . basename($downloadKey);
        }

        if ($this->defaultFileName) {
            return '.' . DIRECTORY_SEPARATOR . $this->defaultFileName;
        }

        $originalName = (string) ($this->payload['originalName'] ?? 'download');
        $baseName = pathinfo($originalName, PATHINFO_FILENAME) ?: 'download';
        return '.' . DIRECTORY_SEPARATOR . $baseName . '.bin';
    }
}
