<?php

declare(strict_types=1);

namespace MscProject\Services;

use MscProject\Repositories\ActivityRepository;
use MscProject\Mailer;

class ActivityService
{
    private ActivityRepository $activityRepository;

    public function __construct(ActivityRepository $activityRepository)
    {
        $this->activityRepository = $activityRepository;
    }

    public function generateReports(): void
    {
        $intervals = [
            '1_week' => '1 WEEK',
            '1_fortnight' => '2 WEEK',
            '1_month' => '1 MONTH',
        ];

        $users = $this->activityRepository->fetchUsersWithTokens();

        foreach ($users as $user) {
            $userId = $user['user_id'];
            $email = $user['email'];
            $gitTokenId = $user['git_token_id'];

            $csvFiles = [];

            foreach ($intervals as $key => $interval) {
                $repositories = $this->activityRepository->fetchInactiveRepositories($gitTokenId, $interval);
                $filename = "inactive_repositories_{$userId}_{$key}.csv";
                $file = fopen($filename, 'w');

                // Write CSV headers
                fputcsv($file, ['ID', 'Name', 'Owner', 'Last Activity']);

                // Write repository data
                foreach ($repositories as $repo) {
                    fputcsv($file, [$repo['id'], $repo['name'], $repo['owner'], $repo['last_activity']]);
                }

                fclose($file);
                $csvFiles[$key] = $filename;
            }

            // Send email with CSV attachments using the Mailer class
            $mailer = Mailer::getInstance();
            $subject = 'Inactive Repositories Report';
            $body = 'Attached are the reports of repositories with no activity in the last 1 week, 1 fortnight, and 1 month.';

            $mailer->sendEmail($email, $subject, $body, $csvFiles);

            // Cleanup
            foreach ($csvFiles as $filename) {
                unlink($filename);
            }
        }
    }
}
