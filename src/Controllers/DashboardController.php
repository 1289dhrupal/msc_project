<?php

declare(strict_types=1);

namespace MscProject\Controllers;

use MscProject\Models\Response\Response;
use MscProject\Models\Response\SuccessResponse;
use MscProject\Services\GitService;
use MscProject\Utils;
use DateTime;
use Exception;
use ErrorException;

class DashboardController
{
    private GitService $gitService;

    public function __construct(GitService $gitService)
    {
        $this->gitService = $gitService;
    }

    public function overallStats(): Response
    {
        try {

            global $userSession;
            $repoID = intval($_GET['repository_id'] ?? 0);

            $response = $this->gitService->listCommits($repoID, $userSession->getId(), true, 0);
            $commits = $response['commits'];

            // Initialize response structure
            $response = [
                'monthly_stats' => [],
                'hourly_stats' => array_fill(0, 24, 0),
                'user_stats' => [],
                'repository_stats' => []
            ];

            foreach ($commits as $commit) {
                // Extract date and time components
                $date = new DateTime($commit['date']);
                $year = (int) $date->format('Y');
                $month = (int) $date->format('m');
                $hour = (int) $date->format('H');

                // Update monthly stats
                if (!isset($response['monthly_stats'][$year])) {
                    $response['monthly_stats'][$year] = array_fill(0, 12, 0);
                }
                $response['monthly_stats'][$year][$month - 1]++;

                // Update hourly stats
                $response['hourly_stats'][$hour]++;

                // Process file changes
                $codeChanges = 0;
                foreach ($commit['files'] as $file) {
                    if (Utils::isCodeFile($file['filename'], $file['total'])) {
                        $codeChanges += $file['total'];
                    }
                }

                // Update user stats
                $author = $commit['author'];
                if (!isset($response['user_stats'][$author])) {
                    $response['user_stats'][$author] = ['count' => 0, 'total_changes' => 0, 'code_changes' => 0];
                }
                $response['user_stats'][$author]['count']++;
                $response['user_stats'][$author]['total_changes'] += $commit['total'];
                $response['user_stats'][$author]['code_changes'] += $codeChanges;

                // Update repository stats
                $repositoryId = $commit['repository_id'];
                if (!isset($response['repository_stats'][$repositoryId])) {
                    $response['repository_stats'][$repositoryId] = ['total_changes' => 0, 'code_changes' => 0];
                }
                $response['repository_stats'][$repositoryId]['total_changes'] += $commit['total'];
                $response['repository_stats'][$repositoryId]['code_changes'] += $codeChanges;
            }

            // Sort the results for consistency
            ksort($response['monthly_stats']);
            ksort($response['hourly_stats']);
            ksort($response['repository_stats']);

            return new SuccessResponse('Success', $response, 200);
        } catch (Exception $e) {
            throw new ErrorException('Failed to fetch stats', 400, E_USER_WARNING, previous: $e);
        }
    }
}
