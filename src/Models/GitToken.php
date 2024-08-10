<?php

declare(strict_types=1);

namespace MscProject\Models;

class GitToken
{
    private ?int $id;
    private int $userId;
    private string $token;
    private string $service;
    private bool $isDisabled;
    private ?string $createdAt;
    private ?string $lastFetchedAt;

    public function __construct(?int $id, int $userId, string $token, string $service, bool $isDisabled, ?string $createdAt, ?string $lastFetchedAt)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->token = $token;
        $this->service = $service;
        $this->isDisabled = $isDisabled;
        $this->createdAt = $createdAt;
        $this->lastFetchedAt = $lastFetchedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getService(): string
    {
        return $this->service;
    }

    public function isDisabled(): bool
    {
        return $this->isDisabled;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getLastFetchedAt(): ?string
    {
        return $this->lastFetchedAt;
    }
}
