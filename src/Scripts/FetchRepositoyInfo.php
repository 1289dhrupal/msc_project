<?php

declare(strict_types=1);

namespace MscProject\Scripts;

require 'vendor/autoload.php';

use Dotenv\Dotenv;
use MscProject\Services\GithubService;
use MscProject\Services\GitAnalysisService;
use MscProject\Routing\Orchestrator;
use PDO;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();


// Create the Orchestrator with the configuration
$githubService = Orchestrator::getInstance()->get(GithubService::class);
$gitAnalysisService = Orchestrator::getInstance()->get(GitAnalysisService::class);

$gitTokens = $githubService->fetchGitTokens();

foreach ($gitTokens as $gitToken) {
    // GitHub token and database credentials
    if ($gitToken->getService() !== 'github') {
        continue;
    }

    $githubToken = $gitToken->getToken();
    $gitTokenId = $gitToken->getId();

    $githubService->authenticate($githubToken);
    $repositories = $githubService->fetchRepositories();
    $repositoryIds = $githubService->storeRepositories($repositories, $gitTokenId);

    // Fetch and store commits for each repository
    foreach ($repositoryIds as  $repositoryId => $repoName) {
        $commits = $githubService->fetchCommits($repoName);
        $commitIds = $githubService->storeCommits($commits, $repositoryId);

        // Fetch and store commit details for each commit
        foreach ($commitIds as $commitId => $sha) {
            $commitDetails = $githubService->fetchCommitDetails($sha, $repoName);
            $githubService->storeCommitDetails($commitDetails, $commitId);

            $gitAnalysisService->analyzeCommit($commitId);
        }

        // Update the last fetched at timestamp for the repository
        $githubService->updateRepositoryFetchedAt($repositoryId);
    }
}
