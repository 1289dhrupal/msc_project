<?php

declare(strict_types=1);

namespace MscProject\Models;

class GitToken
{
    private ?int $id;
    private int $userId;
    private string $token;
    private string $service;

    public function __construct(?int $id, int $userId, string $token, string $service)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->token = $token;
        $this->service = $service;
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
}
