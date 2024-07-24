<?php

declare(strict_types=1);

namespace MscProject\Repositories;

use ErrorException;
use MscProject\Database;
use MscProject\Models\Session;
use PDO;

class SessionRepository
{
    private PDO $db;

    public function __construct(Database $db)
    {
        $this->db = $db->getConnection();
    }

    public function createSession(Session $session): bool
    {
        $stmt = $this->db->prepare("INSERT INTO sessions (user_id, api_key) VALUES (:user_id, :api_key)");
        $stmt->bindParam(':user_id', $session->userId, PDO::PARAM_INT);
        $stmt->bindParam(':api_key', $session->apiKey, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function getSessionByApiKey(string $apiKey): ?Session
    {
        $stmt = $this->db->prepare("SELECT * FROM sessions WHERE api_key = :api_key");
        $stmt->bindParam(':api_key', $apiKey, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            return null;
        }

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return new Session(
            (int)$result['user_id'],
            (string)$result['api_key'],
            (string)$result['created_at']
        );
    }

    public function deleteSessionByApiKey(string $apiKey): bool
    {
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE api_key = :api_key");
        $stmt->bindParam(':api_key', $apiKey, PDO::PARAM_STR);
        return $stmt->execute();
    }
}
