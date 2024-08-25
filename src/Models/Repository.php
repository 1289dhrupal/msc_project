<?php

declare(strict_types=1);

namespace MscProject\Models;

class Repository
{
    private int $id;
    private int $gitTokenId;
    private string $name;
    private string $url;
    private ?string $description;
    private string $owner;
    private bool $isActive;
    private string $createdAt;
    private ?string $lastFetchedAt;

    public function __construct(
        int $id,
        int $gitTokenId,
        string $name,
        string $url,
        ?string $description,
        string $owner,
        bool $isActive,
        string $createdAt,
        ?string $lastFetchedAt
    ) {
        $this->id = $id;
        $this->gitTokenId = $gitTokenId;
        $this->name = $name;
        $this->url = $url;
        $this->description = $description;
        $this->owner = $owner;
        $this->isActive = $isActive;
        $this->createdAt = $createdAt;
        $this->lastFetchedAt = $lastFetchedAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getGitTokenId(): int
    {
        return $this->gitTokenId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getOwner(): string
    {
        return $this->owner;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getLastFetchedAt(): ?string
    {
        return $this->lastFetchedAt;
    }
}
