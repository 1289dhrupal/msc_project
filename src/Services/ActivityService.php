<?php

declare(strict_types=1);

namespace MscProject\Services;

use MscProject\Repositories\ActivityRepository;
use MscProject\Repositories\UserRepository;
use MscProject\Mailer;
use ErrorException;

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
            if (!$alerts->getInactivity()) {
                continue;
            }

            try {
                $csvFiles = $this->generateCsvReportsForUser($user, $intervals);
                $this->sendEmailWithReports($user['email'], $csvFiles);
            } catch (ErrorException $e) {
                // Log the exception or handle it as necessary
                // Log::error("Failed to generate/send reports for user {$user['user_id']}: " . $e->getMessage());
            } finally {
                $this->cleanupFiles($csvFiles ?? []);
            }
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

        if (!$file) {
            throw new ErrorException("Failed to create CSV file: $filename", 500);
        }

        // Write CSV headers
        if (fputcsv($file, ['ID', 'Name', 'Owner', 'Last Activity']) === false) {
            throw new ErrorException("Failed to write to CSV file: $filename", 500);
        }

        // Write repository data
        foreach ($repositories as $repo) {
            if (fputcsv($file, [$repo['id'], $repo['name'], $repo['owner'], $repo['last_activity']]) === false) {
                throw new ErrorException("Failed to write to CSV file: $filename", 500);
            }
        }

        fclose($file);

        return $filename;
    }

    private function sendEmailWithReports(string $email, array $csvFiles): void
    {
        $subject = 'Inactive Repositories Report';
        $body = 'Attached are the reports of repositories with no activity in the last 1 week, 1 fortnight, and 1 month.';

        $mailer = Mailer::getInstance();
        if (!$mailer->sendEmail($email, $subject, $body, $csvFiles)) {
            throw new ErrorException("Failed to send email to $email", 500);
        }
    }

    private function cleanupFiles(array $csvFiles): void
    {
        foreach ($csvFiles as $filename) {
            if (file_exists($filename) && !unlink($filename)) {
                // Log this as a warning or handle it as necessary
                throw new ErrorException("Failed to delete file: $filename", 500);
            }
        }
    }
}
