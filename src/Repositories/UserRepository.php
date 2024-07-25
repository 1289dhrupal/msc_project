<?php

declare(strict_types=1);

namespace MscProject\Repositories;

use MscProject\Database;
use MscProject\Models\User;
use PDO;

class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getUserById(int $id): ?User
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return new User(
            (int)$result['id'],
            (string)$result['name'],
            (string)$result['email'],
            (string)$result['password'],
            (string)$result['status']
        );
    }

    public function getUserByEmail(string $email): ?User
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            return null;
        }

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return new User(
            (int)$result['id'],
            (string)$result['name'],
            (string)$result['email'],
            (string)$result['password'],
            (string)$result['status']
        );
    }

    public function createUser(User $user): int
    {
        $name = $user->getName();
        $email = $user->getEmail();
        $password = $user->getPassword();
        $status = $user->getStatus();

        $stmt = $this->db->prepare("INSERT INTO users (name, email, password, status) VALUES (:name, :email, :password, :status)");
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':password', $password, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->execute();

        return intval($this->db->lastInsertId());
    }

    public function updateUserStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET status = :status WHERE id = :id");
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function updateUserLastAccessed(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET last_accessed = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
