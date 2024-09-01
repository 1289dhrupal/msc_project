<?php

declare(strict_types=1);

namespace MscProject\Controllers;

use MscProject\Services\GitService;
use MscProject\Models\Response\SuccessResponse;
use MscProject\Models\Response\Response;
use Exception;
use ErrorException;

class GitController
{
    private GitService $gitService;

    public function __construct(GitService $gitService)
    {
        $this->gitService = $gitService;
    }

    public function listRepositories(): Response
    {
        $gitTokenId = intval($_GET['git_token_id'] ?? 0);

        try {
            global $userSession;

            $repositories = $this->gitService->listRepositories($userSession->getId(), $gitTokenId);
            return new SuccessResponse("Successfully fetched repositories", $repositories);
        } catch (Exception $e) {
            throw new ErrorException('Failed to fetch repositories', 400, E_USER_WARNING, previous: $e);
        }
    }

    public function toggleRepository(int $repositoryId): Response
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $isActive = filter_var($input['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN);

        try {
            global $userSession;

            $this->gitService->toggleRepository($repositoryId, $isActive, $userSession->getId());
            return new SuccessResponse("Updated the status for repository ID: $repositoryId");
        } catch (Exception $e) {
            throw new ErrorException('Failed to toggle repository', 400, E_USER_WARNING, previous: $e);
        }
    }

    public function deleteRepository(int $repositoryId): Response
    {
        try {
            global $userSession;

            $this->gitService->deleteRepository($repositoryId, $userSession->getId());
            return new SuccessResponse("Deleted the repository with ID: $repositoryId");
        } catch (Exception $e) {
            throw new ErrorException('Failed to delete repository', 400, E_USER_WARNING, previous: $e);
        }
    }

    public function listCommits(int $repositoryId = 0): Response
    {
        try {
            global $userSession;

            $commits = $this->gitService->listCommits($repositoryId, $userSession->getId());
            return new SuccessResponse("Successfully fetched commits", $commits);
        } catch (Exception $e) {
            throw new ErrorException('Failed to fetch commits', 400, E_USER_WARNING, previous: $e);
        }
    }

    public function getStats(int $repositoryId = 0): Response
    {
        try {
            global $userSession;

            $stats = $this->gitService->getStats($repositoryId, $userSession->getId());
            return new SuccessResponse("Successfully fetched stats", $stats);
        } catch (Exception $e) {
            throw new ErrorException('Failed to fetch stats', 400, E_USER_WARNING, previous: $e);
        }
    }
}
