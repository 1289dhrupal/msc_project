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
    private string $defaultBranch;
    private int $hookId;
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
        string $defaultBranch,
        int $hookId,
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
        $this->defaultBranch = $defaultBranch;
        $this->hookId = $hookId;
        $this->isActive = $isActive;
        $this->createdAt = $createdAt;
        $this->lastFetchedAt = $lastFetchedAt;
    }

    // Getters
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

    public function getDefaultBranch(): string
    {
        return $this->defaultBranch;
    }

    public function getHookId(): int
    {
        return $this->hookId;
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

    // Setters
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setGitTokenId(int $gitTokenId): void
    {
        $this->gitTokenId = $gitTokenId;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function setOwner(string $owner): void
    {
        $this->owner = $owner;
    }

    public function setDefaultBranch(string $defaultBranch): void
    {
        $this->defaultBranch = $defaultBranch;
    }

    public function setHookId(int $hookId): void
    {
        $this->hookId = $hookId;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function setCreatedAt(string $createdAt): void
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
            'git_token_id' => $this->gitTokenId,
            'name' => $this->name,
            'url' => $this->url,
            'description' => $this->description,
            'owner' => $this->owner,
            'default_branch' => $this->defaultBranch,
            'hook_id' => $this->hookId,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt,
            'last_fetched_at' => $this->lastFetchedAt
        ]);
    }
}
