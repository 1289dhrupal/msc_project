<?php

declare(strict_types=1);

namespace MscProject\Repositories;

use MscProject\Database;
use MscProject\Models\Session;
use PDO;
use PDOStatement;

class SessionRepository
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

    public function createSession(Session $session): bool
    {
        $sql = "INSERT INTO sessions (user_id, api_key) VALUES (:user_id, :api_key)";
        $params = [
            ':user_id' => [$session->getUserId(), PDO::PARAM_INT],
            ':api_key' => [$session->getApiKey(), PDO::PARAM_STR]
        ];
        return $this->prepareAndExecute($sql, $params)->rowCount() > 0;
    }

    public function getSessionByApiKey(string $apiKey): ?Session
    {
        $sql = "SELECT * FROM sessions WHERE api_key = :api_key";
        $params = [
            ':api_key' => [$apiKey, PDO::PARAM_STR]
        ];
        $result = $this->fetchSingleResult($sql, $params);

        return $result ? new Session(
            (int)$result['user_id'],
            (string)$result['api_key'],
            (string)$result['created_at']
        ) : null;
    }

    public function deleteSessionByApiKey(string $apiKey): bool
    {
        $sql = "DELETE FROM sessions WHERE api_key = :api_key";
        $params = [
            ':api_key' => [$apiKey, PDO::PARAM_STR]
        ];
        return $this->prepareAndExecute($sql, $params)->rowCount() > 0;
    }
}
