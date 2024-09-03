<?php

use PHPUnit\Framework\TestCase;
use MscProject\Models\Commit;
use MscProject\Models\CommitFile;

class CommitTest extends TestCase
{
    public function testCommitConstructor()
    {
        $id = 1;
        $repositoryId = 2;
        $sha = 'abc123';
        $message = 'Initial commit';
        $date = '2024-09-01';
        $author = 'John Doe';
        $additions = 10;
        $deletions = 5;
        $total = 15;
        $numberOfCommentLines = 2;
        $commitChangesQualityScore = 80;
        $commitMessageQualityScore = 90;
        $files = [new CommitFile(1, 'abc123', 'modified', 5, 3, 8, 'file1.php', 'php')];

        $commit = new Commit(
            $id,
            $repositoryId,
            $sha,
            $message,
            $date,
            $author,
            $additions,
            $deletions,
            $total,
            $numberOfCommentLines,
            $commitChangesQualityScore,
            $commitMessageQualityScore,
            $files
        );

        $this->assertEquals($id, $commit->getId());
        $this->assertEquals($repositoryId, $commit->getRepositoryId());
        $this->assertEquals($sha, $commit->getSha());
        $this->assertEquals($message, $commit->getMessage());
        $this->assertEquals($date, $commit->getDate());
        $this->assertEquals($author, $commit->getAuthor());
        $this->assertEquals($additions, $commit->getAdditions());
        $this->assertEquals($deletions, $commit->getDeletions());
        $this->assertEquals($total, $commit->getTotal());
        $this->assertEquals($numberOfCommentLines, $commit->getNumberOfCommentLines());
        $this->assertEquals($commitChangesQualityScore, $commit->getCommitChangesQualityScore());
        $this->assertEquals($commitMessageQualityScore, $commit->getCommitMessageQualityScore());
        $this->assertEquals($files, $commit->getFiles());
    }

    public function testSettersAndGetters()
    {
        $commit = new Commit(
            1,
            2,
            'abc123',
            'Initial commit',
            '2024-09-01',
            'John Doe',
            10,
            5,
            15,
            2,
            80,
            90,
            []
        );

        $commit->setMessage('Updated commit');
        $commit->setAdditions(20);
        $commit->setDeletions(10);

        $this->assertEquals('Updated commit', $commit->getMessage());
        $this->assertEquals(20, $commit->getAdditions());
        $this->assertEquals(10, $commit->getDeletions());
    }
}
