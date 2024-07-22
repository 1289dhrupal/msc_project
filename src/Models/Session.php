<?php

declare(strict_types=1);

namespace MscProject\Models;

class Session
{
    public int $userId;
    public string $apiKey;
    public string $createdAt;

    public function __construct(int $userId, string $apiKey, string $createdAt = null)
    {
        $this->userId = $userId;
        $this->apiKey = $apiKey;
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
    }
}
