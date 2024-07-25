<?php

declare(strict_types=1);

namespace MscProject\Services;

use MscProject\Repositories\GitTokenRepository;
use MscProject\Models\GitToken;

class GitTokenService
{
    private GitTokenRepository $gitTokenRepository;

    public function __construct(GitTokenRepository $gitTokenRepository)
    {
        $this->gitTokenRepository = $gitTokenRepository;
    }

    public function storeGitToken(string $token, string $service): bool
    {
        global $user_session;
        $userId = $user_session->getId();

        $gitToken = $this->gitTokenRepository->getTokenByToken($token);

        if ($token !== null) {
            throw new \ErrorException('Token already exists',  409, E_USER_WARNING);
        }

        $gitToken = new GitToken(null, $userId, $token, $service);
        return $this->gitTokenRepository->create($gitToken);
    }

    /**
     * @param int $userId
     * @return GitToken[]
     */
    public function getTokensByUserId(): array
    {
        global $user_session;
        $userId = $user_session->getId();

        return $this->gitTokenRepository->getTokensByUserId($userId);
    }

    /**
     * @return GitToken[]
     */
    public function fetchAll(): array
    {
        return $this->gitTokenRepository->fetchAll();
    }
}
