<?php

declare(strict_types=1);

namespace MscProject\Services;

use Exception;
use MscProject\Mailer;
use MscProject\Models\GitToken;
use MscProject\Models\Repository;
use MscProject\Repositories\GitRepository;
use MscProject\Repositories\UserRepository;
use MscProject\Services\GitTokenService;

abstract class GitProviderService
{
    protected string $username;
    protected string $service;
    protected GitRepository $gitRepository;
    protected GitTokenService $gitTokenService;
    protected GitAnalysisService $gitAnalysisService;
    protected UserRepository $userRepository;

    public function __construct(GitTokenService $gitTokenService, GitRepository $gitRepository, GitAnalysisService $gitAnalysisService, UserRepository $userRepository, string $service)
    {
        $this->gitRepository = $gitRepository;
        $this->gitTokenService = $gitTokenService;
        $this->gitAnalysisService = $gitAnalysisService;
        $this->userRepository = $userRepository;
        $this->service = $service;
    }

    abstract protected function authenticate(string $token, string $url): void;

    abstract protected function fetchRepositories(): array;

    abstract protected function fetchCommits(string $repoName, string $branch): array;

    abstract protected function fetchCommitDetails(string $sha, string $repoName): array;

    abstract protected function storeRepository(array $repository, int $gitTokenId, int $hookId): int;

    abstract protected function storeCommit(array $commit, array $commitDetails, int $repositoryId): int;

    abstract protected function listWebhooks(string $repoName): array;

    abstract protected function createWebhook(string $repoName, string $defaultBranch): array;

    abstract protected function updateWebhookStatus(string $repoName, int $hookId, bool $active, int $repositoryId): array;

    abstract protected function handleEvent(string $event, int $hookId, array $data): void;

    abstract protected function getRepositoryOwner(array $repository): string;

    abstract protected function getRepositoryPath(array $repository): string;

    abstract protected function getCommitIdentifier(array $commit): string;

    abstract protected function processCommit(array $commit, array $commitDetails): array;

    abstract protected function getCommitSummaries(array $commit, array $commitDetails): string;

    public function fetchGitTokens(): array
    {
        return $this->gitTokenService->list(service: $this->service);
    }

    public function getRepository(int $gitTokenId, string $owner, string $name): array
    {
        $repository = $this->gitRepository->getRepository($gitTokenId, $owner, $name);

        if (!$repository) {
            return [];
        }

        return [
            'id' => $repository->getId(),
            'git_token_id' => $repository->getGitTokenId(),
            'name' => $repository->getName(),
            'url' => $repository->getUrl(),
            'description' => $repository->getDescription(),
            'owner' => $repository->getOwner(),
            'default_branch' => $repository->getDefaultBranch(),
            'hook_id' => $repository->getHookId(),
            'is_active' => $repository->isActive(),
            'created_at' => $repository->getCreatedAt(),
            'last_fetched_at' => $repository->getLastFetchedAt()
        ];
    }

    public function getCommit(int $repositoryId, string $sha): array
    {
        $commit = $this->gitRepository->getCommit($repositoryId, $sha);

        if (!$commit) {
            return [];
        }

        return [
            'id' => $commit->getId(),
            'repository_id' => $commit->getRepositoryId(),
            'sha' => $commit->getSha(),
            'author' => $commit->getAuthor(),
            'message' => $commit->getMessage(),
            'date' => $commit->getDate(),
            'additions' => $commit->getAdditions(),
            'deletions' => $commit->getDeletions(),
            'total' => $commit->getTotal(),
            'files' => json_decode($commit->getFiles(), true)
        ];
    }

    public function updateRepositoryFetchedAt(int $repositoryId): void
    {
        $this->gitRepository->updateRepositoryFetchedAt($repositoryId);
    }

    public function updateTokenFetchedAt(int $gitTokenId): void
    {
        $this->gitTokenService->updateFetchedAt($gitTokenId);
    }

    public function fetchAll()
    {
        $gitTokens = $this->fetchGitTokens();

        foreach ($gitTokens as $gitToken) {

            if (!$gitToken['is_active']) {
                continue;
            }

            $this->authenticate($gitToken['token'], $gitToken['url']);
            $repositories = $this->fetchRepositories();

            foreach ($repositories as $repository) {
                $repoOwner = $this->getRepositoryOwner($repository);
                $repoPath = $this->getRepositoryPath($repository);

                $repo = $this->getRepository($gitToken['id'], $repoOwner, $repository['name']);

                if ($repo && !$repo['is_active']) {
                    continue;
                }

                $repositoryId = $repo['id'] ?? 0;

                if (!$repositoryId) {
                    $hookId = 0;

                    $hooks = $this->listWebhooks($repoPath);
                    foreach ($hooks as $hook) {
                        if ($hook['name'] === 'web') {
                            $hookId = $hook['id'];
                            break;
                        }
                    }

                    if (!$hookId) {
                        $hook = $this->createWebhook($repoPath, $repository['default_branch']);
                        $hookId = $hook['id'];
                    }

                    $repositoryId = $this->storeRepository($repository, $gitToken['id'], $hookId);
                    $this->updateWebhookStatus($repoPath, $hookId, true, $repositoryId);
                }

                $commits = $this->fetchCommits($repoPath, $repository['default_branch']);
                foreach ($commits as $commit) {
                    $commitIdentifier = $this->getCommitIdentifier($commit);
                    if (!$this->getCommit($repositoryId, $commitIdentifier)) {
                        $commitDetails = $this->fetchCommitDetails($commitIdentifier, $repoPath);
                        $commitDetails = $this->processCommit($commit, $commitDetails);
                        $commitId = $this->storeCommit($commit, $commitDetails, $repositoryId);
                    }
                }

                $this->updateRepositoryFetchedAt($repositoryId);
            }

            $this->updateTokenFetchedAt($gitToken['id']);
        }

        // TODO: Send email
        // $this->sendSyncAleartEmail($repository, $gitToken, $commitDetails);
    }

    public function handlePushEvent(Repository $repository, GitToken $gitToken, string $repoPath): void
    {
        $gitTokenId = $gitToken->getId();
        $repositoryId = $repository->getId();
        $defaultBranch = $repository->getDefaultBranch();

        $this->authenticate($gitToken->getToken(), $gitToken->getUrl());

        $commits = $this->fetchCommits($repoPath, $defaultBranch);
        $commitSummaries = [];
        foreach ($commits as $commit) {
            $commitIdentifier = $this->getCommitIdentifier($commit);
            if (!$this->getCommit($repositoryId, $commitIdentifier)) {
                $commitDetails = $this->fetchCommitDetails($commitIdentifier, $repoPath);
                $commitDetails = $this->processCommit($commit, $commitDetails);
                $this->storeCommit($commit, $commitDetails, $repositoryId);
                $commitSummaries[] = $this->getCommitSummaries($commit, $commitDetails);
            }
        }

        $this->updateRepositoryFetchedAt($repositoryId);

        $this->updateTokenFetchedAt($gitTokenId);

        $this->sendActivityAlertEmail($repository, $gitToken, $commitSummaries);
    }

    public function sendActivityAlertEmail(Repository $repository, GitToken $gitToken, array $commitSummaries): void
    {

        $templatePath = __DIR__ . '/../Templates/real_time_activity_alert.txt';
        $commitSummariesFormatted = '<ul><li>' . implode('</li><li>', $commitSummaries) . '</li></ul>';

        $template = str_replace(
            ['[Repository Name]', '[Default Branch Name]', '[Commit Summaries]', '[Your Name or Team Name]'],
            [$repository->getName(), $repository->getDefaultBranch(), $commitSummariesFormatted, $_ENV['APP_NAME']],
            file_get_contents($templatePath)
        );

        // Extract the subject and body using more streamlined methods
        list($subject, $body) = $this->extractSubjectAndBody($template);
        $user = $this->userRepository->getUserById($gitToken->getUserId());

        // Send the email
        $mailer = Mailer::getInstance();
        $mailer->sendEmail($user->getEmail(), $subject, nl2br($body)); // Convert newlines to <br> in the body
    }

    private function extractSubjectAndBody(string $template): array
    {
        // Extract the subject
        preg_match('/^Subject:\s*(.+)$/m', $template, $subjectMatches);
        $subject = trim($subjectMatches[1]);

        // Extract the body content after the "Body:" line
        $body = trim(preg_replace('/^Subject:.*?^Body:\s*/ms', '', $template));

        return [$subject, $body];
    }
}
