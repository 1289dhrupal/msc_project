<?php

declare(strict_types=1);

namespace MscProject\Services;

use MscProject\Repositories\GitRepository;
use MscProject\Models\CommitAnalysis;

class GitAnalysisService
{
    private GitRepository $gitRepository;

    public function __construct(GitRepository $gitRepository)
    {
        $this->gitRepository = $gitRepository;
    }

    private function calculateCommitQuality(string $message, array $diffs): int
    {
        // Analyze the commit message quality
        $messageQuality = min(50, strlen($message) / 2); // Max 50 points for message length
        if (str_starts_with(strtolower($message), 'fix')) {
            $messageQuality = 0; // Deduct points if the message starts with 'fix'
        }

        // Initialize the changes score
        $changesScore = 0;

        // Analyze the diffs
        foreach ($diffs as $diff) {
            // Ensure the 'changes', 'additions', 'deletions', and 'patch' keys exist
            $changes = isset($diff['changes']) ? $diff['changes'] : 0;
            $additions = isset($diff['additions']) ? $diff['additions'] : 0;
            $deletions = isset($diff['deletions']) ? $diff['deletions'] : 0;
            $patch = isset($diff['patch']) ? $diff['patch'] : '';

            // Calculate changes score
            if ($diff['status'] !== 'renamed' && $diff['status'] !== 'copied') {
                // Length of the patch can indicate the complexity of the changes
                $patchLengthScore = min(20, strlen($patch) / 50); // Max 20 points for patch length

                // Number of additions and deletions
                $changesScore += min(30, ($additions + $deletions) / 10); // Max 30 points for changes

                // Add patch length score to the total changes score
                $changesScore += $patchLengthScore;
            }
        }

        // Total score is a combination of message quality and changes score
        return min(100, (int) ($messageQuality + $changesScore));
    }

    private function classifyCommitType(string $message, array $diffs): string
    {
        if (stripos($message, 'bug fix') !== false) {
            return 'Bug Fix';
        }

        $majorChange = array_reduce($diffs, function ($carry, $diff) {
            return $carry || $diff['changes'] > 500;
        }, false);

        return $majorChange ? 'Major Change' : 'Minor or Cosmetic Change';
    }

    public function analyzeCommit(int $commitId): CommitAnalysis
    {
        $commit = $this->gitRepository->getCommitById($commitId);

        $diffs = json_decode($commit->getFiles(), true); // Decode the JSON string into an array
        $quality = $this->calculateCommitQuality($commit->getMessage(), $diffs);
        $commitType = $this->classifyCommitType($commit->getMessage(), $diffs);

        $commitAnalysis = new CommitAnalysis(
            $commitId,
            $quality,
            $commitType
        );

        return $commitAnalysis;
    }

    public function storeCommitAnalysis(CommitAnalysis $commitAnalysis): void
    {
        // Store the analysis result in the database
        $this->gitRepository->storeCommitAnalysis($commitAnalysis);
    }
}
