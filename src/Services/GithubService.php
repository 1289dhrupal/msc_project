<?php

declare(strict_types=1);

namespace MscProject\Services;

require 'vendor/autoload.php';

use ErrorException;
use Github\Client;
use Github\AuthMethod;
use MscProject\Models\GitToken;
use MscProject\Models\Repository;
use MscProject\Repositories\GitRepository;

class GithubService extends GitProviderService
{
    private Client $client;
    private const SERVICE = 'github';

    public function __construct(GitTokenService $gitTokenService, GitRepository $gitRepository)
    {
        $this->client = new Client();
        parent::__construct($gitTokenService, $gitRepository, self::SERVICE);
    }

    public function authenticate(string $githubToken): void
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
        return $this->client->repo()->commits()->all($this->username, $repoName, []);
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
            json_encode($commitDetails['files'])
        );

        return $commitId;
    }

    public function createWebhook(string $repoName, array $events = array('push'), string $defaultBranch = 'main'): array
    {
        $hookData = [
            'name' => 'web',
            'active' => true,
            'events' => $events,
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

    public function updateWebhookStatus(string $repoName, int $hookId, array $data = []): array
    {
        $hookData = [
            'active' => $data['active'] ?? false,
            'config' => [
                'url' => $_ENV['BASE_URL'] . $_ENV['GITHUB_WEBHOOK_RESPONSE_URL'],
            ],
        ];

        var_dump($hookData['active']);
        if (isset($data['events'])) {
            $hookData['events'] = $data['events'];
        }

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
            throw new ErrorException("Repository is not active.");
        }

        $gitToken = $this->gitRepository->getToken($repository['git_token_id']);
        if (!$gitToken || !$gitToken->isActive()) {
            throw new ErrorException("Token is not active.");
        }

        switch ($event) {
            case 'push':
                parent::handlePushEvent($repository, $gitToken, $data);
                break;
            default:
                throw new ErrorException("Event not supported.");
                break;
        }
    }
}
