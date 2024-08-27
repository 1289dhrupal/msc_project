<?php

declare(strict_types=1);

namespace MscProject\Scripts;

require 'vendor/autoload.php';

use Dotenv\Dotenv;
use MscProject\Services\ActivityService;
use MscProject\Routing\Orchestrator;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

/**
 * @var ActivityService
 */
$activityService = Orchestrator::getInstance()->get(ActivityService::class);

// Generate reports
$activityService->generateReports();
