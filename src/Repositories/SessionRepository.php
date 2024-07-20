<?php

namespace MscProject\Repositories;

use MscProject\Database;
use MscProject\Models\Session;
use PDO;

class SessionRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function createSession(Session $session)
    {
        $stmt = $this->db->prepare("INSERT INTO sessions (user_id, api_key, created_at) VALUES (:user_id, :api_key, :created_at)");
        $stmt->bindParam(':user_id', $session->userId);
        $stmt->bindParam(':api_key', $session->apiKey);
        $stmt->bindParam(':created_at', $session->createdAt);
        return $stmt->execute();
    }

    public function getSessionByApiKey($apiKey)
    {
        $stmt = $this->db->prepare("SELECT * FROM sessions WHERE api_key = :api_key");
        $stmt->bindParam(':api_key', $apiKey);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return new Session($result['user_id'], $result['api_key'], $result['created_at']);
    }

    public function deleteSessionByApiKey($apiKey)
    {
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE api_key = :api_key");
        $stmt->bindParam(':api_key', $apiKey);
        $stmt->execute();
        return $stmt->rowCount() !== 0;
    }
}
