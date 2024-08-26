<?php

declare(strict_types=1);

namespace MscProject\Controllers;

use MscProject\Services\GitHubService;
use MscProject\Services\GitLabService;
use MscProject\Models\Response\Response;
use MscProject\Models\Response\SuccessResponse;

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
        $headers = getallheaders();
        $githubEvent = $headers['X-GitHub-Event'] ?? '';
        $githubHookId = $headers['X-GitHub-Hook-ID'] ?? '';

        $payload = file_get_contents('php://input') ?? array();
        $data = json_decode($payload, true);

        $this->githubService->handleEvent($githubEvent, $githubHookId, $data);
        return new SuccessResponse("GitHub webhook processed successfully.");
    }

    public function handleGitLabWebhook(): void
    {

        $headers = getallheaders();
        $gitlabEvent = $headers['X-Gitlab-Event'] ?? '';
        $gitlabHookId = $headers['X-Custom-Webhook-Id'] ?? '';

        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);

        $this->gitlabService->handleEvent($gitlabEvent, $gitlabHookId, $data);
        new SuccessResponse("GitLab webhook processed successfully.");
    }
}
