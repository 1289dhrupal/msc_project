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
    $userId = $gitToken->getUserId();

    $githubService->authenticate($githubToken);
    $repositories = $githubService->fetchRepositories();

    foreach ($repositories as $repository) {
        $update = false;

        $repositoryId = $githubService->getRepository($gitTokenId, $repository['owner']['login'], $repository['name'])?->getId() ?: $githubService->storeRepository($repository, $gitTokenId);
        $commits = $githubService->fetchCommits($repository['name']);

        foreach ($commits as $commit) {
            $commitDetails = $githubService->fetchCommitDetails($commit['sha'], $repository['name']);
            if (!$githubService->getCommit($repositoryId, $commit['sha'])) {
                $commitId = $githubService->storeCommit($commit, $commitDetails, $repositoryId);
                $commitAnalysis = $gitAnalysisService->analyzeCommit($commitId, $commitDetails);
                $gitAnalysisService->storeCommitAnalysis($commitAnalysis);
                $update = true;
            }
        }

        if ($update == true) {
            $githubService->updateRepositoryFetchedAt($repositoryId);
        }
    }
}
