<?php

declare(strict_types=1);

namespace MscProject\Models;

class CommitAnalysis
{
    private int $commitId;
    private int $quality;
    private string $commitType;

    public function __construct(int $commitId, int $quality, string $commitType)
    {
        $this->commitId = $commitId;
        $this->quality = $quality;
        $this->commitType = $commitType;
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
}
