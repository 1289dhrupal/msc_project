<?php

namespace MscProject;

use PDO;

class Database
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        $this->connection = new PDO('mysql:host=localhost;dbname=msc_project', 'root', 'Dhrup@1289');
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->connection;
    }
}
