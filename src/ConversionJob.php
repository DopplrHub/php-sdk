<?php

declare(strict_types=1);

namespace DopplrHub;

final class ConversionJob
{
    public function __construct(
        private readonly DopplrHub $client,
        private array $payload
    ) {
    }

    public function id(): string
    {
        return (string) ($this->payload['jobId'] ?? '');
    }

    public function state(): string
    {
        return (string) ($this->payload['state'] ?? $this->payload['status'] ?? 'queued');
    }

    public function wait(?int $timeoutSeconds = null, int $pollSeconds = 2): self
    {
        $deadline = time() + max($timeoutSeconds ?? 900, 1);

        while (time() <= $deadline) {
            $this->refresh();
            $state = strtolower($this->state());

            if ($state === 'completed') {
                return $this;
            }

            if ($state === 'failed') {
                $reason = (string) ($this->payload['failedReason'] ?? 'Conversion failed.');
                throw new ApiException($reason);
            }

            sleep(max($pollSeconds, 1));
        }

        throw new ApiException('Timed out waiting for conversion job ' . $this->id());
    }

    public function refresh(): self
    {
        $current = $this->client->getJob($this->id());
        $this->payload = array_merge($this->payload, $current);
        return $this;
    }

    public function download(?string $targetPath = null): self
    {
        if ($this->state() !== 'completed') {
            $this->refresh();
        }

        if ($this->state() !== 'completed') {
            throw new ApiException('Job ' . $this->id() . ' is not completed.');
        }

        $downloadUrl = (string) ($this->payload['downloadUrl'] ?? '');
        if ($downloadUrl === '') {
            throw new ApiException('Completed job did not include a downloadUrl.');
        }

        $this->client->downloadFile($downloadUrl, $targetPath ?: $this->defaultDownloadPath());
        return $this;
    }

    public function delete(): self
    {
        $this->client->deleteJob($this->id());
        return $this;
    }

    public function toArray(): array
    {
        return $this->payload;
    }

    private function defaultDownloadPath(): string
    {
        $outputKey = (string) ($this->payload['outputKey'] ?? '');
        if ($outputKey !== '') {
            return '.' . DIRECTORY_SEPARATOR . basename($outputKey);
        }

        $originalName = (string) ($this->payload['originalName'] ?? 'output');
        $baseName = pathinfo($originalName, PATHINFO_FILENAME) ?: 'output';
        $extension = $this->client->extensionFromPayload($this->payload);
        return '.' . DIRECTORY_SEPARATOR . $baseName . '.' . $extension;
    }
}
