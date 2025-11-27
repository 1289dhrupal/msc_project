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

    // Getters
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

    // Setters
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function setService(string $service): void
    {
        $this->service = $service;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function setCreatedAt(?string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setLastFetchedAt(?string $lastFetchedAt): void
    {
        $this->lastFetchedAt = $lastFetchedAt;
    }

    // ToString method that returns JSON
    public function __toString(): string
    {
        return json_encode([
            'id' => $this->id,
            'user_id' => $this->userId,
            'token' => $this->token,
            'service' => $this->service,
            'url' => $this->url,
            'description' => $this->description,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt,
            'last_fetched_at' => $this->lastFetchedAt,
        ]);
    }
}
