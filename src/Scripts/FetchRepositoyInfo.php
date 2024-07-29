<?php

declare(strict_types=1);

namespace MscProject\Scripts;

require 'vendor/autoload.php';

use Dotenv\Dotenv;
use MscProject\Services\GithubService;
use MscProject\Services\GitAnalysisService;
use MscProject\Services\AiIntegrationService;
use MscProject\Routing\Orchestrator;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Create the Orchestrator with the configuration
$githubService = Orchestrator::getInstance()->get(GithubService::class);
$gitAnalysisService = Orchestrator::getInstance()->get(GitAnalysisService::class);
$aiIntegrationService = Orchestrator::getInstance()->get(AiIntegrationService::class);

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
                $commitId = $githubService->storeCommit($commit, $repositoryId, $commitDetails);
                $commitAnalysis = $gitAnalysisService->analyzeCommit($commitId);
                $gitAnalysisService->storeCommitAnalysis($commitAnalysis);
                $update = true;
            }
        }

        if ($update == true) {
            $githubService->updateRepositoryFetchedAt($repositoryId);
        }
    }
}


// Read commit details from sample_input.json
$commit_details_json = file_get_contents(__DIR__ . '/sample_input.json');
$commit_details = json_decode($commit_details_json, true);

// Generate AI report
$response = $aiIntegrationService->generateAiReport($commit_details);
print_r($response);
