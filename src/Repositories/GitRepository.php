<?php

namespace MscProject\Repositories;

use MscProject\Database;
use PDO;

class GitRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function storeRepository(int $gitTokenId, string $name, string $url, ?string $description, string $owner): int
    {
        if ($this->repositoryExists($gitTokenId, $owner, $name)) {
            // Handle already existing repository
            return $this->getRepositoryId($gitTokenId, $owner, $name);
        }

        $stmt = $this->db->prepare("INSERT INTO repositories (git_token_id, name, url, description, owner) VALUES (:git_token_id, :name, :url, :description, :owner)");
        $stmt->execute([
            ':git_token_id' => $gitTokenId,
            ':name' => $name,
            ':url' => $url,
            ':description' => $description,
            ':owner' => $owner
        ]);

        return (int)$this->db->lastInsertId();
    }

    private function repositoryExists(int $gitTokenId, string $owner, string $name): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM repositories WHERE git_token_id = :git_token_id AND owner = :owner AND name = :name");
        $stmt->execute([':git_token_id' => $gitTokenId, ':owner' => $owner, ':name' => $name]);
        return (bool)$stmt->fetchColumn();
    }

    private function getRepositoryId(int $gitTokenId, string $owner, string $name): int
    {
        $stmt = $this->db->prepare("SELECT id FROM repositories WHERE git_token_id = :git_token_id AND owner = :owner AND name = :name");
        $stmt->execute([':git_token_id' => $gitTokenId, ':owner' => $owner, ':name' => $name]);
        return (int)$stmt->fetchColumn();
    }

    public function storeCommit(int $repositoryId, string $sha, string $author, string $message, string $date): int
    {
        if ($this->commitExists($repositoryId, $sha)) {
            // Handle already existing commit
            return $this->getCommitId($repositoryId, $sha);
        }

        $stmt = $this->db->prepare("INSERT INTO commits (repository_id, sha, author, message, date) VALUES (:repository_id, :sha, :author, :message, :date)");
        $stmt->execute([
            ':repository_id' => $repositoryId,
            ':sha' => $sha,
            ':author' => $author,
            ':message' => $message,
            ':date' => $date
        ]);

        return (int)$this->db->lastInsertId();
    }

    private function commitExists(int $repositoryId, string $sha): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM commits WHERE repository_id = :repository_id AND sha = :sha");
        $stmt->execute([':repository_id' => $repositoryId, ':sha' => $sha]);
        return (bool)$stmt->fetchColumn();
    }

    private function getCommitId(int $repositoryId, string $sha): int
    {
        $stmt = $this->db->prepare("SELECT id FROM commits WHERE repository_id = :repository_id AND sha = :sha");
        $stmt->execute([':repository_id' => $repositoryId, ':sha' => $sha]);
        return (int)$stmt->fetchColumn();
    }

    public function storeCommitDetails(int $commitId, string $author, int $additions, int $deletions, int $total, string $files): int
    {
        if ($this->commitDetailsExists($commitId)) {
            // Handle already existing commit details
            return $this->getCommitDetailsId($commitId);
        }

        $stmt = $this->db->prepare("INSERT INTO commit_details (commit_id, author, additions, deletions, total, files) VALUES (:commit_id, :author, :additions, :deletions, :total, :files)");
        $stmt->execute([
            ':commit_id' => $commitId,
            ':author' => $author,
            ':additions' => $additions,
            ':deletions' => $deletions,
            ':total' => $total,
            ':files' => $files
        ]);

        return (int)$this->db->lastInsertId();
    }

    private function commitDetailsExists(int $commitId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM commit_details WHERE commit_id = :commit_id");
        $stmt->execute([':commit_id' => $commitId]);
        return (bool)$stmt->fetchColumn();
    }

    private function getCommitDetailsId(int $commitId): int
    {
        $stmt = $this->db->prepare("SELECT id FROM commit_details WHERE commit_id = :commit_id");
        $stmt->execute([':commit_id' => $commitId]);
        return (int)$stmt->fetchColumn();
    }

    public function updateRepositoryFetchedAt(int $repositoryId): void
    {
        $stmt = $this->db->prepare("UPDATE repositories SET last_fetched_at = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->execute([':id' => $repositoryId]);
    }
}
