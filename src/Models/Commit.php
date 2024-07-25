<?php

declare(strict_types=1);

namespace MscProject\Models;

class Commit
{
    private int $id;
    private int $repositoryId;
    private string $sha;
    private ?string $author;
    private string $message;
    private string $date;

    public function __construct(
        int $id,
        int $repositoryId,
        string $sha,
        ?string $author,
        string $message,
        string $date
    ) {
        $this->id = $id;
        $this->repositoryId = $repositoryId;
        $this->sha = $sha;
        $this->author = $author;
        $this->message = $message;
        $this->date = $date;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getRepositoryId(): int
    {
        return $this->repositoryId;
    }

    public function getSha(): string
    {
        return $this->sha;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getDate(): string
    {
        return $this->date;
    }
}
