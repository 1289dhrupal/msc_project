<?php

declare(strict_types=1);

namespace MscProject\Controllers;

use MscProject\Models\Response\Response;
use MscProject\Models\Response\SuccessResponse;
use MscProject\Services\UserService;

class UserController
{
    private UserService $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    public function register(): Response
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $input = array_merge(array('name' => '', 'email' => '', 'password' => ''), $input);
        $this->service->registerUser($input['name'], $input['email'], $input['password']);

        return new SuccessResponse('User registration successful');
    }

    public function verify(): Response
    {
        $token = $_GET['token'] ?? '';
        $email = $_GET['email'] ?? '';
        $result = $this->service->verifyUser($email, $token);
        return new SuccessResponse('Email verified successfully.');
    }

    public function login(): Response
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $input = array_merge(array('email' => '', 'password' => ''), $input);
        $apiKey = $this->service->loginUser($input['email'], $input['password']);

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
