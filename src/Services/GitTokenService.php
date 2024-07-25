<?php

declare(strict_types=1);

namespace MscProject\Services;

use MscProject\Repositories\GitRepository;
use MscProject\Models\GitToken;

class GitTokenService
{
    private GitRepository $gitRepository;

    public function __construct(GitRepository $gitRepository)
    {
        $this->gitRepository = $gitRepository;
    }

    public function storeGitToken(string $token, string $service): bool
    {
        global $user_session;
        $userId = $user_session->getId();

        $gitToken = $this->gitRepository->getTokenByToken($token);

        if ($token !== null) {
            throw new \ErrorException('Token already exists',  409, E_USER_WARNING);
        }

        $gitToken = new GitToken(null, $userId, $token, $service);
        return $this->gitRepository->create($gitToken);
    }

    /**
     * @param int $userId
     * @return GitToken[]
     */
    public function getTokensByUserId(): array
    {
        global $user_session;
        $userId = $user_session->getId();

        return $this->gitRepository->getTokensByUserId($userId);
    }

    /**
     * @return GitToken[]
     */
    public function fetchAll(): array
    {
        return $this->gitRepository->fetchAll();
    }
}
