<?php

declare(strict_types=1);

namespace MscProject\Middleware;

use ErrorException;
use MscProject\Repositories\UserRepository;
use MscProject\Repositories\SessionRepository;
use MscProject\Models\User;

class AuthMiddleware
{
    private UserRepository $userRepository;
    private SessionRepository $sessionRepository;

    public function __construct(UserRepository $userRepository, SessionRepository $sessionRepository)
    {
        $this->userRepository = $userRepository;
        $this->sessionRepository = $sessionRepository;
    }

    public function execute(): void
    {
        $headers = apache_request_headers();
        $apiKey = $headers['Authorization'] ?? '';
        $this->authenticate($apiKey);
    }

    private function authenticate(string $apiKey): void
    {
        $session = $this->sessionRepository->getSessionByApiKey($apiKey);

        if ($session === null) {
            throw new \ErrorException('Invalid API key', 401, E_USER_WARNING);
        }

        $user = $this->userRepository->getUserById($session->userId);
        global $user_session;
        $user_session = $user;
    }
}
