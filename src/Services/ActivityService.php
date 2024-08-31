<?php

declare(strict_types=1);

namespace MscProject\Services;

use MscProject\Repositories\ActivityRepository;
use MscProject\Mailer;
use MscProject\Repositories\UserRepository;

class ActivityService
{
    private ActivityRepository $activityRepository;
    private UserRepository $userRepository;

    public function __construct(ActivityRepository $activityRepository, UserRepository $userRepository)
    {
        $this->activityRepository = $activityRepository;
        $this->userRepository = $userRepository;
    }

    public function generateReports(): void
    {
        $intervals = $this->getIntervals();
        $users = $this->activityRepository->fetchUsersWithTokens();

        foreach ($users as $user) {
            $alerts = $this->userRepository->getUserAlerts($user['user_id']);
            if ($alerts->getInactivity() === false) {
                continue;
            }
            $csvFiles = $this->generateCsvReportsForUser($user, $intervals);

            $this->sendEmailWithReports($user['email'], $csvFiles);
            $this->cleanupFiles($csvFiles);
        }
    }

    private function getIntervals(): array
    {
        return [
            '1_week' => '1 WEEK',
            '1_fortnight' => '2 WEEK',
            '1_month' => '1 MONTH',
        ];
    }

    private function generateCsvReportsForUser(array $user, array $intervals): array
    {
        $userId = $user['user_id'];
        $gitTokenId = $user['git_token_id'];

        $csvFiles = [];

        foreach ($intervals as $key => $interval) {
            $repositories = $this->activityRepository->fetchInactiveRepositories($gitTokenId, $interval);
            $filename = $this->createCsvFile($userId, $key, $repositories);
            $csvFiles[$key] = $filename;
        }

        return $csvFiles;
    }

    private function createCsvFile(int $userId, string $intervalKey, array $repositories): string
    {
        $filename = "inactive_repositories_{$userId}_{$intervalKey}.csv";
        $file = fopen($filename, 'w');

        // Write CSV headers
        fputcsv($file, ['ID', 'Name', 'Owner', 'Last Activity']);

        // Write repository data
        foreach ($repositories as $repo) {
            fputcsv($file, [$repo['id'], $repo['name'], $repo['owner'], $repo['last_activity']]);
        }

        fclose($file);

        return $filename;
    }

    private function sendEmailWithReports(string $email, array $csvFiles): void
    {
        $mailer = Mailer::getInstance();
        $subject = 'Inactive Repositories Report';
        $body = 'Attached are the reports of repositories with no activity in the last 1 week, 1 fortnight, and 1 month.';

        $mailer->sendEmail($email, $subject, $body, $csvFiles);
    }

    private function cleanupFiles(array $csvFiles): void
    {
        foreach ($csvFiles as $filename) {
            unlink($filename);
        }
    }
}
