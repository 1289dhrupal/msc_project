<?php

declare(strict_types=1);

namespace MscProject\Services;

use Exception;
use MscProject\Repositories\GitRepository;
use MscProject\Services\GitTokenService;

abstract class GitProviderService
{
    protected string $username;
    protected string $service;
    protected GitRepository $gitRepository;
    protected GitTokenService $gitTokenService;
    protected GitAnalysisService $gitAnalysisService;
    public function __construct(GitTokenService $gitTokenService, GitRepository $gitRepository, GitAnalysisService $gitAnalysisService, string $service)
    {
        $this->gitRepository = $gitRepository;
        $this->gitTokenService = $gitTokenService;
        $this->gitAnalysisService = $gitAnalysisService;
        $this->service = $service;
    }

    abstract protected function authenticate(string $token, string $url): void;

    public function fetchGitTokens(): array
    {
        return $this->gitTokenService->list(service: $this->service);
    }

    abstract protected function fetchRepositories(): array;

    abstract protected function fetchCommits(string $repoName, string $branch): array;

    abstract protected function fetchCommitDetails(string $sha, string $repoName): array;

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
            'default_branch' => $repository->getDefaultBranch(),
            'hook_id' => $repository->getHookId(),
            'is_active' => $repository->isActive(),
            'created_at' => $repository->getCreatedAt(),
            'last_fetched_at' => $repository->getLastFetchedAt()
        ];
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

    public function updateRepositoryFetchedAt(int $repositoryId): void
    {
        $this->gitRepository->updateRepositoryFetchedAt($repositoryId);
    }

    public function updateTokenFetchedAt(int $gitTokenId): void
    {
        $this->gitTokenService->updateFetchedAt($gitTokenId);
    }

    abstract protected function storeRepository(array $repository, int $gitTokenId, int $hookId): int;

    abstract protected function storeCommit(array $commit, array $commitDetails, int $repositoryId): int;

    abstract protected function listWebhooks(string $repoName): array;

    abstract protected function createWebhook(string $repoName, string $defaultBranch): array;

    abstract protected function updateWebhookStatus(string $repoName, int $hookId, bool $active, int $repositoryId): array;

    abstract protected function handleEvent(string $event, int $hookId, array $data): void;

    abstract protected function getRepositoryOwner(array $repository): string;

    abstract protected function getRepositoryPath(array $repository): string;

    abstract protected function getCommitIdentifier(array $commit): string;

    abstract protected function processCommit(array $commit, array $commitDetails): array;

    public function fetchAll()
    {
        $gitTokens = $this->fetchGitTokens();

        foreach ($gitTokens as $gitToken) {

            if (!$gitToken['is_active']) {
                continue;
            }

            $this->authenticate($gitToken['token'], $gitToken['url']);
            $repositories = $this->fetchRepositories();

            foreach ($repositories as $repository) {
                $repoOwner = $this->getRepositoryOwner($repository);
                $repoPath = $this->getRepositoryPath($repository);

                $repo = $this->getRepository($gitToken['id'], $repoOwner, $repository['name']);

                if ($repo && !$repo['is_active']) {
                    continue;
                }

                $repositoryId = $repo['id'] ?? 0;

                if (!$repositoryId) {
                    $hookId = 0;

                    $hooks = $this->listWebhooks($repoPath);
                    foreach ($hooks as $hook) {
                        if ($hook['name'] === 'web') {
                            $hookId = $hook['id'];
                            break;
                        }
                    }

                    if (!$hookId) {
                        $hook = $this->createWebhook($repoPath, $repository['default_branch']);
                        $hookId = $hook['id'];
                    }

                    $repositoryId = $this->storeRepository($repository, $gitToken['id'], $hookId);
                    $this->updateWebhookStatus($repoPath, $hookId, true, $repositoryId);
                }

                $commits = $this->fetchCommits($repoPath, $repository['default_branch']);
                foreach ($commits as $commit) {
                    $commitIdentifier = $this->getCommitIdentifier($commit);
                    if (!$this->getCommit($repositoryId, $commitIdentifier)) {
                        $commitDetails = $this->fetchCommitDetails($commitIdentifier, $repoPath);
                        $commitDetails = $this->processCommit($commit, $commitDetails);
                        $commitId = $this->storeCommit($commit, $commitDetails, $repositoryId);
                    }
                }

                $this->updateRepositoryFetchedAt($repositoryId);
            }

            $this->updateTokenFetchedAt($gitToken['id']);
        }
    }

    public function handlePushEvent($repository, $gitToken, $data)
    {
        // TODO: Implement handlePushEvent() method.
    }
}
