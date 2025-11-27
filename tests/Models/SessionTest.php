<?php

use PHPUnit\Framework\TestCase;
use MscProject\Models\Session;

class SessionTest extends TestCase
{
    public function testSessionConstructor()
    {
        $userId = 1;
        $apiKey = 'apikey123';
        $createdAt = '2024-09-01';

        $session = new Session($userId, $apiKey, $createdAt);

        $this->assertEquals($userId, $session->getUserId());
        $this->assertEquals($apiKey, $session->getApiKey());
        $this->assertEquals($createdAt, $session->getCreatedAt());
    }

    public function testSettersAndGetters()
    {
        $session = new Session(1, 'apikey123', '2024-09-01');

        $session->setApiKey('newapikey');
        $session->setCreatedAt('2024-10-01');

        $this->assertEquals('newapikey', $session->getApiKey());
        $this->assertEquals('2024-10-01', $session->getCreatedAt());
    }
}
