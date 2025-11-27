<?php

declare(strict_types=1);

namespace MscProject\Tests\Repositories;

use MscProject\Database;
use MscProject\Repositories\ActivityRepository;
use PDO;
use PHPUnit\Framework\TestCase;

class ActivityRepositoryTest extends TestCase
{
    private ActivityRepository $activityRepository;
    private PDO $db;

    protected function setUp(): void
    {
        // Set up a mock database connection
        $this->db = Database::getInstance()->getConnection();

        // Reset the database for testing
        $this->resetDatabase();
        // Create a new instance of ActivityRepository for testing
        $this->activityRepository = new ActivityRepository();
    }

    private function resetDatabase(): void
    {
        // Code to reset your database for a clean state before each test
        $this->db->exec("DELETE FROM commits");
        $this->db->exec("DELETE FROM repositories");
        $this->db->exec("DELETE FROM git_tokens");
        $this->db->exec("DELETE FROM users");
    }

    private function createTestUser(): int
    {
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password, status) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Test User', 'testuser@example.com', 'password', 'active']);
        return (int)$this->db->lastInsertId();
    }

    private function createTestGitToken(int $userId): int
    {
        $stmt = $this->db->prepare("INSERT INTO git_tokens (user_id, token, service, url, description, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, 'token', 'github', 'https://github.com', 'Test Token', 1]);
        return (int)$this->db->lastInsertId();
    }

    private function createTestRepository(int $gitTokenId, string $name, bool $isActive = true): int
    {
        $stmt = $this->db->prepare("INSERT INTO repositories (git_token_id, name, owner, url, description, default_branch, hook_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$gitTokenId, $name, 'TestOwner', 'https://github.com/test-repo', 'Test Repository', 'main', 123456, (int)$isActive]);
        return (int)$this->db->lastInsertId();
    }

    private function createTestCommit(int $repositoryId, string $sha, string $createdAt): void
    {
        $stmt = $this->db->prepare("INSERT INTO commits (repository_id, sha, message, date, author, additions, deletions, total, number_of_comment_lines, commit_changes_quality_score, commit_message_quality_score, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$repositoryId, $sha, 'Test Commit', '2024-01-01 00:00:00', 'TestAuthor', 10, 5, 15, 0, 80, 90, $createdAt]);
    }

    public function testFetchUsersWithTokens(): void
    {
        $userId = $this->createTestUser();
        $gitTokenId = $this->createTestGitToken($userId);

        $results = $this->activityRepository->fetchUsersWithTokens();

        $this->assertCount(1, $results);
        $this->assertEquals($userId, $results[0]['user_id']);
        $this->assertEquals($gitTokenId, $results[0]['git_token_id']);
        $this->assertEquals('testuser@example.com', $results[0]['email']);
    }

    public function testFetchInactiveRepositories(): void
    {
        $userId = $this->createTestUser();
        $gitTokenId = $this->createTestGitToken($userId);
        $repositoryId = $this->createTestRepository($gitTokenId, 'Inactive Repo');

        // Create a commit that is older than the interval
        $this->createTestCommit($repositoryId, 'sha1', '2023-01-01 00:00:00');

        $results = $this->activityRepository->fetchInactiveRepositories($gitTokenId, '1 YEAR');

        $this->assertCount(1, $results);
        $this->assertEquals($repositoryId, $results[0]['id']);
        $this->assertEquals('Inactive Repo', $results[0]['name']);
    }

    public function testFetchActiveRepositoriesReturnsEmpty(): void
    {
        $userId = $this->createTestUser();
        $gitTokenId = $this->createTestGitToken($userId);
        $repositoryId = $this->createTestRepository($gitTokenId, 'Active Repo');

        // Create a recent commit within the interval
        $this->createTestCommit($repositoryId, 'sha1', date('Y-m-d H:i:s'));

        $results = $this->activityRepository->fetchInactiveRepositories($gitTokenId, '1 YEAR');

        $this->assertCount(0, $results);
    }

    public function testFetchInactiveRepositoriesReturnsMultipleResults(): void
    {
        $userId = $this->createTestUser();
        $gitTokenId = $this->createTestGitToken($userId);

        $repo1Id = $this->createTestRepository($gitTokenId, 'Inactive Repo 1');
        $repo2Id = $this->createTestRepository($gitTokenId, 'Inactive Repo 2');

        // Create old commits for both repositories
        $this->createTestCommit($repo1Id, 'sha1', '2023-01-01 00:00:00');
        $this->createTestCommit($repo2Id, 'sha2', '2023-01-01 00:00:00');

        $results = $this->activityRepository->fetchInactiveRepositories($gitTokenId, '1 YEAR');

        $this->assertCount(2, $results);
        $this->assertEquals('Inactive Repo 1', $results[0]['name']);
        $this->assertEquals('Inactive Repo 2', $results[1]['name']);
    }

    protected function tearDown(): void
    {
        // Clean up the database after each test
        $this->resetDatabase();
    }
}
