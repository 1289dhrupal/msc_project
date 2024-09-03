<?php

declare(strict_types=1);

namespace MscProject\Services;

use DateTime;
use MscProject\Repositories\GitRepository;
use MscProject\Utils;
use Exception;

class GitService
{
    private GitRepository $gitRepository;

    public function __construct(GitRepository $gitRepository)
    {
        $this->gitRepository = $gitRepository;
    }

    public function listRepositories(int $userId = 0, int $gitTokenId = 0, bool $mask = true): array
    {
        $repos = $this->gitRepository->listRepositories($userId, $gitTokenId);
        $repositories = array_map(function ($repo) use ($mask) {
            return [
                'id' => $repo->getId(),
                'git_token_id' => $repo->getGitTokenId(),
                'name' => $repo->getName(),
                'url' => $repo->getUrl(),
                'description' => $repo->getDescription(),
                'owner' => $repo->getOwner(),
                'default_branch' => $repo->getDefaultBranch(),
                'hook_id' => $repo->getHookId(),
                'is_active' => $repo->isActive(),
                'created_at' => $repo->getCreatedAt(),
                'last_fetched_at' => $repo->getLastFetchedAt() ?? 'Never',
            ];
        }, $repos);

        $gitTokens = $this->gitRepository->listTokens($userId, $gitTokenId !== 0 ? "$gitTokenId" : "");
        $gitTokens = array_map(function ($gitToken) use ($mask) {
            $token = $mask ? Utils::maskToken($gitToken->getToken(), 4, 4) : $gitToken->getToken();
            return [
                'id' => $gitToken->getId(),
                'user_id' => $gitToken->getUserId(),
                'is_active' => $gitToken->isActive(),
                'token' => $token,
            ];
        }, $gitTokens);

        return [
            'repositories' => $repositories,
            'git_tokens' => $gitTokens,
        ];
    }

    public function toggleRepository(int $repoId, bool $isActive, int $userId = 0): void
    {
        $this->gitRepository->toggleRepository($repoId, $isActive, $userId);
    }

    public function deleteRepository(int $repoId, int $userId = 0): void
    {
        $repo = $this->gitRepository->getRepositoryById($repoId, $userId);
        if ($repo !== null) {
            // TODO: Delete
            // $this->gitRepository->deleteRepositoriesByTokenId($tokenId);

            $this->gitRepository->deleteRepository($repoId, $userId);
        } else {
            throw new Exception('Repository not found.');
        }
    }

    public function listCommits(int $repoId = 0, int $userId = 0, $includeFiles = false, $slice = 20): array
    {
        $repo = $this->gitRepository->getRepositoryById($repoId, $userId);
        if ($repo === null) {
            throw new Exception('Repository not found');
        }

        $commits = $this->gitRepository->listCommits($repoId, $userId, includeFiles: $includeFiles);
        $commitResponse = array_map(function ($commit) {
            return [
                'id' => $commit->getId(),
                'repository_id' => $commit->getRepositoryId(),
                'sha' => $commit->getSha(),
                'author' => $commit->getAuthor(),
                'message' => $commit->getMessage(),
                'date' => $commit->getDate(),
                'additions' => $commit->getAdditions(),
                'deletions' => $commit->getDeletions(),
                'total' => $commit->getTotal(),
                'files' => array_map(function ($file) {
                    return [
                        'sha' => $file->getSha(),
                        'status' => $file->getStatus(),
                        'additions' => $file->getAdditions(),
                        'deletions' => $file->getDeletions(),
                        'total' => $file->getTotal(),
                        'filename' => $file->getFilename(),
                        'extension' => $file->getExtension(),
                    ];
                }, $commit->getFiles()),
            ];
        }, $commits);

        if ($slice) {
            $commitResponse = array_slice($commitResponse, 0, $slice);
        }

        $repos = $this->gitRepository->listRepositories($userId, repoIds: $repoId !== 0 ? "$repoId" : "");
        $repoResponse = array_map(function ($repo) {
            return [
                'id' => $repo->getId(),
                'git_token_id' => $repo->getGitTokenId(),
                'name' => $repo->getName(),
                'url' => $repo->getUrl(),
                'owner' => $repo->getOwner(),
                'default_branch' => $repo->getDefaultBranch(),
                'hook_id' => $repo->getHookId(),
                'is_active' => $repo->isActive(),
            ];
        }, $repos);

        return ["commits" => $commitResponse, "repositories" => $repoResponse];
    }

    public function getCommitStats(int $commitId, int $userId = 0): array
    {
        $commit = $this->gitRepository->getCommitById($commitId);
        if ($commit === null) {
            throw new Exception('Commit not found');
        }

        $files = $commit->getFiles();
        $response = [
            'id' => $commit->getId(),
            'repository_id' => $commit->getRepositoryId(),
            'sha' => $commit->getSha(),
            'author' => $commit->getAuthor(),
            'message' => $commit->getMessage(),
            'date' => $commit->getDate(),
            'additions' => $commit->getAdditions(),
            'deletions' => $commit->getDeletions(),
            'total' => $commit->getTotal(),
            'number_of_comment_lines' => $commit->getNumberOfCommentLines(),
            'commit_changes_quality_score' => $commit->getCommitChangesQualityScore(),
            'commit_message_quality_score' => $commit->getCommitMessageQualityScore(),
            'files' => array_map(function ($file) {
                return [
                    'sha' => $file->getSha(),
                    'status' => $file->getStatus(),
                    'additions' => $file->getAdditions(),
                    'deletions' => $file->getDeletions(),
                    'total' => $file->getTotal(),
                    'filename' => $file->getFilename(),
                    'extension' => $file->getExtension(),
                ];
            }, $files),
        ];

        return $response;
    }

    public function getStats(int $repoId, int $userId = 0): array
    {
        $repo = $this->gitRepository->getRepositoryById($repoId, $userId);
        if ($repo === null) {
            throw new Exception('Repository not found');
        }

        $commits = $this->gitRepository->listCommits($repoId, $userId, 'ASC');

        $response = [
            'churn_rates' => [],
            'contribution' => [],
            'weekly_stats' => array_fill(0, 53, []),
            'extension_stats' => [],
        ];

        $totalLines = 0;
        $previousCommitDate = null;

        foreach ($commits as $commit) {
            $additions = $commit->getAdditions();
            $deletions = $commit->getDeletions();
            $totalLines += ($additions - $deletions);

            $churnRate = $totalLines == 0 ? 0 : ($additions + $deletions) / $totalLines * 100;

            $currentCommitDate = strtotime($commit->getDate());
            $leadTime = $previousCommitDate ? round(($currentCommitDate - $previousCommitDate) / 3600 / 24) : 0;
            $previousCommitDate = $currentCommitDate;

            $response['churn_rates'][] = [
                'churn_rate' => $churnRate,
                'lead_time' => $leadTime,
                'additions' => $additions,
                'deletions' => -$deletions,
                'total' => $totalLines,
                'score' => ($commit->getCommitChangesQualityScore() * 0.90) + ($commit->getCommitMessageQualityScore() * 0.10),
            ];

            $author = $commit->getAuthor();
            if (!isset($response['contribution'][$author])) {
                $response['contribution'][$author] = [
                    'count' => 0,
                    'total' => 0,
                    'additions' => 0,
                    'deletions' => 0,
                ];
            }

            $response['contribution'][$author]['count']++;
            $response['contribution'][$author]['total'] += $commit->getTotal();
            $response['contribution'][$author]['additions'] += $additions;
            $response['contribution'][$author]['deletions'] += $deletions;

            $date = new DateTime($commit->getDate());
            $year = (int) $date->format('Y');
            $month = (int) $date->format('m');
            $hour = (int) $date->format('H');
            $week = (int) $date->format('W');

            $response['weekly_stats'][$week][$author] = ($response['weekly_stats'][$week][$author] ?? 0) + 1;

            foreach ($commit->getFiles() as $file) {
                $extension = $file->getExtension() ?: 'unknown';
                if (!isset($response['extension_stats'][$extension])) {
                    $response['extension_stats'][$extension] = [];
                }

                if (!isset($response['extension_stats'][$extension][$author])) {
                    $response['extension_stats'][$extension][$author] = [
                        'lines' => 0,
                        'files' => [],
                    ];
                }

                $response['extension_stats'][$extension][$author]['lines'] += $file->getAdditions() - $file->getDeletions();
                $response['extension_stats'][$extension][$author]['files'][] = $file->getFilename();
                $response['extension_stats'][$extension][$author]['files'] = array_unique($response['extension_stats'][$extension][$author]['files']);
            }
        }

        foreach ($response['extension_stats'] as $extension => $stats) {
            foreach ($stats as $author => $stat) {
                $response['extension_stats'][$extension][$author]['files'] = count($stat['files']);
            }
        }

        return $response;
    }
}
