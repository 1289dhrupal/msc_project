<?php

namespace MscProject\Scripts;

require 'vendor/autoload.php';

use Dotenv\Dotenv;
use MscProject\Services\GithubService;
use MscProject\Routing\Orchestrator;
use PDO;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// GitHub token and database credentials
$githubToken = "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";
$gitTokenId = 1;

// Create the Orchestrator with the configuration
$githubService = Orchestrator::getInstance()->get(GithubService::class);

// Assume you have multiple tokens to process
$tokens = [$githubToken, /* other tokens */];

foreach ($tokens as $token) {
    $githubService->authenticate($token);
    $repositories = $githubService->fetchRepositories();
    $repositoryIds = $githubService->storeRepositories($repositories, $gitTokenId);

    // Fetch and store commits for each repository
    foreach ($repositoryIds as $repoName => $repositoryId) {
        $commits = $githubService->fetchCommits($repoName);
        $commitIds = $githubService->storeCommits($commits, $repositoryId);

        // Fetch and store commit details for each commit
        foreach ($commitIds as $sha => $commitId) {
            $commitDetails = $githubService->fetchCommitDetails($sha, $repoName);
            $githubService->storeCommitDetails($commitDetails, $commitId);
        }

        // Update the last fetched at timestamp for the repository
        $githubService->updateRepositoryFetchedAt($repositoryId);
    }
}
