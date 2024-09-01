<?php

declare(strict_types=1);

namespace MscProject\Services;

use MscProject\Repositories\GitRepository;
use MscProject\Repositories\UserRepository;
use ErrorException;

class GitLabService extends GitProviderService
{
    private string $gitlabAPIUrl;
    private string $gitToken;
    private const SERVICE = 'gitlab';

    public function __construct(GitRepository $gitRepository, GitTokenService $gitTokenService, GitAnalysisService $gitAnalysisService, UserRepository $userRepository)
    {
        parent::__construct($gitTokenService, $gitRepository, $gitAnalysisService, $userRepository, self::SERVICE);
    }

    public function authenticate(string $gitlabToken, string $url = null): void
    {
        $this->gitToken = $gitlabToken;
        $this->gitlabAPIUrl = $url ? "{$url}/api/v4" : 'https://campus.cs.le.ac.uk/gitlab/api/v4';
        $this->username = $this->fetchUsername();
    }

    protected function fetchUsername(): string
    {
        $url = "{$this->gitlabAPIUrl}/user";
        $response = $this->makeGetRequest($url);

        return $response['username'] ?? '';
    }

    public function fetchRepositories(): array
    {
        $url = "{$this->gitlabAPIUrl}/projects?owned=true";
        return $this->makeGetRequest($url);
    }

    public function fetchCommits(string $pathWithNamespace, string $branch = 'main'): array
    {
        $url = "{$this->gitlabAPIUrl}/projects/{$this->urlEncodeRepoName($pathWithNamespace)}/repository/commits?ref_name={$branch}&with_stats=true";
        $commits = $this->makeGetRequest($url);

        return array_reverse($commits);  // Ensure commits are in the correct order
    }

    public function fetchCommitDetails(string $sha, string $pathWithNamespace): array
    {
        $url = "{$this->gitlabAPIUrl}/projects/{$this->urlEncodeRepoName($pathWithNamespace)}/repository/commits/{$sha}/diff";
        return $this->makeGetRequest($url);
    }

    public function storeRepository(array $repository, int $gitTokenId, int $hookId): int
    {
        return $this->gitRepository->storeRepository(
            $gitTokenId,
            $repository['name'],
            $repository['web_url'],
            $repository['description'] ?? '',
            $repository['owner']['username'],
            $repository['default_branch'],
            $hookId
        );
    }

    public function storeCommit(array $commit, array $commitDetails, int $repositoryId): int
    {
        return $this->gitRepository->storeCommit(
            $repositoryId,
            $commit['id'],
            $commit['author_email'] ?? $commit['committer_email'],
            $commit['message'],
            $commit['created_at'],
            $commit['stats']['additions'] ?? 0,
            $commit['stats']['deletions'] ?? 0,
            $commit['stats']['total'] ?? 0,
            $commitDetails['files']['stats']['number_of_comment_lines'] ?? 0,
            $commitDetails['files']['stats']['commit_changes_quality_score'] ?? 0,
            $commitDetails['files']['stats']['commit_message_quality_score'] ?? 0,
            $commitDetails['files']['files']
        );
    }

    public function createWebhook(string $pathWithNamespace, string $defaultBranch): array
    {
        $hookData = [
            'name' => 'web',
            'url' => $this->getWebhookUrl(),
            'push_events' => true,
            'push_events_branch_filter' => $defaultBranch,
            'enable_ssl_verification' => false,
        ];

        $url = "{$this->gitlabAPIUrl}/projects/{$this->urlEncodeRepoName($pathWithNamespace)}/hooks";

        return $this->makePostRequest($url, $hookData);
    }

    public function updateWebhookStatus(string $pathWithNamespace, int $hookId, bool $status = false, int $repositoryId = 0): array
    {
        $hookData = [
            'url' => $this->getWebhookUrl(),
            'push_events' => $status,
            'custom_headers' => [[
                'key' => 'X-Custom-Webhook-Id',
                'value' => $repositoryId,
            ]]
        ];

        $url = "{$this->gitlabAPIUrl}/projects/{$this->urlEncodeRepoName($pathWithNamespace)}/hooks/{$hookId}";
        return $this->makePutRequest($url, $hookData);
    }

    public function handleEvent(string $event, int $repoId, array $data): void
    {
        $repository = $this->gitRepository->getRepositoryById($repoId);
        if (!$repository || !$repository->isActive()) {
            throw new ErrorException("Repository is not active.", 200);
        }

        $gitToken = $this->gitRepository->getToken($repository->getGitTokenId());
        if (!$gitToken || !$gitToken->isActive()) {
            throw new ErrorException("Token is not active.", 200);
        }

        $currentBranch = explode('/', $data['ref'])[2] ?? '';
        if ($repository->getDefaultBranch() !== $currentBranch) {
            throw new ErrorException("Tracking branch does not match.", 200);
        }

        switch ($event) {
            case 'Push Hook':
                parent::handlePushEvent($repository, $gitToken, $data['project']['path_with_namespace']);
                break;
            default:
                throw new ErrorException("Event not supported.", 200);
        }
    }

    public function getRepositoryOwner(array $repository): string
    {
        return $repository['namespace']['full_path'];
    }

    public function getRepositoryPath(array $repository): string
    {
        return $repository['path_with_namespace'];
    }

    public function getCommitIdentifier(array $commit): string
    {
        return $commit['id'];
    }

    public function processCommit(array $commit, array $commitDetails): array
    {
        $commitDetails = ['files' => $commitDetails];

        $commitDetails['files'] = array_map(fn($row) => array_merge([
            'sha' => substr(hash('sha256', $row['new_path'] ?? $row['old_path']), 0, 7),
            'filename' => $row['new_path'] ?? $row['old_path'],
            'status' => $row['new_file'] ? 'added' : ($row['deleted_file'] ? 'deleted' : ($row['renamed_file'] ? 'renamed' : 'modified')),
            'patch' => $row['diff'] ?? null,
        ], $this->gitAnalysisService->getChangeStat($row['diff'] ?? '')), $commitDetails['files']);

        $commitAnalysis = $this->gitAnalysisService->analyzeCommit($commitDetails['files'], $commit['message']);
        $commitDetails['files'] = [
            "files" => $commitAnalysis['files'],
            "stats" => array_merge($commit['stats'] ?? [], $commitAnalysis['stats'])
        ];
        $commitDetails['files']["files"] = array_map(fn($row) => [
            'sha' => $row['sha'],
            'filename' => $row['filename'],
            'status' => $row['status'],
            'additions' => $row['additions'] ?? null,
            'deletions' => $row['deletions'] ?? null,
            'changes' => $row['changes'] ?? null,
        ], $commitDetails['files']["files"]);

        return $commitDetails;
    }

    public function listWebhooks(string $repoName): array
    {
        $url = "{$this->gitlabAPIUrl}/projects/{$this->urlEncodeRepoName($repoName)}/hooks";
        return $this->makeGetRequest($url);
    }

    public function getCommitSummaries(array $commit, array $commitDetails): string
    {
        return sprintf(
            "Commit (%s): '%s' by '%s' on '%s'\n   Additions: %d, Deletions: %d, Total: %d\n",
            $commit['id'],
            trim(substr($commit['message'], 0, 10)) . '...',
            $commit['author_email'] ?? $commit['committer_email'],
            $commit['created_at'],
            $commitDetails['stats']['additions'] ?? 0,
            $commitDetails['stats']['deletions'] ?? 0,
            $commitDetails['stats']['total'] ?? 0
        );
    }

    private function makeGetRequest(string $url): array
    {
        $response = $this->executeCurlRequest($url, 'GET');

        return $this->handleApiResponse($response);
    }

    private function makePostRequest(string $url, array $data): array
    {
        $response = $this->executeCurlRequest($url, 'POST', $data);

        return $this->handleApiResponse($response);
    }

    private function makePutRequest(string $url, array $data): array
    {
        $response = $this->executeCurlRequest($url, 'PUT', $data);

        return $this->handleApiResponse($response);
    }

    private function executeCurlRequest(string $url, string $method, array $data = []): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($this->getAuthHeaders(), [
            'Content-Type: application/json',
        ]));

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new ErrorException("Curl request failed: $error", 500);
        }

        curl_close($ch);

        return $response;
    }

    private function handleApiResponse(string $response): array
    {
        $decodedResponse = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ErrorException('Failed to decode JSON response: ' . json_last_error_msg(), 500);
        }

        return $decodedResponse;
    }

    private function getAuthHeaders(): array
    {
        return [
            "Private-Token: {$this->gitToken}",
        ];
    }

    private function getWebhookUrl(): string
    {
        return $_ENV['ENV'] === 'dev' ? $_ENV['DEV_GITLAB_WEBHOOK_RESPONSE_URL'] : $_ENV['BASE_URL'] . $_ENV['GITLAB_WEBHOOK_RESPONSE_URL'];
    }

    private function urlEncodeRepoName(string $pathWithNamespace): string
    {
        return urlencode($pathWithNamespace);
    }
}
