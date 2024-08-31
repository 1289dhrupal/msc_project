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
        $input = $_POST;
        $input = array_merge(array('name' => '', 'email' => '', 'password' => ''), $input);

        // name must be alphnumeric and can include spaces and dots and 3 characters long atleast
        if (!preg_match('/^[a-zA-Z0-9 .]{3,}$/', $input['name'])) {
            throw new \ErrorException('Name must be alphanumeric and can include spaces and dots',  400, E_USER_WARNING);
        }

        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \ErrorException('Invalid email address', 400, E_USER_WARNING);
        }

        if (strlen($input['password']) < 8) {
            throw new \ErrorException('Password must be at least 8 characters long',  400, E_USER_WARNING);
        }

        $this->service->registerUser($input['name'], $input['email'], $input['password']);

        return new SuccessResponse('Please check your email to verify your account before logging in.');
    }

    public function verify(): Response
    {
        $input = array_merge(array('email' => '', 'token' => ''), $_GET);

        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \ErrorException('Invalid email address', 400, E_USER_WARNING);
        }

        $this->service->verifyUser($input['email'], $input['token']);
        return new SuccessResponse('Email verified successfully.');
    }

    public function login(): Response
    {
        $input = $_POST ?: [];
        $input = array_merge(array('email' => '', 'password' => ''), $input);

        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \ErrorException('Invalid email address',  401, E_USER_WARNING);
        }

        if (strlen($input['password']) < 8) {
            throw new \ErrorException('Password must be at least 8 characters long',  401, E_USER_WARNING);
        }

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

    public function getUser(): Response
    {
        $user = $this->service->getUser();
        $alerts = $this->service->getUserAlerts();
        return new SuccessResponse('User details', ['user' => $user, 'alerts' => $alerts]);
    }

    public function updateUser(): Response
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $input = array_merge(array('name' => '', 'password' => ''), $input);


        // name must be alphnumeric and can include spaces and dots and 3 characters long atleast
        if (!preg_match('/^[a-zA-Z0-9 .]{3,}$/', $input['name'])) {
            throw new \ErrorException('Name must be alphanumeric and can include spaces and dots',  400, E_USER_WARNING);
        }


        if (!isset($input['password']) || !$input['password']) {
            $input['password'] = "";
        } else if (strlen($input['password']) < 8) {
            throw new \ErrorException('Password must be at least 8 characters long',  400, E_USER_WARNING);
        }

        $this->service->updateUser($input['name'], $input['password']);

        return new SuccessResponse('User details updated successfully');
    }

    public function updateAlerts(): Response
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $input = array_merge(array('inactivity' => true, 'sync' => true, 'realtime' => true), $input);

        $this->service->updateAlerts($input['inactivity'], $input['sync'], $input['realtime']);

        return new SuccessResponse('User alerts updated successfully');
    }
}
