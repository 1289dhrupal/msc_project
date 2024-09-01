<?php

declare(strict_types=1);

namespace MscProject;

use PDO;
use PDOException;

class Database
{
    private static ?Database $instance = null;
    private ?PDO $connection = null;

    private function __construct()
    {
        // Private constructor to prevent direct instantiation.
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function configure(): void
    {
        if ($this->connection === null) {
            try {
                $dsn = sprintf("mysql:host=%s;dbname=%s", $_ENV['DB_HOST'], $_ENV['DB_NAME']);
                $this->connection = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS']);
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                throw new PDOException("Database connection error: " . $e->getMessage(), 500, $e);
            }
        }
    }

    public function getConnection(): PDO
    {
        $this->configure(); // Ensure the connection is established.
        return $this->connection;
    }

    public function beginTransaction(): void
    {
        $this->getConnection()->beginTransaction();
    }

    public function commit(): void
    {
        $this->getConnection()->commit();
    }

    public function rollback(): void
    {
        $this->getConnection()->rollBack();
    }
}
