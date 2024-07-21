<?php

namespace MscProject\Middleware;

use MscProject\Services\UserService;

class AuthMiddleware
{
    private $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function authenticate()
    {
        $headers = apache_request_headers();
        $apiKey = $headers['Authorization'] ?? '';
        $user = $this->userService->authenticate($apiKey);
        if ($user) {
            $_SESSION['user'] = $user;
            return $user;
        } else {
            header('HTTP/1.0 401 Unauthorized');
            echo json_encode(['error' => 'Invalid API key']);
            exit();
        }
    }
}
