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
                (bool) $result['is_disabled'],
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

        return new GitToken($result['id'], $result['user_id'], $result['token'], $result['service'], $result['is_disabled'], $result['created_at'], $result['last_fetched_at']);
    }

    public function create(GitToken $gitToken): bool
    {
        $userID = $gitToken->getUserId();
        $token = $gitToken->getToken();
        $service = $gitToken->getService();
        $stmt = $this->db->prepare("INSERT INTO git_tokens (user_id, token, service) VALUES (:user_id, :token, :service)");
        $stmt->bindParam(':user_id', $userID, PDO::PARAM_INT);
        $stmt->bindParam(':token', $token, PDO::PARAM_STR);
        $stmt->bindParam(':service', $service, PDO::PARAM_STR);
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
            $tokens[] = new GitToken($result['id'], $result['user_id'], $result['token'], $result['service'], $result['is_disabled'], $result['created_at'], $result['last_fetched_at']);
        }

        return $tokens;
    }

    /**
     * @return GitToken[]
     */
    public function listTokens(int $userId = 0, string $gitTokenIds = ""): array
    {

        $sql = "SELECT * FROM git_tokens WHERE 1";
        if ($userId != 0) {
            $sql .= " AND user_id = :user_id";
        }

        if ($gitTokenIds != '') {
            $sql .= " AND FIND_IN_SET(id, :ids)";
        }

        $stmt = $this->db->prepare($sql);
        if ($userId != 0) {
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        }
        if ($gitTokenIds != '') {
            $stmt->bindParam(':ids', $gitTokenIds, PDO::PARAM_STR);
        }

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tokens = [];
        foreach ($results as $result) {
            $tokens[] = new GitToken($result['id'], $result['user_id'], $result['token'], $result['service'], $result['is_disabled'], $result['created_at'], $result['last_fetched_at']);
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

    public function toggleToken(int $tokenId, bool $isDisabled, int $userId = 0): int
    {
        $sql = "UPDATE git_tokens SET is_disabled = :is_disabled WHERE id = :id";
        if ($userId != 0) {
            $sql .= " AND user_id = :user_id";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':is_disabled', $isDisabled, PDO::PARAM_BOOL);
        $stmt->bindParam(':id', $tokenId, PDO::PARAM_INT);
        if ($userId != 0) {
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function getToken(int $tokenId, int $userId = 0): ?GitToken
    {
        $sql = "SELECT * FROM git_tokens WHERE id = :id";
        if ($userId != 0) {
            $sql .= " AND user_id = :user_id";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $tokenId, PDO::PARAM_INT);
        if ($userId != 0) {
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return new GitToken($result['id'], $result['user_id'], $result['token'], $result['service'], $result['is_disabled'], $result['created_at'], $result['last_fetched_at']);
        }

        return null;
    }

    public function deleteRepositoriesByTokenId(int $tokenId): int
    {
        $sql = "DELETE FROM repositories WHERE git_token_id = :git_token_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':git_token_id', $tokenId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function deleteToken(int $tokenId, int $userId = 0): int
    {
        $sql = "DELETE FROM git_tokens WHERE id = :id";
        if ($userId != 0) {
            $sql .= " AND user_id = :user_id";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $tokenId, PDO::PARAM_INT);
        if ($userId != 0) {
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * @param int $userId
     * @return Repository[]
     */
    public function listRepositories(int $userId = 0, int $gitTokenId = 0, string $repoIds = ""): array
    {
        $sql = "SELECT r.*, token AS git_token FROM repositories AS r, git_tokens AS gt WHERE r.git_token_id = gt.id";
        if ($gitTokenId != 0) {
            $sql .= " AND git_token_id = :git_token_id";
        }
        if ($repoIds != '') {
            $sql .= " AND FIND_IN_SET(r.id, :ids)";
        }
        if ($userId != 0) {
            $sql .= " AND user_id = :user_id";
        }

        $stmt = $this->db->prepare($sql);
        if ($gitTokenId != 0) {
            $stmt->bindParam(':git_token_id', $gitTokenId, PDO::PARAM_INT);
        }
        if ($repoIds != '') {
            $stmt->bindParam(':ids', $repoIds, PDO::PARAM_STR);
        }
        if ($userId != 0) {
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        }

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $repositories = [];
        foreach ($results as $result) {
            $repositories[] = new Repository(
                (int) $result['id'],
                (int) $result['git_token_id'],
                $result['name'],
                $result['url'],
                $result['description'],
                $result['owner'],
                (bool) $result['is_disabled'],
                $result['created_at'],
                $result['last_fetched_at']
            );
        }

        return $repositories;
    }

    public function getRepositoryById(int $repoId = 0, int $userId = 0): Repository
    {
        $sql = "SELECT r.*, token AS git_token FROM repositories AS r, git_tokens AS gt WHERE r.git_token_id = gt.id";
        if ($userId != 0) {
            $sql .= " AND user_id = :user_id";
        }
        if ($repoId != 0) {
            $sql .= " AND r.id = :id";
        }
        $stmt = $this->db->prepare($sql);
        if ($repoId != 0) {
            $stmt->bindParam(':id', $repoId, PDO::PARAM_INT);
        }
        if ($userId != 0) {
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return new Repository(
            (int) $result['id'],
            (int) $result['git_token_id'],
            $result['name'],
            $result['url'],
            $result['description'],
            $result['owner'],
            (bool) $result['is_disabled'],
            $result['created_at'],
            $result['last_fetched_at']
        );
    }
    public function toggleRepository(int $repoId, bool $isDisabled, int $userId = 0): int
    {
        $sql = "UPDATE repositories SET is_disabled = :is_disabled WHERE id = :id";
        if ($userId != 0) {
            $sql .= " AND (SELECT user_id FROM git_tokens WHERE id = git_token_id) = :user_id";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':is_disabled', $isDisabled, PDO::PARAM_BOOL);
        $stmt->bindParam(':id', $repoId, PDO::PARAM_INT);
        if ($userId != 0) {
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function deleteRepository(int $repoId, int $userId = 0): int
    {
        $sql = "DELETE FROM repositories WHERE id = :id";
        if ($userId != 0) {
            $sql .= " AND (SELECT user_id FROM git_tokens WHERE id = git_token_id) = :user_id";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $repoId, PDO::PARAM_INT);
        if ($userId != 0) {
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function getCommits(int $repoId = 0, int $userId = 0): array
    {
        $sql = "SELECT c.* FROM commits c";

        $conditions = [];

        if ($repoId != 0) {
            $conditions[] = "c.repository_id = :repository_id";
        }

        if ($userId != 0) {
            $sql .= " JOIN repositories r ON c.repository_id = r.id";
            $sql .= " JOIN git_tokens gt ON r.git_token_id = gt.id";
            $conditions[] = "gt.user_id = :user_id";
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $this->db->prepare($sql);
        if ($repoId != 0) {
            $stmt->bindParam(':repository_id', $repoId, PDO::PARAM_INT);
        }
        if ($userId != 0) {
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $commits = [];
        foreach ($results as $result) {
            $commits[] = new Commit(
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

        return $commits;
    }

    /**
     * @param int $userId
     * @return CommitAnalysis[]
     */
    public function getCommitAnalysis(int $repoId = 0, int $userId = 0): array
    {
        $sql = "SELECT * FROM commit_analysis WHERE commit_id IN (SELECT id FROM commits WHERE repository_id = :repository_id)";
        if ($userId != 0) {
            $sql .= " AND (SELECT user_id FROM git_tokens WHERE id = (SELECT git_token_id FROM repositories WHERE id = :repository_id)) = :user_id";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':repository_id', $repoId, PDO::PARAM_INT);
        if ($userId != 0) {
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $commitAnalysis = [];
        foreach ($results as $result) {
            $commitAnalysis[] = new CommitAnalysis(
                (int) $result['commit_id'],
                (int) $result['quality'],
                $result['commit_type'],
                $result['files']
            );
        }

        return $commitAnalysis;
    }
}
