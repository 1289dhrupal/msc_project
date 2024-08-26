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
$gitService = Orchestrator::getInstance()->get(GithubService::class);
$gitService->fetchAll();


/**
 * @var GitLabService
 */
$gitService = Orchestrator::getInstance()->get(GitLabService::class);
$gitService->fetchAll();
