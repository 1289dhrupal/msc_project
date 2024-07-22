<?php

declare(strict_types=1);

namespace MscProject\Repositories;

use MscProject\Database;
use MscProject\Models\User;
use PDO;

class UserRepository
{
    private PDO $db;

    public function __construct(Database $db)
    {
        $this->db = $db->getConnection();
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

    public function createUser(User $user): bool
    {
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password, status) VALUES (:name, :email, :password, :status)");
        $stmt->bindParam(':name', $user->name, PDO::PARAM_STR);
        $stmt->bindParam(':email', $user->email, PDO::PARAM_STR);
        $stmt->bindParam(':password', $user->password, PDO::PARAM_STR);
        $stmt->bindParam(':status', $user->status, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function updateUserLastAccessed(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET last_accessed = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
