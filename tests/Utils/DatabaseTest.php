<?php

use PHPUnit\Framework\TestCase;
use MscProject\Database;

class DatabaseTest extends TestCase
{
    protected function setUp(): void
    {
    }

    public function testGetInstanceReturnsSingletonInstance()
    {
        $db1 = Database::getInstance();
        $db2 = Database::getInstance();

        $this->assertInstanceOf(Database::class, $db1);
        $this->assertSame($db1, $db2, "Database::getInstance() does not return the same instance");
    }

    public function testGetConnectionReturnsPdoInstance()
    {
        $database = Database::getInstance();
        $connection = $database->getConnection();
        $this->assertInstanceOf(PDO::class, $connection);
    }
}
