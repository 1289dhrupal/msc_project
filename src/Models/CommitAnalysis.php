<?php

declare(strict_types=1);

namespace MscProject\Models;

class CommitAnalysis
{
    private int $commitId;
    private int $quality;
    private string $commitType;
    private string $files;

    public function __construct(int $commitId, int $quality, string $commitType, string $files)
    {
        $this->commitId = $commitId;
        $this->quality = $quality;
        $this->commitType = $commitType;
        $this->files = $files;
    }

    public function getCommitId(): int
    {
        return $this->commitId;
    }

    public function getQuality(): int
    {
        return $this->quality;
    }

    public function getCommitType(): string
    {
        return $this->commitType;
    }

    public function getFiles(): string
    {
        return $this->files;
    }
}
