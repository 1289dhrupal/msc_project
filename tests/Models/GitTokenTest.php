<?php

use PHPUnit\Framework\TestCase;
use MscProject\Models\GitToken;

class GitTokenTest extends TestCase
{
    public function testGitTokenConstructor()
    {
        $id = 1;
        $userId = 2;
        $token = 'token123';
        $service = 'github';
        $url = 'https://github.com/';
        $description = 'My GitHub token';
        $isActive = true;
        $createdAt = '2024-09-01';
        $lastFetchedAt = '2024-09-01 12:00:00';

        $gitToken = new GitToken($id, $userId, $token, $service, $url, $description, $isActive, $createdAt, $lastFetchedAt);

        $this->assertEquals($id, $gitToken->getId());
        $this->assertEquals($userId, $gitToken->getUserId());
        $this->assertEquals($token, $gitToken->getToken());
        $this->assertEquals($service, $gitToken->getService());
        $this->assertEquals($url, $gitToken->getUrl());
        $this->assertEquals($description, $gitToken->getDescription());
        $this->assertTrue($gitToken->isActive());
        $this->assertEquals($createdAt, $gitToken->getCreatedAt());
        $this->assertEquals($lastFetchedAt, $gitToken->getLastFetchedAt());
    }

    public function testSettersAndGetters()
    {
        $gitToken = new GitToken(1, 2, 'token123', 'github', 'https://github.com/', 'My GitHub token', true, '2024-09-01', '2024-09-01 12:00:00');

        $gitToken->setDescription('Updated description');
        $gitToken->setIsActive(false);

        $this->assertEquals('Updated description', $gitToken->getDescription());
        $this->assertFalse($gitToken->isActive());
    }
}
