<?php

declare(strict_types=1);

namespace MscProject\Services;

use MscProject\Repositories\UserRepository;
use MscProject\Repositories\SessionRepository;
use MscProject\Models\User;
use MscProject\Models\Session;
use MscProject\Mailer;
use PHPMailer\PHPMailer\Exception as MailException;

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
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \ErrorException('Invalid email address', 400, E_USER_WARNING);
        }

        if (strlen($password) < 8) {
            throw new \ErrorException('Password must be at least 8 characters long',  400, E_USER_WARNING);
        }

        // name must be alphnumeric and can include spaces and dots and 3 characters long atleast
        if (!preg_match('/^[a-zA-Z0-9 .]{3,}$/', $name)) {
            throw new \ErrorException('Name must be alphanumeric and can include spaces and dots',  400, E_USER_WARNING);
        }

        $user = $this->userRepository->getUserByEmail($email);

        if ($user !== null) {
            throw new \ErrorException('User already exists',  409, E_USER_WARNING);
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $user = new User(null, $name, $email, $hashedPassword, 'pending');
        $user_id = $this->userRepository->createUser($user);
        if ($user_id !== 0) {
            $user->setId($user_id);
            $this->sendVerificationEmail($user);
            return true;
        }

        return false;
    }

    private function sendVerificationEmail(User $user): void
    {
        $mailer = Mailer::getInstance()->getMailer();
        try {
            // Recipients
            $mailer->addAddress($user->getEmail(), $user->getName());

            // Content
            $mailer->isHTML(true);
            $mailer->Subject = 'Email Verification';
            $mailer->Body    = $this->getVerificationEmailBody($user->getEmail(), password_hash($user->getId() . $user->getEmail(), PASSWORD_BCRYPT));

            $mailer->send();
        } catch (MailException $e) {
            throw new \Exception('Verification email could not be sent. Mailer Error: ' . $mailer->ErrorInfo, 500);
        }
    }


    private function getVerificationEmailBody(string $email, string $token): string
    {
        $verificationUrl = $_ENV['BASE_URL'] . "/verify?email=$email&token=$token";
        return "<p>Please click the following link to verify your email: <a href=\"$verificationUrl\">Verify Email</a></p>";
    }


    public function verifyUser(string $email, string $token): bool
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \ErrorException('Invalid email address', 400, E_USER_WARNING);
        }

        $user = $this->userRepository->getUserByEmail($email);

        if ($user->getStatus() === 'active') {
            throw new \ErrorException('User already verified',  400, E_USER_WARNING);
        }

        if ($user->getStatus() === 'inactive') {
            throw new \ErrorException('User account is inactive, please contact the admin',  403, E_USER_WARNING);
        }

        if ($user === null || !password_verify($user->getId() . $user->getEmail(), $token)) {
            throw new \ErrorException('Verification token is invalid',  401, E_USER_WARNING);
        }

        $user->setStatus('active');
        return $this->userRepository->updateUserStatus($user->getId(), $user->getStatus());
    }

    public function loginUser(string $email, string $password): ?string
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \ErrorException('Invalid email address',  401, E_USER_WARNING);
        }

        if (strlen($password) < 8) {
            throw new \ErrorException('Password must be at least 8 characters long',  401, E_USER_WARNING);
        }

        $user = $this->userRepository->getUserByEmail($email);

        if ($user === null || !password_verify($password, $user->getPassword())) {
            throw new \ErrorException('Incorrect Credentials',  401, E_USER_WARNING);
        }

        if ($user->getStatus() === 'pending') {
            throw new \ErrorException('User account is pending email verification', 403, E_USER_WARNING);
        }

        if ($user->getStatus() === 'inactive') {
            throw new \ErrorException('User account is inactive, please contact the admin',  403, E_USER_WARNING);
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
}
