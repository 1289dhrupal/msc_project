<?php

declare(strict_types=1);

namespace MscProject\Models;

class CommitFile
{

    private int $commitId;
    private string $sha;
    private string $status;
    private int $additions;
    private int $deletions;
    private int $total;
    private string $filename;
    private string $extension;

    public function __construct(
        ?int $commitId,
        string $sha,
        string $status,
        int $additions,
        int $deletions,
        int $total,
        string $filename,
        string $extension = null
    ) {
        $this->commitId = $commitId;
        $this->sha = $sha;
        $this->status = $status;
        $this->additions = $additions;
        $this->deletions = $deletions;
        $this->total = $total;
        $this->filename = $filename;
        $this->extension = $extension;
    }

    public function getCommitId(): int
    {
        return $this->commitId;
    }

    public function getSha(): string
    {
        return $this->sha;
    }

    public function getStatus(): string
    {
        return $this->status;
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

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function setCommitId(int $commitId): void
    {
        $this->commitId = $commitId;
    }

    public function setSha(string $sha): void
    {
        $this->sha = $sha;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function setAdditions(int $additions): void
    {
        $this->additions = $additions;
    }

    public function setDeletions(int $deletions): void
    {
        $this->deletions = $deletions;
    }

    public function setTotal(int $total): void
    {
        $this->total = $total;
    }

    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    public function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }

    // ToString method that returns JSON
    public function __toString(): string
    {
        return json_encode([
            'commit_id' => $this->commitId,
            'sha' => $this->sha,
            'status' => $this->status,
            'additions' => $this->additions,
            'deletions' => $this->deletions,
            'total' => $this->total,
            'filename' => $this->filename,
            'extension' => $this->extension,
        ]);
    }
}
