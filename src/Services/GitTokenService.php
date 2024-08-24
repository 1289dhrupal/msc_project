<?php

declare(strict_types=1);

namespace MscProject\Services;

use Exception;
use MscProject\Repositories\GitRepository;
use MscProject\Models\GitToken;
use MscProject\Utils;
use Throwable;

class GitTokenService
{
    private GitRepository $gitRepository;

    public function __construct(GitRepository $gitRepository)
    {
        $this->gitRepository = $gitRepository;
    }

    public function storeGitToken(string $token, string $service, string $url, string $description, int $userId): bool
    {
        $gitToken = $this->gitRepository->getTokenByToken($token);
        if ($gitToken !== null) {
            throw new \ErrorException('Token already exists',  409, E_USER_WARNING);
        }

        $gitToken = new GitToken(null, $userId, $token, $service, $url, $description, false, null, null);
        return $this->gitRepository->create($gitToken);
    }

    public function list(int $userId = 0, bool $mask = false, string $service = ''): array
    {
        $gitTokens = $this->gitRepository->listTokens($userId, service: $service);
        $res = [];
        foreach ($gitTokens as $i => $gitToken) {
            $token = $gitToken->getToken();
            $token = $mask ? Utils::maskToken($token) : $token;
            $res[] = [
                'id' => $gitToken->getId(),
                'token' => $token,
                'service' => $gitToken->getService(),
                'url' => $gitToken->getUrl(),
                'description' => $gitToken->getDescription(),
                'is_disabled' => $gitToken->isDisabled(),
                'created_at' => $gitToken->getCreatedAt(),
                'last_fetched_at' => $gitToken->getLastFetchedAt() ?? 'Never',
            ];
        }
        return $res;
    }

    public function toggle(int $tokenId, bool $isDisabled, int $userId = 0): void
    {
        $this->gitRepository->toggleToken($tokenId, $isDisabled, $userId);
    }

    public function delete(int $tokenId, int $userId = 0): void
    {
        $gitToken = $this->gitRepository->getToken($tokenId, $userId);
        if ($gitToken !== null) {
            // TODO: Delete
            // $this->gitRepository->deleteRepositoriesByTokenId($tokenId);
            $this->gitRepository->deleteToken($tokenId, $userId);
        }
    }
}
