<?php

declare(strict_types=1);

namespace MscProject\Controllers;

use MscProject\Services\GitHubService;
use MscProject\Services\GitLabService;
use MscProject\Models\Response\Response;
use MscProject\Models\Response\SuccessResponse;
use Exception;
use ErrorException;

class WebHookController
{
    private GitHubService $githubService;
    private GitLabService $gitlabService;

    public function __construct(GitHubService $githubService, GitLabService $gitlabService)
    {
        $this->githubService = $githubService;
        $this->gitlabService = $gitlabService;
    }

    public function handleGitHubWebhook(): Response
    {
        try {
            $headers = getallheaders();
            $githubEvent = $headers['X-GitHub-Event'] ?? '';
            $githubHookId = (int)($headers['X-GitHub-Hook-ID'] ?? '');

            $payload = file_get_contents('php://input') ?? '[]';
            $data = json_decode($payload, true);

            $this->githubService->handleEvent($githubEvent, $githubHookId, $data);

            return new SuccessResponse("GitHub webhook processed successfully.");
        } catch (Exception $e) {
            throw new ErrorException('Failed to process GitHub webhook', 500, E_USER_WARNING, previous: $e);
        }
    }

    public function handleGitLabWebhook(): Response
    {
        try {
            $headers = getallheaders();
            $gitlabEvent = $headers['X-Gitlab-Event'] ?? '';
            $gitlabHookId = (int)($headers['X-Custom-Webhook-Id'] ?? '');

            $payload = file_get_contents('php://input') ?? '[]';
            $data = json_decode($payload, true);

            $this->gitlabService->handleEvent($gitlabEvent, $gitlabHookId, $data);

            return new SuccessResponse("GitLab webhook processed successfully.");
        } catch (Exception $e) {
            throw new ErrorException('Failed to process GitLab webhook', 500, E_USER_WARNING, previous: $e);
        }
    }
}
