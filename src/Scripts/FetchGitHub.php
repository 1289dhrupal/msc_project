<?php

declare(strict_types=1);

namespace MscProject\Scripts;

require 'vendor/autoload.php';

use Dotenv\Dotenv;
use MscProject\Services\GithubService;
use MscProject\Services\GitAnalysisService;
use MscProject\Routing\Orchestrator;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Create the Orchestrator with the configuration

/**
 * @var GitHubService
 */
$githubService = Orchestrator::getInstance()->get(GithubService::class);
/**
 * @var GitAnalysisService
 */
$gitAnalysisService = Orchestrator::getInstance()->get(GitAnalysisService::class);

$gitTokens = $githubService->fetchGitTokens();

foreach ($gitTokens as $gitToken) {

    if (!$gitToken['is_active']) {
        continue;
    }

    // GitHub token and database credentials
    $githubService->authenticate($gitToken['token']);
    $repositories = $githubService->fetchRepositories();

    foreach ($repositories as $repository) {
        $repo = $githubService->getRepository($gitToken['id'], $repository['owner']['login'], $repository['name']);

        if ($repo && !$repo['is_active']) {
            continue;
        }

        $repositoryId = $repo['id'] ?? 0;
        if (!$repositoryId) {
            $hook = $_ENV == 'dev' ? -1 : $githubService->createWebhook($repository['name']);
            $repositoryId = $githubService->storeRepository($repository, $gitToken['id'], $hook['id']);
        }

        $commits = $githubService->fetchCommits($repository['name']);

        foreach ($commits as $commit) {
            $commitDetails = $githubService->fetchCommitDetails($commit['sha'], $repository['name']);
            if (!$githubService->getCommit($repositoryId, $commit['sha'])) {
                $commitDetails['files'] = array_map(fn($row) => [
                    'sha' => substr($row['sha'], 0, 7),
                    'filename' => $row['filename'],
                    'status' => $row['status'],
                    'additions' => $row['additions'],
                    'deletions' => $row['deletions'],
                    'changes' => $row['changes'],
                    'patch' => $row['patch'] ?? null,
                ], $commitDetails['files']);

                $commitAnalysis = $gitAnalysisService->analyzeCommit($commitDetails['files'], $commitDetails['commit']['message']);
                $commitDetails['files'] = ["files" => $commitAnalysis['files'], "stats" => array_merge($commitDetails['stats'], $commitAnalysis['stats'])];
                $commitDetails['files']["files"] = array_map(fn($row) => [
                    'sha' => substr($row['sha'], 0, 7),
                    'filename' => $row['filename'],
                    'status' => $row['status'],
                    'additions' => $row['additions'],
                    'deletions' => $row['deletions'],
                    'changes' => $row['changes'],
                ], $commitDetails['files']["files"]);

                $commitId = $githubService->storeCommit($commit, $commitDetails, $repositoryId);
            }
        }

        $githubService->updateRepositoryFetchedAt($repositoryId);
        // TODO: GITTOKEN SERVICE
    }
}
