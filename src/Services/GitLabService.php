<?php

declare(strict_types=1);

namespace MscProject\Services;

use ErrorException;
use MscProject\Repositories\GitRepository;
use MscProject\Repositories\UserRepository;
use MscProject\Services\GitProviderService;

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

    public function createWebhook(string $pathWithNamespace, string $defaultBranch): array
    {
        $hookData = [
            'name' => 'web',
            'url' => $_ENV['BASE_URL'] . $_ENV['GITLAB_WEBHOOK_RESPONSE_URL'],
            'push_events' => true,
            'push_events_branch_filter' => $defaultBranch,
            'merge_requests_events' => false,
            'tag_push_events' => false,
            'issues_events' => false,
            'enable_ssl_verification' => false,
        ];

        if ($_ENV['ENV'] == 'dev') {
            $hookData['url'] = $_ENV['DEV_GITLAB_WEBHOOK_RESPONSE_URL'];
        }

        $url = $this->gitlabAPIUrl . "/projects/{$this->urlEncodeRepoName($pathWithNamespace)}/hooks";

        $response = $this->makePostRequest($url, $hookData);

        return $response;
    }

    public function updateWebhookStatus(string $pathWithNamespace, int $hookId, bool $status = false, int $repositoryId = 0): array
    {
        $hookData = [
            'url' => $_ENV['BASE_URL'] . $_ENV['GITLAB_WEBHOOK_RESPONSE_URL'],
            'push_events' => $status,
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
                break;
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
            'sha' => substr(hash('sha256', $row['new_path']), 0, 7),
            'filename' => $row['new_path'] ?? $row['old_path'],
            'status' => $row['new_file'] ? 'added' : ($row['deleted_file'] ? 'deleted' : ($row['renamed_file'] ? 'renamed' : 'modified')),
            'patch' => $row['diff'],
        ], $this->gitAnalysisService->getChangeStat($row['diff'])), $commitDetails['files']);

        $commitAnalysis = $this->gitAnalysisService->analyzeCommit($commitDetails['files'], $commit['message']);
        $commitDetails['files'] = ["files" => $commitAnalysis['files'], "stats" => array_merge($commit['stats'] ?? [], $commitAnalysis['stats'])];
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
        $url = $this->gitlabAPIUrl . "/projects/{$this->urlEncodeRepoName($repoName)}/hooks";
        return $this->makeGetRequest($url);
    }

    public function getCommitSummaries(array $commit, array $commitDetails): string
    {
        // Format the commit summary string
        return sprintf(
            "Commit (%s): '%s' by '%s' on '%s'\n   Additions: %d, Deletions: %d, Total: %d\n",
            $commit['id'],
            trim(substr($commit['message'], 0, 10)) . '...',
            $commit['author_email'] ?? $commit['committer_email'],
            $commit['created_at'],
            $commit['stats']['additions'],
            $commit['stats']['deletions'],
            $commit['stats']['total']
        );
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
