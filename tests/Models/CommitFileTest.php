<?php

use PHPUnit\Framework\TestCase;
use MscProject\Models\CommitFile;

class CommitFileTest extends TestCase
{
    public function testCommitFileConstructor()
    {
        $commitId = 1;
        $sha = 'abc123';
        $status = 'modified';
        $additions = 5;
        $deletions = 3;
        $total = 8;
        $filename = 'file1.php';
        $extension = 'php';

        $commitFile = new CommitFile($commitId, $sha, $status, $additions, $deletions, $total, $filename, $extension);

        $this->assertEquals($commitId, $commitFile->getCommitId());
        $this->assertEquals($sha, $commitFile->getSha());
        $this->assertEquals($status, $commitFile->getStatus());
        $this->assertEquals($additions, $commitFile->getAdditions());
        $this->assertEquals($deletions, $commitFile->getDeletions());
        $this->assertEquals($total, $commitFile->getTotal());
        $this->assertEquals($filename, $commitFile->getFilename());
        $this->assertEquals($extension, $commitFile->getExtension());
    }

    public function testSettersAndGetters()
    {
        $commitFile = new CommitFile(1, 'abc123', 'modified', 5, 3, 8, 'file1.php', 'php');

        $commitFile->setStatus('added');
        $commitFile->setFilename('file2.php');

        $this->assertEquals('added', $commitFile->getStatus());
        $this->assertEquals('file2.php', $commitFile->getFilename());
    }
}
