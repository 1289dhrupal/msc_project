<?php

declare(strict_types=1);

namespace MscProject\Controllers;

use MscProject\Services\GitTokenService;
use MscProject\Models\Response\SuccessResponse;
use MscProject\Models\Response\Response;
use Exception;

class GitTokenController
{
    private GitTokenService $gitTokenService;

    public function __construct(GitTokenService $gitTokenService)
    {
        $this->gitTokenService = $gitTokenService;
    }

    public function store(): Response
    {
        global $userSession;

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $input = array_merge(array('token' => '', 'service' => 'github', 'url' => 'https://github.com', 'description' => ''), $input);

        if (empty($input['token']) || empty($input['service'])) {
            throw new \ErrorException('Token, and service are required', 400);
        }

        if (!in_array($input['service'], ['github', 'gitlab'], true)) {
            throw new \ErrorException('Invalid service type', 400);
        }

        if (!filter_var($input['url'], FILTER_VALIDATE_URL)) {
            throw new \ErrorException('Invalid URL', 400);
        }

        $result = $this->gitTokenService->storeGitToken($input['token'], $input['service'], $input['url'], $input['description'], $userSession->getId());
        $response = new SuccessResponse('Git token stored successfully', $result, 201);

        return $response;
    }

    public function list(): Response
    {
        global $userSession;

        $result = $this->gitTokenService->list($userSession->getId(), true);
        $response = new SuccessResponse('Git tokens retrieved successfully', $result);

        return $response;
    }

    public function edit(int $tokenId): Response
    {
        global $userSession;

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $input = array_merge(['token' => '', 'service' => 'github', 'url' => 'https://github.com', 'description' => ''], $input);

        if (empty($input['token']) || empty($input['service'])) {
            throw new \ErrorException('Token, and service are required', 400);
        }

        if (!in_array($input['service'], ['github', 'gitlab'], true)) {
            throw new \ErrorException('Invalid service type', 400);
        }

        if (!filter_var($input['url'], FILTER_VALIDATE_URL)) {
            throw new \ErrorException('Invalid URL', 400);
        }

        $result = $this->gitTokenService->edit($tokenId, $input['token'], $input['service'], $input['url'], $input['description'], $userSession->getId());
        $response = new SuccessResponse('Git token updated successfully', $result);

        return $response;
    }

    public function toggle(int $tokenId): Response
    {
        global $userSession;

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $input = array_merge(['is_active' => true], $input);
        $isActive = filter_var($input['is_active'], FILTER_VALIDATE_BOOLEAN);
        $this->gitTokenService->toggle($tokenId, $isActive, $userSession->getId());
        $response = new SuccessResponse("Updated the status for token ID: $tokenId");

        return $response;
    }

    public function delete(int $tokenId): Response
    {
        global $userSession;

        $this->gitTokenService->delete($tokenId, $userSession->getId());
        $response = new SuccessResponse("Updated the status for token ID: $tokenId");

        return $response;
    }
}
