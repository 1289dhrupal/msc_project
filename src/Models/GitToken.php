<?php

declare(strict_types=1);

namespace MscProject\Models;

class GitToken
{
    private ?int $id;
    private int $userId;
    private string $token;
    private string $service;
    private string $url;
    private string $description;
    private bool $isActive;
    private ?string $createdAt;
    private ?string $lastFetchedAt;

    public function __construct(?int $id, int $userId, string $token, string $service, string $url, string $description, bool $isActive, ?string $createdAt, ?string $lastFetchedAt)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->token = $token;
        $this->service = $service;
        $this->url = $url;
        $this->description = $description;
        $this->isActive = $isActive;
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

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isActive(): bool
    {
        return $this->isActive;
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
