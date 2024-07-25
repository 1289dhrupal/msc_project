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
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $input = array_merge(array('token' => '', 'service' => ''), $input);

        if (empty($input['token']) || empty($input['service'])) {
            throw new \ErrorException('Token, and service are required', 400);
        }

        if (!in_array($input['service'], ['github', 'gitlab'], true)) {
            throw new \ErrorException('Invalid service type', 400);
        }

        $result = $this->gitTokenService->storeGitToken($input['token'], $input['service']);
        $response = new SuccessResponse('Git token stored successfully', $result, 201);

        return $response;
    }
}
