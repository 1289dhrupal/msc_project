<?php

declare(strict_types=1);

namespace MscProject\Scripts;

require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;
use MscProject\Services\ActivityService;
use MscProject\Routing\Orchestrator;
use Exception;
use ErrorException;

try {
    // Load environment variables
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();

    // Get the ActivityService instance from the orchestrator
    /** @var ActivityService $activityService */
    $activityService = Orchestrator::getInstance()->get(ActivityService::class);

    // Generate reports
    $activityService->generateReports();

    echo "Reports generated successfully.\n";
} catch (Exception $e) {
    throw new ErrorException('Failed to generate reports', 500, E_USER_WARNING, previous: $e);
}
