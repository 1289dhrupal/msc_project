<?php

declare(strict_types=1);

namespace MscProject\Services;

use MscProject\Repositories\UserRepository;
use MscProject\Repositories\SessionRepository;
use MscProject\Models\User;
use MscProject\Models\Session;
use MscProject\Mailer;
use MscProject\Models\Alert;
use PHPMailer\PHPMailer\Exception as MailException;
use RuntimeException;
use ErrorException;

class UserService
{
    private UserRepository $userRepository;
    private SessionRepository $sessionRepository;

    public function __construct(UserRepository $userRepository, SessionRepository $sessionRepository)
    {
        $this->userRepository = $userRepository;
        $this->sessionRepository = $sessionRepository;
    }

    public function registerUser(string $name, string $email, string $password): bool
    {
        $user = $this->userRepository->getUserByEmail($email);

        if ($user !== null) {
            throw new ErrorException('User already exists',  409, E_USER_WARNING);
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $user = new User(null, $name, $email, $hashedPassword, 'pending');
        $userId = $this->userRepository->createUser($user);

        if ($userId === 0) {
            return false;
        }

        $user->setId($userId);
        $this->sendVerificationEmail($user);
        return true;
    }

    public function verifyUser(string $email, string $token): bool
    {
        $user = $this->userRepository->getUserByEmail($email);

        if ($user === null) {
            throw new ErrorException('User not found',  404, E_USER_WARNING);
        }

        if ($user->getStatus() === 'active') {
            throw new ErrorException('User already verified',  400, E_USER_WARNING);
        }

        if ($user->getStatus() === 'inactive') {
            throw new ErrorException('User account is inactive, please contact the admin',  403, E_USER_WARNING);
        }

        if (!password_verify($user->getId() . $user->getEmail(), $token)) {
            throw new ErrorException('Verification token is invalid',  401, E_USER_WARNING);
        }

        $user->setStatus('active');
        return $this->userRepository->updateUserStatus($user->getId(), $user->getStatus());
    }

    public function loginUser(string $email, string $password): ?string
    {
        $user = $this->userRepository->getUserByEmail($email);

        if ($user === null || !password_verify($password, $user->getPassword())) {
            throw new ErrorException('Incorrect Credentials',  401, E_USER_WARNING);
        }

        if ($user->getStatus() === 'pending') {
            throw new ErrorException('User account is pending email verification', 403, E_USER_WARNING);
        }

        if ($user->getStatus() === 'inactive') {
            throw new ErrorException('User account is inactive, please contact the admin',  403, E_USER_WARNING);
        }

        $apiKey = bin2hex(random_bytes(32));
        $session = new Session($user->getId(), $apiKey);
        $this->sessionRepository->createSession($session);
        $this->userRepository->updateUserLastAccessed($user->getId());

        return $apiKey;
    }

    public function logoutUser(string $apiKey): bool
    {
        return $this->sessionRepository->deleteSessionByApiKey($apiKey);
    }

    public function getUser(int $id): array
    {
        $user = $this->userRepository->getUserById($id);

        if ($user === null) {
            throw new ErrorException('User not found',  404, E_USER_WARNING);
        }

        return [
            'name' => $user->getName(),
            'email' => $user->getEmail(),
        ];
    }

    public function getUserAlerts(int $userId): array
    {
        $alert = $this->userRepository->getUserAlerts($userId);

        if ($alert === null) {
            throw new ErrorException('Alerts not found for the user',  404, E_USER_WARNING);
        }

        return [
            'inactivity' => $alert->getInactivity(),
            'sync' => $alert->getSync(),
            'realtime' => $alert->getRealtime(),
        ];
    }

    public function updateUser(string $name, string $password, int $id): bool
    {
        $user = $this->userRepository->getUserById($id);

        if ($user === null) {
            throw new ErrorException('User not found',  404, E_USER_WARNING);
        }

        $user->setName($name);

        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $user->setPassword($hashedPassword);
        }

        return $this->userRepository->updateUser($user);
    }

    public function updateAlerts(bool $inactivity, bool $sync, bool $realtime, int $userId): bool
    {
        $alert = new Alert($userId, $inactivity, $sync, $realtime);
        return $this->userRepository->setUserAlerts($alert);
    }

    private function sendVerificationEmail(User $user): void
    {
        $mailer = Mailer::getInstance();

        try {
            $to = $user->getEmail();
            $subject = 'Email Verification';
            $token = password_hash($user->getId() . $user->getEmail(), PASSWORD_BCRYPT);
            $body = $this->getVerificationEmailBody($user->getEmail(), $token);

            $mailer->sendEmail($to, $subject, $body);
        } catch (MailException $e) {
            throw new RuntimeException('Verification email could not be sent. Error: ' . $e->getMessage(), 500, $e);
        }
    }

    private function getVerificationEmailBody(string $email, string $token): string
    {
        $verificationUrl = $_ENV['BASE_URL'] . "/verify?email=$email&token=$token";
        return "<p>Please click the following link to verify your email: <a href=\"$verificationUrl\">Verify Email</a></p>";
    }
}
