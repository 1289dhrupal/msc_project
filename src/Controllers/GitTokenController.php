<?php

declare(strict_types=1);

namespace MscProject\Controllers;

use MscProject\Services\GitTokenService;
use MscProject\Models\Response\SuccessResponse;
use MscProject\Models\Response\Response;
use Exception;
use ErrorException;

class GitTokenController
{
    private GitTokenService $gitTokenService;

    public function __construct(GitTokenService $gitTokenService)
    {
        $this->gitTokenService = $gitTokenService;
    }

    public function store(): Response
    {
        try {
            global $userSession;

            $input = $this->getJsonInput(['token', 'service' => 'github', 'url' => 'https://github.com', 'description' => '']);
            $this->validateInput($input);

            $result = $this->gitTokenService->storeGitToken($input['token'], $input['service'], $input['url'], $input['description'], $userSession->getId());

            return new SuccessResponse('Git token stored successfully', $result, 201);
        } catch (Exception $e) {
            throw new ErrorException('Failed to store Git token', 400, E_USER_WARNING, previous: $e);
        }
    }

    public function list(): Response
    {
        try {
            global $userSession;
            $result = $this->gitTokenService->list($userSession->getId(), true);

            return new SuccessResponse('Git tokens retrieved successfully', $result);
        } catch (Exception $e) {
            throw new ErrorException('Failed to retrieve Git tokens', 400, E_USER_WARNING, previous: $e);
        }
    }

    public function edit(int $tokenId): Response
    {
        try {
            global $userSession;

            $input = $this->getJsonInput(['token', 'service' => 'github', 'url' => 'https://github.com', 'description' => '']);
            $this->validateInput($input);

            $this->gitTokenService->edit($tokenId, $input['token'], $input['service'], $input['url'], $input['description'], $userSession->getId());

            return new SuccessResponse('Git token updated successfully');
        } catch (Exception $e) {
            throw new ErrorException('Failed to update Git token', 400, E_USER_WARNING, previous: $e);
        }
    }

    public function toggle(int $tokenId): Response
    {
        try {
            global $userSession;

            $input = $this->getJsonInput(['is_active' => true]);
            $isActive = filter_var($input['is_active'], FILTER_VALIDATE_BOOLEAN);

            $this->gitTokenService->toggle($tokenId, $isActive, $userSession->getId());

            return new SuccessResponse("Updated the status for token ID: $tokenId");
        } catch (Exception $e) {
            throw new ErrorException('Failed to toggle Git token status', 400, E_USER_WARNING, previous: $e);
        }
    }

    public function delete(int $tokenId): Response
    {
        try {
            global $userSession;

            $this->gitTokenService->delete($tokenId, $userSession->getId());

            return new SuccessResponse("Deleted the token with ID: $tokenId");
        } catch (Exception $e) {
            throw new ErrorException('Failed to delete Git token', 400, E_USER_WARNING, previous: $e);
        }
    }

    private function getJsonInput(array $defaults = []): array
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        return array_merge($defaults, $input);
    }

    private function validateInput(array $input): void
    {
        if (empty($input['token']) || empty($input['service'])) {
            throw new ErrorException('Token and service are required', 400, E_USER_WARNING);
        }

        if (!in_array($input['service'], ['github', 'gitlab'], true)) {
            throw new ErrorException('Invalid service type', 400, E_USER_WARNING);
        }

        if (!filter_var($input['url'], FILTER_VALIDATE_URL)) {
            throw new ErrorException('Invalid URL', 400, E_USER_WARNING);
        }
    }
}
