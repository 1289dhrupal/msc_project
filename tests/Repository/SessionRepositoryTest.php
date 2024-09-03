<?php

use PHPUnit\Framework\TestCase;
use MscProject\Repositories\SessionRepository;
use MscProject\Repositories\UserRepository;
use MscProject\Models\Session;
use MscProject\Models\User;
use MscProject\Database;

class SessionRepositoryTest extends TestCase
{
    private SessionRepository $sessionRepository;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        // Initialize the repositories
        $this->sessionRepository = new SessionRepository();
        $this->userRepository = new UserRepository();

        // Reset the database for testing
        $this->resetDatabase();
    }

    private function resetDatabase(): void
    {
        // Code to reset your database for a clean state before each test
        $db = Database::getInstance()->getConnection();
        $db->exec("DELETE FROM sessions");
        $db->exec("DELETE FROM users");
    }

    private function createTestUser(): int
    {
        $user = new User(null, 'Test User', 'testuser@example.com', 'hashedpassword', 'active');
        return $this->userRepository->createUser($user);
    }

    public function testCreateSession()
    {
        // First, create a user
        $userId = $this->createTestUser();

        // Now, create a session for this user
        $session = new Session($userId, 'testApiKey', date('Y-m-d H:i:s'));
        $result = $this->sessionRepository->createSession($session);

        $this->assertTrue($result);

        // Verify that the session was created
        $storedSession = $this->sessionRepository->getSessionByApiKey('testApiKey');
        $this->assertInstanceOf(Session::class, $storedSession);
        $this->assertEquals($userId, $storedSession->getUserId());
        $this->assertEquals('testApiKey', $storedSession->getApiKey());
    }

    public function testGetSessionByApiKey()
    {
        // Create a user and session
        $userId = $this->createTestUser();
        $session = new Session($userId, 'apiKeyToFind', date('Y-m-d H:i:s'));
        $this->sessionRepository->createSession($session);

        // Retrieve the session
        $storedSession = $this->sessionRepository->getSessionByApiKey('apiKeyToFind');
        $this->assertInstanceOf(Session::class, $storedSession);
        $this->assertEquals($userId, $storedSession->getUserId());
        $this->assertEquals('apiKeyToFind', $storedSession->getApiKey());
    }

    public function testDeleteSessionByApiKey()
    {
        // Create a user and session
        $userId = $this->createTestUser();
        $session = new Session($userId, 'apiKeyToDelete', date('Y-m-d H:i:s'));
        $this->sessionRepository->createSession($session);

        // Delete the session
        $result = $this->sessionRepository->deleteSessionByApiKey('apiKeyToDelete');
        $this->assertTrue($result);

        // Verify that the session was deleted
        $storedSession = $this->sessionRepository->getSessionByApiKey('apiKeyToDelete');
        $this->assertNull($storedSession);
    }

    protected function tearDown(): void
    {
        // Clean up the database after each test
        $this->resetDatabase();
    }
}
