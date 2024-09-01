<?php

declare(strict_types=1);

namespace MscProject\Models;

use MscProject\Models\CommitFile;

class Commit
{
    private int $id;
    private int $repositoryId;
    private string $sha;
    private string $message;
    private string $date;
    private string $author;
    private int $additions;
    private int $deletions;
    private int $total;
    private int $numberOfCommentLines;
    private int $commitChangesQualityScore;
    private int $commitMessageQualityScore;
    /**
     * @var CommitFile[]
     */
    private array $files;
    private string $createdAt;

    public function __construct(
        int $id,
        int $repositoryId,
        string $sha,
        string $message,
        string $date,
        string $author,
        int $additions,
        int $deletions,
        int $total,
        int $numberOfCommentLines,
        int $commitChangesQualityScore,
        int $commitMessageQualityScore,
        /**
         * @var CommitFile[]
         */
        array $files
    ) {
        $this->id = $id;
        $this->repositoryId = $repositoryId;
        $this->sha = $sha;
        $this->message = $message;
        $this->date = $date;
        $this->author = $author;
        $this->additions = $additions;
        $this->deletions = $deletions;
        $this->total = $total;
        $this->numberOfCommentLines = $numberOfCommentLines;
        $this->commitChangesQualityScore = $commitChangesQualityScore;
        $this->commitMessageQualityScore = $commitMessageQualityScore;
        $this->files = $files;
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

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getDate(): string
    {
        return $this->date;
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

    public function getNumberOfCommentLines(): int
    {
        return $this->numberOfCommentLines;
    }

    public function getCommitChangesQualityScore(): int
    {
        return $this->commitChangesQualityScore;
    }

    public function getCommitMessageQualityScore(): int
    {
        return $this->commitMessageQualityScore;
    }

    /**
     * @return CommitFile[]
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }
}
