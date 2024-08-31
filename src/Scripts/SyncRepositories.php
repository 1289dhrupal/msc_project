<?php

declare(strict_types=1);

namespace MscProject\Scripts;

require 'vendor/autoload.php';

use Dotenv\Dotenv;
use MscProject\Services\GithubService;
use MscProject\Services\GitLabService;
use MscProject\Routing\Orchestrator;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Create the Orchestrator with the configuration

/**
 * @var GitHubService
 */
$githubService = Orchestrator::getInstance()->get(GithubService::class);

/**
 * @var GitLabService
 */
$gitlabService = Orchestrator::getInstance()->get(GitLabService::class);

$stats[] = [
    // 'github' => $githubService->syncAll(),
    'gitlab' => $gitlabService->syncAll(),
];
