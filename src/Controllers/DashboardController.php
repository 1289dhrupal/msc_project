<?php

declare(strict_types=1);

namespace MscProject\Controllers;

use MscProject\Models\Response\Response;
use MscProject\Models\Response\SuccessResponse;
use MscProject\Repositories\GitRepository;
use MscProject\Utils;
use DateTime;
use Exception;
use ErrorException;

class DashboardController
{
    private GitRepository $gitRepository;

    public function __construct(GitRepository $gitRepository)
    {
        $this->gitRepository = $gitRepository;
    }

    public function overallStats(): Response
    {
        try {

            global $userSession;
            $repoID = intval($_GET['repository_id'] ?? 0);

            $commits = $this->gitRepository->listCommits($repoID, $userSession->getId());

            // Initialize response structure
            $response = [
                'monthly_stats' => [],
                'hourly_stats' => array_fill(0, 24, 0),
                'user_stats' => [],
                'repository_stats' => []
            ];

            foreach ($commits as $commit) {
                // Extract date and time components
                $date = new DateTime($commit->getDate());
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
                foreach ($commit->getFiles() as $file) {
                    if (Utils::isCodeFile($file->getFilename(), $file->getTotal())) {
                        $codeChanges += $file->getTotal();
                    }
                }

                // Update user stats
                $author = $commit->getAuthor();
                if (!isset($response['user_stats'][$author])) {
                    $response['user_stats'][$author] = ['count' => 0, 'total_changes' => 0, 'code_changes' => 0];
                }
                $response['user_stats'][$author]['count']++;
                $response['user_stats'][$author]['total_changes'] += $commit->getTotal();
                $response['user_stats'][$author]['code_changes'] += $codeChanges;

                // Update repository stats
                $repositoryId = $commit->getRepositoryId();
                if (!isset($response['repository_stats'][$repositoryId])) {
                    $response['repository_stats'][$repositoryId] = ['total_changes' => 0, 'code_changes' => 0];
                }
                $response['repository_stats'][$repositoryId]['total_changes'] += $commit->getTotal();
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
