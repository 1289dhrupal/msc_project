<?php

declare(strict_types=1);

namespace MscProject\Services;

require 'vendor/autoload.php';

use Github\Client;
use Github\AuthMethod;
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

    public function fetchCommits(string $repoName): array
    {
        return $this->client->repo()->commits()->all($this->username, $repoName, []);
    }

    public function fetchCommitDetails(string $sha, string $repoName): array
    {
        return $this->client->repo()->commits()->show($this->username, $repoName, $sha);
    }

    public function storeRepository(array $repository, int $gitTokenId): int
    {
        $repositoryId = $this->gitRepository->storeRepository($gitTokenId, $repository['name'], $repository['html_url'], $repository['description'] ?? '', $repository['owner']['login']);
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
}
