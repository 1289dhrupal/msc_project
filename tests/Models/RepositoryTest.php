<?php

use PHPUnit\Framework\TestCase;
use MscProject\Models\Repository;

class RepositoryTest extends TestCase
{
    public function testRepositoryConstructor()
    {
        $id = 1;
        $gitTokenId = 2;
        $name = 'my-repo';
        $url = 'https://github.com/my-repo';
        $description = 'A test repository';
        $owner = 'JohnDoe';
        $defaultBranch = 'main';
        $hookId = 12345;
        $isActive = true;
        $createdAt = '2024-09-01';
        $lastFetchedAt = '2024-09-01 12:00:00';

        $repository = new Repository($id, $gitTokenId, $name, $url, $description, $owner, $defaultBranch, $hookId, $isActive, $createdAt, $lastFetchedAt);

        $this->assertEquals($id, $repository->getId());
        $this->assertEquals($gitTokenId, $repository->getGitTokenId());
        $this->assertEquals($name, $repository->getName());
        $this->assertEquals($url, $repository->getUrl());
        $this->assertEquals($description, $repository->getDescription());
        $this->assertEquals($owner, $repository->getOwner());
        $this->assertEquals($defaultBranch, $repository->getDefaultBranch());
        $this->assertEquals($hookId, $repository->getHookId());
        $this->assertTrue($repository->isActive());
        $this->assertEquals($createdAt, $repository->getCreatedAt());
        $this->assertEquals($lastFetchedAt, $repository->getLastFetchedAt());
    }

    public function testSettersAndGetters()
    {
        $repository = new Repository(1, 2, 'my-repo', 'https://github.com/my-repo', 'A test repository', 'JohnDoe', 'main', 12345, true, '2024-09-01', '2024-09-01 12:00:00');

        $repository->setDescription('Updated description');
        $repository->setIsActive(false);

        $this->assertEquals('Updated description', $repository->getDescription());
        $this->assertFalse($repository->isActive());
    }
}
