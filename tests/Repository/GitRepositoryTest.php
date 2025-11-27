<?php

use PHPUnit\Framework\TestCase;
use MscProject\Repositories\GitRepository;
use MscProject\Repositories\UserRepository;
use MscProject\Models\GitToken;
use MscProject\Models\Repository;
use MscProject\Models\Commit;
use MscProject\Models\CommitFile;
use MscProject\Models\User;
use MscProject\Database;

class GitRepositoryTest extends TestCase
{
    private GitRepository $gitRepository;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->gitRepository = new GitRepository();
        $this->userRepository = new UserRepository();

        $this->resetDatabase();
    }

    private function resetDatabase(): void
    {
        $db = Database::getInstance()->getConnection();
        $db->exec("DELETE FROM commit_files");
        $db->exec("DELETE FROM commits");
        $db->exec("DELETE FROM repositories");
        $db->exec("DELETE FROM git_tokens");
        $db->exec("DELETE FROM users");
    }

    private function createTestUser(): int
    {
        $user = new User(null, 'Test User', 'testuser@example.com', 'hashedpassword', 'active');
        return $this->userRepository->createUser($user);
    }

    private function createTestGitToken(int $userId): int
    {
        $gitToken = new GitToken(null, $userId, 'testToken', 'github', 'https://github.com/test', 'Test Token', true, null, null);
        $this->gitRepository->create($gitToken);
        return $this->gitRepository->getTokenByToken('testToken')->getId();
    }

    public function testStoreRepository()
    {
        $userId = $this->createTestUser();
        $gitTokenId = $this->createTestGitToken($userId);

        $repositoryId = $this->gitRepository->storeRepository(
            $gitTokenId,
            'test-repo',
            'https://github.com/test-repo',
            'A test repository',
            'testOwner',
            'main',
            123456
        );

        $this->assertIsInt($repositoryId);

        $repository = $this->gitRepository->getRepository($gitTokenId, 'testOwner', 'test-repo');
        $this->assertInstanceOf(Repository::class, $repository);
        $this->assertEquals('test-repo', $repository->getName());
        $this->assertEquals('testOwner', $repository->getOwner());
        $this->assertEquals('main', $repository->getDefaultBranch());
    }

    public function testGetRepository()
    {
        $userId = $this->createTestUser();
        $gitTokenId = $this->createTestGitToken($userId);

        $this->gitRepository->storeRepository(
            $gitTokenId,
            'test-repo',
            'https://github.com/test-repo',
            'A test repository',
            'testOwner',
            'main',
            123456
        );

        $repository = $this->gitRepository->getRepository($gitTokenId, 'testOwner', 'test-repo');
        $this->assertInstanceOf(Repository::class, $repository);
        $this->assertEquals('test-repo', $repository->getName());

        $nonExistentRepo = $this->gitRepository->getRepository($gitTokenId, 'testOwner', 'non-existent-repo');
        $this->assertNull($nonExistentRepo);
    }

    public function testStoreCommit()
    {
        $userId = $this->createTestUser();
        $gitTokenId = $this->createTestGitToken($userId);

        $repositoryId = $this->gitRepository->storeRepository(
            $gitTokenId,
            'test-repo',
            'https://github.com/test-repo',
            'A test repository',
            'testOwner',
            'main',
            123456
        );

        $commitId = $this->gitRepository->storeCommit(
            $repositoryId,
            'testsha123',
            'testAuthor',
            'Initial commit',
            date('Y-m-d H:i:s'),
            10,
            2,
            12,
            0,
            75,
            80,
            [
                [
                    'sha' => 'fileSha1',
                    'status' => 'added',
                    'additions' => 10,
                    'deletions' => 2,
                    'total' => 12,
                    'filename' => 'testfile1.php',
                ]
            ]
        );

        $this->assertIsInt($commitId);

        $commit = $this->gitRepository->getCommit($repositoryId, 'testsha123');
        $this->assertInstanceOf(Commit::class, $commit);
        $this->assertEquals('testsha123', $commit->getSha());
        $this->assertEquals('testAuthor', $commit->getAuthor());
        $this->assertCount(1, $commit->getFiles());

        $commitFile = $commit->getFiles()[0];
        $this->assertInstanceOf(CommitFile::class, $commitFile);
        $this->assertEquals('fileSha1', $commitFile->getSha());
        $this->assertEquals('testfile1.php', $commitFile->getFilename());
    }

    public function testGetCommit()
    {
        $userId = $this->createTestUser();
        $gitTokenId = $this->createTestGitToken($userId);

        $repositoryId = $this->gitRepository->storeRepository(
            $gitTokenId,
            'test-repo',
            'https://github.com/test-repo',
            'A test repository',
            'testOwner',
            'main',
            123456
        );

        $commitId = $this->gitRepository->storeCommit(
            $repositoryId,
            'testsha123',
            'testAuthor',
            'Initial commit',
            date('Y-m-d H:i:s'),
            10,
            2,
            12,
            0,
            75,
            80,
            []
        );

        $commit = $this->gitRepository->getCommit($repositoryId, 'testsha123');
        $this->assertInstanceOf(Commit::class, $commit);
        $this->assertEquals('testsha123', $commit->getSha());
        $this->assertEquals('testAuthor', $commit->getAuthor());

        $nonExistentCommit = $this->gitRepository->getCommit($repositoryId, 'nonexistentsha');
        $this->assertNull($nonExistentCommit);
    }

    public function testUpdateRepositoryFetchedAt()
    {
        $userId = $this->createTestUser();
        $gitTokenId = $this->createTestGitToken($userId);

        $repositoryId = $this->gitRepository->storeRepository(
            $gitTokenId,
            'test-repo',
            'https://github.com/test-repo',
            'A test repository',
            'testOwner',
            'main',
            123456
        );

        $this->gitRepository->updateRepositoryFetchedAt($repositoryId);

        $repository = $this->gitRepository->getRepository($gitTokenId, 'testOwner', 'test-repo');
        $this->assertNotNull($repository->getLastFetchedAt());
    }

    public function testUpdateTokenFetchedAt()
    {
        $userId = $this->createTestUser();
        $gitTokenId = $this->createTestGitToken($userId);

        $this->gitRepository->updateTokenFetchedAt($gitTokenId);

        $gitToken = $this->gitRepository->getToken($gitTokenId);
        $this->assertNotNull($gitToken->getLastFetchedAt());
    }

    public function testGetTokenByToken()
    {
        $userId = $this->createTestUser();
        $gitTokenId = $this->createTestGitToken($userId);

        $gitToken = $this->gitRepository->getTokenByToken('testToken');
        $this->assertInstanceOf(GitToken::class, $gitToken);
        $this->assertEquals('testToken', $gitToken->getToken());

        $nonExistentToken = $this->gitRepository->getTokenByToken('nonexistenttoken');
        $this->assertNull($nonExistentToken);
    }

    public function testListTokens()
    {
        $userId = $this->createTestUser();
        $gitTokenId1 = $this->createTestGitToken($userId);

        $gitToken = new GitToken(null, $userId, 'testToken2', 'gitlab', 'https://gitlab.com/test', 'Test Token 2', true, null, null);
        $this->gitRepository->create($gitToken);

        $tokens = $this->gitRepository->listTokens($userId);
        $this->assertCount(2, $tokens);

        $tokens = $this->gitRepository->listTokens($userId, (string)$gitTokenId1);
        $this->assertCount(1, $tokens);
        $this->assertEquals('testToken', $tokens[0]->getToken());
    }

    public function testToggleToken()
    {
        $userId = $this->createTestUser();
        $gitTokenId = $this->createTestGitToken($userId);

        $affectedRows = $this->gitRepository->toggleToken($gitTokenId, false, $userId);
        $this->assertEquals(1, $affectedRows);

        $gitToken = $this->gitRepository->getToken($gitTokenId);
        $this->assertFalse($gitToken->isActive());

        $affectedRows = $this->gitRepository->toggleToken($gitTokenId, true, $userId);
        $this->assertEquals(1, $affectedRows);

        $gitToken = $this->gitRepository->getToken($gitTokenId);
        $this->assertTrue($gitToken->isActive());
    }

    public function testDeleteRepositoriesByTokenId()
    {
        $userId = $this->createTestUser();
        $gitTokenId = $this->createTestGitToken($userId);

        $this->gitRepository->storeRepository(
            $gitTokenId,
            'test-repo',
            'https://github.com/test-repo',
            'A test repository',
            'testOwner',
            'main',
            123456
        );

        $deletedCount = $this->gitRepository->deleteRepositoriesByTokenId($gitTokenId);
        $this->assertEquals(1, $deletedCount);

        $repository = $this->gitRepository->getRepository($gitTokenId, 'testOwner', 'test-repo');
        $this->assertNull($repository);
    }

    public function testDeleteToken()
    {
        $userId = $this->createTestUser();
        $gitTokenId = $this->createTestGitToken($userId);

        $this->gitRepository->storeRepository(
            $gitTokenId,
            'test-repo',
            'https://github.com/test-repo',
            'A test repository',
            'testOwner',
            'main',
            123456
        );

        $deletedCount = $this->gitRepository->deleteToken($gitTokenId, $userId);
        $this->assertEquals(1, $deletedCount);

        $gitToken = $this->gitRepository->getToken($gitTokenId);
        $this->assertNull($gitToken);
    }

    public function testListRepositories()
    {
        $userId = $this->createTestUser();
        $gitTokenId = $this->createTestGitToken($userId);

        $this->gitRepository->storeRepository(
            $gitTokenId,
            'test-repo-1',
            'https://github.com/test-repo-1',
            'First test repository',
            'testOwner1',
            'main',
            123456
        );

        $this->gitRepository->storeRepository(
            $gitTokenId,
            'test-repo-2',
            'https://github.com/test-repo-2',
            'Second test repository',
            'testOwner2',
            'main',
            123457
        );

        $repositories = $this->gitRepository->listRepositories($userId, $gitTokenId);

        $this->assertCount(2, $repositories);
        $this->assertEquals('test-repo-1', $repositories[0]->getName());
        $this->assertEquals('test-repo-2', $repositories[1]->getName());
    }

    public function testToggleRepository()
    {
        $userId = $this->createTestUser();
        $gitTokenId = $this->createTestGitToken($userId);

        $repositoryId = $this->gitRepository->storeRepository(
            $gitTokenId,
            'test-repo',
            'https://github.com/test-repo',
            'A test repository',
            'testOwner',
            'main',
            123456
        );

        $affectedRows = $this->gitRepository->toggleRepository($repositoryId, false, $userId);
        $this->assertEquals(1, $affectedRows);

        $repository = $this->gitRepository->getRepository($gitTokenId, 'testOwner', 'test-repo');
        $this->assertFalse($repository->isActive());

        $affectedRows = $this->gitRepository->toggleRepository($repositoryId, true, $userId);
        $this->assertEquals(1, $affectedRows);

        $repository = $this->gitRepository->getRepository($gitTokenId, 'testOwner', 'test-repo');
        $this->assertTrue($repository->isActive());
    }

    public function testDeleteRepository()
    {
        $userId = $this->createTestUser();
        $gitTokenId = $this->createTestGitToken($userId);

        $repositoryId = $this->gitRepository->storeRepository(
            $gitTokenId,
            'test-repo',
            'https://github.com/test-repo',
            'A test repository',
            'testOwner',
            'main',
            123456
        );

        $affectedRows = $this->gitRepository->deleteRepository($repositoryId);
        $this->assertEquals(1, $affectedRows);

        $repository = $this->gitRepository->getRepository($gitTokenId, 'testOwner', 'test-repo');
        $this->assertNull($repository);
    }

    public function testListCommits()
    {
        $userId = $this->createTestUser();
        $gitTokenId = $this->createTestGitToken($userId);

        $repositoryId = $this->gitRepository->storeRepository(
            $gitTokenId,
            'test-repo',
            'https://github.com/test-repo',
            'A test repository',
            'testOwner',
            'main',
            123456
        );

        $this->gitRepository->storeCommit(
            $repositoryId,
            'testsha123',
            'testAuthor',
            'Initial commit',
            date('Y-m-d H:i:s'),
            10,
            2,
            12,
            0,
            75,
            80,
            []
        );

        $this->gitRepository->storeCommit(
            $repositoryId,
            'testsha124',
            'testAuthor2',
            'Second commit',
            date('Y-m-d H:i:s'),
            15,
            3,
            18,
            0,
            80,
            85,
            []
        );

        $commits = $this->gitRepository->listCommits($repositoryId);
        $this->assertCount(2, $commits);
        $this->assertEquals('testsha123', $commits[0]->getSha());
        $this->assertEquals('testsha124', $commits[1]->getSha());
    }

    public function testGetRepositoryByHookId()
    {
        $userId = $this->createTestUser();
        $gitTokenId = $this->createTestGitToken($userId);

        $repositoryId = $this->gitRepository->storeRepository(
            $gitTokenId,
            'test-repo',
            'https://github.com/test-repo',
            'A test repository',
            'testOwner',
            'main',
            123456
        );

        $repository = $this->gitRepository->getRepositoryByHookId(123456);
        $this->assertInstanceOf(Repository::class, $repository);
        $this->assertEquals('test-repo', $repository->getName());

        $nonExistentRepository = $this->gitRepository->getRepositoryByHookId(654321);
        $this->assertNull($nonExistentRepository);
    }

    public function testUpdateToken()
    {
        $userId = $this->createTestUser();
        $gitTokenId = $this->createTestGitToken($userId);

        $gitToken = $this->gitRepository->getToken($gitTokenId);
        $gitToken->setToken('updatedToken');
        $this->gitRepository->updateToken($gitToken);

        $updatedGitToken = $this->gitRepository->getToken($gitTokenId);
        $this->assertEquals('updatedToken', $updatedGitToken->getToken());
    }

    protected function tearDown(): void
    {
        // Clean up the database after each test
        $this->resetDatabase();
    }
}
