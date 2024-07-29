<?php

declare(strict_types=1);

namespace MscProject\Scripts;

require 'vendor/autoload.php';

use Dotenv\Dotenv;
use MscProject\Services\AiIntegrationService;
use MscProject\Routing\Orchestrator;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Create the Orchestrator with the configuration
$aiIntegrationService = Orchestrator::getInstance()->get(AiIntegrationService::class);

// Read commit details from sample_input.json
$commit_details_json = file_get_contents(__DIR__ . '/sample_input.json');
$commit_details = json_decode($commit_details_json, true);

// Generate AI report
$response = $aiIntegrationService->generateAiReport($commit_details);

print_r($response);
