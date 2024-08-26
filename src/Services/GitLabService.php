<?php

declare(strict_types=1);

namespace MscProject\Services;

require 'vendor/autoload.php';

use ErrorException;
use MscProject\Repositories\GitRepository;
use MscProject\Services\GitProviderService;

class GitLabService extends GitProviderService
{
    private string $gitlabAPIUrl;
    private string $gitToken;
    private const SERVICE = 'gitlab';

    public function __construct(GitRepository $gitRepository, GitTokenService $gitTokenService)
    {
        parent::__construct($gitTokenService, $gitRepository, self::SERVICE);
    }

    public function authenticate(string $gitlabToken, string $url = null): void
    {
        $this->gitToken = $gitlabToken;
        $this->gitlabAPIUrl =  "{$url}/api/v4" ?? 'https://campus.cs.le.ac.uk/gitlab/api/v4';
        $this->username = $this->fetchUsername();
    }

    protected function fetchUsername(): string
    {
        $url = $this->gitlabAPIUrl . "/user";
        $response = $this->makeGetRequest($url);

        return $response['username'];
    }

    public function fetchRepositories(): array
    {
        $url = $this->gitlabAPIUrl . "/projects?owned=true";
        return $this->makeGetRequest($url);
    }

    public function fetchCommits(string $pathWithNamespace, string $branch = 'main'): array
    {
        $url = $this->gitlabAPIUrl . "/projects/{$this->urlEncodeRepoName($pathWithNamespace)}/repository/commits?ref_name={$branch}&with_stats=true";
        $commits = $this->makeGetRequest($url);
        return array_reverse($commits);
    }

    public function fetchCommitDetails(string $sha, string $pathWithNamespace): array
    {
        $url = $this->gitlabAPIUrl . "/projects/{$this->urlEncodeRepoName($pathWithNamespace)}/repository/commits/{$sha}/diff";
        return $this->makeGetRequest($url);
    }

    public function storeRepository(array $repository, int $gitTokenId, int $hookId): int
    {
        $repositoryId = $this->gitRepository->storeRepository($gitTokenId, $repository['name'], $repository['web_url'], $repository['description'] ?? '', $repository['owner']['username'], $repository['default_branch'],  $hookId);
        return $repositoryId;
    }

    public function storeCommit(array $commit, array $commitDetails, int $repositoryId): int
    {
        $commitId = $this->gitRepository->storeCommit(
            $repositoryId,
            $commit['id'],
            $commit['author_email'] ?? $commit['committer_email'],
            $commit['message'],
            $commit['created_at'],
            $commit['stats']['additions'],
            $commit['stats']['deletions'],
            $commit['stats']['total'],
            json_encode($commitDetails['files'])
        );

        return $commitId;
    }

    public function createWebhook(string $pathWithNamespace, array $events = array('push_events'), string $defaultBranch): array
    {
        $hookData = [
            'url' => $_ENV['BASE_URL'] . $_ENV['GITLAB_WEBHOOK_RESPONSE_URL'],
            'push_events' => $events['push_events'] ?? true,
            'push_events_branch_filter' => $defaultBranch,
            'merge_requests_events' => $events['merge_requests_events'] ?? false,
            'tag_push_events' => $events['tag_push_events'] ?? false,
            'issues_events' => $events['issues_events'] ?? false,
            'enable_ssl_verification' => false,
        ];

        if ($_ENV['ENV'] == 'dev') {
            $hookData['url'] = $_ENV['DEV_GITLAB_WEBHOOK_RESPONSE_URL'];
        }

        $url = $this->gitlabAPIUrl . "/projects/{$this->urlEncodeRepoName($pathWithNamespace)}/hooks";

        $response = $this->makePostRequest($url, $hookData);

        return $response;
    }

    public function updateWebhookStatus(string $pathWithNamespace, int $hookId, array $events = array(), int $repositoryId = 0): array
    {
        $hookData = [
            'url' => $_ENV['BASE_URL'] . $_ENV['GITLAB_WEBHOOK_RESPONSE_URL'],
            'push_events' => $events['push_events'] ?? false,
            'merge_requests_events' => $events['merge_requests_events'] ?? false,
            'tag_push_events' => $events['tag_push_events'] ?? false,
            'issues_events' => $events['issues_events'] ?? false,
            'enable_ssl_verification' => false,
            'custom_headers' => array(
                array(
                    'key' => 'X-Custom-Webhook-Id',
                    'value' => $repositoryId,
                ),
            ),
        ];


        if ($_ENV['ENV'] == 'dev') {
            $hookData['url'] = $_ENV['DEV_GITLAB_WEBHOOK_RESPONSE_URL'];
        }

        $url = $this->gitlabAPIUrl . "/projects/{$this->urlEncodeRepoName($pathWithNamespace)}/hooks/{$hookId}";
        return $this->makePutRequest($url, $hookData);
    }

    public function handleEvent(string $event, int $repoId, array $data): void
    {
        $repository = $this->gitRepository->getRepositoryById($repoId);
        if (!$repository || !$repository->isActive()) {
            throw new ErrorException("Repository is not active.");
        }

        $gitToken = $this->gitRepository->getToken($repository['git_token_id']);
        if (!$gitToken || !$gitToken->isActive()) {
            throw new ErrorException("Token is not active.");
        }

        switch ($event) {
            case 'Push Hook':
                parent::handlePushEvent($repository, $gitToken, $data);
                break;
            default:
                break;
        }
    }

    private function makeGetRequest(string $url): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getAuthHeaders());

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true) ?? [];
    }

    private function makePostRequest(string $url, array $data): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($this->getAuthHeaders(), [
            'Content-Type: application/json',
        ]));

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true) ?? [];
    }

    private function makePutRequest(string $url, array $data): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($this->getAuthHeaders(), [
            'Content-Type: application/json',
        ]));

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true) ?? [];
    }

    private function getAuthHeaders(): array
    {
        return [
            "Private-Token: " . $this->gitToken,
        ];
    }

    private function urlEncodeRepoName(string $pathWithNamespace): string
    {
        return urlencode($pathWithNamespace);
    }
}
