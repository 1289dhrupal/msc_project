<?php

declare(strict_types=1);

namespace MscProject\Repositories;

use MscProject\Database;
use PDO;

class ActivityRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function fetchUsersWithTokens(): array
    {
        $stmt = $this->db->query("
            SELECT u.id as user_id, u.email, gt.id as git_token_id 
            FROM users u
            JOIN git_tokens gt ON u.id = gt.user_id
            WHERE gt.is_active = 1 AND u.status = 'active'
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchInactiveRepositories(int $gitTokenId, string $interval): array
    {
        // Build the SQL query with the interval directly inserted into the string
        $query = "
            SELECT r.id, r.name, r.owner, MAX(c.created_at) AS last_activity
            FROM repositories r
                LEFT JOIN commits c ON r.id = c.repository_id
            WHERE r.git_token_id = :git_token_id AND r.is_active = 1
            GROUP BY r.id, r.name, r.owner
            HAVING last_activity < NOW() - INTERVAL $interval
                OR last_activity IS NULL
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':git_token_id', $gitTokenId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
