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

    // Getters
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

    // Setters
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setRepositoryId(int $repositoryId): void
    {
        $this->repositoryId = $repositoryId;
    }

    public function setSha(string $sha): void
    {
        $this->sha = $sha;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function setDate(string $date): void
    {
        $this->date = $date;
    }

    public function setAuthor(string $author): void
    {
        $this->author = $author;
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

    public function setNumberOfCommentLines(int $numberOfCommentLines): void
    {
        $this->numberOfCommentLines = $numberOfCommentLines;
    }

    public function setCommitChangesQualityScore(int $commitChangesQualityScore): void
    {
        $this->commitChangesQualityScore = $commitChangesQualityScore;
    }

    public function setCommitMessageQualityScore(int $commitMessageQualityScore): void
    {
        $this->commitMessageQualityScore = $commitMessageQualityScore;
    }

    public function setFiles(array $files): void
    {
        $this->files = $files;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    // ToString method that returns JSON
    public function __toString(): string
    {
        return json_encode([
            'id' => $this->id,
            'repository_id' => $this->repositoryId,
            'sha' => $this->sha,
            'message' => $this->message,
            'date' => $this->date,
            'author' => $this->author,
            'additions' => $this->additions,
            'deletions' => $this->deletions,
            'total' => $this->total,
            'number_of_comment_lines' => $this->numberOfCommentLines,
            'commit_changes_quality_score' => $this->commitChangesQualityScore,
            'commit_message_quality_score' => $this->commitMessageQualityScore,
            'files' => $this->files,
            'created_at' => $this->createdAt
        ]);
    }
}
