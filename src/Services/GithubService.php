<?php

declare(strict_types=1);

namespace MscProject\Services;

use Github\Client;
use Github\AuthMethod;
use MscProject\Repositories\GitRepository;
use MscProject\Repositories\UserRepository;
use ErrorException;

class GithubService extends GitProviderService
{
    private Client $client;
    private const SERVICE = 'github';

    public function __construct(
        GitRepository $gitRepository,
        GitTokenService $gitTokenService,
        GitAnalysisService $gitAnalysisService,
        UserRepository $userRepository
    ) {
        $this->client = new Client();
        parent::__construct($gitTokenService, $gitRepository, $gitAnalysisService, $userRepository, self::SERVICE);
    }

    public function authenticate(string $githubToken, string $url = null): void
    {
        $this->client->authenticate($githubToken, AuthMethod::ACCESS_TOKEN);
        $this->username = $this->fetchUsername();
    }

    protected function fetchUsername(): string
    {
        $user = $this->client->currentUser()->show();
        return $user['login'];
    }

    public function fetchRepositories(): array
    {
        return $this->client->user()->repositories($this->username);
    }

    public function fetchCommits(string $repoName, string $branch = 'main'): array
    {
        $allCommits = [];
        $page = 1;

        do {
            $commits = $this->client->repo()->commits()->all($this->username, $repoName, [
                'sha' => $branch,
                'page' => $page,
                'per_page' => 100, // Maximum allowed by GitHub API
            ]);

            $allCommits = array_merge($allCommits, $commits);
            $page++;
        } while (count($commits) > 0);

        return $allCommits;
    }

    public function fetchCommitDetails(string $sha, string $repoName): array
    {
        return $this->client->repo()->commits()->show($this->username, $repoName, $sha);
    }

    public function storeRepository(array $repository, int $gitTokenId, int $hookId): int
    {
        return $this->gitRepository->storeRepository(
            $gitTokenId,
            $repository['name'],
            $repository['html_url'],
            $repository['description'] ?? '',
            $repository['owner']['login'],
            $repository['default_branch'],
            $hookId
        );
    }

    public function storeCommit(array $commit, array $commitDetails, int $repositoryId): int
    {
        return $this->gitRepository->storeCommit(
            $repositoryId,
            $commit['sha'],
            $commit['author']['login'] ?? $commit['commit']['author']['email'],
            $commit['commit']['message'],
            $commit['commit']['author']['date'],
            $commitDetails['stats']['additions'],
            $commitDetails['stats']['deletions'],
            $commitDetails['stats']['total'],
            $commitDetails['files']['stats']['number_of_comment_lines'] ?? 0,
            $commitDetails['files']['stats']['commit_changes_quality_score'] ?? 0,
            $commitDetails['files']['stats']['commit_message_quality_score'] ?? 0,
            $commitDetails['files']['files']
        );
    }

    public function createWebhook(string $repoName, string $defaultBranch = null): array
    {
        $hookData = [
            'name' => 'web',
            'active' => true,
            'events' => ['push'],
            'config' => [
                'url' => $this->getWebhookUrl(),
                'content_type' => 'json',
                'insecure_ssl' => '1',
            ],
        ];

        return $this->client->repo()->hooks()->create($this->username, $repoName, $hookData);
    }

    public function listWebhooks(string $repoName): array
    {
        return $this->client->repo()->hooks()->all($this->username, $repoName);
    }

    public function updateWebhookStatus(string $repoName, int $hookId, bool $status = false, int $repositoryId = 0): array
    {
        $hookData = [
            'active' => $status,
            'config' => [
                'url' => $this->getWebhookUrl(),
            ],
        ];

        return $this->client->repo()->hooks()->update($this->username, $repoName, $hookId, $hookData);
    }

    private function getWebhookUrl(): string
    {
        return $_ENV['ENV'] === 'dev' ? $_ENV['DEV_GITHUB_WEBHOOK_RESPONSE_URL'] : $_ENV['BASE_URL'] . $_ENV['GITHUB_WEBHOOK_RESPONSE_URL'];
    }

    public function handleEvent(string $event, int $hookId, array $data): void
    {
        $repository = $this->gitRepository->getRepositoryByHookId($hookId);
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
            case 'push':
                parent::handlePushEvent($repository, $gitToken, $data['repository']['name']);
                break;
            default:
                throw new ErrorException("Event not supported.", 200);
        }
    }

    public function getRepositoryOwner(array $repository): string
    {
        return $repository['owner']['login'];
    }

    public function getRepositoryPath(array $repository): string
    {
        return $repository['name'];
    }

    public function getCommitIdentifier(array $commit): string
    {
        return $commit['sha'];
    }

    public function processCommit(array $commit, array $commitDetails): array
    {
        $commitDetails['files'] = array_map(fn($row) => [
            'sha' => substr($row['sha'], 0, 7),
            'filename' => $row['filename'],
            'status' => $row['status'],
            'additions' => $row['additions'],
            'deletions' => $row['deletions'],
            'changes' => $row['changes'],
            'patch' => $row['patch'] ?? null,
        ], $commitDetails['files']);

        $commitAnalysis = $this->gitAnalysisService->analyzeCommit($commitDetails['files'], $commitDetails['commit']['message']);
        $commitDetails['files'] = [
            "files" => $commitAnalysis['files'],
            "stats" => array_merge($commitDetails['stats'], $commitAnalysis['stats'])
        ];

        $commitDetails['files']["files"] = array_map(fn($row) => [
            'sha' => substr($row['sha'], 0, 7),
            'filename' => $row['filename'],
            'status' => $row['status'],
            'additions' => $row['additions'],
            'deletions' => $row['deletions'],
            'changes' => $row['changes'],
        ], $commitDetails['files']["files"]);

        return $commitDetails;
    }

    public function getCommitSummaries(array $commit, array $commitDetails): string
    {
        return sprintf(
            "Commit (%s): '%s' by '%s' on '%s'\n   Additions: %d, Deletions: %d, Total: %d\n",
            $commit['sha'],
            trim(substr($commit['commit']['message'], 0, 10)) . '...',
            $commit['author']['login'] ?? $commit['commit']['author']['email'],
            $commit['commit']['author']['date'],
            $commitDetails['stats']['additions'],
            $commitDetails['stats']['deletions'],
            $commitDetails['stats']['total']
        );
    }
}
