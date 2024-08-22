<?php

declare(strict_types=1);

namespace MscProject\Services;

require 'vendor/autoload.php';

use MscProject\Repositories\GitRepository;

class GitLabService
{
    private const GITLAB_API_URL = "https://campus.cs.le.ac.uk/gitlab/api/v4";

    private string $username;
    private string $gitToken;
    private GitRepository $gitRepository;
    private GitTokenService $gitTokenService;

    public function __construct(GitTokenService $gitTokenService, GitRepository $gitRepository)
    {
        $this->gitRepository = $gitRepository;
        $this->gitTokenService = $gitTokenService;
    }

    public function authenticate(string $gitlabToken): void
    {
        $this->gitToken = $gitlabToken;
        $this->username = $this->fetchGitLabUsername();
    }

    public function fetchGitTokens(): array
    {
        return $this->gitTokenService->list(service: 'gitlab');
    }

    public function fetchRepositories(): array
    {
        $url = self::GITLAB_API_URL . "/projects?owned=true";
        return $this->makeGetRequest($url);
    }

    public function getRepository(int $gitTokenId, string $owner, string $name): array
    {
        $repository = $this->gitRepository->getRepository($gitTokenId, $owner, $name);

        if (!$repository) {
            return [];
        }

        return [
            'id' => $repository->getId(),
            'git_token_id' => $repository->getGitTokenId(),
            'name' => $repository->getName(),
            'url' => $repository->getUrl(),
            'description' => $repository->getDescription(),
            'owner' => $repository->getOwner(),
            'is_disabled' => $repository->isDisabled(),
            'created_at' => $repository->getCreatedAt(),
            'last_fetched_at' => $repository->getLastFetchedAt()
        ];
    }

    public function storeRepository(array $repository, int $gitTokenId): int
    {
        try {
            $repositoryId = $this->gitRepository->storeRepository($gitTokenId, $repository['name'], $repository['web_url'], $repository['description'] ?? 'No description', $repository['owner']['username']);
            return $repositoryId;
        } catch (\Exception $e) {
            // Log the error instead of exiting
            // exit('Error: ' . $e->getMessage());
            return 0;
        }
    }

    public function fetchCommits(string $pathWithNamespace): array
    {
        $branch = 'main';  // Specify the branch name here, typically 'main' or 'master'
        $url = self::GITLAB_API_URL . "/projects/{$this->urlEncodeRepoName($pathWithNamespace)}/repository/commits?ref_name={$branch}&with_stats=true";
        $commits = $this->makeGetRequest($url);
        return array_reverse($commits);
    }

    public function getCommit(int $repositoryId, string $sha): array
    {
        $commit = $this->gitRepository->getCommit($repositoryId, $sha);

        if (!$commit) {
            return [];
        }

        return [
            'id' => $commit->getId(),
            'repository_id' => $commit->getRepositoryId(),
            'sha' => $commit->getSha(),
            'author' => $commit->getAuthor(),
            'message' => $commit->getMessage(),
            'date' => $commit->getDate(),
            'additions' => $commit->getAdditions(),
            'deletions' => $commit->getDeletions(),
            'total' => $commit->getTotal(),
            'files' => json_decode($commit->getFiles(), true)
        ];
    }

    public function storeCommit(array $commit, array $commitDetails, int $repositoryId): int
    {
        try {
            $commitId = $this->gitRepository->storeCommit(
                $repositoryId,
                $commit['id'],
                $commit['author_name'],
                $commit['message'],
                $commit['created_at'],
                $commit['stats']['additions'],
                $commit['stats']['deletions'],
                $commit['stats']['total'],
                json_encode($commitDetails['files'])
            );

            return $commitId;
        } catch (\Exception $e) {
            // Log the error instead of exiting
            // exit('Error: ' . $e->getMessage());
            return 0;
        }
    }

    public function fetchCommitDetails(string $sha, string $pathWithNamespace): array
    {
        $url = self::GITLAB_API_URL . "/projects/{$this->urlEncodeRepoName($pathWithNamespace)}/repository/commits/{$sha}/diff";
        return $this->makeGetRequest($url);
    }

    public function updateRepositoryFetchedAt(int $repositoryId): void
    {
        $this->gitRepository->updateRepositoryFetchedAt($repositoryId);
    }

    private function fetchGitLabUsername(): string
    {
        $url = self::GITLAB_API_URL . "/user";
        $response = $this->makeGetRequest($url);

        if (isset($response['username'])) {
            return $response['username'];
        } else {
            // Handle the error appropriately
            // exit('Failed to retrieve username.');
            return '';
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

        if ($response === false) {
            // Log the error instead of exiting
            // exit('Error fetching data from GitLab');
            return [];
        }

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
