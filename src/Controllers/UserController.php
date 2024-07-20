<?php

namespace MscProject\Controllers;

use MscProject\Services\UserService;

class UserController
{
    private $service;

    public function __construct()
    {
        $this->service = new UserService();
    }

    public function register()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $name = $input['name'] ?? '';
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        $result = $this->service->registerUser($name, $email, $password);
        echo json_encode(['success' => $result]);
    }

    public function login()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        $apiKey = $this->service->loginUser($email, $password);
        if ($apiKey) {
            echo json_encode(['apiKey' => $apiKey]);
        } else {
            header('HTTP/1.0 401 Unauthorized');
            echo json_encode(['error' => 'Invalid email or password']);
        }
    }

    public function authenticate()
    {
        $headers = apache_request_headers();
        $apiKey = $headers['Authorization'] ?? '';
        $user = $this->service->authenticate($apiKey);
        if ($user) {
            echo json_encode($user);
        } else {
            header('HTTP/1.0 401 Unauthorized');
            echo json_encode(['error' => 'Invalid API key']);
        }
    }

    public function logout()
    {
        $headers = apache_request_headers();
        $apiKey = $headers['Authorization'] ?? '';
        $result = $this->service->logoutUser($apiKey);
        echo json_encode(['success' => $result]);
    }
}
