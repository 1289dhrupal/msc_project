<?php

declare(strict_types=1);

namespace MscProject\Models;

class Session
{
    private int $userId;
    private string $apiKey;
    private string $createdAt;

    public function __construct(int $userId, string $apiKey, string $createdAt = null)
    {
        $this->userId = $userId;
        $this->apiKey = $apiKey;
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
    }

    // Getters
    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    // Setters
    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    // ToString method that returns JSON
    public function __toString(): string
    {
        return json_encode([
            'user_id' => $this->userId,
            'api_key' => $this->apiKey,
            'created_at' => $this->createdAt,
        ]);
    }
}
