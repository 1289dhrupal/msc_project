<?php

declare(strict_types=1);

namespace MscProject\Services;

use MscProject\Repositories\UserRepository;
use MscProject\Repositories\SessionRepository;
use MscProject\Models\User;
use MscProject\Models\Session;

class UserService
{
    private UserRepository $userRepository;
    private SessionRepository $sessionRepository;

    public function __construct(UserRepository $userRepository, SessionRepository $sessionRepository)
    {
        $this->userRepository = $userRepository;
        $this->sessionRepository = $sessionRepository;
    }

    public function registerUser(string $name, string $email, string $password): bool
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \ErrorException('Invalid email address', 400, E_USER_WARNING);
        }

        if (strlen($password) < 8) {
            throw new \ErrorException('Password must be at least 8 characters long',  400, E_USER_WARNING);
        }

        // name must be alphnumeric and can include spaces and dots and 3 characters long atleast
        if (!preg_match('/^[a-zA-Z0-9 .]{3,}$/', $name)) {
            throw new \ErrorException('Name must be alphanumeric and can include spaces and dots',  400, E_USER_WARNING);
        }

        $user = $this->userRepository->getUserByEmail($email);

        if ($user !== null) {
            throw new \ErrorException('User already exists',  409, E_USER_WARNING);
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $user = new User(null, $name, $email, $hashedPassword, 'pending');
        return $this->userRepository->createUser($user);
    }

    public function loginUser(string $email, string $password): ?string
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \ErrorException('Invalid email address',  401, E_USER_WARNING);
        }

        if (strlen($password) < 8) {
            throw new \ErrorException('Password must be at least 8 characters long',  401, E_USER_WARNING);
        }

        $user = $this->userRepository->getUserByEmail($email);

        if ($user === null || !password_verify($password, $user->password)) {
            throw new \ErrorException('Incorrect Credentials',  401, E_USER_WARNING);
        }

        if ($user->status === 'pending') {
            throw new \ErrorException('User account is pending email verification', 403, E_USER_WARNING);
        }

        if ($user->status === 'inactive') {
            throw new \ErrorException('User account is inactive, please contact the admin',  403, E_USER_WARNING);
        }

        $apiKey = bin2hex(random_bytes(32));
        $session = new Session($user->id, $apiKey);
        $this->sessionRepository->createSession($session);
        $this->userRepository->updateUserLastAccessed($user->id);
        return $apiKey;
    }

    public function logoutUser(string $apiKey): bool
    {
        return $this->sessionRepository->deleteSessionByApiKey($apiKey);
    }
}
