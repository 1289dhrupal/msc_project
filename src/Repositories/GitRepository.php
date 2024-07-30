<?php

namespace MscProject\Repositories;

use MscProject\Database;
use MscProject\Models\GitToken;
use MscProject\Models\Repository;
use MscProject\Models\Commit;
use MscProject\Models\CommitAnalysis;
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
        $stmt = $this->db->prepare("INSERT INTO repositories (git_token_id, name, url, description, owner) VALUES (:git_token_id, :name, :url, :description, :owner)");
        $stmt->bindParam(':git_token_id', $gitTokenId, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':url', $url, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':owner', $owner, PDO::PARAM_STR);
        $stmt->execute();

        return (int)$this->db->lastInsertId();
    }

    public function getRepository(int $gitTokenId, string $owner, string $name): ?Repository
    {
        $stmt = $this->db->prepare("SELECT * FROM repositories WHERE git_token_id = :git_token_id AND owner = :owner AND name = :name");
        $stmt->bindParam(':git_token_id', $gitTokenId, PDO::PARAM_INT);
        $stmt->bindParam(':owner', $owner, PDO::PARAM_STR);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return new Repository(
                (int) $result['id'],
                (int) $result['git_token_id'],
                $result['name'],
                $result['url'],
                $result['description'],
                $result['owner'],
                $result['created_at'],
                $result['last_fetched_at']
            );
        }

        return null;
    }

    public function storeCommit(
        int $repositoryId,
        string $sha,
        string $author,
        string $message,
        string $date,
        int $additions,
        int $deletions,
        int $total,
        string $files
    ): int {
        $stmt = $this->db->prepare("INSERT INTO commits (repository_id, sha, author, message, date, additions, deletions, total, files) VALUES (:repository_id, :sha, :author, :message, :date, :additions, :deletions, :total, :files)");
        $stmt->bindParam(':repository_id', $repositoryId, PDO::PARAM_INT);
        $stmt->bindParam(':sha', $sha, PDO::PARAM_STR);
        $stmt->bindParam(':author', $author, PDO::PARAM_STR);
        $stmt->bindParam(':message', $message, PDO::PARAM_STR);
        $stmt->bindParam(':date', $date, PDO::PARAM_STR);
        $stmt->bindParam(':additions', $additions, PDO::PARAM_INT);
        $stmt->bindParam(':deletions', $deletions, PDO::PARAM_INT);
        $stmt->bindParam(':total', $total, PDO::PARAM_INT);
        $stmt->bindParam(':files', $files, PDO::PARAM_STR);
        $stmt->execute();

        return (int)$this->db->lastInsertId();
    }

    public function getCommit(int $repositoryId, string $sha): ?Commit
    {
        $stmt = $this->db->prepare("SELECT * FROM commits WHERE repository_id = :repository_id AND sha = :sha");
        $stmt->bindParam(':repository_id', $repositoryId, PDO::PARAM_INT);
        $stmt->bindParam(':sha', $sha, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return new Commit(
                (int) $result['id'],
                (int) $result['repository_id'],
                $result['sha'],
                $result['message'],
                $result['date'],
                $result['author'],
                (int) $result['additions'],
                (int) $result['deletions'],
                (int) $result['total'],
                $result['files']
            );
        }

        return null;
    }

    public function getCommitById(int $commitId): ?Commit
    {
        $stmt = $this->db->prepare("SELECT * FROM commits WHERE id = :commit_id");
        $stmt->bindParam(':commit_id', $commitId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return new Commit(
                (int) $result['id'],
                (int) $result['repository_id'],
                $result['sha'],
                $result['message'],
                $result['date'],
                $result['author'],
                (int) $result['additions'],
                (int) $result['deletions'],
                (int) $result['total'],
                $result['files']
            );
        }

        return null;
    }

    public function updateRepositoryFetchedAt(int $repositoryId): void
    {
        $stmt = $this->db->prepare("UPDATE repositories SET last_fetched_at = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->bindParam(':id', $repositoryId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function getTokenByToken(string $token): ?GitToken
    {
        $stmt = $this->db->prepare("SELECT * FROM git_tokens WHERE token = :token");
        $stmt->bindParam(':token', $token, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return null;
        }

        return new GitToken($result['id'], $result['user_id'], $result['token'], $result['service']);
    }

    public function create(GitToken $gitToken): bool
    {
        $stmt = $this->db->prepare("INSERT INTO git_tokens (user_id, token, service) VALUES (:user_id, :token, :service)");
        $stmt->bindParam(':user_id', $gitToken->getUserId(), PDO::PARAM_INT);
        $stmt->bindParam(':token', $gitToken->getToken(), PDO::PARAM_STR);
        $stmt->bindParam(':service', $gitToken->getService(), PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * @param int $userId
     * @return GitToken[]
     */
    public function getTokensByUserId(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM git_tokens WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tokens = [];
        foreach ($results as $result) {
            $tokens[] = new GitToken($result['id'], $result['user_id'], $result['token'], $result['service']);
        }

        return $tokens;
    }

    /**
     * @return GitToken[]
     */
    public function fetchAll(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM git_tokens");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tokens = [];
        foreach ($results as $result) {
            $tokens[] = new GitToken($result['id'], $result['user_id'], $result['token'], $result['service']);
        }

        return $tokens;
    }

    public function storeCommitAnalysis(CommitAnalysis $result): void
    {
        $commitId = $result->getCommitId();
        $quality = $result->getQuality();
        $commitType = $result->getCommitType();
        $files = $result->getFiles();

        $stmt = $this->db->prepare("INSERT INTO commit_analysis (commit_id, quality, commit_type, files) VALUES (:commit_id, :quality, :commit_type, :files)");
        $stmt->bindParam(':commit_id', $commitId, PDO::PARAM_INT);
        $stmt->bindParam(':quality', $quality, PDO::PARAM_INT);
        $stmt->bindParam(':commit_type', $commitType, PDO::PARAM_STR);
        $stmt->bindParam(':files', $files, PDO::PARAM_STR);
        $stmt->execute();
    }

    public function getAllCommitsWithDetails(): array
    {
        $stmt = $this->db->prepare("
            SELECT c.id, c.message, c.files AS diffs
            FROM commits c
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
