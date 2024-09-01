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

    // Get the GitHubService instance from the orchestrator
    /** @var GitHubService $githubService */
    $githubService = Orchestrator::getInstance()->get(GitHubService::class);

    // Get the GitLabService instance from the orchestrator
    /** @var GitLabService $gitlabService */
    $gitlabService = Orchestrator::getInstance()->get(GitLabService::class);

    // Synchronize GitHub and GitLab repositories and collect stats
    $stats = [
        'github' => $githubService->syncAll(),
        'gitlab' => $gitlabService->syncAll(),
    ];

    echo "Synchronization completed successfully.\n" . json_encode($stats, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    throw new ErrorException('Failed to synchronize repositories', 500, E_USER_WARNING, previous: $e);
}
