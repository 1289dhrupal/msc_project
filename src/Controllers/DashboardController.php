<?php

declare(strict_types=1);

namespace MscProject\Controllers;

use MscProject\Models\Response\Response;
use MscProject\Models\Response\SuccessResponse;
use MscProject\Repositories\GitRepository;
use MscProject\Services\UserService;
use MscProject\Utils;
use DateTime;

class DashboardController
{
    private GitRepository $gitRepository;

    public function __construct(GitRepository $gitRepository)
    {
        $this->gitRepository = $gitRepository;
    }

    public function overallStats(): Response
    {
        global $userSession;

        $repoID = intval($_GET['repository_id'] ?? 0);
        $commits = $this->gitRepository->listCommits($repoID, $userSession->getId());
        $response = [];

        foreach ($commits as $commit) {
            // Split the date and time
            $dateTime = explode(' ', $commit->getDate());
            $time = explode(':', $dateTime[1]);

            // Get year and month number from the commit date
            $date = new DateTime($commit->getDate());
            $year = intval($date->format('Y'));
            $month = intval($date->format('m'));

            // Initialize monthly stats array for the specific year if not set
            if (!isset($response['monthly_stats'][$year])) {
                $response['monthly_stats'][$year] = array_fill(0, 13, 0); // 0-52 months
            }

            // Update the monthly stats
            $response['monthly_stats'][$year][$month] += 1;

            // Update hourly stats
            $response['hourly_stats'][(int)$time[0]] = ($response['hourly_stats'][(int)$time[0]] ?? 0) + 1;

            // Process file changes
            $files = json_decode($commit->getFiles(), true)['files'];
            $codeChanges = 0;
            foreach ($files as $file) {
                if (Utils::isCodeFile($file['filename'], $file['changes'])) {
                    $codeChanges += $file['changes'];
                }
            }

            // Update user stats
            $response['user_stats'][$commit->getAuthor()] = [
                'count' => ($response['user_stats'][$commit->getAuthor()]['count'] ?? 0) + 1,
                'total_changes' => ($response['user_stats'][$commit->getAuthor()]['total_changes'] ?? 0) + $commit->getTotal(),
                'code_changes' => ($response['user_stats'][$commit->getAuthor()]['code_changes'] ?? 0) + $codeChanges,
            ];

            // Update repository stats
            $response['repository_stats'][$commit->getRepositoryId()] = [
                'total_changes' => ($response['repository_stats'][$commit->getRepositoryId()]['total_changes'] ?? 0) + $commit->getTotal(),
                'code_changes' => ($response['repository_stats'][$commit->getRepositoryId()]['code_changes'] ?? 0) + $codeChanges,
            ];
        }

        if (isset($response['hourly_stats']))       ksort($response['hourly_stats']);
        if (isset($response['monthly_stats']))      ksort($response['monthly_stats']);
        if (isset($response['repository_stats']))   ksort($response['repository_stats']);

        return new SuccessResponse('Success', $response, 200);
    }
}
