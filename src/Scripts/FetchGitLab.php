<?php

declare(strict_types=1);

namespace MscProject\Scripts;

require 'vendor/autoload.php';

use Dotenv\Dotenv;
use MscProject\Services\GitLabService;
use MscProject\Services\GitAnalysisService;
use MscProject\Routing\Orchestrator;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Create the Orchestrator with the configuration

/**
 * @var GitLabService
 */
$gitLabService = Orchestrator::getInstance()->get(GitLabService::class);
/**
 * @var GitAnalysisService
 */
$gitAnalysisService = Orchestrator::getInstance()->get(GitAnalysisService::class);

$gitTokens = $gitLabService->fetchGitTokens();

foreach ($gitTokens as $gitToken) {

    if ($gitToken['is_disabled']) {
        continue;
    }


    // GitLab token and database credentials
    $gitLabService->authenticate($gitToken['token']);
    $repositories = $gitLabService->fetchRepositories();

    foreach ($repositories as $repository) {
        $repo = $gitLabService->getRepository($gitToken['id'], $repository['namespace']['full_path'], $repository['name']);

        if ($repo && $repo['is_disabled']) {
            continue;
        }

        $repositoryId = $repo['id'] ?? $gitLabService->storeRepository($repository, $gitToken['id']);
        $commits = $gitLabService->fetchCommits($repository['path_with_namespace']);

        foreach ($commits as $commit) {
            $commitDetails = [];
            $commitDetails['files'] = $gitLabService->fetchCommitDetails($commit['id'], $repository['path_with_namespace']);
            if (!$gitLabService->getCommit($repositoryId, $commit['id'])) {

                $commitDetails['files'] = array_map(fn($row) => [
                    'sha' => substr(hash('sha256', $row['new_path']), 0, 7),
                    'filename' => $row['new_path'] ?? $row['old_path'],
                    'status' => $row['new_file'] ? 'added' : ($row['deleted_file'] ? 'deleted' : ($row['renamed_file'] ? 'renamed' : 'modified')),
                    'patch' => $row['diff'],
                ], $commitDetails['files']);

                $commitAnalysis = $gitAnalysisService->analyzeCommit($commitDetails['files'], $commit['message']);
                $commitDetails['files'] = ["files" => $commitAnalysis['files'], "stats" => array_merge($commit['stats'] ?? [], $commitAnalysis['stats'])];
                $commitDetails['files']["files"] = array_map(fn($row) => [
                    'sha' => $row['sha'],
                    'filename' => $row['filename'],
                    'status' => $row['status'],
                    'additions' => $row['additions'] ?? null,
                    'deletions' => $row['deletions'] ?? null,
                    'changes' => $row['changes'] ?? null,
                ], $commitDetails['files']["files"]);


                $commitId = $gitLabService->storeCommit($commit, $commitDetails, $repositoryId);
            }
        }

        $gitLabService->updateRepositoryFetchedAt($repositoryId);
        // TODO: GITTOKEN SERVICE
    }
}
