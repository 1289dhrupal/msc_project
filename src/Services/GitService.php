<?php

declare(strict_types=1);

namespace MscProject\Services;

use Exception;
use MscProject\Repositories\GitRepository;
use MscProject\Models\GitToken;
use MscProject\Utils;
use Throwable;

class GitService
{
    private GitRepository $gitRepository;

    public function __construct(GitRepository $gitRepository)
    {
        $this->gitRepository = $gitRepository;
    }

    public function listRepositories(int $userId = 0, int $gitTokenId = 0, bool $mask = true): array
    {
        $repos = $this->gitRepository->listRepositories($userId, $gitTokenId);
        $repositories = [];
        foreach ($repos as $i => $repo) {
            $repositories[] = [
                'id' => $repo->getId(),
                'git_token_id' => $repo->getGitTokenId(),
                'name' => $repo->getName(),
                'url' => $repo->getUrl(),
                'description' => $repo->getDescription(),
                'owner' => $repo->getOwner(),
                'default_branch' => $repo->getDefaultBranch(),
                'hook_id' => $repo->getHookId(),
                'is_active' => $repo->isActive(),
                'created_at' => $repo->getCreatedAt(),
                'last_fetched_at' => $repo->getLastFetchedAt() ?? 'Never',
            ];
        }

        $gitTokenIds = $gitTokenId !== 0 ? "$gitTokenId" :  "";

        $gitTokens = $this->gitRepository->listTokens($userId, $gitTokenIds);
        foreach ($gitTokens as $i => $gitToken) {
            $token = $gitToken->getToken();
            $token = $mask ? Utils::maskToken($token, 4, 4) : $token;
            $gitTokens[$i] = [
                'id' => $gitToken->getId(),
                'user_id' => $gitToken->getUserId(),
                'is_active' => $gitToken->isActive(),
                'token' => $token,
            ];
        }

        $res = [
            'repositories' => $repositories,
            'git_tokens' => $gitTokens,
        ];
        return $res;
    }

    public function toggleRepository(int $repoId, bool $isActive, int $userId = 0): void
    {
        $this->gitRepository->toggleRepository($repoId, $isActive, $userId);
    }

    public function deleteRepository(int $repoId, int $userId = 0): void
    {
        $repo = $this->gitRepository->getRepositoryById($repoId, $userId);
        if ($repo !== null) {
            // TODO: Delete
            // $this->gitRepository->deleteRepositoriesByTokenId($tokenId);
            $this->gitRepository->deleteRepository($repoId, $userId);
        }
    }

    public function listCommits(int $repoId = 0, int $userId = 0): array
    {
        $repo = $this->gitRepository->getRepositoryById($repoId, $userId);
        if ($repo === null) {
            throw new Exception('Repository not found');
        }

        $commits = $this->gitRepository->listCommits($repoId, $userId);

        $commitResponse = [];
        foreach ($commits as $i => $commit) {
            $commitResponse[$commit->getId()] = [
                'id' => $commit->getId(),
                'repository_id' => $commit->getRepositoryId(),
                'sha' => $commit->getSha(),
                'author' => $commit->getAuthor(),
                'message' => $commit->getMessage(),
                'date' => $commit->getDate(),
                'additions' => $commit->getAdditions(),
                'deletions' => $commit->getDeletions(),
                'total' => $commit->getTotal(),
            ];
        }

        if ($repoId == 0) {
            $commitResponse = array_slice($commitResponse, 0, 20);
        }

        $repoIds = $repoId !== 0 ? "$repoId" :  "";

        $repoIds = $this->gitRepository->listRepositories($userId, repoIds: $repoIds);
        $repoResponse = [];
        foreach ($repoIds as $i => $repoId) {
            $repoResponse[$i] = [
                'id' => $repoId->getId(),
                'git_token_id' => $repoId->getGitTokenId(),
                'name' => $repoId->getName(),
                'url' => $repoId->getUrl(),
                'owner' => $repoId->getOwner(),
                'default_branch' => $repoId->getDefaultBranch(),
                'hook_id' => $repoId->getHookId(),
                'is_active' => $repoId->isActive(),
            ];
        }

        return ["commits" => array_values($commitResponse), "repositories" => $repoResponse];
    }
}
