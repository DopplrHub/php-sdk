<?php

declare(strict_types=1);

namespace DopplerHub;

final class UploadedFile
{
    public function __construct(private readonly array $payload)
    {
    }

    public static function fromResponse(array $payload): self
    {
        return new self($payload);
    }

    public function fileId(): string
    {
        return (string) ($this->payload['fileId'] ?? '');
    }

    public function inputKey(): string
    {
        return (string) ($this->payload['inputKey'] ?? '');
    }

    public function fileName(): string
    {
        return (string) ($this->payload['fileName'] ?? 'input.bin');
    }

    public function fileSize(): ?int
    {
        return isset($this->payload['fileSize']) ? (int) $this->payload['fileSize'] : null;
    }

    public function toArray(): array
    {
        return $this->payload;
    }
}
