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

        $gitToken = new GitToken(null, $userId, $token, $service);
        return $this->gitTokenRepository->create($gitToken);
    }
}
