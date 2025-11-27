<?php

declare(strict_types=1);

namespace MscProject\Repositories;

use MscProject\Database;
use MscProject\Models\Alert;
use MscProject\Models\User;
use PDO;
use PDOStatement;

class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    private function prepareAndExecute(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value[0], $value[1]);
        }
        $stmt->execute();
        return $stmt;
    }

    private function fetchSingleResult(string $sql, array $params = []): ?array
    {
        $stmt = $this->prepareAndExecute($sql, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function fetchAllResults(string $sql, array $params = []): array
    {
        $stmt = $this->prepareAndExecute($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function mapUser(array $result): User
    {
        return new User(
            (int)$result['id'],
            (string)$result['name'],
            (string)$result['email'],
            (string)$result['password'],
            (string)$result['status']
        );
    }

    private function mapAlert(array $result, int $userId): Alert
    {
        return new Alert(
            $userId,
            (bool)$result['inactivity'],
            (bool)$result['sync'],
            (bool)$result['realtime']
        );
    }

    public function getUserById(int $id): ?User
    {
        $sql = "SELECT * FROM users WHERE id = :id";
        $params = [
            ':id' => [$id, PDO::PARAM_INT]
        ];
        $result = $this->fetchSingleResult($sql, $params);

        return $result ? $this->mapUser($result) : null;
    }

    public function getUserAlerts(int $userId): ?Alert
    {
        $sql = "SELECT * FROM alerts WHERE user_id = :id";
        $params = [
            ':id' => [$userId, PDO::PARAM_INT]
        ];
        $result = $this->fetchSingleResult($sql, $params);

        return $result ? $this->mapAlert($result, $userId) : new Alert($userId);
    }

    public function setUserAlerts(Alert $alert): bool
    {
        $sql = "REPLACE INTO alerts (user_id, inactivity, sync, realtime) VALUES (:user_id, :inactivity, :sync, :realtime)";
        $params = [
            ':user_id' => [$alert->getUserId(), PDO::PARAM_INT],
            ':inactivity' => [$alert->getInactivity(), PDO::PARAM_BOOL],
            ':sync' => [$alert->getSync(), PDO::PARAM_BOOL],
            ':realtime' => [$alert->getRealtime(), PDO::PARAM_BOOL]
        ];
        return $this->prepareAndExecute($sql, $params)->rowCount() > 0;
    }

    public function getUserByEmail(string $email): ?User
    {
        $sql = "SELECT * FROM users WHERE email = :email";
        $params = [
            ':email' => [$email, PDO::PARAM_STR]
        ];
        $result = $this->fetchSingleResult($sql, $params);

        return $result ? $this->mapUser($result) : null;
    }

    public function createUser(User $user): int
    {
        $sql = "INSERT INTO users (name, email, password, status) VALUES (:name, :email, :password, :status)";
        $params = [
            ':name' => [$user->getName(), PDO::PARAM_STR],
            ':email' => [$user->getEmail(), PDO::PARAM_STR],
            ':password' => [$user->getPassword(), PDO::PARAM_STR],
            ':status' => [$user->getStatus(), PDO::PARAM_STR]
        ];
        $this->prepareAndExecute($sql, $params);

        return (int)$this->db->lastInsertId();
    }

    public function updateUserStatus(int $id, string $status): bool
    {
        $sql = "UPDATE users SET status = :status WHERE id = :id";
        $params = [
            ':status' => [$status, PDO::PARAM_STR],
            ':id' => [$id, PDO::PARAM_INT]
        ];
        return $this->prepareAndExecute($sql, $params)->rowCount() > 0;
    }

    public function updateUser(User $user): bool
    {
        $sql = "UPDATE users SET name = :name";
        $params = [
            ':name' => [$user->getName(), PDO::PARAM_STR],
            ':id' => [$user->getId(), PDO::PARAM_INT]
        ];

        if ($user->getPassword()) {
            $sql .= ", password = :password";
            $params[':password'] = [$user->getPassword(), PDO::PARAM_STR];
        }

        $sql .= " WHERE id = :id";
        return $this->prepareAndExecute($sql, $params)->rowCount() > 0;
    }

    public function updateUserLastAccessed(int $id): bool
    {
        $sql = "UPDATE users SET last_accessed = CURRENT_TIMESTAMP WHERE id = :id";
        $params = [
            ':id' => [$id, PDO::PARAM_INT]
        ];
        return $this->prepareAndExecute($sql, $params)->rowCount() > 0;
    }

    public function fetchAlertsToNotify(int $userId = 0): array
    {
        $sql = "
            SELECT u.id, u.email, a.inactivity, a.sync, a.realtime
            FROM users u
            JOIN alerts a ON u.id = a.user_id
            WHERE u.status = 'active'
        ";
        return $this->fetchAllResults($sql);
    }
}
