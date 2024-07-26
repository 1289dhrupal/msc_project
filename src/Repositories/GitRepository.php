<?php

declare(strict_types=1);

namespace MscProject\Repositories;

use MscProject\Database;
use MscProject\Models\GitToken;
use MscProject\Models\Repository;
use MscProject\Models\Commit;
use MscProject\Models\CommitDetail;
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
        $repository = $this->getRepository($gitTokenId, $owner, $name);
        if ($repository) {
            return $repository->getId();
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

    public function getRepository(int $gitTokenId, string $owner, string $name): ?Repository
    {
        $stmt = $this->db->prepare("SELECT * FROM repositories WHERE git_token_id = :git_token_id AND owner = :owner AND name = :name");
        $stmt->execute([':git_token_id' => $gitTokenId, ':owner' => $owner, ':name' => $name]);
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

    public function storeCommit(int $repositoryId, string $sha, string $author, string $message, string $date): int
    {
        $commit = $this->getCommit($repositoryId, $sha);
        if ($commit) {
            return $commit->getId();
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

    public function getCommit(int $repositoryId, string $sha): ?Commit
    {
        $stmt = $this->db->prepare("SELECT * FROM commits WHERE repository_id = :repository_id AND sha = :sha");
        $stmt->execute([':repository_id' => $repositoryId, ':sha' => $sha]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return new Commit(
                (int) $result['id'],
                (int) $result['repository_id'],
                $result['sha'],
                $result['author'],
                $result['message'],
                $result['date']
            );
        }

        return null;
    }

    public function getCommitById(int $commitId): ?Commit
    {
        $stmt = $this->db->prepare("SELECT * FROM commits WHERE id = :commit_id");
        $stmt->execute([':commit_id' => $commitId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return new Commit(
                (int) $result['id'],
                (int) $result['repository_id'],
                $result['sha'],
                $result['author'],
                $result['message'],
                $result['date']
            );
        }

        return null;
    }

    public function storeCommitDetails(int $commitId, string $author, int $additions, int $deletions, int $total, string $files): int
    {
        $commitDetails = $this->getCommitDetails($commitId);
        if ($commitDetails) {
            return $commitDetails->getId();
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

    public function getCommitDetails(int $commitId): ?CommitDetail
    {
        $stmt = $this->db->prepare("SELECT * FROM commit_details WHERE commit_id = :commit_id");
        $stmt->execute([':commit_id' => $commitId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return new CommitDetail(
                (int) $result['id'],
                (int) $result['commit_id'],
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
        $stmt->execute([':id' => $repositoryId]);
    }

    public function getTokenByToken(string $token): ?GitToken
    {
        $stmt = $this->db->prepare("SELECT * FROM git_tokens WHERE token = :token");
        $stmt->bindParam(':token', $token);
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
        $stmt->execute([
            ':user_id' => $gitToken->getUserId(),
            ':token' => $gitToken->getToken(),
            ':service' => $gitToken->getService()
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * @param int $userId
     * @return GitToken[]
     */
    public function getTokensByUserId(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM git_tokens WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId);
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
        $stmt = $this->db->prepare("INSERT INTO commit_analysis (commit_id, quality, commit_type) VALUES (:commit_id, :quality, :commit_type)");
        $stmt->execute([
            ':commit_id' => $result->getCommitId(),
            ':quality' => $result->getQuality(),
            ':commit_type' => $result->getCommitType()
        ]);
    }

    public function getAllCommitsWithDetails(): array
    {
        $stmt = $this->db->prepare("
            SELECT c.id, c.message, cd.files AS diffs
            FROM commits c
            JOIN commit_details cd ON c.id = cd.commit_id
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
