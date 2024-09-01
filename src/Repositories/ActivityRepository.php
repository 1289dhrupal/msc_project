<?php

declare(strict_types=1);

namespace MscProject\Repositories;

use MscProject\Database;
use PDO;
use PDOStatement;

class ActivityRepository
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

    private function fetchAllResults(string $sql, array $params = []): array
    {
        $stmt = $this->prepareAndExecute($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchUsersWithTokens(): array
    {
        $sql = "
            SELECT u.id as user_id, u.email, gt.id as git_token_id 
            FROM users u
            JOIN git_tokens gt ON u.id = gt.user_id
            WHERE gt.is_active = 1 AND u.status = 'active'
        ";
        return $this->fetchAllResults($sql);
    }

    public function fetchInactiveRepositories(int $gitTokenId, string $interval): array
    {
        $sql = "
            SELECT r.id, r.name, r.owner, MAX(c.created_at) AS last_activity
            FROM repositories r
                LEFT JOIN commits c ON r.id = c.repository_id
            WHERE r.git_token_id = :git_token_id AND r.is_active = 1
            GROUP BY r.id, r.name, r.owner
            HAVING last_activity < NOW() - INTERVAL $interval
                OR last_activity IS NULL
        ";
        $params = [
            ':git_token_id' => [$gitTokenId, PDO::PARAM_INT]
        ];
        return $this->fetchAllResults($sql, $params);
    }
}
