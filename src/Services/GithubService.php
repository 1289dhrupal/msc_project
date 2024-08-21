<?php

declare(strict_types=1);

namespace MscProject\Services;

require 'vendor/autoload.php';

use Github\Client;
use Github\AuthMethod;
use MscProject\Repositories\GitRepository;
use MscProject\Models\Repository;
use MscProject\Models\Commit;

class GithubService
{
    private Client $client;
    private string $username;
    private GitRepository $gitRepository;
    private GitTokenService $gitTokenService;

    public function __construct(GitTokenService $gitTokenService, GitRepository $gitRepository)
    {
        $this->client = new Client();
        $this->gitRepository = $gitRepository;
        $this->gitTokenService = $gitTokenService;
    }

    public function authenticate(string $githubToken): void
    {
        $this->client->authenticate($githubToken, AuthMethod::ACCESS_TOKEN);
        $this->username = $this->fetchGitHubUsername();
    }

    private function fetchGitHubUsername(): string
    {
        try {
            $user = $this->client->currentUser()->show();
            return $user['login'];
        } catch (\Exception $e) {
            // echo 'Error: ' . $e->getMessage();
            exit(1);
        }
    }

    public function fetchGitTokens(): array
    {
        return $this->gitTokenService->list();
    }

    public function fetchRepositories(): array
    {
        try {
            return $this->client->user()->repositories($this->username);
        } catch (\Exception $e) {
            // echo 'Error: ' . $e->getMessage();
            return [];
        }
    }

    public function getRepository(int $gitTokenId, string $owner, string $name): ?Repository
    {
        $repos = $this->gitRepository->getRepository($gitTokenId, $owner, $name);
        return $repos;
    }

    public function storeRepository(array $repository, int $gitTokenId): int
    {
        try {
            $repositoryId = $this->gitRepository->storeRepository($gitTokenId, $repository['name'], $repository['html_url'], $repository['description'] ?? 'No description', $repository['owner']['login']);
            return $repositoryId;
        } catch (\Exception $e) {
            // echo 'Error: ' . $e->getMessage();
        }
    }

    public function fetchCommits(string $repoName): array
    {
        try {
            return $this->client->repo()->commits()->all($this->username, $repoName, []);
        } catch (\Exception $e) {
            // echo 'Error: ' . $e->getMessage();
            return [];
        }
    }

    public function getCommit(int $repositoryId, string $sha): ?Commit
    {
        return $this->gitRepository->getCommit($repositoryId, $sha);
    }

    public function storeCommit(array $commit, array $commitDetails, int $repositoryId): int
    {
        try {
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
        } catch (\Exception $e) {
            // echo 'Error: ' . $e->getMessage();
        }
    }

    public function fetchCommitDetails(string $sha, string $repoName): array
    {
        try {
            return $this->client->repo()->commits()->show($this->username, $repoName, $sha);
        } catch (\Exception $e) {
            // echo 'Error: ' . $e->getMessage();
            return [];
        }
    }

    public function updateRepositoryFetchedAt(int $repositoryId): void
    {
        $this->gitRepository->updateRepositoryFetchedAt($repositoryId);
    }
}
