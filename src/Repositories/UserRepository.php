<?php

namespace MscProject\Repositories;

use LDAP\Result;
use MscProject\Database;
use MscProject\Models\User;
use PDO;

class UserRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getUserById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result != false) {
            return new User($result['id'], $result['name'], $result['email'], $result['password'], $result['status']);
        }

        return null;
    }

    public function getUserByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result !== false) {
            return new User($result['id'], $result['name'], $result['email'], $result['password'], $result['status']);
        }

        return null;
    }

    public function createUser(User $user)
    {
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password, status) VALUES (:name, :email, :password, :status)");
        $stmt->bindParam(':name', $user->name);
        $stmt->bindParam(':email', $user->email);
        $stmt->bindParam(':password', $user->password);
        $stmt->bindParam(':status', $user->status);
        return $stmt->execute();
    }

    public function updateUserLastAccessed($id)
    {
        $stmt = $this->db->prepare("UPDATE users SET last_accessed = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
