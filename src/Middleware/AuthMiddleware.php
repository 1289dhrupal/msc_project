<?php

declare(strict_types=1);

namespace MscProject\Middleware;

use MscProject\Repositories\UserRepository;
use MscProject\Repositories\SessionRepository;
use MscProject\Models\User;
use ErrorException;

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
        $apiKey = $this->getApiKeyFromHeaders();
        $this->authenticate($apiKey);
    }

    private function getApiKeyFromHeaders(): string
    {
        // Fallback if apache_request_headers() is not available
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            return $headers['Authorization'] ?? '';
        }

        // Alternative for environments without apache_request_headers()
        $headers = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        return is_string($headers) ? $headers : '';
    }

    private function authenticate(string $apiKey): void
    {
        if (empty($apiKey)) {
            throw new ErrorException('API key missing', 401, E_USER_WARNING);
        }

        $session = $this->sessionRepository->getSessionByApiKey($apiKey);

        if ($session === null) {
            throw new ErrorException('Invalid API key', 401, E_USER_WARNING);
        }

        $user = $this->userRepository->getUserById($session->getUserId());

        if ($user === null) {
            throw new ErrorException('User not found', 404, E_USER_WARNING);
        }

        // Using a more secure method to store user information instead of global
        $this->setAuthenticatedUser($user);
    }

    private function setAuthenticatedUser(User $user): void
    {
        // Implement a better way to manage user sessions, for example:
        // - Use session variables
        // - Use a dependency injection container
        // - Store it in a request context object
        // For simplicity, assuming a global session object here, but this should be improved
        global $userSession;
        $userSession = $user;
    }
}
