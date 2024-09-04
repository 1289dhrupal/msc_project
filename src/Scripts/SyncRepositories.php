<?php

declare(strict_types=1);

namespace MscProject\Scripts;

require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;
use MscProject\Services\GitHubService;
use MscProject\Services\GitLabService;
use MscProject\Routing\Orchestrator;
use Exception;
use ErrorException;

try {
    // Load environment variables
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();

    // Parse command-line options
    $options = getopt('r:g:');

    // Get the repoId and gitTokenId from options
    $repoId = isset($options['r']) ? (int) $options['r'] : 0;
    $gitTokenId = isset($options['g']) ? (int) $options['g'] : 0;

    // Get the GitHubService instance from the orchestrator
    /** @var GitHubService $githubService */
    $githubService = Orchestrator::getInstance()->get(GitHubService::class);

    // Get the GitLabService instance from the orchestrator
    /** @var GitLabService $gitlabService */
    $gitlabService = Orchestrator::getInstance()->get(GitLabService::class);

    // Synchronize GitHub and GitLab repositories and collect stats
    $stats = [
        'github' => $githubService->syncAll($repoId, $gitTokenId),
        'gitlab' => $gitlabService->syncAll($repoId, $gitTokenId),
    ];

    echo "Synchronization completed successfully.\n" . json_encode($stats, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    throw new ErrorException('Failed to synchronize repositories', 500, E_USER_WARNING, previous: $e);
}
