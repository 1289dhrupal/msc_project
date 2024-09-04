<?php

declare(strict_types=1);

namespace MscProject\Repositories;

use MscProject\Database;
use MscProject\Models\GitToken;
use MscProject\Models\Repository;
use MscProject\Models\Commit;
use MscProject\Models\CommitFile;
use MscProject\Utils;
use PDO;
use PDOStatement;
use Exception;

class GitRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    private function prepareAndExecute(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value[0], $value[1]);
        }
        $stmt->execute();
        return $stmt;
    }

    private function fetchSingleResult(string $sql, array $params = []): ?array
    {
        $stmt = $this->prepareAndExecute($sql, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function fetchAllResults(string $sql, array $params = []): array
    {
        $stmt = $this->prepareAndExecute($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function mapRepository(array $result): Repository
    {
        return new Repository(
            (int) $result['id'],
            (int) $result['git_token_id'],
            $result['name'],
            $result['url'],
            $result['description'],
            $result['owner'],
            $result['default_branch'],
            (int) $result['hook_id'],
            (bool) $result['is_active'],
            $result['created_at'],
            $result['last_fetched_at']
        );
    }

    private function mapCommit(array $result, array $files): Commit
    {
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
            (int) $result['number_of_comment_lines'],
            (int) $result['commit_changes_quality_score'],
            (int) $result['commit_message_quality_score'],
            $this->mapCommitFiles($files, (int) $result['id'])
        );
    }

    /** @return CommitFile[] $files */
    private function mapCommitFiles(array $fileData, int $commitId): array
    {
        return array_map(fn($file) => new CommitFile(
            $commitId,
            $file['sha'],
            $file['status'],
            (int) $file['additions'],
            (int) $file['deletions'],
            (int) ($file['total'] ?? $file['changes'] ?? ($file['additions'] + $file['deletions'])),
            $file['filename'],
            $file['extension'] ?? Utils::getFileExtension($file['filename'])
        ), $fileData);
    }

    public function storeRepository(int $gitTokenId, string $name, string $url, ?string $description, string $owner, string $defaultBranch, int $hookId): int
    {
        $sql = "INSERT INTO repositories (git_token_id, name, url, description, owner, default_branch, hook_id) 
                VALUES (:git_token_id, :name, :url, :description, :owner, :default_branch, :hook_id)";
        $this->prepareAndExecute($sql, [
            ':git_token_id' => [$gitTokenId, PDO::PARAM_INT],
            ':name' => [$name, PDO::PARAM_STR],
            ':url' => [$url, PDO::PARAM_STR],
            ':description' => [$description, PDO::PARAM_STR],
            ':owner' => [$owner, PDO::PARAM_STR],
            ':default_branch' => [$defaultBranch, PDO::PARAM_STR],
            ':hook_id' => [$hookId, PDO::PARAM_INT]
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function getRepository(int $gitTokenId, string $owner, string $name): ?Repository
    {
        $sql = "SELECT * FROM repositories WHERE git_token_id = :git_token_id AND owner = :owner AND name = :name";
        $result = $this->fetchSingleResult($sql, [
            ':git_token_id' => [$gitTokenId, PDO::PARAM_INT],
            ':owner' => [$owner, PDO::PARAM_STR],
            ':name' => [$name, PDO::PARAM_STR]
        ]);

        return $result ? $this->mapRepository($result) : null;
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
        int $numberOfCommentLines,
        int $commitChangesQualityScore,
        int $commitMessageQualityScore,
        array $files
    ): int {

        $files = array_map(function (array $file) {
            return [
                'sha' => $file['sha'],
                'status' => $file['status'],
                'additions' => (int) $file['additions'],
                'deletions' => (int) $file['deletions'],
                'total' => (int) ($file['total'] ?? $file['changes'] ?? ($file['additions'] + $file['deletions'])),
                'filename' => $file['filename'],
                'extension' => $file['extension'] ?? Utils::getFileExtension($file['filename']),
            ];
        }, $files);

        $sql = "INSERT INTO commits (repository_id, sha, author, message, date, additions, deletions, total, number_of_comment_lines, commit_changes_quality_score, commit_message_quality_score) 
                VALUES (:repository_id, :sha, :author, :message, :date, :additions, :deletions, :total, :number_of_comment_lines, :commit_changes_quality_score, :commit_message_quality_score)";

        $this->prepareAndExecute($sql, [
            ':repository_id' => [$repositoryId, PDO::PARAM_INT],
            ':sha' => [$sha, PDO::PARAM_STR],
            ':author' => [$author, PDO::PARAM_STR],
            ':message' => [$message, PDO::PARAM_STR],
            ':date' => [$date, PDO::PARAM_STR],
            ':additions' => [$additions, PDO::PARAM_INT],
            ':deletions' => [$deletions, PDO::PARAM_INT],
            ':total' => [$total, PDO::PARAM_INT],
            ':number_of_comment_lines' => [$numberOfCommentLines, PDO::PARAM_INT],
            ':commit_changes_quality_score' => [$commitChangesQualityScore, PDO::PARAM_INT],
            ':commit_message_quality_score' => [$commitMessageQualityScore, PDO::PARAM_INT]
        ]);

        $commitId = (int)$this->db->lastInsertId();

        foreach ($files as $file) {
            $this->storeCommitFiles($commitId, $file);
        }

        return $commitId;
    }

    public function storeCommitFiles(int $commitId, array $file): void
    {
        $sql = "INSERT INTO commit_files (commit_id, sha, status, additions, deletions, total, filename, extension) 
                VALUES (:commit_id, :sha, :status, :additions, :deletions, :total, :filename, :extension)";

        $params = [
            ':commit_id' => [$commitId, PDO::PARAM_INT],
            ':sha' => [$file['sha'], PDO::PARAM_STR],
            ':status' => [$file['status'], PDO::PARAM_STR],
            ':additions' => [$file['additions'], PDO::PARAM_INT],
            ':deletions' => [$file['deletions'], PDO::PARAM_INT],
            ':total' => [$file['total'], PDO::PARAM_INT],
            ':filename' => [$file['filename'], PDO::PARAM_STR],
            ':extension' => [$file['extension'], PDO::PARAM_STR]
        ];

        $this->prepareAndExecute($sql, $params);
    }

    public function getCommit(int $repositoryId, string $sha, bool $includeFiles = true): ?Commit
    {
        $sql = "SELECT * FROM commits WHERE repository_id = :repository_id AND sha = :sha";
        $result = $this->fetchSingleResult($sql, [
            ':repository_id' => [$repositoryId, PDO::PARAM_INT],
            ':sha' => [$sha, PDO::PARAM_STR]
        ]);

        if ($result) {
            $files = [];

            if ($includeFiles) {
                $sql = "SELECT * FROM commit_files WHERE commit_id = :commit_id";
                $files = $this->fetchAllResults($sql, [
                    ':commit_id' => [(int)$result['id'], PDO::PARAM_INT]
                ]);
            }

            return $this->mapCommit($result, $files);
        }

        return null;
    }

    public function getCommitById(int $commitId, bool $includeFiles = true): ?Commit
    {
        $sql = "SELECT * FROM commits WHERE id = :commit_id";
        $result = $this->fetchSingleResult($sql, [
            ':commit_id' => [$commitId, PDO::PARAM_INT]
        ]);

        if ($result) {
            $files = [];

            if ($includeFiles) {
                $sql = "SELECT * FROM commit_files WHERE commit_id = :commit_id";
                $files = $this->fetchAllResults($sql, [
                    ':commit_id' => [(int)$result['id'], PDO::PARAM_INT]
                ]);
            }

            return $this->mapCommit($result, $files);
        }

        return null;
    }

    public function updateRepositoryFetchedAt(int $repositoryId): void
    {
        $sql = "UPDATE repositories SET last_fetched_at = CURRENT_TIMESTAMP WHERE id = :id";
        $this->prepareAndExecute($sql, [
            ':id' => [$repositoryId, PDO::PARAM_INT]
        ]);
    }

    public function updateTokenFetchedAt(int $gitTokenId): void
    {
        $sql = "UPDATE git_tokens SET last_fetched_at = CURRENT_TIMESTAMP WHERE id = :id";
        $this->prepareAndExecute($sql, [
            ':id' => [$gitTokenId, PDO::PARAM_INT]
        ]);
    }

    public function getTokenByToken(string $token): ?GitToken
    {
        $sql = "SELECT * FROM git_tokens WHERE token = :token";
        $result = $this->fetchSingleResult($sql, [
            ':token' => [$token, PDO::PARAM_STR]
        ]);

        return $result ? new GitToken($result['id'], $result['user_id'], $result['token'], $result['service'], $result['url'], $result['description'], (bool) $result['is_active'], $result['created_at'], $result['last_fetched_at']) : null;
    }

    public function create(GitToken $gitToken): bool
    {
        $sql = "INSERT INTO git_tokens (user_id, token, service, url, description) 
                VALUES (:user_id, :token, :service, :url, :description)";
        $this->prepareAndExecute($sql, [
            ':user_id' => [$gitToken->getUserId(), PDO::PARAM_INT],
            ':token' => [$gitToken->getToken(), PDO::PARAM_STR],
            ':service' => [$gitToken->getService(), PDO::PARAM_STR],
            ':url' => [$gitToken->getUrl(), PDO::PARAM_STR],
            ':description' => [$gitToken->getDescription(), PDO::PARAM_STR]
        ]);

        return true;
    }

    public function listTokens(int $userId = 0, string $gitTokenIds = "", string $service = ""): array
    {
        $sql = "SELECT * FROM git_tokens WHERE 1";
        $params = [];

        if ($userId != 0) {
            $sql .= " AND user_id = :user_id";
            $params[':user_id'] = [$userId, PDO::PARAM_INT];
        }

        if ($gitTokenIds != '') {
            $sql .= " AND FIND_IN_SET(id, :ids)";
            $params[':ids'] = [$gitTokenIds, PDO::PARAM_STR];
        }

        if ($service != "") {
            $sql .= " AND service = :service";
            $params[':service'] = [$service, PDO::PARAM_STR];
        }

        $results = $this->fetchAllResults($sql, $params);
        return array_map(fn($result) => new GitToken($result['id'], $result['user_id'], $result['token'], $result['service'], $result['url'], $result['description'], (bool) $result['is_active'], $result['created_at'], $result['last_fetched_at']), $results);
    }

    public function toggleToken(int $tokenId, bool $isActive, int $userId = 0): int
    {
        $sql = "UPDATE git_tokens SET is_active = :is_active WHERE id = :id";
        $params = [
            ':is_active' => [$isActive, PDO::PARAM_BOOL],
            ':id' => [$tokenId, PDO::PARAM_INT]
        ];

        if ($userId != 0) {
            $sql .= " AND user_id = :user_id";
            $params[':user_id'] = [$userId, PDO::PARAM_INT];
        }

        $stmt = $this->prepareAndExecute($sql, $params);
        return $stmt->rowCount();
    }

    public function getToken(int $tokenId, int $userId = 0): ?GitToken
    {
        $sql = "SELECT * FROM git_tokens WHERE id = :id";
        $params = [
            ':id' => [$tokenId, PDO::PARAM_INT]
        ];

        if ($userId != 0) {
            $sql .= " AND user_id = :user_id";
            $params[':user_id'] = [$userId, PDO::PARAM_INT];
        }

        $result = $this->fetchSingleResult($sql, $params);

        return $result ? new GitToken($result['id'], $result['user_id'], $result['token'], $result['service'], $result['url'], $result['description'], (bool) $result['is_active'], $result['created_at'], $result['last_fetched_at']) : null;
    }

    public function deleteRepositoriesByTokenId(int $tokenId): int
    {
        $sql = "SELECT * FROM repositories WHERE git_token_id = :git_token_id";
        $results = $this->fetchAllResults($sql, [
            ':git_token_id' => [$tokenId, PDO::PARAM_INT]
        ]);

        $count = 0;
        foreach ($results as $result) {
            $count += $this->deleteRepository((int) $result['id']);
        }
        return $count;
    }

    public function deleteToken(int $tokenId, int $userId = 0): int
    {
        $count = $this->deleteRepositoriesByTokenId($tokenId);

        $sql = "DELETE FROM git_tokens WHERE id = :id";
        $params = [
            ':id' => [$tokenId, PDO::PARAM_INT]
        ];

        if ($userId != 0) {
            $sql .= " AND user_id = :user_id";
            $params[':user_id'] = [$userId, PDO::PARAM_INT];
        }

        $this->prepareAndExecute($sql, $params);
        return $count;
    }

    public function listRepositories(int $userId = 0, int $gitTokenId = 0, string $repoIds = ""): array
    {
        $sql = "SELECT r.*, token AS git_token FROM repositories AS r, git_tokens AS gt WHERE r.git_token_id = gt.id";
        $params = [];

        if ($gitTokenId != 0) {
            $sql .= " AND git_token_id = :git_token_id";
            $params[':git_token_id'] = [$gitTokenId, PDO::PARAM_INT];
        } else {
            $sql .= " AND gt.is_active = 1";
        }

        if ($repoIds != '') {
            $sql .= " AND FIND_IN_SET(r.id, :ids)";
            $params[':ids'] = [$repoIds, PDO::PARAM_STR];
        }

        if ($userId != 0) {
            $sql .= " AND user_id = :user_id";
            $params[':user_id'] = [$userId, PDO::PARAM_INT];
        }

        $sql .= " ORDER BY r.id ASC";
        $results = $this->fetchAllResults($sql, $params);
        return array_map(fn($result) => $this->mapRepository($result), $results);
    }

    public function getRepositoryById(int $repoId = 0, int $userId = 0): Repository
    {
        $sql = "SELECT r.*, token AS git_token FROM repositories AS r, git_tokens AS gt WHERE r.git_token_id = gt.id";
        $params = [];

        if ($userId != 0) {
            $sql .= " AND user_id = :user_id";
            $params[':user_id'] = [$userId, PDO::PARAM_INT];
        }

        if ($repoId != 0) {
            $sql .= " AND r.id = :id";
            $params[':id'] = [$repoId, PDO::PARAM_INT];
        }

        $result = $this->fetchSingleResult($sql, $params);
        return $this->mapRepository($result);
    }

    public function toggleRepository(int $repoId, bool $isActive, int $userId = 0): int
    {
        $sql = "UPDATE repositories SET is_active = :is_active WHERE id = :id";
        $params = [
            ':is_active' => [$isActive, PDO::PARAM_BOOL],
            ':id' => [$repoId, PDO::PARAM_INT]
        ];

        if ($userId != 0) {
            $sql .= " AND (SELECT user_id FROM git_tokens WHERE id = git_token_id) = :user_id";
            $params[':user_id'] = [$userId, PDO::PARAM_INT];
        }

        $stmt = $this->prepareAndExecute($sql, $params);
        return $stmt->rowCount();
    }

    public function deleteRepository(int $repoId): int
    {
        $sql = "DELETE cf FROM commit_files cf JOIN commits c ON cf.commit_id = c.id WHERE c.repository_id = :id";
        $this->prepareAndExecute($sql, [
            ':id' => [$repoId, PDO::PARAM_INT]
        ]);

        $sql = "DELETE FROM commits WHERE repository_id = :id";
        $this->prepareAndExecute($sql, [
            ':id' => [$repoId, PDO::PARAM_INT]
        ]);

        $sql = "DELETE FROM repositories WHERE id = :id";
        $params = [
            ':id' => [$repoId, PDO::PARAM_INT]
        ];

        $stmt = $this->prepareAndExecute($sql, $params);
        return $stmt->rowCount();
    }

    public function listCommits(int $repoId = 0, int $userId = 0, $order = 'DESC', bool $includeFiles = true): array
    {
        $sql = "SELECT c.* FROM commits c";
        $params = [];
        $conditions = [];

        if ($repoId != 0) {
            $conditions[] = "c.repository_id = :repository_id";
            $params[':repository_id'] = [$repoId, PDO::PARAM_INT];
        }

        if ($userId != 0) {
            $sql .= " JOIN repositories r ON c.repository_id = r.id";
            $sql .= " JOIN git_tokens gt ON r.git_token_id = gt.id";
            $conditions[] = "gt.user_id = :user_id";
            $conditions[] = "r.is_active = 1";
            $conditions[] = "gt.is_active = 1";
            $params[':user_id'] = [$userId, PDO::PARAM_INT];
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY c.date $order";

        $results = $this->fetchAllResults($sql, $params);

        $results = array_map(function ($result) use ($includeFiles) {
            $files = [];

            if ($includeFiles) {
                $sql = "SELECT * FROM commit_files WHERE commit_id = :commit_id";
                $files = $this->fetchAllResults($sql, [
                    ':commit_id' => [(int)$result['id'], PDO::PARAM_INT]
                ]);
            }

            return $this->mapCommit($result, $files);
        }, $results);

        return $results;
    }

    public function getRepositoryByHookId(int $hookId): ?Repository
    {
        $sql = "SELECT * FROM repositories WHERE hook_id = :hook_id";
        $result = $this->fetchSingleResult($sql, [
            ':hook_id' => [$hookId, PDO::PARAM_INT]
        ]);

        return $result ? $this->mapRepository($result) : null;
    }

    public function updateToken(GitToken $gitToken): void
    {
        $sql = "UPDATE git_tokens SET token = :token, service = :service, url = :url, description = :description WHERE id = :id";
        $this->prepareAndExecute($sql, [
            ':token' => [$gitToken->getToken(), PDO::PARAM_STR],
            ':service' => [$gitToken->getService(), PDO::PARAM_STR],
            ':url' => [$gitToken->getUrl(), PDO::PARAM_STR],
            ':description' => [$gitToken->getDescription(), PDO::PARAM_STR],
            ':id' => [$gitToken->getId(), PDO::PARAM_INT]
        ]);
    }

    public function sync(int $repoId = 0, int $gitTokenId = 0): void
    {
        try {
            // Define the path to the SyncRepositories.php script
            $scriptPath = __DIR__ . '/../Scripts/SyncRepositories.php';

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows: Use "start /B" to run the script in the background
                exec("start /B php $scriptPath -r $repoId -g $gitTokenId");
            } else {
                // Linux/Mac: Use "nohup" to run the script in the background
                exec("nohup php $scriptPath > /dev/null 2>&1 &");
            }
        } catch (Exception $e) {
            // DO NOTHING
        }
    }
}
