<?php

declare(strict_types=1);

namespace MscProject\Services;

use MscProject\Repositories\GitRepository;
use MscProject\Models\GitToken;
use MscProject\Utils;
use InvalidArgumentException;

class GitTokenService
{
    private GitRepository $gitRepository;

    public function __construct(GitRepository $gitRepository)
    {
        $this->gitRepository = $gitRepository;
    }

    public function storeGitToken(string $token, string $service, string $url, string $description, int $userId): bool
    {
        $existingToken = $this->gitRepository->getTokenByToken($token);
        if ($existingToken !== null) {
            throw new InvalidArgumentException('Token already exists', 409);
        }

        $newToken = new GitToken(null, $userId, $token, $service, $url, $description, false, null, null);
        return $this->gitRepository->create($newToken);
    }

    public function list(int $userId = 0, bool $mask = false, string $service = '', $gitTokenIds = []): array
    {
        $gitTokenIds =  implode(',', $gitTokenIds);
        $gitTokens = $this->gitRepository->listTokens($userId, $gitTokenIds, $service);
        return array_map(function (GitToken $gitToken) use ($mask) {
            $token = $mask ? Utils::maskToken($gitToken->getToken()) : $gitToken->getToken();
            return [
                'id' => $gitToken->getId(),
                'user_id' => $gitToken->getUserId(),
                'token' => $token,
                'service' => $gitToken->getService(),
                'url' => $gitToken->getUrl(),
                'description' => $gitToken->getDescription(),
                'is_active' => $gitToken->isActive(),
                'created_at' => $gitToken->getCreatedAt(),
                'last_fetched_at' => $gitToken->getLastFetchedAt() ?? 'Never',
            ];
        }, $gitTokens);
    }

    public function edit(int $tokenId, string $token, string $service, string $url, string $description, int $userId = 0): void
    {
        $gitToken = $this->gitRepository->getToken($tokenId, $userId);
        if ($gitToken === null) {
            throw new InvalidArgumentException('Token not found');
        }

        $updatedToken = new GitToken(
            $tokenId,
            $userId,
            $token,
            $service,
            $url,
            $description,
            $gitToken->isActive(),
            $gitToken->getCreatedAt(),
            $gitToken->getLastFetchedAt()
        );

        $this->gitRepository->updateToken($updatedToken);
    }

    public function toggle(int $tokenId, bool $isActive, int $userId = 0): void
    {
        $updated = $this->gitRepository->toggleToken($tokenId, $isActive, $userId);
        if ($updated && $isActive) {
            $this->gitRepository->sync(gitTokenId: $tokenId);
        }
    }

    public function delete(int $tokenId, int $userId = 0): void
    {
        $gitToken = $this->gitRepository->getToken($tokenId, $userId);

        if ($gitToken === null) {
            throw new InvalidArgumentException('Token not found');
        }

        $this->gitRepository->deleteToken($tokenId, $userId);
    }

    public function updateFetchedAt(int $tokenId): void
    {
        $this->gitRepository->updateTokenFetchedAt($tokenId);
    }
}
