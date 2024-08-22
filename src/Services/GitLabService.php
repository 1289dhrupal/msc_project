<?php

declare(strict_types=1);

namespace MscProject\Services;

require 'vendor/autoload.php';

use MscProject\Repositories\GitRepository;

class GitLabService extends GitProviderService
{
    private const GITLAB_API_URL = "https://campus.cs.le.ac.uk/gitlab/api/v4";
    private string $gitToken;
    private const SERVICE = 'gitlab';

    public function __construct(GitRepository $gitRepository, GitTokenService $gitTokenService)
    {
        parent::__construct($gitTokenService, $gitRepository, self::SERVICE);
    }

    public function authenticate(string $gitlabToken): void
    {
        $this->gitToken = $gitlabToken;
        $this->username = $this->fetchUsername();
    }

    protected function fetchUsername(): string
    {
        $url = self::GITLAB_API_URL . "/user";
        $response = $this->makeGetRequest($url);

        return $response['username'];
    }

    public function fetchRepositories(): array
    {
        $url = self::GITLAB_API_URL . "/projects?owned=true";
        return $this->makeGetRequest($url);
    }

    public function fetchCommits(string $pathWithNamespace): array
    {
        $branch = 'main';  // Specify the branch name here, typically 'main' or 'master'
        $url = self::GITLAB_API_URL . "/projects/{$this->urlEncodeRepoName($pathWithNamespace)}/repository/commits?ref_name={$branch}&with_stats=true";
        $commits = $this->makeGetRequest($url);
        return array_reverse($commits);
    }

    public function fetchCommitDetails(string $sha, string $pathWithNamespace): array
    {
        $url = self::GITLAB_API_URL . "/projects/{$this->urlEncodeRepoName($pathWithNamespace)}/repository/commits/{$sha}/diff";
        return $this->makeGetRequest($url);
    }

    public function storeRepository(array $repository, int $gitTokenId): int
    {
        $repositoryId = $this->gitRepository->storeRepository($gitTokenId, $repository['name'], $repository['web_url'], $repository['description'] ?? 'No description', $repository['owner']['username']);
        return $repositoryId;
    }

    public function storeCommit(array $commit, array $commitDetails, int $repositoryId): int
    {
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