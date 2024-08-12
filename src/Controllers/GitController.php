<?php

declare(strict_types=1);

namespace MscProject\Controllers;

use MscProject\Services\GitService;
use MscProject\Models\Response\SuccessResponse;
use MscProject\Models\Response\Response;
use Exception;

class GitController
{
    private GitService $gitService;

    public function __construct(GitService $gitService)
    {
        $this->gitService = $gitService;
    }

    public function listRepositories(): Response
    {
        $input = array_merge(array('git_token_id' => 0), $_GET);
        $gitTokenId = intval($input['git_token_id']);

        try {
            global $user_session;
            $repositories = $this->gitService->listRepositories($user_session->getId(), $gitTokenId);
            return new SuccessResponse("Successfully Fetched Respositories", $repositories);
        } catch (Exception $e) {
            throw new \ErrorException('Failed to fetch repositories', 400, E_USER_WARNING);
        }
    }

    public function toggleRepository(int $repositoryId): Response
    {
        global $user_session;

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $input = array_merge(['is_disabled' => false], $input);

        try {
            $is_disabled = filter_var($input['is_disabled'], FILTER_VALIDATE_BOOLEAN);
            $this->gitService->toggleRepository($repositoryId, $is_disabled, $user_session->getId());
            return new SuccessResponse("Updated the status for token ID: $repositoryId");
        } catch (Exception $e) {
            throw new \ErrorException('Failed to toggle repository', 400, E_USER_WARNING);
        }
    }

    public function deleteRepository(int $repositoryId): Response
    {
        try {
            global $user_session;
            $this->gitService->deleteRepository($repositoryId, $user_session->getId());
            return new SuccessResponse("Deleted the repository with ID: $repositoryId");
        } catch (Exception $e) {
            throw new \ErrorException('Failed to delete repository', 400, E_USER_WARNING);
        }
    }
}
