<?php

use PHPUnit\Framework\TestCase;
use MscProject\Repositories\UserRepository;
use MscProject\Models\User;
use MscProject\Models\Alert;
use MscProject\Database;

class UserRepositoryTest extends TestCase
{
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        // Initialize the UserRepository
        $this->userRepository = new UserRepository();

        // Reset the database for testing
        $this->resetDatabase();
    }

    private function resetDatabase(): void
    {
        // Code to reset your database for a clean state before each test
        $db = Database::getInstance()->getConnection();
        $db->exec("DELETE FROM users");
        $db->exec("DELETE FROM alerts");
        // Add other relevant tables to reset as needed
    }

    public function testCreateUser()
    {
        $user = new User(null, 'John Doe', 'john@example.com', 'hashedpassword', 'active');
        $userId = $this->userRepository->createUser($user);

        $this->assertIsInt($userId);
        $this->assertGreaterThan(0, $userId);

        $storedUser = $this->userRepository->getUserById($userId);
        $this->assertInstanceOf(User::class, $storedUser);
        $this->assertEquals('John Doe', $storedUser->getName());
        $this->assertEquals('john@example.com', $storedUser->getEmail());
        $this->assertEquals('hashedpassword', $storedUser->getPassword());
        $this->assertEquals('active', $storedUser->getStatus());
    }

    public function testUpdateUserStatus()
    {
        // First, create a user
        $user = new User(null, 'Jane Doe', 'jane@example.com', 'hashedpassword', 'active');
        $userId = $this->userRepository->createUser($user);

        // Now, toggle the user status to 'inactive'
        $result = $this->userRepository->updateUserStatus($userId, 'inactive');
        $this->assertTrue($result);

        // Fetch the user and verify the status
        $storedUser = $this->userRepository->getUserById($userId);
        $this->assertEquals('inactive', $storedUser->getStatus());
    }

    public function testGetUserById()
    {
        $user = new User(null, 'Alice', 'alice@example.com', 'hashedpassword', 'active');
        $userId = $this->userRepository->createUser($user);

        $storedUser = $this->userRepository->getUserById($userId);
        $this->assertInstanceOf(User::class, $storedUser);
        $this->assertEquals('Alice', $storedUser->getName());
        $this->assertEquals('alice@example.com', $storedUser->getEmail());
    }

    public function testGetUserByEmail()
    {
        $user = new User(null, 'Bob', 'bob@example.com', 'hashedpassword', 'active');
        $this->userRepository->createUser($user);

        $storedUser = $this->userRepository->getUserByEmail('bob@example.com');
        $this->assertInstanceOf(User::class, $storedUser);
        $this->assertEquals('Bob', $storedUser->getName());
    }

    public function testUpdateUser()
    {
        $user = new User(null, 'Charlie', 'charlie@example.com', 'hashedpassword', 'active');
        $userId = $this->userRepository->createUser($user);
        // Update user's name and password
        $user->setId($userId);
        $user->setName('Charlie Updated');
        $user->setPassword('newhashedpassword');

        $result = $this->userRepository->updateUser($user);
        $this->assertTrue($result);

        $storedUser = $this->userRepository->getUserById($userId);
        $this->assertEquals('Charlie Updated', $storedUser->getName());
        $this->assertEquals('newhashedpassword', $storedUser->getPassword());
    }

    public function testSetUserAlerts()
    {
        $user = new User(null, 'Dave', 'dave@example.com', 'hashedpassword', 'active');
        $userId = $this->userRepository->createUser($user);

        $alert = new Alert($userId, true, true, true);
        $result = $this->userRepository->setUserAlerts($alert);

        $this->assertTrue($result);

        $storedAlert = $this->userRepository->getUserAlerts($userId);
        $this->assertInstanceOf(Alert::class, $storedAlert);
        $this->assertTrue($storedAlert->getInactivity());
        $this->assertTrue($storedAlert->getSync());
        $this->assertTrue($storedAlert->getRealtime());
    }

    public function testFetchAlertsToNotify()
    {
        $user1 = new User(null, 'User One', 'user1@example.com', 'hashedpassword1', 'active');
        $user2 = new User(null, 'User Two', 'user2@example.com', 'hashedpassword2', 'active');

        $userId1 = $this->userRepository->createUser($user1);
        $userId2 = $this->userRepository->createUser($user2);

        $alert1 = new Alert($userId1, true, true, true);
        $alert2 = new Alert($userId2, true, false, true);

        $this->userRepository->setUserAlerts($alert1);
        $this->userRepository->setUserAlerts($alert2);

        $alerts = $this->userRepository->fetchAlertsToNotify();
        $this->assertCount(2, $alerts);
    }

    public function testUpdateUserLastAccessed()
    {
        $user = new User(null, 'Eve', 'eve@example.com', 'hashedpassword', 'active');
        $userId = $this->userRepository->createUser($user);

        $result = $this->userRepository->updateUserLastAccessed($userId);
        $this->assertTrue($result);

        // Assuming last_accessed is automatically updated, we would need to check the database directly or via another method.
    }

    protected function tearDown(): void
    {
        // Clean up the database after each test
        $this->resetDatabase();
    }
}
