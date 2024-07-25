<?php

declare(strict_types=1);

namespace MscProject\Repositories;

use MscProject\Database;
use MscProject\Models\GitToken;
use PDO;

class GitTokenRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getTokenByToken(string $token): ?GitToken
    {
        $stmt = $this->db->prepare("SELECT * FROM git_tokens WHERE token = :token");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        $result = $stmt->fetch();

        if (!$result) {
            return null;
        }

        return new GitToken($result['id'], $result['user_id'], $result['token'], $result['service']);
    }

    public function create(GitToken $gitToken): bool
    {
        $user_id = $gitToken->getUserId();
        $token = $gitToken->getToken();
        $service = $gitToken->getService();
        $stmt = $this->db->prepare("INSERT INTO git_tokens (user_id, token, service) VALUES (:user_id, :token, :service)");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':service', $service);
        return $stmt->execute();
    }

    /**
     * @param int $userId
     * @return GitToken[]
     */
    public function getTokensByUserId(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM git_tokens WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $results = $stmt->fetchAll();

        $tokens = [];
        foreach ($results as $result) {
            $tokens[] = new GitToken($result['id'], $result['user_id'], $result['token'], $result['service']);
        }

        return $tokens;
    }

    /**
     * @param int $userId
     * @return GitToken[]
     */
    public function fetchAll(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM git_tokens");
        $stmt->execute();
        $results = $stmt->fetchAll();

        $tokens = [];
        foreach ($results as $result) {
            $tokens[] = new GitToken($result['id'], $result['user_id'], $result['token'], $result['service']);
        }

        return $tokens;
    }

    // Additional methods for fetching and updating Git tokens can be added here
}
