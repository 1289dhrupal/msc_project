<?php

declare(strict_types=1);

namespace MscProject\Services;

use MscProject\Repositories\GitRepository;
use MscProject\Services\GitTokenService;

abstract class GitProviderService
{
    protected string $username;
    protected string $service;
    protected GitRepository $gitRepository;
    protected GitTokenService $gitTokenService;

    public function __construct(GitTokenService $gitTokenService, GitRepository $gitRepository, string $service)
    {
        $this->gitRepository = $gitRepository;
        $this->gitTokenService = $gitTokenService;
        $this->service = $service;
    }

    abstract protected function authenticate(string $token): void;

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

    abstract protected function storeRepository(array $repository, int $gitTokenId, int $hookId): int;

    abstract protected function storeCommit(array $commit, array $commitDetails, int $repositoryId): int;

    abstract protected function createWebhook(string $repoName, array $events, string $defaultBranch): array;

    abstract protected function updateWebhookStatus(string $repoName, int $hookId, array $active): array;

    abstract protected function handleEvent(string $event, int $hookId, array $data): void;

    public function handlePushEvent($repository, $gitToken, $data)
    {
        // TODO: Implement handlePushEvent() method.
    }
}
