<?php

namespace MscProject\Scripts;

require 'vendor/autoload.php';

use Dotenv\Dotenv;
use MscProject\Services\GithubService;
use MscProject\Routing\Orchestrator;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();


// Create the Orchestrator with the configuration
$githubService = Orchestrator::getInstance()->get(GithubService::class);

$gitTokens = $githubService->fetchGitTokens();

foreach ($gitTokens as $gitToken) {
    // GitHub token and database credentials
    if ($gitToken->getService() !== 'github') {
        continue;
    }

    $githubToken = $gitToken->getToken();
    $gitTokenId = $gitToken->getId();

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
