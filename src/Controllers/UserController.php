<?php

declare(strict_types=1);

namespace MscProject\Controllers;

use MscProject\Models\Response\Response;
use MscProject\Models\Response\SuccessResponse;
use MscProject\Services\UserService;
use Exception;
use ErrorException;

class UserController
{
    private UserService $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    public function register(): Response
    {
        try {
            $input = $_POST;
            $input = array_merge(['name' => '', 'email' => '', 'password' => ''], $input);

            if (!preg_match('/^[a-zA-Z0-9 .]{3,}$/', $input['name'])) {
                throw new ErrorException('Name must be alphanumeric and can include spaces and dots', 400, E_USER_WARNING);
            }

            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                throw new ErrorException('Invalid email address', 400, E_USER_WARNING);
            }

            if (strlen($input['password']) < 8) {
                throw new ErrorException('Password must be at least 8 characters long', 400, E_USER_WARNING);
            }

            $this->service->registerUser($input['name'], $input['email'], $input['password']);

            return new SuccessResponse('Please check your email to verify your account before logging in.');
        } catch (Exception $e) {
            throw new ErrorException('Failed to register user', 400, E_USER_WARNING, previous: $e);
        }
    }

    public function verify(): Response
    {
        try {
            $input = array_merge(['email' => '', 'token' => ''], $_GET);

            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                throw new ErrorException('Invalid email address', 400, E_USER_WARNING);
            }

            $this->service->verifyUser($input['email'], $input['token']);

            return new SuccessResponse('Email verified successfully.');
        } catch (Exception $e) {
            throw new ErrorException('Failed to verify email', 400, E_USER_WARNING, previous: $e);
        }
    }

    public function login(): Response
    {
        try {
            $input = $_POST ?: [];
            $input = array_merge(['email' => '', 'password' => ''], $input);

            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                throw new ErrorException('Invalid email address', 401, E_USER_WARNING);
            }

            if (strlen($input['password']) < 8) {
                throw new ErrorException('Password must be at least 8 characters long', 401, E_USER_WARNING);
            }

            $apiKey = $this->service->loginUser($input['email'], $input['password']);

            return new SuccessResponse('User login successful', ['apiKey' => $apiKey]);
        } catch (Exception $e) {
            throw new ErrorException('Failed to log in', 401, E_USER_WARNING, previous: $e);
        }
    }

    public function logout(): Response
    {
        try {
            $headers = apache_request_headers();
            $apiKey = $headers['Authorization'] ?? '';

            $this->service->logoutUser($apiKey);

            return new SuccessResponse('User logout successful');
        } catch (Exception $e) {
            throw new ErrorException('Failed to log out', 400, E_USER_WARNING, previous: $e);
        }
    }

    public function getUser(): Response
    {
        try {
            global $userSession;

            $user = $this->service->getUser($userSession->getId());
            $alerts = $this->service->getUserAlerts($userSession->getId());

            return new SuccessResponse('User details', ['user' => $user, 'alerts' => $alerts]);
        } catch (Exception $e) {
            throw new ErrorException('Failed to get user details', 400, E_USER_WARNING, previous: $e);
        }
    }

    public function updateUser(): Response
    {
        try {
            global $userSession;

            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $input = array_merge(['name' => '', 'password' => ''], $input);

            if (!preg_match('/^[a-zA-Z0-9 .]{3,}$/', $input['name'])) {
                throw new ErrorException('Name must be alphanumeric and can include spaces and dots', 400, E_USER_WARNING);
            }

            if (isset($input['password']) && strlen($input['password']) > 0 && strlen($input['password']) < 8) {
                throw new ErrorException('Password must be at least 8 characters long', 400, E_USER_WARNING);
            }

            $this->service->updateUser($input['name'], $input['password'], $userSession->getId());

            return new SuccessResponse('User details updated successfully');
        } catch (Exception $e) {
            throw new ErrorException('Failed to update user details', 400, E_USER_WARNING, previous: $e);
        }
    }

    public function updateAlerts(): Response
    {
        try {
            global $userSession;

            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $input = array_merge(['inactivity' => true, 'sync' => true, 'realtime' => true], $input);

            $this->service->updateAlerts($input['inactivity'], $input['sync'], $input['realtime'], $userSession->getId());

            return new SuccessResponse('User alerts updated successfully');
        } catch (Exception $e) {
            throw new ErrorException('Failed to update user alerts', 400, E_USER_WARNING, previous: $e);
        }
    }

    public function requestPasswordReset(): Response
    {
        try {
            $email = $_GET['email'] ?? '';
            $this->service->requestPasswordReset($email);

            return new SuccessResponse('Password reset email sent');
        } catch (Exception $e) {
            throw new ErrorException('Failed to send password reset email', 400, E_USER_WARNING, previous: $e);
        }
    }

    public function verifyPasswordReset(): Response
    {
        try {
            $input = array_merge(['token' => '', 'email' => '', 'new_password' => ''], $_POST);

            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                throw new ErrorException('Invalid email address', 400, E_USER_WARNING);
            }

            if (isset($input['password']) && strlen($input['password']) > 0 && strlen($input['password']) < 8) {
                throw new ErrorException('Password must be at least 8 characters long', 400, E_USER_WARNING);
            }

            $this->service->verifyPasswordReset($input['email'], $input['token'], $input['new_password']);

            return new SuccessResponse('Password reset successful');
        } catch (Exception $e) {
            throw new ErrorException('Failed to reset password', 400, E_USER_WARNING, previous: $e);
        }
    }
}
