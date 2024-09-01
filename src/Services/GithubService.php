<?php

declare(strict_types=1);

namespace MscProject\Services;

use ErrorException;
use Github\Client;
use Github\AuthMethod;
use MscProject\Repositories\GitRepository;
use MscProject\Repositories\UserRepository;

class GithubService extends GitProviderService
{
    private Client $client;
    private const SERVICE = 'github';

    public function __construct(GitRepository $gitRepository, GitTokenService $gitTokenService, GitAnalysisService $gitAnalysisService, UserRepository $userRepository)
    {
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
            // Fetch commits from the current page
            $commits = $this->client->repo()->commits()->all($this->username, $repoName, [
                'sha' => $branch,
                'page' => $page,
                'per_page' => 100, // Fetch 100 commits per page (the maximum allowed by most APIs)
            ]);

            // Merge the newly fetched commits with the allCommits array
            $allCommits = array_merge($allCommits, $commits);

            // Increment the page number for the next request
            $page++;
        } while (count($commits) > 0); // Continue fetching as long as we get commits

        return $allCommits;
    }
    public function fetchCommitDetails(string $sha, string $repoName): array
    {
        return $this->client->repo()->commits()->show($this->username, $repoName, $sha);
    }

    public function storeRepository(array $repository, int $gitTokenId, int $hookId): int
    {
        $repositoryId = $this->gitRepository->storeRepository($gitTokenId, $repository['name'], $repository['html_url'], $repository['description'] ?? '', $repository['owner']['login'], $repository['default_branch'], $hookId);
        return $repositoryId;
    }

    public function storeCommit(array $commit, array $commitDetails, int $repositoryId): int
    {
        // Store commit with detailed information
        $commitId = $this->gitRepository->storeCommit(
            $repositoryId,
            $commit['sha'],
            $commit['author']['login'] ?? $commit['commit']['author']['email'],
            $commit['commit']['message'],
            $commit['commit']['author']['date'],
            $commitDetails['stats']['additions'],
            $commitDetails['stats']['deletions'],
            $commitDetails['stats']['total'],
            $commitDetails['files']['stats']['number_of_comment_lines'],
            $commitDetails['files']['stats']['commit_changes_quality_score'],
            $commitDetails['files']['stats']['commit_message_quality_score'],
            $commitDetails['files']['files']
        );

        return $commitId;
    }

    public function createWebhook(string $repoName, string $defaultBranch = null): array
    {
        $hookData = [
            'name' => 'web',
            'active' => true,
            'events' => ['push'],
            'config' => [
                'url' => $_ENV['BASE_URL'] . $_ENV['GITHUB_WEBHOOK_RESPONSE_URL'],
                'content_type' => 'json',
                'insecure_ssl' => '1',
            ],
        ];

        if ($_ENV['ENV'] == 'dev') {
            $hookData['config']['url'] = $_ENV['DEV_GITHUB_WEBHOOK_RESPONSE_URL'];
        }

        return $this->client->repo()->hooks()->create($this->username, $repoName, $hookData);
    }

    public function listWebhooks(string $repoName): array
    {
        return $this->client->repo()->hooks()->all($this->username, $repoName);
    }

    public function updateWebhookStatus(string $repoName, int $hookId, bool $status = false, $repositoryId = 0): array
    {
        $hookData = [
            'active' => $status,
            'config' => [
                'url' => $_ENV['BASE_URL'] . $_ENV['GITHUB_WEBHOOK_RESPONSE_URL'],
            ],
        ];

        if ($_ENV['ENV'] == 'dev') {
            $hookData['config']['url'] = $_ENV['DEV_GITHUB_WEBHOOK_RESPONSE_URL'];
        }

        $hookData['config']['url'] = $_ENV['DEV_GITHUB_WEBHOOK_RESPONSE_URL'];

        return $this->client->repo()->hooks()->update($this->username, $repoName, $hookId, $hookData);
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
                break;
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
        $commitDetails['files'] = ["files" => $commitAnalysis['files'], "stats" => array_merge($commitDetails['stats'], $commitAnalysis['stats'])];

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
        // Format the commit summary string
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
