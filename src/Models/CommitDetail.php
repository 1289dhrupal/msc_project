<?php

declare(strict_types=1);

namespace MscProject\Models;

class CommitDetail
{
    private int $id;
    private int $commitId;
    private string $author;
    private int $additions;
    private int $deletions;
    private int $total;
    private string $files;

    public function __construct(
        int $id,
        int $commitId,
        string $author,
        int $additions,
        int $deletions,
        int $total,
        string $files
    ) {
        $this->id = $id;
        $this->commitId = $commitId;
        $this->author = $author;
        $this->additions = $additions;
        $this->deletions = $deletions;
        $this->total = $total;
        $this->files = $files;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCommitId(): int
    {
        return $this->commitId;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getAdditions(): int
    {
        return $this->additions;
    }

    public function getDeletions(): int
    {
        return $this->deletions;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getFiles(): string
    {
        return $this->files;
    }
}
