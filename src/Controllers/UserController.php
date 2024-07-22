<?php

declare(strict_types=1);

namespace MscProject\Controllers;

use ErrorException;
use MscProject\Models\Response;
use MscProject\Models\SuccessResponse;
use MscProject\Services\UserService;
use MscProject\Models\User;

class UserController
{
    private UserService $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    public function register(): Response
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $name = $input['name'] ?? '';
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';

        $this->service->registerUser($name, $email, $password);

        return new SuccessResponse('User registration successful');
    }

    public function login(): Response
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        $apiKey = $this->service->loginUser($email, $password);

        return new SuccessResponse('User login successful', ['apiKey' => $apiKey]);
    }

    public function logout(): Response
    {
        $headers = apache_request_headers();
        $apiKey = $headers['Authorization'] ?? '';

        $this->service->logoutUser($apiKey);

        return new SuccessResponse('User logout successful');
    }
}
