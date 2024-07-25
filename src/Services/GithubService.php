<?php

namespace MscProject\Services;

require 'vendor/autoload.php';

use Github\Client;
use Github\AuthMethod;
use MscProject\Repositories\GitRepository;

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
            echo 'Error: ' . $e->getMessage();
            exit(1);
        }
    }

    /**
     * @return GitToken[]
     */
    public function fetchGitTokens(): array
    {
        return $this->gitTokenService->fetchAll();
    }

    public function fetchRepositories(): array
    {
        try {
            return $this->client->user()->repositories($this->username);
        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage();
            return [];
        }
    }

    public function storeRepositories(array $repositories, int $gitTokenId): array
    {
        $repositoryIds = [];

        foreach ($repositories as $repo) {
            try {
                $repositoryId = $this->gitRepository->storeRepository($gitTokenId, $repo['name'], $repo['html_url'], $repo['description'] ?? 'No description', $repo['owner']['login']);
                $repositoryIds[$repo['name']] = $repositoryId;
            } catch (\Exception $e) {
                echo 'Error: ' . $e->getMessage();
            }
        }

        return $repositoryIds;
    }

    public function fetchCommits(string $repoName): array
    {
        try {
            return $this->client->repo()->commits()->all($this->username, $repoName, []);
        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage();
            return [];
        }
    }

    public function storeCommits(array $commits, int $repositoryId): array
    {
        $commitIds = [];

        foreach ($commits as $commit) {
            try {
                $commitId = $this->gitRepository->storeCommit($repositoryId, $commit['sha'], $commit['commit']['author']['name'], $commit['commit']['message'], $commit['commit']['author']['date']);
                $commitIds[$commit['sha']] = $commitId;
            } catch (\Exception $e) {
                echo 'Error: ' . $e->getMessage();
            }
        }

        return $commitIds;
    }

    public function fetchCommitDetails(string $sha, string $repoName): array
    {
        try {
            return $this->client->repo()->commits()->show($this->username, $repoName, $sha);
        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage();
            return [];
        }
    }

    public function storeCommitDetails(array $commitDetails, int $commitId): void
    {
        try {
            $this->gitRepository->storeCommitDetails($commitId, $commitDetails['commit']['author']['name'], $commitDetails['stats']['additions'], $commitDetails['stats']['deletions'], $commitDetails['stats']['total'], json_encode($commitDetails['files']));
        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }

    public function updateRepositoryFetchedAt(int $repositoryId): void
    {
        $this->gitRepository->updateRepositoryFetchedAt($repositoryId);
    }
}
