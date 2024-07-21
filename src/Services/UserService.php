<?php

namespace MscProject\Services;

use MscProject\Repositories\UserRepository;
use MscProject\Repositories\SessionRepository;
use MscProject\Models\User;
use MscProject\Models\Session;

class UserService
{
    private $userRepository;
    private $sessionRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->sessionRepository = new SessionRepository();
    }

    public function registerUser($name, $email, $password)
    {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $user = new User(null, $name, $email, $hashedPassword, 'inactive', null);
        return $this->userRepository->createUser($user);
    }

    public function loginUser($email, $password)
    {
        $user = $this->userRepository->getUserByEmail($email);
        if ($user && password_verify($password, $user->password)) {
            $apiKey = bin2hex(random_bytes(32));
            $session = new Session($user->id, $apiKey);
            $this->sessionRepository->createSession($session);
            $this->userRepository->updateUserLastAccessed($user->id);
            return $apiKey;
        }
        return null;
    }

    public function authenticate($apiKey)
    {
        $session = $this->sessionRepository->getSessionByApiKey($apiKey);

        if ($session) {
            $user = $this->userRepository->getUserById($session->userId);
            return $user;
        }

        return null;
    }

    public function logoutUser($apiKey)
    {
        return $this->sessionRepository->deleteSessionByApiKey($apiKey);
    }
}
