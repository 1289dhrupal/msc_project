<?php

declare(strict_types=1);

namespace MscProject\Services;

use MscProject\Mailer;
use MscProject\Models\GitToken;
use MscProject\Models\Repository;
use MscProject\Repositories\GitRepository;
use MscProject\Repositories\UserRepository;
use MscProject\Services\GitTokenService;
use MscProject\Utils;
use Exception;
use ErrorException;

abstract class GitProviderService
{
    protected string $username;
    protected string $service;
    protected GitRepository $gitRepository;
    protected GitTokenService $gitTokenService;
    protected GitAnalysisService $gitAnalysisService;
    protected UserRepository $userRepository;

    public function __construct(
        GitTokenService $gitTokenService,
        GitRepository $gitRepository,
        GitAnalysisService $gitAnalysisService,
        UserRepository $userRepository,
        string $service
    ) {
        $this->gitRepository = $gitRepository;
        $this->gitTokenService = $gitTokenService;
        $this->gitAnalysisService = $gitAnalysisService;
        $this->userRepository = $userRepository;
        $this->service = $service;
    }

    abstract protected function authenticate(string $token, string $url): bool;

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

    public function fetchGitTokens(array $gitTokenIds = []): array
    {
        return $this->gitTokenService->list(service: $this->service, gitTokenIds: $gitTokenIds);
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
            'files' => $commit->getFiles()
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

    public function syncAll(int $repositoryIdToSync, int $gitTokenIdToSync): array
    {
        if ($repositoryIdToSync) {
            $gitTokenIdToSync = $this->gitRepository->getRepositoryById($repositoryIdToSync)->getGitTokenId();
        }

        if ($gitTokenIdToSync) {
            $gitTokens = $this->fetchGitTokens([$gitTokenIdToSync]);
        } else {
            $gitTokens = $this->fetchGitTokens();
        }
        echo "git tokens: " . json_encode($gitTokens) . "\n";

        $summary = [];

        foreach ($gitTokens as $gitToken) {
            if (!$gitToken['is_active']) {
                continue;
            }

            try {

                if (!$this->authenticate($gitToken['token'], $gitToken['url'])) {
                    $this->gitTokenService->toggle($gitToken['id'], false);
                    continue;
                }

                $repositories = $this->fetchRepositories();

                $repositorySummaries = [];
                foreach ($repositories as $repository) {
                    $repoOwner = $this->getRepositoryOwner($repository);
                    $repoPath = $this->getRepositoryPath($repository);

                    $repo = $this->getRepository($gitToken['id'], $repoOwner, $repository['name']);

                    if ($repo && !$repo['is_active']) {
                        continue;
                    }

                    if ($repositoryIdToSync && $repo['id'] !== $repositoryIdToSync) {
                        continue;
                    }

                    $repositoryId = $repo['id'] ?? 0;

                    if (!$repositoryId) {
                        $hookId = $this->manageWebhooks($repository, $repoPath, $gitToken['id']);
                        $repositoryId = $this->storeRepository($repository, $gitToken['id'], $hookId);
                        $this->updateWebhookStatus($repoPath, $hookId, true, $repositoryId);
                    }

                    $commitCount = $this->syncCommits($repositoryId, $repoPath, $repository['default_branch']);
                    $this->updateRepositoryFetchedAt($repositoryId);
                    $repositorySummaries[] = [
                        'owner' => $repoOwner,
                        'path' => $repoPath,
                        'name' => $repository['name'],
                        'commit_count' => $commitCount
                    ];
                }

                $this->updateTokenFetchedAt($gitToken['id']);
                $current_summary = [
                    'user_id' => $gitToken['user_id'],
                    'git_token' => Utils::maskToken($gitToken['token']),
                    'url' => $gitToken['url'],
                    'repositories' => $repositorySummaries
                ];

                $summary[] = $current_summary;

                $this->sendSyncAlertEmail($gitToken, $current_summary);
            } catch (Exception $e) {
                throw new ErrorException("Failed to sync repositories for token {$gitToken['id']}: " . $e->getMessage());
            }
        }

        return $summary;
    }

    private function manageWebhooks(array $repository, string $repoPath, int $gitTokenId): int
    {
        $hooks = $this->listWebhooks($repoPath);
        foreach ($hooks as $hook) {
            if ($hook['name'] === 'web') {
                return $hook['id'];
            }
        }

        $hook = $this->createWebhook($repoPath, $repository['default_branch']);
        return $hook['id'];
    }

    private function syncCommits(int $repositoryId, string $repoPath, string $defaultBranch): int
    {
        $commitCount = 0;
        $commits = $this->fetchCommits($repoPath, $defaultBranch);

        foreach ($commits as $commit) {
            $commitIdentifier = $this->getCommitIdentifier($commit);
            if (!$this->getCommit($repositoryId, $commitIdentifier)) {
                $commitDetails = $this->fetchCommitDetails($commitIdentifier, $repoPath);
                $commitDetails = $this->processCommit($commit, $commitDetails);
                $this->storeCommit($commit, $commitDetails, $repositoryId);
                $commitCount++;
            }
        }

        return $commitCount;
    }

    public function handlePushEvent(Repository $repository, GitToken $gitToken, string $repoPath): void
    {
        try {
            if (!$this->authenticate($gitToken->getToken(), $gitToken->getUrl())) {
                $this->gitTokenService->toggle($gitToken->getId(), false);
                return;
            }

            $commits = $this->fetchCommits($repoPath, $repository->getDefaultBranch());
            $commitSummaries = [];

            foreach ($commits as $commit) {
                $commitIdentifier = $this->getCommitIdentifier($commit);
                if (!$this->getCommit($repository->getId(), $commitIdentifier)) {
                    $commitDetails = $this->fetchCommitDetails($commitIdentifier, $repoPath);
                    $commitDetails = $this->processCommit($commit, $commitDetails);
                    $this->storeCommit($commit, $commitDetails, $repository->getId());
                    $commitSummaries[] = $this->getCommitSummaries($commit, $commitDetails);
                }
            }

            $this->updateRepositoryFetchedAt($repository->getId());
            $this->updateTokenFetchedAt($gitToken->getId());
            $this->sendActivityAlertEmail($repository, $gitToken, $commitSummaries);
        } catch (Exception $e) {
            throw new ErrorException("Failed to handle push event for repository {$repository->getId()}: " . $e->getMessage());
        }
    }

    private function sendActivityAlertEmail(Repository $repository, GitToken $gitToken, array $commitSummaries): void
    {
        if (empty($commitSummaries)) {
            return;
        }

        $alerts = $this->userRepository->getUserAlerts($gitToken->getUserId());
        if (!$alerts || !$alerts->getRealtime()) {
            return;
        }

        $templatePath = __DIR__ . '/../Templates/real_time_activity_alert.txt';
        $commitSummariesFormatted = '<ul><li>' . implode('</li><li>', $commitSummaries) . '</li></ul>';

        $template = str_replace(
            ['[Repository Name]', '[Default Branch Name]', '[Commit Summaries]', '[Your Name or Team Name]'],
            [$repository->getName(), $repository->getDefaultBranch(), $commitSummariesFormatted, $_ENV['APP_NAME']],
            file_get_contents($templatePath)
        );

        list($subject, $body) = $this->extractSubjectAndBody($template);
        $user = $this->userRepository->getUserById($gitToken->getUserId());

        $mailer = Mailer::getInstance();
        $mailer->sendEmail($user->getEmail(), $subject, nl2br($body));
    }

    private function sendSyncAlertEmail(array $gitToken, array $summary): void
    {
        if (empty($summary['repositories']) || array_sum(array_column($summary['repositories'], 'commit_count')) === 0) {
            return;
        }

        $alerts = $this->userRepository->getUserAlerts($gitToken['user_id']);
        if (!$alerts || !$alerts->getSync()) {
            return;
        }

        $repositorySummaries = '';
        $repositorySummaries .= sprintf(
            "Git Token: %s<br>URL: %s<br><br>Repositories:<br>",
            $summary['git_token'],
            $summary['url']
        );

        foreach ($summary['repositories'] as $repo) {
            $repositorySummaries .= sprintf(
                "<li>Repository: %s, Commit Count: %d<li>",
                $repo['name'],
                (int)$repo['commit_count']
            );
        }

        $repositorySummaries .= "-----------------------------<br>";

        $templatePath = __DIR__ . '/../Templates/token_sync.txt';
        $template = str_replace(
            ['[Repository Summaries]', '[Your Name or Team Name]'],
            [$repositorySummaries, $_ENV['APP_NAME']],
            file_get_contents($templatePath)
        );

        list($subject, $body) = $this->extractSubjectAndBody($template);
        $user = $this->userRepository->getUserById($summary['user_id']);

        $mailer = Mailer::getInstance();
        $mailer->sendEmail($user->getEmail(), $subject, nl2br($body));
    }

    private function extractSubjectAndBody(string $template): array
    {
        preg_match('/^Subject:\s*(.+)$/m', $template, $subjectMatches);
        $subject = trim($subjectMatches[1]);

        $body = trim(preg_replace('/^Subject:.*?^Body:\s*/ms', '', $template));

        return [$subject, $body];
    }
}
