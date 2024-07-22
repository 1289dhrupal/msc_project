<?php

declare(strict_types=1);

namespace MscProject\Middleware;

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

    public function execute(): ?bool
    {
        $headers = apache_request_headers();
        $apiKey = $headers['Authorization'] ?? '';
        return $this->authenticate($apiKey);
    }

    private function authenticate(string $apiKey): bool
    {
        $session = $this->sessionRepository->getSessionByApiKey($apiKey);

        if ($session) {
            $user = $this->userRepository->getUserById($session->userId);
            $_SESSION['user'] = $user;
            return true;
        } else {
            header('HTTP/1.0 401 Unauthorized');
            echo json_encode(['error' => 'Invalid API key']);
            return false;
        }
    }
}
